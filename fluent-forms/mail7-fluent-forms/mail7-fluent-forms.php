<?php
/**
 * Plugin Name:       Mail7 Email Validation for Fluent Forms
 * Plugin URI:        https://mail7.net/integrations/fluent-forms/
 * Description:       Validate Fluent Forms email fields in real time via Mail7. Blocks only confirmed-invalid addresses and, unlike most validators, never rejects an address it cannot verify (honest Valid / Not Valid / Unknown). Fail-open on API errors.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Mail7
 * Author URI:        https://mail7.net/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mail7-fluent-forms
 *
 * @package Mail7FluentForms
 */

// No direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MAIL7_FF_VERSION', '1.0.0' );
define( 'MAIL7_FF_API_URL', 'https://mail7.net/api/validate-single' );
define( 'MAIL7_FF_OPTION', 'mail7_ff_settings' );

/**
 * Default settings.
 *
 * @return array
 */
function mail7_ff_defaults() {
	return array(
		'api_key'       => '',
		'block_unknown' => 0, // OFF: an address that cannot be verified (catch-all/greylist/
		                      // disposable) may still be a real person; blocking it rejects
		                      // genuine users. Honest default is to allow Unknown through.
	);
}

/**
 * Read merged settings.
 *
 * @return array
 */
function mail7_ff_get_settings() {
	$saved = get_option( MAIL7_FF_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return wp_parse_args( $saved, mail7_ff_defaults() );
}

/**
 * Call the Mail7 API for one address, with transient caching and fail-open.
 *
 * Any transport/API error returns 'error' (never blocks), so a Mail7 outage can
 * never lock users out of a form.
 *
 * @param string $email Address to check.
 * @return array{status:string,valid:?bool} status is Valid|Not Valid|Unknown|error.
 */
function mail7_ff_check( $email ) {
	$email = trim( (string) $email );
	if ( '' === $email ) {
		return array(
			'status' => 'error',
			'valid'  => null,
		);
	}

	$cache_key = 'mail7_ff_' . md5( strtolower( $email ) );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) && isset( $cached['status'] ) ) {
		return $cached;
	}

	$settings = mail7_ff_get_settings();
	$headers  = array( 'Content-Type' => 'application/json' );
	if ( ! empty( $settings['api_key'] ) ) {
		$headers['X-API-Key'] = $settings['api_key'];
	}

	$response = wp_remote_post(
		MAIL7_FF_API_URL,
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

/**
 * Validate a Fluent Forms email field on submission.
 *
 * Fluent Forms passes the current error for this field; return a non-empty array of
 * messages to reject, or the untouched $error to let it pass. Only confirmed
 * "Not Valid" addresses are blocked (plus "Unknown" if the admin opts in).
 *
 * @param mixed $error    Existing error for the field (string/array), empty when valid so far.
 * @param array $field    Field configuration.
 * @param array $formData All submitted form data.
 * @param array $fields   All form fields.
 * @param mixed $form      Form object.
 * @return mixed
 */
function mail7_ff_validate_email( $error, $field, $formData, $fields = array(), $form = null ) {
	// Respect an error already set by Fluent Forms (empty required, bad format, mismatch).
	if ( ! empty( $error ) ) {
		return $error;
	}

	$name = isset( $field['name'] ) ? $field['name'] : '';
	if ( '' === $name || empty( $formData[ $name ] ) ) {
		return $error;
	}

	$email = trim( (string) $formData[ $name ] );
	if ( '' === $email ) {
		return $error;
	}

	$check         = mail7_ff_check( $email );
	$settings      = mail7_ff_get_settings();
	$block_unknown = ! empty( $settings['block_unknown'] );

	if ( 'Not Valid' === $check['status'] ) {
		return array( esc_html__( 'This email address is not valid or does not exist.', 'mail7-fluent-forms' ) );
	}
	if ( $block_unknown && 'Unknown' === $check['status'] ) {
		return array( esc_html__( 'This email address could not be verified. Please use a different one.', 'mail7-fluent-forms' ) );
	}

	return $error;
}
add_filter( 'fluentform/validate_input_item_input_email', 'mail7_ff_validate_email', 20, 5 );

/* -------------------------------------------------------------------------
 * Settings page (Settings > Mail7 for Fluent Forms).
 * ---------------------------------------------------------------------- */

/**
 * Register the settings page.
 *
 * @return void
 */
function mail7_ff_admin_menu() {
	add_options_page(
		esc_html__( 'Mail7 for Fluent Forms', 'mail7-fluent-forms' ),
		esc_html__( 'Mail7 (Fluent Forms)', 'mail7-fluent-forms' ),
		'manage_options',
		'mail7-fluent-forms',
		'mail7_ff_settings_page'
	);
}
add_action( 'admin_menu', 'mail7_ff_admin_menu' );

/**
 * Register the setting and its sanitizer.
 *
 * @return void
 */
function mail7_ff_register_settings() {
	register_setting( 'mail7_ff_group', MAIL7_FF_OPTION, 'mail7_ff_sanitize' );
}
add_action( 'admin_init', 'mail7_ff_register_settings' );

/**
 * Sanitize submitted settings.
 *
 * @param array $input Raw input.
 * @return array
 */
function mail7_ff_sanitize( $input ) {
	return array(
		'api_key'       => isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '',
		'block_unknown' => empty( $input['block_unknown'] ) ? 0 : 1,
	);
}

/**
 * Render the settings page.
 *
 * @return void
 */
function mail7_ff_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s = mail7_ff_get_settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Mail7 Email Validation for Fluent Forms', 'mail7-fluent-forms' ); ?></h1>
		<p><?php esc_html_e( 'Email fields are validated on submit. Only confirmed-invalid addresses are blocked; Unknown addresses are allowed through by default.', 'mail7-fluent-forms' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'mail7_ff_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="mail7_ff_api_key"><?php esc_html_e( 'Mail7 API key', 'mail7-fluent-forms' ); ?></label></th>
					<td>
						<input name="<?php echo esc_attr( MAIL7_FF_OPTION ); ?>[api_key]" id="mail7_ff_api_key" type="text" class="regular-text" value="<?php echo esc_attr( $s['api_key'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Optional. Single checks work without a key; add one to lift the anonymous rate limit for busy forms.', 'mail7-fluent-forms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Strict mode', 'mail7-fluent-forms' ); ?></th>
					<td>
						<label>
							<input name="<?php echo esc_attr( MAIL7_FF_OPTION ); ?>[block_unknown]" type="checkbox" value="1" <?php checked( ! empty( $s['block_unknown'] ) ); ?> />
							<?php esc_html_e( 'Also block "Unknown" results', 'mail7-fluent-forms' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Off by default. Unknown means the mailbox could not be confirmed (catch-all, greylisting or disposable). It may still be a real person, so blocking it can reject genuine users.', 'mail7-fluent-forms' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
