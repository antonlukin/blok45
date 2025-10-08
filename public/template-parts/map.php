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
		'wrapper_class'      => 'map',
		'wrapper_attributes' => array(),
	)
);

$wrapper_class      = trim( (string) $args['wrapper_class'] );
$wrapper_attributes = is_array( $args['wrapper_attributes'] ) ? $args['wrapper_attributes'] : array();

if ( '' === $wrapper_class ) {
	return;
}

$attributes_string = '';

foreach ( $wrapper_attributes as $attribute => $value ) {
	$attributes_string .= sprintf( ' %1$s="%2$s"', esc_attr( $attribute ), esc_attr( $value ) );
}

?>
<div class="<?php echo esc_attr( $wrapper_class ); ?>"<?php echo $attributes_string; ?>>
	<div class="map__canvas"></div>

	<div class="map__zoom">
		<button class="map__zoom-button map__zoom-in" type="button">+</button>
		<button class="map__zoom-button map__zoom-out" type="button">−</button>
	</div>
</div>
