<?php
/**
 * Required footer file
 *
 * @package blok45
 * @since 1.0
 */
?>

<footer class="footer">
    <div class="footer__inner">
        <div class="footer__about">
            <a class="footer__about-logo logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Главная страница', 'blok45' ); ?>">
                <?php
                printf(
                    '<strong>%s</strong>',
                    esc_html__( 'Пчела', 'blok45' )
                );
                ?>
            </a>

            <div class="footer__about-description">
                <?php echo wp_kses_post( get_theme_mod( 'extra-description' ) ); ?>
            </div>
        </div>

        <div class="footer__navbar">
            <?php
            if ( has_nav_menu( 'footer' ) ) :
                wp_nav_menu(
                    array(
                        'theme_location' => 'footer',
                        'depth'          => 1,
                        'echo'           => true,
                        'items_wrap'     => '<ul class="footer__navbar-menu menu">%3$s</ul>',
                        'container'      => false,
                    )
                );
            endif;

            if ( has_nav_menu( 'social' ) ) :
                wp_nav_menu(
                    array(
                        'theme_location' => 'social',
                        'depth'          => 1,
                        'echo'           => true,
                        'items_wrap'     => '<ul class="footer__navbar-social social">%3$s</ul>',
                        'container'      => false,
                    )
                );
            endif;
            ?>
        </div>

        <div class="footer__copy">
            <?php echo wp_kses_post( get_theme_mod( 'extra-copy' ) ); ?>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>