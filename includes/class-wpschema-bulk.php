<?php
/**
 * Bulk schema assignment by taxonomy.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides an admin tool page for bulk-assigning schema types
 * to posts by category, tag, or custom taxonomy.
 */
class WPSchema_Bulk {

	/**
	 * Hook into WordPress.
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_bulk_page' ) );
		add_action( 'wp_ajax_wpschema_bulk_assign', array( $this, 'ajax_bulk_assign' ) );
		add_action( 'wp_ajax_wpschema_get_terms', array( $this, 'ajax_get_terms' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add the Bulk Assignment page under Tools menu.
	 */
	public function add_bulk_page(): void {
		add_management_page(
			__( 'Bulk Schema Assignment', 'wp-schema-manager' ),
			__( 'Schema Bulk Assign', 'wp-schema-manager' ),
			'manage_options',
			'wpschema-bulk',
			array( $this, 'render_bulk_page' )
		);
	}

	/**
	 * Enqueue assets on the bulk assignment page.
	 *
	 * @param string $hook_suffix The current admin page hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'tools_page_wpschema-bulk' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'wpschema-admin',
			WPSCHEMA_PLUGIN_URL . 'admin/css/wpschema-admin.css',
			array(),
			WPSCHEMA_VERSION
		);

		wp_enqueue_script(
			'wpschema-bulk',
			WPSCHEMA_PLUGIN_URL . 'admin/js/wpschema-bulk.js',
			array( 'jquery' ),
			WPSCHEMA_VERSION,
			true
		);

		wp_localize_script( 'wpschema-bulk', 'wpschemaBulk', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wpschema_bulk_assign' ),
			'strings' => array(
				'processing' => __( 'Processing...', 'wp-schema-manager' ),
				'complete'   => __( 'Bulk assignment complete.', 'wp-schema-manager' ),
				'error'      => __( 'An error occurred. Please try again.', 'wp-schema-manager' ),
				'confirm'    => __( 'This will update the schema type for all matching posts. Continue?', 'wp-schema-manager' ),
			),
		) );
	}

	/**
	 * Render the bulk assignment page.
	 */
	public function render_bulk_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$taxonomies  = $this->get_public_taxonomies();
		$schema_types = $this->get_available_schema_types();
		?>
		<div class="wrap wpschema-settings-wrap">
			<h1><?php esc_html_e( 'Bulk Schema Assignment', 'wp-schema-manager' ); ?></h1>
			<p><?php esc_html_e( 'Assign a schema type to all posts within a specific category, tag, or custom taxonomy term.', 'wp-schema-manager' ); ?></p>

