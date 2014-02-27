<?php
namespace Aqua\Plugin;

use Aqua\Core\App;
use Aqua\Core\Settings;
use Aqua\Http\Request;
use Aqua\UI\FormXML;

class PluginSettings
extends Settings
implements \Serializable
{
	/**
	 * @var null
	 */
	public $data = null;
	/**
	 * @var \Aqua\Plugin\Plugin
	 */
	public $plugin;
	/**
	 * @var bool
	 */
	public $updated = false;

	/**
	 * @param \Aqua\Plugin\Plugin $plugin
	 */
	public function __construct(Plugin $plugin)
	{
		$this->plugin = $plugin;
	}

	public function serialize()
	{
		return serialize(array(
			                 $this->data,
			                 $this->size,
			                 $this->plugin->id
		                 ));
	}

	public function unserialize($serialized)
	{
		list($this->data,
			$this->size,
			$plugin) = unserialize($serialized);
		$this->plugin = Plugin::get($plugin);
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function exists($key)
	{
		$this->data !== null or $this->fetch();

		return parent::exists($key);
	}

	/**
	 * @param \Aqua\Core\Settings|array $settings
	 * @param bool                      $override
	 * @param                           $updated
	 * @return static
	 */
	public function merge($settings, $override = true, &$updated = null)
	{
		$tbl = ac_table('plugin_settings');
		$sth = App::connection()->prepare("
		UPDATE `$tbl`
		SET _value = ?
		WHERE _plugin_id = ?
		AND _key = ?
		LIMIT 1
		");
		if($settings instanceof Settings) {
			$settings = $settings->toArray();
		}
		foreach($settings as $key => $value) {
			if(!$override && $this->exists($key)) {
				continue;
			}
			$sth->bindValue(1, serialize($value), \PDO::PARAM_STR);
			$sth->bindValue(2, $this->plugin->id, \PDO::PARAM_INT);
			$sth->bindValue(3, $key, \PDO::PARAM_STR);
			$sth->execute();
			if($sth->rowCount()) {
				++$updated;
				$this->updated = true;
				if($this->data !== null) {
					$this->data[$key] = $value;
				}
			}
		}

		return $this;
	}

	/**
	 * @param array|string $key
	 * @return static
	 */
	public function delete($key)
	{
		if(!is_array($key)) {
			$key = array( $key );
		}
		$tbl = ac_table('plugin_settings');
		$sth = App::connection()->prepare("
		DELETE FROM `$tbl`
		WHERE _plugin_id = ?
		AND _key = ?
		");
		foreach($key as $k) {
			$sth->bindValue(1, $this->plugin->id, \PDO::PARAM_INT);
			$sth->bindValue(2, $k, \PDO::PARAM_STR);
			$sth->execute();
			if($sth->rowCount()) {
				$this->updated = true;
				unset($this->data[$k]);
			}
		}

		return $this;
	}

	/**
	 * @param string  $key
	 * @param mixed   $default
	 * @return \Aqua\Core\Settings|mixed
	 */
	public function get($key, $default = null)
	{
		$this->data !== null or $this->fetch();

		return parent::get($key, $default);
	}

	/**
	 * @return static
	 */
	public function fetch()
	{
		$tbl = ac_table('plugin_settings');
		$sth = App::connection()->prepare("
		SELECT _key, _value
		FROM `$tbl`
		WHERE _plugin_id = ?
		");
		$sth->bindValue(1, $this->plugin->id, \PDO::PARAM_INT);
		$sth->execute();
		$this->data = array();
		$this->size = 0;
		while($data = $sth->fetch(\PDO::FETCH_NUM)) {
			$this->data[$data[0]] = unserialize($data[1]);
			++$this->size;
		}

		return $this;
	}

	/**
	 * @param array $keys
	 * @return static
	 */
	public function reset(array $keys = null)
	{
		$tbl = ac_table('plugin_settings');
		if($keys) {
			$sth     = App::connection()->prepare("
			UPDATE `$tbl`
			SET _value = _default
			WHERE _plugin_id = ?
			AND _key = ?
			");
			$updated = 0;
			foreach($keys as $key) {
				$sth->bindValue(1, $this->plugin->id, \PDO::PARAM_INT);
				$sth->bindValue(2, $key, \PDO::PARAM_STR);
				$sth->execute();
				if($sth->rowCount()) ++$updated;
			}
			if(!$updated) {
				return $this;
			}
		}
		else {
			$sth = App::connection()->prepare("
			UPDATE `$tbl`
			SET _value = _default
			WHERE _plugin_id = ?
			");
			$sth->bindValue(1, $this->plugin->id, \PDO::PARAM_INT);
			$sth->execute();
		}
		$this->updated = true;
		if($this->data !== null) {
			$this->fetch();
		}

		return $this;
	}

	/**
	 * Add the plugin's default settings to the database
	 *
	 * @return bool
	 */
	public function exportToDatabase()
	{
		if(!($xml = $this->plugin->xml('settings'))) {
			return false;
		}
		$tbl = ac_table('plugin_settings');
		$sth = App::connection()->prepare("
		REPLACE INTO `$tbl` (_plugin_id, _key, _default, _value)
		VALUES (:plugin, :key, :default, :default)
		");
		foreach($xml->settings as $settings) {
			$key = (string)$settings->key;
			if($key === '') {
				continue;
			}
			$sth->bindValue(':plugin', $this->plugin->id, \PDO::PARAM_INT);
			$sth->bindValue(':key', $key, \PDO::PARAM_STR);
			$x = (array)$settings;
			if(isset($x['default'])) {
				$default = $x['default'];
			}
			else {
				$default = '';
			}
			$sth->bindValue(':default', serialize($default), \PDO::PARAM_LOB);
			$sth->execute();
			$this->data[$key] = $default;
		}

		return true;
	}

	/**
	 * @param Settings $settings
	 * @param          $error
	 * @param          $message
	 * @return bool
	 */
	public function update(Settings $settings, &$error = array(), &$message = null)
	{
		$vars = array(
			'settings' => $settings,
			'error'    => &$error,
			'message'  => &$message
		);
		$this->plugin->run('settings', $vars);
		$this->merge($settings, true, $updated);

		return (bool)$updated;
	}

	/**
	 * @param \Aqua\Http\Request $request
	 * @return \Aqua\UI\FormXML|null
	 */
	public function buildForm(Request $request)
	{
		if(!($xml = $this->plugin->xml('settings'))) {
			return null;
		}
		$frm = new FormXML($request, $xml, $this);

		return ($frm->fields ? $frm : null);
	}
}
