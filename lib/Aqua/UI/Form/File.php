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
	 * @param array|string $mimeTypes
	 * @param array|string $extensions
	 * @return \Aqua\UI\Form\File
	 */
	public function accept($mimeTypes, $extensions = null)
	{
		if(!is_array($mimeTypes)) {
			$mimeTypes = array( $mimeTypes );
		}
		foreach($mimeTypes as $type) {
			if($extensions === false) {
				unset($this->accept[$type]);
			} else {
				if(!array_key_exists($type, $this->accept)) {
					$this->accept[$type] = array();
				}
				if(is_array($extensions)) {
					$this->accept[$type] = array_merge($this->accept[$type], $extensions);
				} else if($extensions !== null && $extensions !== true) {
					$this->accept[$type][] = $extensions;
				}
			}
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
		                    $this->errorIndex)) {
			do {
				$finfo = null;
				try { $finfo = new \finfo(FILEINFO_MIME_TYPE); } catch(\Exception $e) { }
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
						$type = ($finfo ? $finfo->file($file['tmp_name']) : $file['type']);
						if(!$this->_checkAccept($error, $type, $file['name'], $i)) {
							break 2;
						}
					}
				}
				break;
			} while(0);
			unset($finfo);
		} else if($errorNum !== null)  switch($errorNum) {
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
		$attr = array();
		if(!empty($this->accept) && $this->getAttr('accept', null) === null) {
			$attr['accept'] = $this->getAttr('accept');
			$this->attr('accept', implode(',', array_keys($this->accept)));
		}
		if($this->getBool('multiple') && $this->getAttr('name')) {
			$name = $this->getAttr('name');
			$attr['name'] = $name;
			$this->attr('name', "{$name}[]");
		}
		$html = parent::render();
		foreach($attr as $key => $value) {
			$this->attr($key, $value);
		}
		return $html;
	}

	protected function _checkAccept(&$error, $type, $name, $index)
	{
		if(empty($this->accept)) {
			return true;
		}
		$xType = preg_replace('/\/(.*)$/', '/*', $type, 1);
		if(array_key_exists($xType, $this->accept)) {
			$type = $xType;
		} else if(!array_key_exists($type, $this->accept)) {
			$this->errorIndex = $index;
			$this->error = self::VALIDATION_INVALID_MIME_TYPE;
			$error = __('upload', 'invalid-mime-type');
			return false;
		}
		$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
		foreach($this->accept[$type] as $extension) {
			if($this->_isRegularExpression($extension) && preg_match($extension, $name) || $extension === $ext) {
				return true;
			}
		}
		$this->errorIndex = $index;
		$this->error = self::VALIDATION_INVALID_EXTENSION;
		$error = __('upload', 'ext-mime-mismatch');
		return false;
	}

	protected function _isRegularExpression($str)
	{
		$str = preg_replace('/[imsxeuADSUXJ]+$/', '', $str);
		return ($str[0] === substr($str, -1) && !ctype_alnum($str[0]));
	}
}
