( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { __ } = wp.i18n;

	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const {
		PanelBody,
		ToggleControl,
		RangeControl,
		TextControl,
		SelectControl,
		ComboboxControl,
		ColorPalette,
		Button,
		ButtonGroup,
		Notice,
	} = wp.components;

	const { useState, useEffect, useRef } = wp.element;
	const ServerSideRender = wp.serverSideRender;
	const apiFetch = wp.apiFetch;
	const el = wp.element.createElement;

	// (Preview helper removed; we render SSR directly in the wrapper below.)

	const Fragment = wp.element.Fragment;

	function clampInt( value, min, max ) {
		const n = parseInt( value, 10 );
		if ( Number.isNaN( n ) ) return min;
		return Math.max( min, Math.min( max, n ) );
	}

	function normalizeColor( v ) {
		return v ? String( v ) : '';
	}

	function useAllTerms( taxonomy ) {
		const [ terms, setTerms ] = useState( [] );
		const [ isLoading, setIsLoading ] = useState( true );
		const [ error, setError ] = useState( null );

		useEffect( function () {
			let isActive = true;

			async function fetchTerms() {
				setIsLoading( true );
				setError( null );

				// 1) Prefer admin-ajax (Elementor-style) to avoid REST pagination/header stripping
				// and security plugins that may block custom REST namespaces.
				try {
					const cfg = window.hmproPft || {};
					const ajaxUrl = window.ajaxurl || cfg.ajaxUrl; // wp-admin always exposes window.ajaxurl
					const nonce = cfg.nonce; // optional
					if ( ajaxUrl ) {
						const form = new window.URLSearchParams();
						form.append( 'action', 'hmpro_pft_get_terms' );
						form.append( 'taxonomy', taxonomy );
						if ( nonce ) {
							form.append( 'nonce', nonce );
						}
						const res = await window.fetch( ajaxUrl, {
							method: 'POST',
							credentials: 'same-origin',
							headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
							body: form.toString(),
						} );
						const json = await res.json();
						if ( json && json.success && Array.isArray( json.data ) ) {
							if ( isActive ) {
								setTerms( json.data );
							}
							return;
						}
					}
				} catch ( e ) {
					// fall through to REST methods
				}

				try {
					// Prefer our single-shot endpoint (avoids REST pagination header stripping on some hosts).
					const all = await apiFetch( {
						path: `/hmpro/v1/terms?taxonomy=${ taxonomy }`,
					} );
					if ( isActive ) {
						setTerms( Array.isArray( all ) ? all : [] );
					}
					return;
				} catch ( e ) {
					// Fallback to wp/v2 pagination.
				}

				try {
					const fetched = [];
					let page = 1;
					let totalPages = 1;
					let headerBasedPagination = false;
					const perPage = 100;
					const safetyMaxPages = 50;

					while ( page <= totalPages && page <= safetyMaxPages ) {
						let response;
						try {
							response = await apiFetch( {
								// Keep the payload small (important on large catalogs).
								// Order by name so the list is stable and searchable.
								path: `/wp/v2/${ taxonomy }?per_page=${ perPage }&hide_empty=0&page=${ page }&orderby=name&order=asc&_fields=id,name`,
								parse: false,
							} );
						} catch ( requestError ) {
							// Some hosts/caches strip pagination headers; if we overshoot the last page
							// WordPress may respond with an invalid page error. In headerless mode, treat
							// that as end-of-list instead of failing the entire dropdown.
							if ( ! headerBasedPagination ) {
								const errCode = requestError?.code || requestError?.data?.code;
								const errStatus = requestError?.data?.status;
								const isInvalidPage =
									errCode === 'rest_post_invalid_page_number' ||
									errCode === 'rest_invalid_param' ||
									errStatus === 400;
								if ( isInvalidPage ) {
									break;
								}
							}
							throw requestError;
						}
						const totalHeader = response.headers.get( 'X-WP-TotalPages' );
						if ( totalHeader ) {
							headerBasedPagination = true;
							totalPages = parseInt( totalHeader, 10 ) || 1;
						}
						const data = await response.json();

						if ( ! isActive ) {
							return;
						}

						if ( Array.isArray( data ) ) {
							fetched.push( ...data );
						}

						// Fallback pagination when REST pagination headers are missing
						if ( ! headerBasedPagination ) {
							if ( ! Array.isArray( data ) || data.length < perPage ) {
								break;
							}
							// No headers: keep going until we hit a short page or safetyMaxPages
							totalPages = safetyMaxPages;
						}

						page += 1;
					}

					if ( isActive ) {
						setTerms( fetched );
					}
				} catch ( fetchError ) {
					if ( isActive ) {
						setError( fetchError );
					}
				} finally {
					if ( isActive ) {
						setIsLoading( false );
					}
				}
			}

			fetchTerms();

			return function () {
				isActive = false;
			};
		}, [ taxonomy ] );

		return { terms, isLoading, error };
	}

	registerBlockType( 'hmpro/product-tabs', {
		title: __( 'HM Product Tabs', 'hm-pro-theme' ),
		icon: 'screenoptions',
		category: 'hmpro',
		description: __( 'Tabbed WooCommerce product grid with optional full width layout.', 'hm-pro-theme' ),
		supports: { html: false },

		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const [ liveOptions, setLiveOptions ] = useState( {} );
			const timersRef = useRef( {} );
			const {
				fullWidth,
				columnsDesktop,
				gridGap,
				gridMaxWidth,
				headerTitle,
				headerSubtitle,
				headerAlign,
				panelBgColor,
				panelBgOpacity,
				panelBorderColor,
				panelBorderWidth,
				panelRadius,
				tabsAlign,
				tabsLayout,
				tabs = [],
				tabTextColor,
				tabBgColor,
				tabTextHoverColor,
				tabBgHoverColor,
				tabTextActiveColor,
				tabBgActiveColor,
			} = attributes;

			// Fetch terms (searchable dropdown)
			const { terms: catTerms, error: catError } = useAllTerms( 'product_cat' );
			const { terms: tagTerms, error: tagError } = useAllTerms( 'product_tag' );

			function getTermOptions( queryType ) {
				const list = queryType === 'tag' ? tagTerms : catTerms;
				if ( ! Array.isArray( list ) ) return [];
				return list.map( function ( t ) {
					return { label: t.name, value: String( t.id ) };
				} );
			}

			function fetchTermsBySearch( index, queryType, input ) {
				const q = ( input || '' ).trim();
				if ( q.length < 2 ) {
					setLiveOptions( function ( prev ) {
						const next = { ...prev };
						delete next[ index ];
						return next;
					} );
					return;
				}

				if ( timersRef.current[ index ] ) {
					window.clearTimeout( timersRef.current[ index ] );
				}

				timersRef.current[ index ] = window.setTimeout( async function () {
					try {
						const taxonomy = queryType === 'tag' ? 'product_tag' : 'product_cat';
						const ajaxUrl = window.ajaxurl || ( window.hmproPft && window.hmproPft.ajaxUrl );
						if ( ! ajaxUrl ) return;

						const form = new window.URLSearchParams();
						form.append( 'action', 'hmpro_pft_get_terms' );
						form.append( 'taxonomy', taxonomy );
						form.append( 'search', q );
						if ( window.hmproPft && window.hmproPft.nonce ) {
							form.append( 'nonce', window.hmproPft.nonce );
						}

						const res = await window.fetch( ajaxUrl, {
							method: 'POST',
							credentials: 'same-origin',
							headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
							body: form.toString(),
						} );
						const json = await res.json();
						if ( json && json.success && Array.isArray( json.data ) ) {
							const opts = json.data.map( function ( t ) {
								return { label: t.name, value: String( t.id ) };
							} );
							setLiveOptions( function ( prev ) {
								return { ...prev, [ index ]: opts };
							} );
						}
					} catch ( e ) {
						// ignore silently; fallback options still exist
					}
				}, 250 );
			}

			function updateTab( index, patch ) {
				const next = [ ...tabs ];
				next[ index ] = Object.assign( {}, next[ index ], patch );
				setAttributes( { tabs: next } );
			}

			function addTab() {
				const next = [
					...tabs,
					{ title: 'Tab ' + ( tabs.length + 1 ), queryType: 'category', termId: 0, perPage: 12 },
				];
				setAttributes( { tabs: next } );
			}

			function removeTab( index ) {
				const next = tabs.filter( function ( _t, i ) { return i !== index; } );
				setAttributes( {
					tabs: next.length ? next : [ { title: 'Tab 1', queryType: 'category', termId: 0, perPage: 12 } ],
				} );
			}

			function moveTab( from, to ) {
				if ( to < 0 || to >= tabs.length ) return;
				const next = [ ...tabs ];
				const item = next.splice( from, 1 )[ 0 ];
				next.splice( to, 0, item );
				setAttributes( { tabs: next } );
			}

			const layoutPanel = el(
				PanelBody,
				{ title: __( 'Layout', 'hm-pro-theme' ), initialOpen: true },
				el( ToggleControl, {
					label: __( 'Full Width (Hero Like)', 'hm-pro-theme' ),
					checked: !! fullWidth,
					onChange: function ( v ) { setAttributes( { fullWidth: !! v } ); },
				} ),
				! fullWidth && el( RangeControl, {
					label: __( 'Grid Max Width (px)', 'hm-pro-theme' ),
					value: gridMaxWidth || 1200,
					onChange: function ( v ) { setAttributes( { gridMaxWidth: clampInt( v, 600, 2200 ) } ); },
					min: 600,
					max: 2200,
					step: 10,
				} ),
				el( RangeControl, {
					label: __( 'Cards Per View (Desktop)', 'hm-pro-theme' ),
					value: columnsDesktop,
					onChange: function ( v ) { setAttributes( { columnsDesktop: clampInt( v, 2, 6 ) } ); },
					min: 2,
					max: 6,
				} ),
				el( RangeControl, {
					label: __( 'Grid Gap (px)', 'hm-pro-theme' ),
					value: gridGap,
					onChange: function ( v ) { setAttributes( { gridGap: clampInt( v, 0, 80 ) } ); },
					min: 0,
					max: 80,
				} ),
								el( SelectControl, {
					label: __( 'Tabs Layout', 'hm-pro-theme' ),
					value: tabsLayout || 'horizontal',
					options: [
						{ label: __( 'Horizontal', 'hm-pro-theme' ), value: 'horizontal' },
						{ label: __( 'Vertical (Left)', 'hm-pro-theme' ), value: 'vertical' },
					],
					onChange: function ( v ) { setAttributes( { tabsLayout: v } ); },
				} ),
el( SelectControl, {
					label: __( 'Tabs Alignment', 'hm-pro-theme' ),
					value: tabsAlign,
					options: [
						{ label: __( 'Left', 'hm-pro-theme' ), value: 'flex-start' },
						{ label: __( 'Center', 'hm-pro-theme' ), value: 'center' },
						{ label: __( 'Right', 'hm-pro-theme' ), value: 'flex-end' },
					],
					onChange: function ( v ) { setAttributes( { tabsAlign: v } ); },
				} )
			);

			const colorsPanel = el(
				PanelBody,
				{ title: __( 'Tab Colors', 'hm-pro-theme' ), initialOpen: false },
				el( 'p', { style: { marginTop: 0 } }, __( 'Tip: set Active background dark + Active text light for readability.', 'hm-pro-theme' ) ),
				el( 'div', { style: { marginBottom: 12 } },
					el( 'strong', null, __( 'Normal Text', 'hm-pro-theme' ) ),
					el( ColorPalette, { value: tabTextColor, onChange: function ( v ) { setAttributes( { tabTextColor: normalizeColor( v ) } ); } } )
				),
				el( 'div', { style: { marginBottom: 12 } },
					el( 'strong', null, __( 'Normal Background', 'hm-pro-theme' ) ),
					el( ColorPalette, { value: tabBgColor, onChange: function ( v ) { setAttributes( { tabBgColor: normalizeColor( v ) } ); } } )
				),
				el( 'div', { style: { marginBottom: 12 } },
					el( 'strong', null, __( 'Hover Text', 'hm-pro-theme' ) ),
					el( ColorPalette, { value: tabTextHoverColor, onChange: function ( v ) { setAttributes( { tabTextHoverColor: normalizeColor( v ) } ); } } )
				),
				el( 'div', { style: { marginBottom: 12 } },
					el( 'strong', null, __( 'Hover Background', 'hm-pro-theme' ) ),
					el( ColorPalette, { value: tabBgHoverColor, onChange: function ( v ) { setAttributes( { tabBgHoverColor: normalizeColor( v ) } ); } } )
				),
				el( 'div', { style: { marginBottom: 12 } },
					el( 'strong', null, __( 'Active Text', 'hm-pro-theme' ) ),
					el( ColorPalette, { value: tabTextActiveColor, onChange: function ( v ) { setAttributes( { tabTextActiveColor: normalizeColor( v ) } ); } } )
				),
				el( 'div', { style: { marginBottom: 0 } },
					el( 'strong', null, __( 'Active Background', 'hm-pro-theme' ) ),
					el( ColorPalette, { value: tabBgActiveColor, onChange: function ( v ) { setAttributes( { tabBgActiveColor: normalizeColor( v ) } ); } } )
				)
			);

			
			const headerPanel = el(
				PanelBody,
				{ title: __( 'Header', 'hm-pro-theme' ), initialOpen: false },
				el( TextControl, {
					label: __( 'Title', 'hm-pro-theme' ),
					value: headerTitle || '',
					onChange: function ( v ) { setAttributes( { headerTitle: v } ); },
				} ),
				el( TextControl, {
					label: __( 'Subtitle', 'hm-pro-theme' ),
					value: headerSubtitle || '',
					onChange: function ( v ) { setAttributes( { headerSubtitle: v } ); },
				} ),
				el( SelectControl, {
					label: __( 'Alignment', 'hm-pro-theme' ),
					value: headerAlign || 'center',
					options: [
						{ label: __( 'Left', 'hm-pro-theme' ), value: 'left' },
						{ label: __( 'Center', 'hm-pro-theme' ), value: 'center' },
						{ label: __( 'Right', 'hm-pro-theme' ), value: 'right' },
					],
					onChange: function ( v ) { setAttributes( { headerAlign: v } ); },
				} )
			);

			const containerPanel = el(
				PanelBody,
				{ title: __( 'Container', 'hm-pro-theme' ), initialOpen: false },
				el( 'p', { style: { marginTop: 0, opacity: 0.8 } }, __( 'Optional panel styling around tabs + grid.', 'hm-pro-theme' ) ),
				el( 'div', { style: { marginBottom: 12 } },
					el( 'strong', null, __( 'Background', 'hm-pro-theme' ) ),
					el( ColorPalette, { value: panelBgColor, onChange: function ( v ) { setAttributes( { panelBgColor: normalizeColor( v ) } ); } } )
				),
				el( RangeControl, {
					label: __( 'Background Opacity', 'hm-pro-theme' ),
					value: typeof panelBgOpacity === 'number' ? panelBgOpacity : 1,
					onChange: function ( v ) { setAttributes( { panelBgOpacity: Math.max( 0, Math.min( 1, parseFloat( v ) ) ) } ); },
					min: 0,
					max: 1,
					step: 0.05,
				} ),
				el( 'div', { style: { marginBottom: 12 } },
					el( 'strong', null, __( 'Border Color', 'hm-pro-theme' ) ),
					el( ColorPalette, { value: panelBorderColor, onChange: function ( v ) { setAttributes( { panelBorderColor: normalizeColor( v ) } ); } } )
				),
				el( RangeControl, {
					label: __( 'Border Width (px)', 'hm-pro-theme' ),
					value: panelBorderWidth || 0,
					onChange: function ( v ) { setAttributes( { panelBorderWidth: clampInt( v, 0, 12 ) } ); },
					min: 0,
					max: 12,
				} ),
				el( RangeControl, {
					label: __( 'Border Radius (px)', 'hm-pro-theme' ),
					value: panelRadius || 16,
					onChange: function ( v ) { setAttributes( { panelRadius: clampInt( v, 0, 48 ) } ); },
					min: 0,
					max: 48,
				} )
			);

const tabsControls = tabs.map( function ( tab, index ) {
				const queryType = tab.queryType || 'category';
				return el(
					'div',
					{ key: index, style: { padding: '12px 0', borderTop: '1px solid rgba(0,0,0,0.08)' } },
					el( TextControl, {
						label: __( 'Tab Title', 'hm-pro-theme' ),
						value: tab.title || '',
						onChange: function ( v ) { updateTab( index, { title: v } ); },
					} ),
					el( SelectControl, {
						label: __( 'Query Type', 'hm-pro-theme' ),
						value: queryType,
						options: [
							{ label: __( 'By Category', 'hm-pro-theme' ), value: 'category' },
							{ label: __( 'By Tag', 'hm-pro-theme' ), value: 'tag' },
						],
						onChange: function ( v ) { updateTab( index, { queryType: v, termId: 0 } ); },
					} ),
					el( ComboboxControl, {
						label: __( 'Select Term', 'hm-pro-theme' ),
						value: String( tab.termId ?? 0 ),
						options: liveOptions[ index ] || getTermOptions( queryType ),
						onChange: function ( v ) { updateTab( index, { termId: clampInt( v, 0, 999999 ) } ); },
						onFilterValueChange: function ( input ) {
							fetchTermsBySearch( index, queryType, input );
						},
						help: __( 'Search and pick a product category or tag.', 'hm-pro-theme' ),
					} ),
					el( RangeControl, {
						label: __( 'Products Per Tab (Max 24)', 'hm-pro-theme' ),
						value: tab.perPage ?? 12,
						onChange: function ( v ) { updateTab( index, { perPage: clampInt( v, 1, 24 ) } ); },
						min: 1,
						max: 24,
					} ),
					el(
						ButtonGroup,
						null,
						el( Button, {
							variant: 'secondary',
							onClick: function () { moveTab( index, index - 1 ); },
							disabled: index === 0,
						}, __( 'Up', 'hm-pro-theme' ) ),
						el( Button, {
							variant: 'secondary',
							onClick: function () { moveTab( index, index + 1 ); },
							disabled: index === ( tabs.length - 1 ),
						}, __( 'Down', 'hm-pro-theme' ) ),
						el( Button, {
							variant: 'secondary',
							isDestructive: true,
							onClick: function () { removeTab( index ); },
						}, __( 'Remove', 'hm-pro-theme' ) )
					)
				);
			} );

			const tabsPanel = el(
				PanelBody,
				{ title: __( 'Tabs', 'hm-pro-theme' ), initialOpen: true },
				el( Notice, { status: 'info', isDismissible: false },
					__( 'Term selection is searchable and loads all product categories/tags automatically.', 'hm-pro-theme' )
				),
				( catError || tagError ) && el( Notice, { status: 'warning', isDismissible: false },
					__( 'Some terms could not be loaded. Please refresh or try again later.', 'hm-pro-theme' )
				),
				...tabsControls,
				el(
					'div',
					{ style: { paddingTop: 12 } },
					el( Button, { variant: 'primary', onClick: addTab }, __( 'Add Tab', 'hm-pro-theme' ) )
				)
			);

			const blockProps = useBlockProps( { className: 'hmpro-pft__editor' } );

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					headerPanel,
					containerPanel,
					layoutPanel,
					colorsPanel,
					tabsPanel
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, { block: 'hmpro/product-tabs', attributes: attributes } )
				)
			);
		},

		save: function () {
			return null;
		},
	} );
} )( window.wp );
