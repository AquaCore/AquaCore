<?php
/**
 * Replace translations in the database with up to date ones found in /install/language xml files
 * Usage: php cli/update-lang.php [namespace=<namespace>,<namespace>,...] [code=<language code>]
 *
 * @example php cli/update-lang.php namespace=account,ragnarok code=en Replaces the english translations of the ragnarok and account namespaces
 *          php cli/update-lang.php                                    Replaces all translations
 */

define('Aqua\ROOT',        str_replace('\\', '/', rtrim(dirname(__DIR__), DIRECTORY_SEPARATOR)));
define('Aqua\SCRIPT_NAME', basename(__FILE__));
define('Aqua\ENVIRONMENT', 'CLI');
define('Aqua\PROFILE',     'MAIN');

include __DIR__ . '/../lib/bootstrap.php';

if(!array_key_exists('code', $argl)) {
	$code = 'en';
} else {
	$code = $argl['code'];
}

$path = \Aqua\ROOT . "/install/language/$code/namespaces";
if(!isset($argl['namespace'])) {
	$namespaces = glob("$path/*.xml");
} else {
	$namespaces = array();
	$x = explode(',', $argl['namespace']);
	foreach($x as $name) {
		if(file_exists("$path/$name.xml")) {
			$namespaces[] = "$path/$name.xml";
		}
	}
}

if(empty($namespaces)) {
	echo "Nothing to import.\r\n";
	die;
}

$tbl = ac_table('language_words');
$lang = \Aqua\Core\L10n::get($code);
if(!$lang) {
	echo "Language \"$code\" does not exist, cannot insert translations.\r\n";
	die;
}
foreach($namespaces as $file) {
	$name = basename($file, '.xml');
	echo "Deleting old translations for \"$name\".\r\n";
	$sth = \Aqua\Core\App::connection()->prepare("
	DELETE FROM `$tbl`
	WHERE _language_id = ? AND _namespace = ?
	");
	$sth->bindValue(1, $lang->id, PDO::PARAM_INT);
	$sth->bindValue(2, $name, PDO::PARAM_STR);
	$sth->execute();
	echo "Importing namespace \"$name\"...\r\n";
	$xml = new SimpleXMLElement(file_get_contents($file));
	\Aqua\Core\L10n::import($xml);
	echo "Finished importing \"$name\".\r\n";
}

echo "All namespaces imported!\r\n";
