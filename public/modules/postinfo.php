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
     * Get link to parent category
     */
    public static function get_category( $output = '' ) {
        $categories = get_the_category();

        if ( empty( $categories ) ) {
            return null;
        }

        $category = $categories[0];

        // Get only the first category
        $cat_id = $category->term_id;

        if ( ! empty( $category->parent ) ) {
            $ancestors = get_ancestors( $category->term_id, 'category' );

            // Get top level category id
            $cat_id = end( $ancestors );
        }

        $output = sprintf(
            '<a class="meta__item" href="%s" title="%s">%s</a>',
            esc_url( get_category_link( $cat_id ) ),
            esc_html__( 'Открыть все записи из категории', 'blok45' ),
            esc_html( get_cat_name( $cat_id ) )
        );

        return $output;
    }

    /**
     * Get primary tag link
     */
    public static function get_tag( $output = '' ) {
        $tags = get_the_tags();

        if ( empty( $tags[0] ) ) {
            return null;
        }

        $output = sprintf(
            '<a class="meta__item" href="%s" title="%s">%s</a>',
            esc_url( get_tag_link( $tags[0] ) ),
            esc_html__( 'Открыть все записи по тегу', 'blok45' ),
            esc_html( $tags[0]->name )
        );

        return $output;
    }

    /**
     * Get list of post authors
     */
    public static function get_authors( $output = '' ) {
        global $authordata;

        if ( ! is_object( $authordata ) ) {
            return $output;
        }

        $class = 'meta__item';

        $output = sprintf(
            '<a class="%s" href="%s" rel="author">%s</a>',
            esc_attr( $class ),
            esc_url( get_author_posts_url( $authordata->ID, $authordata->user_nicename ) ),
            get_the_author()
        );

        return $output;
    }

    /**
     * Get post title with excerpt for single post
     */
    public static function get_title( $output = '' ) {
        $output = get_the_title();

        return $output;
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
    public static function get_date( $output = '' ) {
        $output = sprintf(
            '<span class="meta__item">%s</span>',
            esc_html( get_the_date( 'd.m' ) )
        );

        return $output;
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

    /**
     * Get custom lead from block
     */
    public static function get_reactions( $output = '' ) {
        if ( method_exists( 'Blok45_Modules_Reactions', 'get_list' ) ) {
            $list = Blok45_Modules_Reactions::get_list();

            $buttons = array();

            foreach ( $list as $item => $label ) {
                $buttons[] = sprintf(
                    '<button class="reactions__button" data-reaction="%s" aria-label="%s">%s</button>',
                    esc_attr( $item ),
                    esc_html( $label ),
                    sprintf(
                        '<svg class="reactions__icon"><use xlink:href="%s"></use></svg>',
                        esc_url( blok45_get_icon( $item ) )
                    )
                );
            }

            $output = sprintf(
                '<div class="reactions">%s</div>',
                implode( '', $buttons )
            );
        }

        return $output;
    }
}
