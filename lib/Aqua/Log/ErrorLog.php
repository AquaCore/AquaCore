<?php
namespace Aqua\Log;

use Aqua\Core\App;
use Aqua\SQL\Query;
use Aqua\UI\Template;
use Aqua\User\Account;

class ErrorLog
{
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var string
	 */
	public $url;
	/**
	 * @var int
	 */
	public $userId;
	/**
	 * @var string
	 */
	public $ipAddress;
	/**
	 * @var int
	 */
	public $date;
	/**
	 * @var string
	 */
	public $type;
	/**
	 * @var string
	 */
	public $code;
	/**
	 * @var string
	 */
	public $file;
	/**
	 * @var int
	 */
	public $line;
	/**
	 * @var string
	 */
	public $message;
	/**
	 * @var int
	 */
	public $parentId;
	/**
	 * @var \Aqua\Log\ErrorLog|null
	 */
	public $next = false;
	/**
	 * @var \Aqua\Log\ErrorLog|null
	 */
	public $previous = false;
	/**
	 * @var array
	 */
	public $trace = null;

	const LOG_DIR = '/tmp/error_log';

	protected function __construct() { }

	/**
	 * @param string $format
	 * @return string
	 */
	public function date($format)
	{
		return strftime($format, $this->date);
	}

	/**
	 * @return \Aqua\User\Account|null
	 */
	public function user()
	{
		return ($this->userId ? Account::get($this->userId) : null);
	}

	/**
	 * @return \Aqua\Log\ErrorLog|null
	 */
	public function previous()
	{
		if($this->previous === false) {
			if($this->id === null) {
				$this->previous = null;
			} else {
				$search = self::search()
					->where(array( 'parent' => $this->id ))
					->limit(1)
					->query();
				$this->previous = ($search->count() ? $search->current() : null);
			}
		}

		return $this->previous;
	}

	/**
	 * @return \Aqua\Log\ErrorLog|null
	 */
	public function next()
	{
		if($this->next === false) {
			$this->next = ($this->parentId ? self::get($this->parentId) : null);
		}

		return $this->next;
	}

