<?php
/**
 * Site meta
 * Add custom site header meta and footer description
 *
 * @package blok45
 * @since 1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Blok45_Modules_Sitemeta {
	/**
	 * Use this method instead of constructor to avoid setting multiple hooks
	 */
	public static function load_module() {
		add_filter( 'language_attributes', array( __CLASS__, 'add_xmlns' ) );

		add_action( 'wp_head', array( __CLASS__, 'add_og_tags' ), 5 );
		add_action( 'wp_head', array( __CLASS__, 'add_icons' ), 4 );
		add_action( 'wp_head', array( __CLASS__, 'add_seo_tags' ), 4 );
		add_action( 'admin_head', array( __CLASS__, 'add_icons' ), 4 );

		add_action( 'wp_head', array( __CLASS__, 'add_twitter_tags' ), 5 );
		add_filter( 'wp_sitemaps_add_provider', array( __CLASS__, 'remove_users_from_sitemap' ), 10, 2 );
		add_filter( 'wp_sitemaps_taxonomies', array( __CLASS__, 'remove_categories_from_sitemap' ) );
	}

	/**
	 * Add og xmlns
	 */
	public static function add_xmlns( $output ) {
		return 'prefix="og: http://ogp.me/ns#" ' . $output;
	}

	/**
	 * Add manifest and header icons
	 */
	public static function add_icons() {
		$meta = array();

		$meta[] = sprintf(
			'<link rel="shortcut icon" href="%s" crossorigin="use-credentials">',
			esc_url( get_stylesheet_directory_uri() . '/assets/images/favicon.ico' )
		);

		$meta[] = sprintf(
			'<link rel="icon" type="image/png" sizes="32x32" href="%s">',
			esc_url( get_stylesheet_directory_uri() . '/assets/images/icon-32.png' )
		);

		$meta[] = sprintf(
			'<link rel="icon" type="image/png" sizes="192x192" href="%s">',
			esc_url( get_stylesheet_directory_uri() . '/assets/images/icon-192.png' )
		);

		$meta[] = sprintf(
			'<link rel="apple-touch-icon" sizes="180x180" href="%s">',
			esc_url( get_stylesheet_directory_uri() . '/assets/images/icon-180.png' )
		);

		return self::print_tags( $meta );
	}

	/**
	 * Add seo tags
	 */
	public static function add_seo_tags() {
		$meta = array();

		$meta[] = sprintf(
			'<meta name="description" content="%s">',
			esc_attr( self::get_description() )
		);

		return self::print_tags( $meta );
	}

	/**
	 * Add og tags
	 *
	 * @link https://developers.facebook.com/docs/sharing/webmasters
	 */
	public static function add_og_tags() {
		$meta = array();

		$meta[] = sprintf(
			'<meta property="og:site_name" content="%s">',
			esc_attr( get_bloginfo( 'name' ) )
		);

		$meta[] = sprintf(
			'<meta property="og:locale" content="%s">',
			esc_attr( get_locale() )
		);

		$meta[] = sprintf(
			'<meta property="og:description" content="%s">',
			esc_attr( self::get_description() )
		);

		if ( is_post_type_archive() ) {
			$meta[] = sprintf(
				'<meta property="og:url" content="%s">',
				esc_url( get_post_type_archive_link( get_post_type() ) )
			);
		}

		if ( is_tax() || is_category() || is_tag() ) {
			$object = get_queried_object();

			if ( ! empty( $object->term_id ) ) {
				$meta[] = sprintf(
					'<meta property="og:url" content="%s">',
					esc_url( get_term_link( get_queried_object()->term_id ) )
				);
			}
		}

		if ( is_front_page() ) {
			$meta[] = sprintf(
				'<meta property="og:url" content="%s">',
				esc_url( home_url( '/' ) )
			);

			$meta[] = sprintf(
				'<meta property="og:title" content="%s">',
				esc_attr( get_bloginfo( 'name' ) )
			);
		}

		if ( is_singular() && ! is_front_page() ) {
			$object_id = get_queried_object_id();

			array_push( $meta, '<meta property="og:type" content="article">' );

			$meta[] = sprintf(
				'<meta property="og:url" content="%s">',
				esc_url( get_permalink( $object_id ) )
			);

			$meta[] = sprintf(
				'<meta property="og:title" content="%s">',
				esc_attr( wp_strip_all_tags( get_the_title( $object_id ) ) )
			);
		}

		if ( is_archive() ) {
			$object_type = get_queried_object();

			$meta[] = sprintf(
				'<meta property="og:title" content="%s">',
				esc_attr( wp_get_document_title() )
			);
		}

		return self::print_tags( $meta );
	}

	/**
	 * Add twitter tags
	 * Note: We shouldn't duplicate og tags
	 *
	 * @link https://developer.twitter.com/en/docs/tweets/optimize-with-cards/guides/getting-started.html
	 */
	public static function add_twitter_tags() {
		$meta = array(
			'<meta name="twitter:card" content="summary_large_image">',
		);

		return self::print_tags( $meta );
	}

	/**
	 * Get description
	 */
	private static function get_description() {
		$description = get_bloginfo( 'description' );

		if ( is_singular() && ! is_front_page() ) {
			$object_id = get_queried_object_id();

			if ( has_excerpt( $object_id ) ) {
				return trim( wp_strip_all_tags( get_the_excerpt( $object_id ) ) );
			}

			return $description;
		}

		return html_entity_decode( $description );
	}

	/**
	 * Print tags if not empty array
	 */
	private static function print_tags( $meta ) {
		foreach ( $meta as $tag ) {
			echo $tag . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}

	/**
	 * Disable the users sitemap provider to exclude users from sitemaps.
	 */
	public static function remove_users_from_sitemap( $provider, $name ) {
		if ( 'users' === $name ) {
			return false;
		}

		return $provider;
	}

	/**
	 * Remove categories from sitemap taxonomies list.
	 *
	 * @param array $taxonomies Registered sitemap taxonomies.
	 *
	 * @return array
	 */
	public static function remove_categories_from_sitemap( $taxonomies ) {
		unset( $taxonomies['category'] );

		return $taxonomies;
	}

	private static function wrap_string( $str, $start, $end ) {
		return $start . $str . $end;
	}
}

/**
 * Load current module environment
 */
Blok45_Modules_Sitemeta::load_module();
