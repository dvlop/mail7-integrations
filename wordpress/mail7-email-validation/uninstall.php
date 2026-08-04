<?php
/**
 * Uninstall cleanup for Mail7 Email Validation.
 *
 * Removes the plugin's stored settings and any cached validation transients.
 *
 * @package Mail7EmailValidation
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'mail7_ev_settings' );

// Remove cached per-address results (transients are named mail7_ev_<hash>).
global $wpdb;
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_mail7\\_ev\\_%' OR option_name LIKE '\\_transient\\_timeout\\_mail7\\_ev\\_%'"
);
