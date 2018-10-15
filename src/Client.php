<?php
// A client for Billplz API

namespace apih\Billplz;

class Client
{
	const SANDBOX_URL = 'https://billplz-staging.herokuapp.com/api/v3/';
	const LIVE_URL = 'https://www.billplz.com/api/v3/';

	protected $sandbox = false;
	protected $secret_key;
	protected $xsignature_key;
	protected $url;
	protected $use_ssl = true;
	protected $last_error;

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
		$this->use_ssl = $flag;
	}

	// Error
	public function getLastError()
	{
		return $this->last_error;
	}

	protected function logError($function, $request, $response)
	{
		$this->last_error = [
			'function' => $function,
			'request' => $request,
			'response' => $response
		];

		$error_message = 'Billplz Error:' . PHP_EOL;
		$error_message .= 'function: ' . $function . PHP_EOL;
		$error_message .= 'request: ' . PHP_EOL;
		$error_message .= '-> url: ' . $request['url'] . PHP_EOL;
		$error_message .= '-> data: ' . json_encode($request['data']) . PHP_EOL;
		$error_message .= 'response: ' . PHP_EOL;
		$error_message .= '-> http_code: ' . $response['http_code'] . PHP_EOL;
		$error_message .= '-> body: ' . $response['body'] . PHP_EOL;

		error_log($error_message);
	}

	// curl
	protected function curlInit()
	{
		$this->last_error = null;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, $this->secret_key . ':');

		if ($this->use_ssl === false) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		}

		return $ch;
	}

	protected function curlExec($ch, $function, $url, $data = [])
	{
		$body = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		$decoded_body = json_decode($body, true);

		if ($http_code !== 200 || json_last_error() !== JSON_ERROR_NONE) {
			$this->logError(
				$function,
				compact('url', 'data'),
				compact('http_code', 'body')
			);

			return null;
		}

		return $decoded_body;
	}

	protected function curlGetRequest($function, $action)
	{
		$ch = $this->curlInit();

		curl_setopt($ch, CURLOPT_URL, $this->url . $action);

		return $this->curlExec($ch, $function, $this->url . $action);
	}

	protected function curlPostRequest($function, $action, $data = [])
	{
		$ch = $this->curlInit();

		curl_setopt($ch, CURLOPT_POST, true);

		if ($data) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		}

		curl_setopt($ch, CURLOPT_URL, $this->url . $action);

		return $this->curlExec($ch, $function, $this->url . $action, $data);
	}

	protected function curlDeleteRequest($function, $action)
	{
		$ch = $this->curlInit();

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($ch, CURLOPT_URL, $this->url . $action);

		return $this->curlExec($ch, $function, $this->url . $action, $data);
	}

	// Collection
	public function createCollection($data)
	{
		return $this->curlPostRequest(__FUNCTION__, 'collections', $data);
	}

	public function getCollection($collection_id)
	{
		return $this->curlGetRequest(__FUNCTION__, 'collections/' . $collection_id);
	}

	public function activateCollection()
	{
		return $this->curlPostRequest(__FUNCTION__, 'collections/' . $collection_id . '/activate');
	}

	public function deactivateCollection()
	{
		return $this->curlPostRequest(__FUNCTION__, 'collections/' . $collection_id . '/deactivate');
	}

	// Open collection
	public function createOpenCollection($data)
	{
		return $this->curlPostRequest(__FUNCTION__, 'open_collections', $data);
	}

	public function getOpenCollection($collection_id)
	{
		return $this->curlGetRequest(__FUNCTION__, 'open_collections/' . $collection_id);
	}

	// Bill
	public function createBill($data)
	{
		return $this->curlPostRequest(__FUNCTION__, 'bills', $data);
	}

	public function getBill($bill_id)
	{
		return $this->curlGetRequest(__FUNCTION__, 'bills/' . $bill_id);
	}

	public function deleteBill($bill_id)
	{
		return $this->curlDeleteRequest(__FUNCTION__, 'bills/' . $bill_id);
	}

	// FPX Banks
	public function getFpxBanks()
	{
		return $this->curlGetRequest(__FUNCTION__, 'fpx_banks');
	}
}
?>