			<form id="wpschema-bulk-form" method="post">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="wpschema-bulk-taxonomy"><?php esc_html_e( 'Taxonomy', 'wp-schema-manager' ); ?></label>
						</th>
						<td>
							<select name="taxonomy" id="wpschema-bulk-taxonomy" class="regular-text">
								<option value=""><?php esc_html_e( '— Select Taxonomy —', 'wp-schema-manager' ); ?></option>
								<?php foreach ( $taxonomies as $tax_slug => $tax_label ) : ?>
									<option value="<?php echo esc_attr( $tax_slug ); ?>">
										<?php echo esc_html( $tax_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wpschema-bulk-term"><?php esc_html_e( 'Term', 'wp-schema-manager' ); ?></label>
						</th>
						<td>
							<select name="term_id" id="wpschema-bulk-term" class="regular-text" disabled>
								<option value=""><?php esc_html_e( '— Select a taxonomy first —', 'wp-schema-manager' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Select the specific term to filter posts by.', 'wp-schema-manager' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wpschema-bulk-schema-type"><?php esc_html_e( 'Schema Type', 'wp-schema-manager' ); ?></label>
						</th>
						<td>
							<select name="schema_type" id="wpschema-bulk-schema-type" class="regular-text">
								<?php foreach ( $schema_types as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>">
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label>
								<input type="checkbox" name="enable_schema" value="1" checked />
								<?php esc_html_e( 'Enable schema output', 'wp-schema-manager' ); ?>
							</label>
						</th>
						<td>
							<p class="description"><?php esc_html_e( 'Also set schema output to enabled for matched posts.', 'wp-schema-manager' ); ?></p>
						</td>
					</tr>
				</table>

				<div id="wpschema-bulk-status" style="display: none;">
					<div class="wpschema-bulk-progress">
						<span class="spinner is-active" style="float: none; margin: 0 8px 0 0;"></span>
						<span id="wpschema-bulk-status-text"></span>
					</div>
				</div>

				<div id="wpschema-bulk-result" style="display: none;">
					<div class="notice notice-success inline">
						<p id="wpschema-bulk-result-text"></p>
					</div>
				</div>

				<?php submit_button( __( 'Assign Schema Type', 'wp-schema-manager' ), 'primary', 'wpschema-bulk-submit' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * AJAX handler for bulk schema assignment.
	 */
	public function ajax_bulk_assign(): void {
		check_ajax_referer( 'wpschema_bulk_assign', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-schema-manager' ) );
		}

		$taxonomy    = sanitize_key( $_POST['taxonomy'] ?? '' );
		$term_id     = absint( $_POST['term_id'] ?? 0 );
		$schema_type = sanitize_text_field( $_POST['schema_type'] ?? '' );
		$enable      = ! empty( $_POST['enable_schema'] );

		if ( empty( $taxonomy ) || empty( $term_id ) || empty( $schema_type ) ) {
			wp_send_json_error( __( 'Please fill in all required fields.', 'wp-schema-manager' ) );
		}

		// Verify taxonomy and term exist.
		if ( ! taxonomy_exists( $taxonomy ) ) {
			wp_send_json_error( __( 'Invalid taxonomy.', 'wp-schema-manager' ) );
		}

		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			wp_send_json_error( __( 'Invalid term.', 'wp-schema-manager' ) );
		}

		// Get all posts in this term.
		$posts = get_posts( array(
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			),
		) );

		if ( empty( $posts ) ) {
			wp_send_json_error(
				sprintf(
					/* translators: %s: term name */
					__( 'No posts found in "%s".', 'wp-schema-manager' ),
					$term->name
				)
			);
		}

		$updated = 0;
		foreach ( $posts as $post_id ) {
			update_post_meta( $post_id, '_wpschema_type', $schema_type );

			if ( $enable ) {
				update_post_meta( $post_id, '_wpschema_enabled', '1' );
			}

			$updated++;
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: 1: count of updated posts, 2: schema type, 3: term name */
					__( 'Updated %1$d posts to "%2$s" schema in "%3$s".', 'wp-schema-manager' ),
					$updated,
					$schema_type,
					$term->name
				),
				'count'   => $updated,
			)
		);
	}

	/**
	 * AJAX handler for loading terms for a given taxonomy.
	 */
	public function ajax_get_terms(): void {
		check_ajax_referer( 'wpschema_bulk_assign', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-schema-manager' ) );
		}

		$taxonomy = sanitize_key( $_POST['taxonomy'] ?? '' );

		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			wp_send_json_error( __( 'Invalid taxonomy.', 'wp-schema-manager' ) );
		}

		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		if ( is_wp_error( $terms ) ) {
			wp_send_json_error( __( 'Could not load terms.', 'wp-schema-manager' ) );
		}

		$result = array();
		foreach ( $terms as $term ) {
			$result[] = array(
				'id'    => $term->term_id,
				'name'  => $term->name,
				'count' => $term->count,
			);
		}

		wp_send_json_success( $result );
	}

	/**
	 * Get all public taxonomies as slug => label.
	 *
	 * @return array
	 */
	private function get_public_taxonomies(): array {
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		$result     = array();

		foreach ( $taxonomies as $tax ) {
			$result[ $tax->name ] = $tax->labels->name;
		}

		return $result;
	}

	/**
	 * Get available schema types for assignment.
	 *
	 * @return array
	 */
	private function get_available_schema_types(): array {
		$types = array(
			'Organization'        => __( 'Organisation', 'wp-schema-manager' ),
			'LocalBusiness'       => __( 'Local Business', 'wp-schema-manager' ),
			'ProfessionalService' => __( 'Professional Service', 'wp-schema-manager' ),
			'Person'              => __( 'Person', 'wp-schema-manager' ),
			'Service'             => __( 'Service', 'wp-schema-manager' ),
			'FAQPage'             => __( 'FAQ Page', 'wp-schema-manager' ),
			'Product'             => __( 'Product', 'wp-schema-manager' ),
			'Offer'               => __( 'Offer', 'wp-schema-manager' ),
		);

		/** This filter is documented in includes/class-wpschema-metabox.php */
		return apply_filters( 'wpschema_register_type', $types );
	}
}
