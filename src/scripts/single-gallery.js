( function() {
	if ( typeof Swiper === 'undefined' ) {
		return;
	}

	const exifCache = new Map();
	const pendingRequests = new Map();

	function fetchExif( attachmentId ) {
		if ( ! attachmentId ) {
			return Promise.resolve( {} );
		}

		if ( exifCache.has( attachmentId ) ) {
			return Promise.resolve( exifCache.get( attachmentId ) );
		}

		if ( pendingRequests.has( attachmentId ) ) {
			return pendingRequests.get( attachmentId );
		}

		const request = window.fetch( '/wp-json/b45/v1/exif/' + attachmentId )
			.then( function( response ) {
				if ( ! response.ok ) {
					throw new Error( 'Request failed' );
				}
				return response.json();
			} )
			.then( function( payload ) {
				const meta = payload && payload.meta && typeof payload.meta === 'object' ? payload.meta : {};
				exifCache.set( attachmentId, meta );
				pendingRequests.delete( attachmentId );
				return meta;
			} )
			.catch( function() {
				pendingRequests.delete( attachmentId );
				return {};
			} );

		pendingRequests.set( attachmentId, request );
		return request;
	}

	document.querySelectorAll( '.gallery' ).forEach( function( root ) {
		const topEl = root.querySelector( '[data-gallery="main"]' );

		if ( ! topEl ) {
			return;
		}

		const slidesCount = topEl.querySelectorAll( '.swiper-slide[data-index]' ).length;
		const hasMultiple = slidesCount > 1;
		let thumbsSwiper = null;

		const thumbsEl = root.querySelector( '[data-gallery="thumbs"]' );

		if ( thumbsEl && hasMultiple ) {
			thumbsSwiper = new Swiper( thumbsEl, {
				direction: 'horizontal',
				spaceBetween: 8,
				slidesPerView: 'auto',
				freeMode: true,
				watchSlidesProgress: true,
				watchSlidesVisibility: true,
				slideToClickedSlide: true,
				breakpoints: {
					0: {
						spaceBetween: 6,
					},
					720: {
						spaceBetween: 8,
					},
				},
				a11y: {
					enabled: false,
				},
			} );
		}

		const config = {
			loop: hasMultiple,
			spaceBetween: 10,
			speed: 500,
			grabCursor: true,
			slidesPerView: 1,
			keyboard: {
				enabled: true,
				onlyInViewport: true,
			},
		};

		if ( hasMultiple ) {
			config.loopedSlides = slidesCount;
		}

		if ( hasMultiple ) {
			const prevEl = root.querySelector( '.swiper-button-prev' );
			const nextEl = root.querySelector( '.swiper-button-next' );

			if ( prevEl && nextEl ) {
				config.navigation = {
					prevEl,
					nextEl,
				};
			}
		} else {
			root.querySelectorAll( '.swiper-button-prev, .swiper-button-next' ).forEach( function( el ) {
				el.style.display = 'none';
			} );
		}

		if ( thumbsSwiper ) {
			config.thumbs = { swiper: thumbsSwiper };
		}

		let sidebar = null;
		let current = root.parentElement;

		while ( current && ! sidebar ) {
			sidebar = current.querySelector( '[data-gallery-sidebar]' );
			current = current.parentElement;
		}

		const exifFields = sidebar ? Array.from( sidebar.querySelectorAll( '[data-exif-field]' ) ) : [];
		const emptyNotice = sidebar ? sidebar.querySelector( '[data-exif-empty]' ) : null;
		const placeholder = sidebar ? ( sidebar.getAttribute( 'data-exif-placeholder' ) || '—' ) : '—';

		function applyExifMeta( meta ) {
			if ( ! sidebar || exifFields.length === 0 ) {
				return;
			}

			let hasData = false;

			exifFields.forEach( function( field ) {
				const key = field.getAttribute( 'data-exif-field' );
				const value = key && meta[ key ] ? meta[ key ] : placeholder;

				field.textContent = value;

				if ( field.parentElement ) {
					const isEmpty = value === placeholder;
					field.parentElement.classList.toggle( 'is-empty', isEmpty );
					if ( ! isEmpty ) {
						hasData = true;
					}
				}
			} );

			if ( emptyNotice ) {
				emptyNotice.style.display = hasData ? 'none' : '';
			}
		}

		function updateExifSidebar( index ) {
			if ( ! sidebar || exifFields.length === 0 ) {
				return;
			}

			const slide = root.querySelector( '[data-gallery="main"] .swiper-slide[data-index="' + index + '"]:not(.swiper-slide-duplicate)' );

			if ( ! slide ) {
				applyExifMeta( {} );
				return;
			}

			const attachment = Number( slide.getAttribute( 'data-attachment' ) || 0 );

			if ( attachment > 0 ) {
				applyExifMeta( {} );

				return;
			}

			applyExifMeta( {} );
		}

		const mainSwiper = new Swiper( topEl, config );

		if ( thumbsSwiper ) {
			mainSwiper.on( 'slideChangeTransitionStart', function() {
				thumbsSwiper.slideTo( mainSwiper.realIndex );
			} );
		}

		updateExifSidebar( mainSwiper.realIndex || 0 );
		mainSwiper.on( 'slideChange', function() {
			updateExifSidebar( mainSwiper.realIndex );
		} );
	} );
}() );
