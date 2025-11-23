<?php
/**
 * Directory helpers artist previews.
 *
 * @package blok45
 * @since 1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Blok45_Modules_Directory {
	const TRANSIENT_PREFIX = 'blok45_artist_preview';
	const PREVIEW_LIMIT    = 4;

	/**
	 * Bootstrap directory module.
	 */
	public static function load_module() {
		add_action( 'transition_post_status', array( __CLASS__, 'handle_status_transition' ), 10, 3 );
		add_action( 'before_delete_post', array( __CLASS__, 'flush_all_cache' ) );
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
}

Blok45_Modules_Directory::load_module();
