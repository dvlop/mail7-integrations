<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * Mail7 Email Validation - MailWizz extension.
 *
 * Validates subscriber email addresses through the Mail7 API (mail7.net) and acts on the
 * result. Honest by design: only addresses Mail7 reports as **Not Valid** (do not exist /
 * no mail server) are acted on by default; **Unknown** addresses (catch-all, greylisting,
 * disposable) are left alone, so real people are never wrongly removed.
 *
 * Hooks the "list subscriber after save" action in each app so it covers the public
 * subscribe form, customer add/import, backend add and the API. On a Not Valid result the
 * subscriber is marked (unsubscribed by default, or blacklisted) so campaigns skip it.
 *
 * NOTE: MailWizz is commercial and could not be run to test this end to end. The structure
 * follows the documented extension API + a reference extension; verify on your install and
 * adjust the status constant / hook apps to your MailWizz version if needed.
 */
class Mail7Ext extends ExtensionInit
{
	public $name = 'Mail7 Email Validation';

	public $description = 'Validate subscriber emails via Mail7 - honest Valid / Not Valid / Unknown, so real people are never wrongly removed.';

	public $version = '0.1.0';

	public $minAppVersion = '1.9.0';

	public $author = 'Mail7';

	public $website = 'https://mail7.net/';

	public $email = 'support@mail7.net';

	// Run in every app so the after-save hook fires wherever a subscriber is created.
	public $allowedApps = array('backend', 'customer', 'frontend', 'api', 'console');

	public $cliEnabled = true;

	public function run()
	{
		// Bundled, dependency-free validator (cURL). Same logic as the Mail7 PHP SDK.
		require_once dirname(__FILE__) . '/common/Mail7EmailValidator.php';

		// Backend settings page.
		if ($this->isAppName('backend')) {
			$this->addUrlRules(array(
				array('settings/index', 'pattern' => 'extensions/mail7/settings'),
				array('settings/<action>', 'pattern' => 'extensions/mail7/settings/*'),
			));
			$this->addControllerMap(array(
				'settings' => array(
					'class' => 'ext-mail7.backend.controllers.Mail7ExtSettingsController',
				),
			));
		}

		if ($this->getOption('enabled', 'yes') !== 'yes') {
			return;
		}

		// Validate on subscriber save, in whichever app we are currently running.
		$app = $this->getCurrentAppName();
		if (in_array($app, array('frontend', 'customer', 'backend', 'api'), true)) {
			hooks()->addAction($app . '_model_listsubscriber_aftersave', array($this, '_validateSubscriber'));
		}
	}

	/**
	 * Fired after a subscriber row is saved. Validate the address and act on Not Valid
	 * (and optionally Unknown). Guarded against re-entry from our own save, and never
	 * allowed to break the subscriber flow.
	 *
	 * @param mixed $subscriber ListSubscriber model (or an event carrying ->sender/->params).
	 */
	public function _validateSubscriber($subscriber)
	{
		static $processed = array();

		try {
			// Some MailWizz builds pass the model, others a CEvent whose sender is the model.
			if (is_object($subscriber) && !isset($subscriber->email) && isset($subscriber->sender)) {
				$subscriber = $subscriber->sender;
			}
			if (!is_object($subscriber) || empty($subscriber->email)) {
				return;
			}

			$id = isset($subscriber->subscriber_id) ? (int)$subscriber->subscriber_id : 0;
			if ($id && isset($processed[$id])) {
				return; // already handled this request (avoid loops from our own save)
			}
			if ($id) {
				$processed[$id] = true;
			}

			// Do not re-touch already-excluded subscribers.
			$status = isset($subscriber->status) ? (string)$subscriber->status : '';
			if (in_array($status, array('unsubscribed', 'blacklisted', 'moved'), true)) {
				return;
			}

			$validator = new Mail7EmailValidator(
				(string)$this->getOption('api_key', ''),
				(string)$this->getOption('base_url', 'https://mail7.net/api')
			);
			$result = $validator->validate((string)$subscriber->email);
			$verdict = isset($result['status']) ? (string)$result['status'] : '';

			$blockInvalid = $this->getOption('block_invalid', 'yes') === 'yes';
			$blockUnknown = $this->getOption('block_unknown', 'no') === 'yes';

			$shouldBlock = ($verdict === 'Not Valid' && $blockInvalid)
				|| ($verdict === 'Unknown' && $blockUnknown);

			if (!$shouldBlock) {
				return; // Valid, or Unknown we chose to keep - honest default.
			}

			// Mark the subscriber so campaigns skip it. Default 'unsubscribed' (reversible);
			// 'blacklisted' hard-excludes. Save without validation to avoid a loop.
			$action = $this->getOption('action_on_invalid', 'unsubscribe');
			$newStatus = $action === 'blacklist' ? 'blacklisted' : 'unsubscribed';
			$subscriber->status = $newStatus;
			$subscriber->save(false);
		} catch (Exception $e) {
			// Fail open: never break subscribe/import because validation hiccuped.
			if (function_exists('logger')) {
				// best effort
			}
		}
	}
}
