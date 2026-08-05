<?php
/**
 * Uninstall cleanup for the Mail7 Fluent Forms add-on.
 *
 * @package Mail7FluentForms
 */

// Only run from the WordPress uninstall flow.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'mail7_ff_settings' );

// Best-effort: clear cached validation transients.
global $wpdb;
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mail7_ff_%' OR option_name LIKE '_transient_timeout_mail7_ff_%'"
);
