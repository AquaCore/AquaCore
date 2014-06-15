<?php
namespace Aqua\User;

use Aqua\Core\App;
use Aqua\Event\Event;

class PersistentLogin
{
	/**
	 * Validate a persistent login key and generates a new one.
	 *
	 * @param string $loginKey
	 * @return \Aqua\User\Account|bool
	 */
	public static function authenticate(&$loginKey)
	{
		if(!self::getKey($loginKey, $id, $key)) {
			return false;
		}
		$settings = App::settings()->get('account')->get('persistent_login');
		$sth  = App::connection()->prepare(sprintf('
		SELECT COUNT(1)
		FROM %s
		WHERE _user_id = :id AND _key = :key
		AND _date > DATE_SUB(NOW(), INTERVAL :interval DAY)
		', ac_table('remember_me')));
		$hkey = hash('sha512', $key);
		$sth->bindValue(':id', $id, \PDO::PARAM_INT);
		$sth->bindValue(':key', $hkey, \PDO::PARAM_STR);
		$sth->bindValue(':interval', $settings->get('expire', 5), \PDO::PARAM_INT);
		$sth->execute();
		if(!(int)$sth->fetchColumn(0) || !($user = Account::get($id))) {
			return false;
		}
		$key = bin2hex(secure_random_bytes((int)$settings->get('key_length', 128)));
		$sth = App::connection()->prepare(sprintf('
		UPDATE %s
		SET _key = ?
		WHERE _user_id = ? AND _key = ?
		LIMIT 1
		', ac_table('remember_me')));
		$sth->bindValue(1, hash('sha512', $key), \PDO::PARAM_LOB);
		$sth->bindValue(2, $id, \PDO::PARAM_INT);
		$sth->bindValue(3, $hkey, \PDO::PARAM_INT);
		$sth->execute();
		$loginKey = base64_encode("$id::$key");

		return $user;
	}

	/**
	 * Create a persistent login key.
	 *
	 * @param int $userId
	 * @return bool|string
	 */
	public static function create($userId)
	{
		$settings = App::settings()->get('account')->get('persistent_login');
		$key      = bin2hex(secure_random_bytes((int)$settings->get('key_length', 128)));
		$sth      = App::connection()->prepare(sprintf('
		INSERT INTO %s (_user_id, _key)
		VALUES (?, ?)
		', ac_table('remember_me')));
		$sth->bindValue(1, $userId, \PDO::PARAM_INT);
		$sth->bindValue(2, hash('sha512', $key), \PDO::PARAM_INT);
		$sth->execute();
		if(!$sth->rowCount()) {
			return false;
		}
		$feedback = array( $userId, $key );
		Event::fire('persistent-login.create', $feedback);

		return base64_encode("$userId::$key");
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
		$sth     = App::connection()->prepare(sprintf('
		DELETE FROM %s
		WHERE _user_id = ?
		AND _key = ?
		', ac_table('remember_me')));
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
	 * @param int $userId
	 * @return int
	 */
	public static function deleteAll($userId)
	{
		$sth = App::connection()->prepare(sprintf('
		DELETE FROM %s
		WHERE _user_id = ?
		', ac_table('remember_me')));
		$sth->bindValue(1, $userId, \PDO::PARAM_INT);
		$sth->execute();
		$count = $sth->rowCount();
		$sth->closeCursor();
		if(!$count) {
			return 0;
		}
		$feedback = array( $userId, $count );
		Event::fire('persistent-login.delete-all', $feedback);

		return $count;
	}

	/**
	 * @param string $loginKey
	 * @param        $id
	 * @param        $key
	 * @return bool
	 */
	public static function getKey($loginKey, &$id, &$key)
	{
		if(!$loginKey || !($key = base64_decode($loginKey, true))) {
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
