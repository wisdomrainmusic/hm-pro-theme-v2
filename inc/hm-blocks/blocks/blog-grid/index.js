( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { __ } = wp.i18n;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, RangeControl, SelectControl, ToggleControl, TextControl, CheckboxControl } = wp.components;
	const { useSelect } = wp.data;

	registerBlockType( 'hmpro/blog-grid', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const {
				postsPerPage, orderby, order, category,
				columnsDesktop, columnsTablet, columnsMobile, gap,
				showImage, imageRatio,
				showExcerpt, excerptLength,
				showMeta, metaStyle,
				cardStyle, buttonLabel,
				enableCategoryTabs, tabsPosition, tabsSource, selectedCategories, allTabLabel,
				enablePagination, paginationPrevLabel, paginationNextLabel
			} = attributes;

			const categories = useSelect( function ( select ) {
				const core = select( 'core' );
				const terms = core && core.getEntityRecords
					? core.getEntityRecords( 'taxonomy', 'category', { per_page: 100, hide_empty: false } )
					: null;
				return Array.isArray( terms ) ? terms : [];
			}, [] );

			const blockProps = useBlockProps( {
				className: 'hmpro-blog-grid is-style-' + ( cardStyle || 'soft' ),
				style: {
					'--hmpro-bg-cols-d': columnsDesktop,
					'--hmpro-bg-cols-t': columnsTablet,
					'--hmpro-bg-cols-m': columnsMobile,
					'--hmpro-bg-gap': ( gap || 0 ) + 'px',
					'--hmpro-bg-aspect': ( function () {
						const m = String( imageRatio || '16:9' ).match( /^(\d+)\s*[:\/]\s*(\d+)$/ );
						return m ? ( parseInt( m[1], 10 ) + '/' + parseInt( m[2], 10 ) ) : '16/9';
					} )()
				}
			} );

			// Simple editor preview (dummy cards) – frontend uses real WP_Query in render.php.
			const dummy = new Array( Math.min( 6, Math.max( 1, postsPerPage || 6 ) ) ).fill( 0 );

			return [
				wp.element.createElement(
					InspectorControls,
					{ key: 'inspector' },
					wp.element.createElement(
						PanelBody,
						{ title: __( 'Query', 'hm-pro-theme' ), initialOpen: true },
						wp.element.createElement( RangeControl, {
							label: __( 'Posts per page', 'hm-pro-theme' ),
							min: 1,
							max: 24,
							value: postsPerPage,
							onChange: function ( v ) { setAttributes( { postsPerPage: v } ); }
						} ),
						wp.element.createElement( SelectControl, {
							label: __( 'Order by', 'hm-pro-theme' ),
							value: orderby,
							options: [
								{ label: 'Date', value: 'date' },
								{ label: 'Modified', value: 'modified' },
								{ label: 'Title', value: 'title' },
								{ label: 'Comment count', value: 'comment_count' },
								{ label: 'Random', value: 'rand' }
							],
							onChange: function ( v ) { setAttributes( { orderby: v } ); }
						} ),
						wp.element.createElement( SelectControl, {
							label: __( 'Order', 'hm-pro-theme' ),
							value: order,
							options: [
								{ label: 'DESC', value: 'DESC' },
								{ label: 'ASC', value: 'ASC' }
							],
							onChange: function ( v ) { setAttributes( { order: v } ); }
						} ),
						wp.element.createElement( TextControl, {
							label: __( 'Category ID (optional)', 'hm-pro-theme' ),
							help: __( 'Use 0 for all categories. You can find the ID in Posts → Categories.', 'hm-pro-theme' ),
							value: category,
							onChange: function ( v ) { setAttributes( { category: parseInt( v || '0', 10 ) || 0 } ); }
						} )
					),
					wp.element.createElement(
						PanelBody,
						{ title: __( 'Filters', 'hm-pro-theme' ), initialOpen: false },
						wp.element.createElement( ToggleControl, {
							label: __( 'Enable category tabs', 'hm-pro-theme' ),
							checked: !! enableCategoryTabs,
							onChange: function ( v ) { setAttributes( { enableCategoryTabs: !! v } ); }
						} ),
						enableCategoryTabs ? wp.element.createElement( SelectControl, {
							label: __( 'Tabs position', 'hm-pro-theme' ),
							value: tabsPosition || 'top',
							options: [
								{ label: 'Top', value: 'top' },
								{ label: 'Left', value: 'left' }
							],
							onChange: function ( v ) { setAttributes( { tabsPosition: v } ); }
						} ) : null,
						enableCategoryTabs ? wp.element.createElement( SelectControl, {
							label: __( 'Tabs source', 'hm-pro-theme' ),
							value: tabsSource || 'all',
							options: [
								{ label: 'All categories', value: 'all' },
								{ label: 'Selected categories', value: 'selected' }
							],
							onChange: function ( v ) { setAttributes( { tabsSource: v } ); }
						} ) : null,
						enableCategoryTabs ? wp.element.createElement( TextControl, {
							label: __( '"All" tab label', 'hm-pro-theme' ),
							value: allTabLabel,
							onChange: function ( v ) { setAttributes( { allTabLabel: v } ); }
						} ) : null,
						enableCategoryTabs && ( tabsSource === 'selected' ) ? wp.element.createElement(
							'div',
							{ className: 'hmpro-blog-grid__term-picker' },
							( categories || [] ).map( function ( term ) {
								const id = term && term.id ? term.id : 0;
								if ( ! id ) { return null; }
								const checked = Array.isArray( selectedCategories ) && selectedCategories.indexOf( id ) !== -1;
								return wp.element.createElement( CheckboxControl, {
									key: id,
									label: term.name || ( 'Category ' + id ),
									checked: checked,
									onChange: function ( v ) {
										const next = Array.isArray( selectedCategories ) ? selectedCategories.slice() : [];
										const idx = next.indexOf( id );
										if ( v && idx === -1 ) { next.push( id ); }
										if ( ! v && idx !== -1 ) { next.splice( idx, 1 ); }
										setAttributes( { selectedCategories: next } );
									}
								} );
							} )
						) : null
					),
					wp.element.createElement(
						PanelBody,
						{ title: __( 'Pagination', 'hm-pro-theme' ), initialOpen: false },
						wp.element.createElement( ToggleControl, {
							label: __( 'Enable pagination', 'hm-pro-theme' ),
							checked: !! enablePagination,
							onChange: function ( v ) { setAttributes( { enablePagination: !! v } ); }
						} ),
						enablePagination ? wp.element.createElement( TextControl, {
							label: __( 'Previous label', 'hm-pro-theme' ),
							value: paginationPrevLabel,
							onChange: function ( v ) { setAttributes( { paginationPrevLabel: v } ); }
						} ) : null,
						enablePagination ? wp.element.createElement( TextControl, {
							label: __( 'Next label', 'hm-pro-theme' ),
							value: paginationNextLabel,
							onChange: function ( v ) { setAttributes( { paginationNextLabel: v } ); }
						} ) : null
					),
					wp.element.createElement(
						PanelBody,
						{ title: __( 'Layout', 'hm-pro-theme' ), initialOpen: false },
						wp.element.createElement( RangeControl, {
							label: __( 'Columns (desktop)', 'hm-pro-theme' ),
							min: 1,
							max: 6,
							value: columnsDesktop,
							onChange: function ( v ) { setAttributes( { columnsDesktop: v } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: __( 'Columns (tablet)', 'hm-pro-theme' ),
							min: 1,
							max: 4,
							value: columnsTablet,
							onChange: function ( v ) { setAttributes( { columnsTablet: v } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: __( 'Columns (mobile)', 'hm-pro-theme' ),
							min: 1,
							max: 2,
							value: columnsMobile,
							onChange: function ( v ) { setAttributes( { columnsMobile: v } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: __( 'Gap', 'hm-pro-theme' ),
							min: 0,
							max: 64,
							value: gap,
							onChange: function ( v ) { setAttributes( { gap: v } ); }
						} )
					),
					wp.element.createElement(
						PanelBody,
						{ title: __( 'Card', 'hm-pro-theme' ), initialOpen: false },
						wp.element.createElement( SelectControl, {
							label: __( 'Card style', 'hm-pro-theme' ),
							value: cardStyle,
							options: [
								{ label: 'Soft', value: 'soft' },
								{ label: 'Flat', value: 'flat' }
							],
							onChange: function ( v ) { setAttributes( { cardStyle: v } ); }
						} ),
						wp.element.createElement( ToggleControl, {
							label: __( 'Show featured image', 'hm-pro-theme' ),
							checked: !! showImage,
							onChange: function ( v ) { setAttributes( { showImage: !! v } ); }
						} ),
						wp.element.createElement( TextControl, {
							label: __( 'Image ratio (e.g. 16:9)', 'hm-pro-theme' ),
							value: imageRatio,
							onChange: function ( v ) { setAttributes( { imageRatio: v } ); }
						} ),
						wp.element.createElement( ToggleControl, {
							label: __( 'Show meta', 'hm-pro-theme' ),
							checked: !! showMeta,
							onChange: function ( v ) { setAttributes( { showMeta: !! v } ); }
						} ),
						wp.element.createElement( SelectControl, {
							label: __( 'Meta style', 'hm-pro-theme' ),
							value: metaStyle,
							options: [
								{ label: 'Date', value: 'date' },
								{ label: 'Author', value: 'author' },
								{ label: 'Date + Author', value: 'date_author' }
							],
							onChange: function ( v ) { setAttributes( { metaStyle: v } ); }
						} ),
						wp.element.createElement( ToggleControl, {
							label: __( 'Show excerpt', 'hm-pro-theme' ),
							checked: !! showExcerpt,
							onChange: function ( v ) { setAttributes( { showExcerpt: !! v } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: __( 'Excerpt length (words)', 'hm-pro-theme' ),
							min: 5,
							max: 80,
							value: excerptLength,
							onChange: function ( v ) { setAttributes( { excerptLength: v } ); }
						} ),
						wp.element.createElement( TextControl, {
							label: __( 'Button label', 'hm-pro-theme' ),
							value: buttonLabel,
							onChange: function ( v ) { setAttributes( { buttonLabel: v } ); }
						} )
					)
				),

				wp.element.createElement(
					'div',
					Object.assign( { key: 'content' }, blockProps ),
					wp.element.createElement(
						'div',
						{ className: 'hmpro-blog-grid__placeholder' },
						wp.element.createElement( 'strong', null, __( 'HM Blog Grid', 'hm-pro-theme' ) ),
						wp.element.createElement( 'div', null, __( 'This is an editor preview. Real posts render on the frontend.', 'hm-pro-theme' ) )
					),
					wp.element.createElement(
						'div',
						{ className: 'hmpro-blog-grid__inner', style: { marginTop: '14px' } },
						dummy.map( function ( _, i ) {
							return wp.element.createElement(
								'div',
								{ key: i, className: 'hmpro-blog-card' },
								showImage ? wp.element.createElement( 'div', { className: 'hmpro-blog-card__media' } ) : null,
								wp.element.createElement(
									'div',
									{ className: 'hmpro-blog-card__content' },
									showMeta ? wp.element.createElement( 'div', { className: 'hmpro-blog-card__meta' }, __( 'Meta', 'hm-pro-theme' ) ) : null,
									wp.element.createElement( 'div', { className: 'hmpro-blog-card__title' }, __( 'Post title', 'hm-pro-theme' ) ),
									showExcerpt ? wp.element.createElement( 'div', { className: 'hmpro-blog-card__excerpt' }, __( 'Excerpt preview…', 'hm-pro-theme' ) ) : null,
									wp.element.createElement( 'div', { className: 'hmpro-blog-card__button' }, buttonLabel || __( 'Read more', 'hm-pro-theme' ) )
								)
							);
						} )
					)
				)
			];
		},
		save: function () {
			return null; // dynamic
		}
	} );
} )( window.wp );
