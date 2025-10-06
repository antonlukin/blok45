<?php
/**
 * Theme filters
 * Common snippets for theme modifications
 *
 * @package blok45
 * @since 1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Blok45_Modules_Global {
	/**
	 * Use this method instead of constructor to avoid multiple hook setting
	 */
	public static function load_module() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		add_action( 'after_switch_theme', 'flush_rewrite_rules' );
		add_action( 'after_setup_theme', array( __CLASS__, 'update_theme_settings' ), 12 );
		add_action( 'init', array( __CLASS__, 'disable_default_taxonomies' ), 100 );
		add_action( 'admin_menu', array( __CLASS__, 'remove_fse_menu' ), 12 );

		add_filter( 'get_the_archive_title', array( __CLASS__, 'update_archive_title' ) );
		add_filter( 'body_class', array( __CLASS__, 'update_body_classes' ) );
		add_filter( 'post_class', array( __CLASS__, 'update_post_classes' ), 10, 2 );
		add_action( 'get_header', array( __CLASS__, 'remove_adminbar_styles' ) );
		add_filter( 'feed_links_show_comments_feed', '__return_false' );
		add_filter( 'posts_search', array( __CLASS__, 'hide_empty_search' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'hide_useless_functions' ) );

		// Remove auto suggestions
		add_filter( 'do_redirect_guess_404_permalink', '__return_false' );
		add_filter( 'rest_endpoints', array( __CLASS__, 'remove_users_endpoint' ) );

		// Remove emojis handlers
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_action( 'wp_head', 'wp_print_font_faces', 50 );

		// Remove wp_head default actions
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_resource_hints', 2 );
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
		remove_action( 'wp_head', 'wp_site_icon', 99 );

		// Disables the block editor from managing widgets in the Gutenberg plugin.
		add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' );
		add_filter( 'use_widgets_block_editor', '__return_false' );
	}

	/**
	 * Disable default post categories and tags for the site.
	 */
	public static function disable_default_taxonomies() {
		unregister_taxonomy_for_object_type( 'category', 'post' );
		unregister_taxonomy_for_object_type( 'post_tag', 'post' );
	}

	/**
	 * Add required theme support tags
	 */
	public static function update_theme_settings() {
		add_theme_support( 'title-tag' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'customize-selective-refresh-widgets' );

		remove_theme_support( 'core-block-patterns' );
		remove_theme_support( 'block-templates' );

		add_filter( 'should_load_remote_block_patterns', '__return_false' );
	}

	/**
	 * Remove Full Site Editing menu for non-admin users
	 */
	public static function remove_fse_menu() {
		remove_submenu_page( 'themes.php', 'site-editor.php' );
	}

	/**
	 * Add required theme styles
	 */
	public static function enqueue_styles() {
		$version = filemtime( get_template_directory() . '/assets/styles.min.css' );

		wp_enqueue_style( 'blok45', get_template_directory_uri() . '/assets/styles.min.css', array(), $version );
	}

	/**
	 * Add required theme scripts
	 */
	public static function enqueue_scripts() {
		$version = filemtime( get_template_directory() . '/assets/scripts.min.js' );

		wp_enqueue_script( 'blok45', get_template_directory_uri() . '/assets/scripts.min.js', array( 'wp-i18n' ), $version, true );
	}

	/**
	 * Remove users endpoint from reset api.
	 */
	public static function remove_users_endpoint( $endpoints ) {
		if ( ! is_user_logged_in() ) {
			unset( $endpoints['/wp/v2/users'] );
			unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
		}

		return $endpoints;
	}

	/**
	 * Hide useless dashboard widgets
	 */
	public static function hide_useless_functions() {
		remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_primary', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );

		remove_submenu_page( 'themes.php', 'site-editor.php?path=/patterns' );
		remove_action( 'admin_head', 'wp_site_icon' );
	}

	/**
	 * Halt the main query in the case of an empty search
	 */
	public static function hide_empty_search( $search, $query ) {
		if ( is_admin() ) {
			return $search;
		}

		if ( empty( $search ) && $query->is_search() && $query->is_main_query() ) {
			return $search . ' AND 0=1 ';
		}

		return $search;
	}

	/**
	 * Update annoying body classes
	 *
	 * @link https://github.com/WordPress/WordPress/blob/81500e50eff289e2f5601135707c22c03625a192/wp-includes/post-template.php#L590
	 */
	public static function update_body_classes() {
		$classes = array();

		if ( is_single() ) {
			$classes[] = 'is-single';
		}

		if ( is_archive() ) {
			$classes[] = 'is-archive';
		}

		if ( is_admin_bar_showing() ) {
			$classes[] = 'is-adminbar';
		}

		if ( is_front_page() ) {
			$classes[] = 'is-front';
		}

		if ( is_singular( 'page' ) && ! is_front_page() ) {
			$classes[] = 'is-page';
		}

		if ( is_singular( 'post' ) ) {
			$classes[] = 'is-post';
		}

		if ( ! have_posts() ) {
			$classes[] = 'is-empty';
		}

		return $classes;
	}

	/**
	 * Remove admin-bar styles
	 */
	public static function remove_adminbar_styles() {
		remove_action( 'wp_head', '_admin_bar_bump_cb' );
	}

	/**
	 * Set custom post classes using only post format
	 */
	public static function update_post_classes( $classes, $name ) {
		return $name;
	}

	/**
	 * Custom archive title
	 */
	public static function update_archive_title( $title ) {
		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();

			if ( $term instanceof WP_Term ) {
				return $term->name;
			}
		}

		if ( is_author() ) {
			return get_the_author();
		}

		if ( is_post_type_archive() ) {
			return post_type_archive_title( '', false );
		}

		return $title;
	}
}

/**
 * Load current module environment
 */
Blok45_Modules_Global::load_module();
