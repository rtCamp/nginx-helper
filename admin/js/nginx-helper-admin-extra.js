/**
 * File to add Extra JavaScript for nginx-helper.
 *
 * @package nginx-helper
 */

jQuery( function ($) {

	$content             = $( "#post_form" );
	const $nav           = $( '#navigation' );
	const $sidebar       = $( '#postbox-container-1' );
	const $purgeToggle   = $( "#enable_purge" );
	const $tabsToDisable = $( '.nav-tab[href*="tab=purging"], .nav-tab[href*="tab=logging_tools"]' );
	const $tocItems      = $( "li.hide-on-purge-disabled" );
	const $redisToc      = $( "li.hide-redis" );
	const $fastcgiToc    = $( "li.hide-fastcgi" );
	const $fastcgiRadio  = $( "#cache_method_fastcgi" );
	const $redisRadio    = $( "#cache_method_redis" );

	const SCROLL_OFFSET        = 200;
	const VISIBILITY_THRESHOLD = 250;

	let navItems, visibleSections;

	switch ( nginxHelperVars.currentTab ) {

		case 'purging':
			navItems        = $( ".nav-list.purging-tab li" );
			visibleSections = $( ".purging-tab .postbox, .purging-tab h4" );
			break;

		case 'general':
			navItems        = $( ".nav-list.general-tab li" );
			visibleSections = $( ".general-tab .postbox input, .general-tab .postbox" )
				.filter( ( _, el ) => el.id )
				.slice( 0, 9 );

			$purgeToggle.on( "change", updateNavSections );
			updateNavSections();
			break;

		case 'logging_tools':
			navItems        = $( ".nav-list.logging-tab li" );
			visibleSections = $( ".logging-tab .postbox, .logging-tab .postbox td" );
			break;

		case 'support':
			navItems        = $( ".nav-list.support-tab li" );
			visibleSections = $( ".postbox h3, .postbox th" ).filter( ( _, el ) => el.id );
			$content        = $( "#post-body-content" );
			break;
	}

	$( window ).on( "scroll", () => {
		highlightNavOnScroll( $content, $nav, navItems, visibleSections );
	});

	function highlightNavOnScroll( $container, $nav, $items, $sections ) {

		const top = $container[0].getBoundingClientRect().top;

		$nav.toggleClass( "fixed", top <= 60 );
		$sidebar.toggleClass( "fixed", top <= 75 );

		let activeIndex = 0;
		$sections.each( ( i, el ) => {
			if ( el.getBoundingClientRect().top <= VISIBILITY_THRESHOLD ) {
				activeIndex = i;
			}
		});

		$items.removeClass( "nav-active" );
		if ( activeIndex >= 0 && activeIndex < $items.length ) {
			$( $items[activeIndex] ).addClass( "nav-active" );
		}
	}

	function updateNavSections() {
		if ( ! $purgeToggle.is( ":checked" ) ) {
			visibleSections = visibleSections.slice( 0, 3 );
			return;
		}

		if ( $redisRadio.is( ":checked" ) ) {
			visibleSections = $( ".general-tab .postbox input, .general-tab .postbox" )
				.filter( ( _, el ) => el.id && ! el.id.startsWith( "purge_method" ) );
			navItems        = navItems.filter( ( _, el ) => ! $( el ).hasClass( "hide-fastcgi" ) );
			return;
		}

		navItems        = $( ".nav-list.general-tab li" );
		visibleSections = $( ".general-tab .postbox input, .general-tab .postbox" )
			.filter( ( _, el ) => el.id )
			.slice( 0, 9 );
	}

	function updateTabsState() {
		const purgeEnabled = $purgeToggle.is( ":checked" );

		$tabsToDisable.toggleClass( "nav-tab-disabled", ! purgeEnabled )
			.attr( "title", purgeEnabled ? null : "Enable Purge to access this tab" );

		$tocItems.each( function () {

			const $item = $( this );
			if ( ! purgeEnabled ) {
				$item.hide();
				return;
			}

			if ( $fastcgiRadio.is( ":checked" ) && $fastcgiToc.is( $item ) ) {
				$item.show();
				return;

			} else if ( $redisRadio.is( ":checked" ) && $redisToc.is( $item ) ) {
				$item.show();
				return;

			} else if ( ! $fastcgiToc.is( $item ) && ! $redisToc.is( $item ) ) {
				$item.show();
				return;

			}
			$item.hide();
		});
	}

	function updateTocForCacheMethod() {
		if ( ! $fastcgiRadio.length || ! $redisRadio.length ) {
			return;
		}

		if ( $fastcgiRadio.is( ":checked" ) && $purgeToggle.is( ":checked" ) ) {
			$fastcgiToc.show();
			$redisToc.hide();
		} else if ( $redisRadio.is( ":checked" ) && $purgeToggle.is( ":checked" ) ) {
			$redisToc.show();
			$fastcgiToc.hide();
		}
	}

	if ( $purgeToggle.length ) {
		$purgeToggle.on( "change", updateTabsState );
		$fastcgiRadio.on( "change", () => {
			updateTocForCacheMethod();
			updateNavSections();
		} );
		$redisRadio.on( "change", () => {
			updateTocForCacheMethod();
			updateNavSections();
		} );

		updateTabsState();
		updateTocForCacheMethod();
	}

	$( 'a[href^="#"]' ).on( "click", function (e) {
		const targetId = $( this ).attr( "href" ).substring( 1 );
		const $target  = $( "#" + targetId );

		if ( $target.length ) {
			e.preventDefault();
			const elTop = $target[0].getBoundingClientRect().top + window.scrollY;
			window.scrollTo( { top: elTop - SCROLL_OFFSET, behavior: "smooth" } );
		}
	} );
} );
