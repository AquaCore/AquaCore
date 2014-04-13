<?php
/**
 * Update settings/chargen/hats.php based on the game lua files
 * Usage: php cli/lua.php <directory>
 *
 * @example php cli/lua.php C:/Path/To/Lua/Files
 */

define('Aqua\ROOT',        str_replace('\\', '/', rtrim(dirname(__DIR__), DIRECTORY_SEPARATOR)));
define('Aqua\SCRIPT_NAME', basename(__FILE__));
define('Aqua\ENVIRONMENT', 'CLI');
define('Aqua\PROFILE',     'CHARGEN');

if(!isset($argv[0]) || !($dir = $argv[0])) {
	$dir = getcwd() ?: __DIR__;
}

if(!is_dir($dir)) {
	die("$dir is not a directory.\r\n");
}
if(!is_readable($dir)) {
	die("$dir is nto readable.\r\n");
}
$luaFiles = array(
	'accessoryid' => "$dir/datainfo/accessoryid.lua",
	'accname' => "$dir/datainfo/accname.lua",
);

foreach($luaFiles as $lua => &$file) {
	if(array_key_exists($lua, $argl)) {
		$file = $argl[$lua];
	}
	if(!file_exists($file)) {
		echo "$lua.lua not found.\r\n";
	} else if(!is_readable($file)) {
		echo "$lua.lua is not readable.\r\n";
	} else {
		echo "$lua.lua found at $file.\r\n";
		continue;
	}
	$file = null;
}

if($luaFiles['accname'] && $luaFiles['accessoryid']) {
	echo "Parsing accname.lua and accessoryid.lua...\r\n";
	$accessoryId = file_get_contents($luaFiles['accessoryid']);
	$accName     = file_get_contents($luaFiles['accname']);
	$pattern     = '/ACCESSORY_([^\s=]+)\s*=\s*(\d+)/Sim';
	$offset      = 0;
	$viewIds     = array();
	while(preg_match($pattern, $accessoryId, $match, PREG_OFFSET_CAPTURE, $offset)) {
		$offset = $match[2][1];
		$view = intval($match[2][0]);
		$name = preg_quote($match[1][0], '/');
		if(preg_match('/\[ACCESSORY_IDs\.ACCESSORY_' . $name . '\]\s*=\s*"([^"]+)"/im', $accName, $match)) {
			$viewIds[$view] = '';
			$len = strlen($match[1]);
			for($i = 1; $i < $len; ++$i) {
				$viewIds[$view].= '\\x' . dechex(ord($match[1][$i]));
			}
		}
	}
	$out = "<?php\r\n return array(\r\n";
	foreach($viewIds as $id => $str) {
		$out.= "\t$id => \"$str\",\r\n";
	}
	$out = substr($out, 0, -3);
	$out.= "\r\n);\r\n";
	file_put_contents(\Aqua\ROOT . '/settings/chargen/hats.php', $out);
	echo count($viewIds) . " view ids found.\r\n";
}
