<?php
/**
 * WebSite schema type.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates WebSite JSON-LD schema.
 */
class WPSchema_Type_WebSite extends WPSchema_Type_Base {

	/**
	 * Get the Schema.org @type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'WebSite';
	}

	/**
	 * Build the WebSite schema.
	 *
	 * @param int|null $post_id Optional post ID.
	 * @return array
	 */
	public function build( ?int $post_id = null ): array {
		$data = array(
			'name' => get_bloginfo( 'name' ),
			'url'  => home_url( '/' ),
		);

		$description = get_bloginfo( 'description' );
		if ( ! empty( $description ) ) {
			$data['description'] = $description;
		}

		// Add sitelinks search box.
		$data['potentialAction'] = array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}' ),
			),
			'query-input' => 'required name=search_term_string',
		);

		return $this->wrap( $this->clean( $data ) );
	}
}
