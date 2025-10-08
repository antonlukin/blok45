<?php
/**
 * Map template part
 *
 * @package blok45
 * @since 1.0
 */

$data = blok45_get_map_args( $args );
?>

<div class="<?php echo esc_attr( $data['class'] ); ?>"<?php echo $data['coords'] ? ' data-coords="' . esc_attr( $data['coords'] ) . '"' : ''; ?>>
	<div class="map__canvas"></div>

	<div class="map__zoom">
		<button class="map__zoom-button map__zoom-in" type="button">+</button>
		<button class="map__zoom-button map__zoom-out" type="button">−</button>
	</div>
</div>
