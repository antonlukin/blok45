<?php
/**
 * Reactions buttons
 *
 * @package blok45
 * @since 1.0
 */
if ( ! defined( 'WPINC' ) ) {
    die;
}

class Blok45_Modules_Reactions {
    /**
     * Use this method instead of constructor to avoid multiple hook setting
     */
    public static function load_module() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
        add_action( 'init', array( __CLASS__, 'create_table' ) );
    }

    /**
     * Get list of reactions
     */
    public static function get_list() {
        $reactions = array(
            'heart' => esc_html__( 'Сердечко', 'blok45' ),
            'cup'   => esc_html__( 'Кубок', 'blok45' ),
            'smile' => esc_html__( 'Смайлик', 'blok45' ),
            'fire'  => esc_html__( 'Огонь', 'blok45' ),
            'lamp'  => esc_html__( 'Лампочка', 'blok45' ),
        );

        return $reactions;
    }

    /**
     * Register requests routers
     */
    public static function register_rest_routes() {
        register_rest_route(
            'blok45-reactions/v1',
            '/entry',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'add_post_reaction' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'post'     => array(
                        'required' => true,
                        'type'     => 'integer',
                    ),
                    'reaction' => array(
                        'required' => true,
                        'type'     => 'string',
                    ),
                ),
            )
        );

        register_rest_route(
            'blok45-reactions/v1',
            '/entry',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_post_reactions' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'post' => array(
                        'required' => true,
                        'type'     => 'integer',
                    ),
                ),
            )
        );
    }

    /**
     * Get post reactions
     */
    public static function get_post_reactions( $request ) {
        global $wpdb;

        $post_id = absint( $request->get_param( 'post' ) );

        $result = $wpdb->get_row( // phpcs:ignore
            $wpdb->prepare(
                "SELECT heart, cup, smile, fire, lamp FROM {$wpdb->prefix}reactions WHERE post_id = %d",
                $post_id
            ),
            ARRAY_A
        );

        if ( ! $result ) {
            $result = array_fill_keys( array( 'heart', 'cup', 'smile', 'fire', 'lamp' ), 0 );
        }

        foreach ( $result as &$value ) {
            $value = min( $value, 999 );
        }

        return new WP_REST_Response( $result, 200 );
    }

    /**
     * Add reaction
     */
    public static function add_post_reaction( $request ) {
        global $wpdb;

        $post_id  = absint( $request->get_param( 'post' ) );
        $reaction = sanitize_key( $request->get_param( 'reaction' ) );

        // Set available reactions list
        $valid = array( 'heart', 'cup', 'smile', 'fire', 'lamp' );

        if ( ! in_array( $reaction, $valid, true ) ) {
            return new WP_REST_Response( array( 'message' => esc_html__( 'Некорректный тип реакции', 'blok45' ) ), 400 );
        }

        $values = array_fill_keys( $valid, 0 );

        // Set current reactions
        $values[ $reaction ] = 1;

        $params = array_merge( array( $post_id ), array_values( $values ) );

        // phpcs:disable
        $result = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$wpdb->prefix}reactions (post_id, heart, cup, smile, fire, lamp)
                VALUES (%d, %d, %d, %d, %d, %d) ON DUPLICATE KEY UPDATE {$reaction} = {$reaction} + 1",
                $params
            )
        );
        // phpcs:enable

        if ( $result === false ) {
            return new WP_REST_Response( array( 'message' => esc_html__( 'Ошибка записи в базу данных', 'blok45' ) ), 500 );
        }

        return new WP_REST_Response( null, 200 );
    }

    /**
     * Create custom table on theme switch
     */
    public static function create_table() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $query = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}reactions (
            id int(11) NOT NULL AUTO_INCREMENT,
            post_id int(11) NOT NULL UNIQUE,
            heart int(11) NOT NULL DEFAULT 0,
            cup int(11) NOT NULL DEFAULT 0,
            smile int(11) NOT NULL DEFAULT 0,
            fire int(11) NOT NULL DEFAULT 0,
            lamp int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id)
        )
        DEFAULT CHARACTER SET {$wpdb->charset}";

        // We do not use dbDelta here cause of DESCRIBE error
        $wpdb->query( $query ); // phpcs:ignore
    }
}

/**
 * Load current module environment
 */
Blok45_Modules_Reactions::load_module();
