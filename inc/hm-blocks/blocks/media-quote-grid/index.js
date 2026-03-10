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
		SelectControl,
		TextControl,
		TextareaControl,
		ToggleControl,
		RangeControl,
		Button,
		Notice,
	} = wp.components;

	const PRESET_OPTIONS = [
		{ label: 'One (Single Feature)', value: 'one_feature' },
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

	const PRESET_LIMITS = {
		one_feature: 1,
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
		six_mosaic_left: 5,
		six_mosaic_right: 5,
	};

	function ensureTiles( tiles, count ) {
		const next = Array.isArray( tiles ) ? [ ...tiles ] : [];
		while ( next.length < count ) {
			next.push( {
				show: true,
				title: '',
				paragraph: '',
				quote: '',
				cite: '',
				mediaPosition: ( next.length % 2 === 0 ) ? 'left' : 'right',
				imageId: 0,
				imageUrl: '',
				imageAlt: '',
			} );
		}
		return next.slice( 0, count );
	}

	registerBlockType( 'hmpro/media-quote-grid', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const { preset, tiles, gridGap, cardRadius, cardPadding } = attributes;

			const maxTiles = PRESET_LIMITS[ preset ] || 3;
			const safeTiles = ensureTiles( tiles, maxTiles );

			// Keep attribute tiles synced to preset count (additive + stable).
			if ( ! Array.isArray( tiles ) || tiles.length !== safeTiles.length ) {
				setAttributes( { tiles: safeTiles } );
			}

			function updateTile( index, patch ) {
				const next = [ ...safeTiles ];
				next[ index ] = { ...next[ index ], ...patch };
				setAttributes( { tiles: next } );
			}

			const blockProps = useBlockProps( {
				className: 'hmpro-media-quote-grid',
				style: {
					'--hm-mq-gap': ( gridGap || 20 ) + 'px',
					'--hm-mq-radius': ( cardRadius || 18 ) + 'px',
					'--hm-mq-pad': ( cardPadding || 22 ) + 'px',
				},
			} );

			return (
				wp.element.createElement(
					wp.element.Fragment,
					null,
					wp.element.createElement(
						InspectorControls,
						null,
						wp.element.createElement(
							PanelBody,
							{ title: __( 'Layout', 'hmpro' ), initialOpen: true },
							wp.element.createElement( SelectControl, {
								label: __( 'Preset', 'hmpro' ),
								value: preset,
								options: PRESET_OPTIONS,
								onChange: function ( v ) {
									const cnt = PRESET_LIMITS[ v ] || 3;
									setAttributes( { preset: v, tiles: ensureTiles( safeTiles, cnt ) } );
								},
							} ),
							wp.element.createElement( RangeControl, {
								label: __( 'Grid gap', 'hmpro' ),
								value: gridGap,
								min: 0,
								max: 60,
								onChange: ( v ) => setAttributes( { gridGap: v } ),
							} ),
							wp.element.createElement( RangeControl, {
								label: __( 'Card radius', 'hmpro' ),
								value: cardRadius,
								min: 0,
								max: 40,
								onChange: ( v ) => setAttributes( { cardRadius: v } ),
							} ),
							wp.element.createElement( RangeControl, {
								label: __( 'Card padding', 'hmpro' ),
								value: cardPadding,
								min: 10,
								max: 60,
								onChange: ( v ) => setAttributes( { cardPadding: v } ),
							} )
						)
					),

					wp.element.createElement(
						'div',
						blockProps,
						wp.element.createElement(
							'div',
							{ className: 'hmpro-mq__inner' },
							wp.element.createElement(
								'div',
								{ className: 'hmpro-mq__grid hmpro-mq__grid--' + preset },
								safeTiles.map( function ( tile, index ) {
									const area = [ 'a', 'b', 'c', 'd', 'e', 'f' ][ index ] || 'a';
									const mediaRight = tile.mediaPosition === 'right';

									return wp.element.createElement(
										'article',
										{ key: index, className: 'hmpro-mq__tile hmpro-mq__tile--' + area },
										wp.element.createElement(
											'div',
											{ className: 'hmpro-mq__card ' + ( mediaRight ? 'hmpro-mq__card--media-right' : '' ) },

											// Media column
											wp.element.createElement(
												'div',
												{ className: 'hmpro-mq__media' },
												wp.element.createElement(
													MediaUploadCheck,
													null,
													wp.element.createElement( MediaUpload, {
														onSelect: function ( media ) {
															updateTile( index, {
																imageId: media && media.id ? media.id : 0,
																imageUrl: media && media.url ? media.url : '',
																imageAlt: media && media.alt ? media.alt : '',
															} );
														},
														allowedTypes: [ 'image' ],
														value: tile.imageId || 0,
														render: function ( obj ) {
															if ( tile.imageUrl ) {
																return wp.element.createElement(
																	'div',
																	{ className: 'hmpro-mq-media-preview' },
																	wp.element.createElement( 'img', { src: tile.imageUrl, alt: tile.imageAlt || '' } ),
																	wp.element.createElement(
																		'div',
																		{ style: { marginTop: '8px', display: 'flex', gap: '8px', flexWrap: 'wrap' } },
																		wp.element.createElement( Button, { variant: 'secondary', onClick: obj.open }, __( 'Replace', 'hmpro' ) ),
																		wp.element.createElement( Button, {
																			variant: 'link',
																			isDestructive: true,
																			onClick: function () {
																				updateTile( index, { imageId: 0, imageUrl: '', imageAlt: '' } );
																			},
																		}, __( 'Remove', 'hmpro' ) )
																	)
																);
															}

															return wp.element.createElement(
																'div',
																{ className: 'hmpro-mq-media-placeholder' },
																wp.element.createElement( Button, { variant: 'secondary', onClick: obj.open }, __( 'Select Image', 'hmpro' ) )
															);
														},
													} )
												)
											),

											// Content column
											wp.element.createElement(
												'div',
												{ className: 'hmpro-mq__content' },
												wp.element.createElement( ToggleControl, {
													label: __( 'Show tile', 'hmpro' ),
													checked: tile.show !== false,
													onChange: ( v ) => updateTile( index, { show: !! v } ),
												} ),
												wp.element.createElement( SelectControl, {
													label: __( 'Media position', 'hmpro' ),
													value: tile.mediaPosition || 'left',
													options: [
														{ label: __( 'Left', 'hmpro' ), value: 'left' },
														{ label: __( 'Right', 'hmpro' ), value: 'right' },
													],
													onChange: ( v ) => updateTile( index, { mediaPosition: v } ),
												} ),
												wp.element.createElement( TextControl, {
													label: __( 'Title', 'hmpro' ),
													value: tile.title || '',
													onChange: ( v ) => updateTile( index, { title: v } ),
												} ),
												wp.element.createElement( TextareaControl, {
													label: __( 'Paragraph', 'hmpro' ),
													value: tile.paragraph || '',
													onChange: ( v ) => updateTile( index, { paragraph: v } ),
													rows: 3,
												} ),
												wp.element.createElement( TextareaControl, {
													label: __( 'Quote', 'hmpro' ),
													value: tile.quote || '',
													onChange: ( v ) => updateTile( index, { quote: v } ),
													rows: 3,
												} ),
												wp.element.createElement( TextControl, {
													label: __( 'Cite (optional)', 'hmpro' ),
													value: tile.cite || '',
													onChange: ( v ) => updateTile( index, { cite: v } ),
												} )
											)
										)
									);
								} )
							),

							wp.element.createElement(
								'div',
								{ className: 'hmpro-mq-editor-help' },
								wp.element.createElement(
									Notice,
									{ status: 'info', isDismissible: false },
									__( 'Tip: Choose a preset first. It controls how many tiles are used and how they are arranged.', 'hmpro' )
								)
							)
						)
					)
				)
			);
		},
		save: function () {
			return null; // Rendered in PHP.
		},
	} );
} )( window.wp );
