/**
 * Elwmar Holding AB navigation interactions.
 */
( function () {
	'use strict';
	const __ = window.wp && window.wp.i18n ? window.wp.i18n.__ : function ( text ) {
		return text;
	};

	const navigationSelector = '.primary-navigation';
	const openSelector = '.wp-block-navigation__responsive-container-open';
	const closeSelector = '.wp-block-navigation__responsive-container-close';
	const menuSelector = '.wp-block-navigation__responsive-container.is-menu-open';
	const panelSelector = '.wp-block-navigation__responsive-close';

	function getOpenMenu() {
		return document.querySelector( navigationSelector + ' ' + menuSelector );
	}

	function closeMenu( menu ) {
		const closeButton = menu && menu.querySelector( closeSelector );

		if ( closeButton ) {
			closeButton.click();
		}
	}

	function localizeAccessibilityLabels() {
		document.querySelectorAll( openSelector ).forEach( function ( button ) {
			button.setAttribute( 'aria-label', __( 'Öppna meny', 'elwmarholding' ) );
		} );

		document.querySelectorAll( closeSelector ).forEach( function ( button ) {
			button.setAttribute( 'aria-label', __( 'Stäng meny', 'elwmarholding' ) );
		} );

		const skipLink = document.querySelector( '.skip-link' );
		if ( skipLink ) {
			skipLink.textContent = __( 'Hoppa till innehåll', 'elwmarholding' );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', localizeAccessibilityLabels );
	} else {
		localizeAccessibilityLabels();
	}

	document.addEventListener(
		'click',
		function ( event ) {
			const menu = getOpenMenu();

			if ( ! menu ) {
				return;
			}

			const hamburger = event.target.closest( openSelector );
			if ( hamburger ) {
				event.preventDefault();
				event.stopImmediatePropagation();
				closeMenu( menu );
				return;
			}

			const panel = menu.querySelector( panelSelector );
			const clickedLink = event.target.closest( navigationSelector + ' a' );

			if ( clickedLink || ( panel && ! panel.contains( event.target ) ) ) {
				closeMenu( menu );
			}
		},
		true
	);
}() );
