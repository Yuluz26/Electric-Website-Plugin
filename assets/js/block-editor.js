/**
 * EV Charging Experience — generic block editor.
 *
 * One shared edit() component drives all 9 evpx/* blocks from the control
 * schema PHP passes down as EVPX_BLOCKS (see Elements\Registry::editorSchema()
 * and Elements\Element's $controls contract). This keeps the 9 blocks
 * consistent and avoids hand-writing 9 near-identical edit components.
 *
 * No JSX / no build step: plain wp.element.createElement calls, matching
 * how the rest of this plugin avoids an npm build pipeline.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.element ) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var InnerBlocks = wp.blockEditor.InnerBlocks;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var ServerSideRender = wp.serverSideRender;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var ToggleControl = wp.components.ToggleControl;
	var SelectControl = wp.components.SelectControl;
	var Button = wp.components.Button;
	var __ = wp.i18n.__;

	var GROUP_LABELS = {
		content: __( 'Content', 'ev-charging-experience' ),
		media: __( 'Media', 'ev-charging-experience' ),
		layout: __( 'Layout', 'ev-charging-experience' ),
		visual: __( 'Visual', 'ev-charging-experience' ),
		motion: __( 'Motion', 'ev-charging-experience' ),
		responsive: __( 'Responsive', 'ev-charging-experience' ),
		advanced: __( 'Advanced', 'ev-charging-experience' ),
	};
	var GROUP_ORDER = [ 'content', 'media', 'layout', 'visual', 'motion', 'responsive', 'advanced' ];

	function groupControls( controls ) {
		var groups = {};
		controls.forEach( function ( control ) {
			var g = control.group || 'content';
			groups[ g ] = groups[ g ] || [];
			groups[ g ].push( control );
		} );
		return groups;
	}

	function renderControl( control, attributes, setAttributes ) {
		var value = attributes[ control.key ];
		var onChange = function ( next ) {
			var update = {};
			update[ control.key ] = next;
			setAttributes( update );
		};

		switch ( control.type ) {
			case 'textarea':
			case 'richtext':
				return el( TextareaControl, {
					key: control.key,
					label: control.label,
					value: value || '',
					onChange: onChange,
				} );

			case 'toggle':
				return el( ToggleControl, {
					key: control.key,
					label: control.label,
					checked: !! value,
					onChange: onChange,
				} );

			case 'select':
				return el( SelectControl, {
					key: control.key,
					label: control.label,
					value: value,
					options: Object.keys( control.options || {} ).map( function ( optValue ) {
						return { value: optValue, label: control.options[ optValue ] };
					} ),
					onChange: onChange,
				} );

			case 'number':
				return el( TextControl, {
					key: control.key,
					type: 'number',
					label: control.label,
					value: value,
					onChange: function ( next ) {
						onChange( next === '' ? '' : Number( next ) );
					},
				} );

			case 'url':
				return el( TextControl, {
					key: control.key,
					type: 'url',
					label: control.label,
					value: value || '',
					onChange: onChange,
				} );

			case 'image':
				return el(
					MediaUploadCheck,
					{ key: control.key },
					el( MediaUpload, {
						onSelect: function ( media ) {
							onChange( media.id );
						},
						allowedTypes: [ 'image' ],
						value: value,
						render: function ( obj ) {
							return el(
								'div',
								{ className: 'evpx-editor-media-control' },
								el( 'label', {}, control.label ),
								el(
									Button,
									{ variant: 'secondary', onClick: obj.open },
									value
										? __( 'Replace image', 'ev-charging-experience' )
										: __( 'Select image', 'ev-charging-experience' )
								),
								value
									? el(
											Button,
											{
												variant: 'link',
												isDestructive: true,
												onClick: function () {
													onChange( 0 );
												},
											},
											__( 'Remove', 'ev-charging-experience' )
									  )
									: null
							);
						},
					} )
				);

			case 'text':
			default:
				return el( TextControl, {
					key: control.key,
					label: control.label,
					value: value || '',
					onChange: onChange,
				} );
		}
	}

	function buildEdit( schema ) {
		return function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps ? useBlockProps( { className: 'evpx-editor-block' } ) : {};
			var groups = groupControls( schema.controls );

			var inspector = el(
				InspectorControls,
				{},
				GROUP_ORDER.filter( function ( g ) {
					return groups[ g ];
				} ).map( function ( g ) {
					return el(
						PanelBody,
						{ key: g, title: GROUP_LABELS[ g ] || g, initialOpen: g === 'content' },
						groups[ g ].map( function ( control ) {
							return renderControl( control, attributes, setAttributes );
						} )
					);
				} )
			);

			var canvas;

			if ( schema.allowedChildren && schema.allowedChildren.length ) {
				canvas = el(
					'div',
					blockProps,
					el( InnerBlocks, {
						allowedBlocks: schema.allowedChildren,
						templateLock: false,
					} )
				);
			} else if ( ServerSideRender ) {
				canvas = el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: schema.name,
						attributes: attributes,
					} )
				);
			} else {
				canvas = el( 'div', blockProps, schema.title );
			}

			return el( Fragment, {}, inspector, canvas );
		};
	}

	function buildSave( schema ) {
		if ( schema.allowedChildren && schema.allowedChildren.length ) {
			// Dynamic parent that still needs to persist its children's
			// block markup; WordPress renders children server-side too
			// (see Element::renderBlock), so we only need to preserve
			// their serialized comments via InnerBlocks.Content.
			return function () {
				return el( InnerBlocks.Content );
			};
		}

		return function () {
			return null; // Fully dynamic: PHP render_callback owns output.
		};
	}

	( window.EVPX_BLOCKS && window.EVPX_BLOCKS.elements ? window.EVPX_BLOCKS.elements : [] ).forEach( function ( schema ) {
		registerBlockType( schema.name, {
			title: schema.title,
			icon: 'admin-plugins',
			category: 'ev-charging',
			attributes: ( function () {
				var attrs = {};
				schema.controls.forEach( function ( control ) {
					attrs[ control.key ] = { type: inferType( control.type ), default: control.default };
				} );
				return attrs;
			} )(),
			edit: buildEdit( schema ),
			save: buildSave( schema ),
		} );
	} );

	function inferType( controlType ) {
		if ( controlType === 'toggle' ) {
			return 'boolean';
		}
		if ( controlType === 'number' || controlType === 'image' ) {
			return 'number';
		}
		return 'string';
	}
} )( window.wp );
