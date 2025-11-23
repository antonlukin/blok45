<?php
/**
 * Directory helpers for artist listings and unknown archives.
 *
 * @package blok45
 * @since 1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Blok45_Modules_Directory {
	const TRANSIENT_PREFIX  = 'blok45_artist_preview';
	const PREVIEW_LIMIT     = 4;
	const UNKNOWN_SLUG      = 'artists/unknown';
	const UNKNOWN_QUERY_VAR = 'blok45_unknown_artist';

	/**
	 * Bootstrap directory module.
	 */
	public static function load_module() {
		add_action( 'transition_post_status', array( __CLASS__, 'handle_status_transition' ), 10, 3 );
		add_action( 'before_delete_post', array( __CLASS__, 'flush_all_cache' ) );

		add_action( 'init', array( __CLASS__, 'register_unknown_artist_rewrite' ) );
		add_filter( 'query_vars', array( __CLASS__, 'register_unknown_query_var' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'handle_unknown_archive' ), 9 );
		add_filter( 'template_include', array( __CLASS__, 'ensure_unknown_archive_template' ) );

		add_filter( 'get_the_archive_title', array( __CLASS__, 'filter_unknown_archive_title' ) );
		add_filter( 'get_the_archive_description', array( __CLASS__, 'filter_unknown_archive_description' ) );
	}

	/**
	 * Return filtered artist terms list.
	 *
	 * @param int $min_count Optional minimum post count.
	 *
	 * @return WP_Term[]
	 */
	public static function get_artist_list( $min_count = 0 ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'artist',
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$min_count = max( 0, (int) $min_count );

		if ( $min_count > 0 ) {
			$terms = array_filter(
				$terms,
				static function ( $term ) use ( $min_count ) {
					return (int) $term->count >= $min_count;
				}
			);
		}

		return array_values( $terms );
	}

	/**
	 * Return cached preview thumbnails for an artist.
	 *
	 * Always limited by self::PREVIEW_LIMIT latest posts.
	 *
	 * @param int $artist_id Artist term ID.
	 *
	 * @return string[]
	 */
	public static function get_artist_preview_thumbnails( $artist_id ) {
		$artist_id = absint( $artist_id );

		if ( empty( $artist_id ) ) {
			return array();
		}

		$cache_key  = self::get_cache_key( $artist_id );
		$cached     = get_transient( $cache_key );
		$normalized = self::normalize_thumbnail_cache( $cached );

		if ( ! empty( $normalized ) ) {
			if ( $normalized !== $cached ) {
				set_transient( $cache_key, $normalized, DAY_IN_SECONDS );
			}

			return $normalized;
		}

		$args = array(
			'post_type'           => 'post',
			'posts_per_page'      => self::PREVIEW_LIMIT,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'fields'              => 'ids',
			'orderby'             => 'date',
			'order'               => 'ASC',
			'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'artist',
					'field'    => 'term_id',
					'terms'    => $artist_id,
				),
			),
		);

		$thumbnails = array();

		// Get post IDs.
		$post_ids = get_posts( $args );

		foreach ( $post_ids as $post_id ) {
			$thumbnail = get_the_post_thumbnail(
				$post_id,
				'thumbnail',
				array(
					'class'    => 'directory__preview-image',
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);

			if ( $thumbnail ) {
				$thumbnails[] = $thumbnail;
			}
		}

		set_transient( $cache_key, $thumbnails, DAY_IN_SECONDS );

		return $thumbnails;
	}

	/**
	 * Handle post status transitions to flush previews.
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post       Post object.
	 */
	public static function handle_status_transition( $new_status, $old_status, $post ) {
		if ( empty( $post->post_type ) || $post->post_type !== 'post' ) {
			return;
		}

		if ( wp_is_post_autosave( $post->ID ) || wp_is_post_revision( $post->ID ) ) {
			return;
		}

		self::flush_all_cache();
	}

	/**
	 * Flush all preview caches.
	 */
	public static function flush_all_cache() {
		global $wpdb;

		$transients = $wpdb->get_col( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_' . self::TRANSIENT_PREFIX . '%'
			)
		);

		if ( empty( $transients ) ) {
			return;
		}

		foreach ( $transients as $transient ) {
			$key = str_replace( '_transient_', '', $transient );

			delete_transient( $key );
		}
	}

	/**
	 * Build cache key for preview thumbnails store.
	 *
	 * @param int $artist_id Artist term ID.
	 *
	 * @return string
	 */
	protected static function get_cache_key( $artist_id ) {
		return sprintf( '%s_%d', self::TRANSIENT_PREFIX, absint( $artist_id ) );
	}

	/**
	 * Normalize cached thumbnails to current format.
	 *
	 * Handles legacy caches stored as [limit => array( ... )].
	 *
	 * @param mixed $cache Cached value.
	 *
	 * @return string[]
	 */
	protected static function normalize_thumbnail_cache( $cache ) {
		if ( false === $cache ) {
			return array();
		}

		if ( isset( $cache[ self::PREVIEW_LIMIT ] ) && is_array( $cache[ self::PREVIEW_LIMIT ] ) ) {
			$cache = $cache[ self::PREVIEW_LIMIT ];
		}

		if ( ! is_array( $cache ) ) {
			return array();
		}

		$cache = array_values(
			array_filter(
				$cache,
				static function ( $item ) {
					return is_string( $item ) && $item !== '';
				}
			)
		);

		return $cache;
	}

	/**
	 * Register rewrite rule for unknown artist archive.
	 */
	public static function register_unknown_artist_rewrite() {
		$slug = trim( self::UNKNOWN_SLUG, '/' );

		if ( '' === $slug ) {
			return;
		}

		add_rewrite_rule(
			sprintf( '^%s/?$', $slug ),
			sprintf( 'index.php?post_type=post&%s=1', self::UNKNOWN_QUERY_VAR ),
			'top'
		);
	}

	/**
	 * Register custom query var for unknown archive detection.
	 *
	 * @param array $vars Public query vars.
	 *
	 * @return array
	 */
	public static function register_unknown_query_var( $vars ) {
		$vars[] = self::UNKNOWN_QUERY_VAR;

		return array_unique( $vars );
	}

	/**
	 * Prepare WP_Query for unknown artist archive requests.
	 *
	 * @param WP_Query $query Current query.
	 */
	public static function handle_unknown_archive( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! self::is_unknown_artist_archive() ) {
			return;
		}

		$query->set( 'post_type', 'post' );
		$query->set( 'post_status', 'publish' );
		$query->set( 'posts_per_page', -1 );
		$query->set(
			'tax_query',
			array(
				array(
					'taxonomy' => 'artist',
					'field'    => 'term_id',
					'operator' => 'NOT EXISTS',
				),
			)
		);

		$query->is_archive = true;
		$query->is_home    = false;
	}

	/**
	 * Force archive template usage for unknown artist archive.
	 *
	 * @param string $template Current template path.
	 *
	 * @return string
	 */
	public static function ensure_unknown_archive_template( $template ) {
		if ( self::is_unknown_artist_archive() ) {
			$new_template = locate_template( array( 'archive.php' ) );

			if ( ! empty( $new_template ) ) {
				return $new_template;
			}
		}

		return $template;
	}

	/**
	 * Substitute archive title for unknown artist archive.
	 *
	 * @param string $title Default title.
	 *
	 * @return string
	 */
	public static function filter_unknown_archive_title( $title ) {
		if ( self::is_unknown_artist_archive() ) {
			return esc_html__( 'Unknown Artists', 'blok45' );
		}

		return $title;
	}

	/**
	 * Substitute archive description when needed.
	 *
	 * @param string $description Default description.
	 *
	 * @return string
	 */
	public static function filter_unknown_archive_description( $description ) {
		if ( ! self::is_unknown_artist_archive() ) {
			return $description;
		}

		if ( method_exists( 'Blok45_Modules_Settings', 'get_unknown_archive_description' ) ) {
			$description = Blok45_Modules_Settings::get_unknown_archive_description();
		}

		return $description;
	}

	/**
	 * Determine whether the current or provided query targets unknown artists archive.
	 *
	 * @return bool
	 */
	protected static function is_unknown_artist_archive() {
		if ( get_query_var( self::UNKNOWN_QUERY_VAR ) ) {
			return true;
		}

		return false;
	}
}

Blok45_Modules_Directory::load_module();
