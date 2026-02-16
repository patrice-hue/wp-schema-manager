<?php
/**
 * Plugin Name: WP Schema Manager
 * Plugin URI:  https://github.com/patrice-hue/wp-schema-manager
 * Description: A lightweight, developer-friendly WordPress plugin for managing JSON-LD structured data. Built for technical SEOs and developers who need precise, conflict-free schema output.
 * Version:     0.2.0
 * Author:      Patrice Cognard
 * Author URI:  https://www.digitalhitmen.com.au/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-schema-manager
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPSCHEMA_VERSION', '0.2.0' );
define( 'WPSCHEMA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPSCHEMA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSCHEMA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once WPSCHEMA_PLUGIN_DIR . 'includes/class-wpschema-loader.php';

/**
 * Initialise the plugin.
 *
 * @return WPSchema_Loader
 */
function wpschema_init(): WPSchema_Loader {
	static $instance = null;

	if ( null === $instance ) {
		$instance = new WPSchema_Loader();
		$instance->init();
	}

	return $instance;
}

add_action( 'plugins_loaded', 'wpschema_init' );

register_activation_hook( __FILE__, 'wpschema_activate' );
register_deactivation_hook( __FILE__, 'wpschema_deactivate' );

/**
 * Plugin activation callback.
 */
function wpschema_activate(): void {
	$defaults = array(
		'enabled'            => true,
		'schema_type'        => 'Organization',
		'org_name'           => get_bloginfo( 'name' ),
		'org_url'            => home_url(),
		'org_logo'           => '',
		'org_phone'          => '',
		'org_email'          => get_option( 'admin_email' ),
		'org_street'         => '',
		'org_locality'       => '',
		'org_region'         => '',
		'org_postal_code'    => '',
		'org_country'        => '',
		'website_schema'      => true,
		'enabled_post_types'  => array( 'post', 'page' ),
		'service_name'        => '',
		'service_description' => '',
		'service_url'         => '',
		'service_type'        => '',
		'service_area'        => '',
		'breadcrumb_enabled'  => false,
	);

	if ( ! get_option( 'wpschema_settings' ) ) {
		add_option( 'wpschema_settings', $defaults );
	}
}

/**
 * Plugin deactivation callback.
 */
function wpschema_deactivate(): void {
	// Clean up transients.
	delete_transient( 'wpschema_conflict_check' );
	delete_transient( 'wpschema_github_update' );
}
