<?php
namespace Aqua\Captcha;

use Aqua\Http\Request;
use Aqua\captcha\Exception\ReCaptchaException;

class ReCaptcha
{
	/**
	 * @var string
	 */
	public $publicKey;
	/**
	 * @var string
	 */
	public $privateKey;
	/**
	 * @var bool
	 */
	public $secure;

	const RECAPTCHA_API_SECURE_SERVER = 'https://www.google.com/recaptcha/api';
	const RECAPTCHA_API_SERVER        = 'http://www.google.com/recaptcha/api';
	const RECAPTCHA_VERIFY_SERVER     = 'www.google.com';

	const RECAPTCHA_CORRECT_ANSWER   = 0;
	const RECAPTCHA_INCORRECT_SOL    = 1;
	const RECAPTCHA_INCORRECT_ANSWER = 2;

	/**
	 * @param string $public
	 * @param string $private
	 * @param bool   $secure
	 */
	public function __construct($public, $private, $secure = true)
	{
		$this->publicKey  = $public;
		$this->privateKey = $private;
		$this->secure     = $secure;
	}

	/**
	 * @param array $data
	 * @return string
	 */
	public function queryStringEncode(array $data)
	{
		$req = '';
		foreach($data as $key => $value) {
			$req .= $key . '=' . urlencode(stripslashes($value)) . '&';
		}

		return substr($req, 0, strlen($req) - 1);
	}

	/**
	 * @param string $path
	 * @param array  $data
	 * @param int    $port
	 * @return array
	 * @throws \Aqua\captcha\Exception\ReCaptchaException
	 */
	public function httpPost($path, array $data, $port = 80)
	{
		$req = $this->queryStringEncode($data);

		$http_request = "POST $path HTTP/1.0\r\n";
		$http_request .= "Host: " . self::RECAPTCHA_VERIFY_SERVER . "\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
		$http_request .= "Content-Length: " . strlen($req) . "\r\n";
		$http_request .= "User-Agent: reCAPTCHA/PHP\r\n";
		$http_request .= "\r\n";
		$http_request .= $req;

		$response = '';
		if(false === ($fs = @fsockopen(
				self::RECAPTCHA_VERIFY_SERVER,
				$port,
				$errno,
				$errstr,
				10
			))) {
			throw new ReCaptchaException(
				__('exception', 'could-not-open-socket'),
				ReCaptchaException::FAIL_TO_OPEN_SOCKET
			);
		}

		fwrite($fs, $http_request);

		while(!feof($fs))
			$response .= fgets($fs, 1160); // One TCP-IP packet
		fclose($fs);

		return explode("\r\n\r\n", $response, 2);
	}

	/**
	 * @param array    $options
	 * @param int|null $error
	 * @return string
	 */
	public function render(array $options, $error = null)
	{
		$html = '';
		if($options) {
			$jsonOptions = json_encode($options, JSON_FORCE_OBJECT | JSON_NUMERIC_CHECK);
			$html .= "<script type=\"text/javascript\"> var RecaptchaOptions = $jsonOptions; </script>";
		}
		$html .= <<<HTML
<script src="{$this->scriptSrc($error)}"></script>
<noscript>
	<iframe src="{$this->iframeSrc($error)}" height="300" width="500" frameborder="0"></iframe>
	<textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
	<input type="hidden" name="recaptcha_response_field" value="manual_challenge"></noscript>
</noscript>
HTML;

		return $html;
	}

	/**
	 * @param int|null $error
	 * @return string
	 */
	public function scriptSrc($error)
	{
		if($error) {
			$error = "&amp;error=" . $error;
		}
		if($this->secure) {
			$server = self::RECAPTCHA_API_SECURE_SERVER;
		} else {
			$server = self::RECAPTCHA_API_SERVER;
		}

		return "$server/challenge?k={$this->publicKey}$error";
	}

	/**
	 * @param int|null $error
	 * @return string
	 */
	public function iframeSrc($error)
	{
		if($error) {
			$error = "&amp;error=" . $error;
		}
		if($this->secure) {
			$server = self::RECAPTCHA_API_SECURE_SERVER;
		} else {
			$server = self::RECAPTCHA_API_SERVER;
		}

		return "$server/noscript?k={$this->publicKey}$error";
	}

