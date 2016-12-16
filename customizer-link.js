jQuery(document).ready(function() {
	jQuery( '<div />', {
		id: 'fullscreenlinks',
		'class': 'css-help'
	}).prependTo( '.CodeMirror-wrap' );
	jQuery( '<a />', {
		id: 'fullscreen-link',
		target: '_blank',
		href: legacy_jetpack_css_settings.fullscreenURL
	}).prependTo( '#fullscreenlinks' );
	jQuery( '<span />', {
		'class': 'screen-reader-text',
		text: legacy_jetpack_css_settings.title
	}).prependTo( '#fullscreen-link' );
});
