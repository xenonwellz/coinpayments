<?php

namespace Xenonwellz\CoinPayments;

class CoinPaymentsAPI {
	private $private_key = '';
	private $public_key = '';
	private $ch = null;

	public function Setup($private_key, $public_key) {
		$this->private_key = $private_key;
		$this->public_key = $public_key;
		$this->ch = null;
	}

	public function GetRates($short = TRUE) {
		$short = $short ? 1:0;
		return $this->api_call('rates', array('short' => $short));
	}

	public function GetBasicProfile() {

		return $this->api_call('get_basic_info', []);
	}

	public function GetBalances($all = FALSE) {
		return $this->api_call('balances', array('all' => $all ? 1:0));
	}

	public function CreateTransactionSimple($amount, $currency1, $currency2, $buyer_email, $address='', $ipn_url='') {
		$req = array(
			'amount' => $amount,
			'currency1' => $currency1,
			'currency2' => $currency2,
			'buyer_email' => $buyer_email,
			'address' => $address,
			'ipn_url' => $ipn_url,
		);
		return $this->api_call('create_transaction', $req);
	}

	public function CreateTransaction($req) {
		return $this->api_call('create_transaction', $req);
	}

	public function GetCallbackAddress($currency, $ipn_url = '') {
		$req = array(
			'currency' => $currency,
			'ipn_url' => $ipn_url,
		);
		return $this->api_call('get_callback_address', $req);
	}

	public function CreateWithdrawal($amount, $currency, $address, $auto_confirm = FALSE, $ipn_url = '') {
		$req = array(
			'amount' => $amount,
			'currency' => $currency,
			'address' => $address,
			'auto_confirm' => $auto_confirm ? 1:0,
			'ipn_url' => $ipn_url,
		);
		return $this->api_call('create_withdrawal', $req);
	}

	public function CreateTransfer($amount, $currency, $merchant, $auto_confirm = FALSE) {
		$req = array(
			'amount' => $amount,
			'currency' => $currency,
			'merchant' => $merchant,
			'auto_confirm' => $auto_confirm ? 1:0,
		);
		return $this->api_call('create_transfer', $req);
	}


	public function SendToPayByName($amount, $currency, $pbntag, $auto_confirm = FALSE) {
		$req = array(
			'amount' => $amount,
			'currency' => $currency,
			'pbntag' => $pbntag,
			'auto_confirm' => $auto_confirm ? 1:0,
		);
		return $this->api_call('create_transfer', $req);
	}

	public function convertCoins($amount, $from, $to, $address) {
		$req = array(
			'amount' => $amount,
			'from' => $from,
			'to' => $to,
			'address' => $address,
		);
		return $this->api_call('convert', $req);
	}

	private function is_setup() {
		return (!empty($this->private_key) && !empty($this->public_key));
	}

	private function api_call($cmd, $req = array()) {
		if (!$this->is_setup()) {
			return array('error' => 'You have not called the Setup function with your private and public keys!');
		}


    $req['version'] = 1;
		$req['cmd'] = $cmd;
		$req['key'] = $this->public_key;

		$post_data = http_build_query($req, '', '&');

		$hmac = hash_hmac('sha512', $post_data, $this->private_key);

		if ($this->ch === null) {
			$this->ch = curl_init('https://www.coinpayments.net/api.php');
			curl_setopt($this->ch, CURLOPT_FAILONERROR, TRUE);
			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
		}
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('HMAC: '.$hmac));
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_data);

		$data = curl_exec($this->ch);
		if ($data !== FALSE) {
			if (PHP_INT_SIZE < 8 && version_compare(PHP_VERSION, '5.4.0') >= 0) {	// We are on 32-bit PHP, so use the bigint as string option. If you are using any API calls with Satoshis it is highly NOT recommended to use 32-bit PHP
				$dec = json_decode($data, TRUE, 512, JSON_BIGINT_AS_STRING);
			} else {
				$dec = json_decode($data, TRUE);
			}
			if ($dec !== NULL && count($dec)) {
				return $dec;
			} else {
				return array('error' => 'Unable to parse JSON result ('.json_last_error().')');
			}
		} else {
			return array('error' => 'cURL error: '.curl_error($this->ch));
		}
	}
};
