<?php
/**
 * Helpers for archive pages.
 *
 * @package blok45
 * @since 1.0
 */

class Blok45_Modules_Archive {
	/**
	 * Bootstrap module hooks.
	 */
	public static function load_module() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_archive_script' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'set_chronological_order' ) );
	}

	/**
	 * Force chronological ordering for public archives and the front page.
	 *
	 * @param WP_Query $query Current query instance.
	 */
	public static function set_chronological_order( $query ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! ( $query->is_home() || $query->is_front_page() || $query->is_archive() ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( empty( $orderby ) || in_array( $orderby, array( 'date', 'post_date' ), true ) ) {
			$query->set( 'orderby', 'date' );
			$query->set( 'order', 'ASC' );
		}
	}

	/**
	 * Enqueue infinite scroll script for taxonomy archives.
	 */
	public static function enqueue_archive_script() {
		global $wp_query;

		if ( ! is_tax() ) {
			return;
		}

		$term = get_queried_object();

		if ( empty( $term ) ) {
			return;
		}

		$params = array();

		switch ( $term->taxonomy ) {
			case 'artist':
				$params['artist'] = (int) $term->term_id;
				break;

			case 'years':
				$params['years'] = (int) $term->term_id;
				break;

			default:
				return;
		}

		$max_pages = (int) $wp_query->max_num_pages;

		if ( $max_pages <= 1 ) {
			return;
		}

		$current_page = max( 1, absint( get_query_var( 'paged' ) ) );

		$script_path = get_template_directory() . '/assets/archive.min.js';

		wp_enqueue_script(
			'blok45-archive',
			get_template_directory_uri() . '/assets/archive.min.js',
			array(),
			file_exists( $script_path ) ? filemtime( $script_path ) : null,
			true
		);

		wp_localize_script(
			'blok45-archive',
			'Blok45Archive',
			array(
				'currentPage' => $current_page,
				'maxPages'    => $max_pages,
				'startPage'   => min( $max_pages, $current_page + 1 ),
				'hasMore'     => ( $current_page < $max_pages ),
				'endpoint'    => esc_url_raw( rest_url( 'blok45/v1/filter' ) ),
				'params'      => $params,
			)
		);
	}
}

Blok45_Modules_Archive::load_module();
