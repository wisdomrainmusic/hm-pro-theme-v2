( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { InspectorControls, InnerBlocks, useBlockProps } = wp.blockEditor;
	const { PanelBody, ToggleControl, SelectControl } = wp.components;

	registerBlockType( 'hmpro/section', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const { isSurface, contentWidth } = attributes;

			const blockProps = useBlockProps( {
				className: [
					'hmpro-block',
					'hmpro-section',
					isSurface ? 'hmpro-surface' : ''
				].filter( Boolean ).join( ' ' ),
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
							{ title: 'Section Settings', initialOpen: true },
							wp.element.createElement( ToggleControl, {
								label: 'Surface (use theme surface token)',
								checked: !! isSurface,
								onChange: function ( v ) {
									setAttributes( { isSurface: !! v } );
								}
							} ),
							wp.element.createElement( SelectControl, {
								label: 'Content Width',
								value: contentWidth,
								options: [
									{ label: 'Wide', value: 'wide' },
									{ label: 'Narrow', value: 'narrow' }
								],
								onChange: function ( v ) {
									setAttributes( { contentWidth: v } );
								}
							} )
						)
					),
					wp.element.createElement(
						'section',
						blockProps,
						wp.element.createElement(
							'div',
							{ className: 'hmpro-section__inner is-' + contentWidth },
							wp.element.createElement( InnerBlocks )
						)
					)
				)
			);
		},

		save: function ( props ) {
			const { attributes } = props;
			const { isSurface, contentWidth } = attributes;

			const blockProps = wp.blockEditor.useBlockProps.save( {
				className: [
					'hmpro-block',
					'hmpro-section',
					isSurface ? 'hmpro-surface' : ''
				].filter( Boolean ).join( ' ' ),
			} );

			return wp.element.createElement(
				'section',
				blockProps,
				wp.element.createElement(
					'div',
					{ className: 'hmpro-section__inner is-' + contentWidth },
					wp.element.createElement( wp.blockEditor.InnerBlocks.Content )
				)
			);
		}
	} );
} )( window.wp );
