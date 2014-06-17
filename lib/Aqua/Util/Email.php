<?php
namespace Aqua\Util;

use Aqua\Core\App;
use Aqua\Core\Exception\InvalidArgumentException;
use Aqua\Ragnarok\Account as RagnarokAccount;
use Aqua\Ragnarok\Character;
use Aqua\SQL\Query;
use Aqua\User\Account as UserAccount;
use PHPMailer\PHPMailer;

class Email
{
	public $fromAddress;
	public $fromName;
	public $to = array();
	public $subject;
	public $body;
	public $altBody;
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

	public function __construct($subject, $body, $altBody = null)
	{
		$this->subject = $subject;
		$this->body    = $body;
		$this->altBody = $altBody;
	}

	public function replace(array $replacements)
	{
		$search  = array();
		$replace = array();
		foreach($replacements as $key => $word) {
			$search[]  = "#$key#";
			$replace[] = $word;
		}
		unset($replacements);
		$this->subject = str_replace($search, $replace, $this->subject);
		$this->body    = str_replace($search, $replace, $this->body);
		if(!empty($this->altBody)) {
			$this->altBody = str_replace($search, $replace, $this->altBody);
		}

		return $this;
	}


	public function setFrom($address, $name = null)
	{
		$this->fromAddress = $address;
		$this->fromName    = $name;

		return $this;
	}
	public function addAddress($address, $name = null)
	{
		$this->_address($this->to, $address, $name);

		return $this;
	}

	public function addCC($address, $name = null)
	{
		$this->_address($this->cc, $address, $name);

		return $this;
	}

