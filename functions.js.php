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

	$language = isset( $_GET['lang'] ) ? $_GET['lang'] : 'en';
	include( "language.php" );
	require_once( '/data/project/jarry-common/public_html/global.php' );
	header( "Content-Type: application/x-javascript" );
?>

var imgcache = "";
var cachedanswer = "";
function checkimg(image) {
if(!image.match(/^(File|Image):(.*)[.]svg$/gi)){
return false;
}
if(imgcache != image) {
$.ajax({
url: "checkexist.php?image="+image,
async: false,
complete: abbr
});
imgcache = image;
cachedanswer = (window.imageExists === "FALSE");
}

return cachedanswer;
}

function abbr(originalRequest, status) {
window.imageExists = originalRequest.responseText;
}

function checkdesc( desc ){
var search = /<?php echo preg_quote( mb_strtoupper( _( 'author-complete', 'svgtranslate' ) ) ); ?>/;
return (desc.search(search) === -1);
}

function helpwindow (Adresse){
MeinFenster = window.open(Adresse, "HelpCenter", "scrollbars=yes,width=350,height=400,left=100,top=200");
MeinFenster.focus();
}
function enableupload(){
if( $( "#accbut" ).attr( "checked" ) ){
$( "#startupload" ).removeAttr( "disabled" );
} else{
$( "#startupload" ).attr( "disabled", "disabled" );
}
}
function hideprevx(){
$( "#prevframe" ).css( "display", "none" );
$( "#hideprev" ).css( "display", "none" );
$( "#prev" ).attr( "value", "<?php echo _html( 'preview', 'svgtranslate' ); ?>" );
}
function preview( svg ){
var text = $( "#pagetext" ).attr( "value" );
var url = "preview.php?svg=" + encodeURIComponent( svg ) + "&text=" + encodeURIComponent( text );
$( "#prevframe" ).attr( "src", url );
$( "#prevframe" ).css( "display", "block" );
$( "#hideprev" ).css( "display", "inline" );
$( "#prev" ).attr( "value", "<?php echo _html( 'preview-refresh', 'svgtranslate' ); ?>" );
}