<?php
namespace Aqua\BBCode;

use Aqua\Core\App;
use Aqua\SQL\Query;
use Aqua\SQL\Search;

class Smiley
{
	public static $cache;

	const CACHE_KEY = 'smileys';
	const DIRECTORY = '/uploads/smiley/';

	public static function order(array $newOrder)
	{
		$newOrder = array_unique($newOrder);
		$oldOrder = Query::select(App::connection())
			->columns(array( 'id' => 'id', 'order' => '_order' ))
			->setColumnType(array( 'order' => 'integer' ))
			->from(ac_table('smileys'))
			->query()
			->getColumn('order', 'id');
		if(empty($oldOrder)) {
			return false;
		}
		$update = Query::update(App::connection());
		$i      = 0;
		foreach($newOrder as $id => $order) {
			if(!array_key_exists($id, $oldOrder)) {
				return false;
			}
			if($oldOrder[$id] === $order) {
				continue;
			}
			$update->tables(array( "t$i" => ac_table('smileys') ))
			       ->set(array( "t$i._order" => $order ))
			       ->where(array( "t$i.id" => $id ));
			if(($otherId = array_search($order, $oldOrder)) !== false &&
			   !array_key_exists($otherId, $newOrder)) {
				++$i;
				$update->tables(array( "t$i" => ac_table('smileys') ))
				       ->set(array( "t$i._order" => $oldOrder[$i] ))
				       ->where(array( "t$i.id" => $otherId ));
			}
			++$i;
		}
		if(empty($update->set)) {
			return false;
		}
		$update->query();
		if($update->rowCount) {
			self::rebuildCache();
		    return true;
		} else {
			return false;
		}
	}

	public static function edit($id, $text = null)
	{
		if(!is_array($id)) {
			$id = array( $id => $text);
		}
		$sth = App::connection()->prepare(sprintf('
		UPDATE %s
		SET _text = ?
		WHERE id = ?
		LIMIT 1
		', ac_table('smileys')));
		$edited = array();
		foreach($id as $smileyId => $smileyText) {
			$sth->bindValue(1, $smileyText, \PDO::PARAM_STR);
			$sth->bindValue(2, $smileyId, \PDO::PARAM_INT);
			$sth->execute();
			if($sth->rowCount()) {
				$edited[] = $id;
			}
		}
		if(count($edited)) {
			self::rebuildCache();
			return $edited;
		} else {
			return false;
		}
	}

	/**
	 * @param $ids
	 * @return array|bool
	 */
	public static function delete($ids)
	{
		if(empty($ids)) {
			return false;
		}
		if(!is_array($ids)) {
			$ids = array( $ids );
		}
		array_unshift($ids, Search::SEARCH_IN);
		$fileNames = Query::select(App::connection())
			->columns(array( 'id' => 'id', 'file' => '_file' ))
			->setColumnType(array( 'id' => 'integer' ))
			->from(ac_table('smileys'))
			->where(array( 'id' => $ids ))
			->query()
			->getColumn('file', 'id');
		array_shift($ids);
		$sth = App::connection()->prepare(sprintf('
		DELETE FROM %s
		WHERE id = ?
		', ac_table('smileys')));
		$deleted = array();
		foreach($ids as $id) {
			$sth->bindValue(1, $id, \PDO::PARAM_STR);
			$sth->execute();
			if($sth->rowCount()) {
				$deleted[]= $id;
				if(isset($fileNames[$id])) {
					$file = \Aqua\ROOT . self::DIRECTORY . $fileNames[$id];
					!file_exists($file) or @unlink($file);
				}
			}
			$sth->closeCursor();
		}
		if(count($deleted)) {
			self::rebuildCache();
			return $deleted;
		} else {
			return false;
		}
	}

	public static function upload($key)
	{
		$smileys = array();
		foreach(ac_files($key) as $file) {
			if($file['error']) continue;
			if(preg_match('/^image\//i', $file['type'])) {
				$smileys = array_merge($smileys, self::_uploadImage($file['tmp_name'], $file['name']));
			} else {
				$smileys = array_merge($smileys, self::_uploadZip($file['tmp_name'], $file['name']));
			}
		}
		if(empty($smileys)) {
			return array();
		}
		$order = Query::select(App::connection())
			->columns(array( 'order' => 'MAX(_order)' ))
			->setColumnType(array( 'order' => 'integer' ))
			->from(ac_table('smileys'))
			->query()
			->get('order', 0);
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO %s (_file, _text, _order)
		VALUES (:file, :text, :order)
		', ac_table('smileys')));
		foreach($smileys as $fileName => &$name) {
			$name = '/' . substr($name, 0, 32);
			$sth->bindValue(':file', $fileName, \PDO::PARAM_STR);
			$sth->bindValue(':text', $name, \PDO::PARAM_STR);
			$sth->bindValue(':order', ++$order, \PDO::PARAM_INT);
			$sth->execute();
			$sth->closeCursor();
		}
		self::rebuildCache();
		return $smileys;
	}

	protected static function _uploadImage($location, $name)
	{
		$extension = strtolower(ltrim(strrchr($name, '.'), '.'));
		$id        = uniqid() . ".$extension";
		$file      = \Aqua\ROOT . self::DIRECTORY . $id;
		$smileys   = array();
		$old       = umask(0);
		if(move_uploaded_file($location, $file)) {
			$smileys[$id] = substr(basename($name, ".$extension"), 0, 32);
		}
		umask($old);
		return $smileys;
	}

	protected static function _uploadZip($location, $name)
	{
		if(!preg_match('/\.(tar\.)?[^\.]+$/i', $name, $match)) {
			return array();
		}
		$tmp = \Aqua\ROOT . '/tmp/' . uniqid() . $match[0];
		if(!move_uploaded_file($location, $tmp)) {
			return array();
		}
		$old     = umask(0);
		$smileys = array();
		try {
			try { $phar = new \PharData($tmp); } catch(\Exception $e) {
				umask($old);
				unlink($tmp);
				return array();
			}
			foreach($phar as $fileInfo) {
				$extension = $fileInfo->getExtension();
				if($fileInfo->isFile() && in_array($extension, array( 'gif', 'jpeg', 'jpg', 'png' ))) {
					$id   = uniqid() . ".$extension";
					$file = \Aqua\ROOT . self::DIRECTORY . $id;
					if(copy($fileInfo->getPathname(), $file)) {
						$smileys[$id] = substr($fileInfo->getBasename(".$extension"), 0, 32);
						chmod($file, \Aqua\PUBLIC_FILE_PERMISSION);
					}
				}
			}
		} catch(\Exception $exception) {
			umask($old);
			unlink($tmp);
			throw $exception;
		}
		umask($old);
		unlink($tmp);
		return $smileys;
	}

	public static function smileys()
	{
		if(self::$cache === null && !(self::$cache = App::cache()->fetch(self::CACHE_KEY, false))) {
			self::rebuildCache();
		}
		return self::$cache;
	}

	public static function rebuildCache()
	{
		self::$cache = array();
		$smileys = Query::select(App::connection())
			->columns(array(
				'id'   => 'id',
				'file' => '_file',
				'text' => '_text'
			))
			->from(ac_table('smileys'))
			->order(array( '_order' => 'ASC' ))
			->query();
		foreach($smileys as $data) {
			self::$cache[$data['id']] = array(
				'file' => $data['file'],
			    'text' => $data['text']
			);
		}
		App::cache()->store(self::CACHE_KEY, self::$cache, 0);
	}
}
