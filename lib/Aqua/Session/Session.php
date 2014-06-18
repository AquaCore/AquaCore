<?php
namespace Aqua\Session;

use Aqua\Core\App;
use Aqua\Http\Response;
use Aqua\Event\EventDispatcher;
use Aqua\Session\Exception\SessionException;

class Session
{
	/**
	 * @var string
	 */
	public $sessionId;
	/**
	 * @var string
	 */
	public $ipAddress;
	/**
	 * @var string
	 */
	public $userAgent;
	/**
	 * @var int
	 */
	public $lastUpdate;
	/**
	 * @var int
	 */
	public $startTime;
	/**
	 * @var int
	 */
	public $userId = null;
	/**
	 * @var \Aqua\Http\Response
	 */
	public $response;
	/**
	 * @var array
	 */
	public $data = array();
	/**
	 * @var array
	 */
	public $flash = array();
	/**
	 * @var array
	 */
	public $tmp = array();
	/**
	 * @var bool
	 */
	public $secureCookie = false;
	/**
	 * @var bool
	 */
	public $httpOnlyCookie = true;
	/**
	 * @var bool
	 */
	public $matchIpAddress = false;
	/**
	 * @var bool
	 */
	public $matchUserAgent = false;
	/**
	 * @var bool
	 */
	public $exists = false;
	/**
	 * @var int
	 */
	public $gcProbability = 0;
	/**
	 * @var int
	 */
	public $regenerateId = 0;
	/**
	 * @var int
	 */
	public $expire = 0;
	/**
	 * @var string
	 */
	public $cookieName = 'aquacore_sess_id';
	/**
	 * @var int
	 */
	public $maxCollision = 3;
	/**
	 * @var \Aqua\Event\EventDispatcher
	 */
	public $dispatcher;
	/**
	 * @var bool
	 */
	public $open = false;

	const TYPE_NORMAL          = 1;
	const TYPE_FLASH           = 2;
	const TYPE_TEMPORARY       = 3;

	/**
	 * @param string|null         $session_id
	 * @param string              $ip_address
	 * @param string              $user_agent
	 * @param \Aqua\Http\Response $response
	 * @param array               $options
	 */
	public function __construct($session_id, $ip_address, $user_agent, Response $response, array $options)
	{
		$this->dispatcher = new EventDispatcher;
		$this->sessionId   = $session_id;
		$this->ipAddress   = $ip_address;
		$this->userAgent   = $user_agent;
		$this->response    = $response;
		foreach($options as $option => $value) {
			$this->setOption($option, $value);
		}
	}

	/**
	 * @param string $option
	 * @param mixed  $value
	 * @return \Aqua\Session\Session
	 * @throws \Aqua\Session\Exception\SessionException
	 */
	public function setOption($option, $value = null)
	{
		if($this->open) {
			throw new SessionException(
				__('exception', 'session-already-open'),
				SessionException::INVALID_SESSION_STATE
			);
		}
		switch($option) {
			case 'name':
				$this->cookieName = $value;
				break;
			case 'regenerate_id':
				$this->regenerateId = (int)$value * 60;
				break;
			case 'expire':
				$this->expire = (int)$value;
				break;
			case 'secure':
				$this->secureCookie = (bool)$value;
				break;
			case 'http_only':
				$this->httpOnlyCookie = (bool)$value;
				break;
			case 'match_ip_address':
				$this->matchIpAddress = (bool)$value;
				break;
			case 'match_user_agent':
				$this->matchUserAgent = (bool)$value;
				break;
			case 'max_collision':
				$this->maxCollision = (int)$value;
				break;
			case 'gc_probability':
				$this->gcProbability = $value;
				break;
		}

		return $this;
	}

	/**
	 * @param string $option
	 * @return mixed
	 */
	public function getOption($option)
	{
		switch($option) {
			case 'name':
				return $this->cookieName;
			case 'regenerate_id':
				return $this->regenerateId / 60;
			case 'expire':
				return $this->expire;
			case 'secure':
				return $this->secureCookie;
			case 'http_only':
				return $this->httpOnlyCookie;
			case 'match_ip_address':
				return $this->matchIpAddress;
			case 'match_user_agent':
				return $this->matchUserAgent;
			case 'max_collision':
				return $this->maxCollision;
			case 'gc_probability':
				return $this->gcProbability;
			default:
				return null;
		}
	}

