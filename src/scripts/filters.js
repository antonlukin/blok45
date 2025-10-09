/**
 * Combined taxonomy filters + infinite scroll for the front page list.
 */
( function() {
	const settings = window.Blok45Filters || {};
	const endpoint = settings.endpoint || '/wp-json/blok45/v1/filter';

	const filtersRoot = document.querySelector( '.filters' );
	if ( ! filtersRoot ) {
		return;
	}

	const listRoot = document.querySelector( '.list' );
	if ( ! listRoot ) {
		return;
	}

	const sortList = filtersRoot.querySelector( '.filters__list[data-role="sort"]' );
	const filtersContainer = filtersRoot.closest( '[data-filters-container]' );

	let mobileToggle = null;
	let mobilePanel = null;
	let mobileClose = null;
	let mobileSheetInner = null;

	if ( filtersContainer ) {
		mobileToggle = filtersContainer.querySelector( '[data-filters-toggle]' );
		mobilePanel = filtersContainer.querySelector( '[data-filters-panel]' );
		mobileClose = filtersContainer.querySelector( '[data-filters-close]' );
		mobileSheetInner = filtersContainer.querySelector( '.filters__sheet-inner' );
	}

	let mobileMedia = null;
	if ( typeof window.matchMedia === 'function' ) {
		mobileMedia = window.matchMedia( '(max-width: 767px)' );
	}

	const focusableSelector = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
	const SORT_RATING = 'rating';

	const selectionState = Object.create( null );
	let activeCoords = '';
	let currentSort = '';
	let isFetching = false;
	let nextPage = Number( settings.startPage || 2 );
	let hasMore = true;

	if ( settings.hasMore !== undefined ) {
		hasMore = Boolean( settings.hasMore );
	}

	if ( sortList ) {
		const activeSortButton = sortList.querySelector( '.filters__item--active[data-sort]' );
		let initialSort = '';

		if ( activeSortButton ) {
			initialSort = ( activeSortButton.dataset.sort || '' ).trim();
		}

		if ( initialSort === SORT_RATING ) {
			currentSort = SORT_RATING;
		}
	}

	filtersRoot.querySelectorAll( '.filters__list[data-tax]' ).forEach( function( list ) {
		const tax = list.getAttribute( 'data-tax' );

		if ( ! tax ) {
			return;
		}

		const activeButton = list.querySelector( '.filters__item--active' );
		let value = '';

		if ( activeButton ) {
			value = ( activeButton.dataset.value || '' ).trim();
		}

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
	let isMobilePanelOpen = false;

	function shouldUseMobilePanel() {
		if ( ! filtersContainer || ! mobilePanel ) {
			return false;
		}

		if ( ! mobileMedia ) {
			return true;
		}

		return mobileMedia.matches;
	}

	function openMobilePanel() {
		if ( isMobilePanelOpen ) {
			return;
		}

		if ( ! shouldUseMobilePanel() ) {
			return;
		}

		isMobilePanelOpen = true;
		filtersContainer.classList.add( 'filters--open' );
		document.body.classList.add( 'filters-open' );

		if ( mobilePanel ) {
			mobilePanel.setAttribute( 'aria-hidden', 'false' );
		}

		if ( mobileToggle ) {
			mobileToggle.setAttribute( 'aria-expanded', 'true' );
		}

		let focusTarget = null;
		if ( mobileSheetInner ) {
			focusTarget = mobileSheetInner.querySelector( focusableSelector );
		}

		if ( focusTarget ) {
			focusTarget.focus();
		}

		if ( mobilePanel && mobilePanel.querySelector( '.map' ) ) {
			window.setTimeout( function() {
				window.dispatchEvent( new Event( 'resize' ) );
			}, 200 );
		}
	}

	function closeMobilePanel( options ) {
		if ( ! isMobilePanelOpen ) {
			return;
		}

		let shouldRestoreFocus = true;
		if ( options && options.restoreFocus === false ) {
			shouldRestoreFocus = false;
		}

		isMobilePanelOpen = false;

		if ( filtersContainer ) {
			filtersContainer.classList.remove( 'filters--open' );
		}

		document.body.classList.remove( 'filters-open' );

		if ( mobilePanel ) {
			mobilePanel.setAttribute( 'aria-hidden', 'true' );
		}

		if ( mobileToggle ) {
			mobileToggle.setAttribute( 'aria-expanded', 'false' );
			if ( shouldRestoreFocus ) {
				mobileToggle.focus();
			}
		}
	}

	function toggleMobilePanel() {
		if ( isMobilePanelOpen ) {
			closeMobilePanel();
			return;
		}

		openMobilePanel();
	}

	function bindMobilePanelEvents() {
		if ( ! filtersContainer || ! mobilePanel || ! mobileToggle ) {
			return;
		}

		mobileToggle.addEventListener( 'click', toggleMobilePanel );

		if ( mobileClose ) {
			mobileClose.addEventListener( 'click', function() {
				closeMobilePanel();
			} );
		}

		mobilePanel.addEventListener( 'click', function( event ) {
			if ( event.target === mobilePanel ) {
				closeMobilePanel();
			}
		} );

		document.addEventListener( 'keydown', function( event ) {
			if ( event.key === 'Escape' || event.key === 'Esc' ) {
				closeMobilePanel();
			}
		} );

		if ( mobileMedia ) {
			const syncPanel = function( event ) {
				if ( event.matches ) {
					return;
				}

				closeMobilePanel( { restoreFocus: false } );
			};

			if ( typeof mobileMedia.addEventListener === 'function' ) {
				mobileMedia.addEventListener( 'change', syncPanel );
			} else if ( typeof mobileMedia.addListener === 'function' ) {
				mobileMedia.addListener( syncPanel );
			}
		}
	}

	function createSkeletonCard() {
		const card = document.createElement( 'div' );
		card.className = 'card card--skeleton';
		card.setAttribute( 'aria-hidden', 'true' );
		card.innerHTML = [
			'<div class="card__skeleton-image"></div>',
			'<div class="card__skeleton-like"></div>',
			'<span class="card__skeleton-line"></span>',
		].join( '' );
		return card;
	}

	function ensureHelpers() {
		if ( ! sentinel.isConnected ) {
			listRoot.insertAdjacentElement( 'beforeend', sentinel );
		}

		if ( ! loader.isConnected ) {
			sentinel.insertAdjacentElement( 'afterend', loader );
		}
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

		let insertBeforeNode = null;
		if ( sentinel.parentNode === listRoot ) {
			insertBeforeNode = sentinel;
		}

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

	function buildQuery( extraParams ) {
		const params = new window.URLSearchParams();

		if ( activeCoords ) {
			params.set( 'coords', activeCoords );
		}

		Object.keys( selectionState ).forEach( function( tax ) {
			const value = selectionState[ tax ];

			if ( value ) {
				params.set( tax, value );
			}
		} );

		if ( extraParams ) {
			Object.keys( extraParams ).forEach( function( key ) {
				const value = extraParams[ key ];
				const isEmpty = value === undefined || value === null || value === '';

				if ( ! isEmpty ) {
					params.set( key, value );
				}
			} );
		}

		const query = params.toString();

		if ( query.length > 0 ) {
			return '?' + query;
		}

		return '';
	}

	function getSortParam() {
		if ( currentSort === SORT_RATING ) {
			return SORT_RATING;
		}

		return '';
	}

	function setBusy( state ) {
		filtersRoot.classList.toggle( 'filters--busy', state );

		if ( state ) {
			filtersRoot.setAttribute( 'aria-busy', 'true' );
		} else {
			filtersRoot.setAttribute( 'aria-busy', 'false' );
		}

		filtersRoot.querySelectorAll( '.filters__item' ).forEach( function( element ) {
			if ( state ) {
				element.setAttribute( 'aria-disabled', 'true' );
				element.setAttribute( 'tabindex', '-1' );
			} else {
				element.removeAttribute( 'aria-disabled' );
				element.removeAttribute( 'tabindex' );
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
		const container = listRoot.closest( '.archive' );

		if ( container ) {
			window.scrollTo( {
				top: 0,
				left: 0,
				behavior: 'smooth',
			} );
		}
	}

	async function fetchPage( options ) {
		const config = options || {};
		let page = 1;

		if ( config.page ) {
			page = config.page;
		}

		if ( isFetching ) {
			return;
		}

		isFetching = true;

		const append = Boolean( config.append );

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
			let html = '';

			if ( data && typeof data.html === 'string' ) {
				html = data.html;
			}

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

					const headingEl = document.createElement( 'h3' );
					headingEl.className = 'list__empty-title';
					headingEl.textContent = settings.emptyHeading || '';
					emptyNode.appendChild( headingEl );

					const subheadingEl = document.createElement( 'p' );
					subheadingEl.className = 'list__empty-subtitle';
					subheadingEl.textContent = settings.emptySubheading || '';
					emptyNode.appendChild( subheadingEl );

					listRoot.appendChild( emptyNode );
				}

				ensureHelpers();
			}

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
			} else {
				hasMore = false;
			}
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
		if ( ! hasMore ) {
			return;
		}

		if ( isFetching ) {
			return;
		}

		fetchPage( { page: nextPage, append: true } );
	}

	function activateListButton( list, button ) {
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

			let defaultButton = list.querySelector( '.filters__item[data-value=""]' );
			if ( ! defaultButton ) {
				defaultButton = list.querySelector( '.filters__item' );
			}

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

		let defaultButton = sortList.querySelector( '.filters__item:not([data-sort])' );
		if ( ! defaultButton ) {
			defaultButton = sortList.querySelector( '.filters__item' );
		}

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
		let normalized = '';

		if ( typeof rawCoords === 'string' ) {
			normalized = rawCoords.replace( /\s+/g, '' );
		}

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
		let nextSort = '';

		if ( rawSort === SORT_RATING ) {
			nextSort = SORT_RATING;
		}

		if ( nextSort === currentSort ) {
			return;
		}

		closeMobilePanel( { restoreFocus: false } );

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

		if ( ! button ) {
			return;
		}

		if ( button.getAttribute( 'aria-disabled' ) === 'true' ) {
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

		closeMobilePanel( { restoreFocus: false } );

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
		let detail = {};

		if ( event && event.detail ) {
			detail = event.detail;
		}

		applyCoordsFilter( detail.coords );
	} );

	if ( Array.isArray( window.__blok45MapQueue ) && window.__blok45MapQueue.length > 0 ) {
		const pendingCoords = window.__blok45MapQueue.slice( 0 );
		window.__blok45MapQueue.length = 0;
		pendingCoords.forEach( applyCoordsFilter );
	}

	bindMobilePanelEvents();
	updateObserver();
}() );
