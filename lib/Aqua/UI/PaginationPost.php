<?php
namespace Aqua\UI;

use Aqua\Http\Request;

class PaginationPost
extends Pagination
{
	/**
	 * @var array
	 */
	public $data = array();
	/**
	 * @var array
	 */
	public $requestData;
	/**
	 * @var string
	 */
	public $parameter = '';

	/**
	 * @param \Aqua\Http\Request $request
	 * @param int                $count
	 * @param null               $current_page
	 * @param string             $parameter
	 */
	public function __construct(Request $request, $count, $current_page = null, $parameter = 'page')
	{
		$this->count     = $count;
		$this->parameter = $parameter;
		if($current_page === null) {
			$current_page = $request->getInt($parameter, 1);
		}
		$this->requestData = $request->data;
		unset($this->requestData[$parameter]);
		$this->currentPage = max(1, $current_page);
	}

	/**
	 * @param int $page
	 * @return string
	 */
	public function input($page)
	{
		return "<input type=\"hidden\" name=\"{$this->parameter}\" value=\"$page\">";
	}

	/**
	 * @param int                     $page
	 * @param string                  $url
	 * @param \Aqua\UI\PaginationPost $paginator
	 * @return string
	 */
	public function _renderLink($page, $url, $paginator)
	{
		$html = '<form method="POST" class="ac-pagination-form">' . $paginator->data;
		$html .= '<button type="submit" class="ac-pagination-page-' . $page . ' ac-pagination-link ac-pagination-normal';
		if($page > $paginator->count || $page < 1) {
			$html .= ' ac-pagination-disabled';
		} else {
			if($this->currentPage == $page) {
				$html .= ' ac-pagination-disabled ac-pagination-active';
			} else {
				$html .= "\" name=\"{$paginator->parameter}\" value=\"$page\"";
			}
		}
		$html .= "\">$page</button></form>";

		return $html;
	}

	/**
	 * @param \Aqua\UI\PaginationPost $paginator
	 * @return string
	 */
	public function _renderOpen($paginator)
	{
		if($paginator->currentPage <= 1) {
			return <<<HTML
<form method="POST" class="ac-pagination-form">
	{$paginator->data}
	<button class="ac-pagination-link ac-pagination-first ac-pagination-disabled ac-pagination-active">&#171;</button>
</form>
<form method="POST" class="ac-pagination-form">
	{$paginator->data}
	<button class="ac-pagination-link ac-pagination-previous ac-pagination-disabled ac-pagination-active">&lsaquo;</button>
</form>
HTML;
		} else {
			$first_input = $paginator->input(1);
			$prev_input  = $paginator->input($paginator->currentPage - 1);

			return <<<HTML
<form method="POST" class="ac-pagination-form">
	{$paginator->data}
	{$first_input}
	<button class="ac-pagination-link ac-pagination-first" type="submit">&#171;</button>
</form>
<form method="POST" class="ac-pagination-form">
	{$paginator->data}
	{$prev_input}
	<button class="ac-pagination-link ac-pagination-previous" type="submit">&lsaquo;</button>
</form>
HTML;
		}
	}

	/**
	 * @param \Aqua\UI\PaginationPost $paginator
	 * @return string
	 */
	public function _renderClose($paginator)
	{
		if($paginator->currentPage >= $paginator->count) {
			return <<<HTML
<form method="POST" class="ac-pagination-form">
	{$paginator->data}
	<button class="ac-pagination-link ac-pagination-last ac-pagination-disabled ac-pagination-active">&rsaquo;</button>
</form>
<form method="POST" class="ac-pagination-form">
	{$paginator->data}
	<button class="ac-pagination-link ac-pagination-next ac-pagination-disabled ac-pagination-active">&#187;</button>
</form>
HTML;
		} else {
			$last_input = $paginator->input($paginator->count);
			$next_input = $paginator->input($paginator->currentPage + 1);

			return <<<HTML
<form method="POST" class="ac-pagination-form">
	{$paginator->data}
	{$last_input}
	<button class="ac-pagination-link ac-pagination-last" type="submit">&rsaquo;</button>
</form>
<form method="POST" class="ac-pagination-form">
	{$paginator->data}
	{$next_input}
	<button class="ac-pagination-link ac-pagination-next" type="submit">&#187;</button>
</form>
HTML;
		}
	}

	/**
	 * @param array $functions
	 * @param array $data
	 * @return string
	 */
	public function render(array $functions = array(), array $data = array())
	{
		$this->data = $this->_parseData($data);
		$html       = parent::render($functions);
		$this->data = array();

		return $html;
	}

	protected function _parseData(array $data)
	{
		$html = '';
		unset($data[$this->parameter]);
		$data += $this->requestData;
		foreach($data as $key => &$val) {
			if(is_scalar($val)) {
				$val = htmlspecialchars($val);
				$html .= "<input type=\"hidden\" name=\"$key\" value=\"$val\">";
			} else {
				foreach($val as $_key => &$_val) {
					$_val = htmlspecialchars($_val);
					$html .= "<input type=\"hidden\" name=\"{$key}[{$_key}]\" value=\"$_val\">";
				}
			}
		}

		return $html;
	}
}
