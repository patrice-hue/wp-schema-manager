<?php
/**
 * Admin settings page for WP Schema Manager.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the Settings → Schema Manager admin page.
 */
class WPSchema_Admin {

	/**
	 * Option key for plugin settings.
	 *
	 * @var string
	 */
	private string $option_key = 'wpschema_settings';

	/**
	 * Hook into WordPress.
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . WPSCHEMA_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
	}

	/**
	 * Add the settings page under the Settings menu.
	 */
	public function add_settings_page(): void {
		add_options_page(
			__( 'Schema Manager', 'wp-schema-manager' ),
			__( 'Schema Manager', 'wp-schema-manager' ),
			'manage_options',
			'wpschema-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Add Settings link to plugin action links.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function add_settings_link( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'options-general.php?page=wpschema-settings' ),
			__( 'Settings', 'wp-schema-manager' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Enqueue admin CSS and JS on our settings page only.
	 *
	 * @param string $hook_suffix The current admin page hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'settings_page_wpschema-settings' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'wpschema-admin',
			WPSCHEMA_PLUGIN_URL . 'admin/css/wpschema-admin.css',
			array(),
			WPSCHEMA_VERSION
		);
	}

	/**
	 * Register plugin settings using the Settings API.
	 */
	public function register_settings(): void {
		register_setting(
			'wpschema_settings_group',
			$this->option_key,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// General section.
		add_settings_section(
			'wpschema_general',
			__( 'General Settings', 'wp-schema-manager' ),
			array( $this, 'render_general_section' ),
			'wpschema-settings'
		);

		add_settings_field(
			'wpschema_enabled',
			__( 'Enable Schema Output', 'wp-schema-manager' ),
			array( $this, 'render_enabled_field' ),
			'wpschema-settings',
			'wpschema_general'
		);

		add_settings_field(
			'wpschema_schema_type',
			__( 'Default Schema Type', 'wp-schema-manager' ),
			array( $this, 'render_schema_type_field' ),
			'wpschema-settings',
			'wpschema_general'
		);

		add_settings_field(
			'wpschema_website_schema',
			__( 'WebSite Schema', 'wp-schema-manager' ),
			array( $this, 'render_website_schema_field' ),
			'wpschema-settings',
			'wpschema_general'
		);

		add_settings_field(
			'wpschema_enabled_post_types',
			__( 'Enabled Post Types', 'wp-schema-manager' ),
			array( $this, 'render_post_types_field' ),
			'wpschema-settings',
			'wpschema_general'
		);

		// Organisation / Business section.
		add_settings_section(
			'wpschema_organisation',
			__( 'Organisation / Business Details', 'wp-schema-manager' ),
			array( $this, 'render_organisation_section' ),
			'wpschema-settings'
		);

		$org_fields = array(
			'org_name'        => __( 'Name', 'wp-schema-manager' ),
			'org_url'         => __( 'URL', 'wp-schema-manager' ),
			'org_logo'        => __( 'Logo URL', 'wp-schema-manager' ),
			'org_phone'       => __( 'Phone', 'wp-schema-manager' ),
			'org_email'       => __( 'Email', 'wp-schema-manager' ),
			'org_street'      => __( 'Street Address', 'wp-schema-manager' ),
			'org_locality'    => __( 'City / Locality', 'wp-schema-manager' ),
			'org_region'      => __( 'State / Region', 'wp-schema-manager' ),
			'org_postal_code' => __( 'Postal Code', 'wp-schema-manager' ),
			'org_country'     => __( 'Country', 'wp-schema-manager' ),
		);

		foreach ( $org_fields as $key => $label ) {
			add_settings_field(
				'wpschema_' . $key,
				$label,
				array( $this, 'render_text_field' ),
				'wpschema-settings',
				'wpschema_organisation',
				array( 'field' => $key )
			);
		}

		// Person section (for Person schema type).
		add_settings_section(
			'wpschema_person',
			__( 'Person Details', 'wp-schema-manager' ),
			array( $this, 'render_person_section' ),
			'wpschema-settings'
		);

		$person_fields = array(
			'person_name'      => __( 'Full Name', 'wp-schema-manager' ),
			'person_url'       => __( 'URL', 'wp-schema-manager' ),
			'person_job_title' => __( 'Job Title', 'wp-schema-manager' ),
			'person_image'     => __( 'Image URL', 'wp-schema-manager' ),
		);

		foreach ( $person_fields as $key => $label ) {
			add_settings_field(
				'wpschema_' . $key,
				$label,
				array( $this, 'render_text_field' ),
				'wpschema-settings',
				'wpschema_person',
				array( 'field' => $key )
			);
		}

		// LocalBusiness section.
		add_settings_section(
			'wpschema_localbusiness',
			__( 'Local Business Details', 'wp-schema-manager' ),
			array( $this, 'render_localbusiness_section' ),
			'wpschema-settings'
		);

		$lb_fields = array(
			'lb_price_range'    => __( 'Price Range', 'wp-schema-manager' ),
			'lb_opening_hours'  => __( 'Opening Hours', 'wp-schema-manager' ),
		);

		foreach ( $lb_fields as $key => $label ) {
			add_settings_field(
				'wpschema_' . $key,
				$label,
				array( $this, 'render_text_field' ),
				'wpschema-settings',
				'wpschema_localbusiness',
				array( 'field' => $key )
			);
		}

		// Service section.
		add_settings_section(
			'wpschema_service',
			__( 'Service Details', 'wp-schema-manager' ),
			array( $this, 'render_service_section' ),
			'wpschema-settings'
		);

		$service_fields = array(
			'service_name'        => __( 'Service Name', 'wp-schema-manager' ),
			'service_description' => __( 'Service Description', 'wp-schema-manager' ),
			'service_url'         => __( 'Service URL', 'wp-schema-manager' ),
			'service_type'        => __( 'Service Type', 'wp-schema-manager' ),
			'service_area'        => __( 'Area Served', 'wp-schema-manager' ),
		);

		foreach ( $service_fields as $key => $label ) {
			add_settings_field(
				'wpschema_' . $key,
				$label,
				array( $this, 'render_text_field' ),
				'wpschema-settings',
				'wpschema_service',
				array( 'field' => $key )
			);
		}

		// BreadcrumbList section.
		add_settings_section(
			'wpschema_breadcrumb',
			__( 'Breadcrumb Settings', 'wp-schema-manager' ),
			array( $this, 'render_breadcrumb_section' ),
			'wpschema-settings'
		);

		add_settings_field(
			'wpschema_breadcrumb_enabled',
			__( 'BreadcrumbList Schema', 'wp-schema-manager' ),
			array( $this, 'render_breadcrumb_enabled_field' ),
			'wpschema-settings',
			'wpschema_breadcrumb'
		);
	}

	/**
	 * Sanitize settings on save.
	 *
	 * @param array $input Raw input from the form.
	 * @return array Sanitised settings.
	 */
	public function sanitize_settings( array $input ): array {
		$sanitized = array();

		$sanitized['enabled']        = ! empty( $input['enabled'] );
		$sanitized['schema_type']    = sanitize_text_field( $input['schema_type'] ?? 'Organization' );
		$sanitized['website_schema'] = ! empty( $input['website_schema'] );

		// Post types.
		$sanitized['enabled_post_types'] = array();
		if ( ! empty( $input['enabled_post_types'] ) && is_array( $input['enabled_post_types'] ) ) {
			$sanitized['enabled_post_types'] = array_map( 'sanitize_key', $input['enabled_post_types'] );
		}

		$sanitized['breadcrumb_enabled'] = ! empty( $input['breadcrumb_enabled'] );

		// Text fields.
		$text_fields = array(
			'org_name', 'org_url', 'org_logo', 'org_phone', 'org_email',
			'org_street', 'org_locality', 'org_region', 'org_postal_code', 'org_country',
			'person_name', 'person_url', 'person_job_title', 'person_image',
			'lb_price_range', 'lb_opening_hours',
			'service_name', 'service_description', 'service_url', 'service_type', 'service_area',
		);

		foreach ( $text_fields as $field ) {
			$sanitized[ $field ] = sanitize_text_field( $input[ $field ] ?? '' );
		}

		// Sanitize URL fields specifically.
		$url_fields = array( 'org_url', 'org_logo', 'person_url', 'person_image', 'service_url' );
		foreach ( $url_fields as $field ) {
			if ( ! empty( $sanitized[ $field ] ) ) {
				$sanitized[ $field ] = esc_url_raw( $sanitized[ $field ] );
			}
		}

		// Sanitize email.
		if ( ! empty( $sanitized['org_email'] ) ) {
			$sanitized['org_email'] = sanitize_email( $sanitized['org_email'] );
		}

		return $sanitized;
	}

	/**
	 * Get current settings with defaults.
	 *
	 * @return array
	 */
	public function get_settings(): array {
		$defaults = array(
			'enabled'            => true,
			'schema_type'        => 'Organization',
			'org_name'           => '',
			'org_url'            => '',
			'org_logo'           => '',
			'org_phone'          => '',
			'org_email'          => '',
			'org_street'         => '',
			'org_locality'       => '',
			'org_region'         => '',
			'org_postal_code'    => '',
			'org_country'        => '',
			'website_schema'     => true,
			'enabled_post_types' => array( 'post', 'page' ),
			'person_name'        => '',
			'person_url'         => '',
			'person_job_title'   => '',
			'person_image'       => '',
			'lb_price_range'      => '',
			'lb_opening_hours'    => '',
			'service_name'        => '',
			'service_description' => '',
			'service_url'         => '',
			'service_type'        => '',
			'service_area'        => '',
			'breadcrumb_enabled'  => false,
		);

		$settings = get_option( $this->option_key, array() );

		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap wpschema-settings-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'wpschema_settings_group' ); ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'wpschema_settings_group' );
				do_settings_sections( 'wpschema-settings' );
				submit_button( __( 'Save Settings', 'wp-schema-manager' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the General section description.
	 */
	public function render_general_section(): void {
		echo '<p>' . esc_html__( 'Configure global schema output settings.', 'wp-schema-manager' ) . '</p>';
	}

	/**
	 * Render the Organisation section description.
	 */
	public function render_organisation_section(): void {
		echo '<p>' . esc_html__( 'These details are used for Organization and LocalBusiness schema types.', 'wp-schema-manager' ) . '</p>';
	}

	/**
	 * Render the Person section description.
	 */
	public function render_person_section(): void {
		echo '<p>' . esc_html__( 'These details are used when the default schema type is set to Person.', 'wp-schema-manager' ) . '</p>';
	}

	/**
	 * Render the LocalBusiness section description.
	 */
	public function render_localbusiness_section(): void {
		echo '<p>' . esc_html__( 'Additional details for LocalBusiness schema. Address fields use the Organisation section above.', 'wp-schema-manager' ) . '</p>';
	}

	/**
	 * Render the enabled checkbox field.
	 */
	public function render_enabled_field(): void {
		$settings = $this->get_settings();
		?>
		<label>
			<input type="checkbox" name="wpschema_settings[enabled]" value="1" <?php checked( $settings['enabled'] ); ?> />
			<?php esc_html_e( 'Enable JSON-LD schema output on the front end', 'wp-schema-manager' ); ?>
		</label>
		<?php
	}

	/**
	 * Render the default schema type dropdown.
	 */
	public function render_schema_type_field(): void {
		$settings = $this->get_settings();
		$types    = array(
			'Organization'        => __( 'Organisation', 'wp-schema-manager' ),
			'LocalBusiness'       => __( 'Local Business', 'wp-schema-manager' ),
			'ProfessionalService' => __( 'Professional Service', 'wp-schema-manager' ),
			'Person'              => __( 'Person', 'wp-schema-manager' ),
			'Service'             => __( 'Service', 'wp-schema-manager' ),
		);
		?>
		<select name="wpschema_settings[schema_type]" id="wpschema_schema_type">
			<?php foreach ( $types as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['schema_type'], $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'The default schema type used for site-wide structured data.', 'wp-schema-manager' ); ?></p>
		<?php
	}

	/**
	 * Render the WebSite schema toggle.
	 */
	public function render_website_schema_field(): void {
		$settings = $this->get_settings();
		?>
		<label>
			<input type="checkbox" name="wpschema_settings[website_schema]" value="1" <?php checked( $settings['website_schema'] ); ?> />
			<?php esc_html_e( 'Output WebSite schema with sitelinks search box', 'wp-schema-manager' ); ?>
		</label>
		<?php
	}

	/**
	 * Render the enabled post types checkboxes.
	 */
	public function render_post_types_field(): void {
		$settings   = $this->get_settings();
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		/**
		 * Filter which post types are available for schema overrides.
		 *
		 * @param array $post_types Array of post type objects.
		 */
		$post_types = apply_filters( 'wpschema_enabled_post_types', $post_types );

		foreach ( $post_types as $pt ) {
			if ( 'attachment' === $pt->name ) {
				continue;
			}
			?>
			<label style="display: block; margin-bottom: 4px;">
				<input
					type="checkbox"
					name="wpschema_settings[enabled_post_types][]"
					value="<?php echo esc_attr( $pt->name ); ?>"
					<?php checked( in_array( $pt->name, $settings['enabled_post_types'], true ) ); ?>
				/>
				<?php echo esc_html( $pt->labels->name ); ?>
			</label>
			<?php
		}
		?>
		<p class="description"><?php esc_html_e( 'Select which post types can have per-post schema overrides.', 'wp-schema-manager' ); ?></p>
		<?php
	}

	/**
	 * Render a generic text input field.
	 *
	 * @param array $args Field arguments including 'field' key.
	 */
	public function render_text_field( array $args ): void {
		$settings = $this->get_settings();
		$field    = $args['field'];
		$value    = $settings[ $field ] ?? '';
		?>
		<input
			type="text"
			name="wpschema_settings[<?php echo esc_attr( $field ); ?>]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<?php
		if ( 'lb_opening_hours' === $field ) {
			echo '<p class="description">' . esc_html__( 'Example: Mo-Fr 08:30-17:00, Sa 09:00-13:00', 'wp-schema-manager' ) . '</p>';
		}
		if ( 'service_type' === $field ) {
			echo '<p class="description">' . esc_html__( 'E.g., "Consulting", "Legal Services", "Accounting"', 'wp-schema-manager' ) . '</p>';
		}
		if ( 'service_area' === $field ) {
			echo '<p class="description">' . esc_html__( 'Geographic area where the service is available.', 'wp-schema-manager' ) . '</p>';
		}
	}

	/**
	 * Render the Service section description.
	 */
	public function render_service_section(): void {
		echo '<p>' . esc_html__( 'These details are used for Service and ProfessionalService schema types.', 'wp-schema-manager' ) . '</p>';
	}

	/**
	 * Render the Breadcrumb section description.
	 */
	public function render_breadcrumb_section(): void {
		echo '<p>' . esc_html__( 'BreadcrumbList schema is auto-generated from the permalink structure and post hierarchy.', 'wp-schema-manager' ) . '</p>';
	}

	/**
	 * Render the breadcrumb enabled checkbox field.
	 */
	public function render_breadcrumb_enabled_field(): void {
		$settings = $this->get_settings();
		?>
		<label>
			<input type="checkbox" name="wpschema_settings[breadcrumb_enabled]" value="1" <?php checked( $settings['breadcrumb_enabled'] ); ?> />
			<?php esc_html_e( 'Auto-generate BreadcrumbList schema on singular posts and pages', 'wp-schema-manager' ); ?>
		</label>
		<?php
	}
}
