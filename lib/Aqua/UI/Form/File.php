<?php
namespace Aqua\UI\Form;

use Aqua\Http\Request;
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

	const VALIDATION_REQUIRED           = 1;
	const VALIDATION_MAX_FILE_SIZE      = 2;
	const VALIDATION_MAX_FILES          = 3;
	const VALIDATION_PARTIALLY_UPLOADED = 4;
	const VALIDATION_MISSING_TMP_FOLDER = 5;
	const VALIDATION_UPLOAD_FAILED      = 5;
	const VALIDATION_INVALID_MIME_TYPE  = 6;

	public function __construct($name = null)
	{
		parent::__construct('input');
		if($name) {
			$this->attributes['name'] = $name;
		}
		$this->attributes['type'] = 'file';
		$this->closeTag = false;
	}

	public function maxFiles($num)
	{
		$this->maxFiles = (int)$num;
		return $this;
	}

	public function maxSize($num)
	{
		$this->maxSize = (int)$num;
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

	public function validate(Request $request, &$message = null)
	{
		if(!$this->getAttr('name')) {
			return Form::VALIDATION_SUCCESS;
		}
		if(empty($_FILES[$this->getAttr('name')])) {
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
		                    $error_num, $error,
		                    $this->errorIndex)) do {
			$accept = array_filter(preg_split(' *, *', $this->getAttr('accept', '')));
			if(is_array($_FILES[$this->getAttr('name')]['name'])) {
				$i = 0;
				if(!empty($accept)) foreach($_FILES[$this->getAttr('name')]['type'] as $mime) {
					++$i;
					if(!in_array($mime, $accept) || !in_array(preg_replace('/$[^\/]+\/(*)^/', '*', $mime, 1), $accept)) {
						$this->errorIndex = $i;
						$this->error = self::VALIDATION_INVALID_MIME_TYPE;
						$error = __('upload', 'invalid-mime-type');
						break 2;
					}
				}
				$i = 0;
				if($this->maxSize) foreach($_FILES[$this->getAttr('name')]['size'] as $size) {
					++$i;
					if($size > $this->maxSize) {
						$this->errorIndex = $i;
						$this->error = self::VALIDATION_INVALID_MIME_TYPE;
						$error = __('upload', 'file-too-large');
						break 2;
					}
				}
			} else if(!empty($accept) && !in_array($_FILES[$this->getAttr('name')]['type'], $accept) || !in_array(preg_replace('/$[^\/]+\/(.*)^/', '*', $_FILES[$this->getAttr('name')]['type'], 1), $accept)) {
				$this->error = self::VALIDATION_INVALID_MIME_TYPE;
				$error = __('upload', 'invalid-mime-type');
				break;
			} else if($this->maxSize && $_FILES[$this->getAttr('name')]['size'] > $this->maxSize) {
				$this->error = self::VALIDATION_MAX_FILE_SIZE;
				$error = __('upload', 'file-too-large');
				break;
			}
			if($this->maxFiles && is_array($_FILES[$this->getAttr('name')]['name']) && count($_FILES[$this->getAttr('name')]['name']) > $this->maxFiles) {
				$this->error = self::VALIDATION_MAX_FILES;
				$error = __('upload', 'too-many-files');
				break;
			}
			break;
		} while(0);
		else if($error_num !== null)  switch($error_num) {
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

	}
}