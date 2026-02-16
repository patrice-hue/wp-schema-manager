<?php
/**
 * WebPage schema type.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates WebPage JSON-LD schema.
 */
class WPSchema_Type_WebPage extends WPSchema_Type_Base {

	/**
	 * Get the Schema.org @type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'WebPage';
	}

	/**
	 * Build the WebPage schema.
	 *
	 * @param int|null $post_id Optional post ID for context.
	 * @return array
	 */
	public function build( ?int $post_id = null ): array {
		$data = array();

		if ( $post_id ) {
			$post = get_post( $post_id );

			if ( $post ) {
				$data['name']         = get_the_title( $post );
				$data['url']          = get_permalink( $post );
				$data['description']  = $this->get_description( $post );
				$data['datePublished'] = get_the_date( 'c', $post );
				$data['dateModified']  = get_the_modified_date( 'c', $post );

				// Add author.
				$author_name = get_the_author_meta( 'display_name', $post->post_author );
				if ( ! empty( $author_name ) ) {
					$data['author'] = array(
						'@type' => 'Person',
						'name'  => $author_name,
					);
				}

				// Add isPartOf reference to the WebSite.
				$data['isPartOf'] = array(
					'@type' => 'WebSite',
					'name'  => get_bloginfo( 'name' ),
					'url'   => home_url( '/' ),
				);
			}
		}

		return $this->wrap( $this->clean( $data ) );
	}

	/**
	 * Get an appropriate description for the post.
	 *
	 * @param \WP_Post $post The post object.
	 * @return string
	 */
	private function get_description( \WP_Post $post ): string {
		if ( ! empty( $post->post_excerpt ) ) {
			return wp_strip_all_tags( $post->post_excerpt );
		}

		$content = wp_strip_all_tags( $post->post_content );
		if ( strlen( $content ) > 160 ) {
			$content = substr( $content, 0, 157 ) . '...';
		}

		return $content;
	}
}
