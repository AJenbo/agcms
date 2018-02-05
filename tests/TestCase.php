<?php namespace Tests;

use App\Application;
use App\Services\ConfigService;
use App\Models\User;
use App\Http\Request;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class TestCase extends BaseTestCase
{
    /** @var Application */
    protected $app;

    /** @var User|null */
    private $user;

    /**
     * Initiate the database, config and application.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->user = null;

        // Initialize configuration
        ConfigService::load(__DIR__ . '/application');

        // Initialize application
        $this->app = new Application(__DIR__ . '/../application');

        // Load schema and seed data
        $sql = file_get_contents(__DIR__ . '/fixtures/schema_sqlite.sql');
        $sql .= file_get_contents(__DIR__ . '/fixtures/seed.sql');
        $queries = explode(';', $sql);
        foreach ($queries as $query) {
            app('db')->query($query);
        }
    }

    /**
     * Convert a timastamp to a string appropriate for the HTTP header.
     *
     * @param int $timestamp
     *
     * @return string
     */
    public function timeToHeader(int $timestamp): string
    {
        // Set the call one hour in to the feature to make sure the data is older
        $lastModified = DateTime::createFromFormat('U', (string) $timestamp, new DateTimeZone('GMT'));
        $lastModified = $lastModified->format('r');

        return mb_substr($lastModified, 0, -5) . 'GMT';
    }

    /**
     * Visit the given URI with a GET request.
     *
     * @param string   $uri
     * @param string[] $headers
     *
     * @return TestResponse
     */
    public function get(string $uri, array $headers = []): TestResponse
    {
        $server = $this->transformHeadersToServerVars($headers);

        return $this->call('GET', $uri, [], [], [], $server);
    }

    /**
     * Visit the given URI with a POST request.
     *
     * @param string   $uri
     * @param array    $data
     * @param string[] $headers
     *
     * @return $this
     */
    public function post(string $uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        return $this->call('POST', $uri, $data, [], [], $server);
    }

    /**
     * Call the given URI with a JSON request.
     *
     * @param string   $method
     * @param string   $uri
     * @param array    $data
     * @param string[] $headers
     *
     * @return TestResponse
     */
    public function json(string $method, string $uri, array $data = [], array $headers = []): TestResponse
    {
        $content = json_encode($data);
        $headers = array_merge([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE'   => 'application/json',
            'Accept'         => 'application/json',
        ], $headers);

        return $this->call($method, $uri, [], [], [], $this->transformHeadersToServerVars($headers), $content);
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param string   $method
     * @param string   $uri
     * @param string[] $parameters
     * @param string[] $cookies
     * @param array    $files
     * @param string[] $server
     * @param string   $content
     *
     * @return TestResponse
     */
    public function call(
        string $method,
        string $uri,
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        string $content = null
    ): TestResponse {
        $this->currentUri = config('base_url') . $uri;
        $request = Request::create($this->currentUri, $method, $parameters, $cookies, $files, $server, $content);
        if ($this->user) {
            $request->setUser($this->user);
        }

        return new TestResponse($this->app->handle($request));
    }

    /**
     * Transform headers array to array of $_SERVER vars with HTTP_* format.
     *
     * @param string[] $headers
     *
     * @return string[]
     */
    protected function transformHeadersToServerVars(array $headers): array
    {
        $server = [];
        $prefix = 'HTTP_';
        foreach ($headers as $name => $value) {
            $name = strtr(strtoupper($name), '-', '_');
            if (false === mb_strpos($name, $prefix) && 'CONTENT_TYPE' != $name) {
                $name = $prefix . $name;
            }
            $server[$name] = $value;
        }

        return $server;
    }

    /**
     * Set the User making the request.
     *
     * @param User $user
     *
     * @return $this
     */
    public function actingAs(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Assert that a given where condition exists in the database.
     *
     * @param string $table
     * @param array  $data
     *
     * @return $this
     */
    protected function assertDatabaseHas(string $table, array $data): self
    {
        $message = sprintf(
            'Failed asserting that a row in the table [%s] matches the attributes %s.',
            $table,
            json_encode($data, JSON_PRETTY_PRINT)
        );

        $this->assertTrue($this->getFromDatabase($table, $data), $message);

        return $this;
    }

    /**
     * Assert that a given where condition does not exist in the database.
     *
     * @param string $table
     * @param array  $data
     *
     * @return $this
     */
    protected function assertDatabaseMissing(string $table, array $data): self
    {
        $message = sprintf(
            'Failed asserting that no row in the table [%s] matches the attributes %s.',
            $table,
            json_encode($data, JSON_PRETTY_PRINT)
        );

        $this->assertFalse($this->getFromDatabase($table, $data), $message);

        return $this;
    }

    /**
     * Test if a given where condition exists in the database.
     *
     * @param string $table
     * @param array  $data
     *
     * @return bool
     */
    private function getFromDatabase(string $table, array $data): bool
    {
        $sets = [];
        foreach ($data as $filedName => $value) {
            $sets[] = '`' . $filedName . '` = ' . app('db')->quote($value);
        }
        $query = 'SELECT * FROM `' . $table . '` WHERE ' . implode(' AND ', $sets);

        return (bool) app('db')->fetchOne($query);
    }
}
