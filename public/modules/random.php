<?php
/**
 * Random post
 * Redirect to random post on custom url
 *
 * @package blok45
 * @since 1.0
 */
if ( ! defined( 'WPINC' ) ) {
    die;
}

class Blok45_Modules_Random {
    /**
     * Archive query var
     *
     * @access  public
     * @var     string
     */
    public static $query_var = 'random';

    /**
     * Use this method instead of constructor to avoid multiple hook setting
     */
    public static function load_module() {
        add_action( 'init', array( __CLASS__, 'add_random_rule' ) );
        add_action( 'query_vars', array( __CLASS__, 'append_random_var' ) );
        add_filter( 'template_redirect', array( __CLASS__, 'redirect_random' ), 8 );
    }

    /**
     * Create custom best archive url
     */
    public static function add_random_rule() {
        add_rewrite_rule(
            sprintf( '^%s/?$', self::$query_var ),
            sprintf( 'index.php?%s=1', self::$query_var ),
            'top'
        );
    }

    /**
     * Append best query tag to availible query vars
     */
    public static function append_random_var( $query_vars ) {
        $query_vars[] = self::$query_var;

        return $query_vars;
    }

    /**
     * Redirect url to random post here
     */
    public static function redirect_random() {
        if ( ! get_query_var( self::$query_var ) ) {
            return;
        }

        $posts = get_posts(
            array(
                'post_type'      => 'post',
                'posts_per_page' => 1,
                'orderby'        => 'rand',
                'fields'         => 'ids',
            )
        );

        if ( empty( $posts ) ) {
            return;
        }

        wp_safe_redirect( get_permalink( $posts[0] ), 302 );
        exit;
    }
}

/**
 * Load current module environment
 */
Blok45_Modules_Random::load_module();
