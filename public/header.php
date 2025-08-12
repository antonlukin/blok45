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
<meta name="theme-color" content="#EEFF01">
<meta name="apple-mobile-web-app-status-bar-style" content="#EEFF01">

<?php wp_head(); ?>

</head>

<body <?php body_class(); ?>>

<header class="header">
    <div class="header__inner">
        <a class="header__logo logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Главная страница', 'blok45' ); ?>">
            <?php
            printf(
                '<strong>%s</strong>',
                esc_html__( 'Пчела', 'blok45' )
            );
            ?>
        </a>

        <div class="header__navbar">
            <?php
            if ( has_nav_menu( 'main' ) ) :
                wp_nav_menu(
                    array(
                        'theme_location' => 'main',
                        'depth'          => 1,
                        'echo'           => true,
                        'items_wrap'     => '<ul class="header__navbar-menu menu">%3$s</ul>',
                        'container'      => false,
                    )
                );
            endif;
            ?>
        </div>

        <?php
        if ( has_nav_menu( 'social' ) ) :
            wp_nav_menu(
                array(
                    'theme_location' => 'social',
                    'depth'          => 1,
                    'echo'           => true,
                    'items_wrap'     => '<ul class="header__social social">%3$s</ul>',
                    'container'      => false,
                )
            );
        endif;
        ?>

        <?php
        printf(
            '<a class="header__search" href="%s" aria-label="%s">%s</a>',
            esc_url( home_url( '/search/' ) ),
            esc_attr__( 'Поиск', 'blok45' ),
            sprintf(
                '<svg class="header__search-icon"><use xlink:href="%s"></use></svg>',
                esc_url( blok45_get_icon( 'search' ) )
            )
        );
        ?>
    </div>
</header>
