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

	const sortList = filtersRoot.querySelector( '.filters__list--sort, .filters__list[data-role="sort"]' );
	const filtersContainer = filtersRoot.closest( '.filters' );

	let mobileToggle = null;
	let mobilePanel = null;
	let mobileClose = null;

	if ( filtersContainer ) {
		mobileToggle = filtersContainer.querySelector( '.filters__toggle' );
		mobilePanel = filtersContainer.querySelector( '.filters__sheet' );
		mobileClose = filtersContainer.querySelector( '.filters__close' );
	}

	let mobileMedia = null;

	if ( typeof window.matchMedia === 'function' ) {
		mobileMedia = window.matchMedia( '(max-width: 767px)' );
	}

	const SORT_RATING = 'rating';
	const SORT_ID_DESC = 'reversed';

	const selectionState = Object.create( null );
	const defaultSelectionState = Object.create( null );
	let activeCoords = '';
	let currentSort = '';
	let defaultSort = '';
	let isFetching = false;
	let nextPage = Number( settings.startPage || 2 );
	let hasMore = true;

	const initialHashString = ( window.location.hash || '' ).replace( /^#/, '' );
	const initialSearchString = ( window.location.search || '' ).replace( /^\?/, '' );

	let urlParams;
	if ( initialHashString ) {
		urlParams = new window.URLSearchParams( initialHashString );
	} else {
		urlParams = new window.URLSearchParams( initialSearchString );
	}

	let currentHashString = initialHashString;
	let currentHistoryCoords = activeCoords;
	const hasInitialParams = Boolean( initialHashString ) || Boolean( initialSearchString );

	if ( settings.hasMore !== undefined ) {
		hasMore = Boolean( settings.hasMore );
	}

	if ( sortList ) {
		const defaultSortButton = sortList.querySelector( '.filters__item--active' ) || sortList.querySelector( '.filters__item' );
		defaultSort = ( defaultSortButton && ( defaultSortButton.dataset.sort || '' ).trim() ) || '';
		currentSort = defaultSort;

		const activeSortButton = sortList.querySelector( '.filters__item--active[data-sort]' );

		if ( activeSortButton ) {
			const initialSort = ( activeSortButton.dataset.sort || '' ).trim();
			const normalizedInitialSort = normalizeSortValue( initialSort );

			if ( normalizedInitialSort ) {
				currentSort = normalizedInitialSort;
			}
		}
	} else {
		defaultSort = '';
	}

	filtersRoot.querySelectorAll( '.filters__list--tax' ).forEach( function( list ) {
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
		defaultSelectionState[ tax ] = value;
	} );

	const loader = document.createElement( 'div' );
	loader.className = 'loadmore';

	const sentinel = document.createElement( 'div' );
	sentinel.className = 'loadmore__sentinel';

	listRoot.insertAdjacentElement( 'beforeend', sentinel );
	listRoot.insertAdjacentElement( 'beforeend', loader );

	let observer = null;
	const skeletonMinCount = Math.max( Number( settings.skeletonCount || 0 ), 24 );
	let isMobilePanelOpen = false;

	function normalizeSortValue( value ) {
		const normalized = ( value || '' ).trim();

		if ( normalized === SORT_RATING || normalized === SORT_ID_DESC ) {
			return normalized;
		}

		return '';
	}

	function shouldUseMobilePanel() {
		if ( ! filtersContainer || ! mobilePanel ) {
			return false;
		}

		if ( ! mobileMedia ) {
			return true;
		}

		return mobileMedia.matches;
	}

	function blurIfInsidePanel() {
		if ( mobilePanel && mobilePanel.matches( ':focus-within' ) ) {
			const focused = mobilePanel.querySelector( ':focus' );

			if ( focused && typeof focused.blur === 'function' ) {
				focused.blur();
			}
		}
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

		const scrollOffset = window.pageYOffset || document.documentElement.scrollTop || 0;
		document.body.dataset.scrollLock = String( scrollOffset );
		document.body.style.top = '-' + scrollOffset + 'px';
		document.body.classList.add( 'is-overflow' );

		if ( mobileToggle ) {
			mobileToggle.setAttribute( 'aria-expanded', 'true' );
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

		blurIfInsidePanel();

		let shouldRestoreFocus = true;
		if ( options && options.restoreFocus === false ) {
			shouldRestoreFocus = false;
		}

		isMobilePanelOpen = false;

		if ( filtersContainer ) {
			filtersContainer.classList.remove( 'filters--open' );
		}

		const scrollOffset = Number( document.body.dataset.scrollLock || 0 );
		let targetScroll = 0;
		if ( Number.isFinite( scrollOffset ) ) {
			targetScroll = scrollOffset;
		}

		document.body.classList.remove( 'is-overflow' );
		document.body.style.removeProperty( 'top' );
		delete document.body.dataset.scrollLock;

		window.scrollTo( {
			top: targetScroll,
			left: 0,
		} );

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

		const skeletonImage = document.createElement( 'div' );
		skeletonImage.className = 'card__skeleton-image';

		const skeletonLike = document.createElement( 'div' );
		skeletonLike.className = 'card__skeleton-like';

		const skeletonLine = document.createElement( 'span' );
		skeletonLine.className = 'card__skeleton-line';

		card.appendChild( skeletonImage );
		card.appendChild( skeletonLike );
		card.appendChild( skeletonLine );

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

	function buildParams( extraParams ) {
		const params = new window.URLSearchParams();

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

		return params;
	}

	function getHistorySnapshot() {
		const selection = {};

		Object.keys( selectionState ).forEach( function( tax ) {
			selection[ tax ] = selectionState[ tax ];
		} );

		return {
			coords: activeCoords,
			sort: currentSort,
			selection,
		};
	}

	function syncHistoryState( options ) {
		const config = options || {};
		const params = buildParams( { sort: getSortParam() } );
		const hashString = params.toString();

		let targetHash = '';
		if ( hashString.length > 0 ) {
			targetHash = '#' + hashString;
		}

		const targetUrl = window.location.pathname + targetHash;

		if ( ! window.history || typeof window.history.pushState !== 'function' ) {
			const currentHash = window.location.hash || '';

			if ( config.replace ) {
				if ( ! config.force && hashString === currentHashString && currentHash.replace( /^#/, '' ) === hashString && activeCoords === currentHistoryCoords ) {
					return;
				}

				try {
					window.location.replace( targetUrl || window.location.pathname );
				} catch ( error ) {
					window.location.hash = targetHash;
				}
			} else if ( currentHash !== targetHash ) {
				window.location.hash = targetHash;
			}

			currentHashString = hashString;
			currentHistoryCoords = activeCoords;
			return;
		}

		if ( config.replace ) {
			if ( ! config.force && hashString === currentHashString && window.location.hash.replace( /^#/, '' ) === hashString && activeCoords === currentHistoryCoords ) {
				return;
			}

			window.history.replaceState( getHistorySnapshot(), '', targetUrl );
			currentHashString = hashString;
			currentHistoryCoords = activeCoords;
			return;
		}

		if ( hashString === currentHashString && activeCoords === currentHistoryCoords ) {
			return;
		}

		window.history.pushState( getHistorySnapshot(), '', targetUrl );
		currentHashString = hashString;
		currentHistoryCoords = activeCoords;
	}

	function applyStateFromParams( params, options ) {
		let searchParams = null;

		if ( params instanceof window.URLSearchParams ) {
			searchParams = params;
		} else {
			searchParams = new window.URLSearchParams( params || '' );
		}

		const config = options || {};
		const previousSort = currentSort;
		const result = {
			changed: false,
			coordsChanged: false,
			coordsCleared: false,
		};

		let hasCoords = Boolean( activeCoords );
		if ( config.hasCoords !== undefined ) {
			hasCoords = Boolean( config.hasCoords );
		}

		let desiredSort = defaultSort;

		if ( ! hasCoords ) {
			const sortParam = ( searchParams.get( 'sort' ) || '' ).trim();
			const normalizedSort = normalizeSortValue( sortParam );

			if ( normalizedSort ) {
				desiredSort = normalizedSort;
			}
		}

		if ( desiredSort !== previousSort ) {
			result.changed = true;
		}

		currentSort = desiredSort;

		if ( sortList ) {
			const sortButtons = Array.from( sortList.querySelectorAll( '.filters__item' ) );
			let targetSortButton = sortButtons.find( function( button ) {
				return ( button.dataset.sort || '' ).trim() === desiredSort;
			} );

			if ( ! targetSortButton ) {
				targetSortButton = sortButtons.find( function( button ) {
					return ( button.dataset.sort || '' ).trim() === defaultSort;
				} );
			}

			if ( targetSortButton ) {
				activateListButton( sortList, targetSortButton );
			}
		}

		filtersRoot.querySelectorAll( '.filters__list[data-tax]' ).forEach( function( list ) {
			const tax = list.getAttribute( 'data-tax' );

			if ( ! tax ) {
				return;
			}

			const buttons = Array.from( list.querySelectorAll( '.filters__item' ) );
			let desiredValue = defaultSelectionState[ tax ] || '';

			if ( ! hasCoords && searchParams.has( tax ) ) {
				desiredValue = ( searchParams.get( tax ) || '' ).trim();
			}

			let targetButton = buttons.find( function( button ) {
				return ( button.dataset.value || '' ).trim() === desiredValue;
			} );

			if ( ! targetButton ) {
				desiredValue = defaultSelectionState[ tax ] || '';
				targetButton = buttons.find( function( button ) {
					return ( button.dataset.value || '' ).trim() === desiredValue;
				} );
			}

			if ( targetButton ) {
				activateListButton( list, targetButton );
			}

			if ( selectionState[ tax ] !== desiredValue ) {
				result.changed = true;
			}

			selectionState[ tax ] = desiredValue;
		} );

		return result;
	}

	function getSortParam() {
		const normalizedSort = normalizeSortValue( currentSort );

		if ( normalizedSort ) {
			return normalizedSort;
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
		loader.classList.add( 'loadmore--visible' );
	}

	function hideLoader() {
		loader.classList.remove( 'loadmore--visible' );
	}

	function updateObserver() {
		if ( observer ) {
			observer.disconnect();
		}

		if ( ! hasMore ) {
			hideLoader();
			return;
		}

		observer = new window.IntersectionObserver(
			function( entries ) {
				entries.forEach( function( entry ) {
					if ( entry.isIntersecting ) {
						fetchNextPage();
					}
				} );
			},
			{ rootMargin: '500px 0px', threshold: 0 }
		);

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

		if ( config.append ) {
			showLoader();
		} else {
			setBusy( true );
			clearEmptyState();
			showSkeletonCards();
		}

		try {
			const params = buildParams( { page, sort: getSortParam() } );
			if ( activeCoords ) {
				params.set( 'coords', activeCoords );
			}

			const query = params.toString();
			let requestUrl = endpoint;
			if ( query ) {
				requestUrl = endpoint + '?' + query;
			}

			const response = await fetch( requestUrl );

			if ( ! response.ok ) {
				throw new Error( 'Request failed' );
			}

			const data = await response.json();
			let html = '';

			if ( data && typeof data.html === 'string' ) {
				html = data.html;
			}

			if ( config.append ) {
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
		} catch ( error ) {
			hasMore = false;
		} finally {
			hideLoader();

			if ( ! config.append ) {
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
		currentSort = defaultSort;
	}

	function clearCoordsFilter() {
		if ( ! activeCoords ) {
			return false;
		}

		activeCoords = '';
		return true;
	}

	function updateCoordsState( nextCoords, options ) {
		const config = options || {};

		closeMobilePanel( { restoreFocus: false } );
		clearEmptyState();

		if ( config.deactivateMarker ) {
			deactivateMapMarker();
		}

		activeCoords = nextCoords;

		if ( config.resetFilters ) {
			resetTaxFilters();
			resetSortFilter();
		}

		nextPage = 2;
		hasMore = false;

		if ( config.skipHistory ) {
			currentHistoryCoords = activeCoords;
		} else {
			syncHistoryState();
		}

		scrollListToTop();
		fetchPage( { page: 1, append: false } );
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

			updateCoordsState( '', { deactivateMarker: true } );
			return;
		}

		if ( normalized === activeCoords ) {
			return;
		}

		updateCoordsState( normalized, { resetFilters: true } );
	}

	function applySortSelection( button, listWrap ) {
		const rawSort = ( button.dataset.sort || '' ).trim();
		let nextSort = normalizeSortValue( rawSort );

		if ( ! nextSort ) {
			nextSort = defaultSort;
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

		syncHistoryState();

		scrollListToTop();
		fetchPage( { page: 1, append: false } );
	}

	const initialState = applyStateFromParams( urlParams );

	syncHistoryState( { replace: true, force: true } );

	if ( initialState.coordsCleared ) {
		deactivateMapMarker();
	}

	if ( initialState.changed && hasInitialParams ) {
		nextPage = 2;
		hasMore = false;
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

		if ( button.dataset.navigation === 'true' ) {
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

		syncHistoryState();

		scrollListToTop();
		fetchPage( { page: 1, append: false } );
	} );

	function handleLocationChange( event ) {
		const locationHashString = ( window.location.hash || '' ).replace( /^#/, '' );
		const isPopState = Boolean( event && event.type === 'popstate' );

		if ( ! isPopState && locationHashString === currentHashString ) {
			return;
		}

		let historyCoords = '';
		if ( isPopState && event && event.state && typeof event.state.coords === 'string' ) {
			historyCoords = event.state.coords.replace( /\s+/g, '' );
		}

		const params = new window.URLSearchParams( locationHashString );

		let stateOptions;

		if ( isPopState ) {
			stateOptions = { hasCoords: Boolean( historyCoords ) };
		}

		const stateResult = applyStateFromParams( params, stateOptions );
		let shouldFetch = stateResult.changed;
		let shouldDeactivateMarker = stateResult.coordsCleared;

		if ( isPopState ) {
			const normalizedHistoryCoords = historyCoords;
			const coordsChanged = normalizedHistoryCoords !== activeCoords;

			if ( coordsChanged ) {
				if ( normalizedHistoryCoords ) {
					updateCoordsState( normalizedHistoryCoords, { skipHistory: true } );
				} else {
					updateCoordsState( '', { deactivateMarker: true, skipHistory: true } );
				}

				shouldFetch = false;
				shouldDeactivateMarker = false;
			}
		}

		if ( shouldDeactivateMarker ) {
			deactivateMapMarker();
		}

		if ( shouldFetch ) {
			closeMobilePanel( { restoreFocus: false } );
			clearEmptyState();
			nextPage = 2;
			hasMore = false;

			scrollListToTop();
			fetchPage( { page: 1, append: false } );
		}

		const sanitizedHash = buildParams( { sort: getSortParam() } ).toString();

		if ( sanitizedHash !== locationHashString ) {
			syncHistoryState( { replace: true, force: true } );
		} else {
			currentHashString = sanitizedHash;
			currentHistoryCoords = activeCoords;
		}
	}

	window.addEventListener( 'popstate', handleLocationChange );
	window.addEventListener( 'hashchange', handleLocationChange );

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
