<?php

//include the file  
require_once "firephp.class.php";
//create the object
global $firephp;
$firephp = FirePHP::getInstance(true);

/* Create custom exception classes */
class QueryException extends Exception
{
}

class simple_mysqli extends mysqli
{
	function __construct()
	{
		/* Pass all arguments passed to the constructor on to the parent's constructor */
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

	function fetch_array($query)
	{
		//send information
		//global $firephp;
		//if (!headers_sent())
		//$firephp->fb($query);

		$result = parent::query($query);
		if (mysqli_error($this)) {
			throw new QueryException(mysqli_error($this), mysqli_errno($this));
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

	function fetch_one($query)
	{
		//send information
		//global $firephp;
		//if (!headers_sent())
		//$firephp->fb($query);

		$result = parent::query($query);
		if (mysqli_error($this)) {
			throw new QueryException(mysqli_error($this), mysqli_errno($this));
		}
		$row = $result->fetch_assoc();

		$result->close();

		if (!isset($row)) {
			$row = array();
		}

		return $row;
	}

	function query($query)
	{
		//send information
		//global $firephp;
		//if (!headers_sent())
		//$firephp->fb($query);

		$result = parent::query($query);
		if (mysqli_error($this)) {
			throw new QueryException(mysqli_error($this), mysqli_errno($this));
		}
		return true;
	}

	function escape_wildcards($string)
	{
		return preg_replace('/([%_])/u', '\\\\$1', $string);
	}
}

