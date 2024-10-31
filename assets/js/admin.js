jQuery( function( $ ) {
	'use strict';

	/**
	 * Object to handle paytime admin functions.
	 */
	var wc_paytime_admin = {
		isTestMode: function() {
			return $( '#woocommerce_paytime-payment_testmode' ).is( ':checked' );
		},

		/**
		 * Initialize.
		 */
		init: function() {
			$( document.body ).on( 'change', '#woocommerce_paytime-payment_testmode', function() {
				var test_api_username = $( '#woocommerce_paytime-payment_sandbox_api_username' ).parents( 'tr' ).eq( 0 ),
					test_api_password = $( '#woocommerce_paytime-payment_sandbox_api_password' ).parents( 'tr' ).eq( 0 ),
					test_api_cid = $( '#woocommerce_paytime-payment_sandbox_api_client_id' ).parents( 'tr' ).eq( 0 ),
					test_api_csecret = $( '#woocommerce_paytime-payment_sandbox_api_client_secret' ).parents( 'tr' ).eq( 0 ),
					live_api_username = $( '#woocommerce_paytime-payment_api_username' ).parents( 'tr' ).eq( 0 ),
					live_api_password = $( '#woocommerce_paytime-payment_api_password' ).parents( 'tr' ).eq( 0 ),
					live_api_cid = $( '#woocommerce_paytime-payment_api_client_id' ).parents( 'tr' ).eq( 0 ),
					live_api_csecret = $( '#woocommerce_paytime-payment_api_client_secret' ).parents( 'tr' ).eq( 0 );
				if ( $( this ).is( ':checked' ) ) {
					test_api_username.show();
					test_api_password.show();
					test_api_cid.show();
					test_api_csecret.show();
					live_api_username.hide();
					live_api_password.hide();
					live_api_cid.hide();
					live_api_csecret.hide();
				} else {
					test_api_username.hide();
					test_api_password.hide();
					test_api_cid.hide();
					test_api_csecret.hide();
					live_api_username.show();
					live_api_password.show();
					live_api_cid.show();
					live_api_csecret.show();
				}
			} );

			$( '#woocommerce_paytime-payment_testmode' ).change();
		}
	};

	wc_paytime_admin.init();
});
