<?php
/**
 * Product schema type for WooCommerce.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates Product JSON-LD schema with WooCommerce integration.
 *
 * When WooCommerce is active, automatically extracts product data
 * (price, SKU, description, ratings, stock status) from WC products.
 */
class WPSchema_Type_Product extends WPSchema_Type_Base {

	/**
	 * Get the Schema.org @type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'Product';
	}

	/**
	 * Build the Product schema.
	 *
	 * @param int|null $post_id Optional post ID.
	 * @return array
	 */
	public function build( ?int $post_id = null ): array {
		// If WooCommerce is active and this is a WC product, use WC data.
		if ( $post_id && $this->is_woocommerce_active() && 'product' === get_post_type( $post_id ) ) {
			return $this->build_woocommerce_product( $post_id );
		}

		// Fallback: build from settings for non-WC contexts.
		return $this->build_generic_product( $post_id );
	}

	/**
	 * Build Product schema from WooCommerce product data.
	 *
	 * @param int $post_id The product post ID.
	 * @return array
	 */
	private function build_woocommerce_product( int $post_id ): array {
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return $this->build_generic_product( $post_id );
		}

		$data = array(
			'name'        => $product->get_name(),
			'url'         => get_permalink( $post_id ),
			'description' => wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ),
		);

		// Product image.
		$image_id = $product->get_image_id();
		if ( $image_id ) {
			$image_url = wp_get_attachment_url( $image_id );
			if ( $image_url ) {
				$data['image'] = $image_url;
			}
		}

		// SKU.
		$sku = $product->get_sku();
		if ( ! empty( $sku ) ) {
			$data['sku'] = $sku;
		}

		// Brand from organisation settings.
		if ( ! empty( $this->settings['org_name'] ) ) {
			$data['brand'] = array(
				'@type' => 'Brand',
				'name'  => $this->settings['org_name'],
			);
		}

		// Aggregate rating.
		if ( $product->get_review_count() > 0 ) {
			$data['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $product->get_average_rating(),
				'reviewCount' => $product->get_review_count(),
			);
		}

		// Offers.
		$offer = $this->build_woocommerce_offer( $product );
		if ( ! empty( $offer ) ) {
			$data['offers'] = $offer;
		}

		return $this->wrap( $this->clean( $data ) );
	}

	/**
	 * Build Offer schema from a WooCommerce product.
	 *
	 * @param \WC_Product $product The WooCommerce product.
	 * @return array
	 */
	private function build_woocommerce_offer( $product ): array {
		$offer = array(
			'@type'         => 'Offer',
			'url'           => get_permalink( $product->get_id() ),
			'priceCurrency' => get_woocommerce_currency(),
			'price'         => $product->get_price(),
		);

		// Availability.
		$stock_status = $product->get_stock_status();
		$offer['availability'] = match ( $stock_status ) {
			'instock'     => 'https://schema.org/InStock',
			'outofstock'  => 'https://schema.org/OutOfStock',
			'onbackorder' => 'https://schema.org/BackOrder',
			default       => 'https://schema.org/InStock',
		};

		// Price valid until (end of year as default).
		$offer['priceValidUntil'] = gmdate( 'Y-12-31' );

		// Seller from org settings.
		if ( ! empty( $this->settings['org_name'] ) ) {
			$offer['seller'] = array(
				'@type' => 'Organization',
				'name'  => $this->settings['org_name'],
			);
		}

		return $offer;
	}

	/**
	 * Build a generic Product schema from post data and settings.
	 *
	 * @param int|null $post_id Optional post ID.
	 * @return array
	 */
	private function build_generic_product( ?int $post_id = null ): array {
		$data = array();

		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( $post ) {
				$data['name']        = get_the_title( $post );
				$data['url']         = get_permalink( $post );
				$data['description'] = wp_strip_all_tags( $post->post_excerpt ?: wp_trim_words( $post->post_content, 30 ) );

				$thumbnail = get_the_post_thumbnail_url( $post_id, 'full' );
				if ( $thumbnail ) {
					$data['image'] = $thumbnail;
				}
			}
		}

		if ( ! empty( $this->settings['org_name'] ) ) {
			$data['brand'] = array(
				'@type' => 'Brand',
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
