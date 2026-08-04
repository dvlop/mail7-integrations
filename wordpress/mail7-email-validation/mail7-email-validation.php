<?php
/**
 * Plugin Name:       Mail7 Email Validation
 * Plugin URI:        https://mail7.net/
 * Description:       Real-time email validation for registration, comments and forms (Contact Form 7, WPForms) via the Mail7 API. Blocks fake and undeliverable addresses - and, unlike most validators, never blocks an address it cannot verify (honest Valid / Not Valid / Unknown).
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Mail7
 * Author URI:        https://mail7.net/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mail7-email-validation
 *
 * @package Mail7EmailValidation
 */

// No direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MAIL7_EV_VERSION', '1.0.0' );
define( 'MAIL7_EV_API_URL', 'https://mail7.net/api/validate-single' );
define( 'MAIL7_EV_OPTION', 'mail7_ev_settings' );

/**
 * Default settings.
 *
 * @return array
 */
function mail7_ev_defaults() {
	return array(
		'api_key'         => '',
		'block_invalid'   => 1, // Block addresses Mail7 reports as "Not Valid".
		'block_unknown'   => 0, // Block "Unknown" too. OFF by default - honest: an address
		                        // that cannot be verified (catch-all/greylist/disposable) may
		                        // still be a real person; blocking it creates false negatives.
		'on_registration' => 1,
		'on_comments'     => 1,
		'on_cf7'          => 1,
		'on_wpforms'      => 1,
	);
}

/**
 * Read merged settings.
 *
 * @return array
 */
function mail7_ev_get_settings() {
	$saved = get_option( MAIL7_EV_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return wp_parse_args( $saved, mail7_ev_defaults() );
}

/**
 * Call the Mail7 API for one address.
 *
 * Result is cached in a transient (keyed by a hash of the address) so a repeated
 * address is not re-checked - matching Mail7's "duplicates are not charged" pricing
 * and keeping form submission fast. Any transport/API error fails OPEN (returns
 * 'error') so a Mail7 outage can never lock users out of a form.
 *
 * @param string $email Address to check.
 * @return array{status:string,valid:?bool} status is Valid|Not Valid|Unknown|error.
 */
function mail7_ev_validate( $email ) {
	$email = trim( (string) $email );
	if ( '' === $email ) {
		return array(
			'status' => 'error',
			'valid'  => null,
		);
	}

	$cache_key = 'mail7_ev_' . md5( strtolower( $email ) );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) && isset( $cached['status'] ) ) {
		return $cached;
	}

	$settings = mail7_ev_get_settings();
	$headers  = array( 'Content-Type' => 'application/json' );
	if ( ! empty( $settings['api_key'] ) ) {
		$headers['X-API-Key'] = $settings['api_key'];
	}

	$response = wp_remote_post(
		MAIL7_EV_API_URL,
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

	$code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== (int) $code ) {
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

	$result = array(
		'status' => (string) $data['status'],
		'valid'  => isset( $data['valid'] ) ? $data['valid'] : null,
	);

	// Cache only definite results; leave Unknown/error uncached so they can resolve later.
	if ( in_array( $result['status'], array( 'Valid', 'Not Valid' ), true ) ) {
		set_transient( $cache_key, $result, 12 * HOUR_IN_SECONDS );
	}

	return $result;
}

/**
 * Decide whether an address should be rejected, per settings.
 *
 * @param string $email Address.
 * @return bool True = reject the submission.
 */
function mail7_ev_should_reject( $email ) {
	$settings = mail7_ev_get_settings();
	$result   = mail7_ev_validate( $email );

	if ( 'error' === $result['status'] ) {
		return false; // Fail open.
	}
	if ( 'Not Valid' === $result['status'] ) {
		return ! empty( $settings['block_invalid'] );
	}
	if ( 'Unknown' === $result['status'] ) {
		return ! empty( $settings['block_unknown'] );
	}
	return false; // Valid.
}

/**
 * User-facing rejection message.
 *
 * @return string
 */
function mail7_ev_reject_message() {
	return __( 'Please enter a valid email address that can receive mail.', 'mail7-email-validation' );
}

/* -------------------------------------------------------------------------
 * Integration: WordPress user registration.
 * ---------------------------------------------------------------------- */
add_filter(
	'registration_errors',
	function ( $errors, $sanitized_user_login, $user_email ) {
		$settings = mail7_ev_get_settings();
		if ( empty( $settings['on_registration'] ) ) {
			return $errors;
		}
		if ( $user_email && mail7_ev_should_reject( $user_email ) ) {
			$errors->add( 'mail7_ev_invalid_email', mail7_ev_reject_message() );
		}
		return $errors;
	},
	10,
	3
);

/* -------------------------------------------------------------------------
 * Integration: comment author email.
 * ---------------------------------------------------------------------- */
add_filter(
	'preprocess_comment',
	function ( $commentdata ) {
		$settings = mail7_ev_get_settings();
		if ( empty( $settings['on_comments'] ) ) {
			return $commentdata;
		}
		// Skip logged-in users (their email is already trusted).
		if ( is_user_logged_in() ) {
			return $commentdata;
		}
		$email = isset( $commentdata['comment_author_email'] ) ? $commentdata['comment_author_email'] : '';
		if ( $email && mail7_ev_should_reject( $email ) ) {
			wp_die(
				esc_html( mail7_ev_reject_message() ),
				esc_html__( 'Invalid email', 'mail7-email-validation' ),
				array( 'back_link' => true )
			);
		}
		return $commentdata;
	}
);

/* -------------------------------------------------------------------------
 * Integration: Contact Form 7 (email fields).
 * ---------------------------------------------------------------------- */
