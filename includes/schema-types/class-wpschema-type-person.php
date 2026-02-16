<?php
/**
 * Person schema type.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates Person JSON-LD schema.
 */
class WPSchema_Type_Person extends WPSchema_Type_Base {

	/**
	 * Get the Schema.org @type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'Person';
	}

	/**
	 * Build the Person schema.
	 *
	 * @param int|null $post_id Optional post ID.
	 * @return array
	 */
	public function build( ?int $post_id = null ): array {
		$data = array();

		if ( ! empty( $this->settings['person_name'] ) ) {
			$data['name'] = $this->settings['person_name'];
		}

		if ( ! empty( $this->settings['person_url'] ) ) {
			$data['url'] = $this->settings['person_url'];
		}

		if ( ! empty( $this->settings['person_job_title'] ) ) {
			$data['jobTitle'] = $this->settings['person_job_title'];
		}

		if ( ! empty( $this->settings['person_image'] ) ) {
			$data['image'] = $this->settings['person_image'];
		}

		return $this->wrap( $this->clean( $data ) );
	}
}
