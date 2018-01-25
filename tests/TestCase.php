<?php namespace AGCMS\Tests;

use AGCMS\Application;
use AGCMS\Config;
use AGCMS\DB;
use AGCMS\Request;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Assert;

abstract class TestCase extends BaseTestCase
{
    /** @var Application */
    protected $app;
    /** @var Response|null */
    protected $response;

    /**
     * Initiate the database, config and application.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->response = null;

        // Set the db connection
        $connection = new DB('sqlite::memory:');
        db($connection);

        // Load schema and seed data
        $sql = file_get_contents(__DIR__ . '/fixtures/schema_sqlite.sql');
        $sql .= file_get_contents(__DIR__ . '/fixtures/seed.sql');
        $queries = explode(';', $sql);
        foreach ($queries as $query) {
            db()->query($query);
        }

        // Initialize configuration
        Config::load(__DIR__ . '/application');

        // Initialize application
        $app = new Application(__DIR__ . '/../application');

        // Load routes
        require_once __DIR__ . '/../application/inc/routes.php';

        $this->app = $app;
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
     * @return void
     */
    public function call(
        $method,
        $uri,
        $parameters = [],
        $cookies = [],
        $files = [],
        $server = [],
        $content = null
    ): void {
        $this->currentUri = config('base_url') . $uri;
        $request = Request::create($this->currentUri, $method, $parameters, $cookies, $files, $server, $content);

        $this->response = $this->app->handle($request);
    }

    /**
     * Assert that the client response has a given code.
     *
     * @param  int  $code
     * @return void
     */
    public function assertResponseStatus($code)
    {
        $actual = $this->response->getStatusCode();

        return Assert::assertEquals($code, $this->response->getStatusCode(), "Expected status code {$code}, got {$actual}.");
    }
}
