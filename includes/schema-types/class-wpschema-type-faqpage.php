<?php
/**
 * FAQPage schema type.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates FAQPage JSON-LD schema.
 *
 * Integrates with WordPress core Details block (accordion pattern)
 * to automatically extract FAQ content from post content.
 */
class WPSchema_Type_FAQPage extends WPSchema_Type_Base {

	/**
	 * Get the Schema.org @type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'FAQPage';
	}

	/**
	 * Build the FAQPage schema.
	 *
	 * @param int|null $post_id Optional post ID.
	 * @return array
	 */
	public function build( ?int $post_id = null ): array {
		$data = array(
			'name' => '',
			'url'  => '',
		);

		if ( $post_id ) {
			$post = get_post( $post_id );

			if ( $post ) {
				$data['name'] = get_the_title( $post );
				$data['url']  = get_permalink( $post );

				$questions = $this->extract_faq_from_content( $post->post_content );
				if ( ! empty( $questions ) ) {
					$data['mainEntity'] = $questions;
				}
			}
		}

		return $this->wrap( $this->clean( $data ) );
	}

	/**
	 * Extract FAQ questions and answers from post content.
	 *
	 * Parses WordPress core Details blocks (<!-- wp:details -->)
	 * which serve as the accordion/FAQ pattern in Gutenberg.
	 * Also supports manually structured heading + paragraph FAQ patterns.
	 *
	 * @param string $content The post content.
	 * @return array Array of Question schema objects.
	 */
	private function extract_faq_from_content( string $content ): array {
		$questions = array();

		// Parse WordPress Details blocks (accordion pattern).
		$questions = array_merge( $questions, $this->parse_details_blocks( $content ) );

		// If no Details blocks found, try HTML <details> elements.
		if ( empty( $questions ) ) {
			$questions = $this->parse_html_details( $content );
		}

		return $questions;
	}

	/**
	 * Parse WordPress core Details blocks.
	 *
	 * The Details block uses <!-- wp:details --> with a <summary> for the
	 * question and the inner content for the answer.
	 *
	 * @param string $content Post content.
	 * @return array Array of Question schema objects.
	 */
	private function parse_details_blocks( string $content ): array {
		$questions = array();
		$blocks    = parse_blocks( $content );

		foreach ( $blocks as $block ) {
			if ( 'core/details' !== ( $block['blockName'] ?? '' ) ) {
				continue;
			}

			$question_text = $this->extract_summary_text( $block );
			$answer_text   = $this->extract_details_answer( $block );

			if ( ! empty( $question_text ) && ! empty( $answer_text ) ) {
				$questions[] = $this->build_question( $question_text, $answer_text );
			}
		}

		return $questions;
	}

	/**
	 * Extract the summary (question) text from a Details block.
	 *
	 * @param array $block The parsed block.
	 * @return string
	 */
	private function extract_summary_text( array $block ): string {
		$html = $block['innerHTML'] ?? '';

		if ( preg_match( '/<summary[^>]*>(.*?)<\/summary>/si', $html, $matches ) ) {
			return trim( wp_strip_all_tags( $matches[1] ) );
		}

		// Check block attributes for showContent or summary.
		return trim( $block['attrs']['summary'] ?? '' );
	}

	/**
	 * Extract the answer content from a Details block.
	 *
	 * @param array $block The parsed block.
	 * @return string
	 */
	private function extract_details_answer( array $block ): string {
		$answer_parts = array();

		// Inner blocks contain the answer content.
		if ( ! empty( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $inner_block ) {
				$rendered = render_block( $inner_block );
				$text     = trim( wp_strip_all_tags( $rendered ) );
				if ( ! empty( $text ) ) {
					$answer_parts[] = $text;
				}
			}
		}

		if ( ! empty( $answer_parts ) ) {
			return implode( ' ', $answer_parts );
		}

		// Fallback: extract text after </summary> from innerHTML.
		$html = $block['innerHTML'] ?? '';
		if ( preg_match( '/<\/summary>(.*?)<\/details>/si', $html, $matches ) ) {
			$text = trim( wp_strip_all_tags( $matches[1] ) );
			if ( ! empty( $text ) ) {
				return $text;
			}
		}

		return '';
	}

	/**
	 * Parse native HTML <details>/<summary> elements from rendered content.
	 *
	 * @param string $content Post content.
	 * @return array Array of Question schema objects.
	 */
	private function parse_html_details( string $content ): array {
		$questions = array();

		// Render shortcodes and blocks first.
		$rendered = do_shortcode( $content );

		if ( ! preg_match_all(
			'/<details[^>]*>\s*<summary[^>]*>(.*?)<\/summary>(.*?)<\/details>/si',
			$rendered,
			$matches,
			PREG_SET_ORDER
		) ) {
			return $questions;
		}

		foreach ( $matches as $match ) {
			$question_text = trim( wp_strip_all_tags( $match[1] ) );
			$answer_text   = trim( wp_strip_all_tags( $match[2] ) );

			if ( ! empty( $question_text ) && ! empty( $answer_text ) ) {
				$questions[] = $this->build_question( $question_text, $answer_text );
			}
		}

		return $questions;
	}

	/**
	 * Build a single FAQ Question schema object.
	 *
	 * @param string $question The question text.
	 * @param string $answer   The answer text.
	 * @return array
	 */
	private function build_question( string $question, string $answer ): array {
		return array(
			'@type'          => 'Question',
			'name'           => $question,
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $answer,
			),
		);
	}
}
