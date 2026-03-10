/**
 * HM Instagram Story (frontend)
 * Full modal + auto slide + scroll arrows.
 */
( function () {
	function initWrapper( wrapper ) {
		if ( ! wrapper || wrapper.__hmproInited ) return;

		const dataAttr = wrapper.getAttribute( 'data-hm-stories' );
		if ( ! dataAttr ) return;

		let stories = [];
		try {
			stories = JSON.parse( dataAttr );
		} catch ( e ) {
			// eslint-disable-next-line no-console
			console.error( 'HM Instagram Story: invalid JSON', e );
			return;
		}

		if ( ! stories || ! stories.length ) return;

		const list = wrapper.querySelector( '.hmpro-is-list' );
		const items = wrapper.querySelectorAll( '.hmpro-is-item' );

		const modal = wrapper.querySelector( '.hmpro-is-modal' );
		const modalLabel = wrapper.querySelector( '.hmpro-is-modal-label' );
		const modalImage = wrapper.querySelector( '.hmpro-is-modal-image' );
		const modalTitle = wrapper.querySelector( '.hmpro-is-modal-title' );
		const modalLink = wrapper.querySelector( '.hmpro-is-modal-link' );
		const modalProgress = wrapper.querySelector( '.hmpro-is-modal-progress' );

		const modalClose = wrapper.querySelector( '.hmpro-is-modal-close' );
		const clickPrev = wrapper.querySelector( '.hmpro-is-modal-click-prev' );
		const clickNext = wrapper.querySelector( '.hmpro-is-modal-click-next' );

		const modalArrowPrev = wrapper.querySelector( '.modal-prev' );
		const modalArrowNext = wrapper.querySelector( '.modal-next' );

		const arrowPrev = wrapper.querySelector( '.hmpro-is-arrow.prev' );
		const arrowNext = wrapper.querySelector( '.hmpro-is-arrow.next' );

		let currentStoryIndex = 0;
		let currentSlideIndex = 0;

		let autoTimer = null;
		const autoTimeAttr = parseInt( wrapper.getAttribute( 'data-hm-auto' ) || '4000', 10 );
		const AUTO_TIME = Number.isFinite( autoTimeAttr ) ? Math.max( 1200, autoTimeAttr ) : 4000;

		function stopAutoSlide() {
			if ( autoTimer ) {
				clearInterval( autoTimer );
				autoTimer = null;
			}
		}

		function startAutoSlide() {
			stopAutoSlide();
			autoTimer = setInterval( () => {
				const story = stories[ currentStoryIndex ];
				if ( ! story ) return;

				if ( currentSlideIndex < story.slides.length - 1 ) {
					currentSlideIndex++;
				} else {
					if ( currentStoryIndex < stories.length - 1 ) {
						currentStoryIndex++;
						currentSlideIndex = 0;
					} else {
						closeModal();
						return;
					}
				}

				renderSlide();
			}, AUTO_TIME );
		}

		function updateActiveThumb() {
			items.forEach( ( item ) => item.classList.remove( 'is-active' ) );
			const active = wrapper.querySelector( '.hmpro-is-item[data-story-index="' + currentStoryIndex + '"]' );
			if ( active ) active.classList.add( 'is-active' );
		}

		function renderSlide() {
			const story = stories[ currentStoryIndex ];
			if ( ! story || ! story.slides || ! story.slides.length ) return;

			const slide = story.slides[ currentSlideIndex ];
			if ( ! slide ) return;

			if ( modalLabel ) modalLabel.textContent = story.label || '';
			if ( modalImage ) {
				modalImage.src = slide.image || '';
				modalImage.alt = story.label || '';
			}
			if ( modalTitle ) modalTitle.textContent = slide.title || '';

			if ( modalLink ) {
				if ( slide.link_url && slide.link_text ) {
					modalLink.hidden = false;
					modalLink.textContent = slide.link_text;
					modalLink.href = slide.link_url;

					let rel = 'noopener';
					if ( slide.link_nofollow ) rel += ' nofollow';
					modalLink.rel = rel;
					modalLink.target = slide.link_is_external ? '_blank' : '_self';
				} else {
					modalLink.hidden = true;
					modalLink.removeAttribute( 'href' );
				}
			}

			if ( modalProgress ) {
				const percent = ( ( currentSlideIndex + 1 ) / story.slides.length ) * 100;
				modalProgress.style.width = percent + '%';
			}

			updateActiveThumb();
		}

		function openModal( storyIndex ) {
			currentStoryIndex = storyIndex;
			currentSlideIndex = 0;
			renderSlide();
			startAutoSlide();

			wrapper.classList.add( 'hmpro-is-open' );
			if ( modal ) modal.setAttribute( 'aria-hidden', 'false' );
			document.body.classList.add( 'hmpro-is-no-scroll' );
		}

		function closeModal() {
			wrapper.classList.remove( 'hmpro-is-open' );
			if ( modal ) modal.setAttribute( 'aria-hidden', 'true' );
			document.body.classList.remove( 'hmpro-is-no-scroll' );
			stopAutoSlide();
		}

		function nextSlide() {
			const story = stories[ currentStoryIndex ];
			if ( ! story ) return;

			if ( currentSlideIndex < story.slides.length - 1 ) {
				currentSlideIndex++;
			} else {
				if ( currentStoryIndex < stories.length - 1 ) {
					currentStoryIndex++;
					currentSlideIndex = 0;
				} else {
					closeModal();
					return;
				}
			}
			renderSlide();
			startAutoSlide();
		}

		function prevSlide() {
			const story = stories[ currentStoryIndex ];
			if ( ! story ) return;

			if ( currentSlideIndex > 0 ) {
				currentSlideIndex--;
			} else {
				if ( currentStoryIndex > 0 ) {
					currentStoryIndex--;
					currentSlideIndex = stories[ currentStoryIndex ].slides.length - 1;
				} else {
					return;
				}
			}
			renderSlide();
			startAutoSlide();
		}

		items.forEach( ( item ) => {
			item.addEventListener( 'click', () => {
				const index = parseInt( item.getAttribute( 'data-story-index' ), 10 );
				if ( ! Number.isNaN( index ) ) openModal( index );
			} );
		} );

		if ( modalClose ) modalClose.addEventListener( 'click', closeModal );
		if ( modal ) {
			modal.addEventListener( 'click', ( e ) => {
				if ( e.target === modal ) closeModal();
			} );
		}

		if ( clickNext ) clickNext.addEventListener( 'click', ( e ) => { e.stopPropagation(); nextSlide(); } );
		if ( clickPrev ) clickPrev.addEventListener( 'click', ( e ) => { e.stopPropagation(); prevSlide(); } );
		if ( modalArrowNext ) modalArrowNext.addEventListener( 'click', ( e ) => { e.stopPropagation(); nextSlide(); } );
		if ( modalArrowPrev ) modalArrowPrev.addEventListener( 'click', ( e ) => { e.stopPropagation(); prevSlide(); } );

		document.addEventListener( 'keydown', ( e ) => {
			if ( ! wrapper.classList.contains( 'hmpro-is-open' ) ) return;
			if ( e.key === 'Escape' ) closeModal();
			if ( e.key === 'ArrowRight' ) nextSlide();
			if ( e.key === 'ArrowLeft' ) prevSlide();
		} );

		if ( arrowNext && list ) {
			arrowNext.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				list.scrollBy( { left: 180, behavior: 'smooth' } );
			} );
		}
		if ( arrowPrev && list ) {
			arrowPrev.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				list.scrollBy( { left: -180, behavior: 'smooth' } );
			} );
		}

		wrapper.__hmproInited = true;
	}

	function initAll() {
		const wrappers = document.querySelectorAll( '.hmpro-is-wrapper[data-hm-stories]' );
		if ( ! wrappers || ! wrappers.length ) return;
		wrappers.forEach( initWrapper );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}
} )();
