<?php
namespace Aqua\UI\Form;

use Aqua\Core\App;
use Aqua\UI\AbstractForm;
use Aqua\UI\Form;

class Captcha
implements FieldInterface
{
	/**
	 * @var string
	 */
	public $label;
	/**
	 * @var string
	 */
	public $description;
	/**
	 * @var string
	 */
	public $warning;
	/**
	 * @var int
	 */
	public $error = 0;
	/**
	 * @var string
	 */
	public $errorMessage = array();

	const VALIDATION_INCORRECT_ANSWER = 1;

	/**
	 * @param string $label
	 * @return \Aqua\UI\Form\Captcha
	 */
	public function setLabel($label)
	{
		$this->label = $label;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @param string $desc
	 * @return \Aqua\UI\Form\Captcha
	 */
	public function setDescription($desc)
	{
		$this->description = $desc;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @param string $warning
	 * @return \Aqua\UI\Form\Captcha
	 */
	public function setWarning($warning)
	{
		$this->warning = $warning;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getWarning()
	{
		return $this->warning;
	}

	/**
	 * @param int $error
	 * @return \Aqua\UI\Form\Captcha
	 */
	public function setError($error)
	{
		$this->error = $error;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @param string $message
	 * @param int    $type
	 * @return \Aqua\UI\Form\Captcha
	 */
	public function setDefaultErrorMessage($message, $type = self::VALIDATION_INCORRECT_ANSWER)
	{
		$this->errorMessage[$type] = $message;

		return $this;
	}

	/**
	 * @return string
	 */
	public function render()
	{
		$key     = App::captcha()->create(App::request()->ip);
		$url     = ac_build_url(array(
				'base_dir' => App::settings()->get('base_dir'),
				'script'   => 'captcha.php',
				'query'    => array( 'id' => $key )
			));
		$message = __('application', '');

		return "
<div class=\"ac-captcha\">
	<div class=\"ac-captcha-message\">$message</div>
	<img src=\"{$url}\" class=\"ac-captcha-image\">
	<div class=\"ac-captcha-input\">
		<input type=\"hidden\" name=\"ac_captcha_key\" value=\"{$key}\">
		<input type=\"text\" name=\"ac_captcha_response\">
		<a href=\"javascript:void(0);\" class=\"ac-captcha-refresh\" captcha_key=\"{$key}\"></a>
		<div class=\"ac-captcha-clear-fix\"></div>
	</div>
</div>
<script type=\"text/javascript\">
(function() {
	\"use strict\";
	var elements = document.getElementsByClassName(\"ac-captcha-refresh\"),
		clickFunction = function (e) {
		var img, i, xmlhttp,
			key = this.getAttribute(\"captcha_key\"),
			timestamp = new Date().getTime();
	    if(typeof window.XMLHttpRequest === \"undefined\") {
			try {
				xmlhttp = new ActiveXObject(\"Msxml2.XMLHTTP.6.0\");
			} catch(e) { try {
				xmlhttp = new ActiveXObject(\"Msxml2.XMLHTTP.3.0\");
			} catch(e) { try {
				xmlhttp = new ActiveXObject(\"Microsoft.XMLHTTP\");
			} catch(e) {
				return;
			}}}
		} else {
			xmlhttp = new XMLHttpRequest();
		}

		for(i = 0; i < this.parentNode.parentNode.childNodes.length; i++) {
		    if(this.parentNode.parentNode.childNodes[i].className == \"ac-captcha-image\") {
				img = this.parentNode.parentNode.childNodes[i];
		        break;
		    }
		}
	    xmlhttp.onreadystatechange = function() {
		    if(xmlhttp.readyState == 4 && xmlhttp.status == 200) {
		        img.src = \"/captcha.php?id=\" + key + \"&_=\" + timestamp ;
		    }
	    };
	    xmlhttp.open(\"GET\", \"/captcha.php?id=\" + key + \"&x=refresh&_=\" + timestamp, false);
	    xmlhttp.send();
	};
	for(var i = 0; i < elements.length; ++i) {
		elements[i].onclick = clickFunction;
	}
})();
</script>
";
	}

	/**
	 * @param \Aqua\UI\AbstractForm $form
	 * @param                       $message
	 * @return int
	 */
	public function validate(AbstractForm $form, &$message = null)
	{
		if(($input = $form->getString('ac_captcha_response', null)) === null ||
		   ($key = $form->getString('ac_captcha_key', null)) === null) {
			return Form::VALIDATION_INCOMPLETE;
		}
		switch(App::captcha()->validate($key, App::request()->ip, $input)) {
			case \Aqua\Captcha\Captcha::CAPTCHA_INCORRECT_ANSWER:
				$this->error = self::VALIDATION_INCORRECT_ANSWER;
				$this->_setWarning(__('form', 'invalid-captcha'));

				return Form::VALIDATION_FAIL;
			case \Aqua\Captcha\Captcha::CAPTCHA_SUCCESS:
				return Form::VALIDATION_SUCCESS;
			case \Aqua\Captcha\Captcha::CAPTCHA_INCOMPLETE:
			default:
				return Form::VALIDATION_INCOMPLETE;
		}
	}

	public function _setWarning($warning)
	{
		if(empty($this->errorMessage[$this->error])) {
			$this->setWarning($warning);
		} else {
			$this->setWarning($this->errorMessage[$this->error]);
		}
	}
}