add_filter( 'wpcf7_validate_email', 'mail7_ev_cf7_validate', 20, 2 );
add_filter( 'wpcf7_validate_email*', 'mail7_ev_cf7_validate', 20, 2 );

/**
 * Contact Form 7 email validation.
 *
 * @param WPCF7_Validation $result Validation result object.
 * @param WPCF7_FormTag    $tag    The form tag being validated.
 * @return WPCF7_Validation
 */
function mail7_ev_cf7_validate( $result, $tag ) {
	$settings = mail7_ev_get_settings();
	if ( empty( $settings['on_cf7'] ) ) {
		return $result;
	}
	$name  = ( is_object( $tag ) && isset( $tag->name ) ) ? $tag->name : '';
	$value = isset( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- CF7 handles the form nonce.
	if ( $value && mail7_ev_should_reject( $value ) ) {
		$result->invalidate( $tag, mail7_ev_reject_message() );
	}
	return $result;
}

/* -------------------------------------------------------------------------
 * Integration: WPForms (email fields).
 * ---------------------------------------------------------------------- */
add_action(
	'wpforms_process_validate_email',
	function ( $field_id, $field_submit, $form_data ) {
		$settings = mail7_ev_get_settings();
		if ( empty( $settings['on_wpforms'] ) ) {
			return;
		}
		$email = is_array( $field_submit ) ? ( isset( $field_submit['primary'] ) ? $field_submit['primary'] : '' ) : $field_submit;
		$email = sanitize_text_field( (string) $email );
		if ( $email && mail7_ev_should_reject( $email ) ) {
			wpforms()->process->errors[ $form_data['id'] ][ $field_id ] = mail7_ev_reject_message();
		}
	},
	20,
	3
);

/* -------------------------------------------------------------------------
 * Admin settings page.
 * ---------------------------------------------------------------------- */
add_action(
	'admin_menu',
	function () {
		add_options_page(
			__( 'Mail7 Email Validation', 'mail7-email-validation' ),
			__( 'Mail7 Validation', 'mail7-email-validation' ),
			'manage_options',
			'mail7-email-validation',
			'mail7_ev_render_settings'
		);
	}
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'mail7_ev_group',
			MAIL7_EV_OPTION,
			array( 'sanitize_callback' => 'mail7_ev_sanitize_settings' )
		);
	}
);

/**
 * Sanitize + validate settings on save.
 *
 * @param array $input Raw input.
 * @return array
 */
function mail7_ev_sanitize_settings( $input ) {
	$out                    = mail7_ev_defaults();
	$out['api_key']         = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '';
	$out['block_invalid']   = empty( $input['block_invalid'] ) ? 0 : 1;
	$out['block_unknown']   = empty( $input['block_unknown'] ) ? 0 : 1;
	$out['on_registration'] = empty( $input['on_registration'] ) ? 0 : 1;
	$out['on_comments']     = empty( $input['on_comments'] ) ? 0 : 1;
	$out['on_cf7']          = empty( $input['on_cf7'] ) ? 0 : 1;
	$out['on_wpforms']      = empty( $input['on_wpforms'] ) ? 0 : 1;
	return $out;
}

/**
 * Render the settings page.
 */
function mail7_ev_render_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s = mail7_ev_get_settings();
	$cb = function ( $key, $label, $desc = '' ) use ( $s ) {
		printf(
			'<tr><th scope="row">%s</th><td><label><input type="checkbox" name="%s[%s]" value="1" %s> %s</label>%s</td></tr>',
			esc_html( $label ),
			esc_attr( MAIL7_EV_OPTION ),
			esc_attr( $key ),
			checked( ! empty( $s[ $key ] ), true, false ),
			esc_html__( 'Enabled', 'mail7-email-validation' ),
			$desc ? '<p class="description">' . esc_html( $desc ) . '</p>' : ''
		);
	};
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Mail7 Email Validation', 'mail7-email-validation' ); ?></h1>
		<p><?php esc_html_e( 'Real-time email validation via the Mail7 API. Honest by design: an address that cannot be verified is reported as Unknown and is NOT blocked by default, so real people are never turned away.', 'mail7-email-validation' ); ?>
			<a href="https://mail7.net/" target="_blank" rel="noopener">mail7.net</a></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'mail7_ev_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="mail7_ev_api_key"><?php esc_html_e( 'API key (optional)', 'mail7-email-validation' ); ?></label></th>
					<td>
						<input name="<?php echo esc_attr( MAIL7_EV_OPTION ); ?>[api_key]" id="mail7_ev_api_key" type="text" class="regular-text" value="<?php echo esc_attr( $s['api_key'] ); ?>" autocomplete="off">
						<p class="description"><?php esc_html_e( 'Leave empty to use the free anonymous tier (rate-limited). Paste a Mail7 API key for higher limits and volume.', 'mail7-email-validation' ); ?></p>
					</td>
				</tr>
				<?php
				$cb( 'block_invalid', __( 'Block invalid addresses', 'mail7-email-validation' ), __( 'Reject addresses Mail7 reports as Not Valid (does not exist / no mail server).', 'mail7-email-validation' ) );
				$cb( 'block_unknown', __( 'Also block unverifiable', 'mail7-email-validation' ), __( 'Off is recommended: Unknown means the address may still be real (catch-all, greylisting, disposable). Blocking it risks turning away genuine users.', 'mail7-email-validation' ) );
				$cb( 'on_registration', __( 'Check on user registration', 'mail7-email-validation' ) );
				$cb( 'on_comments', __( 'Check on comments', 'mail7-email-validation' ) );
				$cb( 'on_cf7', __( 'Check Contact Form 7 fields', 'mail7-email-validation' ) );
				$cb( 'on_wpforms', __( 'Check WPForms fields', 'mail7-email-validation' ) );
				?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

