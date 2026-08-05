<?php
/**
 * Gravity Forms Add-On: Mail7 email validation.
 *
 * @package Mail7GravityForms
 */

// No direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

GFForms::include_addon_framework();

/**
 * Validates Gravity Forms email fields against the Mail7 API.
 */
class GF_Mail7 extends GFAddOn {

	protected $_version                  = MAIL7_GF_VERSION;
	protected $_min_gravityforms_version = '2.4';
	protected $_slug                     = 'mail7-gravity-forms';
	protected $_path                     = 'mail7-gravity-forms/mail7-gravity-forms.php';
	protected $_full_path                = __FILE__;
	protected $_url                       = 'https://mail7.net/integrations/gravity-forms/';
	protected $_title                    = 'Mail7 Email Validation';
	protected $_short_title              = 'Mail7';

	/**
	 * Singleton instance.
	 *
	 * @var GF_Mail7|null
	 */
	private static $_instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return GF_Mail7
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Hook the field validation filter once the add-on initialises.
	 *
	 * @return void
	 */
	public function init() {
		parent::init();
		add_filter( 'gform_field_validation', array( $this, 'validate_field' ), 10, 4 );
	}

	/**
	 * Add-on settings (Forms > Settings > Mail7).
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'  => esc_html__( 'Mail7 Email Validation', 'mail7-gravity-forms' ),
				'fields' => array(
					array(
						'name'    => 'api_key',
						'label'   => esc_html__( 'Mail7 API key', 'mail7-gravity-forms' ),
						'type'    => 'text',
						'class'   => 'medium',
						'tooltip' => esc_html__( 'Optional. Single checks work without a key; add one to lift the anonymous rate limit for busy forms.', 'mail7-gravity-forms' ),
					),
					array(
						'name'          => 'block_unknown',
						'label'         => esc_html__( 'Also block "Unknown" results', 'mail7-gravity-forms' ),
						'type'          => 'toggle',
						'default_value' => 0,
						'tooltip'       => esc_html__( 'Off by default. Unknown means the mailbox could not be confirmed (catch-all, greylisting or disposable). It may still be a real person, so blocking it can reject genuine users.', 'mail7-gravity-forms' ),
					),
				),
			),
		);
	}

	/**
	 * Validate an email field on submission.
	 *
	 * Only confirmed "Not Valid" addresses are blocked (plus "Unknown" if the admin
	 * opts in). Any API/transport error fails OPEN so a Mail7 outage never locks a
	 * user out of the form.
	 *
	 * @param array $result Validation result ('is_valid', 'message').
	 * @param mixed $value  Submitted value (array when the email field has confirmation on).
	 * @param array $form   Form object.
	 * @param object $field Field object.
	 * @return array
	 */
	public function validate_field( $result, $value, $form, $field ) {
		// Only email fields.
		if ( ! is_object( $field ) || 'email' !== $field->get_input_type() ) {
			return $result;
		}
		// Respect an already-failed validation (empty required, bad format, mismatch).
		if ( empty( $result['is_valid'] ) ) {
			return $result;
		}

		// Confirmation-enabled email fields submit an array; take the primary address.
		$email = is_array( $value ) ? reset( $value ) : $value;
		$email = trim( (string) $email );
		if ( '' === $email ) {
			return $result;
		}

		$check         = $this->check_email( $email );
		$block_unknown = (bool) $this->get_plugin_setting( 'block_unknown' );

		$reject = ( 'Not Valid' === $check['status'] )
			|| ( $block_unknown && 'Unknown' === $check['status'] );

		if ( $reject ) {
			$result['is_valid'] = false;
			$result['message']  = ( 'Not Valid' === $check['status'] )
				? esc_html__( 'This email address is not valid or does not exist.', 'mail7-gravity-forms' )
				: esc_html__( 'This email address could not be verified. Please use a different one.', 'mail7-gravity-forms' );
		}

		return $result;
	}

	/**
	 * Call the Mail7 API for one address, with transient caching and fail-open.
	 *
	 * @param string $email Address to check.
	 * @return array{status:string,valid:?bool} status is Valid|Not Valid|Unknown|error.
	 */
	private function check_email( $email ) {
		$cache_key = 'mail7_gf_' . md5( strtolower( $email ) );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) && isset( $cached['status'] ) ) {
			return $cached;
		}

		$headers = array( 'Content-Type' => 'application/json' );
		$api_key = (string) $this->get_plugin_setting( 'api_key' );
		if ( '' !== $api_key ) {
			$headers['X-API-Key'] = $api_key;
		}

		$response = wp_remote_post(
			MAIL7_GF_API_URL,
			array(
				'timeout' => 15,
				'headers' => $headers,
				'body'    => wp_json_encode( array( 'email' => $email ) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'status' => 'error',
				'valid'  => null,
			);
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			// 429 (rate limit), 4xx, 5xx - fail open, do not cache.
			return array(
				'status' => 'error',
				'valid'  => null,
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || ! isset( $data['status'] ) ) {
			return array(
				'status' => 'error',
				'valid'  => null,
			);
		}

		$out = array(
			'status' => (string) $data['status'],
			'valid'  => isset( $data['valid'] ) ? $data['valid'] : null,
		);

		// Cache only definite results; leave Unknown/error uncached so they can resolve later.
		if ( in_array( $out['status'], array( 'Valid', 'Not Valid' ), true ) ) {
			set_transient( $cache_key, $out, 12 * HOUR_IN_SECONDS );
		}

		return $out;
	}
}
