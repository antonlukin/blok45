<?php
/**
 * The template for displaying the header
 *
 * @package blok45
 * @since 1.0
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="theme-color" content="#000000">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<header class="header">
	<a class="header__logo logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Homepage', 'blok45' ); ?>">
		Blok <span>45</span>
	</a>

	<div class="header__navbar">
		<div class="header__navbar-menu menu">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'header-menu',
					'container'      => false,
					'depth'          => 1,
					'items_wrap'     => '<ul class="menu__list">%3$s</ul>',
					'fallback_cb'    => false,
				)
			);
			?>
		</div>
	</div>
</header>
