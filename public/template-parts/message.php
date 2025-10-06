<?php
/**
 * No content template
 *
 * @package blok45
 * @since 1.0
 */
?>

<div class="message">
	<h1 class="message__title"><?php esc_html_e( 'Page not found', 'blok45' ); ?></h1>

	<div class="message__content">
		<p>
			<?php echo esc_html__( 'Sorry, the page you are looking for does not exist or has been moved. Please check the URL or return to the homepage', 'blok45' ); ?>
		</p>
	</div>
</div>
