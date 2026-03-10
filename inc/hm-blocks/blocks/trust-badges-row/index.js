( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { __ } = wp.i18n;
	const { InspectorControls, RichText, useBlockProps } = wp.blockEditor;
	const { PanelBody, SelectControl, RangeControl, Button, BaseControl, ColorPalette } = wp.components;

	const ICONS = [
		{ label: 'Truck', value: 'truck', svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="7" cy="19" r="1.5"/><circle cx="18" cy="19" r="1.5"/></svg>' },
		{ label: 'Shield', value: 'shield', svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l8 4v6c0 5-3.5 9.4-8 10-4.5-.6-8-5-8-10V6l8-4z"/></svg>' },
		{ label: 'Return', value: 'return', svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14l-4-4 4-4"/><path d="M5 10h9a5 5 0 0 1 0 10H7"/></svg>' },
		{ label: 'Cash', value: 'cash', svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="10" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg>' },
		{ label: 'Box', value: 'box', svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v10l9 5 9-5V8"/><path d="M12 13v10"/></svg>' },
		{ label: 'Check', value: 'check', svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>' },
		{ label: 'Star', value: 'star', svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.1 6.3 7 .9-5.1 5 1.2 7-6.2-3.3-6.2 3.3 1.2-7-5.1-5 7-.9z"/></svg>' },
		{ label: 'Chat', value: 'chat', svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>' }
	];

	function getIconSvg( key ) {
		const found = ICONS.find( function ( p ) { return p.value === key; } );
		return found ? found.svg : ICONS[0].svg;
	}

	function clampInt( v, min, max ) {
		const n = parseInt( v, 10 );
		if ( isNaN( n ) ) return min;
		return Math.max( min, Math.min( max, n ) );
	}

	registerBlockType( 'hmpro/trust-badges-row', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const { align, items, columns, iconSize, titleSize, textSize, iconColor, titleColor, textColor, bgColor, borderColor } = attributes;

			const safeColumns = clampInt( columns || ( items ? items.length : 4 ), 2, 6 );
			const blockProps = useBlockProps( {
				className: [ 'hmpro-block', 'hmpro-trust-badges', 'is-align-' + ( align || 'center' ) ].join( ' ' ),
				style: {
					'--hmpro-tb-cols': safeColumns,
					'--hmpro-tb-icon': ( iconSize || 22 ) + 'px',
					'--hmpro-tb-title': ( titleSize || 14 ) + 'px',
					'--hmpro-tb-text': ( textSize || 12 ) + 'px',
					'--hmpro-tb-ic': iconColor || '',
					'--hmpro-tb-tc': titleColor || '',
					'--hmpro-tb-xc': textColor || '',
					'--hmpro-tb-bg': bgColor || '',
					'--hmpro-tb-bd': borderColor || ''
				}
			} );

			function setCount( nextCount ) {
				nextCount = clampInt( nextCount, 2, 6 );
				const current = Array.isArray( items ) ? items.slice() : [];
				const next = current.slice( 0, nextCount );
				while ( next.length < nextCount ) {
					next.push( { icon: 'check', title: 'Trust Badge', text: 'Short supporting text' } );
				}
				setAttributes( { items: next, columns: nextCount } );
			}

			function updateItem( idx, patch ) {
				const next = ( items || [] ).map( function ( it, i ) {
					return ( i === idx ) ? Object.assign( {}, it, patch ) : it;
				} );
				setAttributes( { items: next } );
			}

			return wp.element.createElement(
				wp.element.Fragment,
				null,
				wp.element.createElement(
					InspectorControls,
					null,
					wp.element.createElement(
						PanelBody,
						{ title: __( 'Trust Badges Row', 'hm-pro-theme' ), initialOpen: true },
						wp.element.createElement( SelectControl, {
							label: __( 'Align', 'hm-pro-theme' ),
							value: align,
							options: [
								{ label: 'Center', value: 'center' },
								{ label: 'Left', value: 'left' }
							],
							onChange: function ( v ) { setAttributes( { align: v } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: __( 'Badges Count', 'hm-pro-theme' ),
							value: ( items || [] ).length,
							min: 2,
							max: 6,
							onChange: function ( v ) { setCount( v ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: __( 'Icon Size (px)', 'hm-pro-theme' ),
							value: iconSize,
							min: 14,
							max: 48,
							onChange: function ( v ) { setAttributes( { iconSize: v || 22 } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: __( 'Title Size (px)', 'hm-pro-theme' ),
							value: titleSize,
							min: 12,
							max: 24,
							onChange: function ( v ) { setAttributes( { titleSize: v || 14 } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: __( 'Text Size (px)', 'hm-pro-theme' ),
							value: textSize,
							min: 10,
							max: 20,
							onChange: function ( v ) { setAttributes( { textSize: v || 12 } ); }
						} )
					)
				),
				wp.element.createElement(
					InspectorControls,
					null,
					wp.element.createElement(
						PanelBody,
						{ title: __( 'Colors', 'hm-pro-theme' ), initialOpen: false },
						wp.element.createElement( BaseControl, { label: __( 'Icon Color', 'hm-pro-theme' ) },
							wp.element.createElement( ColorPalette, {
								value: iconColor,
								onChange: function ( v ) { setAttributes( { iconColor: v || '' } ); }
							} )
						),
						wp.element.createElement( BaseControl, { label: __( 'Title Color', 'hm-pro-theme' ) },
							wp.element.createElement( ColorPalette, {
								value: titleColor,
								onChange: function ( v ) { setAttributes( { titleColor: v || '' } ); }
							} )
						),
						wp.element.createElement( BaseControl, { label: __( 'Text Color', 'hm-pro-theme' ) },
							wp.element.createElement( ColorPalette, {
								value: textColor,
								onChange: function ( v ) { setAttributes( { textColor: v || '' } ); }
							} )
						),
						wp.element.createElement( BaseControl, { label: __( 'Background', 'hm-pro-theme' ) },
							wp.element.createElement( ColorPalette, {
								value: bgColor,
								onChange: function ( v ) { setAttributes( { bgColor: v || '' } ); }
							} )
						),
						wp.element.createElement( BaseControl, { label: __( 'Border', 'hm-pro-theme' ) },
							wp.element.createElement( ColorPalette, {
								value: borderColor,
								onChange: function ( v ) { setAttributes( { borderColor: v || '' } ); }
							} )
						)
					)
				),
				wp.element.createElement(
					'div',
					blockProps,
					wp.element.createElement(
						'div',
						{ className: 'hmpro-tb__grid' },
						( items || [] ).map( function ( item, idx ) {
							return wp.element.createElement(
								'div',
								{ className: 'hmpro-tb__item', key: idx },
								wp.element.createElement(
									'div',
									{ className: 'hmpro-tb__icon', dangerouslySetInnerHTML: { __html: getIconSvg( item.icon || 'check' ) } }
								),
								wp.element.createElement(
									'div',
									{ className: 'hmpro-tb__content' },
									wp.element.createElement( RichText, {
										tagName: 'div',
										className: 'hmpro-tb__title',
										value: item.title,
										placeholder: __( 'Badge title…', 'hm-pro-theme' ),
										onChange: function ( v ) { updateItem( idx, { title: v } ); }
									} ),
									wp.element.createElement( RichText, {
										tagName: 'div',
										className: 'hmpro-tb__text',
										value: item.text,
										placeholder: __( 'Short supporting text…', 'hm-pro-theme' ),
										onChange: function ( v ) { updateItem( idx, { text: v } ); }
									} )
								),
								wp.element.createElement(
									'div',
									{ className: 'hmpro-tb__controls' },
									wp.element.createElement( SelectControl, {
										label: __( 'Icon', 'hm-pro-theme' ),
										value: item.icon || 'check',
										options: ICONS.map( function ( p ) { return { label: p.label, value: p.value }; } ),
										onChange: function ( v ) { updateItem( idx, { icon: v } ); }
									} ),
									wp.element.createElement( 'div', { className: 'hmpro-tb__icon-preview', dangerouslySetInnerHTML: { __html: getIconSvg( item.icon || 'check' ) } } )
								)
							);
						} )
					)
				)
			);
		},

		save: function () {
			// Dynamic render in PHP.
			return null;
		}
	} );
} )( window.wp );
