<?php
/**
 * Service schema type.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates Service JSON-LD schema.
 */
class WPSchema_Type_Service extends WPSchema_Type_Base {

	/**
	 * Get the Schema.org @type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'Service';
	}

	/**
	 * Build the Service schema.
	 *
	 * @param int|null $post_id Optional post ID.
	 * @return array
	 */
	public function build( ?int $post_id = null ): array {
		$data = array();

		if ( ! empty( $this->settings['service_name'] ) ) {
			$data['name'] = $this->settings['service_name'];
		}

		if ( ! empty( $this->settings['service_description'] ) ) {
			$data['description'] = $this->settings['service_description'];
		}

		if ( ! empty( $this->settings['service_url'] ) ) {
			$data['url'] = $this->settings['service_url'];
		}

		if ( ! empty( $this->settings['service_area'] ) ) {
			$data['areaServed'] = $this->settings['service_area'];
		}

		if ( ! empty( $this->settings['service_type'] ) ) {
			$data['serviceType'] = $this->settings['service_type'];
		}

		// Link to the providing organisation if configured.
		$provider = $this->build_provider();
		if ( $provider ) {
			$data['provider'] = $provider;
		}

		return $this->wrap( $this->clean( $data ) );
	}

	/**
	 * Build the provider (Organization) reference from org settings.
	 *
	 * @return array|null
	 */
	protected function build_provider(): ?array {
		if ( empty( $this->settings['org_name'] ) ) {
			return null;
		}

		$provider = array(
			'@type' => 'Organization',
			'name'  => $this->settings['org_name'],
		);

		if ( ! empty( $this->settings['org_url'] ) ) {
			$provider['url'] = $this->settings['org_url'];
		}

		return $provider;
	}
}
