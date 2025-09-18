<?php
/**
 * Postinfo filters
 * Helper class for template-tags to get post info inside loop
 * Use get_ prefix for public methods
 *
 * @package blok45
 * @since 1.0
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Blok45_Modules_Postinfo {
	/**
	 * Get list of post authors
	 */
	public static function get_meta() {
		$links = array();

		$artists = get_the_terms( get_the_ID(), 'artist' );

		if ( $artists && ! is_wp_error( $artists ) ) {

			foreach ( $artists as $artist ) {
				$links[] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( get_term_link( $artist ) ),
					esc_html( $artist->name )
				);
			}
		}

		$years = get_the_terms( get_the_ID(), 'years' );

		if ( $years && ! is_wp_error( $years ) ) {

			foreach ( $years as $year ) {
				$links[] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( get_term_link( $year ) ),
					esc_html( $year->name )
				);
			}
		}

		return implode( '', $links );
	}

	/**
	 * Get post excerpt if exists
	 */
	public static function get_excerpt( $output = '' ) {
		if ( has_excerpt() ) {
			$output = apply_filters( 'the_excerpt', get_the_excerpt() );
		}

		return $output;
	}

	/**
	 * Get post publish date
	 */
	public static function get_date() {
		return esc_html( get_the_date( 'd.m' ) );
	}

	/**
	 * Get thumbnail caption
	 */
	public static function get_caption( $output = '' ) {
		$caption = get_the_post_thumbnail_caption();

		if ( ! empty( $caption ) ) {
			$output = esc_html( $caption );
		}

		return $output;
	}
}
