<?php
/**
 * Login filters
 * Change login screen template
 *
 * @package blok45
 * @since 1.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class Blok45_Modules_Login {
    /**
     * Use this method instead of constructor to avoid multiple hook setting
     */
    public static function load_module() {
        add_filter( 'login_headerurl', array( __CLASS__, 'change_url' ) );
        add_filter( 'login_headertext', array( __CLASS__, 'change_title' ) );
    }

    /**
     * Change logo links to front page instead of wordpress.org
     */
    public static function change_url() {
        return home_url();
    }

    /**
     * Change logo title
     */
    public static function change_title() {
        return esc_html__( 'На главную', 'blok45' );
    }
}

/**
 * Load current module environment
 */
Blok45_Modules_Login::load_module();
