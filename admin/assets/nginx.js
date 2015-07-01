jQuery( document ).ready( function() {
	var news_section = jQuery( '#latest_news' );
	if ( news_section.length > 0 ) {
		jQuery.get( news_url, function( data ) {
			news_section.find( '.inside' ).html( data );
		} );
	}

	jQuery( "form#purgeall a" ).click( function( e ) {
		if ( confirm( "Purging entire cache is not recommended. Would you like to continue ?" ) == true ) {
			// continue submitting form
		} else {
			e.preventDefault();
		}

	} );

	/**
	 * Show OR Hide options on option checkbox
	 * @param {type} selector Selector of Checkbox and PostBox
	 */
	function nginx_show_option( selector ) {
		jQuery( '#' + selector ).on( 'change', function() {
			if ( jQuery( this ).is( ':checked' ) ) {
				jQuery( '.' + selector ).show();
				if ( selector == "cache_method_redis" ) {
					jQuery( '.cache_method_fastcgi' ).hide();
				} else if ( selector == "cache_method_fastcgi" ) {
					jQuery( '.cache_method_redis' ).hide();
				}
			} else {
				jQuery( '.' + selector ).hide();
			}
		} );
	}
	/* Function call with parameter */
	nginx_show_option( 'cache_method_fastcgi' );
	nginx_show_option( 'cache_method_redis' );
	nginx_show_option( 'enable_map' );
	nginx_show_option( 'enable_log' );
	nginx_show_option( 'enable_purge' );
} );