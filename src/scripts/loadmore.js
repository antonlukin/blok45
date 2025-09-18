/**
 * Front-page infinite scroll: fetches next pages and appends cards
 *
 * Assumes global settings are provided in window.blok45_more before this script.
 */
( function() {
	const settings = window.blok45_more || {};
	const endpoint = settings.endpoint || '/wp-json/b45/v1/more';

	/** Root list container on the front page. */
	const list = document.querySelector( '.list' );

	if ( ! list ) {
		return;
	}

	let nextPage = Number( settings.startPage || 2 );

	let busy = false;
	let done = false;

	const loader = document.createElement( 'div' );
	loader.className = 'loadmore';
	loader.setAttribute( 'aria-hidden', 'true' );

	const sentinel = document.createElement( 'div' );
	sentinel.className = 'loadmore__sentinel';

	// Place UI helpers inside the list to not break the 2-column grid
	// Ensure sentinel is before loader so loader is always the last element
	list.insertAdjacentElement( 'beforeend', sentinel );
	list.insertAdjacentElement( 'beforeend', loader );

	loader.style.display = 'none';

	function showLoader() {
		sentinel.insertAdjacentElement( 'afterend', loader );
		loader.style.display = 'flex';
		busy = true;
	}

	function hideLoader() {
		loader.style.display = 'none';
		busy = false;
	}

	/**
	 * Fetches the next page and appends results before the sentinel.
	 */
	async function fetchMore() {
		if ( busy || done ) {
			return;
		}

		const finishLoading = () => {
			done = true;

			if ( sentinel?.parentNode ) {
				sentinel.parentNode.removeChild( sentinel );
			}

			if ( loader?.parentNode ) {
				loader.parentNode.removeChild( loader );
			}

			hideLoader();
		};

		showLoader();

		try {
			const response = await fetch( `${ endpoint }?page=${ nextPage }` );

			if ( ! response.ok ) {
				return finishLoading();
			}

			const data = await response.json();
			const html = typeof data?.html === 'string' ? data.html : '';

			if ( html.length > 0 ) {
				sentinel.insertAdjacentHTML( 'beforebegin', html );
				nextPage += 1;
			}

			hideLoader();

			if ( ! Boolean( data?.has_more ) ) {
				return finishLoading();
			}
		} catch ( e ) {
			return finishLoading();
		}
	}

	// Observer: requests more when sentinel intersects viewport.
	const io = new window.IntersectionObserver( function( entries ) {
		entries.forEach( function( entry ) {
			if ( entry.isIntersecting ) {
				fetchMore();
			}
		} );
	}, { rootMargin: '200px 0px', threshold: 0 } );

	io.observe( sentinel );
}() );
