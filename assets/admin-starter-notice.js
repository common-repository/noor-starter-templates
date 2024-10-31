/**
 * Ajax install the Theme Plugin
 *
 */
 (function($, window, document, undefined){
	"use strict";
	$(function(){
		$( '.starter-upsell-wrap .starter-upsell-dismiss' ).on( 'click', function( event ) {
			noor_starter_dismissNotice();
		} );
		function noor_starter_dismissNotice(){
			var data = new FormData();
			data.append( 'action', 'noor_starter_dismiss_notice' );
			data.append( 'security', dimaStarterAdmin.ajax_nonce );
			$.ajax({
				url : dimaStarterAdmin.ajax_url,
				method:  'POST',
				data: data,
				contentType: false,
				processData: false,
			});
			$( '.starter-upsell-wrap' ).remove();
		}
	});
})(jQuery, window, document);