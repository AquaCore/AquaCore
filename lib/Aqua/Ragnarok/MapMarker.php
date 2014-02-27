<?php
namespace Aqua\Ragnarok;

use Aqua\Core\App;
use Aqua\UI\Tag;

class MapMarker
extends Tag
{
	protected $map;

	public static $mapCache = null;
	public static $miniMapSize = array();

	const MAPCACHE    = '/assets/client/map_cache.dat';
	const MINIMAP_DIR = '/assets/images/map/';

	public function __construct($map)
	{
		parent::__construct('div');
		$this->map = strtolower($map);
		$this->attr('class', 'ac-map');
		$img = new Tag('img');
		$img->closeTag = false;
		$img->attr('src', \Aqua\URL . self::MINIMAP_DIR . $this->map . '.png');
		$this->append($img);
	}

	public function mark($x, $y, $x2 = null, $y2 = null)
	{
		$marker = new Tag('div');
		$this->prepend($marker);
		if(!$this->miniMapSize($minimap_x, $minimap_y) || !$this->mapSize($map_x, $map_y)) {
			return $marker;
		}
		$x_rate = $minimap_x / $map_x;
		$y_rate = $minimap_y / $map_y;
		$x_pos = (($x * $x_rate) / ($minimap_x / 100));
		$y_pos = (($y * $y_rate) / ($minimap_y / 100));
		$style = "left: $x_pos%; bottom: $y_pos%;";
		if($x2 !== null && $y2 !== null) {
			$x2_pos = (($x2 * $x_rate) / ($minimap_x / 100));
			$y2_pos = (($y2 * $y_rate) / ($minimap_y / 100));
			if($x2_pos < $x_pos) {
				list($x2_pos, $x_pos) = array($x_pos, $x2_pos);
			}
			if($y2_pos < $y_pos) {
				list($y2_pos, $y_pos) = array($y_pos, $y2_pos);
			}
			$x2_pos -= $x_pos;
			$y2_pos -= $y_pos;
			$style .= " width: $x2_pos%; height: $y2_pos%;";
			$marker->attr('class', 'ac-map-range');
		} else {
			$marker->attr('class', 'ac-map-marker');
		}
		$marker->attr('style', $style);
		return $marker;
	}

	public function mapSize(&$x, &$y)
	{
		if(self::$mapCache === null && !(self::$mapCache = App::cache()->fetch('ragnarok_map_cache', null))) {
			self::rebuildCache();
		}
		if(isset(self::$mapCache[$this->map])) {
			list($x, $y) = self::$mapCache[$this->map];
			return true;
		} else {
			return false;
		}
	}

	public function miniMapSize(&$x, &$y)
	{
		if(isset(self::$miniMapSize[$this->map])) {
			if(self::$miniMapSize[$this->map] === null) {
				return false;
			} else {
				list($x, $y) = self::$miniMapSize[$this->map];
				return true;
			}
		}
		$file = \Aqua\ROOT . self::MINIMAP_DIR . $this->map . '.png';
		$x = $y = 0;
		if(!file_exists($file)) {
			self::$miniMapSize[$this->map] = null;
			return false;
		}
		self::$miniMapSize[$this->map] = getimagesize($file);
		list($x, $y) = self::$miniMapSize[$this->map];
		return true;
	}

	public static function rebuildCache()
	{
		$mapCache = array();
		$fp = fopen(\Aqua\ROOT . self::MAPCACHE, 'r');
		$size  = current(unpack('L', fread($fp, 4)));
		$count = current(unpack('S', fread($fp, 4)));
		$l = 8;
		for($i = 0; $i < $count; ++$i) {
			fseek($fp, $l);
			$mapname = trim(fread($fp, 12), chr(0));
			$mapCache[$mapname][0] = current(unpack('s', fread($fp, 2)));
			$mapCache[$mapname][1] = current(unpack('s', fread($fp, 2)));
			$l += 20 + current(unpack('l', fread($fp, 4)));
		}
		self::$mapCache = $mapCache;
		App::cache()->store('ragnarok_map_cache', $mapCache);
	}

	public static function hasMiniMap($map)
	{
		return file_exists(\Aqua\ROOT . self::MINIMAP_DIR . strtolower($map) . '.png');
	}
}