	public function addBCC($address, $name = null)
	{
		$this->_address($this->bcc, $address, $name);

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

	public function isHtml($useHtml = true)
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
		$phpMailer->Subject  = $this->subject;
		$phpMailer->Priority = $this->priority;
		$phpMailer->AltBody  = $this->altBody;
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
			$phpMailer->isHTML(false);
		}
		foreach($this->to as $address => $name) {
			$phpMailer->addAddress($address, $name);
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
		INSERT INTO %s (_date, _from_address, _from_name, _subject, _content, _status)
		VALUES (NOW(), :fromaddr, :fromname, :subject, :body, \'pending\')
		', ac_table('mail_queue')));
		$sth->bindValue(':subject', $this->subject, \PDO::PARAM_STR);
		$sth->bindValue(':body', $this->body, \PDO::PARAM_STR);
		if($this->fromAddress) $sth->bindValue(':fromaddr', $this->fromAddress, \PDO::PARAM_STR);
		else $sth->bindValue(':fromaddr', null, \PDO::PARAM_NULL);
		if($this->fromAddress) $sth->bindValue(':fromname', $this->fromName, \PDO::PARAM_STR);
		else $sth->bindValue(':fromname', null, \PDO::PARAM_NULL);
		$sth->execute();
		$id = App::connection()->lastInsertId();
		$sth->closeCursor();
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO %s (_mail_id, _address, _name, _type)
		VALUES (:id, :addr, :name, :type)
		', ac_table('mail_recipient')));
		foreach($this->to as $address => $name) {
			$sth->bindValue(':id', $id, \PDO::PARAM_INT);
			$sth->bindValue(':addr', $address, \PDO::PARAM_STR);
			$sth->bindValue(':type', 'to', \PDO::PARAM_STR);
			if($name) $sth->bindValue(':name', $name, \PDO::PARAM_STR);
			else $sth->bindValue(':name', null, \PDO::PARAM_NULL);
			$sth->execute();
			$sth->closeCursor();
		}
		foreach($this->cc as $address => $name) {
			$sth->bindValue(':id', $id, \PDO::PARAM_INT);
			$sth->bindValue(':addr', $address, \PDO::PARAM_STR);
			$sth->bindValue(':type', 'cc', \PDO::PARAM_STR);
			if($name) $sth->bindValue(':name', $name, \PDO::PARAM_STR);
			else $sth->bindValue(':name', null, \PDO::PARAM_NULL);
			$sth->execute();
			$sth->closeCursor();
		}
		foreach($this->bcc as $address => $name) {
			$sth->bindValue(':id', $id, \PDO::PARAM_INT);
			$sth->bindValue(':addr', $address, \PDO::PARAM_STR);
			$sth->bindValue(':type', 'bcc', \PDO::PARAM_STR);
			if($name) $sth->bindValue(':name', $name, \PDO::PARAM_STR);
			else $sth->bindValue(':name', null, \PDO::PARAM_NULL);
			$sth->execute();
			$sth->closeCursor();
		}
		return $this;
	}

	protected function _address(&$addrList, $address, $name)
	{
		if(is_array($address)) {
			foreach($address as $addr => $name) {
				if(is_int($addr)) {
					$this->_address($addrList, $name, null);
				} else {
					$this->_address($addrList, $addr, $name);
				}
			}
		} else {
			if($address instanceof Character) {
				$name    = $address->name;
				$address = $address->account()->email;
			} else if($address instanceof RagnarokAccount) {
				$name    = $address->username;
				$address = $address->email;
			} else if($address instanceof UserAccount) {
				$name    = $address->displayName;
				$address = $address->email;
			} else if(!is_string($address)) {
				throw new InvalidArgumentException(1, array(
					'Aqua\\Ragnarok\\Character',
					'Aqua\\Ragnarok\\Account',
					'Aqua\\User\\Account',
				    'string'
				), $address);
			}
			$addrList[$address] = $name;
		}
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

	/**
	 * @param string $template
	 * @return \Aqua\Util\Email|bool
	 */
	public static function fromTemplate($template)
	{
		if(!$template = self::getTemplate($template, false)) {
			return false;
		}
		return new static($template['subject'], $template['body'], $template['alt_body']);
	}

	public static function getTemplate($key, $placeholders = false)
	{
		$select = Query::select(App::connection())
			->columns(array(
				'key'             => '_key',
			    'name'            => '_name',
			    'default_subject' => '_default_subject',
			    'default_body'    => '_default_body',
			    'subject'         => '_subject',
			    'body'            => '_body',
			    'alt_body'        => '_alt_body',
			    'plugin_id'       => '_plugin_id',
			))
			->setColumnType(array( 'plugin_id' => 'integer' ))
			->from(ac_table('email_templates'))
			->where(array( '_key' => $key ))
			->limit(1)
			->query();
		if(!$select->valid()) {
			return null;
		}
		$template = array(
			'key'       => $select->get('key'),
			'name'      => $select->get('name'),
			'plugin_id' => $select->get('plugin_id'),
			'subject'   => $select->get('subject') ?: $select->get('default_subject'),
			'body'      => $select->get('body') ?: $select->get('default_body'),
			'alt_body'  => $select->get('alt_body') ?: '',
		);
		if($placeholders) {
			$placeholders = Query::select(App::connection())
				->columns(array(
					'key'         => '_key',
				    'description' => '_description'
				))
				->from(ac_table('email_placeholders'))
				->where(array( '_email' => $select->get('key') ))
				->query();
			$template['placeholders'] = $placeholders->getColumn('description', 'key');
		}
		return $template;
	}

	public static function editTemplate($template, $subject, $body, $altBody)
	{
		$sth = App::connection()->prepare(sprintf('
		UPDATE %s
		SET _subject = :subject,
			_body = :body,
			_alt_body = :alt
		WHERE _key = :key
		', ac_table('email_templates')));
		$sth->bindValue(':key', $template, \PDO::PARAM_STR);
		if($subject !== null) $sth->bindValue(':subject', $subject, \PDO::PARAM_STR);
		else $sth->bindValue(':subject', null, \PDO::PARAM_NULL);
		if($body !== null) $sth->bindValue(':body', $body, \PDO::PARAM_STR);
		else $sth->bindValue(':body', null, \PDO::PARAM_NULL);
		if($altBody !== null) $sth->bindValue(':alt', $altBody, \PDO::PARAM_STR);
		else $sth->bindValue(':alt', null, \PDO::PARAM_NULL);
		$sth->execute();
		return (bool)$sth->rowCount();
	}
}
