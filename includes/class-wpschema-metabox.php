<?php
/**
 * Post/page meta box for schema overrides.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a Schema meta box to post/page editors for per-post overrides.
 */
class WPSchema_Metabox {

	/**
	 * Meta key prefix for schema post meta.
	 *
	 * @var string
	 */
	private string $meta_prefix = '_wpschema_';

	/**
	 * Hook into WordPress.
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta' ), 10, 2 );
	}

	/**
	 * Register the meta box on enabled post types.
	 */
	public function add_meta_box(): void {
		$settings   = $this->get_settings();
		$post_types = $settings['enabled_post_types'] ?? array( 'post', 'page' );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'wpschema-meta-box',
				__( 'Schema Settings', 'wp-schema-manager' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render the meta box content.
	 *
	 * @param \WP_Post $post The current post.
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'wpschema_meta_save', 'wpschema_meta_nonce' );

		$enabled     = get_post_meta( $post->ID, $this->meta_prefix . 'enabled', true );
		$schema_type = get_post_meta( $post->ID, $this->meta_prefix . 'type', true );
		$custom_json = get_post_meta( $post->ID, $this->meta_prefix . 'custom_json', true );

		// Default to enabled if not explicitly set.
		if ( '' === $enabled ) {
			$enabled = '1';
		}

		$types = array(
			''              => __( '— Use Default —', 'wp-schema-manager' ),
			'WebPage'       => __( 'WebPage', 'wp-schema-manager' ),
			'Organization'  => __( 'Organisation', 'wp-schema-manager' ),
			'LocalBusiness' => __( 'Local Business', 'wp-schema-manager' ),
			'Person'        => __( 'Person', 'wp-schema-manager' ),
		);

		/**
		 * Filter available schema types in the meta box.
		 *
		 * @param array $types Array of type => label.
		 */
		$types = apply_filters( 'wpschema_register_type', $types );
		?>
		<div class="wpschema-metabox">
			<p>
				<label>
					<input
						type="checkbox"
						name="wpschema_enabled"
						value="1"
						<?php checked( $enabled, '1' ); ?>
					/>
					<?php esc_html_e( 'Enable schema output for this post', 'wp-schema-manager' ); ?>
				</label>
			</p>

			<p>
				<label for="wpschema_type">
					<strong><?php esc_html_e( 'Schema Type Override:', 'wp-schema-manager' ); ?></strong>
				</label><br />
				<select name="wpschema_type" id="wpschema_type" style="width: 100%;">
					<?php foreach ( $types as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $schema_type, $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				<label for="wpschema_custom_json">
					<strong><?php esc_html_e( 'Custom JSON-LD:', 'wp-schema-manager' ); ?></strong>
				</label><br />
				<textarea
					name="wpschema_custom_json"
					id="wpschema_custom_json"
					rows="8"
					style="width: 100%; font-family: monospace; font-size: 12px;"
					placeholder='<?php esc_attr_e( 'Paste custom JSON-LD to override auto-generated schema...', 'wp-schema-manager' ); ?>'
				><?php echo esc_textarea( $custom_json ); ?></textarea>
			</p>
			<p class="description">
				<?php esc_html_e( 'If custom JSON-LD is provided, it will be used instead of auto-generated schema for this post.', 'wp-schema-manager' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 */
	public function save_meta( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['wpschema_meta_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['wpschema_meta_nonce'], 'wpschema_meta_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$settings   = $this->get_settings();
		$post_types = $settings['enabled_post_types'] ?? array( 'post', 'page' );

		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return;
		}

		// Save enabled state.
		$enabled = isset( $_POST['wpschema_enabled'] ) ? '1' : '0';
		update_post_meta( $post_id, $this->meta_prefix . 'enabled', $enabled );

		// Save schema type override.
		$schema_type = sanitize_text_field( $_POST['wpschema_type'] ?? '' );
		update_post_meta( $post_id, $this->meta_prefix . 'type', $schema_type );

		// Save custom JSON-LD (validate it's valid JSON if provided).
		$custom_json = wp_unslash( $_POST['wpschema_custom_json'] ?? '' );
		if ( ! empty( $custom_json ) ) {
			$decoded = json_decode( $custom_json, true );
			if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
				// Invalid JSON — store empty to avoid broken output.
				$custom_json = '';
			}
		}
		update_post_meta( $post_id, $this->meta_prefix . 'custom_json', sanitize_textarea_field( $custom_json ) );
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
			'enabled_post_types' => array( 'post', 'page' ),
		);

		return wp_parse_args( get_option( 'wpschema_settings', array() ), $defaults );
	}
}
