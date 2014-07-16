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

// Simple PHP script checking for the existence or non-existence
// of a file. It is written like this to allow AJAX calls to use it. 

require_once( '/home/jarry/public_html/mw-peachy/Init.php' );
$pgVerbose = array();
$site = Peachy::newWiki( null, null, null, 'http://commons.wikimedia.org/w/api.php' );
$image = new Image( $site, $_GET['image'] );
if($image->get_exists()){
	echo "TRUE"; //Does exist
} else {
	echo "FALSE"; //Doesn't exist
}
?>