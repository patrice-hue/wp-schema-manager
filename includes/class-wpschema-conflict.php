<?php
/**
 * Conflict detection for schema output.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detects if other active plugins are likely outputting structured data,
 * and displays a warning notice in the admin.
 */
class WPSchema_Conflict {

	/**
	 * Known plugins that output schema/structured data.
	 *
	 * @var array<string, string>
	 */
	private const KNOWN_SCHEMA_PLUGINS = array(
		'wordpress-seo/wp-seo.php'                                     => 'Yoast SEO',
		'all-in-one-seo-pack/all_in_one_seo_pack.php'                  => 'All in One SEO',
		'schema/schema.php'                                            => 'Schema',
		'schema-and-structured-data-for-wp/structured-data-for-wp.php' => 'Schema & Structured Data for WP',
		'wp-seo-structured-data-schema/developer-schema.php'           => 'WP SEO Structured Data Schema',
		'rank-math-seo/rank-math.php'                                  => 'Rank Math SEO',
		'seo-by-rank-math/rank-math.php'                               => 'Rank Math SEO',
		'jeremynouvelles-seo/jeremynouvelles-seo.php'                  => 'Jeremynouvelles SEO',
		'jeremynouvelles-seo-pack/jeremynouvelles-seo-pack.php'        => 'Jeremynouvelles SEO Pack',
	);

	/**
	 * Hook into WordPress.
	 */
	public function init(): void {
		add_action( 'admin_notices', array( $this, 'check_conflicts' ) );
	}

	/**
	 * Check for conflicting plugins and display admin notice.
	 */
	public function check_conflicts(): void {
		// Only show on our settings page and the plugins page.
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'settings_page_wpschema-settings', 'plugins' ), true ) ) {
			return;
		}

		$conflicts = $this->detect_conflicts();

		if ( empty( $conflicts ) ) {
			return;
		}

		$plugin_list = implode( ', ', array_map( static function ( string $plugin ): string {
			return '<strong>' . esc_html( $plugin ) . '</strong>';
		}, $conflicts ) );

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			sprintf(
				/* translators: %s: comma-separated list of plugin names */
				wp_kses(
					__( '<strong>WP Schema Manager — Conflict Detected:</strong> The following active plugin(s) may also be outputting structured data: %s. This could result in duplicate or conflicting schema markup. Consider disabling schema output in those plugins.', 'wp-schema-manager' ),
					array( 'strong' => array() )
				),
				$plugin_list // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			)
		);
	}

	/**
	 * Detect active plugins that are known to output schema.
	 *
	 * @return string[] List of conflicting plugin names.
	 */
	private function detect_conflicts(): array {
		// Use a transient to avoid checking on every page load.
		$cached = get_transient( 'wpschema_conflict_check' );
		if ( false !== $cached ) {
			return $cached;
		}

		$conflicts      = array();
		$active_plugins = get_option( 'active_plugins', array() );

		foreach ( $active_plugins as $plugin ) {
			if ( isset( self::KNOWN_SCHEMA_PLUGINS[ $plugin ] ) ) {
				$conflicts[] = self::KNOWN_SCHEMA_PLUGINS[ $plugin ];
			}
		}

		// Deduplicate in case multiple paths map to same plugin name.
		$conflicts = array_unique( $conflicts );

		// Cache for 1 hour.
		set_transient( 'wpschema_conflict_check', $conflicts, HOUR_IN_SECONDS );

		return $conflicts;
	}
}
