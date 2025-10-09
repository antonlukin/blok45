( function() {
	if ( typeof Swiper === 'undefined' ) {
		return;
	}

	document.querySelectorAll( '[data-single-swiper]' ).forEach( function( root ) {
		const mainEl = root.querySelector( '[data-swiper="main"]' );

		if ( ! mainEl ) {
			return;
		}

		const slidesCount = mainEl.querySelectorAll( '.swiper-slide[data-index]' ).length;
		const hasMultiple = slidesCount > 1;
		let thumbsSwiper = null;

		const thumbsEl = root.querySelector( '[data-swiper="thumbs"]' );

		if ( thumbsEl && hasMultiple ) {
			thumbsSwiper = new window.Swiper( thumbsEl, {
				direction: 'horizontal',
				spaceBetween: 16,
				slidesPerView: 'auto',
				freeMode: true,
				watchSlidesProgress: true,
				watchSlidesVisibility: true,
				slideToClickedSlide: true,
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
			const prevEl = root.querySelector( '[data-swiper-nav="prev"]' );
			const nextEl = root.querySelector( '[data-swiper-nav="next"]' );

			if ( prevEl && nextEl ) {
				config.navigation = {
					prevEl,
					nextEl,
				};
			}
		} else {
			root.querySelectorAll( '[data-swiper-nav]' ).forEach( function( el ) {
				el.style.display = 'none';
			} );
		}

		if ( thumbsSwiper ) {
			config.thumbs = { swiper: thumbsSwiper };
		}

		const mainSwiper = new window.Swiper( mainEl, config );

		if ( thumbsSwiper ) {
			mainSwiper.on( 'slideChangeTransitionStart', function() {
				thumbsSwiper.slideTo( mainSwiper.realIndex );
			} );
		}
	} );
}() );
