<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * Backend settings page for the Mail7 Email Validation extension.
 *
 * Reads/writes the extension options via getOption()/setOption(). Follows the MailWizz 2.x
 * ExtensionController pattern; on older builds adjust the base class / helpers to match.
 */
class Mail7ExtSettingsController extends ExtensionController
{
	public function actionIndex()
	{
		$extension = $this->getExtension();
		$request = request();
		$notify = notify();

		if ($request->getIsPostRequest()) {
			$post = (array)$request->getPost('settings', array());

			$extension->setOption('enabled', !empty($post['enabled']) ? 'yes' : 'no');
			$extension->setOption('api_key', isset($post['api_key']) ? trim((string)$post['api_key']) : '');
			$baseUrl = isset($post['base_url']) ? trim((string)$post['base_url']) : '';
			$extension->setOption('base_url', $baseUrl !== '' ? rtrim($baseUrl, '/') : 'https://mail7.net/api');
			$extension->setOption('block_invalid', !empty($post['block_invalid']) ? 'yes' : 'no');
			$extension->setOption('block_unknown', !empty($post['block_unknown']) ? 'yes' : 'no');
			$action = isset($post['action_on_invalid']) && $post['action_on_invalid'] === 'blacklist' ? 'blacklist' : 'unsubscribe';
			$extension->setOption('action_on_invalid', $action);

			$notify->addSuccess('Your Mail7 settings have been saved.');
			$this->redirect(array('settings/index'));
		}

		$data = array(
			'enabled' => $extension->getOption('enabled', 'yes'),
			'api_key' => $extension->getOption('api_key', ''),
			'base_url' => $extension->getOption('base_url', 'https://mail7.net/api'),
			'block_invalid' => $extension->getOption('block_invalid', 'yes'),
			'block_unknown' => $extension->getOption('block_unknown', 'no'),
			'action_on_invalid' => $extension->getOption('action_on_invalid', 'unsubscribe'),
		);

		$this->render('index', array('data' => $data));
	}
}
