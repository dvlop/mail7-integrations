<?php

declare(strict_types=1);

namespace Mail7;

/**
 * Official Mail7 email validation client.
 *
 * Honest classification: `status` is "Valid" / "Not Valid" / "Unknown", and `valid` is
 * null when the address could not be verified (catch-all, greylisting, disposable).
 * Branch on `status`; do not treat Unknown as invalid.
 */
class Mail7
{
    /** @var string|null */
    private $apiKey;

    /** @var string */
    private $baseUrl;

    /** @var int */
    private $timeout;

    public function __construct(?string $apiKey = null, string $baseUrl = 'https://mail7.net/api', int $timeout = 20)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Validate a single email address.
     *
     * @return array<string,mixed> Result with keys: email, valid (bool|null), status
     *                             ("Valid"|"Not Valid"|"Unknown"), is_disposable, mx_servers, ...
     * @throws Mail7Exception on API or transport error.
     */
    public function validate(string $email): array
    {
        $headers = ['Content-Type: application/json'];
        if ($this->apiKey !== null && $this->apiKey !== '') {
            $headers[] = 'X-API-Key: ' . $this->apiKey;
        }

        $ch = curl_init($this->baseUrl . '/validate-single');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['email' => $email]),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);

        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Mail7Exception('Mail7 request failed: ' . $error);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($status !== 200) {
            throw new Mail7Exception(sprintf('Mail7 API error %d: %s', $status, (string) $body));
        }

        $data = json_decode((string) $body, true);
        if (!is_array($data)) {
            throw new Mail7Exception('Mail7 returned invalid JSON');
        }

        return $data;
    }
}
