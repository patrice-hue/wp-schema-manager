<?php
/**
 * Schema preview panel for the admin editor.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a schema preview meta box that shows the raw JSON-LD
 * output that will be generated for the current post.
 */
class WPSchema_Preview {

	/**
	 * Hook into WordPress.
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_preview_meta_box' ) );
		add_action( 'wp_ajax_wpschema_preview', array( $this, 'ajax_preview' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the preview meta box on enabled post types.
	 */
	public function add_preview_meta_box(): void {
		$settings   = $this->get_settings();
		$post_types = $settings['enabled_post_types'] ?? array( 'post', 'page' );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'wpschema-preview',
				__( 'Schema Preview', 'wp-schema-manager' ),
				array( $this, 'render_preview_meta_box' ),
				$post_type,
				'normal',
				'low'
			);
		}
	}

	/**
	 * Enqueue assets for the preview panel.
	 *
	 * @param string $hook_suffix The current admin page hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen     = get_current_screen();
		$settings   = $this->get_settings();
		$post_types = $settings['enabled_post_types'] ?? array( 'post', 'page' );

		if ( ! $screen || ! in_array( $screen->post_type, $post_types, true ) ) {
			return;
		}

		wp_enqueue_style(
			'wpschema-admin',
			WPSCHEMA_PLUGIN_URL . 'admin/css/wpschema-admin.css',
			array(),
			WPSCHEMA_VERSION
		);

		wp_enqueue_script(
			'wpschema-preview',
			WPSCHEMA_PLUGIN_URL . 'admin/js/wpschema-admin.js',
			array( 'jquery' ),
			WPSCHEMA_VERSION,
			true
		);

		wp_localize_script( 'wpschema-preview', 'wpschemaPreview', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wpschema_preview' ),
			'postId'  => get_the_ID(),
		) );
	}

	/**
	 * Render the preview meta box.
	 *
	 * @param \WP_Post $post The current post.
	 */
	public function render_preview_meta_box( \WP_Post $post ): void {
		$schema_json = $this->generate_preview( $post->ID );
		?>
		<div class="wpschema-preview-wrap">
			<p>
				<button type="button" class="button button-secondary" id="wpschema-refresh-preview">
					<?php esc_html_e( 'Refresh Preview', 'wp-schema-manager' ); ?>
				</button>
				<span class="wpschema-preview-status"></span>
			</p>
			<pre class="wpschema-preview-output" id="wpschema-preview-output"><?php echo esc_html( $schema_json ); ?></pre>
			<p class="description">
				<?php esc_html_e( 'This is the JSON-LD structured data that will be output in the <head> for this post. Use the Google Rich Results Test to validate.', 'wp-schema-manager' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * AJAX handler for refreshing the schema preview.
	 */
	public function ajax_preview(): void {
		check_ajax_referer( 'wpschema_preview', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-schema-manager' ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( __( 'Invalid post ID.', 'wp-schema-manager' ) );
		}

		$preview = $this->generate_preview( $post_id );

		wp_send_json_success( array( 'schema' => $preview ) );
	}

	/**
	 * Generate the schema preview JSON for a post.
	 *
	 * @param int $post_id The post ID.
	 * @return string Formatted JSON string.
	 */
	private function generate_preview( int $post_id ): string {
		$settings = $this->get_settings();
		$schemas  = array();

		// Check per-post enabled toggle.
		$post_enabled = get_post_meta( $post_id, '_wpschema_enabled', true );
		if ( '0' === $post_enabled ) {
			return __( '// Schema output is disabled for this post.', 'wp-schema-manager' );
		}

		if ( empty( $settings['enabled'] ) ) {
			return __( '// Schema output is globally disabled.', 'wp-schema-manager' );
		}

		// Check for custom JSON-LD.
		$custom_json = get_post_meta( $post_id, '_wpschema_custom_json', true );
		if ( ! empty( $custom_json ) ) {
			$decoded = json_decode( $custom_json, true );
			if ( null !== $decoded ) {
				return wp_json_encode( $decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
			}
		}

		// WebSite schema.
		if ( ! empty( $settings['website_schema'] ) ) {
			$website   = new WPSchema_Type_WebSite( $settings );
			$schemas[] = $website->build();
		}

		// WebPage schema.
		$webpage   = new WPSchema_Type_WebPage( $settings );
		$schemas[] = $webpage->build( $post_id );

		// Entity schema.
		$type_override = get_post_meta( $post_id, '_wpschema_type', true );
		$schema_type   = ! empty( $type_override ) ? $type_override : ( $settings['schema_type'] ?? 'Organization' );

		if ( 'WebPage' !== $schema_type ) {
			$entity = match ( $schema_type ) {
				'Organization'        => new WPSchema_Type_Organisation( $settings ),
				'LocalBusiness'       => new WPSchema_Type_LocalBusiness( $settings ),
				'ProfessionalService' => new WPSchema_Type_ProfessionalService( $settings ),
				'Person'              => new WPSchema_Type_Person( $settings ),
				'Service'             => new WPSchema_Type_Service( $settings ),
				'FAQPage'             => new WPSchema_Type_FAQPage( $settings ),
				'Product'             => new WPSchema_Type_Product( $settings ),
				'Offer'               => new WPSchema_Type_Offer( $settings ),
				default               => null,
			};

			if ( $entity ) {
				$schemas[] = $entity->build( $post_id );
			}
		}

		// BreadcrumbList preview.
		if ( ! empty( $settings['breadcrumb_enabled'] ) ) {
			$breadcrumb = new WPSchema_Type_BreadcrumbList( $settings );
			$breadcrumb_schema = $breadcrumb->build( $post_id );
			if ( ! empty( $breadcrumb_schema ) ) {
				$schemas[] = $breadcrumb_schema;
			}
		}

		if ( empty( $schemas ) ) {
			return __( '// No schema data to output.', 'wp-schema-manager' );
		}

		$output = '';
		foreach ( $schemas as $schema ) {
			$output .= wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . "\n\n";
		}

		return trim( $output );
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
			'breadcrumb_enabled' => false,
		);

		return wp_parse_args( get_option( 'wpschema_settings', array() ), $defaults );
	}
}
