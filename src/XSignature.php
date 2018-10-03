<?php
// Billplz XSignature

namespace apih\Billplz;

class XSignature
{
	protected $key;

	public function __construct($key)
	{
		$this->key = $key;
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

	public function verify($data, $hash)
	{
		$source_string = $this->buildSourceString($data);

		return hash_equals(hash_hmac('sha256', $source_string, $this->key), $hash);
	}
}
?>