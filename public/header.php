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
    <meta name="apple-mobile-web-app-status-bar-style" content="#000000">

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
            if ( has_nav_menu( 'main' ) ) :
                wp_nav_menu(
                    array(
                        'theme_location' => 'main',
                        'depth'          => 1,
                        'echo'           => true,
                        'items_wrap'     => '%3$s',
                        'container'      => false,
                    )
                );
            endif;
            ?>

            <div class="menu__item">SR</div>
        </div>
    </div>
</header>
