<?php
/**
 * Theme settings stored via the Settings API.
 *
 * @package blok45
 * @since 1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Blok45_Modules_Settings {
	const OPTION_CORRECTION_URL          = 'blok45_correction_url';
	const OPTION_UNKNOWN_ARCHIVE_MESSAGE = 'blok45_unknown_archive_message';
	const OPTION_QR_UTM_QUERY            = 'blok45_qr_utm_query';

	/**
	 * Bootstraps settings hooks.
	 */
	public static function load_module() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'after_setup_theme', array( __CLASS__, 'disable_theme_customizer' ), 20 );
		add_action( 'admin_menu', array( __CLASS__, 'remove_customizer_menu_items' ), 999 );
		add_action( 'admin_bar_menu', array( __CLASS__, 'remove_customizer_from_admin_bar' ), 999 );
		add_action( 'load-customize.php', array( __CLASS__, 'block_customizer_screen' ) );
		add_filter( 'map_meta_cap', array( __CLASS__, 'block_customizer_capability' ), 10, 4 );
	}

	/**
	 * Registers the custom options on the General settings page.
	 */
	public static function register_settings() {
		register_setting(
			'general',
			self::OPTION_CORRECTION_URL,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);

		add_settings_field(
			self::OPTION_CORRECTION_URL,
			__( 'Correction form URL', 'blok45' ),
			array( __CLASS__, 'render_correction_field' ),
			'general',
			'default',
			array(
				'label_for' => self::OPTION_CORRECTION_URL,
			)
		);

		register_setting(
			'general',
			self::OPTION_UNKNOWN_ARCHIVE_MESSAGE,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
				'default'           => '',
			)
		);

		add_settings_field(
			self::OPTION_UNKNOWN_ARCHIVE_MESSAGE,
			__( 'Unknown artists description', 'blok45' ),
			array( __CLASS__, 'render_unknown_description_field' ),
			'general',
			'default',
			array(
				'label_for' => self::OPTION_UNKNOWN_ARCHIVE_MESSAGE,
			)
		);

		register_setting(
			'general',
			self::OPTION_QR_UTM_QUERY,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_qr_utm_query' ),
				'default'           => '',
			)
		);

		add_settings_field(
			self::OPTION_QR_UTM_QUERY,
			__( 'QR UTM parameters', 'blok45' ),
			array( __CLASS__, 'render_qr_utm_field' ),
			'general',
			'default',
			array(
				'label_for' => self::OPTION_QR_UTM_QUERY,
			)
		);
	}

	/**
	 * Renders the unknown archive description field markup.
	 */
	public static function render_unknown_description_field() {
		$value = get_option( self::OPTION_UNKNOWN_ARCHIVE_MESSAGE, '' );

		printf(
			'<textarea id="%1$s" name="%1$s" rows="5" class="regular-text">%2$s</textarea>',
			esc_attr( self::OPTION_UNKNOWN_ARCHIVE_MESSAGE ),
			esc_textarea( $value )
		);

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Shown on the Unknown Artists archive page. Leave empty to use the default theme text.', 'blok45' )
		);
	}

	/**
	 * Renders the correction URL field markup.
	 */
	public static function render_correction_field() {
		$value = get_option( self::OPTION_CORRECTION_URL, '' );

		printf(
			'<input type="url" id="%1$s" name="%1$s" value="%2$s" class="regular-text ltr" placeholder="%3$s">',
			esc_attr( self::OPTION_CORRECTION_URL ),
			esc_attr( $value ),
			esc_attr__( 'https://tally.so/r/mZqJDA?entry=%s', 'blok45' )
		);

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Used for the "Suggest an update" link on single posts.', 'blok45' )
		);

		printf(
			'<p class="description">%s</p>',
			sprintf(
				esc_html__( 'Use %1$s as a placeholder for the post permalink, for example %2$s.', 'blok45' ),
				'%s',
				'https://tally.so/r/mZqJDA?entry=%s'
			)
		);
	}

	/**
	 * Renders the QR UTM query field markup.
	 */
	public static function render_qr_utm_field() {
		$value = self::get_qr_utm_query();

		printf(
			'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text ltr" placeholder="%3$s">',
			esc_attr( self::OPTION_QR_UTM_QUERY ),
			esc_attr( $value ),
			esc_attr__( 'utm_source=qr&utm_medium=offline', 'blok45' )
		);

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Applied to QR short links like /qr/123/ before redirecting to the canonical object URL.', 'blok45' )
		);

		printf(
			'<p class="description">%s</p>',
			sprintf(
				esc_html__( 'Format: %s', 'blok45' ),
				'utm_source=qr&utm_medium=offline'
			)
		);
	}

	/**
	 * Returns the correction URL, applying the permalink placeholder if present.
	 *
	 * @param int|WP_Post|null $post Optional post reference.
	 */
	public static function get_correction_url( $post = null ) {
		$url = get_option( self::OPTION_CORRECTION_URL, '' );

		if ( empty( $url ) ) {
			$url = home_url( '/' );
		}

		if ( false === strpos( $url, '%s' ) ) {
			return $url;
		}

		$post = get_post( $post );

		if ( ! $post ) {
			return home_url( '/' );
		}

		$permalink = get_permalink( $post );

		if ( empty( $permalink ) ) {
			return home_url( '/' );
		}

		return sprintf( $url, rawurlencode( $permalink ) );
	}

	/**
	 * Returns the Unknown Artists archive description with a sensible fallback.
	 */
	public static function get_unknown_archive_description() {
		$description = trim( get_option( self::OPTION_UNKNOWN_ARCHIVE_MESSAGE, '' ) );

		return wp_kses_post( $description );
	}

	/**
	 * Returns the sanitized QR UTM query string.
	 *
	 * @return string
	 */
	public static function get_qr_utm_query() {
		return self::sanitize_qr_utm_query( get_option( self::OPTION_QR_UTM_QUERY, '' ) );
	}

	/**
	 * Normalizes a raw UTM query string.
	 *
	 * @param mixed $value Raw option value.
	 *
	 * @return string
	 */
	public static function sanitize_qr_utm_query( $value ) {
		$value = ltrim( trim( (string) $value ), '?' );

		if ( '' === $value ) {
			return '';
		}

		$parsed = array();
		wp_parse_str( $value, $parsed );

		if ( empty( $parsed ) || ! is_array( $parsed ) ) {
			return '';
		}

		$parsed = array_filter(
			$parsed,
			static function ( $item ) {
				return is_scalar( $item ) && '' !== trim( (string) $item );
			}
		);

		if ( empty( $parsed ) ) {
			return '';
		}

		return http_build_query( $parsed, '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * Removes Customizer-related theme support features.
	 */
	public static function disable_theme_customizer() {
		remove_theme_support( 'customize-selective-refresh-widgets' );
	}

	/**
	 * Removes Customizer entry from the Appearance menu.
	 */
	public static function remove_customizer_menu_items() {
		remove_submenu_page( 'themes.php', 'customize.php' );
	}

	/**
	 * Removes the Customizer link from the admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public static function remove_customizer_from_admin_bar( $wp_admin_bar ) {
		if ( ! $wp_admin_bar instanceof WP_Admin_Bar ) {
			return;
		}

		$wp_admin_bar->remove_menu( 'customize' );
	}

	/**
	 * Stops direct access to the Customizer screen.
	 */
	public static function block_customizer_screen() {
		wp_die(
			esc_html__( 'The Theme Customizer is disabled for this site.', 'blok45' ),
			403
		);
	}

	/**
	 * Denies the customize capability for every user.
	 *
	 * @param array  $caps    Primitive caps.
	 * @param string $cap     Capability being checked.
	 * @param int    $user_id User ID.
	 * @param array  $args    Extra arguments.
	 *
	 * @return array
	 */
	public static function block_customizer_capability( $caps, $cap, $user_id, $args ) {
		unset( $user_id, $args );

		if ( 'customize' === $cap ) {
			$caps = array( 'do_not_allow' );
		}

		return $caps;
	}
}

Blok45_Modules_Settings::load_module();
