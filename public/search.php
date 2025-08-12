<?php
/**
 * Search template
 *
 * @package blok45
 * @since 1.0
 */

get_header(); ?>

<section class="archive">
    <form class="search" action="/search/" method="GET">
        <div class="caption caption--search">
            <?php
            printf(
                '<h1 class="caption__title">%s</h1>',
                esc_html__( 'Поиск 🎁', 'blok45' )
            );

            printf(
                '<p class="caption__description">%s</p>',
                esc_html__( 'Вы молодые, шутливые, вам все легко. Это не то. Это не Чикатило и даже не архивы спецслужб. Сюда лучше не лезть. Серьезно, любой из вас будет жалеть.', 'blok45' )
            );
            ?>
        </div>

        <div class="search__wrapper">
            <?php
            printf(
                '<input class="search__input" type="text" name="s" value="%s" placeholder="%s" required autofocus>',
                esc_html( get_search_query() ),
                esc_html__( 'Что вы ищете? Подумайте хорошенько', 'blok45' )
            );

            printf(
                '<button class="search__button" type="submit">%s</button>',
                esc_html__( 'Найти', 'blok45' )
            );
            ?>
        </div>

        <?php if ( ! have_posts() && ! empty( get_search_query() ) ) : ?>
            <div class="search__message">
                <p><?php echo wp_kses_post( __( 'Пока ничего не удалось найти', 'blok45' ) ); ?></p>
            </div>
        <?php endif; ?>
    </form>

    <?php if ( have_posts() ) : ?>
        <div class="archive">
        </div>

        <?php if ( get_next_posts_link() ) : ?>
            <nav class="navigate">
                <?php next_posts_link( esc_html__( 'Следующая страница', 'blok45' ) ); ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php
get_footer();
