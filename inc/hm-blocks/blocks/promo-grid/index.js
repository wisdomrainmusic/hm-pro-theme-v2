( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { __ } = wp.i18n;
	const {
		InspectorControls,
		MediaUpload,
		MediaUploadCheck,
		useBlockProps,
	} = wp.blockEditor;
	const {
		PanelBody,
		ToggleControl,
		SelectControl,
		TextControl,
		TextareaControl,
		RangeControl,
		__experimentalNumberControl: NumberControl,
		Button,
		ButtonGroup,
		Notice,
		BaseControl,
		ColorPalette,
	} = wp.components;

	// Normalize decimals from locale inputs: "1,25" -> 1.25
	function normalizeFloat( val, fallback ) {
		if ( val === null || val === undefined ) return fallback;
		if ( typeof val === 'number' && Number.isFinite( val ) ) return val;
		const s = String( val ).trim().replace( ',', '.' );
		const n = parseFloat( s );
		return Number.isFinite( n ) ? n : fallback;
	}

	function formatFloatForInput( n ) {
		return ( n === null || n === undefined ) ? '' : String( n );
	}

	function normalizeInt( val, fallback ) {
		const n = parseInt( String( val ).replace( ',', '.' ), 10 );
		return Number.isFinite( n ) ? n : fallback;
	}

	const PRESETS = [
		{ label: 'Two Equal', value: 'two_equal' },
		{ label: 'Two 70/30', value: 'two_split_70_30' },
		{ label: 'Two 30/70', value: 'two_split_30_70' },
		{ label: 'Three Equal', value: 'three_equal' },
		{ label: '3 Mosaic Left', value: 'three_mosaic_left' },
		{ label: '3 Mosaic Right', value: 'three_mosaic_right' },
		{ label: 'Four Checker', value: 'four_checker' },
		{ label: '4 Mosaic Left', value: 'four_mosaic_left' },
		{ label: '4 Mosaic Right', value: 'four_mosaic_right' },
		{ label: 'Six Grid', value: 'six_grid' },
		{ label: '6 Mosaic Left', value: 'six_mosaic_left' },
		{ label: '6 Mosaic Right', value: 'six_mosaic_right' },
	];

	const TILE_LIMITS = {
		two_equal: 2,
		two_split_70_30: 2,
		two_split_30_70: 2,
		three_equal: 3,
		three_mosaic_left: 3,
		three_mosaic_right: 3,
		four_checker: 4,
		four_mosaic_left: 4,
		four_mosaic_right: 4,
		six_grid: 6,
		six_mosaic_left: 6,
		six_mosaic_right: 6,
	};

	const POSITIONS = [
		{ label: 'Bottom Left', value: 'bottom-left' },
		{ label: 'Bottom Center', value: 'bottom-center' },
		{ label: 'Bottom Right', value: 'bottom-right' },
		{ label: 'Center Left', value: 'center-left' },
		{ label: 'Center', value: 'center' },
		{ label: 'Center Right', value: 'center-right' },
		{ label: 'Top Left', value: 'top-left' },
		{ label: 'Top Center', value: 'top-center' },
		{ label: 'Top Right', value: 'top-right' },
	];

	const HOVER = [
		{ label: 'None', value: 'none' },
		{ label: 'Zoom', value: 'zoom' },
		{ label: 'Dim', value: 'dim' },
	];

	function normalizeTiles( tiles, maxTiles ) {
		const arr = Array.isArray( tiles ) ? tiles.slice( 0 ) : [];
		if ( arr.length > maxTiles ) {
			return arr.slice( 0, maxTiles );
		}
		return arr;
	}

	function createEmptyTile() {
		return {
			show: true,
			imageId: 0,
			imageUrl: '',
			title: '',
			subtitle: '',
			buttonText: '',
			linkUrl: '',
			newTab: false,
			nofollow: false,
			titleColor: '',
			subtitleColor: '',
			buttonBgColor: '',
			buttonTextColor: '',
			titleFontSize: 0,
			contentScale: 1,
			contentScaleMobile: 0,
			mobileOffsetY: 0,
			overlay: true,
			position: 'bottom-left',
			offsetX: 0,
			offsetY: 0,
			contentMaxWidth: 520,
			contentPadding: 18
		};
	}

	registerBlockType( 'hmpro/promo-grid', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const {
				preset,
				fullWidth,
				fixedHeight,
				containerMinHeight,
				containerHeight,
				gridGap,
				tileMinHeight,
				hoverEffect,
				mediaScale,
				overlayOpacity,
				tiles,
			} = attributes;

			// Normalize numeric values that may arrive as localized strings (e.g. "0,5").
			const toNumber = ( value, fallback ) => {
				if ( value === null || value === undefined || value === '' ) {
					return fallback;
				}
				if ( typeof value === 'number' && Number.isFinite( value ) ) {
					return value;
				}
				const normalized = String( value ).replace( ',', '.' );
				const parsed = parseFloat( normalized );
				return Number.isFinite( parsed ) ? parsed : fallback;
			};

			const maxTiles = TILE_LIMITS[ preset ] || 6;
			const safeTiles = normalizeTiles( tiles, maxTiles );

			// Keep tiles trimmed when preset changes.
			if ( safeTiles.length !== ( Array.isArray( tiles ) ? tiles.length : 0 ) ) {
				setAttributes( { tiles: safeTiles } );
			}

			const blockProps = useBlockProps( {
				className: [
					'hmpro-block',
					'hmpro-promo-grid',
					fullWidth ? 'hmpro-pg--fullwidth' : '',
					fixedHeight ? 'hmpro-pg--fixed-height' : '',
					hoverEffect && hoverEffect !== 'none' ? ( 'hmpro-pg--hover-' + hoverEffect ) : '',
				].filter( Boolean ).join( ' ' ),
			} );

			function updateTile( index, patch ) {
				const next = safeTiles.slice( 0 );
				next[ index ] = Object.assign( {}, next[ index ] || createEmptyTile(), patch );
				setAttributes( { tiles: next } );
			}

			function removeTile( index ) {
				const next = safeTiles.slice( 0 );
				next.splice( index, 1 );
				setAttributes( { tiles: next } );
			}

			function addTile() {
				const next = safeTiles.slice( 0 );
				if ( next.length >= maxTiles ) {
					return;
				}
				next.push( createEmptyTile() );
				setAttributes( { tiles: next } );
			}

			const previewStyle = {
				'--hm-pg-gap': ( gridGap || 24 ) + 'px',
				'--hm-pg-minh': ( containerMinHeight || 360 ) + 'px',
				'--hm-pg-tile-minh': ( tileMinHeight || 340 ) + 'px',
				'--hm-pg-media-scale': toNumber( mediaScale, 1.08 ),
				'--hm-pg-overlay-opacity': toNumber( overlayOpacity, 0.35 ),
				// IMPORTANT: editor preview needs explicit height when fixedHeight is on
				...( fixedHeight ? { height: ( containerHeight || 520 ) + 'px' } : {} ),
			};

			return wp.element.createElement(
				wp.element.Fragment,
				null,
				wp.element.createElement(
					InspectorControls,
					null,
					wp.element.createElement(
						PanelBody,
						{ title: __( 'Layout', 'hm-pro-theme' ), initialOpen: true },
						wp.element.createElement( SelectControl, {
							label: __( 'Preset', 'hm-pro-theme' ),
							value: preset,
							options: PRESETS,
							onChange: function ( v ) {
								setAttributes( { preset: v } );
							},
						} ),
						wp.element.createElement( ToggleControl, {
							label: __( 'Full Width (bleed)', 'hm-pro-theme' ),
							checked: !! fullWidth,
							onChange: function ( v ) {
								setAttributes( { fullWidth: !! v } );
							},
						} ),
						wp.element.createElement( ToggleControl, {
							label: __( 'Fixed Height Mode', 'hm-pro-theme' ),
							checked: !! fixedHeight,
							onChange: function ( v ) {
								setAttributes( { fixedHeight: !! v } );
							},
						} ),
						fixedHeight
							? wp.element.createElement( RangeControl, {
									label: __( 'Container Height (px)', 'hm-pro-theme' ),
									min: 200,
									max: 900,
									value: containerHeight,
									onChange: function ( v ) {
										setAttributes( { containerHeight: v || 520 } );
									},
							  } )
							: null,
						wp.element.createElement( RangeControl, {
							label: __( 'Min Height (px)', 'hm-pro-theme' ),
							min: 200,
							max: 900,
							value: containerMinHeight,
							onChange: function ( v ) {
								setAttributes( { containerMinHeight: v || 360 } );
							},
						} )
					),
					wp.element.createElement(
						PanelBody,
						{ title: __( 'Grid', 'hm-pro-theme' ), initialOpen: false },
						wp.element.createElement( RangeControl, {
							label: __( 'Grid Gap (px)', 'hm-pro-theme' ),
							min: 0,
							max: 80,
							value: gridGap,
							onChange: function ( v ) {
								setAttributes( { gridGap: v || 0 } );
							},
						} ),
						wp.element.createElement( RangeControl, {
							label: __( 'Tile Min Height (px)', 'hm-pro-theme' ),
							min: 100,
							max: 900,
							value: tileMinHeight,
							onChange: function ( v ) {
								setAttributes( { tileMinHeight: v || 0 } );
							},
						} )
					),
					wp.element.createElement(
						PanelBody,
						{ title: __( 'Effects', 'hm-pro-theme' ), initialOpen: false },
						wp.element.createElement( SelectControl, {
							label: __( 'Hover Effect', 'hm-pro-theme' ),
							value: hoverEffect,
							options: HOVER,
							onChange: function ( v ) {
								setAttributes( { hoverEffect: v } );
							},
						} ),
						wp.element.createElement( RangeControl, {
							label: __( 'Media Scale', 'hm-pro-theme' ),
							min: 1,
							max: 1.3,
							step: 0.05,
							value: mediaScale,
							onChange: function ( v ) {
								setAttributes( { mediaScale: toNumber( v, 1.08 ) } );
							},
						} ),
						wp.element.createElement( RangeControl, {
							label: __( 'Overlay Opacity', 'hm-pro-theme' ),
							min: 0,
							max: 0.85,
							step: 0.05,
							value: overlayOpacity,
							onChange: function ( v ) {
								setAttributes( { overlayOpacity: toNumber( v, 0.35 ) } );
							},
						} )
					),
					wp.element.createElement(
						PanelBody,
						{ title: __( 'Tiles', 'hm-pro-theme' ), initialOpen: true },
						wp.element.createElement(
							Notice,
							{ status: 'info', isDismissible: false },
							__( 'Tip: Add images first. Preset controls how many tiles are used.', 'hm-pro-theme' )
						),
						wp.element.createElement(
							'div',
							{ className: 'hmpro-pg-editor__tiles' },
							safeTiles.map( function ( tile, idx ) {
								const t = Object.assign( createEmptyTile(), tile || {} );
								return wp.element.createElement(
									'div',
									{ key: idx, className: 'hmpro-pg-editor__tile' },
									wp.element.createElement(
										'div',
										{ className: 'hmpro-pg-editor__tile-head' },
										wp.element.createElement(
											'strong',
											null,
											( t.title && t.title.trim() ) ? t.title : ( 'Tile ' + ( idx + 1 ) )
										),
										wp.element.createElement(
											Button,
											{
												isDestructive: true,
												isSmall: true,
												onClick: function () { removeTile( idx ); }
											},
											__( 'Remove', 'hm-pro-theme' )
										)
									),
									wp.element.createElement( ToggleControl, {
										label: __( 'Show Tile', 'hm-pro-theme' ),
										checked: !! t.show,
										onChange: function ( v ) { updateTile( idx, { show: !! v } ); }
									} ),
									wp.element.createElement(
										MediaUploadCheck,
										null,
										wp.element.createElement( MediaUpload, {
											onSelect: function ( media ) {
												updateTile( idx, {
													imageId: media && media.id ? media.id : 0,
													imageUrl: media && media.url ? media.url : ''
												} );
											},
											allowedTypes: [ 'image' ],
											value: t.imageId || 0,
											render: function ( obj ) {
												return wp.element.createElement(
													Button,
													{ onClick: obj.open, isSecondary: true },
													t.imageUrl ? __( 'Change Image', 'hm-pro-theme' ) : __( 'Select Image', 'hm-pro-theme' )
												);
											}
										} )
									),
									t.imageUrl ? wp.element.createElement( 'div', { className: 'hmpro-pg-editor__thumb', style: { backgroundImage: 'url(' + t.imageUrl + ')' } } ) : null,
									wp.element.createElement( TextControl, {
										label: __( 'Title', 'hm-pro-theme' ),
										value: t.title,
										onChange: function ( v ) { updateTile( idx, { title: v } ); }
									} ),
									wp.element.createElement( TextareaControl, {
										label: __( 'Subtitle', 'hm-pro-theme' ),
										value: t.subtitle,
										onChange: function ( v ) { updateTile( idx, { subtitle: v } ); }
									} ),
									wp.element.createElement( TextControl, {
										label: __( 'Button Text', 'hm-pro-theme' ),
										value: t.buttonText,
										onChange: function ( v ) { updateTile( idx, { buttonText: v } ); }
									} ),
									wp.element.createElement(
										PanelBody,
										{ title: __( 'Content Group', 'hm-pro-theme' ), initialOpen: false },
										wp.element.createElement( NumberControl, {
											label: __( 'Content group scale (desktop)', 'hm-pro-theme' ),
											help: __( 'Title + subtitle + button scale together. Example: 1,25', 'hm-pro-theme' ),
											value: formatFloatForInput( normalizeFloat( t.contentScale, 1 ) ),
											min: 0.5,
											max: 2.5,
											step: 0.05,
											spinControls: 'native',
											onChange: function ( val ) {
												// val can arrive as string; allow comma in UI
												const n = normalizeFloat( val, 1 );
												updateTile( idx, { contentScale: n } );
											},
											onBlur: function ( e ) {
												const n = normalizeFloat( e?.target?.value, 1 );
												updateTile( idx, { contentScale: n } );
											},
										} ),
										wp.element.createElement( NumberControl, {
											label: __( 'Mobile content group scale (optional)', 'hm-pro-theme' ),
											help: __( 'Only on mobile/tablet. If 0, desktop scale is used.', 'hm-pro-theme' ),
											value: formatFloatForInput( normalizeFloat( t.contentScaleMobile, 0 ) ),
											min: 0,
											max: 2.5,
											step: 0.05,
											spinControls: 'native',
											onChange: function ( val ) {
												const n = normalizeFloat( val, 0 );
												updateTile( idx, { contentScaleMobile: n } );
											},
											onBlur: function ( e ) {
												const n = normalizeFloat( e?.target?.value, 0 );
												updateTile( idx, { contentScaleMobile: n } );
											},
										} ),
										wp.element.createElement( NumberControl, {
											label: __( 'Mobile/Tablet content top offset (px)', 'hm-pro-theme' ),
											help: __( 'Mobile/tablet only. Move content up (-) or down (+).', 'hm-pro-theme' ),
											value: String( normalizeInt( t.mobileOffsetY, 0 ) ),
											min: -400,
											max: 400,
											step: 1,
											spinControls: 'native',
											onChange: function ( val ) {
												const n = normalizeInt( val, 0 );
												updateTile( idx, { mobileOffsetY: n } );
											},
											onBlur: function ( e ) {
												const n = normalizeInt( e?.target?.value, 0 );
												updateTile( idx, { mobileOffsetY: n } );
											},
										} )
									),
									wp.element.createElement(
										PanelBody,
										{
											title: __( 'Typography & Colors', 'hm-pro-theme' ),
											initialOpen: false
										},
										wp.element.createElement(
											BaseControl,
											{ label: __( 'Title color', 'hm-pro-theme' ) },
											wp.element.createElement( ColorPalette, {
												value: t.titleColor || undefined,
												onChange: function ( val ) { updateTile( idx, { titleColor: val || '' } ); }
											} )
										),
										wp.element.createElement(
											BaseControl,
											{ label: __( 'Subtitle color', 'hm-pro-theme' ) },
											wp.element.createElement( ColorPalette, {
												value: t.subtitleColor || undefined,
												onChange: function ( val ) { updateTile( idx, { subtitleColor: val || '' } ); }
											} )
										),
										wp.element.createElement(
											BaseControl,
											{ label: __( 'Button background', 'hm-pro-theme' ) },
											wp.element.createElement( ColorPalette, {
												value: t.buttonBgColor || undefined,
												onChange: function ( val ) { updateTile( idx, { buttonBgColor: val || '' } ); }
											} )
										),
										wp.element.createElement(
											BaseControl,
											{ label: __( 'Button text color', 'hm-pro-theme' ) },
											wp.element.createElement( ColorPalette, {
												value: t.buttonTextColor || undefined,
												onChange: function ( val ) { updateTile( idx, { buttonTextColor: val || '' } ); }
											} )
										),
										wp.element.createElement( RangeControl, {
											label: __( 'Title font size (px)', 'hm-pro-theme' ),
											value: Number( t.titleFontSize || 0 ),
											min: 0,
											max: 96,
											step: 1,
											allowReset: true,
											onChange: function ( val ) { updateTile( idx, { titleFontSize: Number( val || 0 ) } ); }
										} )
									),
									wp.element.createElement( TextControl, {
										label: __( 'Link URL', 'hm-pro-theme' ),
										value: t.linkUrl,
										placeholder: 'https://',
										onChange: function ( v ) { updateTile( idx, { linkUrl: v } ); }
									} ),
									wp.element.createElement( ToggleControl, {
										label: __( 'Open in new tab', 'hm-pro-theme' ),
										checked: !! t.newTab,
										onChange: function ( v ) { updateTile( idx, { newTab: !! v } ); }
									} ),
									wp.element.createElement( ToggleControl, {
										label: __( 'Nofollow', 'hm-pro-theme' ),
										checked: !! t.nofollow,
										onChange: function ( v ) { updateTile( idx, { nofollow: !! v } ); }
									} ),
									wp.element.createElement( ToggleControl, {
										label: __( 'Overlay enabled', 'hm-pro-theme' ),
										checked: !! t.overlay,
										onChange: function ( v ) { updateTile( idx, { overlay: !! v } ); }
									} ),
									wp.element.createElement( SelectControl, {
										label: __( 'Content Position', 'hm-pro-theme' ),
										value: t.position,
										options: POSITIONS,
										onChange: function ( v ) { updateTile( idx, { position: v } ); }
									} ),
									wp.element.createElement( RangeControl, {
										label: __( 'Content Offset X (px)', 'hm-pro-theme' ),
										min: -120,
										max: 120,
										value: t.offsetX,
										onChange: function ( v ) { updateTile( idx, { offsetX: v || 0 } ); }
									} ),
									wp.element.createElement( RangeControl, {
										label: __( 'Content Offset Y (px)', 'hm-pro-theme' ),
										min: -120,
										max: 120,
										value: t.offsetY,
										onChange: function ( v ) { updateTile( idx, { offsetY: v || 0 } ); }
									} ),
									wp.element.createElement( RangeControl, {
										label: __( 'Content Max Width (px)', 'hm-pro-theme' ),
										min: 240,
										max: 900,
										value: t.contentMaxWidth,
										onChange: function ( v ) { updateTile( idx, { contentMaxWidth: v || 520 } ); }
									} ),
									wp.element.createElement( RangeControl, {
										label: __( 'Content Padding (px)', 'hm-pro-theme' ),
										min: 0,
										max: 48,
										value: t.contentPadding,
										onChange: function ( v ) { updateTile( idx, { contentPadding: v || 0 } ); }
									} )
								);
							} )
						),
						wp.element.createElement(
							ButtonGroup,
							null,
							wp.element.createElement(
								Button,
								{ isPrimary: true, onClick: addTile, disabled: safeTiles.length >= maxTiles },
								__( 'Add Tile', 'hm-pro-theme' )
							)
						),
						wp.element.createElement(
							'div',
							{ className: 'hmpro-pg-editor__limit' },
							__( 'Max tiles for this preset:', 'hm-pro-theme' ) + ' ' + maxTiles
						)
					)
				),
				wp.element.createElement(
					'div',
					Object.assign( {}, blockProps, { style: previewStyle, 'data-preset': preset } ),
					wp.element.createElement(
						'div',
						{ className: 'hmpro-pg__inner' },
						wp.element.createElement(
							'div',
							{ className: 'hmpro-pg__grid hmpro-pg__grid--' + preset },
							safeTiles.length
								? safeTiles.map( function ( tile, idx ) {
										const t = Object.assign( createEmptyTile(), tile || {} );
										if ( ! t.show ) return null;

										// Elementor-like editor placeholders (do NOT persist to attributes).
										const phTitle = 'Tile ' + ( idx + 1 ) + ' Title';
										const phSubtitle = 'Add subtitle here';
										const phBtn = 'Learn More';
										const titleText = t.title ? t.title : phTitle;
										const subtitleText = t.subtitle ? t.subtitle : phSubtitle;
										const buttonText = t.buttonText ? t.buttonText : phBtn;
										const overlayClass = [ 'hmpro-pg__overlay', ( t.overlay ? '' : 'is-disabled' ) ]
											.filter( Boolean ).join( ' ' );

										const tileStyle = {
											'--hm-pg-offset-x': ( t.offsetX || 0 ) + 'px',
											'--hm-pg-offset-y': ( t.offsetY || 0 ) + 'px',
											'--hm-pg-content-maxw': ( t.contentMaxWidth || 520 ) + 'px',
											'--hm-pg-content-pad': ( t.contentPadding || 18 ) + 'px',
											'--hm-pg-content-scale': String( normalizeFloat( t.contentScale, 1 ) ),
											// If mobile scale is 0, fall back to desktop scale (Header Banner rule)
											'--hm-pg-content-scale-m': String(
												( normalizeFloat( t.contentScaleMobile, 0 ) > 0 )
													? normalizeFloat( t.contentScaleMobile, 0 )
													: normalizeFloat( t.contentScale, 1 )
											),
											'--hm-pg-mobile-offset-y': ( normalizeInt( t.mobileOffsetY, 0 ) ) + 'px',
											'--hm-pg-title-color': t.titleColor || '',
											'--hm-pg-subtitle-color': t.subtitleColor || '',
											'--hm-pg-btn-bg': t.buttonBgColor || '',
											'--hm-pg-btn-color': t.buttonTextColor || '',
											'--hm-pg-title-size': ( t.titleFontSize && Number( t.titleFontSize ) > 0 )
												? Number( t.titleFontSize ) + 'px'
												: '',
										};

										return wp.element.createElement(
											'div',
											{
												key: idx,
												style: tileStyle,
												className: [
													'hmpro-pg__tile',
													'hmpro-pg__tile--preview',
													'hmpro-pg__tile-position-' + ( t.position || 'bottom-left' )
												].join( ' ' )
											},
											t.imageUrl
												? wp.element.createElement(
														'div',
														{ className: 'hmpro-pg__media' },
														wp.element.createElement( 'img', {
															src: t.imageUrl,
															alt: titleText || '',
															loading: 'lazy'
														} )
												  )
												: wp.element.createElement( 'div', { className: 'hmpro-pg__media hmpro-pg__media--empty' }, __( 'Select image', 'hm-pro-theme' ) ),
											wp.element.createElement(
												'div',
												{ className: overlayClass },
												wp.element.createElement(
													'div',
													{ className: 'hmpro-pg__content hmpro-pg__content--' + ( t.position || 'bottom-left' ) },
													wp.element.createElement( 'div', { className: 'hmpro-pg__title' }, titleText ),
													wp.element.createElement( 'div', { className: 'hmpro-pg__subtitle' }, subtitleText ),
													wp.element.createElement( 'span', { className: 'hmpro-pg__button' }, buttonText )
												)
											)
										);
								  } )
								: wp.element.createElement(
										'div',
										{ className: 'hmpro-pg-editor__empty' },
										__( 'Add tiles from the right sidebar → Tiles panel.', 'hm-pro-theme' )
								  )
						)
					)
				)
			);
		},

		// Dynamic render in PHP (stable frontend HTML).
		save: function () {
			return null;
		},
	} );
} )( window.wp );
