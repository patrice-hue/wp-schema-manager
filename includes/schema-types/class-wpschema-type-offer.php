<?php
/**
 * Offer schema type for WooCommerce.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates standalone Offer JSON-LD schema.
 *
 * While Offer is typically embedded within a Product schema, this class
 * allows standalone Offer output for landing pages, promotions, or
 * custom offer pages where Product context is not applicable.
 */
class WPSchema_Type_Offer extends WPSchema_Type_Base {

	/**
	 * Get the Schema.org @type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'Offer';
	}

	/**
	 * Build the Offer schema.
	 *
	 * @param int|null $post_id Optional post ID.
	 * @return array
	 */
	public function build( ?int $post_id = null ): array {
		// If WooCommerce is active and this is a product, build from WC data.
		if ( $post_id && $this->is_woocommerce_active() && 'product' === get_post_type( $post_id ) ) {
			return $this->build_woocommerce_offer( $post_id );
		}

		return $this->build_generic_offer( $post_id );
	}

	/**
	 * Build Offer schema from WooCommerce product data.
	 *
	 * @param int $post_id The product post ID.
	 * @return array
	 */
	private function build_woocommerce_offer( int $post_id ): array {
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return $this->build_generic_offer( $post_id );
		}

		$data = array(
			'url'           => get_permalink( $post_id ),
			'priceCurrency' => get_woocommerce_currency(),
			'price'         => $product->get_price(),
			'itemCondition' => 'https://schema.org/NewCondition',
		);

		$data['name'] = $product->get_name();

		// Availability.
		$stock_status = $product->get_stock_status();
		$data['availability'] = match ( $stock_status ) {
			'instock'     => 'https://schema.org/InStock',
			'outofstock'  => 'https://schema.org/OutOfStock',
			'onbackorder' => 'https://schema.org/BackOrder',
			default       => 'https://schema.org/InStock',
		};

		$data['priceValidUntil'] = gmdate( 'Y-12-31' );

		// Seller.
		if ( ! empty( $this->settings['org_name'] ) ) {
			$data['seller'] = array(
				'@type' => 'Organization',
				'name'  => $this->settings['org_name'],
			);
		}

		return $this->wrap( $this->clean( $data ) );
	}

	/**
	 * Build a generic Offer schema from post data.
	 *
	 * @param int|null $post_id Optional post ID.
	 * @return array
	 */
	private function build_generic_offer( ?int $post_id = null ): array {
		$data = array();

		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( $post ) {
				$data['name'] = get_the_title( $post );
				$data['url']  = get_permalink( $post );
			}
		}

		if ( ! empty( $this->settings['org_name'] ) ) {
			$data['seller'] = array(
				'@type' => 'Organization',
				'name'  => $this->settings['org_name'],
			);
		}

		return $this->wrap( $this->clean( $data ) );
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	private function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}
}
