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

ini_set('user_agent', ' derivativeFX by Luxo on the Toolserver / PHP');
require_once('/home/jarry/public_html/global.php');
require_once('svgtranslate.php'); 
if( isset( $_GET['svg'] ) ){
	$name = stripslashes( $_GET['svg'] );
	$svg = file_get_contents( $name );
} else {
	$svg = '';
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8">
		<title>Preview of Upload</title>
		<link rel="stylesheet" type="text/css" media="screen, projection" href="http://bits.wikimedia.org/commons.wikimedia.org/load.php?debug=false&lang=<?php echo $I18N->getLang(); ?>&modules=mediawiki.legacy.commonPrint%7Cmediawiki.legacy.shared%7Cskins.vector&only=styles&skin=vector" />
	</head>
	<body class="mediawiki ns--0 ltr" style="direction: ltr;">
		<div id="globalWrapper" style="top:5px;bottom:5px;right:5px;left:5px;">
			<?php
				echo $svg;
			?>
			<?php
				if(isset( $_GET['text'] ) ){
					$url = "http://commons.wikimedia.org/w/api.php?action=parse&format=php&pst&text=".urlencode($_GET['text']);
					$data = unserialize(file_get_contents($url));
					echo $data['parse']['text']['*'];//return data
				}
			?>
		</div> 
	</body>
</html>
