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

			$bulk = new WPSchema_Bulk();
			$bulk->init();

			$updater = new WPSchema_GitHub_Updater();
			$updater->init();
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
		require_once $includes_dir . 'class-wpschema-bulk.php';
		require_once $includes_dir . 'class-wpschema-github-updater.php';

		// Schema type classes.
		$types_dir = $includes_dir . 'schema-types/';
		require_once $types_dir . 'class-wpschema-type-base.php';
		require_once $types_dir . 'class-wpschema-type-localbusiness.php';
		require_once $types_dir . 'class-wpschema-type-person.php';
		require_once $types_dir . 'class-wpschema-type-organisation.php';
		require_once $types_dir . 'class-wpschema-type-website.php';
		require_once $types_dir . 'class-wpschema-type-webpage.php';
		require_once $types_dir . 'class-wpschema-type-service.php';
		require_once $types_dir . 'class-wpschema-type-professionalservice.php';
		require_once $types_dir . 'class-wpschema-type-faqpage.php';
		require_once $types_dir . 'class-wpschema-type-breadcrumblist.php';
		require_once $types_dir . 'class-wpschema-type-product.php';
		require_once $types_dir . 'class-wpschema-type-offer.php';
	}
}
