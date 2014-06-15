<?php
namespace Aqua\User;

use Aqua\Core\App;
use Aqua\Core\Meta;
use Aqua\Log\BanLog;
use Aqua\Log\ProfileUpdateLog;
use Aqua\Log\TransferLog;
use Aqua\Event\Event;
use Aqua\Ragnarok\Server;
use Aqua\SQL\Query;
use Aqua\SQL\Search;
use Aqua\UI\Tag;
use Phpass\Hash;

class Account
{
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var string
	 */
	public $username = '';
	/**
	 * @var string
	 */
	public $displayName = '';
	/**
	 * @var string
	 */
	public $email = '';
	/**
	 * @var int
	 */
	public $status = 0;
	/**
	 * @var string
	 */
	public $roleId;
	/**
	 * @var int
	 */
	public $registrationDate = 0;
	/**
	 * @var int
	 */
	public $birthDate = 0;
	/**
	 * @var int
	 */
	public $unbanDate = 0;
	/**
	 * @var int
	 */
	public $credits = 0;
	/**
	 * @var string
	 */
	public $avatar = '';
	/**
	 * @var bool
	 */
	public $isAvatarUploaded = false;
	/**
	 * @var string
	 */
	public $profileUrl = '';
	/**
	 * @var \Aqua\Core\Meta
	 */
	public $meta;
	/**
	 * @var \Aqua\User\Account
	 */
	public static $users = array();
	/**
	 * @var array
	 */
	public static $cache = null;

	const STATUS_NORMAL                   = 0;
	const STATUS_AWAITING_VALIDATION      = 1;
	const STATUS_SUSPENDED                = 2;
	const STATUS_BANNED                   = 3;
	const STATUS_LOCKED                   = 4;
	const STATUS_FLAGGED                  = 5;

	const REGISTRATION_USERNAME_TAKEN     = 1;
	const REGISTRATION_DISPLAY_NAME_TAKEN = 2;
	const REGISTRATION_EMAIL_TAKEN        = 3;
	const INVALID_CREDENTIALS             = -1;

	const FIELD_OK                        = 0;
	const FIELD_INVALID_SIZE              = 1;
	const FIELD_INVALID_CHARACTERS        = 2;
	const FIELD_RESTRICTED                = 3;

	const CACHE_KEY                       = 'general_cache.account';
	const CACHE_TTL                       = 86400;
	const CACHE_LATEST_ACCOUNTS           = 5;
	const CACHE_REGISTRATION_WEEKS        = 2;

	const OPT_ADMIN_NOTIFY_COMMENT_REPORT = 1;

	/**
	 * @return string
	 */
	public function status()
	{
		return __('account-state', $this->status);
	}

