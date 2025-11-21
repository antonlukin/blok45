<?php
/**
 * Custom template tags for this theme

 * @package blok45
 * @since 1.0
 */

if ( ! function_exists( 'blok45_display_meta' ) ) :
	/**
	 * Public template function to show post info
	 */
	function blok45_display_meta( $before = '', $after = '' ) {
		$output = '';

		if ( method_exists( 'Blok45_Modules_Filters', 'get_meta' ) ) {
			$output = Blok45_Modules_Filters::get_meta();
		}

		if ( ! empty( $output ) ) {
			$output = $before . $output . $after;

			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
endif;

if ( ! function_exists( 'blok45_year_ranges' ) ) :
	/**
	 * Public template function to show post info
	 */
	function blok45_year_ranges() {
		$output = array();

		if ( ! method_exists( 'Blok45_Modules_Filters', 'get_year_ranges' ) ) {
			return $output;
		}

		$output = Blok45_Modules_Filters::get_year_ranges();

		return $output;
	}
endif;

if ( ! function_exists( 'blok45_get_icon' ) ) :
	/**
	 * Public template function to show icon
	 */
	function blok45_get_icon( $name ) {
		$version = filemtime( get_template_directory() . '/assets/images/symbol-defs.svg' );

		return get_template_directory_uri() . "/assets/images/symbol-defs.svg?v={$version}#blok45-icon-{$name}";
	}
endif;

if ( ! function_exists( 'blok45_get_gallery_items' ) ) :
	/**
	 * Return gallery items extracted from the first Gutenberg gallery block.
	 *
	 * @param int|WP_Post|null $post Optional post object or ID.
	 *
	 * @return array
	 */
	function blok45_get_gallery_items( $post = null ) {
		if ( ! method_exists( 'Blok45_Modules_Gallery', 'get_gallery_items' ) ) {
			return array();
		}

		return Blok45_Modules_Gallery::get_gallery_items( $post );
	}
endif;

if ( ! function_exists( 'blok45_get_map_args' ) ) :
	/**
	 * Return sanitized arguments for the map template part.
	 *
	 * @param array $args Raw arguments.
	 *
	 * @return array
	 */
	function blok45_get_map_args( array $args = array() ) {
		if ( method_exists( 'Blok45_Modules_Map', 'prepare_template_args' ) ) {
			$args = Blok45_Modules_Map::prepare_template_args( $args );
		}

		return $args;
	}
endif;

if ( ! function_exists( 'blok45_get_post_rating' ) ) :
	/**
	 * Return rating data for the given post.
	 *
	 * @param int|WP_Post|null $post Optional post reference.
	 *
	 * @return array
	 */
	function blok45_get_post_rating( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return 0;
		}

		$rating = 0;

		if ( method_exists( 'Blok45_Modules_Rating', 'get_post_rating_value' ) ) {
			$rating = (float) Blok45_Modules_Rating::get_post_rating_value( $post->ID );
		}

		return $rating;
	}
endif;

if ( ! function_exists( 'blok45_get_single_context' ) ) :
	/**
	 * Return prepared context data for the single post template.
	 *
	 * @param int|WP_Post|null $post Optional post object or ID.
	 *
	 * @return array
	 */
	function blok45_get_single_context( $post = null ) {
		if ( ! method_exists( 'Blok45_Modules_Single', 'get_template_context' ) ) {
			return array();
		}

		return Blok45_Modules_Single::get_template_context( $post );
	}
endif;

if ( ! function_exists( 'blok45_get_correction_url' ) ) :
	/**
	 * Return the correction form URL stored in theme settings.
	 *
	 * @param int|WP_Post|null $post Optional post reference.
	 *
	 * @return string
	 */
	function blok45_get_correction_url( $post = null ) {
		if ( method_exists( 'Blok45_Modules_Settings', 'get_correction_url' ) ) {
			return Blok45_Modules_Settings::get_correction_url( $post );
		}

		return home_url( '/' );
	}
endif;

if ( ! function_exists( 'blok45_get_artist_list' ) ) :
	/**
	 * Return list of artists, optionally filtered by min count.
	 *
	 * @param int $min_count Minimum number of posts per artist. Default 0.
	 *
	 * @return WP_Term[] Array of artist terms.
	 */
	function blok45_get_artist_list( $min_count = 0 ) {
		if ( ! class_exists( 'Blok45_Modules_Directory' ) ) {
			return array();
		}

		return Blok45_Modules_Directory::get_artist_list( $min_count );
	}
endif;

if ( ! function_exists( 'blok45_get_artist_preview_query' ) ) :
	/**
	 * Return preview thumbnails for a given artist.
	 *
	 * @param WP_Term $artist Artist term object.
	 *
	 * @return string[] Array of thumbnail HTML strings.
	 */
	function blok45_get_artist_preview_query( $artist ) {
		if ( empty( $artist->term_id ) || ! class_exists( 'Blok45_Modules_Directory' ) ) {
			return array();
		}

		return Blok45_Modules_Directory::get_artist_preview_thumbnails( (int) $artist->term_id );
	}
endif;
