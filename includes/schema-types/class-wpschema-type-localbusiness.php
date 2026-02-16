<?php
/**
 * LocalBusiness schema type.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates LocalBusiness JSON-LD schema.
 */
class WPSchema_Type_LocalBusiness extends WPSchema_Type_Base {

	/**
	 * Get the Schema.org @type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'LocalBusiness';
	}

	/**
	 * Build the LocalBusiness schema.
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

		// Parse opening hours string (e.g., "Mo-Fr 08:30-17:00, Sa 09:00-13:00").
		$opening_hours = $this->parse_opening_hours();
		if ( ! empty( $opening_hours ) ) {
			$data['openingHoursSpecification'] = $opening_hours;
		}

		return $this->wrap( $this->clean( $data ) );
	}

	/**
	 * Parse the opening hours string into OpeningHoursSpecification format.
	 *
	 * Accepts formats like: "Mo-Fr 08:30-17:00, Sa 09:00-13:00"
	 *
	 * @return array Array of OpeningHoursSpecification objects.
	 */
	private function parse_opening_hours(): array {
		$hours_string = $this->settings['lb_opening_hours'] ?? '';

		if ( empty( $hours_string ) ) {
			return array();
		}

		$day_map = array(
			'Mo' => 'Monday',
			'Tu' => 'Tuesday',
			'We' => 'Wednesday',
			'Th' => 'Thursday',
			'Fr' => 'Friday',
			'Sa' => 'Saturday',
			'Su' => 'Sunday',
		);

		$specifications = array();
		$entries        = array_map( 'trim', explode( ',', $hours_string ) );

		foreach ( $entries as $entry ) {
			if ( ! preg_match( '/^([A-Za-z-]+)\s+(\d{2}:\d{2})-(\d{2}:\d{2})$/', $entry, $matches ) ) {
				continue;
			}

			$day_part = $matches[1];
			$opens    = $matches[2];
			$closes   = $matches[3];

			$days = array();

			if ( str_contains( $day_part, '-' ) ) {
				$range      = explode( '-', $day_part );
				$start_key  = ucfirst( strtolower( $range[0] ) );
				$end_key    = ucfirst( strtolower( $range[1] ) );
				$day_keys   = array_keys( $day_map );
				$start_idx  = array_search( $start_key, $day_keys, true );
				$end_idx    = array_search( $end_key, $day_keys, true );

				if ( false !== $start_idx && false !== $end_idx ) {
					for ( $i = $start_idx; $i <= $end_idx; $i++ ) {
						$days[] = $day_map[ $day_keys[ $i ] ];
					}
				}
			} else {
				$key = ucfirst( strtolower( $day_part ) );
				if ( isset( $day_map[ $key ] ) ) {
					$days[] = $day_map[ $key ];
				}
			}

			if ( ! empty( $days ) ) {
				$specifications[] = array(
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => $days,
					'opens'     => $opens,
					'closes'    => $closes,
				);
			}
		}

		return $specifications;
	}
}
