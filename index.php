<?
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
	require_once( 'svgtranslate.php' );
	require_once( '/home/jarry/public_html/global.php' );
	
	/*
	$trans = new SVGtranslate();
	$step = $trans->handle_post( $_REQUEST );
	$output = $trans->do_step( $step );
	*/
	// End processing, begin output (will already have died if necessary)
	echo get_html( 'header', _html( 'title' ) );
	echo '<p>Unfortunately it appears that the tool has some serious problems, which I will hopefully try to address soon. Sorry for any inconvenience.</p>';
	/*
	echo $output;
	echo '<script type="text/javascript" src="functions.js.php"></script>';
	*/
	echo get_html( 'footer', $trans->get_footer_text() );
?>