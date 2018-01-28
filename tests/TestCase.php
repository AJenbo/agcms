<?php namespace AGCMS\Tests;

use AGCMS\Application;
use AGCMS\Config;
use AGCMS\DB;
use AGCMS\Entity\User;
use AGCMS\Request;
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
        Config::load(__DIR__ . '/application');

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
     * Visit the given URI with a GET request.
     *
     * @param string $uri
     * @param array  $headers
     *
     * @return $this
     */
    public function get($uri, array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        return $this->call('GET', $uri, [], [], [], $server);
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param string $method
     * @param string $uri
     * @param array  $parameters
     * @param array  $cookies
     * @param array  $files
     * @param array  $server
     * @param string $content
     *
     * @return TestResponse
     */
    public function call(
        $method,
        $uri,
        $parameters = [],
        $cookies = [],
        $files = [],
        $server = [],
        $content = null
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
     * @param array $headers
     *
     * @return array
     */
    protected function transformHeadersToServerVars(array $headers)
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
}
