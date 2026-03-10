(function(wp){
	'use strict';

	if(!wp || !wp.plugins) return;

	var __ = wp.i18n.__;
	var registerPlugin = wp.plugins.registerPlugin;
	var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
	var ToggleControl = wp.components.ToggleControl;
	var el = wp.element.createElement;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;

	function Panel(){
		var meta = useSelect(function(select){
			return select('core/editor').getEditedPostAttribute('meta') || {};
		}, []);

		var editPost = useDispatch('core/editor').editPost;
		var checked = !!meta._hmpro_hide_title;

		return el(
			PluginDocumentSettingPanel,
			{
				name: 'hmpro-title-visibility',
				title: __('Title Visibility', 'hm-pro-theme'),
				className: 'hmpro-title-visibility-panel'
			},
			el(ToggleControl, {
				label: __('Hide title on frontend', 'hm-pro-theme'),
				checked: checked,
				onChange: function(val){
					editPost({ meta: Object.assign({}, meta, { _hmpro_hide_title: !!val }) });
				}
			})
		);
	}

	registerPlugin('hmpro-title-visibility', { render: Panel, icon: null });
})(window.wp);
