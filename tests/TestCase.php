<?php

namespace Tests;

use App\Application;
use App\Http\Request;
use App\Models\User;
use App\Services\ConfigService;
use App\Services\DbService;
use DateTime;
use DateTimeZone;
use Exception;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    protected Application $app;
    private ?User $user;
    private string $currentUri;

    /**
     * Initiate the database, config and application.
     */
    protected function setUp(): void
    {
        $this->user = null;

        // Initialize configuration
        ConfigService::load(__DIR__ . '/application');

        // Initialize application
        $this->app = new Application(__DIR__ . '/../application');

        $db = app(DbService::class);

        // Load schema and seed data
        $sql = file_get_contents(__DIR__ . '/fixtures/schema_sqlite.sql');
        $sql .= file_get_contents(__DIR__ . '/fixtures/seed.sql');
        $queries = explode(';', $sql);
        foreach ($queries as $query) {
            $db->query($query);
        }
    }

    /**
     * Convert a timastamp to a string appropriate for the HTTP header.
     *
     * @throws Exception
     */
    public function timeToHeader(int $timestamp): string
    {
        // Set the call one hour in to the feature to make sure the data is older
        $lastModified = DateTime::createFromFormat('U', (string)$timestamp, new DateTimeZone('GMT'));
        if ($lastModified === false) {
            throw new Exception('Unable to parse timestamp: ' . $timestamp);
        }

        $lastModified = $lastModified->format('r');

        return mb_substr($lastModified, 0, -5) . 'GMT';
    }

    /**
     * Visit the given URI with a HEAD request.
     *
     * @param string[] $headers
     */
    public function head(string $uri, array $headers = []): TestResponse
    {
        $server = $this->transformHeadersToServerVars($headers);

        return $this->call('HEAD', $uri, [], [], [], $server);
    }

    /**
     * Visit the given URI with a GET request.
     *
     * @param string[] $headers
     */
    public function get(string $uri, array $headers = []): TestResponse
    {
        $server = $this->transformHeadersToServerVars($headers);

        return $this->call('GET', $uri, [], [], [], $server);
    }

    /**
     * Visit the given URI with a POST request.
     *
     * @param string[] $data
     * @param string[] $headers
     */
    public function post(string $uri, array $data = [], array $headers = []): TestResponse
    {
        $server = $this->transformHeadersToServerVars($headers);

        return $this->call('POST', $uri, $data, [], [], $server);
    }

    /**
     * Call the given URI with a JSON request.
     *
     * @param mixed[]  $data
     * @param string[] $headers
     */
    public function json(string $method, string $uri, array $data = [], array $headers = []): TestResponse
    {
        $content = json_encode($data) ?: null;
        $headers = array_merge([
            'CONTENT_LENGTH' => (string)mb_strlen($content ?: '', '8bit') ?: '0',
            'CONTENT_TYPE'   => 'application/json',
            'Accept'         => 'application/json',
        ], $headers);

        return $this->call($method, $uri, [], [], [], $this->transformHeadersToServerVars($headers), $content);
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param string[]       $parameters
     * @param string[]       $cookies
     * @param UploadedFile[] $files
     * @param string[]       $server
     */
    public function call(
        string $method,
        string $uri,
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null
    ): TestResponse {
        $this->currentUri = ConfigService::getString('base_url') . $uri;
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
            $name = strtr(mb_strtoupper($name), '-', '_');
            if (false === mb_strpos($name, $prefix) && 'CONTENT_TYPE' !== $name) {
                $name = $prefix . $name;
            }
            $server[$name] = $value;
        }

        return $server;
    }

    /**
     * Set the User making the request.
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
     * @param array<string, null|float|int|string> $data
     *
     * @return $this
     */
    protected function assertDatabaseHas(string $table, array $data): self
    {
        $message = sprintf(
            'Failed asserting that a row in the table [%s] matches the attributes %s.',
            $table,
            json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );

        static::assertTrue($this->isInDatabase($table, $data), $message);

        return $this;
    }

    /**
     * Assert that a given where condition does not exist in the database.
     *
     * @param array<string, null|float|int|string> $data
     *
     * @return $this
     */
    protected function assertDatabaseMissing(string $table, array $data): self
    {
        $message = sprintf(
            'Failed asserting that no row in the table [%s] matches the attributes %s.',
            $table,
            json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );

        static::assertFalse($this->isInDatabase($table, $data), $message);

        return $this;
    }

    /**
     * Test if a given where condition exists in the database.
     *
     * @param array<string, null|float|int|string> $data
     */
    private function isInDatabase(string $table, array $data): bool
    {
        $db = app(DbService::class);

        $sets = [];
        foreach ($data as $filedName => $value) {
            if (null === $value) {
                $sets[] = '`' . $filedName . '` IS NULL';

                continue;
            }

            $sets[] = '`' . $filedName . '` = ' . $db->quote((string)$value);
        }
        $query = 'SELECT * FROM `' . $table . '` WHERE ' . implode(' AND ', $sets);

        return (bool)$db->fetchOne($query);
    }
}
