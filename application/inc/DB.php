<?php namespace AGCMS;

use PDO;

class DB
{
    /** @var int */
    private static $timeOffset;
    /** @var PDO */
    private $connection;

    /**
     * Connect the database and set session to UTF-8 Danish.
     *
     * @param string $dsn
     * @param string $user
     * @param string $password
     */
    public function __construct(string $dsn, string $user, string $password)
    {
        $this->connection = new PDO($dsn, $user, $password);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->connection->query("SET NAMES 'UTF8'");
        $this->connection->query("SET SESSION character_set_server = 'UTF8'");
        $this->connection->query('SET collation_server=utf8_danish_ci');
    }

    /**
     * Performe query and return result as an array of associative arrays.
     *
     * @param string $query The SQL query to preforme
     *
     * @throws \PDOException
     *
     * @return array[]
     */
    public function fetchArray(string $query): array
    {
        $result = $this->connection->query($query, PDO::FETCH_ASSOC);
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
        $this->connection->query($query);

        return $this->connection->lastInsertId();
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
        return preg_replace('/([%_])/u', '\\\\$1', $string);
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
        return $this->connection->quote($string);
    }

    public function escNum(float $number, int $decimals = 2): string
    {
        return number_format($number, $decimals, '.', '');
    }

    /**
     * Find out what offset the on the time database has form UTC.
     *
     * @return int
     */
    public function getTimeOffset(): int
    {
        if (null === self::$timeOffset) {
            self::$timeOffset = time() - strtotime($this->fetchOne('SELECT NOW() date')['date']);
        }

        return self::$timeOffset;
    }
}
