( function( $, api ) {
	var $window = $( window ),
		$document = $( document ),
		$body = $( 'body' );
	/**
	 * API on ready event handlers
	 *
	 * All handlers need to be inside the 'ready' state.
	 */
	wp.customize.bind( 'ready', function() {
		/**
		 * Init import export.
		 */
		var noorImportExport = {
			init: function() {
				$( 'input[name=noor-starter-export-button]' ).on( 'click', noorImportExport.export );
				$( 'input[name=noor-starter-import-button]' ).on( 'click', noorImportExport.import );
				$( 'input[name=noor-starter-reset-button]' ).on( 'click', noorImportExport.reset );
			},

			export: function() {
				window.location.href = noorStarterImport.customizerURL + '?noor-starter-export=' + noorStarterImport.nonce.export;
			},
			import: function() {
				var win			= $( window ),
					body		= $( 'body' ),
					form		= $( '<form class="noor-starter-import-form" method="POST" enctype="multipart/form-data"></form>' ),
					controls	= $( '.noor-starter-import-controls' ),
					file		= $( 'input[name=noor-starter-import-file]' ),
					message		= $( '.noor-starter-uploading' );

				if ( '' == file.val() ) {
					alert( noorStarterImport.emptyImport );
				}
				else {
					win.off( 'beforeunload' );
					body.append( form );
					form.append( controls );
					message.show();
					form.submit();
				}
			},
			reset: function() {
				var data = {
					wp_customize: 'on',
					action: 'noor_starter_reset',
					nonce: noorStarterImport.nonce.reset
				};

				var r = confirm( noorStarterImport.resetConfirm );

				if (!r) return;

				$( 'input[name=noor-starter-reset-button]' ).attr('disabled', 'disabled');

				$.post( ajaxurl, data, function () {
					wp.customize.state('saved').set( true );
					location.reload();
				});
			}
		};

		$( noorImportExport.init );
	});

} )( jQuery, wp );