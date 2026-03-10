( function ( wp ) {
	"use strict";

	const { registerBlockType } = wp.blocks;
	const { InspectorControls, useBlockProps, MediaUpload, MediaUploadCheck, BlockControls } = wp.blockEditor || wp.editor;
	const { PanelBody, ToggleControl, RangeControl, SelectControl, Button, TextControl, ToolbarGroup, ToolbarButton, BaseControl, ColorPalette } = wp.components;
	const { Fragment, useEffect, useMemo, useRef, useState } = wp.element;

	const MAX_SLIDES = 12;
	const TYPO_PRESETS = {
		"modern_store": {
			label: "Modern Store (inter / poppins)",
			titleFamily: "Poppins, sans-serif",
			subtitleFamily: "Inter, sans-serif",
			buttonFamily: "Inter, sans-serif"
		},
		"editorial_fashion": {
			label: "Editorial / Fashion (inter / playfair_display)",
			titleFamily: "\"Playfair Display\", serif",
			subtitleFamily: "Inter, sans-serif",
			buttonFamily: "Inter, sans-serif"
		},
		"soft_elegant": {
			label: "Soft Elegant (lato / poppins)",
			titleFamily: "Poppins, sans-serif",
			subtitleFamily: "Lato, sans-serif",
			buttonFamily: "Lato, sans-serif"
		},
		"signature_handwritten": {
			label: "Signature Brand (Handwritten) (inter / dancing_script)",
			titleFamily: "\"Dancing Script\", cursive",
			subtitleFamily: "Inter, sans-serif",
			buttonFamily: "Inter, sans-serif"
		}
	};

	function getPreviewDeviceType() {
		try {
			if ( wp.data && wp.data.select ) {
				const sel = wp.data.select( "core/edit-post" ) || wp.data.select( "core/editor" );
				if ( sel && typeof sel.__experimentalGetPreviewDeviceType === "function" ) {
					return sel.__experimentalGetPreviewDeviceType();
				}
				if ( sel && typeof sel.getPreviewDeviceType === "function" ) {
					return sel.getPreviewDeviceType();
				}
			}
		} catch ( e ) {}
		return "Desktop";
	}

	function pickMediaUrlForDevice( slide, deviceType ) {
		const d = ( deviceType || "Desktop" ).toLowerCase();
		const desktop = slide.mediaUrl || "";
		const tablet = slide.mediaUrlTablet || "";
		const mobile = slide.mediaUrlMobile || "";
		if ( d.indexOf( "mobile" ) !== -1 ) return mobile || tablet || desktop;
		if ( d.indexOf( "tablet" ) !== -1 ) return tablet || desktop;
		return desktop;
	}

	function clamp( n, min, max ) {
		n = parseFloat( n );
		if ( isNaN( n ) ) n = min;
		if ( n < min ) n = min;
		if ( n > max ) n = max;
		return n;
	}

	function ensureMinSlides( slides ) {
		const arr = Array.isArray( slides ) ? slides.slice() : [];
		while ( arr.length < 1 ) {
			arr.push( {
				mediaId: 0,
				mediaUrl: "",
				mediaIdTablet: 0,
				mediaUrlTablet: "",
				mediaIdMobile: 0,
				mediaUrlMobile: "",
				mediaType: "image",
				title: "",
				subtitle: "",
				buttonText: "",
				buttonUrl: "",
				titleColor: "",
				subtitleColor: "",
				buttonTextColor: "",
				buttonBgColor: ""
			} );
		}
		// Hard normalize to IMAGE-only (legacy content may still have mediaType=video)
		for ( let i = 0; i < arr.length; i++ ) {
			arr[ i ] = Object.assign(
				{
					mediaId: 0,
					mediaUrl: "",
					mediaIdTablet: 0,
					mediaUrlTablet: "",
					mediaIdMobile: 0,
					mediaUrlMobile: "",
					mediaType: "image"
				},
				arr[ i ] || {},
				{ mediaType: "image" }
			);
		}
		if ( arr.length > MAX_SLIDES ) {
			return arr.slice( 0, MAX_SLIDES );
		}
		return arr;
	}

	registerBlockType( "hmpro/hero-slider", {
		edit: function ( props ) {
			const { attributes, setAttributes, isSelected } = props;
			const {
				slides,
				imageFit,
				mobileHeightMode,
				mobileHeightVh,
				onlyHomepage,
				fullWidth,
				maxWidth,
				height,
				hideOnMobile,
				overlayOpacity,
				autoplay,
				interval,
				showArrows,
				showDots,
				groupX,
				groupY,
				mobileGroupX,
				mobileGroupY,
				mobileTitleScale,
				contentScale,
				mobileContentScale,
				titleFontFamily,
				titleFontWeight,
				titleFontSize,
				titleFontSizeMobile,
				subtitleFontFamily,
				subtitleFontWeight,
				subtitleFontSize,
				subtitleFontSizeMobile,
				buttonFontFamily,
				buttonFontWeight,
				buttonFontSize,
				buttonFontSizeMobile,
				typographyPreset
			} = attributes;

			// Ensure minimum slides.
			useEffect( function () {
				const normalized = ensureMinSlides( slides );
				if ( JSON.stringify( normalized ) !== JSON.stringify( slides || [] ) ) {
					setAttributes( { slides: normalized } );
				}
			}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

			const [ active, setActive ] = useState( 0 );
			const timerRef = useRef( null );

			const normalizedSlides = useMemo( function () {
				return ensureMinSlides( slides );
			}, [ slides ] );

			const [ previewDevice, setPreviewDevice ] = useState( getPreviewDeviceType() );
			useEffect( function () {
				if ( ! wp.data || ! wp.data.subscribe ) return;
				let last = getPreviewDeviceType();
				const unsub = wp.data.subscribe( function () {
					const next = getPreviewDeviceType();
					if ( next !== last ) {
						last = next;
						setPreviewDevice( next );
					}
				} );
				return function () { try { unsub(); } catch ( e ) {} };
			}, [] );

			const current = normalizedSlides[ active ] || normalizedSlides[ 0 ] || {};

			function applyTypographyPreset( key ) {
				if ( !key || !TYPO_PRESETS[ key ] ) return;
				const preset = TYPO_PRESETS[ key ];
				setAttributes( {
					typographyPreset: key,
					titleFontFamily: preset.titleFamily || "",
					subtitleFontFamily: preset.subtitleFamily || "",
					buttonFontFamily: preset.buttonFamily || ""
				} );
			}

			function resetTypographyPreset() {
				setAttributes( {
					typographyPreset: "",
					titleFontFamily: "",
					titleFontWeight: "",
					titleFontSize: 0,
					titleFontSizeMobile: 0,
					subtitleFontFamily: "",
					subtitleFontWeight: "",
					subtitleFontSize: 0,
					subtitleFontSizeMobile: 0,
					buttonFontFamily: "",
					buttonFontWeight: "",
					buttonFontSize: 0,
					buttonFontSizeMobile: 0
				} );
			}

			function updateSlide( idx, patch ) {
				const next = normalizedSlides.slice();
				next[ idx ] = Object.assign( {}, next[ idx ] || {}, patch || {}, { mediaType: "image" } );
				setAttributes( { slides: ensureMinSlides( next ) } );
			}

			function updateSlides( next ) {
				setAttributes( { slides: ensureMinSlides( next ) } );
			}

			function setActiveSlideField( field, value ) {
				const next = normalizedSlides.slice();
				const cur = Object.assign( {}, next[ active ] || {} );
				cur[ field ] = value;
				next[ active ] = cur;
				updateSlides( next );
			}

			function onSelectTablet( media ) {
				const url = ( media && media.url ) ? media.url : "";
				const id = ( media && media.id ) ? media.id : 0;
				setActiveSlideField( "mediaIdTablet", id );
				setActiveSlideField( "mediaUrlTablet", url );
			}
			function onSelectMobile( media ) {
				const url = ( media && media.url ) ? media.url : "";
				const id = ( media && media.id ) ? media.id : 0;
				setActiveSlideField( "mediaIdMobile", id );
				setActiveSlideField( "mediaUrlMobile", url );
			}

			function addSlide() {
				if ( normalizedSlides.length >= MAX_SLIDES ) return;
				const next = normalizedSlides.concat( [ {
					mediaId: 0,
					mediaUrl: "",
					mediaIdTablet: 0,
					mediaUrlTablet: "",
					mediaIdMobile: 0,
					mediaUrlMobile: "",
					mediaType: "image",
					title: "",
					subtitle: "",
					buttonText: "",
					buttonUrl: "",
					titleColor: "",
					subtitleColor: "",
					buttonTextColor: "",
					buttonBgColor: ""
				} ] );
				setAttributes( { slides: ensureMinSlides( next ) } );
				setActive( next.length - 1 );
			}

			function duplicateSlide( idx ) {
				if ( normalizedSlides.length >= MAX_SLIDES ) return;
				const src = normalizedSlides[ idx ] || {};
				let clone;
				try {
					clone = JSON.parse( JSON.stringify( src ) );
				} catch ( e ) {
					clone = Object.assign( {}, src );
				}
				clone = Object.assign( {}, clone, { mediaType: "image" } );
				const next = normalizedSlides.slice();
				next.splice( idx + 1, 0, clone );
				setAttributes( { slides: ensureMinSlides( next ) } );
				setActive( idx + 1 );
			}

			function removeSlide( idx ) {
				if ( normalizedSlides.length <= 1 ) return;
				const next = normalizedSlides.slice();
				next.splice( idx, 1 );
				setAttributes( { slides: ensureMinSlides( next ) } );
				setActive( Math.max( 0, Math.min( active, next.length - 1 ) ) );
			}

			function moveSlide( idx, dir ) {
				const next = normalizedSlides.slice();
				const to = idx + dir;
				if ( to < 0 || to >= next.length ) return;
				const tmp = next[ idx ];
				next[ idx ] = next[ to ];
				next[ to ] = tmp;
				setAttributes( { slides: ensureMinSlides( next ) } );
				setActive( to );
			}

			function go( idx ) {
				const len = normalizedSlides.length;
				if ( !len ) return;
				let n = idx;
				if ( n < 0 ) n = len - 1;
				if ( n >= len ) n = 0;
				setActive( n );
			}

			// Editor autoplay preview (only when block is selected, to avoid background timers).
			useEffect( function () {
				if ( timerRef.current ) {
					clearInterval( timerRef.current );
					timerRef.current = null;
				}
				if ( !isSelected ) return;
				if ( !autoplay ) return;
				const d = clamp( interval, 1500, 20000 );
				timerRef.current = setInterval( function () {
					setActive( function ( prev ) {
						const len = normalizedSlides.length || 0;
						if ( len < 2 ) return 0;
						return ( prev + 1 ) % len;
					} );
				}, d );
				return function () {
					if ( timerRef.current ) {
						clearInterval( timerRef.current );
						timerRef.current = null;
					}
				};
			}, [ isSelected, autoplay, interval, normalizedSlides.length ] );

			const blockProps = useBlockProps( {
				className: [
					"hmpro-block",
					"hmpro-hero-slider",
					( mobileHeightMode && mobileHeightMode !== "auto" ) ? ( mobileHeightMode === "square" ? "is-mobile-square" : "is-mobile-compact" ) : "",
					fullWidth ? "is-fullwidth" : "is-boxed",
					hideOnMobile ? "is-hide-mobile" : ""
				].filter( Boolean ).join( " " ),
				style: {
					"--hmpro-hero-h": clamp( height, 200, 1200 ) + "px",
					"--hmpro-hero-bg-fit": ( imageFit === "contain" ? "contain" : "cover" ),
					"--hmpro-hero-h-m": clamp( mobileHeightVh || 56, 40, 80 ) + "vh",
					"--hmpro-hero-overlay": clamp( overlayOpacity, 0, 0.8 ),
					"--hmpro-hero-maxw": clamp( maxWidth, 720, 1800 ) + "px",
					"--hmpro-hero-group-x": clamp( groupX, -300, 300 ) + "px",
					"--hmpro-hero-group-y": clamp( groupY, -300, 300 ) + "px",
					"--hmpro-hero-group-x-m": clamp( mobileGroupX, -300, 300 ) + "px",
					"--hmpro-hero-group-y-m": clamp( mobileGroupY, -300, 300 ) + "px",
					"--hmpro-hero-scale": clamp( contentScale, 0.7, 1.3 ),
					"--hmpro-hero-scale-m": clamp( mobileContentScale, 0.6, 1.3 ),
					"--hmpro-hero-title-scale-m": clamp( mobileTitleScale, 0.6, 1.2 ),
					"--hmpro-hero-title-color": current.titleColor || "",
					"--hmpro-hero-subtitle-color": current.subtitleColor || "",
					"--hmpro-hero-btn-color": current.buttonTextColor || "",
					"--hmpro-hero-btn-bg": current.buttonBgColor || "",
					"--hmpro-hero-title-ff": titleFontFamily || "",
					"--hmpro-hero-title-fw": titleFontWeight || "",
					"--hmpro-hero-title-fs": titleFontSize ? ( clamp( titleFontSize, 12, 120 ) + "px" ) : "",
					"--hmpro-hero-title-fs-m": titleFontSizeMobile ? ( clamp( titleFontSizeMobile, 12, 96 ) + "px" ) : "",
					"--hmpro-hero-subtitle-ff": subtitleFontFamily || "",
					"--hmpro-hero-subtitle-fw": subtitleFontWeight || "",
					"--hmpro-hero-subtitle-fs": subtitleFontSize ? ( clamp( subtitleFontSize, 10, 60 ) + "px" ) : "",
					"--hmpro-hero-subtitle-fs-m": subtitleFontSizeMobile ? ( clamp( subtitleFontSizeMobile, 10, 54 ) + "px" ) : "",
					"--hmpro-hero-btn-ff": buttonFontFamily || "",
					"--hmpro-hero-btn-fw": buttonFontWeight || "",
					"--hmpro-hero-btn-fs": buttonFontSize ? ( clamp( buttonFontSize, 10, 42 ) + "px" ) : "",
					"--hmpro-hero-btn-fs-m": buttonFontSizeMobile ? ( clamp( buttonFontSizeMobile, 10, 38 ) + "px" ) : ""
				}
			} );

			function onSelectMedia( idx, media ) {
				if ( !media ) return;
				let url = "";
				if ( media.url ) url = media.url;
				updateSlide( idx, {
					mediaId: media.id || 0,
					mediaUrl: url,
					mediaType: "image"
				} );
			}

			function mediaPlaceholder( idx ) {
				const s = normalizedSlides[ idx ] || {};
				return wp.element.createElement(
					"div",
					{ className: "hmpro-hero-editor__mediaPick" },
					wp.element.createElement( "div", { className: "hmpro-hero-editor__mediaLabel" }, "Image" ),
					wp.element.createElement(
						MediaUploadCheck,
						null,
						wp.element.createElement( MediaUpload, {
							onSelect: function ( media ) { onSelectMedia( idx, media ); },
							allowedTypes: [ "image" ],
							value: s.mediaId || 0,
							render: function ( obj ) {
								return wp.element.createElement(
									Button,
									{ isPrimary: true, onClick: obj.open },
									s.mediaUrl ? "Replace Image" : "Select Image"
								);
							}
						} )
					)
				);
			}

			function renderMedia( s ) {
				const bgUrl = s ? pickMediaUrlForDevice( s, previewDevice ) : "";
				if ( bgUrl ) {
					return wp.element.createElement( "div", {
						className: "hmpro-hero__bgImage",
						style: { backgroundImage: "url(" + bgUrl + ")" }
					} );
				}
				return wp.element.createElement( "div", { className: "hmpro-hero__bgImage is-empty" } );
			}

			return wp.element.createElement(
				Fragment,
				null,
				wp.element.createElement(
					BlockControls,
					null,
					wp.element.createElement(
						ToolbarGroup,
						null,
						wp.element.createElement( ToolbarButton, {
							icon: "arrow-left-alt2",
							label: "Previous slide",
							onClick: function () { go( active - 1 ); }
						} ),
						wp.element.createElement( ToolbarButton, {
							icon: "arrow-right-alt2",
							label: "Next slide",
							onClick: function () { go( active + 1 ); }
						} )
					)
				),
				wp.element.createElement(
					InspectorControls,
					null,
					wp.element.createElement(
						PanelBody,
						{ title: "Slider Settings", initialOpen: true },
						wp.element.createElement( ToggleControl, {
							label: "Only show on homepage",
							checked: !!onlyHomepage,
							onChange: function ( v ) { setAttributes( { onlyHomepage: !!v } ); }
						} ),
						wp.element.createElement( ToggleControl, {
							label: "Full width (hero)",
							checked: !!fullWidth,
							onChange: function ( v ) { setAttributes( { fullWidth: !!v } ); }
						} ),
						wp.element.createElement( SelectControl, {
							label: "Image fit",
							help: "Cover fills the frame (may crop). Contain shows the full image (may letterbox).",
							value: ( imageFit === "contain" ? "contain" : "cover" ),
							options: [
								{ label: "Cover (fill)", value: "cover" },
								{ label: "Contain (show full image)", value: "contain" }
							],
							onChange: function ( v ) { setAttributes( { imageFit: ( v === "contain" ? "contain" : "cover" ) } ); }
						} ),
						wp.element.createElement( SelectControl, {
							label: "Mobile height mode",
							help: "Tune phone layout without affecting desktop/tablet.",
							value: ( mobileHeightMode === "compact" || mobileHeightMode === "square" ) ? mobileHeightMode : "auto",
							options: [
								{ label: "Auto (use Hero Height)", value: "auto" },
								{ label: "Compact (vh)", value: "compact" },
								{ label: "Square-ish (min(100vw, 70vh))", value: "square" }
							],
							onChange: function ( v ) { setAttributes( { mobileHeightMode: v } ); }
						} ),
						( ( mobileHeightMode === "compact" ) ? wp.element.createElement( RangeControl, {
							label: "Mobile height (vh)",
							help: "Recommended: 50–62",
							value: clamp( mobileHeightVh || 56, 40, 80 ),
							min: 40,
							max: 80,
							onChange: function ( v ) { setAttributes( { mobileHeightVh: v } ); }
						} ) : null ),
						wp.element.createElement( RangeControl, {
							label: "Max width (boxed mode)",
							value: clamp( maxWidth, 720, 1800 ),
							min: 720,
							max: 1800,
							step: 10,
							onChange: function ( v ) { setAttributes( { maxWidth: clamp( v, 720, 1800 ) } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: "Hero height (px)",
							help: "Tip: 520–760 is a good range. Increase if you use a larger title.",
							value: clamp( height, 200, 1200 ),
							min: 200,
							max: 1200,
							step: 10,
							withInputField: true,
							onChange: function ( v ) { setAttributes( { height: clamp( v, 200, 1200 ) } ); }
						} ),
						wp.element.createElement( ToggleControl, {
							label: "Hide on mobile",
							help: "Hides the entire slider on small screens.",
							checked: !!hideOnMobile,
							onChange: function ( v ) { setAttributes( { hideOnMobile: !!v } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: "Overlay opacity",
							value: clamp( overlayOpacity, 0, 0.8 ),
							min: 0,
							max: 0.8,
							step: 0.05,
							onChange: function ( v ) { setAttributes( { overlayOpacity: clamp( v, 0, 0.8 ) } ); }
						} )
					),
					wp.element.createElement(
						PanelBody,
						{ title: "Autoplay & Controls", initialOpen: false },
						wp.element.createElement( ToggleControl, {
							label: "Autoplay",
							checked: !!autoplay,
							onChange: function ( v ) { setAttributes( { autoplay: !!v } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: "Interval (ms)",
							value: clamp( interval, 1500, 20000 ),
							min: 1500,
							max: 20000,
							step: 100,
							disabled: !autoplay,
							onChange: function ( v ) { setAttributes( { interval: clamp( v, 1500, 20000 ) } ); }
						} ),
						wp.element.createElement( ToggleControl, {
							label: "Show arrows",
							checked: !!showArrows,
							onChange: function ( v ) { setAttributes( { showArrows: !!v } ); }
						} ),
						wp.element.createElement( ToggleControl, {
							label: "Show dots",
							checked: !!showDots,
							onChange: function ( v ) { setAttributes( { showDots: !!v } ); }
						} )
					),
					wp.element.createElement(
						PanelBody,
						{ title: "Active Slide Styles", initialOpen: false },
						wp.element.createElement(
							BaseControl,
							{ label: "Title color" },
							wp.element.createElement( ColorPalette, {
								value: current.titleColor || "",
								onChange: function ( v ) { updateSlide( active, { titleColor: v || "" } ); }
							} )
						),
						wp.element.createElement(
							BaseControl,
							{ label: "Subtitle color" },
							wp.element.createElement( ColorPalette, {
								value: current.subtitleColor || "",
								onChange: function ( v ) { updateSlide( active, { subtitleColor: v || "" } ); }
							} )
						),
						wp.element.createElement(
							BaseControl,
							{ label: "Button text color" },
							wp.element.createElement( ColorPalette, {
								value: current.buttonTextColor || "",
								onChange: function ( v ) { updateSlide( active, { buttonTextColor: v || "" } ); }
							} )
						),
						wp.element.createElement(
							BaseControl,
							{ label: "Button background" },
							wp.element.createElement( ColorPalette, {
								value: current.buttonBgColor || "",
								onChange: function ( v ) { updateSlide( active, { buttonBgColor: v || "" } ); }
							} )
						)
					),
					wp.element.createElement(
						PanelBody,
						{ title: "Typography", initialOpen: false },
						wp.element.createElement( SelectControl, {
							label: "Typography preset",
							help: "Applies a curated font combo. Make sure these fonts are loaded by the theme/site.",
							value: typographyPreset || "",
							options: [
								{ label: "— Select —", value: "" },
								{ label: "Modern Store (inter / poppins)", value: "modern_store" },
								{ label: "Editorial / Fashion (inter / playfair_display)", value: "editorial_fashion" },
								{ label: "Soft Elegant (lato / poppins)", value: "soft_elegant" },
								{ label: "Signature Brand (Handwritten) (inter / dancing_script)", value: "signature_handwritten" },
								{ label: "Reset (default)", value: "__reset" }
							],
							onChange: function ( value ) {
								if ( value === "__reset" ) {
									resetTypographyPreset();
									return;
								}
								applyTypographyPreset( value );
							}
						} ),
						wp.element.createElement( TextControl, {
							label: "Title font family",
							value: titleFontFamily || "",
							onChange: function ( v ) { setAttributes( { titleFontFamily: v } ); }
						} ),
						wp.element.createElement( TextControl, {
							label: "Title font weight",
							help: "e.g. 400, 600, 700",
							value: titleFontWeight || "",
							onChange: function ( v ) { setAttributes( { titleFontWeight: v } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: "Title size (desktop)",
							help: "0 = default",
							value: titleFontSize || 0,
							min: 0,
							max: 120,
							step: 1,
							onChange: function ( v ) { setAttributes( { titleFontSize: v || 0 } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: "Title size (mobile)",
							help: "0 = default",
							value: titleFontSizeMobile || 0,
							min: 0,
							max: 96,
							step: 1,
							onChange: function ( v ) { setAttributes( { titleFontSizeMobile: v || 0 } ); }
						} ),
						wp.element.createElement( TextControl, {
							label: "Subtitle font family",
							value: subtitleFontFamily || "",
							onChange: function ( v ) { setAttributes( { subtitleFontFamily: v } ); }
						} ),
						wp.element.createElement( TextControl, {
							label: "Subtitle font weight",
							help: "e.g. 400, 500, 600",
							value: subtitleFontWeight || "",
							onChange: function ( v ) { setAttributes( { subtitleFontWeight: v } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: "Subtitle size (desktop)",
							help: "0 = default",
							value: subtitleFontSize || 0,
							min: 0,
							max: 60,
							step: 1,
							onChange: function ( v ) { setAttributes( { subtitleFontSize: v || 0 } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: "Subtitle size (mobile)",
							help: "0 = default",
							value: subtitleFontSizeMobile || 0,
							min: 0,
							max: 54,
							step: 1,
							onChange: function ( v ) { setAttributes( { subtitleFontSizeMobile: v || 0 } ); }
						} ),
						wp.element.createElement( TextControl, {
							label: "Button font family",
							value: buttonFontFamily || "",
							onChange: function ( v ) { setAttributes( { buttonFontFamily: v } ); }
						} ),
						wp.element.createElement( TextControl, {
							label: "Button font weight",
							help: "e.g. 500, 600, 700",
							value: buttonFontWeight || "",
							onChange: function ( v ) { setAttributes( { buttonFontWeight: v } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: "Button size (desktop)",
							help: "0 = default",
							value: buttonFontSize || 0,
							min: 0,
							max: 42,
							step: 1,
							onChange: function ( v ) { setAttributes( { buttonFontSize: v || 0 } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: "Button size (mobile)",
							help: "0 = default",
							value: buttonFontSizeMobile || 0,
							min: 0,
							max: 38,
							step: 1,
							onChange: function ( v ) { setAttributes( { buttonFontSizeMobile: v || 0 } ); }
						} )
					),
					wp.element.createElement(
						PanelBody,
						{ title: "Content Position (Desktop/Mobile)", initialOpen: false },
						wp.element.createElement( RangeControl, {
							label: "Desktop X offset (px)",
							value: clamp( groupX, -300, 300 ),
							min: -300,
							max: 300,
							step: 1,
							onChange: function ( v ) { setAttributes( { groupX: clamp( v, -300, 300 ) } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: "Desktop Y offset (px)",
							value: clamp( groupY, -300, 300 ),
							min: -300,
							max: 300,
							step: 1,
							onChange: function ( v ) { setAttributes( { groupY: clamp( v, -300, 300 ) } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: "Mobile X offset (px)",
							value: clamp( mobileGroupX, -300, 300 ),
							min: -300,
							max: 300,
							step: 1,
							onChange: function ( v ) { setAttributes( { mobileGroupX: clamp( v, -300, 300 ) } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: "Mobile Y offset (px)",
							value: clamp( mobileGroupY, -300, 300 ),
							min: -300,
							max: 300,
							step: 1,
							onChange: function ( v ) { setAttributes( { mobileGroupY: clamp( v, -300, 300 ) } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: "Content scale (desktop)",
							value: clamp( contentScale, 0.7, 1.3 ),
							min: 0.7,
							max: 1.3,
							step: 0.02,
							onChange: function ( v ) { setAttributes( { contentScale: clamp( v, 0.7, 1.3 ) } ); }
						} ),
						wp.element.createElement( RangeControl, {
							label: "Content scale (mobile)",
							value: clamp( mobileContentScale, 0.6, 1.3 ),
							min: 0.6,
							max: 1.3,
							step: 0.02,
							onChange: function ( v ) { setAttributes( { mobileContentScale: clamp( v, 0.6, 1.3 ) } ); }
						} )
					),
					wp.element.createElement(
						PanelBody,
						{ title: "Slides (1–12)", initialOpen: true },
						wp.element.createElement(
							"div",
							{ className: "hmpro-hero-editor__slideList" },
							normalizedSlides.map( function ( s, idx ) {
								const isActive = idx === active;
								return wp.element.createElement(
									"div",
									{ key: idx, className: "hmpro-hero-editor__slideRow" + ( isActive ? " is-active" : "" ) },
									wp.element.createElement(
										Button,
										{
											isSecondary: true,
											onClick: function () { setActive( idx ); }
										},
										"Slide " + ( idx + 1 )
									),
									wp.element.createElement(
										"div",
										{ className: "hmpro-hero-editor__slideRowBtns" },
										wp.element.createElement( Button, {
											isSmall: true,
											isSecondary: true,
											disabled: normalizedSlides.length >= MAX_SLIDES,
											onClick: function () { duplicateSlide( idx ); }
										}, "Duplicate" ),
										wp.element.createElement( Button, {
											isSmall: true,
											isSecondary: true,
											disabled: idx === 0,
											onClick: function () { moveSlide( idx, -1 ); }
										}, "Up" ),
										wp.element.createElement( Button, {
											isSmall: true,
											isSecondary: true,
											disabled: idx === ( normalizedSlides.length - 1 ),
											onClick: function () { moveSlide( idx, 1 ); }
										}, "Down" ),
										wp.element.createElement( Button, {
											isSmall: true,
											isDestructive: true,
											disabled: normalizedSlides.length <= 1,
											onClick: function () { removeSlide( idx ); }
										}, "Remove" )
									)
								);
							} )
						),
						wp.element.createElement(
							Button,
							{ isPrimary: true, onClick: addSlide, disabled: normalizedSlides.length >= MAX_SLIDES },
							"Add Slide"
						)
					),
					wp.element.createElement(
						PanelBody,
						{ title: "Active Slide Media", initialOpen: false },
						wp.element.createElement(
							"div",
							{ style: { marginBottom: "10px" } },
							"Active: Slide " + ( active + 1 )
						),
						wp.element.createElement(
							"div",
							{ style: { display: "grid", gap: "10px" } },
							wp.element.createElement(
								"div",
								null,
								wp.element.createElement( "div", { style: { fontSize: "12px", opacity: 0.8, marginBottom: "6px" } }, "Tablet image (optional)" ),
								wp.element.createElement(
									MediaUploadCheck,
									null,
									wp.element.createElement( MediaUpload, {
										onSelect: onSelectTablet,
										allowedTypes: [ "image" ],
										value: current.mediaIdTablet || 0,
										render: function ( obj ) {
											return wp.element.createElement(
												Fragment,
												null,
												wp.element.createElement(
													Button,
													{ isSecondary: true, onClick: obj.open },
													current.mediaUrlTablet ? "Replace Tablet Image" : "Select Tablet Image"
												),
												current.mediaUrlTablet
													? wp.element.createElement( Button, {
														isLink: true,
														isDestructive: true,
														onClick: function () {
															setActiveSlideField( "mediaIdTablet", 0 );
															setActiveSlideField( "mediaUrlTablet", "" );
														},
														style: { marginLeft: "8px" }
													}, "Remove" )
													: null
											);
										}
									} )
								)
							),
							wp.element.createElement(
								"div",
								null,
								wp.element.createElement( "div", { style: { fontSize: "12px", opacity: 0.8, marginBottom: "6px" } }, "Mobile image (optional)" ),
								wp.element.createElement(
									MediaUploadCheck,
									null,
									wp.element.createElement( MediaUpload, {
										onSelect: onSelectMobile,
										allowedTypes: [ "image" ],
										value: current.mediaIdMobile || 0,
										render: function ( obj ) {
											return wp.element.createElement(
												Fragment,
												null,
												wp.element.createElement(
													Button,
													{ isSecondary: true, onClick: obj.open },
													current.mediaUrlMobile ? "Replace Mobile Image" : "Select Mobile Image"
												),
												current.mediaUrlMobile
													? wp.element.createElement( Button, {
														isLink: true,
														isDestructive: true,
														onClick: function () {
															setActiveSlideField( "mediaIdMobile", 0 );
															setActiveSlideField( "mediaUrlMobile", "" );
														},
														style: { marginLeft: "8px" }
													}, "Remove" )
													: null
											);
										}
									} )
								)
							)
						)
					)
				),
				wp.element.createElement(
					"div",
					blockProps,
					wp.element.createElement(
						"div",
						{ className: "hmpro-hero__frame" },
						wp.element.createElement( "div", { className: "hmpro-hero__media" }, renderMedia( current ) ),
						wp.element.createElement( "div", { className: "hmpro-hero__overlay", "aria-hidden": true } ),
						wp.element.createElement(
							"div",
							{ className: "hmpro-hero__inner" },
							wp.element.createElement(
								"div",
								{ className: "hmpro-hero__content" },
								wp.element.createElement(
									"div",
									{ className: "hmpro-hero-editor__activeMeta" },
									wp.element.createElement( "span", null, "Editing Slide " + ( active + 1 ) + " / " + normalizedSlides.length )
								),
								wp.element.createElement(
									"div",
									{ className: "hmpro-hero-editor__fields" },
									wp.element.createElement(
										"div",
										{ className: "hmpro-hero-editor__mediaBlock" },
										mediaPlaceholder( active )
									),
									wp.element.createElement( TextControl, {
										label: "Title",
										value: current.title || "",
										onChange: function ( v ) { updateSlide( active, { title: v } ); }
									} ),
									wp.element.createElement( TextControl, {
										label: "Subtitle",
										value: current.subtitle || "",
										onChange: function ( v ) { updateSlide( active, { subtitle: v } ); }
									} ),
									wp.element.createElement( TextControl, {
										label: "Button text",
										value: current.buttonText || "",
										onChange: function ( v ) { updateSlide( active, { buttonText: v } ); }
									} ),
									wp.element.createElement( TextControl, {
										label: "Button URL",
										value: current.buttonUrl || "",
										onChange: function ( v ) { updateSlide( active, { buttonUrl: v } ); }
									} )
								),
								wp.element.createElement(
									"div",
									{ className: "hmpro-hero__cta" },
									current.title ? wp.element.createElement( "div", { className: "hmpro-hero__title" }, current.title ) : null,
									current.subtitle ? wp.element.createElement( "div", { className: "hmpro-hero__subtitle" }, current.subtitle ) : null,
									( current.buttonText && current.buttonUrl ) ? wp.element.createElement( "a", { className: "hmpro-hero__btn", href: current.buttonUrl, onClick: function(e){ e.preventDefault(); } }, current.buttonText ) : null
								)
							),
							( showArrows && normalizedSlides.length > 1 ) ? wp.element.createElement(
								Fragment,
								null,
								wp.element.createElement( "button", {
									className: "hmpro-hero__arrow hmpro-hero__arrow--prev",
									type: "button",
									onClick: function () { go( active - 1 ); },
									"aria-label": "Previous slide"
								}, "‹" ),
								wp.element.createElement( "button", {
									className: "hmpro-hero__arrow hmpro-hero__arrow--next",
									type: "button",
									onClick: function () { go( active + 1 ); },
									"aria-label": "Next slide"
								}, "›" )
							) : null,
							( showDots && normalizedSlides.length > 1 ) ? wp.element.createElement(
								"div",
								{ className: "hmpro-hero__dots", role: "tablist", "aria-label": "Slides" },
								normalizedSlides.map( function ( s, idx ) {
									return wp.element.createElement( "button", {
										key: idx,
										type: "button",
										className: "hmpro-hero__dot" + ( idx === active ? " is-active" : "" ),
										onClick: function () { go( idx ); },
										"aria-label": "Go to slide " + ( idx + 1 )
									} );
								} )
							) : null
						)
					)
				)
			);
		},
		save: function () {
			// Dynamic render via render.php
			return null;
		}
	} );
} )( window.wp );
