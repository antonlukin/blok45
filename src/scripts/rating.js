/**
 * Simple front-page rating handler with toggle support.
 */
( function() {
	const settings = window.Blok45Rating || {};
	const endpoint = settings.endpoint || '/wp-json/blok45/v1/rating';
	const storageKey = settings.storageKey;
	const pending = new Set();
	let storeCache = null;

	function readStore() {
		if ( storeCache instanceof Set ) {
			return storeCache;
		}

		let parsed = [];

		try {
			const raw = window.localStorage.getItem( storageKey );
			parsed = raw ? JSON.parse( raw ) : [];
		} catch ( e ) {
			parsed = [];
		}

		if ( ! Array.isArray( parsed ) ) {
			parsed = [];
		}

		storeCache = new Set();

		parsed.forEach( function( value ) {
			const id = Number( value );

			if ( ! Number.isNaN( id ) && id > 0 ) {
				storeCache.add( id );
			}
		} );

		return storeCache;
	}

	function persistStore() {
		const snapshot = Array.from( readStore() );

		try {
			window.localStorage.setItem( storageKey, JSON.stringify( snapshot ) );
		} catch ( e ) {
			// Ignore storage errors.
		}
	}

	function hasRated( postId ) {
		return readStore().has( postId );
	}

	function markRated( postId ) {
		readStore().add( postId );
		persistStore();
	}

	function unmarkRated( postId ) {
		readStore().delete( postId );
		persistStore();
	}

	function updateCount( button, rating ) {
		const counter = button.querySelector( '.card__like-count' );

		if ( counter ) {
			counter.dataset.rating = String( rating );
			counter.textContent = new Intl.NumberFormat().format( rating );
		}
	}

	function setBusyState( button, busy ) {
		if ( busy ) {
			button.classList.add( 'card__like--busy' );
			button.setAttribute( 'aria-disabled', 'true' );
		} else {
			button.classList.remove( 'card__like--busy' );
			button.removeAttribute( 'aria-disabled' );
		}
	}

	function applyButtonState( button, liked ) {
		button.classList.toggle( 'card__like--active', liked );
		button.setAttribute( 'aria-pressed', liked ? 'true' : 'false' );
	}

	function syncButton( button ) {
		const postId = Number( button.dataset.post );

		if ( Number.isNaN( postId ) || postId <= 0 ) {
			return;
		}

		const counter = button.querySelector( '.card__like-count' );
		const rating = counter ? Number( counter.dataset.rating ) : Number.NaN;

		if ( ! Number.isNaN( rating ) ) {
			updateCount( button, rating );
		}

		const liked = hasRated( postId );
		applyButtonState( button, liked );
	}

	function syncAllButtons() {
		document.querySelectorAll( '.card__like[data-post]' ).forEach( syncButton );
	}

	async function sendRating( postId, liked ) {
		const response = await window.fetch( endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			credentials: 'same-origin',
			body: JSON.stringify( { post: postId, liked } ),
		} );

		if ( ! response.ok ) {
			throw new Error( 'Request failed' );
		}

		const data = await response.json();

		if ( typeof data?.rating !== 'number' ) {
			throw new Error( 'Bad payload' );
		}

		return {
			rating: Number( data.rating ) || 0,
			liked: Boolean( data.liked ),
		};
	}

	document.addEventListener( 'click', function( event ) {
		const button = event.target.closest( '.card__like[data-post]' );

		if ( ! button ) {
			return;
		}

		const postId = Number( button.dataset.post );

		if ( Number.isNaN( postId ) || postId <= 0 ) {
			return;
		}

		if ( pending.has( postId ) ) {
			return;
		}

		const nextLiked = ! hasRated( postId );

		pending.add( postId );
		setBusyState( button, true );

		sendRating( postId, nextLiked )
			.then( function( payload ) {
				if ( nextLiked ) {
					markRated( postId );
				} else {
					unmarkRated( postId );
				}

				updateCount( button, payload.rating );
				applyButtonState( button, nextLiked );
			} )
			.catch( function() {
				// Ignore errors for now.
			} )
			.finally( function() {
				pending.delete( postId );
				setBusyState( button, false );
			} );
	} );

	syncAllButtons();
	window.addEventListener( 'blok45:cards-updated', syncAllButtons );
}() );
