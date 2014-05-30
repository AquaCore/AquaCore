<?php
namespace Aqua\Util;

use Aqua\Core\App;
use Aqua\Core\L10n;
use PHPMailer\PHPMailer;

class Email
{
	public $fromAddress;
	public $fromName;
	public $toAddress;
	public $subject;
	public $body;
	public $toName;
	public $tidy = true;
	public $tidyConfig = array(
		'indent'       => true,
	    'output-xhtml' => true
	);
	public $html = true;
	public $priority = 3;
	public $ccLimit = 0;
	public $cc = array();
	public $bcc = array();
	protected static $_phpMailer;
	protected static $_fromAddress;
	protected static $_fromName;

	public function __construct($subject, $body, $address, $name = null)
	{
		$this->toAddress = $address;
		$this->toName    = $name;
		if(is_array($body)) {
			L10n::getDefault()->email($subject, $body, $this->subject, $this->body);
		} else {
			$this->subject = $subject;
			$this->body    = $body;
		}
	}

	public function setFrom($address, $name = null)
	{
		$this->fromAddress = $address;
		$this->fromName    = $name;

		return $this;
	}

	public function addCC($address, $name = null)
	{
		if(!is_array($address)) {
			$this->cc[$address] = $name;
		} else {
			$this->cc = array_merge($this->cc, $address);
		}

		return $this;
	}

	public function addBCC($address, $name = null)
	{
		if(!is_array($address)) {
			$this->bcc[$address] = $name;
		} else {
			$this->bcc = array_merge($this->bcc, $address);
		}

		return $this;
	}

	public function setCCLimit($limit)
	{
		$this->ccLimit = $limit;

		return $this;
	}

	public function setPriority($priority)
	{
		switch(strtolower((string)$priority)) {
			case 'high':
			case '1':
				$this->priority = 1;
				break;
			default:
			case 'normal':
			case '3':
				$this->priority = 3;
				break;
			default:
			case 'low':
			case '5':
				$this->priority = 5;
				break;
		}

		return $this;
	}

	public function html($useHtml = true)
	{
		$this->html = $useHtml;

		return $this;
	}

	public function tidy($useTidy = true, $config = null)
	{
		$this->tidy = $useTidy;
		if($config) {
			$this->tidyConfig = $config;
		}

		return null;
	}

	public function send()
	{
		$phpMailer = self::phpMailer();
		if($this->fromAddress) {
			$phpMailer->setFrom($this->fromAddress);
		}
		if($this->fromName) {
			$phpMailer->FromName = $this->fromName;
		}
		$phpMailer->addAddress($this->toAddress, $this->toName);
		$phpMailer->Subject = $this->subject;
		$phpMailer->Priority = $this->priority;
		if($this->html) {
			$body = $this->body;
			if($this->tidy && class_exists('tidy')) {
				try {
					$tidy = new \tidy;
					$tidy->parseString($body, $this->tidyConfig);
					$tidy->cleanRepair();
					$body = $tidy->html()->value;
				} catch(\Exception $exception) { }
			}
			$phpMailer->Body = $body;
			$phpMailer->isHTML(true);
		} else {
			$phpMailer->Body = $this->body;
			$phpMailer->AltBody = $this->body;
			$phpMailer->isHTML(false);
		}
		foreach($this->cc as $address => $name) {
			$phpMailer->addCC($address, $name);
		}
		foreach($this->bcc as $address => $name) {
			$this->addBCC($address, $name);
		}
		$ret = $phpMailer->send();
		self::_reset();
		return $ret;
	}

