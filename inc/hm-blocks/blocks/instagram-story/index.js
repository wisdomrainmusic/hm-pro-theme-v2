( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { __ } = wp.i18n;
	const {
		InspectorControls,
		MediaUpload,
		MediaUploadCheck,
		useBlockProps,
	} = wp.blockEditor || wp.editor;
	const {
		PanelBody,
		ToggleControl,
		TextControl,
		RangeControl,
		Button,
		Notice,
		BaseControl,
		ColorPalette,
	} = wp.components;

	const MAX_STORIES = 24;
	const MAX_SLIDES = 4;

	function normalizeInt( val, fallback ) {
		const n = parseInt( String( val ).replace( ',', '.' ), 10 );
		return Number.isFinite( n ) ? n : fallback;
	}

	function createEmptySlide() {
		return {
			imageId: 0,
			imageUrl: '',
			title: '',
			linkText: '',
			linkUrl: '',
			newTab: true,
			nofollow: true,
		};
	}

	function createEmptyStory() {
		return {
			label: '',
			thumbnailId: 0,
			thumbnailUrl: '',
			slides: [ createEmptySlide() ],
		};
	}

	function safeArray( v ) {
		return Array.isArray( v ) ? v : [];
	}

	function clampSlides( slides ) {
		const arr = safeArray( slides ).slice( 0, MAX_SLIDES );
		return arr.length ? arr : [ createEmptySlide() ];
	}

	registerBlockType( 'hmpro/instagram-story', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const {
				fullWidth,
				bubbleSize,
				itemGap,
				labelColor,
				labelFontSize,
				modalTitleColor,
				modalLinkColor,
				modalLinkBg,
				autoTime,
				contentScaleDesktop,
				contentScaleMobile,
				stories,
			} = attributes;

			const safeStories = safeArray( stories ).slice( 0, MAX_STORIES ).map( ( s ) => ( {
				...createEmptyStory(),
				...( s || {} ),
				slides: clampSlides( ( s || {} ).slides ),
			} ) );

			// Keep attributes normalized.
			if ( safeArray( stories ).length !== safeStories.length ) {
				setAttributes( { stories: safeStories } );
			}

			function updateStory( index, patch ) {
				const next = safeStories.slice( 0 );
				next[ index ] = Object.assign( {}, next[ index ] || createEmptyStory(), patch );
				// ensure slides always clamped
				next[ index ].slides = clampSlides( next[ index ].slides );
				setAttributes( { stories: next } );
			}

			function removeStory( index ) {
				const next = safeStories.slice( 0 );
				next.splice( index, 1 );
				setAttributes( { stories: next } );
			}

			function addStory() {
				if ( safeStories.length >= MAX_STORIES ) return;
				const next = safeStories.slice( 0 );
				next.push( createEmptyStory() );
				setAttributes( { stories: next } );
			}

			function updateSlide( storyIndex, slideIndex, patch ) {
				const story = safeStories[ storyIndex ] || createEmptyStory();
				const slides = clampSlides( story.slides );
				const nextSlides = slides.slice( 0 );
				nextSlides[ slideIndex ] = Object.assign( {}, nextSlides[ slideIndex ] || createEmptySlide(), patch );
				updateStory( storyIndex, { slides: nextSlides } );
			}

			function addSlide( storyIndex ) {
				const story = safeStories[ storyIndex ] || createEmptyStory();
				const slides = clampSlides( story.slides );
				if ( slides.length >= MAX_SLIDES ) return;
				const nextSlides = slides.slice( 0 );
				nextSlides.push( createEmptySlide() );
				updateStory( storyIndex, { slides: nextSlides } );
			}

			function removeSlide( storyIndex, slideIndex ) {
				const story = safeStories[ storyIndex ] || createEmptyStory();
				const slides = clampSlides( story.slides );
				const nextSlides = slides.slice( 0 );
				nextSlides.splice( slideIndex, 1 );
				updateStory( storyIndex, { slides: clampSlides( nextSlides ) } );
			}

			const blockProps = useBlockProps( {
				className: [
					'hmpro-block',
					'hmpro-instagram-story',
					fullWidth ? 'hmpro-is--fullwidth' : '',
				].filter( Boolean ).join( ' ' ),
				style: {
					'--hm-is-bubble': ( normalizeInt( bubbleSize, 68 ) || 68 ) + 'px',
					'--hm-is-gap': ( normalizeInt( itemGap, 24 ) || 24 ) + 'px',
					'--hm-is-label-color': labelColor || '#111111',
					'--hm-is-label-fs': ( normalizeInt( labelFontSize, 13 ) || 13 ) + 'px',
				},
			} );

			// Editor preview: no modal/JS; just the highlight list.
			const previewStories = safeStories.length ? safeStories : [
				{
					label: __( 'Highlight 1', 'hm-pro-theme' ),
					thumbnailUrl: '',
					slides: [ { imageUrl: '' } ],
				},
				{
					label: __( 'Highlight 2', 'hm-pro-theme' ),
					thumbnailUrl: '',
					slides: [ { imageUrl: '' } ],
				},
			];

			return wp.element.createElement(
				wp.element.Fragment,
				null,
				wp.element.createElement(
					InspectorControls,
					null,

					wp.element.createElement(
						PanelBody,
						{ title: __( 'Layout', 'hm-pro-theme' ), initialOpen: true },
						wp.element.createElement( ToggleControl, {
							label: __( 'Full Width (bleed)', 'hm-pro-theme' ),
							checked: !! fullWidth,
							onChange: function ( v ) {
								setAttributes( { fullWidth: !! v } );
							},
						} ),
						wp.element.createElement( RangeControl, {
							label: __( 'Highlight Bubble Size (px)', 'hm-pro-theme' ),
							min: 40,
							max: 200,
							value: normalizeInt( bubbleSize, 68 ),
							onChange: function ( v ) {
								setAttributes( { bubbleSize: normalizeInt( v, 68 ) } );
							},
						} ),
						wp.element.createElement( RangeControl, {
							label: __( 'Highlight Item Gap (px)', 'hm-pro-theme' ),
							min: 0,
							max: 40,
							value: normalizeInt( itemGap, 24 ),
							onChange: function ( v ) {
								setAttributes( { itemGap: normalizeInt( v, 24 ) } );
							},
						} ),
						wp.element.createElement( RangeControl, {
							label: __( 'Label Font Size (px)', 'hm-pro-theme' ),
							min: 10,
							max: 20,
							value: normalizeInt( labelFontSize, 13 ),
							onChange: function ( v ) {
								setAttributes( { labelFontSize: normalizeInt( v, 13 ) } );
							},
						} )
					),

					wp.element.createElement(
						PanelBody,
						{ title: __( 'Colors', 'hm-pro-theme' ), initialOpen: false },
						wp.element.createElement(
							BaseControl,
							{ label: __( 'Label Color', 'hm-pro-theme' ) },
							wp.element.createElement( ColorPalette, {
								value: labelColor,
								onChange: function ( v ) {
									setAttributes( { labelColor: v || '#111111' } );
								},
							} )
						),
						wp.element.createElement(
							BaseControl,
							{ label: __( 'Modal Title Color', 'hm-pro-theme' ) },
							wp.element.createElement( ColorPalette, {
								value: modalTitleColor,
								onChange: function ( v ) {
									setAttributes( { modalTitleColor: v || '#ffffff' } );
								},
							} )
						),
						wp.element.createElement(
							BaseControl,
							{ label: __( 'Modal Link Text Color', 'hm-pro-theme' ) },
							wp.element.createElement( ColorPalette, {
								value: modalLinkColor,
								onChange: function ( v ) {
									setAttributes( { modalLinkColor: v || '#ffffff' } );
								},
							} )
						),
						wp.element.createElement(
							BaseControl,
							{ label: __( 'Modal Link Background', 'hm-pro-theme' ) },
							wp.element.createElement( ColorPalette, {
								value: modalLinkBg,
								onChange: function ( v ) {
									setAttributes( { modalLinkBg: v || 'rgba(0,0,0,0.55)' } );
								},
							} )
						)
					),

					wp.element.createElement(
						PanelBody,
						{ title: __( 'Playback', 'hm-pro-theme' ), initialOpen: false },
						wp.element.createElement( RangeControl, {
							label: __( 'Auto-advance Time (ms)', 'hm-pro-theme' ),
							min: 1500,
							max: 10000,
							step: 100,
							value: normalizeInt( autoTime, 4000 ),
							onChange: function ( v ) {
								setAttributes( { autoTime: normalizeInt( v, 4000 ) } );
							},
						} )
					),

					wp.element.createElement(
						PanelBody,
						{ title: __( 'Content Scale', 'hm-pro-theme' ), initialOpen: false },
						wp.element.createElement( RangeControl, {
							label: __( 'Content Scale (Desktop)', 'hm-pro-theme' ),
							min: 0.6,
							max: 1.4,
							step: 0.01,
							value: Number.isFinite( contentScaleDesktop ) ? contentScaleDesktop : 1,
							onChange: function ( v ) {
								setAttributes( { contentScaleDesktop: parseFloat( String( v ).replace( ',', '.' ) ) || 1 } );
							},
						} ),
						wp.element.createElement( RangeControl, {
							label: __( 'Content Scale (Mobile)', 'hm-pro-theme' ),
							min: 0.6,
							max: 1.4,
							step: 0.01,
							value: Number.isFinite( contentScaleMobile ) ? contentScaleMobile : 1,
							onChange: function ( v ) {
								setAttributes( { contentScaleMobile: parseFloat( String( v ).replace( ',', '.' ) ) || 1 } );
							},
						} )
					),

					wp.element.createElement(
						PanelBody,
						{ title: __( 'Stories', 'hm-pro-theme' ), initialOpen: true },
						safeStories.length === 0
							? wp.element.createElement( Notice, { status: 'info', isDismissible: false }, __( 'Add at least one story highlight.', 'hm-pro-theme' ) )
							: null,

						safeStories.map( ( story, storyIndex ) => {
							const title = story.label ? story.label : ( __( 'Story', 'hm-pro-theme' ) + ' ' + ( storyIndex + 1 ) );

							return wp.element.createElement(
								PanelBody,
								{
									key: 'hmpro-is-story-' + storyIndex,
									title: title,
									initialOpen: false,
								},

								wp.element.createElement( TextControl, {
									label: __( 'Highlight Label', 'hm-pro-theme' ),
									value: story.label || '',
									onChange: function ( v ) {
										updateStory( storyIndex, { label: v } );
									},
								} ),

								wp.element.createElement(
									'div',
									{ className: 'hmpro-is-media-row' },
									wp.element.createElement(
										MediaUploadCheck,
										null,
										wp.element.createElement( MediaUpload, {
											onSelect: function ( media ) {
											updateStory( storyIndex, {
												thumbnailId: media && media.id ? media.id : 0,
												thumbnailUrl: media && media.url ? media.url : '',
											} );
										},
										allowedTypes: [ 'image' ],
										value: story.thumbnailId || 0,
										render: function ( obj ) {
											return wp.element.createElement(
												Button,
												{ variant: 'secondary', onClick: obj.open },
												story.thumbnailUrl ? __( 'Change Thumbnail', 'hm-pro-theme' ) : __( 'Select Thumbnail', 'hm-pro-theme' )
											);
										},
										} )
									),
									story.thumbnailUrl
										? wp.element.createElement(
												Button,
												{
													variant: 'link',
													isDestructive: true,
													onClick: function () {
														updateStory( storyIndex, { thumbnailId: 0, thumbnailUrl: '' } );
													},
												},
												__( 'Remove', 'hm-pro-theme' )
										  )
										: null
								),

								wp.element.createElement( 'div', { className: 'hmpro-is-slides-head' }, __( 'Slides', 'hm-pro-theme' ) ),

								clampSlides( story.slides ).map( ( slide, slideIndex ) => {
									return wp.element.createElement(
										'div',
										{ key: 'hmpro-is-slide-' + storyIndex + '-' + slideIndex, className: 'hmpro-is-slide-card' },

										wp.element.createElement( 'div', { className: 'hmpro-is-slide-title' }, __( 'Slide', 'hm-pro-theme' ) + ' ' + ( slideIndex + 1 ) ),

										wp.element.createElement(
											MediaUploadCheck,
											null,
											wp.element.createElement( MediaUpload, {
												onSelect: function ( media ) {
												updateSlide( storyIndex, slideIndex, {
													imageId: media && media.id ? media.id : 0,
													imageUrl: media && media.url ? media.url : '',
												} );
											},
											allowedTypes: [ 'image' ],
											value: slide.imageId || 0,
											render: function ( obj ) {
												return wp.element.createElement(
													Button,
													{ variant: 'secondary', onClick: obj.open },
													slide.imageUrl ? __( 'Change Image', 'hm-pro-theme' ) : __( 'Select Image', 'hm-pro-theme' )
												);
											},
										} )
										),

										slide.imageUrl
											? wp.element.createElement(
													Button,
													{
														variant: 'link',
														isDestructive: true,
														onClick: function () {
															updateSlide( storyIndex, slideIndex, { imageId: 0, imageUrl: '' } );
														},
													},
													__( 'Remove Image', 'hm-pro-theme' )
											  )
											: null,

										wp.element.createElement( TextControl, {
											label: __( 'Title', 'hm-pro-theme' ),
											value: slide.title || '',
											onChange: function ( v ) {
												updateSlide( storyIndex, slideIndex, { title: v } );
											},
										} ),
										wp.element.createElement( TextControl, {
											label: __( 'Link Text', 'hm-pro-theme' ),
											value: slide.linkText || '',
											onChange: function ( v ) {
												updateSlide( storyIndex, slideIndex, { linkText: v } );
											},
										} ),
										wp.element.createElement( TextControl, {
											label: __( 'Link URL', 'hm-pro-theme' ),
											value: slide.linkUrl || '',
											onChange: function ( v ) {
												updateSlide( storyIndex, slideIndex, { linkUrl: v } );
											},
										} ),
										wp.element.createElement( ToggleControl, {
											label: __( 'Open in new tab', 'hm-pro-theme' ),
											checked: !! slide.newTab,
											onChange: function ( v ) {
												updateSlide( storyIndex, slideIndex, { newTab: !! v } );
											},
										} ),
										wp.element.createElement( ToggleControl, {
											label: __( 'Nofollow', 'hm-pro-theme' ),
											checked: !! slide.nofollow,
											onChange: function ( v ) {
												updateSlide( storyIndex, slideIndex, { nofollow: !! v } );
											},
										} ),

										wp.element.createElement(
											'div',
											{ className: 'hmpro-is-slide-actions' },
											wp.element.createElement(
												Button,
												{
													variant: 'secondary',
													onClick: function () {
														addSlide( storyIndex );
													},
													disabled: clampSlides( story.slides ).length >= MAX_SLIDES,
												},
												__( 'Add Slide', 'hm-pro-theme' )
											),
											wp.element.createElement(
												Button,
												{
													variant: 'link',
													isDestructive: true,
													onClick: function () {
														removeSlide( storyIndex, slideIndex );
													},
													disabled: clampSlides( story.slides ).length <= 1,
												},
												__( 'Remove Slide', 'hm-pro-theme' )
											)
										)
									);
								} ),

								wp.element.createElement(
									'div',
									{ className: 'hmpro-is-story-actions' },
									wp.element.createElement(
										Button,
										{
											variant: 'primary',
											onClick: addStory,
											disabled: safeStories.length >= MAX_STORIES,
										},
										__( 'Add Story', 'hm-pro-theme' )
									),
									wp.element.createElement(
										Button,
										{
											variant: 'link',
											isDestructive: true,
											onClick: function () {
												removeStory( storyIndex );
											},
										},
										__( 'Remove Story', 'hm-pro-theme' )
									)
								)
							);
						} ),

						safeStories.length === 0
							? wp.element.createElement(
									Button,
									{
										variant: 'primary',
										onClick: addStory,
									},
									__( 'Add Story', 'hm-pro-theme' )
							  )
							: null
					)
				),

				wp.element.createElement(
					'div',
					blockProps,
					wp.element.createElement(
						'div',
						{ className: 'hmpro-is__editor-wrap' },
						wp.element.createElement(
							'div',
							{ className: 'hmpro-is__list', role: 'list' },
							previewStories.map( ( s, i ) => {
								const thumb = s.thumbnailUrl || ( s.slides && s.slides[ 0 ] ? s.slides[ 0 ].imageUrl : '' );
								const firstSlide = ( s.slides && s.slides[ 0 ] ) ? s.slides[ 0 ] : {};
								const metaTitle = firstSlide.title || '';
								const metaLink = firstSlide.linkText || '';
								return wp.element.createElement(
									'div',
									{ key: 'hmpro-is-prev-' + i, className: 'hmpro-is__item', role: 'listitem' },
									wp.element.createElement(
										'div',
										{ className: 'hmpro-is__thumbWrap' },
										wp.element.createElement(
											'div',
											{ className: 'hmpro-is__thumb' },
											thumb
												? wp.element.createElement( 'img', { src: thumb, alt: s.label || '' } )
												: wp.element.createElement( 'div', { className: 'hmpro-is__thumbPh' } )
									)
								),
									s.label
										? wp.element.createElement( 'div', { className: 'hmpro-is__label' }, s.label )
										: null
									,
									( metaTitle || metaLink )
										? wp.element.createElement(
											'div',
											{ className: 'hmpro-is__meta' },
											metaTitle
												? wp.element.createElement( 'div', { className: 'hmpro-is__metaTitle' }, metaTitle )
												: null,
											metaLink
												? wp.element.createElement( 'div', { className: 'hmpro-is__metaLink' }, metaLink )
												: null
										)
										: null
								);
							} )
						)
					)
				)
			);
		},

		save: function () {
			return null; // server-side render
		},
	} );
} )( window.wp );