	/**
	 * @param bool $registerShutdown
	 * @return \Aqua\Session\Session
	 * @throws \Aqua\Session\Exception\SessionException
	 */
	public function open($registerShutdown = true)
	{
		if($this->open) {
			throw new SessionException(
				__('exception', 'session-already-open'),
				SessionException::INVALID_SESSION_STATE
			);
		}
		$this->open = true;
		$this->notify('open');
		if($this->sessionId && ctype_xdigit($this->sessionId) || strlen($this->sessionId) !== 127) {
			$query = sprintf('
			SELECT _user_id,
			       _data,
			       _tmp,
			       _flash,
			       UNIX_TIMESTAMP(_session_start),
			       UNIX_TIMESTAMP(_last_update)
			FROM %s
			WHERE _key = :key AND _last_update > DATE_SUB(NOW(), INTERVAL :expire MINUTE)
			', ac_table('session'));
			if($this->matchUserAgent) {
				$query .= " AND _user_agent = :ua";
			}
			if($this->matchIpAddress) {
				$query .= " AND _ip_address = :ip";
			}
			$query .= " LIMIT 1";
			$sth = App::connection()->prepare($query);
			$sth->bindValue(':key', hash('sha512', $this->sessionId), \PDO::PARAM_STR);
			$sth->bindValue(':expire', $this->expire, \PDO::PARAM_INT);
			if($this->matchIpAddress) {
				$sth->bindValue(':ip', $this->ipAddress, \PDO::PARAM_LOB);
			}
			if($this->matchUserAgent) {
				$sth->bindValue(':ua', substr($this->userAgent, 0, 255), \PDO::PARAM_STR);
			}
			$sth->execute();
			if($data = $sth->fetch(\PDO::FETCH_NUM)) {
				$this->userId     = (empty($data[0]) ? null : (int)$data[0]);
				$this->data      = unserialize($data[1]);
				$this->tmp       = unserialize($data[2]);
				$this->flash     = unserialize($data[3]);
				$this->startTime  = (int)$data[4];
				$this->lastUpdate = (int)$data[5];
				$this->deleteExpiredTmp();
				$this->markExpiredFlash();
				$this->exists = true;
				if($this->lastUpdate < (time() - $this->regenerateId)) {
					$this->regenerateId(null, true);
				}
			} else {
				$this->regenerateId(null, false);
			}
		}
		if($registerShutdown) {
			register_shutdown_function(array( $this, 'close' ), true);
			if(ac_probability($this->gcProbability)) {
				register_shutdown_function(array( $this, 'gc' ));
			}
		}
		$this->notify('ready');

		return $this;
	}

	/**
	 * @param bool $commit
	 * @return \Aqua\Session\Session
	 * @throws \Aqua\Session\Exception\SessionException
	 */
	public function close($commit = true)
	{
		if(!$this->open) {
			throw new SessionException(
				__('exception', 'session-not-open')
			);
		}
		$this->notify('close');
		if($commit) {
			$this->commit();
		}
		$this->open = false;

		return $this;
	}

	/**
	 * @return \Aqua\Session\Session
	 * @throws \Aqua\Session\Exception\SessionException
	 */
	public function commit()
	{
		if(!$this->open) {
			throw new SessionException(
				__('exception', 'session-not-open'),
				SessionException::INVALID_SESSION_STATE
			);
		}
		$this->deleteExpiredFlash();
		if(!$this->exists && $this->userId === null && empty($this->data) && empty($this->flash) && empty($this->tmp)) {
			return $this;
		}
		$this->save();

		return $this;
	}

	/**
	 * Replace the session ID
	 *
	 * @param string $id
	 * @return \Aqua\Session\Session
	 * @throws \Aqua\Session\Exception\SessionException
	 */
	public function regenerateId($id = null)
	{
		$sth = App::connection()->prepare(sprintf('
		SELECT COUNT(1)
		FROM %s
		WHERE _key = ?
		LIMIT 1
		', ac_table('session')));
		if(!$id) {
			for($i = 0; $i < $this->maxCollision; ++$i) {
				$id = $this->generateId();
				$sth->execute(array( $id ));
				if(!(int)$sth->fetchColumn(0)) {
					break;
				} else {
					$id = null;
				}
			}
		}
		if(!$id) {
			throw new SessionException(
				__('exception', 'session-collision', $this->maxCollision),
				SessionException::COLLISION
			);
		}
		$oldId           = $this->sessionId;
		$this->sessionId = $id;
		$this->updateCookie();
		if($this->exists) {
			$sth = App::connection()->prepare(sprintf('
			UPDATE %s
			SET _key = ?
			WHERE _key = ?
			LIMIT 1
			', ac_table('session')));
			$sth->bindValue(1, hash('sha512', $id), \PDO::PARAM_STR);
			$sth->bindValue(2, hash('sha512', $oldId), \PDO::PARAM_STR);
			$sth->execute();
			$this->lastUpdate = time();
		}
		$feedback = array( 'old_id' => $oldId, 'new_id' => $id );
		$this->notify('regenerate_id', $feedback);

		return $this;
	}

	/**
	 * Save session data
	 *
	 * @return \Aqua\Session\Session
	 */
	public function save()
	{
		if($this->open) {
			$sth = App::connection()->prepare(sprintf('
			INSERT INTO %s (_key, _ip_address, _user_agent, _user_id, _data, _tmp, _flash, _session_start)
			VALUES (:id, :ip, :ua, :user, :data, :tmp, :flash, NOW())
			ON DUPLICATE KEY UPDATE
			_user_id = VALUES(_user_id),
			_data = VALUES(_data),
			_tmp = VALUES(_tmp),
			_flash = VALUES(_flash)
			', ac_table('session')));
			$sth->bindValue(':id', hash('sha512', $this->sessionId), \PDO::PARAM_STR);
			$sth->bindValue(':ip', $this->ipAddress, \PDO::PARAM_LOB);
			$sth->bindValue(':ua', substr($this->userAgent, 0, 255), \PDO::PARAM_STR);
			$sth->bindValue(':data', serialize($this->data), \PDO::PARAM_LOB);
			$sth->bindValue(':tmp', serialize($this->tmp), \PDO::PARAM_LOB);
			$sth->bindValue(':flash', serialize($this->flash), \PDO::PARAM_LOB);
			if($this->userId === null) $sth->bindValue(':user', null, \PDO::PARAM_NULL);
			else $sth->bindValue(':user', $this->userId, \PDO::PARAM_INT);
			$sth->execute();
			$this->notify('save');
		}

		return $this;
	}

	/**
	 * Delete current session
	 *
	 * @param bool $reset
	 * @return \Aqua\Session\Session
	 */
	public function destroy($reset = true)
	{
		$sth = App::connection()->prepare(sprintf('
		DELETE FROM %s
		WHERE _key = ?
		LIMIT 1
		', ac_table('session')));
		$sth->bindValue(1, hash('sha512', $this->sessionId), \PDO::PARAM_STR);
		$sth->execute();
		$sth->closeCursor();
		$this->exists = false;
		$this->notify('destroy');
		if($reset) {
			$this->reset();
		}

		return $this;
	}

	/**
	 * Delete all other sessions logged in the same account as the current one's
	 *
	 * @return int
	 */
	public function destroyUserId()
	{
		if(!$this->userId) {
			return 0;
		}
		$sth = App::connection()->prepare(sprintf('
		DELETE FROM %s
		WHERE _user_id = ?
		AND _key != ?
		', ac_table('session')));
		$sth->bindValue(1, $this->userId, \PDO::PARAM_INT);
		$sth->bindValue(2, hash('sha512', $this->sessionId), \PDO::PARAM_STR);
		$sth->execute();
		if(!($count = $sth->rowCount())) {
			return 0;
		}
		$feedback = array( $count );
		$this->notify('destroy-user', $feedback);

		return $count;
	}

	/**
	 * Clear session data
	 *
	 * @return \Aqua\Session\Session
	 */
	public function reset()
	{
		$this->data  = array();
		$this->flash = array();
		$this->tmp   = array();

		return $this;
	}

	/**
	 * Delete expired sessions
	 */
	public function gc()
	{
		$sth = App::connection()->prepare(sprintf('
		DELETE FROM %s
		WHERE _last_update < DATE_SUB(NOW(), INTERVAL :interval MINUTE)
		', ac_table('session')));
		$sth->bindValue(':interval', $this->expire, \PDO::PARAM_INT);
		$sth->execute();
		$sth->closeCursor();
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function exists($key)
	{
		return (array_key_exists($key, $this->data)  ||
		        array_key_exists($key, $this->flash) ||
		        array_key_exists($key, $this->tmp));
	}

	/**
	 * @param string $key
	 * @return int|null
	 * @see \Aqua\Session\Session::TYPE_NORMAL
	 * @see \Aqua\Session\Session::TYPE_FLASH
	 * @see \Aqua\Session\Session::TYPE_TEMPORARY
	 */
	public function type($key)
	{
		if(array_key_exists($key, $this->data)) {
			return self::TYPE_NORMAL;
		} else if(array_key_exists($key, $this->flash)) {
			return self::TYPE_FLASH;
		} else if(array_key_exists($key, $this->tmp)) {
			return self::TYPE_TEMPORARY;
		} else {
			return null;
		}
	}

	/**
	 * @param string $key
	 * @param mixed  $default Value returned if it doesn't exist
	 * @return mixed
	 */
	public function &get($key, $default = null)
	{
		switch($this->type($key)) {
			case self::TYPE_NORMAL:
				return $this->data[$key];
			case self::TYPE_FLASH:
				return $this->flash[$key][0];
			case self::TYPE_TEMPORARY:
				return $this->tmp[$key][0];
			default:
				return $default;
		}
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @return \Aqua\Session\Session
	 */
	public function set($key, $value)
	{
		$this->delete($key);
		$this->data[$key] = $value;

		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $ttl
	 * @return \Aqua\Session\Session
	 */
	public function tmp($key, $value, $ttl)
	{
		if($ttl <= 0) {
			// Will only be available for the current request
			$this->flash[$key] = array( $value, false );
		}
		$this->delete($key);
		$this->tmp[$key] = array( $value, time() + $ttl );

		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $requests
	 * @return \Aqua\Session\Session
	 */
	public function flash($key, $value, $requests = 1)
	{
		$this->delete($key);
		$this->flash[$key] = array( $value, $requests );

		return $this;
	}

	/**
	 * Delete session data by key
	 *
	 * @param string $key
	 * @return \Aqua\Session\Session
	 */
	public function delete($key)
	{
		switch($this->type($key)) {
			case self::TYPE_NORMAL:
				unset($this->data[$key]);
				break;
			case self::TYPE_FLASH:
				unset($this->flash[$key]);
				break;
			case self::TYPE_TEMPORARY:
				unset($this->tmp[$key]);
				break;
		}
		return $this;
	}

	/**
	 * Keep flash data for more requests
	 *
	 * @param string $key
	 * @param int    $requests
	 * @return \Aqua\Session\Session
	 */
	public function keep($key, $requests = 1)
	{
		if(array_key_exists($key, $this->flash)) {
			$this->flash[$key][1] += $requests;
		}

		return $this;
	}

	/**
	 * @param int $ttl
	 */
	public function updateCookie($ttl = 0)
	{
		$this->notify('update_cookie');
		$this->response->setCookie(
			$this->cookieName,
			array(
				'value'     => $this->sessionId,
				'ttl'       => $ttl,
				'path'      => '/',
				'secure'    => $this->secureCookie,
				'http_only' => $this->httpOnlyCookie
			)
		);
	}

	/**
	 * Remove expired temporary data
	 *
	 * @return void
	 * @access protected
	 */
	public function deleteExpiredTmp()
	{
		foreach($this->tmp as $key => $value) {
			if($value[1] <= time()) {
				unset($this->tmp[$key]);
			}
		}
	}

	/**
	 * Remove expired flash data
	 */
	public function deleteExpiredFlash()
	{
		foreach($this->flash as $key => $value) {
			if($value[1] < 1) {
				unset($this->flash[$key]);
			}
		}
	}

	/**
	 * Decrease flash data's duration by one request
	 */
	public function markExpiredFlash()
	{
		foreach($this->flash as &$value) {
			--$value[1];
		}
	}

	/**
	 * @return string
	 */
	public function generateId()
	{
		return uniqid(bin2hex(secure_random_bytes(32)));
	}

	public function attach($event, \Closure $listener)
	{
		$this->dispatcher->attach("session.$event", $listener);
	}

	public function detach($event, \Closure $listener)
	{
		$this->dispatcher->detach("session.$event", $listener);
	}

	public function notify($event, &$feedback = null)
	{
		$this->dispatcher->notify("session.$event", $feedback);
	}
}
