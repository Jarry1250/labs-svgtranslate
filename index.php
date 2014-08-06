<?php
	/**
	 * SVGTranslate 2 © 2011
	 * @author Harry Burt <jarry1250@gmail.com>
	 * @author Nikola Smolenski <smolensk@eunet.yu>
	 * @author Magnus Manske
	 * @author Luxo <luxo@toolserver.org>
	 * @license http://www.opensource.org/licenses/lgpl-2.1 LGPL 2.1
	 * @package SVGTranslate
	 *
	 * SVGTranslate 2 is free software; you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published by
	 * the Free Software Foundation; either version 2 of the License, or
	 * (at your option) any later version.
	 *
	 * SVGTranslate 2 is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with SVGTranslate 2; if not, write to the Free Software
	 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
	 */

	error_reporting( E_ALL );
	ini_set( 'display_errors', 1 );
	ini_set('session.prefix', 'svgtranslate-');
	ini_set('session.save_handler', 'redis');
	ini_set('session.save_path', 'tcp://tools-redis:6379');
	require_once( '/data/project/svgtranslate/public_html/svgtranslate.php' );
	require_once( '/data/project/svgtranslate/OAuthConfig.php' );
	require_once( '/data/project/jarry-common/public_html/global.php' );
	require_once( '/data/project/jarry-common/public_html/libs/OAuthHandler.php' );

	session_start();
	if( empty( $_REQUEST ) ) session_unset();
	$trans = isset( $_SESSION['trans'] ) ? $_SESSION['trans'] : new SVGtranslate();

	// Details from OAuth
	global $details;
	$oAuth = new OAuthHandler( $details );

	if ( isset( $_GET['oauth_verifier'] ) && $_GET['oauth_verifier'] ) {
		// Fetch the access token if this is the callback from requesting authorization
		$oAuth->fetchAccessToken();
		header( "Location: http://tools.wmflabs.org/svgtranslate/index.php?authorised=true" );
	} elseif( isset( $_GET['authorised'] ) ) {
		// Load from cache
		$output = $trans->do_step( 'getdetails' );
	} else {
		$step = $trans->handle_post( $_REQUEST );
		$output = $trans->do_step( $step );
	}

	// End processing, begin output (will already have died if necessary)
	echo get_html( 'header', _html( 'title' ) );

	echo $output;
	echo '<script type="text/javascript" src="functions.js.php"></script>';

	echo get_html( 'footer', $trans->get_footer_text() );