<?php namespace App\Services;

use App\Exceptions\Exception;
use PDO;
use PDOStatement;

class DbService
{
    /** @var int|null */
    private $timeOffset;

    /** @var ?PDO */
    private $connection;

    /** @var string */
    private $driver = 'mysql';

    /** @var bool[] */
    private $loadedTables = [];

    /** @var string */
    private $dsn = '';

    /** @var string */
    private $user = '';

    /** @var string */
    private $password = '';

    /**
     * Connect the database and set session to UTF-8 Danish.
     *
     * The MySQL and sqlite driver is supported, for other driveres it will default ot MySQL syntax
     */
    public function __construct()
    {
        $this->dsn = config('db_dns') ?: 'mysql:dbname=' . config('mysql_database') . ';host=' . config('mysql_server');
        $this->user = config('mysql_user', 'root');
        $this->password = config('mysql_password', '');
    }

    /**
     * Get connection.
     *
     * @return PDO
     */
    private function connection(): PDO
    {
        if (!$this->connection) {
            $pdo = new PDO($this->dsn, $this->user, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if (0 === mb_strpos($this->dsn, 'sqlite:')) {
                $this->driver = 'sqlite';
            }

            if ('mysql' === $this->driver) {
                $pdo->query("SET NAMES 'UTF8'");
                $pdo->query("SET SESSION character_set_server = 'UTF8'");
                $pdo->query('SET collation_server=utf8_danish_ci');
            }

            $this->connection = $pdo;
        }

        return $this->connection;
    }

    /**
     * Performe query and return result as an array of associative arrays.
     *
     * @param string $query The SQL query to preforme
     *
     * @throws \PDOException
     *
     * @return array<int, array<string|int, string>>
     */
    public function fetchArray(string $query): array
    {
        /** @var PDOStatement<int, array<string, string>> */
        $result = $this->connection()->query($query, PDO::FETCH_ASSOC);
        $rows = [];
        foreach ($result as $row) {
            $rows[] = $row;
        }
        $result->closeCursor();

        return $rows;
    }

    /**
     * Performe query and return the first result as an associative arrays.
     *
     * @param string $query The SQL query to preforme
     *
     * @return array<string|int, string>
     */
    public function fetchOne(string $query): array
    {
        $row = $this->fetchArray($query . ' LIMIT 1');
        $row = array_shift($row);

        if (!$row) {
            $row = [];
        }

        return $row;
    }

    /**
     * Performe query.
     *
     * @param string $query The query string
     *
     * @throws \PDOException
     *
     * @return int
     */
    public function query(string $query): int
    {
        $this->connection()->query($query);

        return (int) $this->connection()->lastInsertId();
    }

    /**
     * Escape all SQL wildcards.
     *
     * @param string $string String to process
     *
     * @return string
     */
    public function escapeWildcards(string $string): string
    {
        $string = preg_replace('/([%_])/u', '\\\\$1', $string);
        if (null === $string) {
            throw new Exception('preg_replace failed');
        }

        return $string;
    }

    /**
     * Escape and quate a string.
     *
     * @param string $string
     *
     * @return string
     */
    public function quote(string $string): string
    {
        return $this->connection()->quote($string);
    }

    /**
     * A string that will convert to the current datetime for the current driver.
     *
     * @return string
     */
    public function getNowValue(): string
    {
        if ('sqlite' === $this->driver) {
            return $this->connection()->quote('now');
        }

        return 'NOW()';
    }

    /**
     * A string that will convert a unixtimestam to datetime for the current driver.
     *
     * @return string
     */
    public function getDateValue(int $timestamp): string
    {
        if ('sqlite' === $this->driver) {
            return 'datetime(' . $timestamp . ', \'unixepoch\')';
        }

        return 'DATE_ADD(\'1970-01-01 00:00:00\', INTERVAL ' . ($timestamp + $this->getTimeOffset()) . ' SECOND)';
    }

    public function escNum(float $number, int $decimals = 2): string
    {
        return number_format($number, $decimals, '.', '');
    }

    /**
     * Find out what offset database local time has form UTC.
     *
     * @return int
     */
    public function getTimeOffset(): int
    {
        if (null === $this->timeOffset) {
            $sql = 'SELECT NOW() now';
            if ('sqlite' === $this->driver) {
                $sql = 'SELECT datetime(\'now\') now';
            }

            $this->timeOffset = time() - strtotime($this->fetchOne($sql)['now']);
        }

        return $this->timeOffset;
    }

    /**
     * Remember what tabels where read during page load.
     *
     * @param array<int, string> ...$tableNames The table name
     *
     * @return void
     */
    public function addLoadedTable(string ...$tableNames): void
    {
        foreach ($tableNames as $tableName) {
            $this->loadedTables[$tableName] = true;
        }
    }

    /**
     * Get update for loaded tables tables.
     *
     * @return ?int Unix time stamp, or null if no tables has ben accessed
     */
    public function dataAge(): ?int
    {
        if (!$this->loadedTables) {
            return null;
        }

        if ('sqlite' === $this->driver) {
            return time();
        }

        $tableNames = array_keys($this->loadedTables);

        $updateTime = 0;
        $sql = 'SHOW TABLE STATUS WHERE Name IN(\'' . implode('\', \'', $tableNames) . '\')';
        $tables = $this->fetchArray($sql);
        foreach ($tables as $table) {
            $updateTime = max($updateTime, strtotime($table['Update_time']) + $this->getTimeOffset());
        }

        return $updateTime;
    }
}
