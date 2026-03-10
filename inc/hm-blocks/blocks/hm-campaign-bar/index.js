( function ( wp ) {
	const el = wp.element.createElement;
	const registerBlockType = wp.blocks.registerBlockType;
	const InspectorControls = wp.blockEditor.InspectorControls;
	const useBlockProps = wp.blockEditor.useBlockProps;

	const PanelBody = wp.components.PanelBody;
	const TextareaControl = wp.components.TextareaControl;
	const TextControl = wp.components.TextControl;
	const ToggleControl = wp.components.ToggleControl;
	const SelectControl = wp.components.SelectControl;
	const RangeControl = wp.components.RangeControl;
	const ColorPalette = wp.components.ColorPalette;

	function clampNum( v, min, max, fallback ) {
		const n = typeof v === 'number' && isFinite( v ) ? v : parseFloat( v );
		if ( ! isFinite( n ) ) return fallback;
		return Math.max( min, Math.min( max, n ) );
	}

	registerBlockType( 'hmpro/hm-campaign-bar', {
		edit: function ( props ) {
			const attrs = props.attributes;
			const setAttributes = props.setAttributes;

			function ensureSelected() {
				// Some themes/components can swallow editor clicks; force select on interaction.
				try {
					wp.data.dispatch( 'core/block-editor' ).selectBlock( props.clientId );
				} catch ( e ) {}
			}

			const textMode = attrs.textMode || 'static';
			const isMarquee = textMode === 'marquee';

			const styleVars = {
				'--hm-cb-bg': attrs.bgColor || '#000000',
				'--hm-cb-text': attrs.textColor || '#ffffff',
				'--hm-cb-gap': clampNum( attrs.marqueeGap, 8, 180, 48 ) + 'px',
				'--hm-cb-speed': clampNum( attrs.marqueeSpeed, 5, 120, 18 ) + 's',
				'--hm-cb-h': clampNum( attrs.height, 30, 140, 56 ) + 'px',
				'--hm-cb-padx': clampNum( attrs.paddingX, 0, 80, 18 ) + 'px',
				'--hm-cb-radius': clampNum( attrs.borderRadius, 0, 50, 10 ) + 'px'
			};

			const classes = [ 'hmpro-cb' ];
			if ( attrs.fullWidth ) classes.push( 'is-fullwidth' );
			if ( isMarquee ) classes.push( 'is-marquee' );
			if ( attrs.linkUrl ) classes.push( 'has-link' );

			const content = isMarquee
				? el(
						'span',
						{ className: 'hmpro-cb__marquee' },
						el(
							'span',
							{ className: 'hmpro-cb__marqueeContent' },
							[ 0, 1, 2, 3, 4, 5 ].map( function ( i ) {
								return el(
									'span',
									{ key: 'hmpro-cb-t-' + i, className: 'hmpro-cb__text' },
									attrs.text || ''
								);
							} )
						)
				  )
				: el( 'span', { className: 'hmpro-cb__text' }, attrs.text || '' );

			const blockProps = useBlockProps( {
				className: [ 'hmpro-cb-editorWrap' ].concat( classes ).join( ' ' ),
				style: styleVars,
				onMouseDown: ensureSelected,
				onClick: ensureSelected
			} );

			const preview = el(
				'div',
				Object.assign( { key: 'preview' }, blockProps ),
				el(
					'div',
					{ className: 'hmpro-cb__link', role: 'button', tabIndex: 0 },
					el( 'span', { className: 'hmpro-cb__inner' }, content )
				)
			);

			return [
				el(
					InspectorControls,
					{ key: 'inspector' },
					el(
						PanelBody,
						{ title: 'Content', initialOpen: true },
						el( TextareaControl, {
							label: 'Text',
							value: attrs.text,
							rows: 2,
							onChange: function ( v ) {
								setAttributes( { text: v } );
							}
						} ),
						el( TextControl, {
							label: 'Link URL',
							value: attrs.linkUrl,
							placeholder: 'https://',
							onChange: function ( v ) {
								setAttributes( { linkUrl: v } );
							}
						} ),
						el( ToggleControl, {
							label: 'Open in new tab',
							checked: !! attrs.openInNewTab,
							onChange: function ( v ) {
								setAttributes( { openInNewTab: !! v } );
							}
						} ),
						el( ToggleControl, {
							label: 'Nofollow',
							checked: !! attrs.nofollow,
							onChange: function ( v ) {
								setAttributes( { nofollow: !! v } );
							}
						} ),
						el( SelectControl, {
							label: 'Text Mode',
							value: textMode,
							options: [
								{ label: 'Static Text', value: 'static' },
								{ label: 'Scrolling / Marquee', value: 'marquee' }
							],
							onChange: function ( v ) {
								setAttributes( { textMode: v } );
							}
						} ),
						isMarquee
							? el( RangeControl, {
									label: 'Marquee Speed (seconds/loop)',
									min: 5,
									max: 120,
									step: 1,
									value: clampNum( attrs.marqueeSpeed, 5, 120, 18 ),
									onChange: function ( v ) {
										setAttributes( { marqueeSpeed: v } );
									}
							  } )
							: null,
						isMarquee
							? el( RangeControl, {
									label: 'Marquee Gap (px)',
									min: 8,
									max: 180,
									step: 1,
									value: clampNum( attrs.marqueeGap, 8, 180, 48 ),
									onChange: function ( v ) {
										setAttributes( { marqueeGap: v } );
									}
							  } )
							: null
					),
					el(
						PanelBody,
						{ title: 'Layout & Style', initialOpen: false },
						el( ToggleControl, {
							label: 'Full Width (100vw)',
							checked: !! attrs.fullWidth,
							onChange: function ( v ) {
								setAttributes( { fullWidth: !! v } );
							}
						} ),
						el( RangeControl, {
							label: 'Bar Thickness (height)',
							min: 30,
							max: 140,
							step: 1,
							value: clampNum( attrs.height, 30, 140, 56 ),
							onChange: function ( v ) {
								setAttributes( { height: v } );
							}
						} ),
						el( RangeControl, {
							label: 'Horizontal Padding (px)',
							min: 0,
							max: 80,
							step: 1,
							value: clampNum( attrs.paddingX, 0, 80, 18 ),
							onChange: function ( v ) {
								setAttributes( { paddingX: v } );
							}
						} ),
						el( RangeControl, {
							label: 'Border Radius (px)',
							min: 0,
							max: 50,
							step: 1,
							value: clampNum( attrs.borderRadius, 0, 50, 10 ),
							onChange: function ( v ) {
								setAttributes( { borderRadius: v } );
							}
						} ),
						el( 'div', { className: 'hmpro-cb__cpRow' }, 'Background' ),
						el( ColorPalette, {
							value: attrs.bgColor,
							onChange: function ( v ) {
								setAttributes( { bgColor: v } );
							}
						} ),
						el( 'div', { className: 'hmpro-cb__cpRow' }, 'Text Color' ),
						el( ColorPalette, {
							value: attrs.textColor,
							onChange: function ( v ) {
								setAttributes( { textColor: v } );
							}
						} )
					)
				),
				preview
			];
		},

		save: function () {
			return null; // dynamic render.php
		}
	} );
} )( window.wp );
