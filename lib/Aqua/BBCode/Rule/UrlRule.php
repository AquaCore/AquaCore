<?php
namespace Aqua\BBCode\Rule;

use Aqua\BBCode\AbstractRule;
use Aqua\BBCode\Node;

class UrlRule
extends AbstractRule
{
	public function __construct(array $settings = array())
	{
		parent::__construct($settings);
		$self = $this;
		$this->addTag('url', array(
				'htmlTag' => 'a',
				'attributes' => array(
					'$' => array(
						'map' => 'href',
						'optional' => false,
						'encode' => array( $this, 'encodeUrl' )
					)
				)
			))->addTag('email', array(
				'htmlTag' => 'a',
				'attributes' => array(
					'$' => array(
						'map' => 'href',
						'encode' => false,
						'optional' => false,
						'format' => function($email) use($self) {
							return 'mailto:' . urlencode($email);
						}
					)
				)
			))->addTag('img', array(
				'htmlTag' => 'img',
				'attributes' => array(
					'$' => array(
						'map' => 'src',
						'optional' => false,
						'encode' => array( $this, 'encodeUrl' )
					),
					'alt' => array(
						'map' => array( 'alt', 'title' ),
						'optional' => true
					)
				)
			));
	}

	public function parse(Node $node)
	{
		$content = $node->content();
		if(!$node->value) {
			$node->attributes['$'] = $content;
		} else if($node->name === 'img') {
			$node->attributes['alt'] = $content;
			$node->clear();
		}
		return parent::parse($node);
	}

	public function encodeUrl($url)
	{
		if(!($parts = parse_url($url)) || isset($parts['scheme']) && $parts['scheme'] === 'javascript') {
			return urlencode($url);
		}
		$parts+= array(
			'scheme'      => null,
			'host'        => null,
			'url_rewrite' => false,
			'user'        => null,
			'pass'        => null,
			'base_dir'    => null,
			'fragment'    => null
		);
		if(isset($parts['query'])) {
			parse_str($parts['query'], $query);
			$parts['query'] = $query;
		}
		if($parts['scheme']) {
			$parts['protocol'] = $parts['scheme'] . '://';
		} else {
			$parts['protocol'] = null;
		}
		$parts['domain']   = urlencode($parts['host']);
		$parts['username'] = urlencode($parts['user']);
		$parts['password'] = urlencode($parts['pass']);
		$parts['base_dir'] = $parts['path'];
		unset($parts['scheme']);
		unset($parts['host']);
		unset($parts['user']);
		unset($parts['pass']);
		unset($parts['path']);
		if(isset($parts['base_dir'])) {
			$path = explode('/', $parts['base_dir']);
			$parts['base_dir'] = '';
			foreach($path as $_path) {
				$parts['base_dir'].= '/' . urlencode($_path);
			}
		}
		return htmlentities(ac_build_url($parts), ENT_QUOTES, 'UTF-8');
	}
}
