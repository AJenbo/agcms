<?php
/**
 * Declare the Simple_Mysqli class
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

/**
 * Helper classe to make it simple to makes querys to MySQL and get the results
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */
class Simple_Mysqli extends mysqli
{

    /**
     * Connect the database and set session to UTF-8 Danish
     */
    function __construct()
    {
        /**
         * Pass all arguments passed to the constructor on to the parent's
         * constructor
         */
        $args = func_get_args();
        parent::__construct($args[0], $args[1], $args[2], $args[3]);

        /* Throw an error if the connection fails */
        if (mysqli_connect_error()) {
            throw new Exception('', mysqli_connect_errno());
        }

        $this->query("SET NAMES 'UTF8'");
        $this->query("SET SESSION character_set_server = 'UTF8'");
        $this->query("SET collation_server=utf8_danish_ci");
    }

    /**
     * Performe query and return result as an array of associative arrays
     *
     * @param string $query The MySQL query to preforme
     *
     * @return array
     */
    public function fetchArray($query)
    {
        $result = parent::query($query);
        if (mysqli_error($this)) {
            throw new Exception(mysqli_error($this), mysqli_errno($this));
        }
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->close();

        if (!isset($rows)) {
            $rows = array();
        }

        return $rows;
    }

    /**
     * Performe query and return the first result as an associative arrays
     *
     * @param string $query The MySQL query to preforme
     *
     * @return array
     */
    public function fetchOne($query)
    {
        $row = $this->fetchArray($query);
        $row = $row[0];

        if (!isset($row)) {
            $row = array();
        }

        return $row;
    }

    /**
     * Performe query
     *
     * @param string $query The MySQL query to preforme
     *
     * @return null
     */
    public function query($query)
    {
        $result = parent::query($query);
        if (mysqli_error($this)) {
            throw new Exception(mysqli_error($this), mysqli_errno($this));
        }
        return true;
    }

    /**
     * Escape all MySQL wildcards
     *
     * @param string $string String to process
     *
     * @return string
     */
    public function escapeWildcards($string)
    {
        return preg_replace('/([%_])/u', '\\\\$1', $string);
    }
}