	/**
	 * @return bool
	 */
	public function isBanned()
	{
		return ($this->status === self::STATUS_SUSPENDED || $this->status === self::STATUS_BANNED);
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function birthDate($format)
	{
		return strftime($format, $this->birthDate);
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function registrationDate($format)
	{
		return strftime($format, $this->registrationDate);
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function unbanDate($format)
	{
		return strftime($format, $this->unbanDate);
	}

	/**
	 * @return \Aqua\User\Role
	 */
	public function role()
	{
		return Role::get($this->roleId);
	}

	/**
	 * @param int $size
	 * @return string
	 */
	public function avatar($size = null)
	{
		if(!$this->avatar) {
			$path = App::settings()->get('account')->get('default_avatar', '');
			if($path) {
				return \Aqua\URL . $path;
			} else {
				return \Aqua\BLANK;
			}
		} else {
			if($this->isAvatarUploaded) {
				return \Aqua\URL . $this->avatar . "?s={$size}";
			} else {
				return $this->avatar . "?s={$size}";
			}
		}
	}

	/**
	 * @param bool $link
	 * @return \Aqua\UI\Tag
	 */
	public function display($link = null)
	{
		if($link === null) {
			$link = (\Aqua\PROFILE !== 'ADMINISTRATION');
		}
		$display = $this->role()->display($this->displayName, 'ac-username');
		if($link && $this->profileUrl) {
			$link = new Tag('a');
			$link->append($display);
			if($this->profileUrl[0] === '/' &&
			   $this->profileUrl[1] !== '/') {
				$link->attr('href', \Aqua\URL . $this->profileUrl);
			} else {
				$link->attr('href', $this->profileUrl);
			}
			return $link;
		} else {
			return $display;
		}
	}

	public function spamFilter()
	{
		$isSpam = false;
		$feedback = array( $this, $isSpam );
		if(Event::fire('account.spam-filter', $feedback) === false || $isSpam) {
			$this->update(array( 'status' => self::STATUS_FLAGGED ));
			return true;
		}
		return false;
	}

	/**
	 * @param string $new_name
	 * @param bool   $bypass
	 * @return bool
	 */
	public function updateDisplayName($new_name, $bypass = false)
	{
		if(strcasecmp($new_name, $this->displayName) === 0) {
			return ($this->update(array( 'display_name' => $new_name )) ? 1 : -1);
		} else {
			return $this->_updateField('display_name', $new_name, $bypass);
		}
	}

	/**
	 * @param string $new_email
	 * @param bool   $bypass
	 * @return bool
	 */
	public function updateEmail($new_email, $bypass = false)
	{
		return $this->_updateField('email', $new_email, $bypass);
	}

	/**
	 * @param string $new_password
	 * @param bool   $bypass
	 * @return bool
	 */
	public function updatePassword($new_password, $bypass = false)
	{
		return $this->_updateField('password', $new_password, $bypass);
	}

	/**
	 * @param int  $timestamp
	 * @param bool $bypass
	 * @return bool
	 */
	public function updateBirthday($timestamp, $bypass = false)
	{
		return $this->_updateField('birthday', date('Y-m-d', $timestamp), $bypass);
	}

	/**
	 * @param string $type
	 * @param string $new_value
	 * @param bool   $bypass
	 * @return bool
	 * @access protected
	 */
	protected function _updateField($type, $new_value, $bypass)
	{
		$settings = App::settings()->get('account')->get($type);
		if(!$bypass && ($limit = $settings->get('update_limit', 0)) &&
		   ($days = $settings->get('update_days', 0))) {
			$search = ProfileUpdateLog::search()
				->columns(array( 'count' => 'COUNT(1)' ), false)
				->setColumnType(array( 'count' => 'integer' ))
				->where(array(
					'user_id' => $this->id,
					'field'   => $type,
					'date'    => array( Search::SEARCH_HIGHER, date('Y-m-d H:i:s', strtotime("-{$days} days")) )
				))
				->parser(null)
				->groupBy(array())
				->query();
			if($search->get('count', 0) >= $limit) {
				return 0;
			}
		}
		if($type === 'password') {
			$old_value = Query::select(App::connection())
				->columns(array( 'password' => '_password' ))
				->from(ac_table('users'))
				->where(array( 'id' => $this->id ))
				->limit(1)
				->query()
				->results[0]['password'];
			$password = self::hashPassword($new_value);
			if(!$this->update(array( 'password_hashed' => $password, 'password' => $new_value ))) {
				return -1;
			}
			$new_value = $password;
		} else if($type === 'birthday') {
			$old_value = date('Y-m-d', $this->birthDate);
			if(!$this->update(array( 'birthday' => \DateTime::createFromFormat('Y-m-d', $new_value)->getTimestamp() ))) {
				return -1;
			}
		} else {
			switch($type) {
				case 'display_name':
					$old_value = $this->displayName;
					break;
				case 'email':
					$old_value = $this->email;
					break;
				default:
					return -1;
			}
			if(!$this->update(array( $type => $new_value ))) {
				return -1;
			}
		}
		ProfileUpdateLog::logSql($this, $type, $new_value, $old_value);

		return true;
	}

	/**
	 * @param array $options
	 * @return bool
	 */
	public function update(array $options)
	{
		$values   = array();
		$acc_data = array();
		$update = '';
		$options = array_intersect_key($options, array_flip(array(
				'username', 'credits', 'email', 'status', 'password',
				'password_hashed', 'birthday', 'unban_date', 'role',
				'avatar', 'display_name', 'profile_url'
			)));
		if(empty($options)) {
			return false;
		}
		$options = array_map(function($val) {
			return (is_string($val) ? trim($val) : $val);
		}, $options);
		if(array_key_exists('username', $options) && $options['username'] !== $this->username) {
			$values['username'] = $acc_data['username'] = $options['username'];
			$update .= '_username = ?, ';
		}
		if(array_key_exists('credits', $options) && $options['credits'] !== $this->credits) {
			$values['credits'] = $acc_data['credits'] = $options['credits'];
			$update .= '_credits = ?, ';
		}
		if(array_key_exists('email', $options) && $options['email'] !== $this->email) {
			$values['email'] = $acc_data['email'] = $options['email'];
			$update .= '_email = ?, ';
		}
		if(array_key_exists('status', $options) && $options['status'] !== $this->status) {
			$values['status'] = $acc_data['status'] = $options['status'];
			$update .= '_status = ?, ';
		}
		if(array_key_exists('password_hashed', $options)) {
			$values['password'] = $options['password_hashed'];
			$update .= '_password = ?, ';
		} else if(array_key_exists('password', $options)) {
			$values['password'] = self::hashPassword($options['password']);
			$update .= '_password = ?, ';
		}
		if(array_key_exists('birthday', $options) && $options['birthday'] !== $this->birthDate) {
			$values['birthday']    = date('Y-m-d', $options['birthday']);
			$acc_data['birthDate'] = $options['birthday'];
			$update .= '_birthday = ?, ';
		}
		if(array_key_exists('unban_date', $options) && $options['unban_date'] !== $this->unbanDate) {
			$acc_data['unbanDate'] = $options['unban_date'];
			if($options['unban_date'] === null) {
				$update .= '_unban_date = NULL, ';
			} else {
				$values['unban_date'] = date('Y-m-d H:i:s', $options['unban_date']);
				$update .= '_unban_date = ?, ';
			}
		}
		if(array_key_exists('role', $options) && $options['role'] !== $this->roleId) {
			$values['role'] = $acc_data['roleId'] = $options['role'];
			$update .= '_role_id = ?, ';
		}
		if(array_key_exists('profile_url', $options) && $options['profile_url'] !== $this->profileUrl) {
			$values['profile_url'] = $acc_data['profileUrl'] = $options['profile_url'];
			$update .= '_profile_url = ?, ';
		}
		if(array_key_exists('avatar', $options)) {
			$values['avatar'] = $acc_data['avatar'] = $options['avatar'];
			if(substr($options['avatar'], 0, 17) === '/uploads/avatar/') {
				$acc_data['isAvatarUploaded'] = true;
			} else {
				$acc_data['isAvatarUploaded'] = false;
			}
			$update .= '_avatar = ?, ';
		}
		if(array_key_exists('display_name', $options) && $options['display_name'] !== $this->displayName) {
			$values['display_name'] = $acc_data['displayName'] = $options['display_name'];
			$update .= '_display_name = ?, ';
		}
		if(empty($values)) {
			return false;
		}
		$update   = substr($update, 0, -2);
		$values[] = $this->id;
		$sth      = App::connection()->prepare(sprintf('
		UPDATE %s
		SET %s
		WHERE id = ?
		LIMIT 1
		', ac_table('users'), $update));
		if(!$sth->execute(array_values($values)) || !$sth->rowCount()) {
			return false;
		}
		array_pop($values);
		if(array_key_exists('unbanDate', $acc_data)) $values['unban_date'] = $acc_data['unbanDate'];
		if(array_key_exists('birthDate', $acc_data)) $values['birthday'] = $acc_data['birthDate'];
		if(array_key_exists('password', $options)) $values['plain_password'] = $options['password'];
		unset($options['password']);
		$feedback = array( $this, $values );
		Event::fire('account.update', $feedback);
		foreach($acc_data as $key => $val) {
			$this->$key = $val;
		}
		self::$cache !== null or self::fetchCache(null, true);
		if(!empty(self::$cache) && (array_key_exists('role', $values) || array_key_exists('avatar', $values) ||
		                            array_key_exists('display_name', $values))) {
			$update = 0;
			foreach(self::$cache['last_user'] as &$data) {
				if($data['id'] !== $this->id) continue;
				$data['role_id']      = $this->roleId;
				$data['avatar']       = $this->avatar();
				$data['display_name'] = $this->displayName;
				++$update;
				break;
			}
			foreach(self::$cache['reg_stats'] as &$data) {
				if($data['id'] !== $this->id) continue;
				$data['role_id']      = $this->roleId;
				$data['display_name'] = $this->displayName;
				++$update;
				break;
			}
			if($update) {
				App::cache()->store(self::CACHE_KEY, self::$cache, self::CACHE_TTL);
			}
		}

		return true;
	}

	/**
	 * @param bool $update
	 */
	public function removeAvatar($update = true)
	{
		if($this->isAvatarUploaded) {
			$file = \Aqua\ROOT . '/' . $this->avatar;
			if(file_exists($file)) {
				@unlink($file);
			}
		}
		if($update) {
			$this->update(array( 'avatar' => '' ));
			$feedback = array( $this );
			Event::fire('account.remove-avatar', $feedback);
		}
	}

	/**
	 * @param string $path
	 * @param string $original
	 */
	public function setAvatar($path, $original)
	{
		$this->removeAvatar(false);
		$this->update(array( 'avatar' => $path ));
		$feedback = array( $this, 'file', $path, $original );
		Event::fire('account.update-avatar', $feedback);
	}

	/**
	 * @param string $email
	 */
	public function setGravatar($email)
	{
		$this->removeAvatar(false);
		$url = 'https://secure.gravatar.com/avatar/' . md5(strtolower($email));
		$this->update(array( 'avatar' => $url ));
		$feedback = array( $this, 'gravatar', $url, $email );
		Event::fire('account.update-avatar', $feedback);
	}

	/**
	 * @param \Aqua\User\Account $user
	 * @param int                $unban_time
	 * @param string             $reason
	 */
	public function ban(self $user, $unban_time = null, $reason = null)
	{
		$this->update(array(
				'status'     => ($unban_time ? self::STATUS_SUSPENDED : self::STATUS_BANNED),
				'unban_date' => $unban_time
			));
		BanLog::logSql($user,
		               $this,
		               $unban_time,
		               ($unban_time ? BanLog::TYPE_BAN_TEMPORARILY : BanLog::TYPE_BAN_PERMANENTLY),
		               $reason);
		$feedback = array( $this, $user, $unban_time, $reason );
		Event::fire('account.ban', $feedback);
	}

	/**
	 * @param \Aqua\User\Account $user
	 * @param string             $reason
	 */
	public function unban(self $user = null, $reason = null)
	{
		$this->update(array( 'status' => self::STATUS_NORMAL, 'unban_date' => null ));
		if($user) {
			BanLog::logSql($user, $this, null, BanLog::TYPE_UNBAN, $reason);
		}
		$feedback = array( $this, $user, $reason );
		Event::fire('account.unban', $feedback);
	}

	/**
	 * @param Account    $target
	 * @param  int       $amount
	 * @return bool
	 * @throws \Exception
	 */
	public function transferCredits(self $target, $amount)
	{
		App::connection()->beginTransaction();
		try {
			$sth = App::connection()->prepare(sprintf('
			UPDATE %s
			SET _credits = _credits - :amount
			WHERE id = :id
			', ac_table('users')));
			$sth->bindValue(':amount', $amount, \PDO::PARAM_INT);
			$sth->bindValue(':id', $this->id, \PDO::PARAM_INT);
			$sth->execute();
			$sth->closeCursor();
			$sth = App::connection()->prepare(sprintf('
			UPDATE %s
			SET _credits = _credits + :amount
			WHERE id = :id
			', ac_table('users')));
			$sth->bindValue(':amount', $amount, \PDO::PARAM_INT);
			$sth->bindValue(':id', $target->id, \PDO::PARAM_INT);
			$sth->execute();
			$sth->closeCursor();
			App::connection()->commit();
		} catch(\Exception $exception) {
			App::connection()->rollBack();
			throw $exception;
		}
		$feedback = array( $this, $target, $amount );
		Event::fire('account.transfer', $feedback);
		TransferLog::logSql($this, $target, $amount);

		return true;
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public static function search()
	{
		return Query::search(App::connection())
			->columns(array(
				'id'                => 'u.id',
				'username'          => 'u._username',
				'display_name'      => 'u._display_name',
				'email'             => 'u._email',
				'avatar'            => 'u._avatar',
				'profile_url'       => 'u._profile_url',
				'role_id'           => 'u._role_id',
				'birthday'          => 'UNIX_TIMESTAMP(u._birthday)',
				'status'            => 'u._status',
				'credits'           => 'u._credits',
				'registration_date' => 'UNIX_TIMESTAMP(u._registration_date)',
				'unban_date'        => 'UNIX_TIMESTAMP(u._unban_date)',
				'tos'               => 'u._agreed_tos',
			))->whereOptions(array(
				'id'                => 'u.id',
				'username'          => 'u._username',
				'display_name'      => 'u._display_name',
				'email'             => 'u._email',
				'avatar'            => 'u._avatar',
				'profile_url'       => 'u._profile_url',
				'role_id'           => 'u._role_id',
				'birthday'          => 'u._birthday',
				'status'            => 'u._status',
				'credits'           => 'u._credits',
				'registration_date' => 'u._registration_date',
				'unban_date'        => 'u._unban_date',
				'tos'               => 'u._agreed_tos',
			))
			->from(ac_table('users'), 'u')
			->groupBy('u.id')
			->parser(array( __CLASS__, 'parseAccountSql' ));
	}

	/**
	 * Get a single user by ID, username or e-mail
	 *
	 * @param mixed    $id
	 * @param string   $type
	 * @return \Aqua\User\Account
	 */
	public static function get($id, $type = 'id')
	{
		if($type !== 'id' || !isset(self::$users[$id])) {
			$search = Query::select(App::connection())
				->columns(array(
					'id'                => 'u.id',
					'username'          => 'u._username',
					'display_name'      => 'u._display_name',
					'email'             => 'u._email',
					'avatar'            => 'u._avatar',
					'profile_url'       => 'u._profile_url',
					'role_id'           => 'u._role_id',
					'birthday'          => 'UNIX_TIMESTAMP(u._birthday)',
					'status'            => 'u._status',
					'credits'           => 'u._credits',
					'registration_date' => 'UNIX_TIMESTAMP(u._registration_date)',
					'unban_date'        => 'UNIX_TIMESTAMP(u._unban_date)',
					'tos'               => 'u._agreed_tos',
				))
				->from(ac_table('users'), 'u')
				->limit(1)
				->parser(array( __CLASS__, 'parseAccountSql' ));
			switch($type) {
				case 'id':
					$search->where(array( 'u.id' => $id ));
					break;
				case 'username':
				case 'email':
				case 'display_name';
					$search->where(array( "u._{$type}" => $id ));
					break;
				default:
					return null;
			}
			$search->query();

			return ($search->valid() ? $search->current() : null);
		} else {
			return self::$users[$id];
		}
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @param        $id
	 * @return int
	 */
	public static function checkCredentials($username, $password, &$id = null)
	{
		$select = Query::select(App::connection())
			->columns(array(
				'id'         => 'u.id',
				'password'   => 'u._password',
				'status'     => 'u._status',
				'unban_date' => 'UNIX_TIMESTAMP(u._unban_date)'
			))
			->setColumnType(array(
				'id'         => 'integer',
				'status'     => 'integer',
				'unban_date' => 'timestamp'
			))
			->from(ac_table('users'), 'u')
			->limit(1);
		if(App::settings()->get('account')->get('email_login', false)) {
			$select->where(array( array( 'u._username' => $username, 'OR', 'u._email' => $username ) ));
		} else {
			$select->where(array( 'u._username' => $username ));
		}
		$select->where(array(array(
				'u._status'            => array( Search::SEARCH_DIFFERENT, self::STATUS_AWAITING_VALIDATION ),
				'OR',
				'u._registration_date' => array(
					Search::SEARCH_LOWER,
					date('Y-m-d H:i:s', time() - (App::settings()
						                              ->get('account')
						                              ->get('registration')
						                              ->get('validation_time', 48) * 3600))
			))))
			->query();
		if($select->valid()) {
			$data = $select->current();
			$id   = $data['id'];
			if(!self::checkPassword($password, $data['password'])) {
				return self::INVALID_CREDENTIALS;
			}
			switch($data['status']) {
				case self::STATUS_NORMAL:
				case self::STATUS_AWAITING_VALIDATION:
					break;
				case self::STATUS_SUSPENDED:
					if((int)$data['unban_date'] <= time()) {
						break;
					}
				default:
					return $data['status'];
			}

			return 0;
		} else {
			return self::INVALID_CREDENTIALS;
		}
	}

	/**
	 * @param string $username
	 * @param        $error_message
	 * @return int
	 * @see \Aqua\User\Account::FIELD_*
	 */
	public static function checkValidUsername($username, &$error_message)
	{
		$settings = App::settings()->get('account')->get('username');
		$error_id = self::FIELD_OK;
		$feedback = array( $username, &$error_id, &$error_message );
		if(Event::fire('account.validate-username', $feedback) === false) return $error_id;
		$min_len = $settings->get('min_length', 3);
		$max_len = $settings->get('max_length', 50);
		$length  = strlen($username);
		$regex   = $settings->get('regex', null);
		if($length < $min_len || ($max_len > 0 && $length > $max_len)) {
			if($max_len < 1)
					{
						$error_message = __('profile', 'username-len-min', $min_len);
					}
			else {
				$error_message = __('profile', 'username-len-range', $min_len, $max_len);
			}

			return self::FIELD_INVALID_SIZE;
		}
		if($regex && preg_match_all($regex, $username, $match)) {
			$error_message = __('profile', 'username-invalid-character', implode(', ', array_unique($match[0])));

			return self::FIELD_INVALID_CHARACTERS;
		}

		return self::FIELD_OK;
	}

	/**
	 * @param string $display_name
	 * @param        $error_message
	 * @return int
	 * @see \Aqua\User\Account::FIELD_*
	 */
	public static function checkValidDisplayName($display_name, &$error_message)
	{
		$settings = App::settings()->get('account')->get('display_name');
		$error_id = self::FIELD_OK;
		$feedback = array( $display_name, &$error_id, &$error_message );
		if(Event::fire('account.validate-display-name', $feedback) === false) return $error_id;
		$min_len = $settings->get('min_length', 3);
		$max_len = $settings->get('max_length', 50);
		$length  = strlen($display_name);
		$regex   = $settings->get('regex', null);
		if($length < $min_len || ($max_len > 0 && $length > $max_len)) {
			if($max_len < 1)
				$error_message = __('profile', 'display-name-len-min', $min_len);
			else
				$error_message = __('profile', 'display-name-len-range', $min_len, $max_len);

			return self::FIELD_INVALID_SIZE;
		}
		if($regex && preg_match_all($regex, $display_name, $match)) {
			$error_message = __('profile', 'display-name-invalid-character', implode(', ', array_unique($match[0])));

			return self::FIELD_INVALID_CHARACTERS;
		}

		return self::FIELD_OK;
	}

	/**
	 * @param string $password
	 * @param        $error_message
	 * @return int
	 * @see \Aqua\User\Account::FIELD_*
	 */
	public static function checkValidPassword($password, &$error_message)
	{
		$settings = App::settings()->get('account')->get('password');
		$error_id = self::FIELD_OK;
		$feedback = array( $password, &$error_id, &$error_message );
		if(Event::fire('account.validate-password', $feedback) === false) return $error_id;
		$min_len = $settings->get('min_length', 3);
		$max_len = $settings->get('max_length', 50);
		$length  = strlen($password);
		$regex   = $settings->get('regex', null);
		if($length < $min_len || ($max_len > 0 && $length > $max_len)) {
			if($max_len < 1)
				$error_message = __('profile', 'password-len-min', $min_len);
			else
				$error_message = __('profile', 'password-len-range', $min_len, $max_len);

			return self::FIELD_INVALID_SIZE;
		}
		if($regex && preg_match_all($regex, $password, $match)) {
			$error_message = __('profile', 'password-invalid-character', implode(', ', array_unique($match[0])));

			return self::FIELD_INVALID_CHARACTERS;
		}

		return self::FIELD_OK;
	}

	/**
	 * @param string $email
	 * @param        $error_message
	 * @return int
	 * @see \Aqua\User\Account::FIELD_*
	 */
	public static function checkValidEmail($email, &$error_message)
	{
		$settings = App::settings()->get('account')->get('email');
		$error_id = self::FIELD_OK;
		$feedback = array( $email, &$error_id, &$error_message );
		if(Event::fire('account.validate-email', $feedback) === false) return $error_id;
		$min_len = $settings->get('min_length', 6);
		$max_len = $settings->get('max_length', 39);
		$length  = strlen($email);
		if($length < $min_len || ($max_len > 0 && $length > $max_len)) {
			if($max_len < 1)
				$error_message = __('profile', 'email-len-min', $min_len);
			else
				$error_message = __('profile', 'email-len-range', $min_len, $max_len);

			return self::FIELD_INVALID_SIZE;
		}
		if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$error_message = __('form', 'email-invalid');

			return self::FIELD_INVALID_CHARACTERS;
		}

		return self::FIELD_OK;
	}

	/**
	 * @param int $timestamp
	 * @param     $error_message
	 * @return int
	 * @see \Aqua\User\Account::FIELD_*
	 */
	public static function checkValidBirthday($timestamp, &$error_message)
	{
		$settings = App::settings()->get('account')->get('birthday');
		$error_id = self::FIELD_OK;
		$feedback = array( $timestamp, &$error_id, &$error_message );
		if(Event::fire('account.validate-birthday', $feedback) === false) return $error_id;
		$min_date    = $settings->get('min_date', 0);
		$max_date    = $settings->get('max_date', 0);
		$date_format = App::settings()->get('date_format', '');
		if($timestamp < $min_date || ($max_date && $timestamp > $max_date)) {
			if($max_date == 0)
				$error_message = __('profile', 'birthday-len-min', strftime($date_format, $min_date));
			else
				$error_message = __('profile',
				                    'birthday-len-range',
				                    strftime($date_format, $min_date),
				                    strftime($date_format, $max_date));

			return self::FIELD_INVALID_SIZE;
		}

		return self::FIELD_OK;
	}

	/**
	 * Check if the a username, display name or email is available
	 *
	 * @param string $username
	 * @param string $display
	 * @param string $email
	 * @param int    $id      Account id to exclude from search
	 * @return int
	 * @see \Aqua\User\Account::REGISTRATION_*
	 */
	public static function exists($username, $display, $email, $id = null)
	{
		if($username === null && $display === null && $email === null) {
			return 0;
		}
		$error_id = 0;
		$feedback = array( $username, $display, $email, $id, &$error_id );
		Event::fire('account.check-exists', $feedback);
		if($error_id !== 0) return $error_id;
		$select = Query::select(App::connection())
			->columns(array(
				'username'     => 'u._username',
				'email'        => 'u._email',
				'display_name' => 'u._display_name',
			))
			->from(ac_table('users'), 'u');
		if($id) {
			$select->where(array( 'id' => array( Search::SEARCH_DIFFERENT, $id ) ));
		}
		$where = array();
		if($username !== null) {
			$where['u._username'] = $username;
			$where[]           = 'OR';
		}
		if($email !== null) {
			$where['u._email'] = $email;
			$where[]        = 'OR';
		}
		if($display !== null) {
			$where['u._display_name'] = $display;
			$where[]          = 'OR';
		}
		array_pop($where);
		$select->where(array($where, array(
				'u._status'            => array( Search::SEARCH_DIFFERENT, self::STATUS_AWAITING_VALIDATION ),
				'OR',
				'u._registration_date' => array(
					Search::SEARCH_LOWER,
					date('Y-m-d H:i:s', time() - (App::settings()
						          ->get('account')
						          ->get('registration')
						          ->get('validation_time', 48) * 3600))
			))))
			->limit(1)
			->query();
		if($select->valid()) {
			$account = $select->current();
			if($username !== null && strcasecmp($account['username'], $username) == 0) {
				return self::REGISTRATION_USERNAME_TAKEN;
			} else if($display !== null && strcasecmp($account['display_name'], $display) === 0) {
				return self::REGISTRATION_DISPLAY_NAME_TAKEN;
			} else if($email !== null && strcasecmp($account['email'], $email) === 0) {
				return self::REGISTRATION_EMAIL_TAKEN;
			}
		}

		return 0;
	}

	/**
	 * Register an account
	 *
	 * @param string              $username
	 * @param string              $display_name
	 * @param string              $plain_password
	 * @param string              $email
	 * @param string              $birth_date
	 * @param \Aqua\User\Role|int $role
	 * @param int                 $status
	 * @param bool                $tos
	 * @return \Aqua\User\Account|int
	 * @see \Aqua\User\Account::STATUS_*
	 * @see \Aqua\User\Account::REGISTRATION_*
	 */
	public static function register(
		$username,
		$display_name,
		$plain_password,
		$email,
		$birth_date,
		$role,
		$status = self::STATUS_NORMAL,
		$tos = false
	) {
		$username     = trim($username);
		$email        = trim(strtolower($email));
		$display_name = trim($display_name);
		$display_name = empty($display_name) ? $username : $display_name;
		if(($exists = self::exists($username, $display_name, $email)) !== 0) {
			return $exists;
		}
		$sth      = App::connection()->prepare(sprintf('
		INSERT INTO %s (
			_username,
			_display_name,
			_password,
			_email,
			_birthday,
			_role_id,
			_status,
			_agreed_tos,
			_registration_date
			)
		VALUES (
			:username,
			:display,
			:password,
			:email,
			:birth_date,
			:role_id,
			:status,
			:tos,
			NOW()
			)
		', ac_table('users')));
		$password = self::hashPassword($plain_password);
		if(is_int($role)) {
			$role = Role::get($role);
		} else if($role === null) {
			$role = Role::get(Role::ROLE_USER);
		}
		if(!$birth_date) {
			$birth_date = '0000-00-00';
		} else if(is_int($birth_date)) {
			$birth_date = date('Y-m-d', $birth_date);
		}
		$sth->bindValue(':username', $username, \PDO::PARAM_LOB);
		$sth->bindValue(':display', $display_name, \PDO::PARAM_LOB);
		$sth->bindValue(':password', $password, \PDO::PARAM_STR);
		$sth->bindValue(':role_id', $role->id, \PDO::PARAM_INT);
		$sth->bindValue(':email', $email, \PDO::PARAM_STR);
		$sth->bindValue(':birth_date', $birth_date, \PDO::PARAM_STR);
		$sth->bindValue(':status', $status, \PDO::PARAM_INT);
		$sth->bindValue(':tos', ($tos ? 'y' : 'n'), \PDO::PARAM_STR);
		$sth->execute();
		$account                   = new self;
		$account->id               = (int)App::connection()->lastInsertId();
		$account->username         = $username;
		$account->displayName      = $display_name;
		$account->email            = $email;
		$account->birthDate        = strtotime($birth_date);
		$account->registrationDate = time();
		$account->roleId           = $role->id;
		$account->status           = $status;
		self::$users[$account->id] = $account;
		$sth->closeCursor();
		$feedback = array( $plain_password, $account );
		Event::fire('account.registration-complete', $feedback);
		self::$cache !== null or self::fetchCache(null, true);
		if(!empty(self::$cache)) {
			self::$cache['count']++;
			array_unshift(self::$cache['last_user'],array(
					'id'                => $account->id,
					'role_id'           => $account->roleId,
					'display_name'      => $account->displayName,
					'registration_date' => $account->registrationDate
				));
			if(count(self::$cache['last_user']) > self::CACHE_LATEST_ACCOUNTS) {
				self::$cache['last_user'] = array_slice(
					self::$cache['last_user'],
					0,
					self::CACHE_LATEST_ACCOUNTS,
					false
				);
			}
			if(self::$cache['reg_stats_expire'] < time()) {
				self::rebuildCache('reg_stats');
			} else {
				self::$cache['reg_stats'][date('w')]++;
			}
			App::cache()->store(self::CACHE_KEY, self::$cache, self::CACHE_TTL);
		}

		return $account;
	}

	/**
	 * @param string $password
	 * @return string
	 */
	public static function hashPassword($password)
	{
		$phpass = new Hash(App::settings()->get('account')->get('phpass')->toArray());

		return $phpass->hashPassword($password);
	}

	/**
	 * @param string $plain_password
	 * @param string $hashed_password
	 * @return bool
	 */
	public static function checkPassword($plain_password, $hashed_password)
	{
		$phpass = new Hash(App::settings()->get('account')->get('phpass')->toArray());

		return $phpass->checkPassword($plain_password, $hashed_password);
	}

	/**
	 * @param string|null $name
	 * @param bool        $internal
	 * @return mixed
	 */
	public static function fetchCache($name = null, $internal = false)
	{
		self::$cache === null or (self::$cache = App::cache()->fetch(self::CACHE_KEY, array()));
		if($internal) {
			return null;
		}
		if(empty(self::$cache)) {
			self::$cache = array();
			self::rebuildCache();
		}
		if((!$name || $name === 'reg_stats') &&
		   (!isset(self::$cache['reg_stats_expire']) || self::$cache['reg_stats_expire'] < time())
		) {
			self::rebuildCache('reg_stats');
		}
		if(!$name) {
			return self::$cache;
		} else if(array_key_exists($name, self::$cache)) {
			return self::$cache[$name];
		} else {
			return null;
		}
	}

	/**
	 * @param string|null $name
	 */
	public static function rebuildCache($name = null)
	{
		self::$cache === null or self::fetchCache(null, true);
		if(!$name || $name === 'last_user') {
			self::$cache['last_user'] = Query::select(App::connection())
				->columns(array(
					'id'                => 'u.id',
					'role_id'           => 'u._role_id',
					'display_name'      => 'u._display_name',
					'registration_date' => 'UNIX_TIMESTAMP(u._registration_date)',
					'avatar'            => 'u._avatar'
				))
				->setColumnType(array(
					'id'                => 'integer',
					'role_id'           => 'integer',
					'registration_date' => 'timestamp',
				))
				->from(ac_table('users'), 'u')
				->where(array(
					'u._status'            => array( Search::SEARCH_DIFFERENT, self::STATUS_AWAITING_VALIDATION ),
					'OR',
					'u._registration_date' => array(
						Search::SEARCH_LOWER,
						date('Y-m-d H:i:s', time() - (App::settings()
									->get('account')
									->get('registration')
									->get('validation_time', 48) * 3600))
				)))
				->order(array( 'u._registration_date' => 'DESC' ))
				->limit(self::CACHE_LATEST_ACCOUNTS)
				->query()
				->results;
			foreach(self::$cache['last_user'] as &$user) {
				if(substr($user['avatar'], 0, 16) === '/uploads/avatar/') {
					$user['avatar'] = \Aqua\URL . $user['avatar'];
				} else if(empty($user['avatar'])) {
					$path = App::settings()->get('account')->get('default_avatar', '');
					if($path) {
						$user['avatar'] = \Aqua\URL . $path;
					} else {
						$user['avatar'] = \Aqua\BLANK;
					}
				}
			}
		}
		if(!$name || $name === 'count') {
			self::$cache['count'] = Query::select(App::connection())
				->columns(array( 'count' => 'COUNT(1)' ))
				->setColumnType(array( 'count' => 'integer' ))
				->from(ac_table('users'), 'u')
				->where(array(
					'u._status'            => array( Search::SEARCH_DIFFERENT, self::STATUS_AWAITING_VALIDATION ),
					'OR',
					'u._registration_date' => array(
						Search::SEARCH_LOWER,
						date('Y-m-d H:i:s', time() - (App::settings()
									->get('account')
									->get('registration')
									->get('validation_time', 48) * 3600))
				)))
				->query()
				->get('count', 0);
		}
		if(!$name || $name === 'reg_stats') {
			$sth      = App::connection()->query(sprintf('
			SELECT UNIX_TIMESTAMP(_registration_date)
			FROM %s
			ORDER BY _registration_date ASC
			LIMIT 1
			', ac_table('users')));
			$min_date = (int)$sth->fetchColumn(0);
			$sth      = App::connection()->prepare(sprintf('
			SELECT COUNT(*), UNIX_TIMESTAMP(DATE(_registration_date)) AS `date`
			FROM %s
			WHERE _registration_date BETWEEN :this_week AND :next_week
			GROUP BY `date`;
			', ac_table('users')));
			$weekday  = (int)date('w');
			if($weekday === 0) {
				$sun = strtotime('midnight');
			} else {
				$sun = strtotime('last sunday midnight');
			}
			if($weekday === 6) {
				$sat = strtotime('23:59:59');
			} else {
				$sat = strtotime('next saturday 23:59:59');
			}
			self::$cache['reg_stats']        = array();
			self::$cache['reg_stats_expire'] = strtotime('+1 day', $sat);
			for($i = 0; $i < self::CACHE_REGISTRATION_WEEKS; ++$i) {
				$sth->bindValue(':next_week', date('Y-m-d H:i:s', $sat), \PDO::PARAM_STR);
				$sth->bindValue(':this_week', date('Y-m-d H:i:s', $sun), \PDO::PARAM_STR);
				$sth->execute();
				$week = array( 0, 0, 0, 0, 0, 0, 0 );
				while($data = $sth->fetch(\PDO::FETCH_NUM)) {
					$week[date('w', (int)$data[1])] = (int)$data[0];
				}
				$sth->closeCursor();
				self::$cache['reg_stats'][] = $week;
				$sun                        = strtotime('-7 day', $sun);
				$sat                        = strtotime('-7 day', $sat);
				if($sat < $min_date) {
					break;
				}
			}
			for($i = $weekday + 1; $i < 7; ++$i) {
				self::$cache['reg_stats'][0][$i] = null;
			}
		}
		App::cache()->store(self::CACHE_KEY, self::$cache, self::CACHE_TTL);
	}

	/**
	 * @param array $data
	 * @return \Aqua\User\Account
	 */
	public static function parseAccountSql(array $data)
	{
		if(isset(self::$users[$data['id']])) {
			$acc = self::$users[$data['id']];
		} else {
			$acc       = new self;
			$acc->meta = new Meta(ac_table('user_meta'), $data['id']);
		}
		$acc->id               = (int)$data['id'];
		$acc->status           = (int)$data['status'];
		$acc->credits          = (int)$data['credits'];
		$acc->birthDate        = (int)$data['birthday'];
		$acc->registrationDate = (int)$data['registration_date'];
		$acc->unbanDate        = (int)$data['unban_date'];
		$acc->roleId           = (int)$data['role_id'];
		$acc->username         = $data['username'];
		$acc->displayName      = $data['display_name'];
		$acc->email            = $data['email'];
		$acc->avatar           = $data['avatar'];
		$acc->profileUrl       = $data['profile_url'];
		if(substr($acc->avatar, 0, 17) === '/uploads/avatar/') {
			$acc->isAvatarUploaded = true;
		}
		if($acc->status === self::STATUS_SUSPENDED && $acc->unbanDate <= time()) {
			$acc->unban();
		}
		if(Role::get($data['role_id'])) {
			$acc->roleId = (int)$data['role_id'];
		} else {
			$acc->roleId = Role::ROLE_USER;
		}
		self::$users[$acc->id] = $acc;

		return $acc;
	}
}
