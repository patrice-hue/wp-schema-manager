<?php
/**
 * ProfessionalService schema type.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates ProfessionalService JSON-LD schema.
 *
 * ProfessionalService is a more specific type of LocalBusiness,
 * suitable for law firms, accounting firms, medical practices, etc.
 */
class WPSchema_Type_ProfessionalService extends WPSchema_Type_Base {

	/**
	 * Get the Schema.org @type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'ProfessionalService';
	}

	/**
	 * Build the ProfessionalService schema.
	 *
	 * @param int|null $post_id Optional post ID.
	 * @return array
	 */
	public function build( ?int $post_id = null ): array {
		$data = array();

		if ( ! empty( $this->settings['org_name'] ) ) {
			$data['name'] = $this->settings['org_name'];
		}

		if ( ! empty( $this->settings['org_url'] ) ) {
			$data['url'] = $this->settings['org_url'];
		}

		if ( ! empty( $this->settings['org_logo'] ) ) {
			$data['logo'] = $this->settings['org_logo'];
		}

		if ( ! empty( $this->settings['org_phone'] ) ) {
			$data['telephone'] = $this->settings['org_phone'];
		}

		if ( ! empty( $this->settings['org_email'] ) ) {
			$data['email'] = $this->settings['org_email'];
		}

		$address = $this->build_address();
		if ( $address ) {
			$data['address'] = $address;
		}

		if ( ! empty( $this->settings['lb_price_range'] ) ) {
			$data['priceRange'] = $this->settings['lb_price_range'];
		}

		if ( ! empty( $this->settings['service_type'] ) ) {
			$data['additionalType'] = $this->settings['service_type'];
		}

		return $this->wrap( $this->clean( $data ) );
	}
}
