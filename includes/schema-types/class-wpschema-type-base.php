<?php
/**
 * Base schema type class.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract base class for all schema types.
 */
abstract class WPSchema_Type_Base {

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	protected array $settings;

	/**
	 * Constructor.
	 *
	 * @param array $settings Plugin settings.
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Get the Schema.org @type value.
	 *
	 * @return string
	 */
	abstract public function get_type(): string;

	/**
	 * Build the schema array for this type.
	 *
	 * @param int|null $post_id Optional post ID for context.
	 * @return array
	 */
	abstract public function build( ?int $post_id = null ): array;

	/**
	 * Wrap schema data with the JSON-LD context.
	 *
	 * @param array $data Schema-specific data.
	 * @return array Complete JSON-LD array.
	 */
	protected function wrap( array $data ): array {
		return array_merge(
			array(
				'@context' => 'https://schema.org',
				'@type'    => $this->get_type(),
			),
			$data
		);
	}

	/**
	 * Build a PostalAddress schema fragment.
	 *
	 * @return array|null PostalAddress array or null if no address data.
	 */
	protected function build_address(): ?array {
		$street   = $this->settings['org_street'] ?? '';
		$locality = $this->settings['org_locality'] ?? '';
		$region   = $this->settings['org_region'] ?? '';
		$postal   = $this->settings['org_postal_code'] ?? '';
		$country  = $this->settings['org_country'] ?? '';

		if ( empty( $street ) && empty( $locality ) && empty( $country ) ) {
			return null;
		}

		$address = array(
			'@type' => 'PostalAddress',
		);

		if ( ! empty( $street ) ) {
			$address['streetAddress'] = $street;
		}
		if ( ! empty( $locality ) ) {
			$address['addressLocality'] = $locality;
		}
		if ( ! empty( $region ) ) {
			$address['addressRegion'] = $region;
		}
		if ( ! empty( $postal ) ) {
			$address['postalCode'] = $postal;
		}
		if ( ! empty( $country ) ) {
			$address['addressCountry'] = $country;
		}

		return $address;
	}

	/**
	 * Remove empty values from a schema array.
	 *
	 * @param array $data Schema data.
	 * @return array Cleaned data.
	 */
	protected function clean( array $data ): array {
		return array_filter( $data, static function ( $value ) {
			if ( is_array( $value ) ) {
				return ! empty( $value );
			}
			return '' !== $value && null !== $value;
		} );
	}
}
