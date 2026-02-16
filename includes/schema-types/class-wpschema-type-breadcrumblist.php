<?php
/**
 * BreadcrumbList schema type.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates BreadcrumbList JSON-LD schema from the current post's
 * URL hierarchy and WordPress permalink structure.
 */
class WPSchema_Type_BreadcrumbList extends WPSchema_Type_Base {

	/**
	 * Get the Schema.org @type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'BreadcrumbList';
	}

	/**
	 * Build the BreadcrumbList schema.
	 *
	 * @param int|null $post_id Optional post ID.
	 * @return array
	 */
	public function build( ?int $post_id = null ): array {
		$items = $this->build_breadcrumb_trail( $post_id );

		if ( empty( $items ) ) {
			return array();
		}

		$data = array(
			'itemListElement' => $items,
		);

		return $this->wrap( $data );
	}

	/**
	 * Build the breadcrumb trail for a given post.
	 *
	 * @param int|null $post_id The post ID.
	 * @return array Array of ListItem schema objects.
	 */
	private function build_breadcrumb_trail( ?int $post_id ): array {
		$items    = array();
		$position = 1;

		// Always start with Home.
		$items[] = $this->build_list_item( $position++, get_bloginfo( 'name' ), home_url( '/' ) );

		if ( ! $post_id ) {
			return $items;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return $items;
		}

		$post_type = get_post_type( $post_id );

		// Add post type archive if applicable (not for pages).
		if ( 'page' !== $post_type ) {
			$archive_items = $this->get_post_type_breadcrumb( $post_type, $position );
			if ( ! empty( $archive_items ) ) {
				$items    = array_merge( $items, $archive_items );
				$position += count( $archive_items );
			}
		}

		// Add taxonomy breadcrumb (primary category for posts).
		if ( 'post' === $post_type ) {
			$category_items = $this->get_category_breadcrumb( $post_id, $position );
			if ( ! empty( $category_items ) ) {
				$items    = array_merge( $items, $category_items );
				$position += count( $category_items );
			}
		}

		// Add parent pages for hierarchical post types.
		if ( is_post_type_hierarchical( $post_type ) && $post->post_parent ) {
			$parent_items = $this->get_ancestor_breadcrumbs( $post_id, $position );
			if ( ! empty( $parent_items ) ) {
				$items    = array_merge( $items, $parent_items );
				$position += count( $parent_items );
			}
		}

		// Add the current page as the final breadcrumb.
		$items[] = $this->build_list_item( $position, get_the_title( $post ), get_permalink( $post ) );

		return $items;
	}

	/**
	 * Get the post type archive breadcrumb item.
	 *
	 * @param string $post_type The post type slug.
	 * @param int    $position  Current breadcrumb position.
	 * @return array
	 */
	private function get_post_type_breadcrumb( string $post_type, int $position ): array {
		$items = array();

		$post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type_object || ! $post_type_object->has_archive ) {
			return $items;
		}

		$archive_url = get_post_type_archive_link( $post_type );
		if ( $archive_url ) {
			$items[] = $this->build_list_item(
				$position,
				$post_type_object->labels->name,
				$archive_url
			);
		}

		return $items;
	}

	/**
	 * Get category hierarchy breadcrumb items for a post.
	 *
	 * @param int $post_id  The post ID.
	 * @param int $position Current breadcrumb position.
	 * @return array
	 */
	private function get_category_breadcrumb( int $post_id, int $position ): array {
		$items      = array();
		$categories = get_the_category( $post_id );

		if ( empty( $categories ) ) {
			return $items;
		}

		// Use the first (primary) category.
		$category  = $categories[0];
		$ancestors = array();

		// Build ancestor chain.
		if ( $category->parent ) {
			$ancestor_ids = get_ancestors( $category->term_id, 'category' );
			$ancestor_ids = array_reverse( $ancestor_ids );

			foreach ( $ancestor_ids as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'category' );
				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$ancestors[] = $ancestor;
				}
			}
		}

		// Add ancestor categories.
		foreach ( $ancestors as $ancestor ) {
			$items[] = $this->build_list_item(
				$position++,
				$ancestor->name,
				get_term_link( $ancestor )
			);
		}

		// Add the primary category.
		$items[] = $this->build_list_item(
			$position,
			$category->name,
			get_term_link( $category )
		);

		return $items;
	}

	/**
	 * Get parent page breadcrumb items for hierarchical post types.
	 *
	 * @param int $post_id  The post ID.
	 * @param int $position Current breadcrumb position.
	 * @return array
	 */
	private function get_ancestor_breadcrumbs( int $post_id, int $position ): array {
		$items     = array();
		$ancestors = get_post_ancestors( $post_id );

		if ( empty( $ancestors ) ) {
			return $items;
		}

		// Ancestors are returned child-to-parent; reverse to parent-to-child.
		$ancestors = array_reverse( $ancestors );

		foreach ( $ancestors as $ancestor_id ) {
			$ancestor = get_post( $ancestor_id );
			if ( $ancestor ) {
				$items[] = $this->build_list_item(
					$position++,
					get_the_title( $ancestor ),
					get_permalink( $ancestor )
				);
			}
		}

		return $items;
	}

	/**
	 * Build a single ListItem for the BreadcrumbList.
	 *
	 * @param int    $position The position in the breadcrumb trail.
	 * @param string $name     The breadcrumb label.
	 * @param string $url      The breadcrumb URL.
	 * @return array
	 */
	private function build_list_item( int $position, string $name, string $url ): array {
		return array(
			'@type'    => 'ListItem',
			'position' => $position,
			'name'     => $name,
			'item'     => $url,
		);
	}
}
