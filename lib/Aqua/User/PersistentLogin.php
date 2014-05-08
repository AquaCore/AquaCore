<?php
namespace Aqua\User;

use Aqua\Core\App;
use Aqua\Event\Event;

class PersistentLogin
{
	/**
	 * Validate a persistent login key and generates a new one.
	 *
	 * @param string $login_key
	 * @return \Aqua\User\Account|bool
	 */
	public static function authenticate(&$login_key)
	{
		if(!self::getKey($login_key, $id, $key)) {
			return false;
		}
		$settings = App::settings()->get('account')->get('persistent_login');
		$tbl  = ac_table('remember_me');
		$sth  = App::connection()->prepare("
		SELECT COUNT(1)
		FROM `$tbl`
		WHERE _user_id = :id AND _key = :key
		AND _date > DATE_SUB(NOW(), INTERVAL :interval DAY)
		");
		$hkey = hash('sha512', $key);
		$sth->bindValue(':id', $id, \PDO::PARAM_INT);
		$sth->bindValue(':key', $hkey, \PDO::PARAM_STR);
		$sth->bindValue(':interval', $settings->get('expire', 5), \PDO::PARAM_INT);
		$sth->execute();
		if(!(int)$sth->fetchColumn(0) || !($user = Account::get($id))) {
			return false;
		}
		$key = bin2hex(secure_random_bytes((int)$settings->get('key_length', 128)));
		$sth = App::connection()->prepare("
		UPDATE `$tbl`
		SET _key = ?
		WHERE _user_id = ? AND _key = ?
		LIMIT 1
		");
		$sth->bindValue(1, hash('sha512', $key), \PDO::PARAM_LOB);
		$sth->bindValue(2, $id, \PDO::PARAM_INT);
		$sth->bindValue(3, $hkey, \PDO::PARAM_INT);
		$sth->execute();
		$login_key = base64_encode("$id::$key");

		return $user;
	}

	/**
	 * Create a persistent login key.
	 *
	 * @param int $user_id
	 * @return bool|string
	 */
	public static function create($user_id)
	{
		$settings = App::settings()->get('account')->get('persistent_login');
		$tbl      = ac_table('remember_me');
		$key      = bin2hex(secure_random_bytes((int)$settings->get('key_length', 128)));
		$sth      = App::connection()->prepare("
		INSERT INTO `$tbl` (_user_id, _key)
		VALUES (?, ?)
		");
		$sth->bindValue(1, $user_id, \PDO::PARAM_INT);
		$sth->bindValue(2, hash('sha512', $key), \PDO::PARAM_INT);
		$sth->execute();
		if(!$sth->rowCount()) {
			return false;
		}
		$feedback = array( $user_id, $key );
		Event::fire('persistent-login.create', $feedback);

		return base64_encode("$user_id::$key");
	}

	/**
	 * Delete a persistent login key.
	 *
	 * @param string $login_key
	 * @return bool
	 */
	public static function delete($login_key)
	{
		if(!self::getKey($login_key, $id, $key)) {
			return false;
		}
		$tbl     = ac_table('remember_me');
		$sth     = App::connection()->prepare("
		DELETE FROM `$tbl`
		WHERE _user_id = ?
		AND _key = ?
		");
		$sth->bindValue(1, $id, \PDO::PARAM_INT);
		$sth->bindValue(2, hash('sha512', $key), \PDO::PARAM_STR);
		$sth->execute();
		$count = $sth->rowCount();
		$sth->closeCursor();
		if(!$count) {
			return false;
		}
		$feedback = array( $id, $key );
		Event::fire('persistent-login.delete', $feedback);

		return true;
	}

	/**
	 * Deletes all persistent login keys from a user.
	 *
	 * @param int $user_id
	 * @return int
	 */
	public static function deleteAll($user_id)
	{
		$tbl = ac_table('remember_me');
		$sth = App::connection()->prepare("
		DELETE FROM `$tbl`
		WHERE _user_id = ?
		");
		$sth->bindValue(1, $user_id, \PDO::PARAM_INT);
		$sth->execute();
		if(!($count = $sth->rowCount())) {
			return 0;
		}
		$feedback = array( $user_id, $count );
		Event::fire('persistent-login.delete-all', $feedback);

		return $count;
	}

	/**
	 * @param string $login_key
	 * @param        $id
	 * @param        $key
	 * @return bool
	 */
	public static function getKey($login_key, &$id, &$key)
	{
		if(!$login_key || !($key = base64_decode($login_key, true))) {
			return false;
		}
		$key = explode('::', $key);
		if(count($key) !== 2 || !ctype_digit($key[0]) || !ctype_xdigit($key[1])) {
			return false;
		}
		$id  = (int)$key[0];
		$key = $key[1];

		return true;
	}
}
