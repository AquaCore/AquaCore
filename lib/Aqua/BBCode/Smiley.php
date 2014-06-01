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
		UPDATE `%s`
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
		DELETE FROM `%s`
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

	public static function upload($key, $multiple = false)
	{
		$smileys = array();
		if($multiple) {
			$count = count($_FILES[$key]['name']);
			for($i = 0; $i < $count; ++$i) {
				if($_FILES[$key]['error'][$i]) {
					continue;
				}
				switch(self::_getUploadedFileType($_FILES[$key]['type'][$i],
				                                  $_FILES[$key]['name'][$i])) {
					case 1:
						$smileys = array_merge($smileys, self::_uploadImage($_FILES[$key]['tmp_name'][$i],
						                                                    $_FILES[$key]['name'][$i]));
						break;
					case 2:
						$smileys = array_merge($smileys, self::_uploadZip($_FILES[$key]['tmp_name'][$i],
						                                                  $_FILES[$key]['name'][$i]));
						break;
				}
			}
		} else {
			switch(self::_getUploadedFileType($_FILES[$key]['type'],
			                                  $_FILES[$key]['name'])) {
				case 1:
					$smileys = self::_uploadImage($_FILES[$key]['tmp_name'],
					                              $_FILES[$key]['name']);
					break;
				case 2:
					$smileys = self::_uploadZip($_FILES[$key]['tmp_name'],
					                            $_FILES[$key]['name']);
					break;
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
		INSERT INTO `%s` (_file, _text, _order)
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

	protected static function _getUploadedFileType($type, $name)
	{
		switch($type) {
			case 'application/zip':
				return (preg_match('/\.zipx?$/i', $name) ? 2 :false);
			case 'application/x-tar':
				return (preg_match('/\.tar$/i', $name) ? 2 :false);
			case 'application/x-gtar':
				return (preg_match('/\.t(ar\.)?(gz|bz2)$/i', $name) ? 2 :false);
			case 'image/png':
				return (preg_match('/\.png$/i', $name) ? 1 :false);
			case 'image/jpeg':
				return (preg_match('/\.jpe?g/i', $name) ? 1 :false);
			case 'image/gif':
				return (preg_match('/\.gif/i', $name) ? 1 :false);
			default:
				return false;
		}
	}

	protected static function _uploadImage($location, $name)
	{
		$extension = strtolower(ltrim(strrchr($name, '.'), '.'));
		$id        = uniqid() . ".$extension";
		$file      = \Aqua\ROOT . self::DIRECTORY . $id;
		$smileys   = array();
		$old       = umask(0);
		if(move_uploaded_file($location, $file)) {
			$smileys[$id] = basename($name, ".$extension");
		}
		umask($old);
		return $smileys;
	}

	protected static function _uploadZip($location, $name)
	{
		preg_match('/\.(zipx?|tar|t(?:ar\.)?(?:gz|bz2))$/i', $name, $match);
		$tmp = \Aqua\ROOT . '/tmp/' . uniqid() . $match[0];
		if(!move_uploaded_file($location, $tmp)) {
			return array();
		}
		$old     = umask(0);
		$smileys = array();
		try {
			$phar    = new \PharData($tmp);
			foreach($phar as $fileInfo) {
				$extension = $fileInfo->getExtension();
				if($fileInfo->isFile() && in_array($extension, array( 'gif', 'jpeg', 'jpg', 'png' ))) {
					$id   = uniqid() . ".$extension";
					$file = \Aqua\ROOT . self::DIRECTORY . $id;
					if(copy($fileInfo->getPathname(), $file)) {
						$smileys[$id] = $fileInfo->getBasename(".$extension");
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
