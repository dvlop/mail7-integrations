<?php
/**
 * Uninstall cleanup for the Mail7 Gravity Forms add-on.
 *
 * @package Mail7GravityForms
 */

// Only run from the WordPress uninstall flow.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove the add-on settings stored by the Gravity Forms framework.
delete_option( 'gravityformsaddon_mail7-gravity-forms_settings' );
delete_option( 'gravityformsaddon_mail7-gravity-forms_version' );

// Best-effort: clear cached validation transients.
global $wpdb;
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mail7_gf_%' OR option_name LIKE '_transient_timeout_mail7_gf_%'"
);
