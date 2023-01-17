(
	function( $ ) {
		'use strict';

		$( document ).ready( function() {
			// Remove inline css.
			$( '.mo-openid-app-icons' ).each( function() {
				$( this ).find( '.mo_btn-social' ).removeAttr( 'style' );
				$( this ).find( '.mo_btn-social .mofa' ).removeAttr( 'style' );
				$( this ).find( '.mo_btn-social svg' ).removeAttr( 'style' );
			} );
		} );

	}( jQuery )
);
