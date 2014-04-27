<?php
use Aqua\Core\App;
use Aqua\SQL\Query;

foreach(array(ac_table('content_meta'),
              ac_table('comment_meta'),
              ac_table('category_meta')) as $tbl) {
	$select = Query::select(App::connection())
		->columns(array(
			'id'    => '_id',
			'key'   => '_key',
			'value' => '_val'
		))
		->from($tbl)
		->query();
	if(!$select->count()) {
		continue;
	}
	$delete = array();
	$update = array();
	foreach($select as $data) {
		if(!($value = @unserialize($data['value']))) {
			$delete[] = array( $data['id'], $data['key'] );
			continue;
		}
		if(is_string($value)) {
			$type = 'S';
		} else if(is_float($value)) {
			$type = 'F';
		} else if(is_int($value)) {
			$type = 'I';
		} else if(is_bool($value)) {
			$type = 'B';
			$value = ($value ? '1' : '');
		} else {
			$type = 'X';
			$value = serialize($value);
		}
		$update[] = array( $value, $type, $data['id'], $data['key'] );
	}
	if(count($delete)) {
		$sth = App::connection()->prepare(sprintf('
		DELETE FROM `%s`
		WHERE _id = ?
		AND   _key = ?
		', $tbl));
		foreach($delete as $data) {
			$sth->bindValue(1, $data[0], PDO::PARAM_INT);
			$sth->bindValue(2, $data[1], PDO::PARAM_STR);
			$sth->execute();
		}
	}
	if(count($update)) {
		$sth = App::connection()->prepare(sprintf('
		UPDATE `%s`
		SET _val = ?,
		    _type = ?
		WHERE _id = ?
		AND   _key = ?
		', $tbl));
		foreach($delete as $data) {
			$sth->bindValue(1, $data[0], PDO::PARAM_STR);
			$sth->bindValue(2, $data[1], PDO::PARAM_STR);
			$sth->bindValue(3, $data[3], PDO::PARAM_INT);
			$sth->bindValue(4, $data[4], PDO::PARAM_STR);
			$sth->execute();
		}
	}
}