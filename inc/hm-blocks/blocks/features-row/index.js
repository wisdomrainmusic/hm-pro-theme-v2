( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { __ } = wp.i18n;
	const { InspectorControls, InnerBlocks, useBlockProps } = wp.blockEditor;
	const { PanelBody, RangeControl, ToggleControl, SelectControl } = wp.components;

	const ALLOWED = [ 'hmpro/feature-item' ];
	const TEMPLATE = [
		[ 'hmpro/feature-item', { title: 'Fast setup', text: 'Add feature items with presets.' } ],
		[ 'hmpro/feature-item', { title: 'SVG presets', text: 'Pick from icon presets or paste custom SVG.' } ],
		[ 'hmpro/feature-item', { title: 'Clean output', text: 'Stable frontend HTML via PHP render.' } ]
	];

	registerBlockType( 'hmpro/features-row', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const { columns, gap, contentWidth, isSurface, stackOnMobile } = attributes;

			const blockProps = useBlockProps( {
				className: [
					'hmpro-block',
					'hmpro-features-row',
					isSurface ? 'hmpro-surface' : '',
					stackOnMobile ? 'is-stack-mobile' : ''
				].filter( Boolean ).join( ' ' ),
				style: {
					'--hmpro-fr-cols': columns,
					'--hmpro-fr-gap': gap + 'px'
				}
			} );

			return wp.element.createElement(
				wp.element.Fragment,
				null,
				wp.element.createElement(
					InspectorControls,
					null,
					wp.element.createElement(
						PanelBody,
						{ title: __( 'Features Row Settings', 'hm-pro-theme' ), initialOpen: true },
						wp.element.createElement( RangeControl, {
							label: __( 'Columns', 'hm-pro-theme' ),
							value: columns,
							min: 1,
							max: 6,
							onChange: function ( v ) { setAttributes( { columns: v || 3 } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: __( 'Gap (px)', 'hm-pro-theme' ),
							value: gap,
							min: 0,
							max: 80,
							onChange: function ( v ) { setAttributes( { gap: v || 0 } ); }
						} ),
						wp.element.createElement( SelectControl, {
							label: __( 'Content Width', 'hm-pro-theme' ),
							value: contentWidth,
							options: [
								{ label: 'Wide', value: 'wide' },
								{ label: 'Narrow', value: 'narrow' }
							],
							onChange: function ( v ) { setAttributes( { contentWidth: v } ); }
						} ),
						wp.element.createElement( ToggleControl, {
							label: __( 'Surface (use theme surface token)', 'hm-pro-theme' ),
							checked: !! isSurface,
							onChange: function ( v ) { setAttributes( { isSurface: !! v } ); }
						} ),
						wp.element.createElement( ToggleControl, {
							label: __( 'Stack on mobile', 'hm-pro-theme' ),
							checked: !! stackOnMobile,
							onChange: function ( v ) { setAttributes( { stackOnMobile: !! v } ); }
						} )
					)
				),
				wp.element.createElement(
					'div',
					blockProps,
					wp.element.createElement(
						'div',
						{ className: 'hmpro-features-row__inner is-' + contentWidth },
						wp.element.createElement( InnerBlocks, {
							allowedBlocks: ALLOWED,
							template: TEMPLATE,
							templateLock: false
						} )
					)
				)
			);
		},

		// Dynamic render in PHP (stable frontend HTML).
		save: function () {
			/**
			 * IMPORTANT:
			 * This block uses InnerBlocks (hmpro/feature-item). We must serialize
			 * inner blocks into post_content so the PHP renderer receives them
			 * in $content. Otherwise frontend output is empty.
			 *
			 * Wrapper markup is generated in render.php, so only serialize children.
			 */
			return wp.element.createElement( InnerBlocks.Content, null );
		}
	} );
} )( window.wp );
