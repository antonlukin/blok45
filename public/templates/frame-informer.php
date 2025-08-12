<?php
/**
 * Informer widget template
 *
 * Informer is an important single post with feature meta
 *
 * @package blok45
 * @since 1.0
 */
?>

<a class="frame-informer__wrapper" <?php echo implode( ' ', $args['attributes'] ); // phpcs:ignore ?>>
    <div class="frame-informer__content">
        <?php
        printf(
            '<p class="frame-informer__title">%s</p>',
            esc_html( $args['title'] )
        );

        if ( ! empty( $args['icon'] ) ) :
            printf(
                '<svg class="frame-informer__icon"><use xlink:href="%s"></use></svg>',
                esc_url( blok45_get_icon( $args['icon'] ) )
            );
        endif;
        ?>
    </div>
</a>
