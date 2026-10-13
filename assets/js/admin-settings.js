/**
 * The bunq gateway settings page: shows the connection and OAuth client of the mode the test mode checkbox selects,
 * copies the redirect URL and confirms disconnecting.
 */
( function ( $, settings ) {
	'use strict';

	var $testmode = $( '#woocommerce_bunq_testmode' );
	var $directGateway = $( '#woocommerce_bunq_direct_gateway' );
	var $paymentMethods = $( '#woocommerce_bunq_enabled_payment_methods' );
	var $bankAccount = $( '#woocommerce_bunq_monetary_account_bank_id' );
	var editingClient = {};
	var savedMode;

	function activeMode() {
		return $testmode.is( ':checked' ) ? 'sandbox' : 'live';
	}

	function update() {
		var mode = activeMode();
		var $status = $( '.bunq-connection-row[data-bunq-mode="' + mode + '"]' );
		var showClient = '1' !== String( $status.data( 'bunqConnected' ) ) || !! editingClient[ mode ];

		$( '.bunq-connection-row' ).each( function () {
			this.hidden = $( this ).data( 'bunqMode' ) !== mode;
		} );

		// The OAuth client of the active mode; once connected only after "Change OAuth client".
		$( ':input[data-bunq-mode]' ).each( function () {
			$( this ).closest( 'tr' ).prop( 'hidden', $( this ).data( 'bunqMode' ) !== mode || ! showClient );
		} );
		$( '.bunq-redirect-row, .bunq-connect-row' ).prop( 'hidden', ! showClient );
		$status.find( '.bunq-edit-client' ).prop( 'hidden', showClient );

		// The bank accounts are those of the saved mode; another mode's account cannot be picked before saving.
		$bankAccount.prop( 'disabled', mode !== savedMode );
		$bankAccount.siblings( '.bunq-mode-note' ).prop( 'hidden', mode === savedMode );

		$paymentMethods.closest( 'tr' ).prop( 'hidden', ! $directGateway.is( ':checked' ) );
	}

	$testmode.on( 'change', update );
	$directGateway.on( 'change', update );

	$( document ).on( 'click', '.bunq-edit-client', function () {
		var mode = activeMode();

		editingClient[ mode ] = true;
		update();
		$( ':input[data-bunq-mode="' + mode + '"]' ).first().trigger( 'focus' );
	} );

	// Like "Copy URL to clipboard" in the media library.
	$( document ).on( 'click', '.bunq-copy', function () {
		var $success = $( this ).closest( 'td' ).find( '.success' );
		var input = document.getElementById( $( this ).data( 'bunqCopy' ) );

		function copied() {
			$success.removeClass( 'hidden' );
			wp.a11y.speak( settings.copiedAnnouncement );
			window.setTimeout( function () {
				$success.addClass( 'hidden' );
			}, 3000 );
		}

		function fallback() {
			input.select();
			if ( document.execCommand( 'copy' ) ) {
				copied();
			}
		}

		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( input.value ).then( copied, fallback );
		} else {
			fallback();
		}
	} );

	$( document ).on( 'focus', '.bunq-redirect-url', function () {
		this.select();
	} );

	$( document ).on( 'click', '.bunq-disconnect', function ( event ) {
		if ( ! window.confirm( settings.confirmDisconnect ) ) {
			event.preventDefault();
		} else {
			window.onbeforeunload = null;
		}
	} );

	// The connect buttons save the form as well: no "leave the page" warning for that.
	$( document ).on( 'click', '.bunq-connect-button', function () {
		window.onbeforeunload = null;
	} );

	savedMode = activeMode();
	$paymentMethods.closest( 'tr' ).addClass( 'bunq-payment-methods' );
	$bankAccount.siblings( '.description' ).last().after(
		$( '<p class="description bunq-mode-note" hidden />' ).text( settings.saveToSwitchMode )
	);
	update();
}( jQuery, window.bunqForWooCommerceSettings ) );
