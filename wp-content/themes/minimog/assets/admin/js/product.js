( function( $ ) {
		'use strict';

		$( document ).ready( function () {
			$( '.woo-sctr-countdown-timer-admin-product' ).slideUp(0);

			$( '.sale_schedule' ).on( 'click', function(e) {
				e.preventDefault();

				$( this ).closest( '.form-field' ).siblings( '.woo-sctr-countdown-timer-admin-product' ).slideDown(0);
			} );

			$( '.cancel_sale_schedule' ).on( 'click', function(e) {
				e.preventDefault();

				$( this ).closest( '.form-field' ).siblings( '.woo-sctr-countdown-timer-admin-product' ).slideUp(0);
			} );
		});

		$( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function(e) {
			$( '.woo-sctr-countdown-timer-admin-product' ).slideUp();

			$( '.sale_schedule' ).on( 'click', function(e) {
				e.preventDefault();

				$( this ).closest( '.form-field' ).siblings( '.woo-sctr-countdown-timer-admin-product' ).slideDown(0);
			} );

			$( '.cancel_sale_schedule' ).on( 'click', function(e) {
				e.preventDefault();

				$( this ).closest( '.form-field' ).siblings( '.woo-sctr-countdown-timer-admin-product' ).slideUp(0);
			} );
		});

}( jQuery ));