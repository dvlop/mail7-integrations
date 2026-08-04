<?php

declare(strict_types=1);

namespace MauticPlugin\MauticMail7Bundle\Helper;

/**
 * Minimal, dependency-free Mail7 API client (same logic as the official Mail7 PHP SDK).
 */
class Mail7Client
{
    private string $apiKey;

    private string $baseUrl;

    private int $timeout;

    public function __construct(string $apiKey = '', string $baseUrl = 'https://mail7.net/api', int $timeout = 15)
    {
        $this->apiKey  = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Validate a single email address.
     *
     * @return array<string,mixed> Result with keys status ("Valid"|"Not Valid"|"Unknown"),
     *                             valid (bool|null), is_disposable, ... Empty array on any
     *                             transport/API error so the caller can fail open.
     */
    public function validate(string $email): array
    {
        $email = trim($email);
        if ('' === $email) {
            return [];
        }

        $headers = ['Content-Type: application/json'];
        if ('' !== $this->apiKey) {
            $headers[] = 'X-API-Key: ' . $this->apiKey;
        }

        $ch = curl_init($this->baseUrl . '/validate-single');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['email' => $email]),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
        ]);
        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if (false === $body || 200 !== $status) {
            return [];
        }

        $data = json_decode((string) $body, true);

        return is_array($data) ? $data : [];
    }
}
