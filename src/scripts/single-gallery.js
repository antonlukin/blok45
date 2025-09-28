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

	document.querySelectorAll( '[data-single-swiper]' ).forEach( function( root ) {
		const topEl = root.querySelector( '[data-swiper="main"]' );

		if ( ! topEl ) {
			return;
		}

		const slidesCount = topEl.querySelectorAll( '.swiper-slide[data-index]' ).length;
		const hasMultiple = slidesCount > 1;
		let thumbsSwiper = null;

		const thumbsEl = root.querySelector( '[data-swiper="thumbs"]' );

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

		const overlay = topEl.querySelector( '[data-swiper-exif-panel]' );
		const exifFields = overlay ? Array.from( overlay.querySelectorAll( '[data-swiper-exif-field]' ) ) : [];
		const emptyNotice = overlay ? overlay.querySelector( '[data-swiper-exif-empty]' ) : null;
		const closeButton = overlay ? overlay.querySelector( '[data-swiper-exif-close]' ) : null;
		const placeholder = overlay ? overlay.getAttribute( 'data-swiper-exif-placeholder' ) || '—' : '—';
		const exifTrigger = topEl.querySelector( '[data-swiper-exif-trigger]' );
		const downloadLink = topEl.querySelector( '[data-swiper-download]' );
		let currentAttachment = 0;
		let currentDownloadUrl = '';
		let currentDownloadName = '';

		function getSlideByIndex( index ) {
			return root.querySelector( '[data-swiper="main"] .swiper-slide[data-index="' + index + '"]:not(.swiper-slide-duplicate)' );
		}

		function setDownloadLink() {
			if ( ! downloadLink ) {
				return;
			}

			if ( ! currentDownloadUrl ) {
				downloadLink.setAttribute( 'aria-disabled', 'true' );
				downloadLink.setAttribute( 'tabindex', '-1' );
				downloadLink.classList.add( 'is-disabled' );
				downloadLink.removeAttribute( 'href' );
				downloadLink.removeAttribute( 'download' );
				return;
			}

			downloadLink.classList.remove( 'is-disabled' );
			downloadLink.removeAttribute( 'aria-disabled' );
			downloadLink.removeAttribute( 'tabindex' );
			downloadLink.setAttribute( 'href', currentDownloadUrl );

			if ( currentDownloadName ) {
				downloadLink.setAttribute( 'download', currentDownloadName );
			} else {
				downloadLink.removeAttribute( 'download' );
			}
		}

		function closeOverlay() {
			if ( ! overlay ) {
				return;
			}

			overlay.hidden = true;
			overlay.setAttribute( 'aria-hidden', 'true' );
			overlay.classList.remove( 'is-visible' );

			if ( exifTrigger ) {
				exifTrigger.setAttribute( 'aria-expanded', 'false' );
				exifTrigger.classList.remove( 'is-open' );
			}
		}

		function applyExifMeta( meta ) {
			if ( ! overlay || exifFields.length === 0 ) {
				return;
			}

			let hasData = false;

			exifFields.forEach( function( field ) {
				const key = field.getAttribute( 'data-swiper-exif-field' );
				const value = key && meta[ key ] ? meta[ key ] : placeholder;

				field.textContent = value;

				const row = field.closest( '[data-swiper-exif-row]' );
				const isEmpty = value === placeholder;

				if ( row ) {
					row.classList.toggle( 'is-empty', isEmpty );
				}

				if ( ! isEmpty ) {
					hasData = true;
				}
			} );

			if ( emptyNotice ) {
				emptyNotice.style.display = hasData ? 'none' : '';
			}
		}

		function resetExifContent() {
			applyExifMeta( {} );
		}

		function setTriggerState( disabled ) {
			if ( ! exifTrigger ) {
				return;
			}

			if ( disabled ) {
				exifTrigger.classList.add( 'is-disabled' );
				exifTrigger.setAttribute( 'aria-disabled', 'true' );
			} else {
				exifTrigger.classList.remove( 'is-disabled' );
				exifTrigger.removeAttribute( 'aria-disabled' );
			}
		}

		function updateSlideState( index ) {
			const slide = getSlideByIndex( index );

			if ( ! slide ) {
				currentAttachment = 0;
				currentDownloadUrl = '';
				currentDownloadName = '';
				setTriggerState( true );
				slipReset();
				return;
			}

			currentAttachment = Number( slide.getAttribute( 'data-attachment' ) || 0 );
			currentDownloadUrl = slide.getAttribute( 'data-full' ) || '';
			const img = slide.querySelector( 'img' );
			const alt = img ? img.getAttribute( 'alt' ) : '';

			if ( currentDownloadUrl ) {
				try {
					const url = new URL( currentDownloadUrl, window.location.origin );
					const path = url.pathname ? url.pathname.split( '/' ).pop() : '';
					currentDownloadName = path || alt || '';
				} catch ( error ) {
					currentDownloadName = alt || '';
				}
			} else {
				currentDownloadName = '';
			}

			setDownloadLink();
			setTriggerState( currentAttachment <= 0 );
			closeOverlay();
			resetExifContent();

			if ( exifTrigger ) {
				exifTrigger.classList.remove( 'is-loading' );
				exifTrigger.removeAttribute( 'aria-busy' );
			}
		}

		function slipReset() {
			closeOverlay();
			resetExifContent();
			setDownloadLink();

			if ( exifTrigger ) {
				exifTrigger.classList.remove( 'is-loading' );
				exifTrigger.removeAttribute( 'aria-busy' );
			}
		}

		function openOverlay() {
			if ( ! overlay ) {
				return;
			}

			overlay.hidden = false;
			overlay.setAttribute( 'aria-hidden', 'false' );
			overlay.classList.add( 'is-visible' );

			if ( exifTrigger ) {
				exifTrigger.setAttribute( 'aria-expanded', 'true' );
				exifTrigger.classList.add( 'is-open' );
			}
		}

		const mainSwiper = new Swiper( topEl, config );

		if ( thumbsSwiper ) {
			mainSwiper.on( 'slideChangeTransitionStart', function() {
				thumbsSwiper.slideTo( mainSwiper.realIndex );
			} );
		}

		updateSlideState( mainSwiper.realIndex || 0 );
		mainSwiper.on( 'slideChange', function() {
			updateSlideState( mainSwiper.realIndex );
		} );

		if ( exifTrigger ) {
			exifTrigger.addEventListener( 'click', function( event ) {
				event.preventDefault();

				if ( exifTrigger.classList.contains( 'is-disabled' ) || currentAttachment <= 0 ) {
					return;
				}

				if ( overlay && overlay.classList.contains( 'is-visible' ) ) {
					closeOverlay();
					return;
				}

				const reveal = function( meta ) {
					applyExifMeta( meta || {} );
					openOverlay();
				};

				if ( exifCache.has( currentAttachment ) ) {
					reveal( exifCache.get( currentAttachment ) );
					return;
				}

				exifTrigger.classList.add( 'is-loading' );
				exifTrigger.setAttribute( 'aria-busy', 'true' );

				fetchExif( currentAttachment )
					.then( reveal )
					.finally( function() {
						exifTrigger.classList.remove( 'is-loading' );
						exifTrigger.removeAttribute( 'aria-busy' );
					} );
			} );
		}

		if ( closeButton ) {
			closeButton.addEventListener( 'click', function( event ) {
				event.preventDefault();
				closeOverlay();
			} );
		}

		if ( overlay ) {
			overlay.addEventListener( 'click', function( event ) {
				if ( event.target === overlay ) {
					closeOverlay();
				}
			} );
		}

		topEl.addEventListener( 'keydown', function( event ) {
			if ( 'Escape' === event.key && overlay && overlay.classList.contains( 'is-visible' ) ) {
				event.stopPropagation();
				closeOverlay();
			}
		} );
	} );
}() );
