<?php
/**
 * Plugin loader and bootstrapper.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main loader class that initialises all plugin components.
 */
class WPSchema_Loader {

	/**
	 * Initialise the plugin components.
	 */
	public function init(): void {
		$this->load_dependencies();

		if ( is_admin() ) {
			$admin = new WPSchema_Admin();
			$admin->init();

			$metabox = new WPSchema_Metabox();
			$metabox->init();

			$preview = new WPSchema_Preview();
			$preview->init();

			$conflict = new WPSchema_Conflict();
			$conflict->init();
		}

		$output = new WPSchema_Output();
		$output->init();
	}

	/**
	 * Load all required class files.
	 */
	private function load_dependencies(): void {
		$includes_dir = WPSCHEMA_PLUGIN_DIR . 'includes/';

		require_once $includes_dir . 'class-wpschema-admin.php';
		require_once $includes_dir . 'class-wpschema-metabox.php';
		require_once $includes_dir . 'class-wpschema-output.php';
		require_once $includes_dir . 'class-wpschema-conflict.php';
		require_once $includes_dir . 'class-wpschema-preview.php';

		// Schema type classes.
		$types_dir = $includes_dir . 'schema-types/';
		require_once $types_dir . 'class-wpschema-type-base.php';
		require_once $types_dir . 'class-wpschema-type-localbusiness.php';
		require_once $types_dir . 'class-wpschema-type-person.php';
		require_once $types_dir . 'class-wpschema-type-organisation.php';
		require_once $types_dir . 'class-wpschema-type-website.php';
		require_once $types_dir . 'class-wpschema-type-webpage.php';
	}
}
