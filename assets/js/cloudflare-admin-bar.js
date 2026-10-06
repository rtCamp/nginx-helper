/**
 * Cleans up after the Cloudflare admin-bar "Clear Cloudflare Edge Cache" button.
 *
 * After a purge the page reloads once with a status flag in the URL. After a short delay this
 * removes the flag from the address bar and restores the button label, without reloading.
 */
( function () {
	'use strict';

	var config = window.ecCfAdminBar;

	if ( ! config ) {
		return;
	}

	window.setTimeout( function () {
		var params = window.location.search.replace( /^\?/, '' ).split( '&' ).filter( function ( param ) {
			return param && 0 !== param.indexOf( config.param + '=' );
		} );
		var search = params.length ? '?' + params.join( '&' ) : '';

		window.history.replaceState( window.history.state, '', window.location.pathname + search + window.location.hash );

		var item = document.querySelector( '#wp-admin-bar-clear-page-cache > .ab-item' );

		if ( item ) {
			item.textContent = config.label;
		}
	}, config.timeout );
}() );
