<?php
/**
 * Plugin uninstall handler.
 *
 * Fires when the plugin is deleted via the WordPress admin.
 * Cleans up all plugin data from the database.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin options.
delete_option( 'wpschema_settings' );

// Remove transients.
delete_transient( 'wpschema_conflict_check' );

// Remove all post meta created by the plugin.
global $wpdb;

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
		'_wpschema_%'
	)
);
