<?php
use Aqua\Core\App;
use Aqua\User\Account;
use Aqua\Log\ErrorLog;
use Aqua\Log\PayPalLog;
use Aqua\Plugin\Plugin;
use Aqua\Event\Event;

!empty($_POST) or die;


define('Aqua\ROOT',        str_replace('\\', '/', rtrim(__DIR__, DIRECTORY_SEPARATOR)));
define('Aqua\SCRIPT_NAME', basename(__FILE__));
define('Aqua\ENVIRONMENT', 'MINIMAL');
define('Aqua\PROFILE',     'PAYPAL_IPN_LISTENER');

include 'lib/bootstrap.php';

try {
	Plugin::init();
	$request = App::request();
	$settings = App::settings()->get('donation');
	Event::fire('paypal.verify');
	// Validate
	$pp_url = $settings->get('pp_sandbox', false) ? 'www.sandbox.paypal.com' : 'www.paypal.com';
	$query_string = 'cmd=_notify-validate';
	foreach($_POST as $key => $val) {
		$query_string.= "&{$key}=" . urlencode(stripslashes($val));
	}
	$length = strlen($query_string);
	$pp_request = "POST /cgi-bin/webscr HTTP/1.1\r\n";
	$pp_request.= "Content-Type: application/x-www-form-urlencoded\r\n";
	$pp_request.= "Content-Length: {$length}\r\n";
	$pp_request.= "Host: {$pp_url}\r\n";
	$pp_request.= "Connection: close\r\n\r\n";
	$pp_request.= $query_string;
	$fp = fsockopen("ssl://{$pp_url}", 443, $errno, $errstr, 20);
	$fp or die;
	fputs($fp, $pp_request);
	$line = '';
	while(!feof($fp)) {
		$line = fgets($fp);
	}
	fclose($fp);
	if(strtoupper(trim($line)) !== 'VERIFIED') { die; }

	Event::fire('paypal.verified');

	// Process
	$currency         = strtoupper($settings->get('currency'));
	$c_exchange_rate  = (float)$settings->get('exchange_rate', 1);
	$min_donation     = (float)$settings->get('min_donation', 2);
	$emails           = $settings->get('pp_receiver_email')->toArray();
	array_unshift($emails, $settings->get('pp_business_email', ''));
	array_map('strtolower', $emails);
	$receiver_email   = $request->getString('receiver_email', null);
	$transaction_type = $request->getString('txn_type', null);
	$payment_status   = $request->getString('payment_status', null);
	$settle_amount    = $request->getFloat('settle_amount', 0, 0);
	$mc_gross         = $request->getFloat('mc_gross', 0, 0);
	$mc_fee           = $request->getFloat('mc_fee', 0, 0);
	$mc_currency      = strtoupper(substr($request->getString('mc_currency', ''), 0, 3));
	$user_id          = $request->getInt('custom', null, 1);

	$txn_types = array(
		'web_accept',
		'subscr_payment',
		'recurring_payment',
		'send_money'
	);
	if(!in_array($receiver_email, $emails) ||
	   !in_array($transaction_type, $txn_types) ||
	   $payment_status !== 'Completed') {
		die;
	}

	Event::fire('paypal.process');

	if($mc_currency === $currency && $mc_gross >= $min_donation) {
		$credits = floor($mc_gross / $c_exchange_rate);
		$exchange = true;
	} else {
		$credits = 0;
		$exchange = false;
	}
	// Exchange credits
	try {
		$exchanged = false;
		if($exchange && $credits && $user_id && ($account = Account::get($user_id))) {
			$feedback = array( $account, &$credits );
			Event::fire('paypal.before-exchange', $feedback);
			$tbl = ac_table('users');
			$sth = App::connection()->prepare("
			UPDATE `$tbl`
			SET _credits = _credits + :amount
			WHERE id = :id
			");
			$sth->bindValue(':amount', $credits, PDO::PARAM_INT);
			$sth->bindValue(':id', $account->id, PDO::PARAM_INT);
			$sth->execute();
			if($sth->rowCount()) {
				Event::fire('paypal.after-exchange', $feedback);
				$exchanged = true;
			}
		} else {
			$credits = 0;
		}
	} catch(Exception $exception) {
		ErrorLog::logSql($exception);
	}
	PayPalLog::logSql($request, $user_id, $credits, $c_exchange_rate, $exchanged);
} catch (Exception $exception) {
	ErrorLog::logSql($exception);
}
