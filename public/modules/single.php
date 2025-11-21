<?php
/**
 * Single template helpers.
 *
 * @package blok45
 * @since 1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Blok45_Modules_Single {
	/**
	 * Prepare data for the single post template.
	 *
	 * @param int|WP_Post|null $post Optional post object or ID.
	 *
	 * @return array
	 */
	public static function get_template_context( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return array();
		}

		$post_id = (int) $post->ID;

		return array(
			'post_id'  => $post_id,
			'gallery'  => self::prepare_gallery( $post_id, $post ),
			'meta'     => self::prepare_meta_groups( $post_id ),
			'map'      => self::prepare_map( $post_id ),
			'archived' => self::is_graffiti_archived( $post_id ),
		);
	}

	/**
	 * Build gallery related data.
	 *
	 * @param int     $post_id Current post ID.
	 * @param WP_Post $post    Current post object.
	 *
	 * @return array
	 */
	protected static function prepare_gallery( $post_id, $post ) {
		$raw_items = blok45_get_gallery_items( $post_id );
		$items     = array();

		foreach ( $raw_items as $index => $item ) {
			$items[] = array(
				'index'      => (int) $index,
				'caption'    => isset( $item['caption'] ) ? $item['caption'] : '',
				'alt'        => self::resolve_alt_text( $item, $post ),
				'width'      => isset( $item['width'] ) ? (int) $item['width'] : null,
				'height'     => isset( $item['height'] ) ? (int) $item['height'] : null,
				'attachment' => isset( $item['id'] ) ? (int) $item['id'] : 0,
				'full'       => isset( $item['full'] ) ? $item['full'] : '',
				'thumb'      => self::resolve_thumb_url( $item ),
				/* translators: %d: slide number */
				'label'      => sprintf( __( 'Show image %d', 'blok45' ), (int) $index + 1 ),
				'loading'    => 0 === (int) $index ? 'eager' : 'lazy',
			);
		}

		$has_thumbs = count( $items ) > 1;

		$args = array(
			'items'          => $items,
			'has_thumbs'     => $has_thumbs,
			'thumbs_classes' => self::prepare_class_string( array( 'swiper', 'swiper--thumbs' ) ),
			'rating'         => self::prepare_rating( $post_id ),
		);

		$main_classes = array( 'swiper', 'swiper--main' );

		if ( $has_thumbs ) {
			$main_classes[] = 'swiper--with-thumbs';
		}

		$args['main_classes'] = self::prepare_class_string( $main_classes );

		return $args;
	}

	/**
	 * Resolve the alt text for a gallery image.
	 *
	 * @param array   $item Gallery item.
	 * @param WP_Post $post Current post.
	 *
	 * @return string
	 */
	protected static function resolve_alt_text( $item, $post ) {
		$alt = '';

		if ( isset( $item['alt'] ) && '' !== trim( (string) $item['alt'] ) ) {
			$alt = (string) $item['alt'];
		} else {
			$alt = get_the_title( $post );
		}

		return $alt;
	}

	/**
	 * Resolve the thumbnail URL for a gallery image.
	 *
	 * @param array $item Gallery item.
	 *
	 * @return string
	 */
	protected static function resolve_thumb_url( $item ) {
		if ( isset( $item['thumb'] ) && $item['thumb'] ) {
			return (string) $item['thumb'];
		}

		return isset( $item['full'] ) ? (string) $item['full'] : '';
	}

	/**
	 * Build css class string from an array of class names.
	 *
	 * @param array $classes Raw class names.
	 *
	 * @return string
	 */
	protected static function prepare_class_string( array $classes ) {
		$classes = array_unique( array_filter( array_map( 'sanitize_html_class', $classes ) ) );

		return implode( ' ', $classes );
	}

	/**
	 * Prepare rating related data.
	 *
	 * @param int $post_id Current post ID.
	 *
	 * @return array
	 */
	protected static function prepare_rating( $post_id ) {
		$rating = blok45_get_post_rating( $post_id );

		$value   = 0;
		$display = number_format_i18n( 0 );

		if ( is_array( $rating ) ) {
			if ( isset( $rating['value'] ) && is_numeric( $rating['value'] ) ) {
				$value = max( 0, (int) $rating['value'] );
			}

			$display = ! empty( $rating['display'] ) ? (string) $rating['display'] : number_format_i18n( $value );

		} elseif ( is_numeric( $rating ) ) {
			$value   = max( 0, (int) round( $rating ) );
			$display = number_format_i18n( $value );
		}

		return array(
			'value'   => $value,
			'display' => $display,
		);
	}

	/**
	 * Prepare term meta groups for the sidebar.
	 *
	 * @param int $post_id Current post ID.
	 *
	 * @return array
	 */
	protected static function prepare_meta_groups( $post_id ) {
		$groups  = array();
		$artists = get_the_terms( $post_id, 'artist' );
		$years   = get_the_terms( $post_id, 'years' );

		if ( self::has_terms( $artists ) ) {
			$items = self::prepare_term_items( $artists );

			if ( ! empty( $items ) ) {
				$groups[] = array(
					'label' => _n( 'Artist', 'Artists', count( $artists ), 'blok45' ),
					'items' => $items,
				);
			}
		}

		if ( self::has_terms( $years ) ) {
			$items = self::prepare_term_items( $years );

			if ( ! empty( $items ) ) {
				$groups[] = array(
					'label' => _n( 'Year', 'Years', count( $years ), 'blok45' ),
					'items' => $items,
				);
			}
		}

		return $groups;
	}

	/**
	 * Check whether the terms array is valid and not empty.
	 *
	 * @param mixed $terms Terms collection.
	 *
	 * @return bool
	 */
	protected static function has_terms( $terms ) {
		return is_array( $terms ) && ! is_wp_error( $terms ) && ! empty( $terms );
	}

	/**
	 * Prepare simple term items for rendering.
	 *
	 * @param array $terms List of WP_Term objects.
	 *
	 * @return array
	 */
	protected static function prepare_term_items( array $terms ) {
		$items = array();

		foreach ( $terms as $term ) {
			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			$items[] = array(
				'url'  => $link,
				'name' => $term->name,
			);
		}

		return $items;
	}

	/**
	 * Prepare map data for the template.
	 *
	 * @param int $post_id Current post ID.
	 *
	 * @return array
	 */
	protected static function prepare_map( $post_id ) {
		$coords = trim( (string) get_post_meta( $post_id, 'blok45_coords', true ) );

		if ( '' === $coords ) {
			$coords = trim( (string) get_post_meta( $post_id, 'b45_coords', true ) );
		}

		if ( '' === $coords ) {
			return array();
		}

		return array( 'coords' => $coords );
	}

	/**
	 * Return true when graffiti is marked as archived.
	 *
	 * @param int $post_id Current post ID.
	 *
	 * @return bool
	 */
	protected static function is_graffiti_archived( $post_id ) {
		return (bool) get_post_meta( $post_id, 'blok45_graffiti_archived', true );
	}
}
