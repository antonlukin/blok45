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
        add_action( 'customize_register', array( __CLASS__, 'update_customizer_settings' ) );

        add_action( 'wp_head', array( __CLASS__, 'add_og_tags' ), 5 );
        add_action( 'wp_head', array( __CLASS__, 'add_icons' ), 4 );
        add_action( 'wp_head', array( __CLASS__, 'add_seo_tags' ), 4 );
        add_action( 'admin_head', array( __CLASS__, 'add_icons' ), 4 );

        add_action( 'wp_head', array( __CLASS__, 'add_twitter_tags' ), 5 );

        // Add JSON-LD microdata
        add_action( 'wp_head', array( __CLASS__, 'add_singular_microdata' ), 25 );
        add_action( 'wp_head', array( __CLASS__, 'add_frontpage_microdata' ), 25 );
    }

    /**
     * Add og xmlns
     */
    public static function add_xmlns( $output ) {
        return 'prefix="og: http://ogp.me/ns#" ' . $output;
    }

    /**
     * Footer description option
     */
    public static function update_customizer_settings( $wp_customize ) {
        $wp_customize->add_setting( 'extra-copy' );
        $wp_customize->add_setting( 'extra-description' );

        $wp_customize->add_section(
            'blok45_extra',
            array(
                'title'    => esc_html__( 'Дополнительные настройки', 'blok45' ),
                'priority' => 160,
            )
        );

        $wp_customize->add_control(
            new WP_Customize_Code_Editor_Control(
                $wp_customize,
                'extra-description',
                array(
                    'label'     => esc_html__( 'Описание в подвале', 'blok45' ),
                    'section'   => 'blok45_extra',
                    'code_type' => 'text/html',
                    'priority'  => 10,
                )
            )
        );

        $wp_customize->add_control(
            new WP_Customize_Code_Editor_Control(
                $wp_customize,
                'extra-copy',
                array(
                    'label'     => esc_html__( 'Копирайт в подвале', 'blok45' ),
                    'section'   => 'blok45_extra',
                    'code_type' => 'text/html',
                    'priority'  => 10,
                )
            )
        );

        // Remove site icon customize setting
        $wp_customize->remove_control( 'site_icon' );
        $wp_customize->remove_section( 'static_front_page' );
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
                wp_get_document_title()
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
     * Add JSON-LD microdata for front page template
     */
    public static function add_frontpage_microdata() {
        if ( ! is_front_page() ) {
            return;
        }

        $schema = array(
            '@context' => 'http://schema.org',
            '@type'    => 'WebSite',
            'url'      => home_url( '/' ),
        );

        $schema['potentialAction'] = array(
            '@type'       => 'SearchAction',
            'target'      => home_url( '/search/{search_term_string}' ),
            'query-input' => 'required name=search_term_string',
        );

        printf(
            '<script type="application/ld+json">%s</script>',
            wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
        );
    }

    /**
     * Add JSON-LD microdata for singular templates
     */
    public static function add_singular_microdata() {
        if ( ! is_singular() || is_front_page() ) {
            return;
        }

        $schema = array(
            '@context' => 'http://schema.org',
            '@type'    => 'Article',
        );

        $post_id = get_queried_object_id();

        $schema['url']           = get_permalink( $post_id );
        $schema['@id']           = $schema['url'] . '#post-' . $post_id;
        $schema['datePublished'] = get_the_date( 'c', $post_id );
        $schema['dateModified']  = get_the_modified_date( 'c', $post_id );
        $schema['headline']      = wp_strip_all_tags( get_the_title( $post_id ) );

        if ( get_post_type( $post_id ) === 'post' ) {
            $content = get_the_content( null, false, $post_id );
            $content = preg_replace( '~[ \t\r\n]+~', ' ', $content );

            // Strip content tags
            $schema['text'] = wp_strip_all_tags( $content );
        }

        printf(
            '<script type="application/ld+json">%s</script>',
            wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
        );
    }

    /**
     * Get description
     */
    private static function get_description() {
        $description = wp_strip_all_tags( get_theme_mod( 'extra-description' ) );

        if ( is_singular() && ! is_front_page() ) {
            $object_id = get_queried_object_id();

            if ( has_excerpt( $object_id ) ) {
                return trim( wp_strip_all_tags( get_the_excerpt( $object_id ) ) );
            }

            return self::parse_excerpt( $object_id, $description );
        }

        if ( is_archive() ) {
            $object_type = get_queried_object();

            if ( ! empty( $object_type->description ) ) {
                return wp_strip_all_tags( $object_type->description, true );
            }

            if ( ! empty( $object_type->name ) ) {
                $description = sprintf( __( 'Раздел &laquo;%s&raquo;', 'blok45' ), wp_strip_all_tags( $object_type->name ) );
            }

            if ( ! empty( $object_type->label ) ) {
                $description = sprintf( __( 'Раздел &laquo;%s&raquo;', 'blok45' ), wp_strip_all_tags( $object_type->label ) );
            }

            if ( is_author() ) {
                $description = sprintf( __( 'Публикации автора: %s', 'blok45' ), wp_strip_all_tags( get_the_author() ) );
            }

            if ( get_query_var( 'paged' ) ) {
                $description = $description . sprintf( __( ' — Cтраница %d', 'blok45' ), get_query_var( 'paged' ) );
            }
        }

        return html_entity_decode( $description );
    }

    /**
     * Parse excerpt from content
     */
    private static function parse_excerpt( $post_id, $description ) {
        $blocks = parse_blocks( get_the_content( $post_id ) );

        foreach ( $blocks as $block ) {
            if ( $block['blockName'] === 'core/paragraph' ) {
                return wp_strip_all_tags( $block['innerHTML'] );
            }
        }

        return $description;
    }

    /**
     * Print tags if not empty array
     */
    private static function print_tags( $meta ) {
        foreach ( $meta as $tag ) {
            echo $tag . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
        }
    }

    private static function wrap_string( $str, $start, $end ) {
        return $start . $str . $end;
    }
}

/**
 * Load current module environment
 */
Blok45_Modules_Sitemeta::load_module();
