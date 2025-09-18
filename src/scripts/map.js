/**
 * Standalone Map initializer (built to assets/map.min.js)
 *
 * Assumes Mapbox GL assets are already loaded (enqueued via PHP),
 * and global settings are provided in window.blok45_map before this script.
 */

( function() {
	/**
	 * Parse "lat, lng" into numeric object.
	 * @param {string} str
	 */
	function parseCoords( str ) {
		const m = String( str ).trim().split( ',' );

		if ( m.length !== 2 ) {
			return null;
		}

		const lat = parseFloat( m[ 0 ] );
		const lng = parseFloat( m[ 1 ] );

		if ( ! Number.isFinite( lat ) || ! Number.isFinite( lng ) ) {
			return null;
		}

		return { lat, lng };
	}

	/**
	 * Fetch posts by coords and dispatch a 'b45:map-select' event.
	 * @param {string}             coordStr
	 * @param {{byCoords?:string}} endpoints
	 */
	async function handleMarkerClick( coordStr, endpoints ) {
		try {
			const url = ( endpoints && endpoints.byCoords );
			const response = await fetch( url + '?coords=' + encodeURIComponent( coordStr ) );
			const data = await response.json();

			console.log( 'Marker data:', data );
		} catch ( e ) {
			/* silent */
		}
	}

	/**
	 * Initialize a single `.map` block: creates Mapbox instance, loads points, adds markers.
	 * @param {HTMLElement} block
	 */
	async function initBlock( block ) {
		const settings = window.blok45_map || {};
		const accessToken = settings.accessToken || '';
		const style = settings.style || 'mapbox://styles/mapbox/streets-v12';
		const center = Array.isArray( settings.center ) ? settings.center : [ 0, 0 ];
		const endpoints = settings.endpoints || {};

		const canvas = block.querySelector( '.map__canvas' );

		if ( ! canvas || ! accessToken || ! window.mapboxgl ) {
			return;
		}

		window.mapboxgl.accessToken = accessToken;

		const map = new window.mapboxgl.Map( {
			container: canvas,
			style,
			center,
			zoom: Number( settings.zoom || 2 ),
			antialias: true,
			attributionControl: false,
			scrollZoom: false,
		} );

		const zoomIn = block.querySelector( '.map__zoom-in' );

		if ( zoomIn ) {
			zoomIn.addEventListener( 'click', function() {
				map.zoomIn( { duration: 200 } );
			} );
		}

		const zoomOut = block.querySelector( '.map__zoom-out' );

		if ( zoomOut ) {
			zoomOut.addEventListener( 'click', function() {
				map.zoomOut( { duration: 200 } );
			} );
		}

		// Load points and add markers
		try {
			const response = await fetch( endpoints.coords || '/wp-json/b45/v1/coords' );
			const items = await response.json();

			if ( ! Array.isArray( items ) ) {
				return;
			}

			const groups = new Map();

			items.forEach( function( it ) {
				const p = parseCoords( it.coords );

				if ( ! p ) {
					return;
				}

				const key = p.lat.toFixed( 6 ) + ', ' + p.lng.toFixed( 6 );

				if ( ! groups.has( key ) ) {
					groups.set( key, { pos: { lat: p.lat, lng: p.lng }, items: [] } );
				}

				groups.get( key ).items.push( it );
			} );

			groups.forEach( function( group, coordStr ) {
				const el = document.createElement( 'div' );
				el.className = 'map__pin';

				const pos = group.pos;

				new window.mapboxgl.Marker( { element: el } )
					.setLngLat( [ pos.lng, pos.lat ] )
					.addTo( map );

				el.addEventListener( 'click', function() {
					handleMarkerClick( coordStr, endpoints );
				} );
			} );
		} catch ( e ) {
			// silent
		}
	}

	const blocks = document.querySelectorAll( '.map' );
	if ( ! blocks || ! blocks.length ) {
		return;
	}
	blocks.forEach( initBlock );
}() );
