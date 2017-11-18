<?php
// A wrapper for Billplz API

namespace apih\Billplz;

class Billplz
{
	const SANDBOX_URL = 'https://billplz-staging.herokuapp.com/api/v3/';
	const LIVE_URL = 'https://www.billplz.com/api/v3/';

	protected $sandbox = false;
	protected $secret_key;
	protected $xsignature_key;
	protected $url;
	protected $use_ssl = true;

	public function __construct($secret_key, $xsignature_key = null)
	{
		$this->secret_key = $secret_key;
		$this->xsignature_key = $xsignature_key;
		$this->url = self::LIVE_URL;
	}

	public function useSandbox($flag = true)
	{
		$this->url = $flag ? self::SANDBOX_URL : self::LIVE_URL;
	}

	public function useSsl($flag = true)
	{
		$this->useSsl = $flag;
	}

	protected function curlInit()
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, $this->secret_key . ':');

		if ($this->useSsl === false) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		}

		return $ch;
	}

	protected function curlPostRequest($action, $query_data)
	{
		$ch = $this->curlInit();

		curl_setopt($ch, CURLOPT_POST, true);

		if ($query_data) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query_data));
		}

		curl_setopt($ch, CURLOPT_URL, $this->url . $action);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		return [$response, $http_code];
	}

	protected function logError($action, $http_code, $response)
	{
		error_log('Billplz Error: ' . $action . ' - ' . $http_code . ' - ' . $response);
	}

	// Collection
	public function createCollection($query_data)
	{
		list($response, $http_code) = $this->curlPostRequest('collections', $query_data);
		
		if ($http_code !== 200) {
			$this->logError(__FUNCTION__, $http_code, $response);
			return false;
		}

		return json_decode($response, true);
	}

	public function getCollection($collection_id)
	{
		$ch = $this->curlInit();

		curl_setopt($ch, CURLOPT_URL, $this->url . 'collections/' . $collection_id);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if ($http_code !== 200) {
			$this->logError(__FUNCTION__, $http_code, $response);
			return false;
		}

		return json_decode($response, true);
	}

	public function activateCollection()
	{
		list($response, $http_code) = $this->curlPostRequest('collections/' . $collection_id . '/activate', []);

		if ($http_code !== 200) {
			$this->logError(__FUNCTION__, $http_code, $response);
			return false;
		}

		return json_decode($response, true);
	}

	public function deactivateCollection()
	{
		list($response, $http_code) = $this->curlPostRequest('collections/' . $collection_id . '/deactivate', []);

		if ($http_code !== 200) {
			$this->logError(__FUNCTION__, $http_code, $response);
			return false;
		}

		return json_decode($response, true);
	}

	// Open collection
	public function createOpenCollection($query_data)
	{
		list($response, $http_code) = $this->curlPostRequest('open_collections', $query_data);

		if ($http_code !== 200) {
			$this->logError(__FUNCTION__, $http_code, $response);
			return false;
		}

		return json_decode($response, true);
	}

	public function getOpenCollection($collection_id)
	{
		$ch = $this->curlInit();

		curl_setopt($ch, CURLOPT_URL, $this->url . 'open_collections/' . $collection_id);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if ($http_code !== 200) {
			$this->logError(__FUNCTION__, $http_code, $response);
			return false;
		}

		return json_decode($response, true);
	}

	// Bill
	public function createBill($query_data)
	{
		list($response, $http_code) = $this->curlPostRequest('bills', $query_data);

		if ($http_code !== 200) {
			$this->logError(__FUNCTION__, $http_code, $response);
			return false;
		}

		return json_decode($response, true);
	}

	public function getBill($bill_id)
	{
		$ch = $this->curlInit();

		curl_setopt($ch, CURLOPT_URL, $this->url . 'bills/' . $bill_id);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if ($http_code !== 200) {
			$this->logError(__FUNCTION__, $http_code, $response);
			return false;
		}

		return json_decode($response, true);
	}

	public function deleteBill($bill_id)
	{
		$ch = $this->curlInit();

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($ch, CURLOPT_URL, $this->url . 'bills/' . $bill_id);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if ($http_code !== 200) {
			$this->logError(__FUNCTION__, $http_code, $response);
			return false;
		}

		return json_decode($response, true);
	}

	// XSignature
	public function setXSignatureKey($xsignature_key)
	{
		$this->xsignature_key = $xsignature_key;
	}

	protected function buildSourceString($data, $prefix = '')
	{
		uksort($data, function($a, $b) {
			$a_len = strlen($a);
			$b_len = strlen($b);

			$result = strncasecmp($a, $b, min($a_len, $b_len));

			if ($result === 0) {
				$result = $b_len - $a_len;
			}

			return $result;
		});

		$processed = [];

		foreach ($data as $key => $value) {
			if ($key === 'x_signature') continue;

			if (is_array($value)) {
				$processed[] = $this->buildSourceString($value, $key);
			} else {
				$processed[] = $prefix . $key . $value;
			}
		}

		return implode('|', $processed);
	}

	public function verifyXSignature($data, $hash)
	{
		$source_string = $this->buildSourceString($data);

		return hash_equals(hash_hmac('sha256', $source_string, $this->xsignature_key), $hash);
	}
}
?>