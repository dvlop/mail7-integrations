<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * Minimal, dependency-free Mail7 API client (same logic as the official Mail7 PHP SDK,
 * inlined here so the extension has no Composer dependency). Plain class name, no
 * namespace, to stay out of MailWizz's autoloader's way.
 */
class Mail7EmailValidator
{
	/** @var string */
	private $apiKey;

	/** @var string */
	private $baseUrl;

	/** @var int */
	private $timeout;

	public function __construct($apiKey = '', $baseUrl = 'https://mail7.net/api', $timeout = 15)
	{
		$this->apiKey = (string)$apiKey;
		$this->baseUrl = rtrim((string)$baseUrl, '/');
		$this->timeout = (int)$timeout;
	}

	/**
	 * Validate a single email address.
	 *
	 * @param string $email
	 * @return array Result with keys: status ("Valid"|"Not Valid"|"Unknown"), valid
	 *               (bool|null), is_disposable, ... On any transport/API error returns an
	 *               empty array so the caller can fail open.
	 */
	public function validate($email)
	{
		$email = trim((string)$email);
		if ($email === '') {
			return array();
		}

		$headers = array('Content-Type: application/json');
		if ($this->apiKey !== '') {
			$headers[] = 'X-API-Key: ' . $this->apiKey;
		}

		$ch = curl_init($this->baseUrl . '/validate-single');
		curl_setopt_array($ch, array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode(array('email' => $email)),
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => $this->timeout,
		));
		$body = curl_exec($ch);
		$status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		curl_close($ch);

		if ($body === false || $status !== 200) {
			return array(); // fail open
		}

		$data = json_decode((string)$body, true);
		return is_array($data) ? $data : array();
	}
}
