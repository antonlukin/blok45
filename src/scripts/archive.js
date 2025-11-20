( function() {
	const settings = window.Blok45Archive || {};
	const archiveRoot = document.querySelector( '.archive' );

	if ( ! archiveRoot ) {
		return;
	}

	const listRoot = archiveRoot.querySelector( '.list' );

	if ( ! listRoot ) {
		return;
	}

	let hasMore = true;
	if ( settings.hasMore !== undefined ) {
		hasMore = Boolean( settings.hasMore );
	}

	const endpoint = ( settings.endpoint || '' ).trim();
	let baseParams = {};
	if ( settings.params && typeof settings.params === 'object' ) {
		baseParams = settings.params;
	}

	if ( ! endpoint ) {
		return;
	}

	const maxPages = Number( settings.maxPages || 0 );
	const currentPage = Number( settings.currentPage || 1 );

	let nextPage = Number( settings.startPage || currentPage + 1 );

	if ( ! hasMore || ! Number.isFinite( nextPage ) || ( maxPages > 0 && nextPage > maxPages ) ) {
		return;
	}

	const loader = document.createElement( 'div' );
	loader.className = 'loadmore';

	const sentinel = document.createElement( 'div' );
	sentinel.className = 'loadmore__sentinel';

	listRoot.insertAdjacentElement( 'beforeend', sentinel );
	listRoot.insertAdjacentElement( 'beforeend', loader );

	let isFetching = false;
	let observer = null;

	function buildRequestUrl( page ) {
		let url;

		try {
			url = new window.URL( endpoint, window.location.origin );
		} catch ( error ) {
			return '';
		}

		Object.keys( baseParams ).forEach( function( key ) {
			const value = baseParams[ key ];

			if ( value === null || value === undefined ) {
				return;
			}

			const stringValue = String( value ).trim();

			if ( stringValue.length > 0 ) {
				url.searchParams.set( key, stringValue );
			}
		} );

		url.searchParams.set( 'page', String( page ) );

		return url.toString();
	}

	function showLoader() {
		loader.classList.add( 'loadmore--visible' );
	}

	function hideLoader() {
		loader.classList.remove( 'loadmore--visible' );
	}

	function stopObserving() {
		if ( observer ) {
			observer.disconnect();
			observer = null;
		}

		if ( sentinel.parentNode ) {
			sentinel.parentNode.removeChild( sentinel );
		}

		if ( loader.parentNode ) {
			loader.parentNode.removeChild( loader );
		}

		hideLoader();
	}

	async function fetchNextPage( page ) {
		if ( ! hasMore || isFetching ) {
			return;
		}

		const requestUrl = buildRequestUrl( page );

		if ( ! requestUrl ) {
			hasMore = false;
			stopObserving();
			return;
		}

		isFetching = true;
		showLoader();

		try {
			const response = await window.fetch( requestUrl, {
				credentials: 'same-origin',
			} );

			if ( ! response.ok ) {
				throw new Error( 'Request failed' );
			}

			const data = await response.json();
			let html = '';
			if ( data && typeof data.html === 'string' ) {
				html = data.html;
			}
			const markup = html.trim();

			if ( markup.length === 0 ) {
				hasMore = false;
				return;
			}

			sentinel.insertAdjacentHTML( 'beforebegin', markup );
			window.dispatchEvent( new CustomEvent( 'blok45:cards-updated' ) );

			let resolvedPage = page;
			if ( data && data.page !== undefined ) {
				const parsedPage = Number( data.page );

				if ( Number.isFinite( parsedPage ) ) {
					resolvedPage = parsedPage;
				}
			}

			nextPage = resolvedPage + 1;

			if ( data && data.has_more !== undefined ) {
				hasMore = Boolean( data.has_more );
			} else if ( maxPages > 0 && resolvedPage >= maxPages ) {
				hasMore = false;
			}
		} catch ( error ) {
			hasMore = false;
		} finally {
			isFetching = false;
			hideLoader();

			if ( ! hasMore ) {
				stopObserving();
			}
		}
	}

	observer = new window.IntersectionObserver(
		function( entries ) {
			entries.forEach( function( entry ) {
				if ( entry.isIntersecting ) {
					fetchNextPage( nextPage );
				}
			} );
		},
		{ rootMargin: '200px 0px', threshold: 0 }
	);

	observer.observe( sentinel );
}() );
