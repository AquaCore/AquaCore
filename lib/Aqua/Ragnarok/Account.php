<?php
namespace Aqua\Ragnarok;

use Aqua\Core\App;
use Aqua\Event\Event;
use Aqua\Ragnarok\Server\CharMap;
use Aqua\SQL\Query;
use Aqua\User\Account as UserAccount;

class Account
{
	/**
	 * Ragnarok account id
	 * @var int
	 */
	public $id;
	/**
	 * Aquacore account id
	 * @var
	 */
	public $owner;
	/**
	 * @var \Aqua\Ragnarok\Server
	 */
	public $server;
	/**
	 * @var string
	 */
	public $username = '';
	/**
	 * @var int
	 */
	public $gender = 0;
	/**
	 * @var string
	 */
	public $email = '';
	/**
	 * @var int
	 */
	public $birthday = 0;
	/**
	 * @var int
	 */
	public $state = 0;
	/**
	 * @var int
	 */
	public $groupId = 0;
	/**
	 * @var int
	 */
	public $unbanTime = 0;
	/**
	 * @var string
	 */
	public $pinCode = '';
	/**
	 * @var int
	 */
	public $pinCodeChange = 0;
	/**
	 * @var string
	 */
	public $password = '';
	/**
	 * @var int
	 */
	public $lastLogin = 0;
	/**
	 * @var int
	 */
	public $loginCount = 0;
	/**
	 * @var string
	 */
	public $lastIp = '';
	/**
	 * @var int
	 */
	public $slots = 0;
	/**
	 * @var int
	 */
	public $options = 0;

	protected $_uri;

	const GENDER_MALE   = 0;
	const GENDER_FEMALE = 1;
	const GENDER_SERVER = 2;

	const STATE_NORMAL             = 0;
	const STATE_BANNED_TEMPORARILY = 7;
	const STATE_BANNED_PERMANENTLY = 10;
	const STATE_LOCKED             = 14;

