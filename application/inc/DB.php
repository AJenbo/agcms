<?php namespace AGCMS;

use Exception;
use mysqli;

/**
 * @property int $insert_id
 * @property int $affected_rows
 */
class DB extends mysqli
{
    /** @var int */
    private static $timeOffset;

    /**
     * Connect the database and set session to UTF-8 Danish.
     *
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $schema
     */
    public function __construct($host, $user, $password, $schema)
    {
        parent::__construct($host, $user, $password, $schema);

        /* Throw an error if the connection fails */
        if (mysqli_connect_error()) {
            return;
        }

        $this->query("SET NAMES 'UTF8'");
        $this->query("SET SESSION character_set_server = 'UTF8'");
        $this->query('SET collation_server=utf8_danish_ci');
    }

    /**
     * Performe query and return result as an array of associative arrays.
     *
     * @param string $query The MySQL query to preforme
     *
     * @return array[]
     */
    public function fetchArray(string $query): array
    {
        $result = parent::query($query);
        if (mysqli_error($this)) {
            throw new Exception(mysqli_error($this), mysqli_errno($this));
        }
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->close();

        return $rows;
    }

    /**
     * Performe query and return the first result as an associative arrays.
     *
     * @param string $query The MySQL query to preforme
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
     * @param string   $query      The query string
     * @param int|null $resultmode
     *
     * @return bool
     */
    public function query($query, $resultmode = null): bool
    {
        parent::query($query, $resultmode);
        if (mysqli_error($this)) {
            throw new Exception(mysqli_error($this), mysqli_errno($this));
        }

        return true;
    }

    /**
     * Escape all MySQL wildcards.
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
     * Escape a string for concatting in squery string.
     *
     * @param string $string The sting to escape
     *
     * @return string
     */
    public function esc(string $string): string
    {
        return parent::real_escape_string($string);
    }

    /**
     * Escape and quate a string.
     *
     * @param string $string
     *
     * @return string
     */
    public function eandq(string $string): string
    {
        return '"' . $this->esc($string) . '"';
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