	/**
	 * @return array
	 */
	public function trace()
	{
		if($this->trace === null) {
			$this->trace = Query::select(App::connection())
				->columns(array(
					'file'     => '_file',
					'line'     => '_line',
					'class'    => '_class',
					'function' => '_method',
					'type'     => '_type'
				))
				->from(ac_table('error_trace'))
				->where(array( '_error_id' => $this->id ))
				->order(array( 'id' => 'DESC' ))
				->query()
				->results;
		}

		return $this->trace;
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public static function search()
	{
		return Query::search(App::connection())
			->columns(array(
				'id'         => 'id',
				'url'        => '_url',
				'ip_address' => '_ip_address',
				'user_id'    => '_user_id',
				'date'       => 'UNIX_TIMESTAMP(_date)',
				'type'       => '_type',
				'code'       => '_code',
				'file'       => '_file',
				'line'       => '_line',
				'parent'     => '_parent',
				'message'    => '_message'
			))
			->whereOptions(array(
				'id'         => 'id',
				'url'        => '_url',
				'ip_address' => '_ip_address',
				'user_id'    => '_user_id',
				'date'       => '_date',
				'type'       => '_type',
				'code'       => '_code',
				'file'       => '_file',
				'line'       => '_line',
				'parent'     => '_parent'
			))
			->from(ac_table('error_log'))
			->groupBy('id')
			->parser(array( __CLASS__, 'parseErrorSql' ));
	}

	/**
	 * @param int $id
	 * @return \Aqua\Log\ErrorLog|null
	 */
	public static function get($id)
	{
		$select = Query::select(App::connection())
			->columns(array(
				'id'         => 'id',
				'url'        => '_url',
				'ip_address' => '_ip_address',
				'user_id'    => '_user_id',
				'date'       => 'UNIX_TIMESTAMP(_date)',
				'type'       => '_type',
				'code'       => '_code',
				'file'       => '_file',
				'line'       => '_line',
				'parent'     => '_parent',
				'message'    => '_message'
			))
			->from(ac_table('error_log'))
			->where(array( 'id' => $id ))
			->limit(1)
			->parser(array( __CLASS__, 'parseErrorSql' ))
			->query();

		return ($select->valid() ? $select->current() : null);
	}

	/**
	 * @param \Exception         $exception
	 * @param \Aqua\Log\ErrorLog $parent
	 * @return \Aqua\Log\ErrorLog
	 */
	public static function logSql(\Exception $exception, self $parent = null)
	{
		try {
			if(!($dbh = App::connection())) {
				return self::logText($exception);
			}
			$log            = new self;
			$log->url       = App::request()->uri->url();
			$log->userId    = (App::$user && App::$user->loggedIn() ? App::$user->account->id : null);
			$log->ipAddress = App::request()->ipString;
			$log->type      = get_class($exception);
			$log->date      = time();
			$log->file      = $exception->getFile();
			$log->line      = $exception->getLine();
			$log->code      = $exception->getCode();
			$log->message   = $exception->getMessage();
			if($parent instanceof self) {
				$log->parentId = $parent->id;
			} else {
				$log->parentId = null;
			}
			$log->next  = $parent;
			$log->trace = array();
			$error_tbl  = ac_table('error_log');
			$sth        = $dbh->prepare("
			INSERT INTO `$error_tbl` (_url, _user_id, _ip_address, _date, _type, _code, _file, _line, _message, _parent)
			VALUES (:url, :user, :ip, NOW(), :type, :code, :file, :line, :message, :parent)
			");
			$sth->bindValue(':url', $log->url, \PDO::PARAM_STR);
			$sth->bindValue(':ip', $log->ipAddress, \PDO::PARAM_STR);
			$sth->bindValue(':type', $log->type, \PDO::PARAM_STR);
			$sth->bindValue(':file', $log->file, \PDO::PARAM_STR);
			$sth->bindValue(':line', $log->line, \PDO::PARAM_INT);
			$sth->bindValue(':code', $log->code, \PDO::PARAM_STR);
			$sth->bindValue(':message', $log->message, \PDO::PARAM_LOB);
			if($parent) {
				$sth->bindValue(':parent', $parent->id, \PDO::PARAM_INT);
			} else {
				$sth->bindValue(':parent', null, \PDO::PARAM_NULL);
			}
			if($log->userId !== null) $sth->bindValue(':user', $log->userId, \PDO::PARAM_INT);
			else $sth->bindValue(':user', null, \PDO::PARAM_NULL);
			$sth->execute();
			$log->id = (int)$dbh->lastInsertId();
			$trace   = $exception->getTrace();
			if(!empty($trace)) {
				$trace_tbl = ac_table('error_trace');
				$i         = -1;
				$sth       = $dbh->prepare("
				INSERT INTO `$trace_tbl` (id, _error_id, _file, _line, _class, _method, _type)
				VALUES (:id, :error, :file, :line, :class, :method, :type)
				");
				foreach($trace as $t) {
					++$i;
					$t += array(
						'file'     => null,
						'line'     => null,
						'class'    => '',
						'function' => null,
						'type'     => null
					);
					$t['class']   = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $t['class']);
					$log->trace[] = $t;
					$sth->bindValue(':id', $i, \PDO::PARAM_INT);
					$sth->bindValue(':error', $log->id, \PDO::PARAM_INT);
					if(!empty($t['file'])) {
						$sth->bindValue(':file', $t['file'], \PDO::PARAM_STR);
					} else {
						$sth->bindValue(':file', null, \PDO::PARAM_NULL);
					}
					if(!empty($t['line'])) {
						$sth->bindValue(':line', $t['line'], \PDO::PARAM_INT);
					} else {
						$sth->bindValue(':line', null, \PDO::PARAM_NULL);
					}
					if(!empty($t['class'])) {
						$sth->bindValue(':class', $t['class'], \PDO::PARAM_STR);
					} else {
						$sth->bindValue(':class', null, \PDO::PARAM_NULL);
					}
					if(!empty($t['function'])) {
						$sth->bindValue(':method', $t['function'], \PDO::PARAM_STR);
					} else {
						$sth->bindValue(':method', null, \PDO::PARAM_NULL);
					}
					if(!empty($t['type'])) {
						$sth->bindValue(':type', $t['type'], \PDO::PARAM_STR);
					} else {
						$sth->bindValue(':type', null, \PDO::PARAM_NULL);
					}
					$sth->execute();
					$sth->closeCursor();
				}
				$log->trace = array_reverse($log->trace, true);
			}
			if($exception->getPrevious() instanceof \Exception) {
				$log->previous = self::logSql($exception->getPrevious(), $log);
			}

			return $log;
		} catch(\Exception $e) {
			self::logText($e);

			return self::logText($exception);
		}
	}

	/**
	 * @param \Exception $exception
	 * @return \Aqua\Log\ErrorLog
	 */
	public static function logText(\Exception $exception)
	{
		if(isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === '1')) {
			$url = 'https://';
		} else {
			$url = 'http://';
		}
		$url .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		$ip   = $_SERVER['REMOTE_ADDR'];
		$_err = $err = new self;
		$next = null;
		do {
			$_err->url       = $url;
			$_err->ipAddress = $ip;
			$_err->type      = get_class($exception);
			$_err->line      = (int)$exception->getLine();
			$_err->file      = $exception->getFile();
			$_err->code      = $exception->getCode();
			$_err->date      = time();
			$_err->message   = $exception->getMessage();
			$_err->next      = $next;
			$_err->trace     = array();
			$trace           = $exception->getTrace();
			if(!empty($trace)) {
				foreach($trace as $t) {
					$t += array(
						'file'     => null,
						'line'     => null,
						'class'    => '',
						'function' => null,
						'type'     => null
					);
					$t['class']    = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $t['class']);
					$_err->trace[] = $t;
				}
				$_err->trace = array_reverse($_err->trace, true);
			}
		} while(($exception = $exception->getPrevious()) &&
		        (($_err->previous = new self) && ($_err = $_err->previous) || 1));
		$tpl = new Template;
		$tpl->set('error', $err);
		file_put_contents(\Aqua\ROOT . self::LOG_DIR . '/' . uniqid(date('Y-m-d.U.')) . '.log',
		                  $tpl->render('exception/log'));

		return $err;
	}

	/**
	 * @param array $data
	 * @return \Aqua\Log\ErrorLog
	 */
	public static function parseErrorSql(array $data)
	{
		$err            = new self;
		$err->id        = (int)$data['id'];
		$err->date      = (int)$data['date'];
		$err->line      = (int)$data['line'];
		$err->parentId  = (int)$data['parent'];
		$err->userId    = ($data['user_id'] ? (int)$data['user_id'] : null);
		$err->url       = $data['url'];
		$err->ipAddress = $data['ip_address'];
		$err->type      = $data['type'];
		$err->code      = $data['code'];
		$err->file      = $data['file'];
		$err->message   = $data['message'];

		return $err;
	}
}