	/**
	 * @param array $options
	 * @return bool
	 */
	public function update(array $options)
	{
		$value = array();
		$update = '';
		$account_data = array();
		$columns = array(
			'username', 'password', 'sex', 'email', 'group_id',
			'unban_time', 'login_count', 'last_ip', 'birthday', 'state'
		);
		if($this->server->login->getOption('use-pincode')) {
			$columns[] = 'pincode';
			$columns[] = 'pincode_change';
		}
		if($this->server->login->getOption('use-slots')) {
			$columns[] = 'slots';
		}
		$options = array_intersect_key($options, array_flip($columns));
		if(empty($options)) {
			return false;
		}
		$options = array_map('trim', $options);
		if(array_key_exists('username', $options) && $options['username'] !== $this->username) {
			$value['username'] = $account_data['username'] = substr($options['username'], 0, 23);
			$update.= 'userid = ?, ';
		}
		if(array_key_exists('password', $options)) {
			$value['password'] = $account_data['password'] = ($this->server->login->getOption('use-md5') ?
			                                                  md5($options['password']) :
				                                              substr($options['password'], 0, 32));
			$plain_password = $options['password'];
			$update.= 'user_pass = ?, ';
		}
		if(array_key_exists('sex', $options)) {
			$options['sex'] = ($options['sex'] === 1 || $options['sex'] === 'F' ? 1 : 0);
			if($options['sex'] !== $this->gender) {
				$value['sex'] = ($options['sex'] ? 'F' : 'M');
				$account_data['gender'] = $options['sex'];
				$update.= 'sex = ?, ';
			}
		}
		if(array_key_exists('email', $options) && $options['email'] !== $this->email) {
			$value['email'] = $account_data['email'] = substr($options['email'], 0, 39);
			$update.= 'email = ?, ';
		}
		if(array_key_exists('state', $options) && $options['state'] !== $this->state) {
			$value['state'] = $account_data['state'] = $options['state'];
			$update.= 'state = ?, ';
		}
		if(array_key_exists('unban_time', $options) && $options['unban_time'] !== $this->unbanTime) {
			$value['unban_time'] = $account_data['unbanTime'] = $options['unban_time'];
			$update.= 'unban_time = ?, ';
		}
		if(array_key_exists('login_count', $options) && $options['login_count'] !== $this->loginCount) {
			$value['login_count'] = $account_data['loginCount'] = $options['login_count'];
			$update.= 'logincount = ?, ';
		}
		if(array_key_exists('last_login', $options) && $options['last_login'] !== $this->lastLogin) {
			$value['last_login'] = date('Y-m-d H:i:s', $options['last_login']);
			$account_data['lastLogin'] = $options['last_login'];
			$update.= 'lastlogin = ?, ';
		}
		if(array_key_exists('last_ip', $options) && $options['last_ip'] !== $this->lastIp) {
			$value['last_ip'] = $account_data['lastIp'] = $options['last_ip'];
			$update.= 'last_ip = ?, ';
		}
		if(array_key_exists('birthday', $options)) {
			$value['birthday'] = date('Y-m-d', $options['birthday']);
			$update.= 'birthdate = ?, ';
		}
		if(array_key_exists('pincode', $options)) {
			$value['pincode'] = $options['pincode'];
			$update.= 'pincode = ?, ';
		}
		if(array_key_exists('pincode_change', $options)) {
			$value['pincode_change'] = $options['pincode_change'];
			$update.= 'pincode_change = ?, ';
		}
		if(array_key_exists('slots', $options)) {
			$value['slots'] = $account_data['slots'] = $options['character_slots'];
			$update.= 'character_slots = ?, ';
		}
		if(array_key_exists('group_id', $options)) {
			$value['group_id'] = $account_data['groupId'] = $options['group_id'];
			$update.= 'group_id = ?, ';
		}
		if(array_key_exists('cp_options', $options) && $options['cp_options'] !== $this->options) {
			$value['cp_options'] = $account_data['options'] = $options['cp_options'];
			$update.= 'ac_options = ?, ';
		}
		if(empty($value)) {
			return false;
		}
		$value[] = $this->id;
		$update = substr($update, 0, -2);
		$sth = $this->server->login->connection()->prepare("
		UPDATE {$this->server->login->table('login')}
		SET {$update}
		WHERE account_id = ?
		LIMIT 1
		");
		if(!$sth->execute(array_values($value)) || !$sth->rowCount()) {
			return false;
		}
		array_pop($options);
		if(isset($plain_password)) $value['plain_password'] = $plain_password;
		if(array_key_exists('birthday', $value)) $value['birthday'] = strtotime($value['birthday']);
		if(array_key_exists('last_login', $value)) $value['last_login'] = strtotime($value['last_login']);
		$feedback = array( $this, $value );
		Event::fire('ragnarok.update-account', $feedback);
		foreach($account_data as $key => $val) {
			$this->$key = $val;
		}
		return true;
	}

	/**
	 * Reset character order
	 *
	 * @param \Aqua\Ragnarok\Server\CharMap $charMap The char server to update
	 * @param array                         $newOrder Associative array of the new character order: id => order
	 * @return bool Returns false when there is nothing to update or if there is a missing id
	 */
	public function setOrder(CharMap $charMap, array $newOrder)
	{
		$newOrder = array_unique($newOrder);
		$oldOrder = Query::select($charMap->connection())
			->columns(array( 'id' => 'char_id', 'order' => 'char_num' ))
			->setColumnType(array( 'id' => 'integer' , 'order' => 'integer'))
			->from($charMap->table('char'))
			->where(array( 'account_id' => $this->id ))
			->query()
			->getColumn('order', 'id');
		if(empty($oldOrder)) {
			return false;
		}
		$update = Query::update($charMap->connection());
		$table  = $charMap->table('char');
		foreach($newOrder as $id => $slot) {
			if(!array_key_exists($id, $oldOrder)) {
				return false;
			}
			if($oldOrder[$id] === $slot) {
				continue;
			}
			$update->tables(array( "t$id" => $table ))
			       ->set(array( "t$id.char_num" => $slot ))
			       ->where(array( "t$id.char_id" => $id ));
			if(($otherId = array_search($slot, $oldOrder)) !== false &&
			   !array_key_exists($otherId, $newOrder)) {
				$update->tables(array( "t$otherId" => $table ))
				       ->set(array( "t$otherId.char_num" => $oldOrder[$id] ))
				       ->where(array( "t$otherId.char_id" => $otherId ));
			}
		}
		if(empty($update->set)) {
			return false;
		}
		$update->query();
		return (bool)$update->rowCount;
	}

	/**
	 * @param \Aqua\User\Account $user
	 * @param int        $time
	 * @param string     $reason
	 * @return bool
	 */
	public function ban(UserAccount $user, $time = null, $reason = null)
	{
		$sth = $this->server->login->connection()->prepare("
		UPDATE {$this->server->login->table('login')}
		SET state = ?, unban_time = ?
		WHERE account_id = ?
		LIMIT 1
		");
		$sth->bindValue(1, ($time ? self::STATE_NORMAL : self::STATE_BANNED_TEMPORARILY), \PDO::PARAM_INT);
		$sth->bindValue(2, $time, \PDO::PARAM_INT);
		$sth->bindValue(3, $this->id, \PDO::PARAM_INT);
		if(!$sth->execute() || !$sth->rowCount()) {
			return false;
		}
		$this->server->login->log->logBan($this, $user, $time, $reason);
		$feedback = array( $this, $user, $time, $reason );
		Event::fire('ragnarok.ban', $feedback);
		$this->state     = ($time ? self::STATE_NORMAL : self::STATE_BANNED_TEMPORARILY);
		$this->unbanTime = $time;
		return false;
	}

	/**
	 * @param \Aqua\User\Account $user
	 * @param string             $reason
	 * @return bool
	 */
	public function unban(UserAccount $user, $reason = null)
	{
		$sth = $this->server->login->connection()->prepare("
		UPDATE {$this->server->login->table('login')}
		SET state = ?, unban_time = 0
		WHERE account_id = ?
		LIMIT 1
		");
		$sth->bindValue(1, self::STATE_NORMAL, \PDO::PARAM_INT);
		$sth->bindValue(2, $this->id, \PDO::PARAM_INT);
		if(!$sth->execute() || !$sth->rowCount()) {
			return false;
		}
		$this->server->login->log->logUnban($this, $user, $reason);
		$feedback = array( $this, $user, $reason );
		Event::fire('ragnarok.unban', $feedback);
		$this->state     = self::STATE_NORMAL;
		$this->unbanTime = 0;
		return true;
	}

	/**
	 * @param \Aqua\User\Account $user
	 */
	public function link(UserAccount $user)
	{
		$sth = $this->server->login->connection()->prepare("
		UPDATE {$this->server->login->table('login')}
		SET ac_user_id = ?
		WHERE account_id = ?
		LIMIT 1
		");
		$sth->bindValue(1, $user->id, \PDO::PARAM_INT);
		$sth->bindValue(2, $this->id, \PDO::PARAM_INT);
		$sth->execute();
		$this->owner = $user->id;
		$feedback = array( $this, $user );
		Event::fire('ragnarok.link-account', $feedback);
	}

	public function unlink()
	{
		$sth = $this->server->login->connection()->prepare("
		UPDATE {$this->server->login->table('login')}
		SET ac_user_id = 0
		WHERE account_id = ?
		LIMIT 1
		");
		$sth->bindValue(1, $this->id, \PDO::PARAM_INT);
		$sth->execute();
		$this->owner = 0;
		$feedback = array( $this );
		Event::fire('ragnarok.unlink-account', $feedback);
	}

	public function user()
	{
		return UserAccount::get($this->owner);
	}

	public function gender()
	{
		return __('ragnarok-gender', $this->gender);
	}

	public function state()
	{
		return __('ragnarok-state', min($this->state, 20));
	}

	public function groupName()
	{
		return $this->server->login->group($this->groupId);
	}

	public function lastLogin($format)
	{
		if(!$this->lastLogin) {
			return __('application', 'never');
		} else {
			return strftime($format, $this->lastLogin);
		}
	}

	public function birthday($format)
	{
		return strftime($format, $this->birthday);
	}

	public function unbanTime($format)
	{
		return strftime($format, $this->unbanTime);
	}

	public function isBanned()
	{
		return ($this->state === self::STATE_BANNED_TEMPORARILY || $this->state === self::STATE_BANNED_PERMANENTLY);
	}

	public function isLocked()
	{
		return ($this->state === self::STATE_LOCKED);
	}

	public function url(array $options = array())
	{
		if(!$this->_uri) {
			$this->_uri = clone $this->server->uri;
			$this->_uri->path[] = 'a';
			if(App::settings()->get('ragnarok')->get('acc_username_url', true)) {
				$this->_uri->path[] = $this->username;
			} else {
				$this->_uri->path[] = $this->id;
			}
		}
		return $this->_uri->url($options);
	}
}