	/**
	 * @param \Aqua\Http\Request $request
	 * @param array              $extra
	 * @param int|null           $error
	 * @return int
	 */
	public function verify(Request $request, array $extra = array(), &$error = null)
	{
		$response  = $request->getString('recaptcha_response_field', '');
		$challenge = $request->getString('recaptcha_challenge_field', '');
		if($response === '' || strlen($response) === 0 || $challenge === '' || strlen($challenge) == 0) {
			return self::RECAPTCHA_INCORRECT_SOL;
		}
		$httpResponse = $this->httpPost(
			'/recaptcha/api/verify', array(
				'privatekey' => $this->privateKey,
				'remoteip'   => $request->ipString,
				'challenge'  => $challenge,
				'response'   => $response
			) + $extra
		);
		$answers      = explode("\n", $httpResponse[1]);
		if(trim($answers[0]) == 'true') {
			return self::RECAPTCHA_CORRECT_ANSWER;
		}
		$error = $answers[1];

		return self::RECAPTCHA_INCORRECT_ANSWER;
	}

	/**
	 * @param string $val
	 * @return string
	 */
	public function aesPad($val)
	{
		$block_size = 16;
		$numpad     = $block_size - (strlen($val) % $block_size);

		return str_pad($val, strlen($val) + $numpad, chr($numpad));
	}

	/**
	 * @param string $val
	 * @param string $key
	 * @return string
	 * @throws \Aqua\captcha\Exception\ReCaptchaException
	 */
	public function aesEncrypt($val, $key)
	{
		if(!function_exists("mcrypt_encrypt")) {
			throw new ReCaptchaException(
				__('exception', 'missing-extension', 'ReCaptcha', 'mcrypt'),
				ReCaptchaException::EXT_MISSING
			);
		}
		$mode = MCRYPT_MODE_CBC;
		$enc  = MCRYPT_RIJNDAEL_128;
		$val  = $this->aesPad($val);

		return mcrypt_encrypt($enc, $key, $val, $mode, "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0");
	}

	/**
	 * @param string $x
	 * @return string
	 */
	public function mailHideBase64($x)
	{
		return strtr(base64_encode($x), '+/', '-_');
	}

	/**
	 * @param string $email
	 * @return string
	 */
	public function mailHideUrl($email)
	{
		$key       = pack('H*', $this->privateKey);
		$cryptmail = $this->aesEncrypt($email, $key);

		return 'http://www.google.com/recaptcha/mailhide/d?k=' . $this->publicKey . '&c=' . $this->mailHideBase64($cryptmail);
	}

	/**
	 * @param string $email
	 * @return array
	 */
	public function mailHideEmailParts($email)
	{
		$arr = preg_split('/@/', $email);
		if(strlen($arr[0]) <= 4) {
			$arr[0] = substr($arr[0], 0, 1);
		} else {
			if(strlen($arr[0]) <= 6) {
				$arr[0] = substr($arr[0], 0, 3);
			} else {
				$arr[0] = substr($arr[0], 0, 4);
			}
		}

		return $arr;
	}

	/**
	 * @param string $email
	 * @return string
	 */
	public function mailHideHtml($email)
	{
		$emailparts = $this->mailHideEmailParts($email);
		$url        = $this->mailHideUrl($email);

		return htmlentities($emailparts[0]) . "<a href='" . htmlentities($url) .
				"' onclick=\"window.open('" . htmlentities($url) .
				"', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300');" .
				"return false;\" title=\"Reveal this e-mail address\">...</a>@" .
				htmlentities($emailparts [1]);
	}

	/**
	 * @param string $domain
	 * @return string
	 */
	public static function signupUrl($domain = \Aqua\DOMAIN)
	{
		return 'https://www.google.com/recaptcha/admin/create?' . http_build_query(array(
				'domains' => $domain,
				'app'     => 'AquaCore'
			));
	}
}
