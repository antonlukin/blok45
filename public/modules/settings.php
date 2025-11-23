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

	/**
	 * Bootstraps settings hooks.
	 */
	public static function load_module() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
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
	}

	/**
	 * Renders the unknown archive description field markup.
	 */
	public static function render_unknown_description_field() {
		$value = get_option( self::OPTION_UNKNOWN_ARCHIVE_MESSAGE, '' );

		printf(
			'<textarea id="%1$s" name="%1$s" rows="5" class="large-text">%2$s</textarea>',
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

		if ( '' === $description ) {
			return esc_html__( 'This section features graffiti whose authors we haven’t been able to identify yet. If you recognize a style, tag, or artist, feel free to suggest a correction — your input helps us keep the archive accurate.', 'blok45' );
		}

		return wp_kses_post( $description );
	}
}

Blok45_Modules_Settings::load_module();
