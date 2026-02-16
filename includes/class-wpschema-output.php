<?php
/**
 * Frontend JSON-LD output handler.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects JSON-LD structured data into the <head> section.
 */
class WPSchema_Output {

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private array $settings;

	/**
	 * Hook into WordPress.
	 */
	public function init(): void {
		add_action( 'wp_head', array( $this, 'output_schema' ), 1 );
	}

	/**
	 * Output JSON-LD schema in the document head.
	 */
	public function output_schema(): void {
		$this->settings = $this->get_settings();

		if ( empty( $this->settings['enabled'] ) ) {
			return;
		}

		$schemas = array();

		// Site-wide WebSite schema.
		if ( ! empty( $this->settings['website_schema'] ) ) {
			$website  = new WPSchema_Type_WebSite( $this->settings );
			$schemas[] = $website->build();
		}

		// Context-specific schema.
		if ( is_singular() ) {
			$post_id = get_the_ID();

			if ( $post_id ) {
				$schemas = array_merge( $schemas, $this->get_singular_schemas( $post_id ) );
			}
		} else {
			// Non-singular pages get the default entity schema.
			$entity_schema = $this->build_default_entity_schema();
			if ( $entity_schema ) {
				$schemas[] = $entity_schema;
			}
		}

		/**
		 * Filter the complete schema output before rendering.
		 *
		 * @param array    $schemas  Array of schema arrays.
		 * @param int|null $post_id  Current post ID or null.
		 */
		$post_id = is_singular() ? get_the_ID() : null;
		$schemas = apply_filters( 'wpschema_output', $schemas, $post_id );

		foreach ( $schemas as $schema ) {
			if ( ! empty( $schema ) ) {
				$this->render_json_ld( $schema );
			}
		}
	}

	/**
	 * Get schemas for a singular post/page.
	 *
	 * @param int $post_id The post ID.
	 * @return array Array of schema arrays.
	 */
	private function get_singular_schemas( int $post_id ): array {
		$schemas = array();

		// Check if schema is enabled for this post type.
		$post_type      = get_post_type( $post_id );
		$enabled_types  = $this->settings['enabled_post_types'] ?? array( 'post', 'page' );

		if ( ! in_array( $post_type, $enabled_types, true ) ) {
			return $schemas;
		}

		// Check per-post enabled toggle.
		$post_enabled = get_post_meta( $post_id, '_wpschema_enabled', true );
		if ( '0' === $post_enabled ) {
			return $schemas;
		}

		// Check for custom JSON-LD override.
		$custom_json = get_post_meta( $post_id, '_wpschema_custom_json', true );
		if ( ! empty( $custom_json ) ) {
			$decoded = json_decode( $custom_json, true );
			if ( null !== $decoded ) {
				$schemas[] = $decoded;
				return $schemas;
			}
		}

		// WebPage schema for this post.
		$webpage  = new WPSchema_Type_WebPage( $this->settings );
		$schemas[] = $webpage->build( $post_id );

		// Entity schema (Organization/LocalBusiness/Person) based on type override or default.
		$type_override = get_post_meta( $post_id, '_wpschema_type', true );
		$schema_type   = ! empty( $type_override ) ? $type_override : $this->settings['schema_type'];

		// Don't duplicate WebPage if that's the selected type.
		if ( 'WebPage' !== $schema_type ) {
			$entity_schema = $this->build_entity_schema( $schema_type, $post_id );
			if ( $entity_schema ) {
				$schemas[] = $entity_schema;
			}
		}

		return $schemas;
	}

	/**
	 * Build the default entity schema (Organization/LocalBusiness/Person).
	 *
	 * @return array|null
	 */
	private function build_default_entity_schema(): ?array {
		return $this->build_entity_schema( $this->settings['schema_type'] ?? 'Organization' );
	}

	/**
	 * Build an entity schema by type.
	 *
	 * @param string   $type    The schema type identifier.
	 * @param int|null $post_id Optional post ID.
	 * @return array|null
	 */
	private function build_entity_schema( string $type, ?int $post_id = null ): ?array {
		$schema_object = match ( $type ) {
			'Organization'  => new WPSchema_Type_Organisation( $this->settings ),
			'LocalBusiness' => new WPSchema_Type_LocalBusiness( $this->settings ),
			'Person'        => new WPSchema_Type_Person( $this->settings ),
			default         => null,
		};

		if ( ! $schema_object ) {
			return null;
		}

		return $schema_object->build( $post_id );
	}

	/**
	 * Render a single JSON-LD script block.
	 *
	 * @param array $schema The schema data to render.
	 */
	private function render_json_ld( array $schema ): void {
		$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

		if ( ! $json ) {
			return;
		}

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			$json // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD requires unescaped output.
		);
	}

	/**
	 * Get plugin settings.
	 *
	 * @return array
	 */
	private function get_settings(): array {
		$defaults = array(
			'enabled'            => true,
			'schema_type'        => 'Organization',
			'website_schema'     => true,
			'enabled_post_types' => array( 'post', 'page' ),
		);

		return wp_parse_args( get_option( 'wpschema_settings', array() ), $defaults );
	}
}
