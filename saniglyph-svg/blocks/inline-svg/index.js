(function (blocks, element, components, blockEditor, i18n) {
	'use strict';
	var el = element.createElement;
	var registerBlockType = blocks.registerBlockType;
	var Button = components.Button;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var Placeholder = components.Placeholder;
	var InspectorControls = blockEditor.InspectorControls;
	var MediaUpload = blockEditor.MediaUpload;
	var MediaUploadCheck = blockEditor.MediaUploadCheck;
	var useBlockProps = blockEditor.useBlockProps;
	var __ = i18n.__;

	registerBlockType('saniglyph-svg/inline-svg', {
		title: __('Inline SVG', 'saniglyph-svg'),
		description: __('Render a sanitized SVG attachment inline.', 'saniglyph-svg'),
		category: 'media',
		icon: 'format-image',
		edit: function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps({ className: 'sgsvg-inline-svg-editor' });

			function onSelect(media) {
				setAttributes({ attachmentId: media.id || 0, mediaUrl: media.url || '' });
			}

			var controls = el(
				InspectorControls,
				null,
				el(PanelBody, { title: __('SVG dimensions', 'saniglyph-svg'), initialOpen: true },
					el(TextControl, { label: __('Width', 'saniglyph-svg'), value: attributes.width || '', onChange: function (value) { setAttributes({ width: value }); }, help: __('Example: 120, 120px, or 50%.', 'saniglyph-svg') }),
					el(TextControl, { label: __('Height', 'saniglyph-svg'), value: attributes.height || '', onChange: function (value) { setAttributes({ height: value }); } })
				)
			);

			var content;
			if (attributes.attachmentId && attributes.mediaUrl) {
				content = el('div', blockProps,
					el('img', { src: attributes.mediaUrl, alt: '', style: { maxWidth: '100%', height: 'auto' } }),
					el(MediaUploadCheck, null,
						el(MediaUpload, { onSelect: onSelect, allowedTypes: ['image/svg+xml'], value: attributes.attachmentId, render: function (obj) { return el(Button, { variant: 'secondary', onClick: obj.open }, __('Replace SVG', 'saniglyph-svg')); } })
					)
				);
			} else {
				content = el('div', blockProps,
					el(Placeholder, { icon: 'format-image', label: __('Inline SVG', 'saniglyph-svg'), instructions: __('Choose an SVG from the Media Library.', 'saniglyph-svg') },
						el(MediaUploadCheck, null,
							el(MediaUpload, { onSelect: onSelect, allowedTypes: ['image/svg+xml'], render: function (obj) { return el(Button, { variant: 'primary', onClick: obj.open }, __('Choose SVG', 'saniglyph-svg')); } })
						)
					)
				);
			}

			return el(element.Fragment, null, controls, content);
		},
		save: function () { return null; }
	});
}(window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.i18n));
