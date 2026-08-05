<?php
/**
 * Plugin Name:       Mail7 Email Validation for Gravity Forms
 * Plugin URI:        https://mail7.net/integrations/gravity-forms/
 * Description:       Validate Gravity Forms email fields in real time via Mail7. Blocks only confirmed-invalid addresses and, unlike most validators, never rejects an address it cannot verify (honest Valid / Not Valid / Unknown). Fail-open on API errors.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Mail7
 * Author URI:        https://mail7.net/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mail7-gravity-forms
 *
 * @package Mail7GravityForms
 */

// No direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MAIL7_GF_VERSION', '1.0.0' );
define( 'MAIL7_GF_API_URL', 'https://mail7.net/api/validate-single' );

/**
 * Load the add-on once Gravity Forms itself has loaded. If Gravity Forms is not
 * active, the add-on stays dormant (no fatals).
 */
add_action( 'gform_loaded', array( 'Mail7_GF_Bootstrap', 'load' ), 5 );

/**
 * Bootstrap: register the add-on with the Gravity Forms Add-On Framework.
 */
class Mail7_GF_Bootstrap {

	/**
	 * Register the add-on if the framework is available.
	 *
	 * @return void
	 */
	public static function load() {
		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}
		require_once plugin_dir_path( __FILE__ ) . 'class-gf-mail7.php';
		GFAddOn::register( 'GF_Mail7' );
	}
}

/**
 * Convenience accessor.
 *
 * @return GF_Mail7
 */
function gf_mail7() {
	return GF_Mail7::get_instance();
}
