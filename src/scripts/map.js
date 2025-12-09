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

	let activeMarkerInstance = null;

	function deactivateActiveMarker() {
		if ( activeMarkerInstance && typeof activeMarkerInstance.getElement === 'function' ) {
			const markerEl = activeMarkerInstance.getElement();
			markerEl.classList.remove( 'map__pin--active' );
			markerEl.setAttribute( 'aria-pressed', 'false' );
		}

		activeMarkerInstance = null;
	}

	function createMarkerElement( label ) {
		const el = document.createElement( 'button' );
		el.type = 'button';
		el.className = 'map__pin';
		el.setAttribute( 'role', 'button' );
		el.setAttribute( 'aria-pressed', 'false' );

		if ( label ) {
			el.setAttribute( 'aria-label', label );
		}

		return el;
	}

	function setupDynamicResize( map, target ) {
		if ( ! map || typeof map.resize !== 'function' ) {
			return;
		}

		let rafId = 0;
		let isDestroyed = false;

		function scheduleResize() {
			if ( isDestroyed ) {
				return;
			}

			if ( rafId ) {
				window.cancelAnimationFrame( rafId );
			}

			rafId = window.requestAnimationFrame( function() {
				rafId = 0;
				map.resize();
			} );
		}

		map.on( 'load', function() {
			scheduleResize();
			setTimeout( scheduleResize, 200 );
		} );

		if ( typeof window.ResizeObserver === 'function' && target ) {
			const observer = new window.ResizeObserver( scheduleResize );
			observer.observe( target );

			map.on( 'remove', function() {
				isDestroyed = true;
				observer.disconnect();
			} );
		} else {
			window.addEventListener( 'resize', scheduleResize );
			window.addEventListener( 'orientationchange', scheduleResize );

			map.on( 'remove', function() {
				isDestroyed = true;
				window.removeEventListener( 'resize', scheduleResize );
				window.removeEventListener( 'orientationchange', scheduleResize );
			} );
		}
	}

	function setupResponsiveInteractions( map ) {
		if ( map && map.dragPan ) {
			map.dragPan.enable();
		}
	}

	function handleMarkerClick( coordStr, items, markerInstance ) {
		const isSameActive = markerInstance && activeMarkerInstance === markerInstance;

		if ( isSameActive ) {
			deactivateActiveMarker();

			window.dispatchEvent(
				new CustomEvent( 'blok45:map-select', {
					detail: {
						coords: '',
						items: Array.isArray( items ) ? items : [],
					},
				} )
			);

			return;
		}

		const normalized = String( coordStr || '' ).replace( /\s+/g, '' );

		if ( ! normalized ) {
			return;
		}

		if ( activeMarkerInstance && activeMarkerInstance !== markerInstance ) {
			deactivateActiveMarker();
		}

		if ( markerInstance && typeof markerInstance.getElement === 'function' ) {
			const el = markerInstance.getElement();
			el.classList.add( 'map__pin--active' );
			el.setAttribute( 'aria-pressed', 'true' );
			activeMarkerInstance = markerInstance;
		}

		if ( ! Array.isArray( window.__blok45MapQueue ) ) {
			window.__blok45MapQueue = [];
		}

		window.__blok45MapQueue.push( normalized );
		if ( window.__blok45MapQueue.length > 5 ) {
			window.__blok45MapQueue.shift();
		}

		window.dispatchEvent(
			new CustomEvent( 'blok45:map-select', {
				detail: {
					coords: normalized,
					items: Array.isArray( items ) ? items : [],
				},
			} )
		);
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
		const staticCoords = block.dataset.coords ? parseCoords( block.dataset.coords ) : null;
		const mapCenter = staticCoords ? [ staticCoords.lng, staticCoords.lat ] : center;
		const endpoints = settings.endpoints || {};

		const canvas = block.querySelector( '.map__canvas' );

		if ( ! canvas || ! accessToken || ! window.mapboxgl ) {
			return;
		}

		window.mapboxgl.accessToken = accessToken;

		const map = new window.mapboxgl.Map( {
			container: canvas,
			style,
			center: mapCenter,
			zoom: Number( settings.zoom || 2 ),
			antialias: true,
			attributionControl: false,
			scrollZoom: false,
		} );

		setupDynamicResize( map, block );
		setupResponsiveInteractions( map );

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

		if ( staticCoords ) {
			const markerEl = createMarkerElement();

			const lngLat = [ staticCoords.lng, staticCoords.lat ];

			new window.mapboxgl.Marker( { element: markerEl } )
				.setLngLat( lngLat )
				.addTo( map );

			return;
		}

		// Load points and add markers
		try {
			const response = await fetch( endpoints.coords || '/wp-json/blok45/v1/coords' );
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
				const normalizedCoords = String( it.coords || '' ).replace( /\s+/g, '' );

				if ( ! groups.has( key ) ) {
					groups.set( key, { pos: { lat: p.lat, lng: p.lng }, coords: normalizedCoords, items: [] } );
				}

				groups.get( key ).items.push( it );
			} );

			groups.forEach( function( group, coordStr ) {
				let markerLabel = '';
				if ( Array.isArray( group.items ) ) {
					const labeledItem = group.items.find( function( item ) {
						return item && item.title;
					} );

					if ( labeledItem && labeledItem.title ) {
						markerLabel = labeledItem.title;
					}
				}

				const el = createMarkerElement( markerLabel );

				const pos = group.pos;
				const emitCoords = group.coords || coordStr.replace( /\s+/g, '' );

				const marker = new window.mapboxgl.Marker( { element: el } )
					.setLngLat( [ pos.lng, pos.lat ] )
					.addTo( map );

				el.addEventListener( 'click', function() {
					handleMarkerClick( emitCoords, group.items, marker );
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

	window.__blok45DeactivateMarker = deactivateActiveMarker;
}() );
