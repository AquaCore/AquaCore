<?php
namespace Aqua\Schedule;

use Aqua\Core\App;
use Aqua\SQL\Query;
use Cron\CronExpression;

class TaskData
{
	public $id;
	public $title;
	public $description;
	public $name;
	public $expression;
	public $lastRun;
	public $nextRun;
	public $isRunning = null;
	public $isEnabled;
	public $errorMessage;
	public $pluginId;
	protected $_cron;

	const ERR_PLUGIN_DISABLED    = 1;
	const ERR_CLASS_NOT_FOUND    = 2;
	const ERR_INVALID_CLASS      = 3;
	const ERR_INVALID_EXPRESSION = 4;

	public function disable()
	{
		return $this->_setEnabled(false);
	}

	public function enable()
	{
		return $this->_setEnabled(true);
	}

	protected function _setEnabled($isEnabled)
	{
		$tbl = ac_table('tasks');
		$sth = App::connection()->prepare("
		UPDATE `$tbl`
		SET _enabled = ?
		WHERE id = ?
		");
		$sth->bindValue(1, $isEnabled ? 'y' : 'n', \PDO::PARAM_STR);
		$sth->bindValue(2, $this->id, \PDO::PARAM_INT);
		$sth->execute();
		if($sth->rowCount()) {
			$this->isEnabled = (bool)$isEnabled;
			return true;
		} else {
			return false;
		}
	}

	public function setError($errorMessage)
	{
		$tbl = ac_table('tasks');
		$sth = App::connection()->prepare("
		UPDATE `$tbl`
		SET _error_message = ?
		WHERE id = ?
		");
		if($errorMessage === null || $errorMessage === '') {
			$sth->bindValue(1, '', \PDO::PARAM_STR);
		} else {
			$sth->bindValue(1, $errorMessage, \PDO::PARAM_STR);
		}
		$sth->execute();
		if($sth->rowCount()) {
			$this->errorMessage = $errorMessage;
			return true;
		} else {
			return false;
		}
	}

	public function delete()
	{
		$tbl = ac_table('tasks');
		$sth = App::connection()->prepare("DELETE FROM `$tbl` WHERE id = ?");
		$sth->execute(array( $this->id ));
		if($sth->rowCount()) {
			return true;
		} else {
			return false;
		}
	}

	public function cron()
	{
		if(!$this->_cron) {
			$this->_cron = CronExpression::factory($this->expression);
		}
		return $this->_cron;
	}
}
