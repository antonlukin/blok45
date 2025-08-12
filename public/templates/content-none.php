<?php
/**
 * No content template
 *
 * @package blok45
 * @since 1.0
 */
?>

<div class="message">
    <h1 class="message__title"><?php esc_html_e( '404', 'blok45' ); ?></h1>

    <div class="message__content">
        <p>
            <?php echo esc_html__( 'Вы ошиблись, понимаете, запутались, промахнулись. Как видите, черт подери, вы сбились с пути. Елки-палки, да вы дали маху!', 'blok45' ); ?>
        </p>
    </div>

    <div class="message__notes">
        <p>
            <?php
            printf(
                wp_kses_post( __( 'Сходите лучше <a href="%1$s">на главную страницу</a> или почитайте <a href="%2$s">случайную статью</a>.', 'blok45' ) ),
                esc_url( home_url( '/' ) ),
                esc_url( home_url( '/random/' ) ),
            );
            ?>
        </p>
    </div>
</div>
