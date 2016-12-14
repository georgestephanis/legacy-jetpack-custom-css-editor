/* jshint onevar: false, smarttabs: true */

(function($){
	var Jetpack_CSS = {
			modes: {
				'default': 'text/css',
				'less': 'text/x-less',
				'sass': 'text/x-scss'
			},
			init: function() {
				this.$textarea = $( '#safecss' );
				this.editor = window.CodeMirror.fromTextArea( this.$textarea.get(0),{
					mode: this.getMode(),
					lineNumbers: true,
					tabSize: 2,
					indentWithTabs: true,
					lineWrapping: true
				});
				this.addListeners();
			},
			addListeners: function() {
				// keep textarea synced up
				this.editor.on( 'change', _.bind( function( editor ){
					this.$textarea.val( editor.getValue() );
				}, this ) );
				// change mode
				$( '#preprocessor_choices' ).change( _.bind( function(){
					this.editor.setOption( 'mode', this.getMode() );
				}, this ) );
			},
			getMode: function() {
				var mode = $( '#preprocessor_choices' ).val();
				if ( '' === mode || ! this.modes[ mode ] ) {
					mode = 'default';
				}
				return this.modes[ mode ];
			}
		},
		$switcher = $('.other-themes-wrap');

	$( document ).ready( _.bind( Jetpack_CSS.init, Jetpack_CSS ) );

	$switcher.find('button').on('click', function(e){
		e.preventDefault();
		if ( $switcher.find('select').val() ) {
			var win = window.open( $switcher.find('select').val(), '_blank' );
			if ( win ) {
				win.focus();
			}
		}
	}).text('View');

	$('#preview').on('click', function(e){
		e.preventDefault();
		alert( 'This feature is not implemented yet.' );
	})
})(jQuery);
