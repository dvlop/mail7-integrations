<?php

/**
 * Mail7 Email Validation - phpList plugin.
 *
 * Validates subscriber email addresses through the Mail7 API (mail7.net) at the point
 * phpList checks an address (public subscribe form and import), by overriding the
 * validateEmailAddress() hook. Honest by design: only addresses Mail7 reports as
 * **Not Valid** (do not exist / no mail server) are rejected. **Unknown** addresses
 * (catch-all, greylisting, disposable) are accepted, so real people are never wrongly
 * rejected. Fails open if Mail7 is briefly unreachable.
 */
class Mail7Plugin extends phplistPlugin
{
    public $name = 'Mail7 Email Validation';

    public $version = '0.1.0';

    public $authors = 'Mail7';

    public $description = 'Validate subscriber emails via Mail7 - honest Valid / Not Valid / Unknown, so real people are never wrongly rejected.';

    public $documentationUrl = 'https://mail7.net/';

    /** Prepended with PLUGIN_ROOTDIR by the base constructor. */
    public $coderoot = 'Mail7Plugin/';

    /** Configuration items rendered on the phpList Settings page. */
    public $settings = array(
        'mail7_api_key' => array(
            'value' => '',
            'description' => 'Mail7 API key (optional). Raises rate limits and monthly volume. Leave empty to use the free anonymous tier. Get a key at mail7.net.',
            'type' => 'text',
            'allowempty' => true,
            'category' => 'Mail7',
        ),
        'mail7_base_url' => array(
            'value' => 'https://mail7.net/api',
            'description' => 'Mail7 API base URL.',
            'type' => 'text',
            'allowempty' => false,
            'category' => 'Mail7',
        ),
        'mail7_block_unknown' => array(
            'value' => false,
            'description' => 'Also reject Unknown addresses (catch-all, greylisting, disposable). Off is recommended - Unknown may still be a real person, and rejecting it risks turning away genuine subscribers.',
            'type' => 'boolean',
            'allowempty' => true,
            'category' => 'Mail7',
        ),
    );

    public function dependencyCheck()
    {
        return array(
            'phpList 3.3.0 or later' => version_compare(VERSION, '3.3.0') >= 0,
            'curl extension available' => function_exists('curl_init'),
        );
    }

    /**
     * Called by phpList when validating an email address (subscribe + import).
     *
     * @param string $emailAddress
     * @return bool false rejects the address; true accepts it
     */
    public function validateEmailAddress($emailAddress)
    {
        try {
            require_once $this->coderoot . 'Mail7EmailValidator.php';

            $baseUrl = (string) getConfig('mail7_base_url');
            if ($baseUrl === '') {
                $baseUrl = 'https://mail7.net/api';
            }
            $validator = new Mail7EmailValidator((string) getConfig('mail7_api_key'), $baseUrl);
            $result = $validator->validate((string) $emailAddress);
            $status = isset($result['status']) ? (string) $result['status'] : '';

            if ($status === 'Not Valid') {
                return false;
            }
            if ($status === 'Unknown' && getConfig('mail7_block_unknown')) {
                return false;
            }

            return true; // Valid, Unknown (kept), or an error (fail open)
        } catch (Exception $e) {
            return true; // never block subscribe/import because validation hiccuped
        }
    }
}
