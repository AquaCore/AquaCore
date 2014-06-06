<?php
namespace Aqua\Schedule;

use Aqua\Core\App;
use Aqua\Event\Event;
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
	public $logging;
	public $isRunning = null;
	public $isEnabled;
	public $isProtected;
	public $errorMessage;
	public $pluginId;
	protected $_cron;

	const ERR_PLUGIN_DISABLED    = 1;
	const ERR_CLASS_NOT_FOUND    = 2;
	const ERR_INVALID_CLASS      = 3;
	const ERR_INVALID_EXPRESSION = 4;

	public function nextRun($format)
	{
		return strftime($format, $this->nextRun);
	}

	public function lastRun($format)
	{
		return strftime($format, $this->lastRun);
	}

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
		if($this->isEnabled === $isEnabled) {
			return false;
		}
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

	public function edit(array $options = array())
	{
		$value = array();
		$update = '';
		$columns = array( 'title', 'description', 'expression', 'logging' );
		$options = array_intersect_key($options, array_flip($columns));
		if(empty($options)) {
			return false;
		}
		$options = array_map('trim', $options);
		if(array_key_exists('title', $options) && $options['title'] !== $this->title) {
			$value['title'] = $options['title'];
			$update.= '_title = ?, ';
		}
		if(array_key_exists('description', $options) && $options['description'] !== $this->title) {
			$value['description'] = $options['description'];
			$update.= '_description = ?, ';
		}
		if(array_key_exists('expression', $options) && $options['expression'] !== $this->expression) {
			$value['expression'] = $options['expression'];
			$update.= '_expression = ?, ';
		}
		if(array_key_exists('logging', $options) && $options['logging'] !== $this->logging) {
			$value['logging'] = ($options['logging'] ? 'y' : 'n');
			$update.= '_logging = ?, ';
		}
		if(empty($value)) {
			return false;
		}
		$value[] = $this->id;
		$update = substr($update, 0, -2);
		$sth = App::connection()->prepare(sprintf('
		UPDATE `%s`
		SET %s
		WHERE id = ?
		LIMIT 1
		', ac_table('tasks'), $update));
		if(!$sth->execute(array_values($value)) || !$sth->rowCount()) {
			return false;
		}
		array_pop($options);
		if(isset($value['logging'])) {
			$value['logging'] = $value['logging'] === 'y';
		}
		$feedback = array( $this, $value );
		Event::fire('task.update', $feedback);
		foreach($value as $key => $val) {
			$this->$key = $val;
		}
		return true;
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
