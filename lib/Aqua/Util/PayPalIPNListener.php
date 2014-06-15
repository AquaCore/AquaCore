<?php
namespace Aqua\Util;

use Aqua\Core\App;
use Aqua\Core\Settings;
use Aqua\Event\Event;
use Aqua\Http\Request;
use Aqua\Log\PayPalLog;

class PayPalIPNListener
{
	public $sandbox;
	public $currency;
	public $minimumDonation;
	public $exchangeRate;
	public $validReceiverEmails;
	public $validTransactionTypes;

	public $request;
	public $error;
	public $output;

	const FAILED_TO_CONNECT = 1;
	const INVALID_REQUEST   = 2;

	public function __construct(Request $request, Settings $settings)
	{
		$this->request               = $request;
		$this->sandbox               = $settings->get('pp_sandbox', false);
		$this->currency              = strtoupper($settings->get('currency', 'USD'));
		$this->exchangeRate          = (float)$settings->get('exchange_rate', 1);
		$this->minimumDonation       = (float)$settings->get('min_donation', 2);
		$this->validReceiverEmails   = $settings->get('pp_receiver_email')->toArray();
		$this->validTransactionTypes = $settings->get('pp_txn_types')->toArray();
		array_unshift($this->validReceiverEmails, $settings->get('pp_business_email', null));
		$this->validReceiverEmails   = array_map('strtolower', array_filter($this->validReceiverEmails));
	}

	public function process()
	{
		$this->append('Received request from %s', App::request()->ipString);
		if(!$this->verifyRequest()) {
			return false;
		}
		$this->append('Validating transaction ID #%d...', $this->request->getString('txn_id', 'NULL'));
		if(!$this->isValidReceiverEmail()) {
			$this->append('Invalid receiver email "%s".', $this->request->getString('receiver_email', 'NULL'));
			return false;
		}
		if(!$this->isValidTxnType()) {
			$this->append('Invalid transaction type "%s".', $this->request->getString('txn_type', 'NULL'));
			return false;
		}
		$exchanged = false;
		switch($this->isExchangable()) {
			case 0:
				if($exchanged = $this->exchange()) {
					$this->append('%d credit points exchanged.', $this->credits());
					$exchanged = true;
				} else if(!$this->request->getInt('custom', null)) {
					$this->append('User ID not specified, cannot exchange credits.');
				} else {
					$this->append('Failed to deposit credits, invalid user ID (%s).', $this->request->getInt('custom', 'NULL'));
				}
				break;
			case 1:
				$this->append('Incomplete payment status (%s).', $this->request->getString('payment_status', 'NULL'));
				break;
			case 2:
				$this->append('Transction currency "%s" not exchangable.', $this->request->getString('mc_currency', 'NULL'));
				break;
			case 3:
				$this->append('Donation is less than the minimum amount configured.');
				break;
		}
		$this->log($exchanged);
		return true;
	}

	public function verifyRequest($timeout = 20)
	{
		$this->append('Verifying request...');
		$host = ($this->sandbox ? 'sandbox.paypal.com' : 'paypal.com');
		$queryString = 'cmd=_notify-validate';
		foreach($this->request->data as $key => $val) {
			$queryString.= "&{$key}=" . urlencode(stripslashes($val));
		}
		$len  = strlen($queryString);
		$request = "POST /cgi-bin/webscr HTTP/1.1\r\n" .
	               "Content-Type: application/x-www-form-urlencoded\r\n" .
	               "Content-Length: $len\r\n" .
	               "Host: $host\r\n" .
	               "Connection: Close\r\n\r\n" . $queryString;
		$fp = @fsockopen("ssl://www.$host", 443, $errno, $errstr, $timeout);
		if(!$fp) {
			$this->error = self::FAILED_TO_CONNECT;
			$this->append('Failed to connect to %s.', $host);
			return false;
		}
		$this->append('Sending request to %s:443', $host);
		fputs($fp, $request);
		$response = '';
		while(!feof($fp)) {
			$response.= fgets($fp, 1024);
		}
		fclose($fp);
		$this->append('Response:');
		$this->output.= $response;
		if(strpos($response, 'VERIFIED') !== false) {
			$this->append('Notification verified.');
			return true;
		} else {
			$this->append('Notification not verified.');
			$this->error = self::INVALID_REQUEST;
			return false;
		}
	}

	public function isValidReceiverEmail()
	{
		$receiverEmail = strtolower($this->request->getString('receiver_email', null));
		if(!in_array($receiverEmail, $this->validReceiverEmails)) {
			return false;
		} else {
			return true;
		}
	}

	public function isValidTxnType()
	{
		$txnType = strtolower($this->request->getString('txn_type', null));
		if(!in_array($txnType, $this->validTransactionTypes)) {
			return false;
		} else {
			return true;
		}
	}

	public function isExchangable()
	{
		if($this->request->getString('payment_status', null) !== 'Completed') {
			return 1;
		}
		$mcCurrency = strtoupper(substr($this->request->getString('mc_currency', ''), 0, 3));
		$mcGross    = $this->request->getFloat('mc_gross', 0, 0);
		if($mcCurrency !== $this->currency) {
			return 2;
		} else if($mcGross < $this->minimumDonation) {
			return 3;
		} else {
			return 0;
		}
	}

	public function credits()
	{
		return floor($this->request->getFloat('mc_gross', 0, 0) / $this->exchangeRate);
	}

	public function exchange()
	{
		$userId = $this->request->getInt('custom', null, 0);
		if($userId === null || $this->credits() <= 0) {
			return false;
		}
		$feedback = array( $this );
		Event::fire('paypal.exchange', $feedback);
		$sth = App::connection()->prepare(sprintf('
		UPDATE %s
		SET _credits = _credits + :amount
		WHERE id = :id
		', ac_table('users')));
		$sth->bindValue(':amount', $this->credits(), \PDO::PARAM_INT);
		$sth->bindValue(':id', $userId, \PDO::PARAM_INT);
		$sth->execute();
	    if($sth->rowCount()) {
		    Event::fire('paypal.after-exchange', $feedback);
		    return true;
	    } else {
		    return false;
	    }
	}

	public function log($exchanged)
	{
		return PayPalLog::logSql(
			$this->request,
			$this->request->getInt('custom', null, 0),
			$exchanged ? $this->credits() : 0,
			$this->exchangeRate,
			$exchanged
		);
	}

	public function append($str)
	{
		$this->output.= '[' . date('Y-m-d H:i:s') . '] ';
		$this->output.= call_user_func_array('sprintf', func_get_args()) . "\r\n";
		return $this;
	}
}
