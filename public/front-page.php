<?php
/**
 * Template for showing site front-page
 *
 * @package blok45
 * @since 1.0
 */

get_header();
?>

<section class="archive archive--front">
    <nav class="navigate">
        <?php
        printf(
            '<a class="navigate__button" href="%s">%s</a>',
            esc_url( home_url( '/articles/' ) ),
            esc_html__( 'Все статьи «Пчелы»', 'blok45' )
        );
        ?>
    </nav>
</section>

<?php
get_footer();
