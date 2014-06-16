<?php
namespace Aqua\UI\Form;

use Aqua\UI\AbstractForm;
use Aqua\UI\Form;
use Aqua\UI\Tag;

class File
extends Tag
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
	 * @var string
	 */
	public $errorIndex = null;
	/**
	 * @var int
	 */
	public $error = 0;
	/**
	 * @var array
	 */
	public $errorMessage = array();
	/**
	 * @var int
	 */
	public $maxFiles = null;
	/**
	 * @var int
	 */
	public $maxSize = 0;
	/**
	 * @var array
	 */
	public $accept = array();

	const VALIDATION_REQUIRED           = 1;
	const VALIDATION_MAX_FILE_SIZE      = 2;
	const VALIDATION_MAX_FILES          = 3;
	const VALIDATION_PARTIALLY_UPLOADED = 4;
	const VALIDATION_MISSING_TMP_FOLDER = 5;
	const VALIDATION_UPLOAD_FAILED      = 5;
	const VALIDATION_INVALID_MIME_TYPE  = 6;
	const VALIDATION_INVALID_EXTENSION  = 7;

	public function __construct($name = null)
	{
		parent::__construct('input');
		if($name) {
			$this->attributes['name'] = $name;
		}
		$this->attributes['type'] = 'file';
		$this->closeTag = false;
	}

	/**
	 * @param bool $val
	 * @return \Aqua\UI\Form\File
	 */
	public function multiple($val = true)
	{
		$this->bool('multiple', $val);
		return $this;
	}

	/**
	 * @param int $num
	 * @return \Aqua\UI\Form\File
	 */
	public function maxFiles($num)
	{
		$this->maxFiles = (int)$num;
		if($num) {
			$this->attr('max', $num);
		} else {
			$this->attr('max', null);
		}
		return $this;
	}

	/**
	 * @param int $num
	 * @return \Aqua\UI\Form\File
	 */
	public function maxSize($num)
	{
		$this->maxSize = (int)$num;
		return $this;
	}

	/**
	 * @param array $mimeTypes
	 * @return \Aqua\UI\Form\File
	 */
	public function accept(array $mimeTypes)
	{
		if(empty($mimeTypes)) {
			$this->accept = null;
			$this->attr('accept', null);
		} else {
			$accept = array();
			foreach($mimeTypes as $key => $val) {
				if(is_int($key)) {
					$accept[$val] = null;
				} else {
					$accept[$key] = $val;
				}
			}
			$this->accept = $accept;
			$this->attr('accept', implode(',', array_keys($accept)));
		}
		return $this;
	}

	/**
	 * @return \Aqua\UI\Form\File
	 */
	public function required()
	{
		$this->boolean[] = 'required';
		return $this;
	}

	/**
	 * @param string $label
	 * @return \Aqua\UI\Form\File
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
	 * @return \Aqua\UI\Form\File
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
	 * @return \Aqua\UI\Form\File
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
	 * @return \Aqua\UI\Form\File
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
	 * @return \Aqua\UI\Form\File
	 */
	public function setDefaultErrorMessage($message, $type = self::VALIDATION_REQUIRED)
	{
		$this->errorMessage[$type] = $message;
		return $this;
	}

	public function validate(AbstractForm $form, &$message = null)
	{
		if(!$this->getAttr('name')) {
			return Form::VALIDATION_SUCCESS;
		}
		$files = ac_files($this->getAttr('name'));
		if(empty($files)) {
			if($this->getAttr('required')) {
				$this->error = self::VALIDATION_REQUIRED;
				$this->_setWarning(__('form', 'field-required'));
				return Form::VALIDATION_FAIL;
			} else {
				return Form::VALIDATION_SUCCESS;
			}
		}
		$error = null;
		if(ac_file_uploaded($this->getAttr('name'),
		                    $this->getBool('multiple'),
		                    $errorNum, $error,
		                    $this->errorIndex)) do {
			if($this->getBool('multiple')) {
				$i = 0;
				if($this->maxFiles && count($files) > $this->maxFiles) {
					$this->error = self::VALIDATION_MAX_FILES;
					$error = __('upload', 'too-many-files');
					break;
				}
				foreach($files as $file) {
					++$i;
					if($this->maxSize && $file['size'] > $this->maxSize) {
						$this->errorIndex = $i;
						$this->error = self::VALIDATION_INVALID_MIME_TYPE;
						$error = __('upload', 'invalid-mime-type');
						break 2;
					}
					if(!$this->_checkAccept($error, $file['type'], $file['name'], $i)) {
						break 2;
					}
				}
			}
			break;
		} while(0);
		else if($errorNum !== null)  switch($errorNum) {
			case UPLOAD_ERR_NO_FILE:
				break;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$this->error = self::VALIDATION_MAX_FILE_SIZE;
				break;
			case UPLOAD_ERR_PARTIAL:
				$this->error = self::VALIDATION_PARTIALLY_UPLOADED;
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$this->error = self::VALIDATION_MISSING_TMP_FOLDER;
				break;
			default:
				$this->error = self::VALIDATION_UPLOAD_FAILED;
				break;
		}
		if($this->error) {
			$this->_setWarning($error);
			return Form::VALIDATION_FAIL;
		} else {
			return Form::VALIDATION_SUCCESS;
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

	public function render()
	{
		if($this->getBool('multiple') && ($name = $this->getAttr('name'))) {
			$this->attr('name', "{$name}[]");
			$html = parent::render();
			$this->attr('name', $name);
			return $html;
		} else {
			return parent::render();
		}
	}

	protected function _checkAccept(&$error, $type, $name, $index)
	{
		$xType = preg_replace('/$[^\/]+\/(.*)^/', '*', $type, 1);
		if(empty($this->accept)) {
			return true;
		}
		if(array_key_exists($xType, $this->accept)) {
			$type = $xType;
		} else if(!array_key_exists($type, $this->accept)) {
			$this->errorIndex = $index;
			$this->error = self::VALIDATION_INVALID_MIME_TYPE;
			$error = __('upload', 'invalid-mime-type');
			return false;
		}
		if(is_array($this->accept[$type])) {
			$ext = pathinfo($name, PATHINFO_EXTENSION);
			if(!in_array(strtolower($ext), $this->accept[$type])) {
				$this->errorIndex = $index;
				$this->error = self::VALIDATION_INVALID_EXTENSION;
				$error = __('upload', 'ext-mime-mismatch');
				return false;
			}
		} else if(is_string($this->accept[$type]) && $this->accept[$type] &&
		          !preg_match($this->accept[$type], $name)) {
			$this->errorIndex = $index;
			$this->error = self::VALIDATION_INVALID_EXTENSION;
			$error = __('upload', 'ext-mime-mismatch');
			return false;
		}
		return true;
	}
}