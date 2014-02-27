<?php
namespace Aqua\BBCode\Filter;

use Aqua\BBCode\AbstractFilter;
use Aqua\BBCode\BBCode;
use Aqua\BBCode\Node;

/**
 * BBCode filter that replaces url strings with links
 *
 * @package Aqua\BBCode\Filter
 */
class ClickableFilter
extends AbstractFilter
{
	/**
	 * @var array
	 */
	public $settings = array(
		'protocols' => array(
			'http', 'https', 'shttp', 'ftp', 'ftps', 'sftp',
			'chrome', 'git', 'irc', 'irc6', 'ircs', 'ssh',
			'svn', 'svn+ssh', 'teamspeak', 'ventrilo'
		),
	);
	/**
	 * @var string
	 */
	public $pattern = null;

	/**
	 * @param array $settings
	 */
	public function __construct(array $settings = array())
	{
		parent::__construct($settings);
		$this->rebuildPattern();
	}

	public function rebuildPattern()
	{
		if(empty($this->settings['protocols'])) {
			$protocols = array( 'http' );
		} else {
			$protocols = $this->settings['protocols'];
		}
		foreach($protocols as &$p) {
			$p = preg_quote($p, '/');
		}

		// Protocol
		$pattern = '(?:(?P<scheme>' . implode('|', $protocols) . '):\/\/)?';
		// Username & password
		$pattern.= '(?:(?P<username>(?:&amp;|[a-z0-9\$\(\)\.\:\;\*\+\-\,\=\!]|%[a-z0-9]{2})+)(?P<password>\:(?:&amp;|[a-z0-9\$\(\)\.\:\;\*\+\-\,\=\!]|%[a-z0-9]{2})+)?@)?';
		// Domain
		$pattern.= '(?:(?P<domain>(?:www\.)?(?:[a-z0-9]+\.)?[a-z0-9](?:&amp;|[a-z0-9\$\(\)\.\:\;\*\+\-\,\=\!]|%[a-z0-9]{2}){0,253}[a-z0-9](?:\.[a-z0-9-]{2,5}){1,2})|(?P<ipv4>\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b)|(?P<ipv6>(?>(?>([a-f0-9]{1,4})(?>:(?1)){7}|(?!(?:.*[a-f0-9](?>:|$)){8,})((?1)(?>:(?1)){0,6})?::(?2)?)|(?>(?>(?1)(?>:(?1)){5}:|(?!(?:.*[a-f0-9]:){6,})(?3)?::(?>((?1)(?>:(?1)){0,4}):)?)?(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])(?>\.(?4)){3}))))';
		// Port
		$pattern.= '(?:\:(?P<port>\d+))?';
		// Path
		$pattern.= '(?P<path>(?:\/(?:[a-z0-9\$\(\)\.\:\*\+\-\,\=\!\@]|%[a-z0-9]{2})+)*)\/?';
		// Query string
		$pattern.= '(?:\?(?P<query>(?:&amp;|[a-z0-9\/\$\(\)\.\,\:\;\*\+\-\=\!\@]|%[a-z0-9]{2})*))?';
		// Hash
		$pattern.= '(?:#(?P<hash>[a-z0-9-_\+\/]*))?';
		$this->pattern = "/$pattern/i";
	}

	public function afterParse(BBCode $bbcode, Node $node, &$content, &$parse)
	{
		if(!$parse) {
			return;
		}
		$_node = $node;
		do {
			if($node->name && preg_match('/(url|email)/i', $node->name)) {
				return;
			}
		} while($_node = $_node->parent);
		$content = preg_replace_callback($this->pattern, array( $this, 'replaceUrl' ), $content);
	}

	public function replaceUrl($match)
	{
		$match = array_map('htmlspecialchars_decode', $match);
		$url = array();
		if(!empty($match['domain'])) {
			$url['domain'] = urlencode($match['domain']);
		} else if(!empty($match['ipv4']) && filter_var($match['ipv4'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			$url['domain'] = $match['ipv4'];
		} else if(!empty($match['ipv6']) && filter_var($match['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$url['domain'] = $match['ipv6'];
		} else {
			return htmlspecialchars($match[0]);
		}
		if(!empty($match['username'])) {
			$url['username'] = urlencode($match['username']);
		}
		if(!empty($match['password'])) {
			$url['password'] = urlencode($match['password']);
		}
		if(!empty($match['port'])) {
			$url['port'] = intval($match['port']);
		} else {
			$url['port'] = null;
		}
		if(!empty($match['path'])) {
			$path = explode('/', $match['path']);
			foreach($path as &$p) {
				$p = urlencode($p);
			}
			$url['base_dir'] = implode('/', $path);
		} else {
			$url['base_dir'] = null;
		}
		if(!empty($match['query'])) {
			parse_str($match['query'], $url['query']);
		}
		if(!empty($match['hash'])) {
			$url['hash'] = $match['hash'];
		}
		if(!empty($match['scheme'])) {
			$url['protocol'] = $match['scheme'] . '://';
		} else {
			$url['protocol'] = 'http://';
		}
		return '<a href="' . ac_build_url($url) . '">' . htmlspecialchars($match[0]) . '</a>';
	}
}
