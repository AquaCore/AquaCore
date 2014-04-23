<?php
namespace Aqua\UI\Form;

use Aqua\Captcha\ReCaptcha as _ReCaptcha;
use Aqua\Core\App;
use Aqua\Http\Request;
use Aqua\UI\AbstractForm;
use Aqua\UI\Form;

class ReCaptcha
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
	public $error;
	/**
	 * @var string
	 */
	public $errorMessage = array();
	/**
	 * @var array
	 */
	public $options = array();
	/**
	 * @var \Aqua\Captcha\ReCaptcha
	 */
	public $recaptcha;

	const VALIDATION_INCORRECT_ANSWER = 1;

	/**
	 * @param array $options
	 */
	public function __construct(array $options = array())
	{
		$options += array(
			'use_ssl'     => App::settings()->get('captcha')->get('recaptcha_ssl'),
			'public_key'  => App::settings()->get('captcha')->get('recaptcha_public_key'),
			'private_key' => App::settings()->get('captcha')->get('recaptcha_private_key'),
		);
		$this->recaptcha = new _ReCaptcha($options['public_key'], $options['private_key'], $options['use_ssl']);
	}

	/**
	 * @param string $label
	 * @return \Aqua\UI\Form\ReCaptcha
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
	 * @return \Aqua\UI\Form\ReCaptcha
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
	 * @return \Aqua\UI\Form\ReCaptcha
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
	 * @return \Aqua\UI\Form\ReCaptcha
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
	 * @return \Aqua\UI\Form\ReCaptcha
	 */
	public function setDefaultErrorMessage($message, $type = self::VALIDATION_INCORRECT_ANSWER)
	{
		$this->errorMessage[$type] = $message;

		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @return \Aqua\UI\Form\ReCaptcha
	 */
	public function setOption($key, $value)
	{
		$this->options[$key] = $value;

		return $this;
	}

	/**
	 * @param array $options
	 * @return \Aqua\UI\Form\ReCaptcha
	 */
	public function setOptions($options)
	{
		$this->options = array_merge($this->options, $options);

		return $this;
	}

	/**
	 * @param \Aqua\UI\AbstractForm $form
	 * @param                       $errorMessage
	 * @return int
	 */
	public function validate(AbstractForm $form, &$errorMessage = null)
	{
		switch($this->recaptcha->verify($form->request)) {
			case _ReCaptcha::RECAPTCHA_CORRECT_ANSWER:
				return Form::VALIDATION_SUCCESS;
			case _ReCaptcha::RECAPTCHA_INCORRECT_SOL:
				return Form::VALIDATION_INCOMPLETE;
			default:
				$this->error = self::VALIDATION_INCORRECT_ANSWER;
				$this->_setWarning(__('form', 'invalid-captcha'));

				return Form::VALIDATION_FAIL;
		}
	}

	/**
	 * @return string
	 */
	public function render()
	{
		return $this->recaptcha->render($this->options);
	}

	/**
	 * @param string $warning
	 */
	public function _setWarning($warning)
	{
		if(empty($this->errorMessage[$this->error])) {
			$this->setWarning($warning);
		} else {
			$this->setWarning($this->errorMessage[$this->error]);
		}
	}

	public function __clone()
	{
		$this->recaptcha = clone $this->recaptcha;
	}
}
