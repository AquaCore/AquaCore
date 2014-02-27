<?php
namespace Aqua\UI;

use Aqua\Http\Uri;
use Aqua\UI\Exception\PaginationException;

class Pagination
{
	/**
	 * @var int
	 */
	public $range = 7;
	/**
	 * @var int
	 */
	public $count;
	/**
	 * @var int
	 */
	public $currentPage = 1;
	/**
	 * @var int
	 */
	public $style = 4;
	/**
	 * @var string
	 */
	public $url;

	/**
	 * Scrolling style, always displays all pages.
	 */
	const STYLE_ALL = 1;
	/**
	 * Scrolling style, the range of the links is fixed.
	 * e.g.: 1-7, 8-14, 16-21, ...
	 */
	const STYLE_JUMPING = 2;
	/**
	 * Scrolling style, the range expands, displaying all links
	 * from 1 to the current page.
	 * e.g.: 1-7, 1-8, 1-9, ...
	 */
	const STYLE_ELASTIC = 3;
	/**
	 * Scrolling style, the active link is always in the middle,
	 * except in the first and last ranges.
	 * e.g.: 1-7, 2-8, 3-9, ...
	 */
	const STYLE_SLIDING = 4;

	/**
	 * @param \Aqua\Http\Uri $uri
	 * @param int            $count        Maximum number of pages.
	 * @param int            $current_page
	 * @param string         $parameter
	 */
	public function __construct(Uri $uri, $count, $current_page = null, $parameter = 'page')
	{
		$parameter   = urlencode($parameter);
		$this->count = $count;
		if($current_page === null) {
			$current_page = $uri->getInt($parameter, 1, 1, $count);
		}
		$this->currentPage = max(1, $current_page);
		$query             = $uri->parameters;
		unset($query[$parameter]);
		$this->url = ac_build_url(
			array(
				'query'     => $query,
				'path'      => $uri->path,
				'action'    => $uri->action,
				'arguments' => $uri->arguments,
				'base_dir'  => \Aqua\WORKING_DIR
			)
		);
		if(empty($query) && \Aqua\REWRITE) {
			$this->url .= "?$parameter=";
		} else {
			$this->url .= "&$parameter=";
		}
	}

	/**
	 * @param int $min
	 * @param int $max
	 * @return \Aqua\UI\Pagination
	 */
	public function capRange($min, $max)
	{
		$this->range = min($max, max($min, $this->count));

		return $this;
	}

	/**
	 * @param int $page
	 * @return string
	 */
	public function url($page)
	{
		return $this->url . $page;
	}

	/**
	 * @param int                 $page
	 * @param string              $url
	 * @param \Aqua\UI\Pagination $paginator
	 * @return string
	 */
	public function _renderLink($page, $url, $paginator)
	{
		$link = '<a class="ac-pagination-page-' . $page . ' ac-pagination-link ac-pagination-normal';
		if($page > $paginator->count || $page < 1) {
			$link .= ' ac-pagination-disabled';
		} else {
			if($this->currentPage == $page) {
				$link .= ' ac-pagination-disabled ac-pagination-active';
			} else {
				$link .= "\" href=\"$url\"";
			}
		}
		$link .= "\">$page</a>";

		return $link;
	}

	/**
	 * @param \Aqua\UI\Pagination $paginator
	 * @return string
	 */
	public function _renderOpen($paginator)
	{
		if($paginator->currentPage <= 1) {
			return <<<HTML
<a class="ac-pagination-link ac-pagination-first ac-pagination-disabled ac-pagination-active">&#171;</a>
<a class="ac-pagination-link ac-pagination-previous ac-pagination-disabled ac-pagination-active">&lsaquo;</a>
HTML;
		} else {
			$first_url = $paginator->url(1);
			$prev_url  = $paginator->url($paginator->currentPage - 1);

			return <<<HTML
<a class="ac-pagination-link ac-pagination-first" href="$first_url">&#171;</a>
<a class="ac-pagination-link ac-pagination-previous" href="$prev_url">&lsaquo;</a>
HTML;
		}
	}

	/**
	 * @param \Aqua\UI\Pagination $paginator
	 * @return string
	 */
	public function _renderClose($paginator)
	{
		if($paginator->currentPage >= $paginator->count) {
			return <<<HTML
<a class="ac-pagination-link ac-pagination-last ac-pagination-disabled ac-pagination-active">&rsaquo;</a>
<a class="ac-pagination-link ac-pagination-next ac-pagination-disabled ac-pagination-active">&#187;</a>
HTML;
		} else {
			$last_url = $paginator->url($paginator->count);
			$next_url = $paginator->url($paginator->currentPage + 1);

			return <<<HTML
<a class="ac-pagination-link ac-pagination-last" href="$next_url">&rsaquo;</a>
<a class="ac-pagination-link ac-pagination-next" href="$last_url">&#187;</a>
HTML;
		}
	}

	/**
	 * @param array $functions
	 * @return string
	 * @throws \Aqua\UI\Exception\PaginationException
	 */
	public function render(array $functions = array())
	{
		$functions += array(
			'open'  => array( $this, '_renderOpen' ),
			'close' => array( $this, '_renderClose' ),
			'link'  => array( $this, '_renderLink' )
		);
		if(!is_callable($functions['link'], true)) {
			throw new PaginationException(
				__('exception', 'pagination-not-callable'),
				PaginationException::RENDERING_FUNCTION_NOT_CALLABLE
			);
		}
		$this->range($page, $count);
		if(is_callable($functions['open'], true)) {
			$html = call_user_func($functions['open'], $this);
		} else {
			$html = '';
		}
		for(; $page < $count; ++$page) {
			$html .= call_user_func($functions['link'], $page, $this->url($page), $this);
		}
		if(is_callable($functions['close'], true)) {
			$html .= call_user_func($functions['close'], $this);
		}

		return $html;
	}

	/**
	 * @param int $page
	 * @param int $count
	 * @throws \Aqua\UI\Exception\PaginationException
	 */
	public function range(&$page, &$count)
	{
		$page  = 1;
		$count = $this->range;
		switch($this->style) {
			case self::STYLE_ALL:
				$count = $this->count;
				break;
			case self::STYLE_JUMPING:
				$page = ceil($this->currentPage / ($this->range - 1)) - 1;
				$page = $page * $this->range - $page + 1;
				break;
			case self::STYLE_ELASTIC:
				$count = max($this->range, min($this->currentPage + 1, $this->count));
				break;
			case self::STYLE_SLIDING:
				$page = ceil($this->range / 2) + 1;
				if($this->currentPage < $page) {
					$page = 1;
				} else if(($this->currentPage - 1) > ($this->count - $page)) {
					$page = max(1, $this->count - $this->range + 1);
				} else {
					$page = $this->currentPage - $page + 2;
				}
				break;
			default:
				throw new PaginationException(
					__('exception', 'pagination-sliding-style', $this->style),
					PaginationException::INVALID_SLIDING_STYLE
				);
		}
		$count += $page;
	}
}
