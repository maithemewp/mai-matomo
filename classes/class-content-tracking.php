<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

class Mai_Analytics_Content_Tracking {
	/**
	 * Construct the class.
	 *
	 * @return void
	 */
	function __construct() {
		$this->hooks();
	}

	/**
	 * Runs frontend hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function hooks() {
		add_filter( 'maicca_content', [ $this, 'add_cca_attributes' ], 12, 2 );
		add_filter( 'maiam_ad',       [ $this, 'add_ad_attributes' ], 12, 2 );
	}

	/**
	 * Maybe add attributes to Mai CCA.
	 *
	 * @since TBD
	 *
	 * @param string $content The CCA content.
	 * @param array  $args    The CCA args.
	 *
	 * @return string
	 */
	function add_cca_attributes( $content, $args ) {
		// Bail if no name.
		if ( ! isset( $args['id'] ) || empty( $args['id'] ) ) {
			return $content;
		}

		return $this->add_attributes( $content, $args['id'] );
	}

	/**
	 * Maybe add attributes to Mai Ad.
	 *
	 * @since TBD
	 *
	 * @param string $content The CCA content.
	 * @param string $args    The CCA args.
	 *
	 * @return string
	 */
	function add_ad_attributes( $content, $args ) {
		// Bail if no name.
		if ( ! isset( $args['name'] ) || empty( $args['name'] ) ) {
			return $content;
		}

		return $this->add_attributes( $content, $args['name'] );
	}

	/**
	 * Adds element attributes.
	 *
	 * If you set the same attribute or the same class on multiple elements within one block,
	 * the first element found will always win. Nested content blocks are currently not supported in Matomo.
	 * This would happen if a Mai Ad block was used inside of a Mai CCA,
	 * the CCA would take precedence and the Ad links will have the content piece.
	 *
	 * @since TBD
	 *
	 * @param string $content The content.
	 * @param string $name    The name.
	 *
	 * @return string
	 */
	function add_attributes( $content, $name ) {
		// Bail if no content.
		if ( ! $content ) {
			return $content;
		}

		$dom      = maicca_get_dom_document( $content );
		$children = $dom->getElementsByTagName('body')->item(0)->childNodes;

		// Bail if no nodes.
		if ( ! $children->length ) {
			return $content;
		}

		if ( 1 === $children->length ) {
			// Get first element and set main attributes.
			$first = $children->item(0);
			$first->setAttribute( 'data-track-content', '' );
			$first->setAttribute( 'data-content-name', esc_attr( $name ) );
		} else {
			foreach ( $children as $node ) {
				// Skip if not an element we can add attributes to.
				if ( 'DOMElement' !== get_class( $node ) ) {
					continue;
				}

				// Set main attributes to all top level child elements.
				$node->setAttribute( 'data-track-content', '' );
				$node->setAttribute( 'data-content-name', esc_attr( $name ) );
			}
		}

		// Query elements.
		$xpath   = new DOMXPath( $dom );
		$actions = $xpath->query( '//a | //button | //input[@type="submit"]' );

		if ( $actions->length ) {
			foreach ( $actions as $node ) {
				$piece = 'input' === $node->tagName ? $node->getAttribute( 'value' ) : $node->textContent;

				if ( $piece ) {
					$node->setAttribute( 'data-content-piece', $piece );
				}

				// Disabled, because target should happen automatically via href in Matomo.
				// $target = 'a' === $node->tagName ? $node->getAttribute( 'href' ) : '';
				// if ( $target ) {
				// 	$node->setAttribute( 'data-content-target', $target );
				// }
			}
		}

		// Save new content.
		$content = $dom->saveHTML( $dom->documentElement );

		return $content;
	}
}