	public function queue()
	{
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO `%s` (_date, _to_address, _to_name, _from_address, _from_name, _subject, _body, _status)
		VALUES (NOW(), :toaddr, :toname, :fromaddr, :fromname, :subject, :body, \'pending\')
		', ac_table('mail_queue')));
		$sth->bindValue(':toaddr', $this->toAddress, \PDO::PARAM_STR);
		$sth->bindValue(':toname', $this->toName, \PDO::PARAM_STR);
		$sth->bindValue(':subject', $this->subject, \PDO::PARAM_STR);
		$sth->bindValue(':subject', $this->body, \PDO::PARAM_STR);
		if($this->fromAddress) $sth->bindValue(':fromaddr', $this->fromAddress, \PDO::PARAM_STR);
		else $sth->bindValue(':fromaddr', null, \PDO::PARAM_NULL);
		if($this->fromAddress) $sth->bindValue(':fromname', $this->fromName, \PDO::PARAM_STR);
		else $sth->bindValue(':fromname', null, \PDO::PARAM_NULL);
		$sth->execute();
		$id = App::connection()->lastInsertId();
		$sth->closeCursor();
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO `%s` (_mail_id, _address, _name, _bcc)
		VALUES (:id, :addr, :name, :bcc)
		', ac_table('mail_cc')));
		foreach($this->cc as $address => $name) {
			$sth->bindValue(':id', $id, \PDO::PARAM_INT);
			$sth->bindValue(':addr', $address, \PDO::PARAM_STR);
			$sth->bindValue(':bcc', 'n', \PDO::PARAM_STR);
			if($name) $sth->bindValue(':name', $name, \PDO::PARAM_STR);
			else $sth->bindValue(':name', null, \PDO::PARAM_NULL);
			$sth->execute();
			$sth->closeCursor();
		}
		foreach($this->bcc as $address => $name) {
			$sth->bindValue(':id', $id, \PDO::PARAM_INT);
			$sth->bindValue(':addr', $address, \PDO::PARAM_STR);
			$sth->bindValue(':bcc', 'y', \PDO::PARAM_STR);
			if($name) $sth->bindValue(':name', $name, \PDO::PARAM_STR);
			else $sth->bindValue(':name', null, \PDO::PARAM_NULL);
			$sth->execute();
			$sth->closeCursor();
		}
		return $this;
	}

	protected static function _reset()
	{
		$phpMailer = self::phpMailer();
		$phpMailer->From = self::$_fromAddress;
		$phpMailer->FromName = self::$_fromName;
		$phpMailer->Subject = $phpMailer->Body = $phpMailer->AltBody = '';
		$phpMailer->isHTML(false);
		$phpMailer->clearAllRecipients();
		$phpMailer->clearAttachments();
		$phpMailer->clearCustomHeaders();
		$phpMailer->clearReplyTos();
	}

	public static function phpMailer()
	{
		if(!self::$_phpMailer) {
			$settings = App::settings()->get('email');
			self::$_phpMailer = new PHPMailer(true);
			if($settings->get('use_smtp', false)) {
				self::$_phpMailer->IsSMTP();
				self::$_phpMailer->SMTPSecure  = $settings->get('smtp_encryption', '');
				self::$_phpMailer->SMTPAuth    = (bool)$settings->get('smtp_authentication', false);
				self::$_phpMailer->Port        = (int)$settings->get('smtp_port', 25);
				self::$_phpMailer->Helo        = $settings->get('smtp_helo', '');
				self::$_phpMailer->Realm       = $settings->get('smtp_realm', '');
				self::$_phpMailer->Workstation = $settings->get('smtp_workstation', '');
				self::$_phpMailer->Timeout     = $settings->get('smtp_timeout', 10);
				self::$_phpMailer->Host        = $settings->get('smtp_host', '');
				self::$_phpMailer->Username    = $settings->get('smtp_username', '');
				self::$_phpMailer->Password    = $settings->get('smtp_password', '');
			}
			self::$_phpMailer->Hostname = $settings->get('hostname', '');
			self::$_phpMailer->CharSet  = $settings->get('charset', 'UTF-8');
			self::$_fromAddress         = $settings->get('from_address', '');
			self::$_fromName            = $settings->get('from_name', '');
			self::$_phpMailer->SetFrom(self::$_fromAddress, self::$_fromName);
		}
		return self::$_phpMailer;
	}
}
