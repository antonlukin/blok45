<?php
/**
 * Sharing buttons frame template
 *
 * @package blok45
 * @since 1.0
 */
?>

<div class="frame-sharing__wrapper">
    <?php
    printf(
        '<p class="frame-sharing__caption">%s</p>',
        esc_html( $args['caption'] )
    );
    ?>

    <div class="frame-sharing__buttons sharing">
        <?php
        printf(
            '<a class="sharing__link sharing__link--vkontakte" href="https://vk.com/share.php?url=%s&title=%s" target="_blank">%s%s</a>',
            rawurlencode( get_permalink() ),
            rawurlencode( html_entity_decode( get_the_title() ) ),
            sprintf(
                '<svg class="sharing__icon"><use xlink:href="%s"></use></svg>',
                esc_url( blok45_get_icon( 'vkontakte' ) )
            ),
            esc_html__( 'ВКонтакте', 'blok45' )
        );

        printf(
            '<a class="sharing__link sharing__link--telegram" href="https://t.me/share/url?url=%s&text=%s" target="_blank">%s%s</a>',
            rawurlencode( get_permalink() ),
            rawurlencode( html_entity_decode( get_the_title() ) ),
            sprintf(
                '<svg class="sharing__icon"><use xlink:href="%s"></use></svg>',
                esc_url( blok45_get_icon( 'telegram' ) )
            ),
            esc_html__( 'Телеграм', 'blok45' )
        );

        if ( empty( $args['minimal'] ) ) :
            printf(
                '<a class="sharing__link sharing__link--whatsapp" href="https://wa.me/?text=%s" target="_blank">%s%s</a>',
                rawurlencode( get_permalink() ),
                sprintf(
                    '<svg class="sharing__icon"><use xlink:href="%s"></use></svg>',
                    esc_url( blok45_get_icon( 'whatsapp' ) )
                ),
                esc_html__( 'Вотсапп', 'blok45' )
            );
        endif;

        printf(
            '<a class="sharing__link sharing__link--x" href="https://x.com/intent/tweet?url=%s&text=%s" target="_blank">%s%s</a>',
            rawurlencode( get_permalink() ),
            rawurlencode( html_entity_decode( get_the_title() ) ),
            sprintf(
                '<svg class="sharing__icon"><use xlink:href="%s"></use></svg>',
                esc_url( blok45_get_icon( 'x' ) )
            ),
            esc_html__( 'Сеть «Икс»', 'blok45' )
        );

        printf(
            '<button class="sharing__link sharing__link--copy" title="%s" data-url="%s">%s</button>',
            esc_attr__( 'Скопировать ссылку', 'blok45' ),
            esc_url( get_permalink() ),
            sprintf(
                '<svg class="sharing__icon sharing__icon--link"><use xlink:href="%s"></use></svg>',
                esc_url( blok45_get_icon( 'link' ) )
            ),
        )
        ?>
    </div>
</div>
