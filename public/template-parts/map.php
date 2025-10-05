<?php
/**
 * Map template part
 *
 * @package blok45
 * @since 1.0
 */

$args = wp_parse_args(
	isset( $args ) ? $args : array(),
	array(
		'context' => 'default',
		'class'   => '',
		'coords'  => '',
		'zoom'    => '',
		'label'   => '',
	)
);

$classes = array( 'map' );

if ( ! empty( $args['class'] ) ) {
	$classes[] = $args['class'];
}

if ( 'single' === $args['context'] ) {
	$classes[] = 'map--single';
}

$classes = array_unique( array_filter( $classes ) );

$attributes = array();

foreach ( array( 'coords', 'zoom', 'label' ) as $attribute ) {
	if ( empty( $args[ $attribute ] ) ) {
		continue;
	}

	$attributes[] = sprintf( 'data-%1$s="%2$s"', esc_attr( $attribute ), esc_attr( $args[ $attribute ] ) );
}

$attr_string = $attributes ? ' ' . implode( ' ', $attributes ) : '';

?>
<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"<?php echo $attr_string; ?>>
	<div class="map__canvas"></div>

	<div class="map__zoom">
		<button class="map__zoom-button map__zoom-in" type="button">+</button>
		<button class="map__zoom-button map__zoom-out" type="button">−</button>
	</div>
</div>
