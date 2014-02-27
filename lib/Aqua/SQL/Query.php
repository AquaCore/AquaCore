<?php
namespace Aqua\SQL;

class Query
{
	/**
	 * @param \PDO $dbh
	 * @return \Aqua\SQL\Select
	 */
	public static function select(\PDO $dbh) {
		return new Select($dbh);
	}

	/**
	 * @param \PDO $dbh
	 * @return \Aqua\SQL\Update
	 */
	public static function update(\PDO $dbh) {
		return new Update($dbh);
	}

	/**
	 * @param \PDO   $dbh
	 * @param string $name The name of the table
	 * @return \Aqua\SQL\CreateTable
	 */
	public static function createTable(\PDO $dbh, $name) {
		return new CreateTable($dbh, $name);
	}

	/**
	 * @param \PDO $dbh
	 * @return \Aqua\SQL\Search
	 */
	public static function search(\PDO $dbh) {
		return new Search($dbh);
	}
}
