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

	const formatter = new Intl.NumberFormat();

	function updateCount( button, rating ) {
		const counter = button.querySelector( '.like__count' );

		if ( counter ) {
			counter.dataset.rating = String( rating );
			counter.textContent = formatter.format( rating );
		}
	}

	function animateCount( button, target ) {
		const counter = button.querySelector( '.like__count' );

		if ( ! counter ) {
			return;
		}

		counter.dataset.rating = String( target );

		if ( target === 0 ) {
			counter.textContent = formatter.format( 0 );
			return;
		}

		const duration = 400;
		const start = performance.now();

		function step( now ) {
			const progress = Math.min( ( now - start ) / duration, 1 );
			const eased = 1 - Math.pow( 1 - progress, 3 );
			const current = Math.round( eased * target );

			counter.textContent = formatter.format( current );

			if ( progress < 1 ) {
				window.requestAnimationFrame( step );
			}
		}

		window.requestAnimationFrame( step );
	}

	function setBusyState( button, busy ) {
		if ( busy ) {
			button.classList.add( 'like--busy' );
			button.setAttribute( 'aria-disabled', 'true' );
		} else {
			button.classList.remove( 'like--busy' );
			button.removeAttribute( 'aria-disabled' );
		}
	}

	function applyButtonState( button, liked ) {
		button.classList.toggle( 'like--active', liked );
		button.setAttribute( 'aria-pressed', liked ? 'true' : 'false' );
	}

	function syncButton( button ) {
		const postId = Number( button.dataset.post );

		if ( Number.isNaN( postId ) || postId <= 0 ) {
			return;
		}

		applyButtonState( button, hasRated( postId ) );
	}

	function syncAllButtons() {
		document.querySelectorAll( '.like[data-post]' ).forEach( syncButton );
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
		const button = event.target.closest( '.like[data-post]' );

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

	const refreshedButtons = new WeakSet();

	function refreshRatings() {
		const buttons = document.querySelectorAll( '.like[data-post]' );
		const ids = [];
		const targetButtons = [];

		buttons.forEach( function( button ) {
			if ( refreshedButtons.has( button ) ) {
				return;
			}

			const id = Number( button.dataset.post );

			if ( ! Number.isNaN( id ) && id > 0 ) {
				if ( ids.indexOf( id ) === -1 ) {
					ids.push( id );
				}

				targetButtons.push( button );
			}
		} );

		if ( ! ids.length ) {
			return;
		}

		const url = endpoint.replace( /\/rating$/, '/ratings' ) + '?posts=' + ids.join( ',' );

		window.fetch( url, { credentials: 'same-origin' } )
			.then( function( response ) {
				return response.ok ? response.json() : null;
			} )
			.then( function( data ) {
				if ( ! data || ! data.ratings ) {
					return;
				}

				targetButtons.forEach( function( button ) {
					refreshedButtons.add( button );
				} );

				targetButtons.forEach( function( button ) {
					const postId = String( button.dataset.post );

					if ( data.ratings.hasOwnProperty( postId ) ) {
						animateCount( button, Number( data.ratings[ postId ] ) || 0 );
					}
				} );
			} )
			.catch( function() {} );
	}

	syncAllButtons();
	refreshRatings();
	window.addEventListener( 'blok45:cards-updated', function() {
		syncAllButtons();
		refreshRatings();
	} );
}() );
