<?php namespace AGCMS\Tests;

use AGCMS\Application;
use AGCMS\Config;
use AGCMS\DB;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** @var Application */
    protected $app;

    /**
     * Initiate the database, config and application.
     *
     * @return void
     */
    protected function setUp(): void
    {
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
        $app = new Application(__DIR__ . '/application');

        // Load routes
        require_once __DIR__ . '/../application/inc/routes.php';

        $this->app = $app;
    }
}
