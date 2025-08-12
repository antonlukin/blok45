<?php
/**
 * Frame template for single post
 *
 * @package blok45
 * @since 1.0
 */
?>

<a class="frame-running__link" href="<?php echo esc_url( get_permalink() ); ?>">
    <div class="frame-running__content">
        <?php
        for ( $i = 0; $i < 2; $i++ ) :
            printf(
                '<div class="frame-running__title">%s</div>',
                esc_html( get_the_title() )
            );
        endfor;
        ?>
    </div>
</a>
