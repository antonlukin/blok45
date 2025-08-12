<?php
/**
 * Widget with subscribe to socials and mailing form
 *
 * @package blok45
 * @since 1.0
 */
?>

<div class="frame-subscribe__wrapper">
    <div class="frame-subscribe__hero">
        <?php
        if ( ! empty( $args['caption'] ) ) {
            printf(
                '<p class="frame-subscribe__caption">%s</p>',
                esc_html( $args['caption'] )
            );
        }

        if ( ! empty( $args['description'] ) ) {
            printf(
                '<p class="frame-subscribe__description">%s</p>',
                esc_html( $args['description'] )
            );
        }
        ?>
    </div>

    <div class="frame-subscribe__content">
    <?php
        wp_nav_menu(
            array(
                'theme_location' => 'social',
                'menu'           => $args['term_id'],
                'depth'          => 1,
                'echo'           => true,
                'items_wrap'     => '<ul class="frame-subscribe__social social">%3$s</ul>',
                'container'      => false,
            )
        );
        ?>

        <div class="frame-subscribe__fields">
            <?php
            if ( ! empty( $args['intro'] ) ) {
                printf(
                    '<p class="frame-subscribe__intro">%s</p>',
                    esc_html( $args['intro'] )
                );
            }
            ?>

            <form class="frame-subscribe__form form" action="/" method="POST" data-requests="mailing">
                <p class="form__message"></p>

                <?php
                printf(
                    '<input class="form__input" type="email" name="email" required placeholder="%s">',
                    esc_html__( 'Введите ваш e-mail', 'blok45' )
                );

                printf(
                    '<button class="form__button" type="submit">%s</button>',
                    esc_html__( 'Подписаться', 'blok45' )
                );

                if ( ! empty( $args['disclaimer'] ) ) {
                    printf(
                        '<label class="frame-subscribe__disclaimer"><input type="checkbox" required> <span>%s</span></label>',
                        wp_kses_post( links_add_target( $args['disclaimer'] ) )
                    );
                }
                ?>
            </form>
        </div>
    </div>
</div>
