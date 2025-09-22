/**
 * Combined taxonomy filters + infinite scroll for the front page list.
 */
( function() {
	const settings = window.B45Filters || {};
	const endpoint = settings.endpoint || '/wp-json/b45/v1/filter';

	const filtersRoot = document.querySelector( '.filters' );
	const listRoot = document.querySelector( '.archive--front .list' );

	if ( ! filtersRoot || ! listRoot ) {
		return;
	}

	const selections = Object.create( null );
	let isFetching = false;
	let nextPage = Number( settings.startPage || 2 );
	let hasMore = settings.hasMore !== undefined ? Boolean( settings.hasMore ) : true;

	const loader = document.createElement( 'div' );
	loader.className = 'loadmore';
	loader.style.display = 'none';

	const sentinel = document.createElement( 'div' );
	sentinel.className = 'loadmore__sentinel';

	listRoot.insertAdjacentElement( 'beforeend', sentinel );
	listRoot.insertAdjacentElement( 'beforeend', loader );

	let observer = null;

	function parseValues( button ) {
		const raw = ( button.dataset.value || '' ).trim();
		if ( ! raw ) {
			return [];
		}

		return raw
			.split( ',' )
			.map( function( part ) {
				return part.trim();
			} )
			.filter( function( value ) {
				return value.length > 0;
			} );
	}

	function recomputeSelection( tax ) {
		const activeButtons = filtersRoot.querySelectorAll( '.filters__list[data-tax="' + tax + '"] .filters__item--active' );
		const termSet = new Set();

		activeButtons.forEach( function( button ) {
			parseValues( button ).forEach( function( value ) {
				termSet.add( value );
			} );
		} );

		if ( termSet.size > 0 ) {
			selections[ tax ] = termSet;
		} else {
			delete selections[ tax ];
		}
	}

	function buildQuery( extraParams = {} ) {
		const params = new URLSearchParams();

		Object.keys( selections ).forEach( function( tax ) {
			const values = Array.from( selections[ tax ] );
			if ( values.length ) {
				params.set( tax, values.join( ',' ) );
			}
		} );

		Object.keys( extraParams ).forEach( function( key ) {
			const value = extraParams[ key ];
			if ( value === undefined || value === null || value === '' ) {
				return;
			}
			params.set( key, value );
		} );

		const query = params.toString();
		return query ? '?' + query : '';
	}

	function setBusy( state ) {
		filtersRoot.classList.toggle( 'filters--busy', state );
		filtersRoot.setAttribute( 'aria-busy', state ? 'true' : 'false' );

		const items = filtersRoot.querySelectorAll( '.filters__item' );
		items.forEach( function( el ) {
			if ( state ) {
				el.setAttribute( 'aria-disabled', 'true' );
				el.setAttribute( 'tabindex', '-1' );
			} else {
				el.removeAttribute( 'aria-disabled' );
				el.removeAttribute( 'tabindex' );
			}
		} );
	}

	function showLoader() {
		sentinel.insertAdjacentElement( 'afterend', loader );
		loader.style.display = 'flex';
	}

	function hideLoader() {
		loader.style.display = 'none';
	}

	function ensureHelpers() {
		if ( ! sentinel.isConnected ) {
			listRoot.insertAdjacentElement( 'beforeend', sentinel );
		}

		if ( ! loader.isConnected ) {
			listRoot.insertAdjacentElement( 'beforeend', loader );
		}
	}

	function updateObserver() {
		if ( observer ) {
			observer.disconnect();
		}

		if ( ! hasMore ) {
			hideLoader();
			return;
		}

		observer = new window.IntersectionObserver( function( entries ) {
			entries.forEach( function( entry ) {
				if ( entry.isIntersecting ) {
					fetchNextPage();
				}
			} );
		}, { rootMargin: '200px 0px', threshold: 0 } );

		observer.observe( sentinel );
	}

	async function fetchPage( options = {} ) {
		const page = options.page || 1;

		if ( isFetching ) {
			return;
		}

		isFetching = true;

		const append = Boolean( options.append );

		if ( append ) {
			showLoader();
		} else {
			setBusy( true );
			showLoader();
		}

		try {
			const query = buildQuery( { page } );
			const response = await fetch( endpoint + query );

			if ( ! response.ok ) {
				throw new Error( 'Request failed' );
			}

			const data = await response.json();
			const html = typeof data?.html === 'string' ? data.html : '';

			if ( append ) {
				sentinel.insertAdjacentHTML( 'beforebegin', html );
			} else {
				listRoot.innerHTML = html;
				ensureHelpers();
			}

			const resolvedPage = Number( data?.page ) || page;
			hasMore = Boolean( data?.has_more );
			nextPage = resolvedPage + 1;
		} catch ( e ) {
			hasMore = false;
		} finally {
			hideLoader();
			if ( ! append ) {
				setBusy( false );
			}
			isFetching = false;
			ensureHelpers();
			updateObserver();
		}
	}

	function fetchNextPage() {
		if ( ! hasMore || isFetching ) {
			return;
		}

		fetchPage( { page: nextPage, append: true } );
	}

	filtersRoot.addEventListener( 'click', function( event ) {
		if ( isFetching ) {
			return;
		}

		const button = event.target.closest( '.filters__item' );
		if ( ! button || button.getAttribute( 'aria-disabled' ) === 'true' ) {
			return;
		}

		const listWrap = button.closest( '.filters__list' );
		if ( ! listWrap ) {
			return;
		}

		const tax = listWrap.getAttribute( 'data-tax' );
		const values = parseValues( button );

		if ( ! tax || values.length === 0 ) {
			return;
		}

		button.classList.toggle( 'filters__item--active' );
		recomputeSelection( tax );

		nextPage = 2;
		hasMore = false;

		fetchPage( { page: 1, append: false } );
	} );

	updateObserver();
}() );
