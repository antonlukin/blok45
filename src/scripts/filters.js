/**
 * Combined taxonomy filters + infinite scroll for the front page list.
 */
( function() {
	const settings = window.Blok45Filters || {};
	const endpoint = settings.endpoint || '/wp-json/blok45/v1/filter';

	const filtersRoot = document.querySelector( '.filters' );
	const listRoot = document.querySelector( '.list' );
	const sortList = filtersRoot ? filtersRoot.querySelector( '.filters__list[data-role="sort"]' ) : null;
	const ALLOWED_SORTS = new Set( [ 'oldest', 'newest', 'rating' ] );

	if ( ! filtersRoot || ! listRoot ) {
		return;
	}

	const selectionState = Object.create( null );

	let activeCoords = '';
	let currentSort = '';
	let isFetching = false;
	let nextPage = Number( settings.startPage || 2 );
	let hasMore = settings.hasMore !== undefined ? Boolean( settings.hasMore ) : true;

	if ( sortList ) {
		const activeSortButton = sortList.querySelector( '.filters__item--active[data-sort]' );
		const initialSort = activeSortButton ? ( activeSortButton.dataset.sort || '' ).trim() : '';
		currentSort = ALLOWED_SORTS.has( initialSort ) ? initialSort : '';
	}

	filtersRoot.querySelectorAll( '.filters__list[data-tax]' ).forEach( function( list ) {
		const tax = list.getAttribute( 'data-tax' );
		if ( ! tax ) {
			return;
		}

		const activeButton = list.querySelector( '.filters__item--active' );
		const value = activeButton ? ( activeButton.dataset.value || '' ).trim() : '';
		selectionState[ tax ] = value;
	} );

	const loader = document.createElement( 'div' );
	loader.className = 'loadmore';
	loader.style.display = 'none';

	const sentinel = document.createElement( 'div' );
	sentinel.className = 'loadmore__sentinel';

	listRoot.insertAdjacentElement( 'beforeend', sentinel );
	listRoot.insertAdjacentElement( 'beforeend', loader );

	let observer = null;
	const skeletonMinCount = Math.max( Number( settings.skeletonCount || 0 ), 24 );

	function createSkeletonCard() {
		const card = document.createElement( 'div' );
		card.className = 'card card--skeleton';
		card.setAttribute( 'aria-hidden', 'true' );
		card.innerHTML = [
			'<div class="card__skeleton-image"></div>',
			'<div class="card__skeleton-like"></div>',
			'<div class="card__skeleton-footer">',
			'<span class="card__skeleton-line card__skeleton-line--title card__skeleton-line--short"></span>',
			'</div>',
		].join( '' );
		return card;
	}

	function showSkeletonCards() {
		const currentCards = listRoot.querySelectorAll( '.card' ).length;
		const targetCount = Math.max( currentCards, skeletonMinCount );

		listRoot.querySelectorAll( '.card--skeleton' ).forEach( function( node ) {
			node.remove();
		} );

		ensureHelpers();

		const fragment = document.createDocumentFragment();
		for ( let index = 0; index < targetCount; index += 1 ) {
			fragment.appendChild( createSkeletonCard() );
		}

		const insertBeforeNode = sentinel.parentNode === listRoot ? sentinel : null;

		if ( insertBeforeNode ) {
			listRoot.insertBefore( fragment, insertBeforeNode );
		} else {
			listRoot.appendChild( fragment );
		}

		listRoot.classList.add( 'list--loading' );
	}

	function clearSkeletonCards() {
		listRoot.classList.remove( 'list--loading' );
		listRoot.querySelectorAll( '.card--skeleton' ).forEach( function( node ) {
			node.remove();
		} );
	}

	function buildQuery( extraParams = {} ) {
		const params = new URLSearchParams();

		if ( activeCoords ) {
			params.set( 'coords', activeCoords );
		}

		Object.keys( selectionState ).forEach( function( tax ) {
			const value = selectionState[ tax ];
			if ( value ) {
				params.set( tax, value );
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

	function getSortParam() {
		return ALLOWED_SORTS.has( currentSort ) ? currentSort : '';
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
			sentinel.insertAdjacentElement( 'afterend', loader );
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

	function scrollListToTop() {
		const container = listRoot.closest( '.archive' ) || listRoot;

		if ( ! container ) {
			return;
		}

		window.scrollTo( {
			top: 0,
			left: 0,
			behavior: 'smooth',
		} );
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
			clearEmptyState();
			showSkeletonCards();
		}

		try {
			const query = buildQuery( { page, sort: getSortParam() } );
			const response = await fetch( endpoint + query );

			if ( ! response.ok ) {
				throw new Error( 'Request failed' );
			}

			const data = await response.json();
			const html = typeof data?.html === 'string' ? data.html : '';

			if ( append ) {
				sentinel.insertAdjacentHTML( 'beforebegin', html );
			} else {
				const trimmed = html.trim();

				if ( trimmed.length > 0 ) {
					listRoot.innerHTML = html;
				} else {
					listRoot.innerHTML = '';
					const emptyNode = document.createElement( 'div' );
					emptyNode.className = 'list__empty';
					emptyNode.textContent = settings.emptyMessage || '';
					listRoot.appendChild( emptyNode );
				}

				ensureHelpers();
			}

			window.dispatchEvent( new CustomEvent( 'blok45:cards-updated' ) );

			const resolvedPage = Number( data?.page ) || page;
			hasMore = Boolean( data?.has_more );
			nextPage = resolvedPage + 1;
		} catch ( e ) {
			hasMore = false;
		} finally {
			hideLoader();
			if ( ! append ) {
				setBusy( false );
				clearSkeletonCards();
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

	function activateListButton( list, button ) {
		if ( ! list || ! button ) {
			return;
		}

		list.querySelectorAll( '.filters__item' ).forEach( function( item ) {
			item.classList.remove( 'filters__item--active' );
			item.setAttribute( 'aria-pressed', 'false' );
		} );

		button.classList.add( 'filters__item--active' );
		button.setAttribute( 'aria-pressed', 'true' );
	}

	function clearEmptyState() {
		listRoot.querySelectorAll( '.list__empty' ).forEach( function( node ) {
			node.remove();
		} );
	}

	function deactivateMapMarker() {
		if ( typeof window.__blok45DeactivateMarker === 'function' ) {
			window.__blok45DeactivateMarker();
		}
	}

	function resetTaxFilters() {
		filtersRoot.querySelectorAll( '.filters__list[data-tax]' ).forEach( function( list ) {
			const tax = list.getAttribute( 'data-tax' );
			if ( ! tax ) {
				return;
			}

			const defaultButton = list.querySelector( '.filters__item[data-value=""]' ) || list.querySelector( '.filters__item' );
			if ( ! defaultButton ) {
				return;
			}

			activateListButton( list, defaultButton );
			selectionState[ tax ] = ( defaultButton.dataset.value || '' ).trim();
		} );
	}

	function resetSortFilter() {
		if ( ! sortList ) {
			return;
		}

		const defaultButton = sortList.querySelector( '.filters__item:not([data-sort])' ) || sortList.querySelector( '.filters__item' );
		if ( ! defaultButton ) {
			return;
		}

		activateListButton( sortList, defaultButton );
		currentSort = '';
	}

	function clearCoordsFilter() {
		if ( ! activeCoords ) {
			return false;
		}

		activeCoords = '';
		return true;
	}

	function applyCoordsFilter( rawCoords ) {
		const normalized = typeof rawCoords === 'string' ? rawCoords.replace( /\s+/g, '' ) : '';

		if ( ! normalized ) {
			if ( ! activeCoords ) {
				return;
			}

			clearEmptyState();
			deactivateMapMarker();
			activeCoords = '';
			nextPage = 2;
			hasMore = false;

			scrollListToTop();
			fetchPage( { page: 1, append: false } );
			return;
		}

		clearEmptyState();
		activeCoords = normalized;
		resetTaxFilters();
		resetSortFilter();
		nextPage = 2;
		hasMore = false;

		scrollListToTop();
		fetchPage( { page: 1, append: false } );
	}

	function applySortSelection( button, listWrap ) {
		const rawSort = ( button.dataset.sort || '' ).trim();
		const nextSort = ALLOWED_SORTS.has( rawSort ) ? rawSort : '';

		if ( nextSort === currentSort ) {
			return;
		}

		clearEmptyState();
		deactivateMapMarker();
		clearCoordsFilter();

		listWrap.querySelectorAll( '.filters__item' ).forEach( function( item ) {
			item.classList.remove( 'filters__item--active' );
			item.setAttribute( 'aria-pressed', 'false' );
		} );

		button.classList.add( 'filters__item--active' );
		button.setAttribute( 'aria-pressed', 'true' );

		currentSort = nextSort;
		nextPage = 2;
		hasMore = false;

		scrollListToTop();
		fetchPage( { page: 1, append: false } );
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

		const role = listWrap.getAttribute( 'data-role' );

		if ( role === 'sort' ) {
			applySortSelection( button, listWrap );
			return;
		}

		const tax = listWrap.getAttribute( 'data-tax' );

		if ( ! tax ) {
			return;
		}

		const nextValue = ( button.dataset.value || '' ).trim();
		const currentValue = selectionState[ tax ] || '';

		if ( nextValue === currentValue ) {
			return;
		}

		clearEmptyState();
		deactivateMapMarker();
		clearCoordsFilter();

		activateListButton( listWrap, button );

		selectionState[ tax ] = nextValue;

		nextPage = 2;
		hasMore = false;

		scrollListToTop();
		fetchPage( { page: 1, append: false } );
	} );

window.addEventListener( 'blok45:map-select', function( event ) {
		const detail = event && event.detail ? event.detail : {};
		applyCoordsFilter( detail.coords );
	} );

if ( Array.isArray( window.__blok45MapQueue ) && window.__blok45MapQueue.length ) {
	const pendingCoords = window.__blok45MapQueue.slice( 0 );
	window.__blok45MapQueue.length = 0;
		pendingCoords.forEach( applyCoordsFilter );
	}

	updateObserver();
}() );
