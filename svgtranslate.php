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

	require_once( '/data/project/jarry-common/public_html/global.php' );
	require_once( '/data/project/jarry-common/public_html/peachy/Init.php' );

	require_once( '/data/project/svgtranslate/public_html/licence.php' );
	require_once( '/data/project/svgtranslate/public_html/language.php' );

	/**
	 * Main class
	 * @package SVGTranslate
	 */
	class SVGtranslate {

		/**
		 * The name of the SVG file being translated.
		 * @var string
		 */
		private $name = null;

		/**
		 * The description of the SVG file being translated.
		 * @var string
		 */
		private $description = null;

		/**
		 * The resolved URL of the SVG file being translated.
		 * @var string
		 */
		private $url = null;

		/**
		 * A record of whether or not the file is on a Wikimedia site
		 * @var bool
		 */
		private $wikimedia = false;

		/**
		 * The original, untranslated phrases
		 * @var array
		 */
		private $originals = null;

		/**
		 * The final, translated phrases
		 * @var array
		 */
		private $translations = null;

		/**
		 * Two letter code of the language the SVG file is being translated into
		 * @var string
		 */
		private $targetlanguage = null;

		/**
		 * A concatenated list of the original licences of the image
		 * @var string
		 */
		private $original_licences = null;

		/**
		 * The original description of the image
		 * @var string
		 */
		private $original_description = null;

		/**
		 * The categories of the original (and hence new) file
		 * @var string
		 */
		private $categories = null;

		/**
		 * The chosen destination file name
		 * @var string
		 */
		private $destination = null;

		/**
		 * The chosen licence of the new file
		 * @var string
		 */
		private $licence = null;

		/**
		 * The chosen upload username
		 * @var string
		 */
		private $tuscuser = null;

		/**
		 * The chosen upload password
		 * @var string
		 */
		private $tuscpass = null;

		/**
		 * The chosen final page text
		 * @var string
		 */
		private $pagetext = null;

		/**
		 * The filename of the final SVG, including translations
		 * @var string
		 */
		private $tempname = null;

		/**
		 * Simple constructor function
		 * @param string $name File name
		 * @global array $pgVerbose From the Peachy API framework
		 * @global TsIntuition $I18N The I18N object from the Toolserver Intuition section of the I18N
		 */
		public function __construct( $name = null ) {
			global $pgVerbose, $I18N;
			$pgVerbose = array(); // Suppress echos from API framework
			$I18N->setDomain( 'svgtranslate' ); // Set up internationalisation
			$this->name = $name;
		}

		/* HELPER FUNCTIONS */

		/**
		 * Gets the number of times the counter has been hit
		 * @return int
		 */
		private function get_counter() {
			return Counter::getCounter( 'svgtranslate.txt' );
		}

		/**
		 * Logs the details for the file and increments the counter
		 * @global TsIntuition $I18N The I18N object from the Toolserver Intuition section of the I18N
		 * @return void
		 */
		private function log() {
			global $I18N;
			Counter::increment( 'svgtranslate.txt' );
			$line = "\r\n\"" . $this->name . "\", \"" . $this->targetlanguage . "\", \"{$I18N->getLang()}\", \"" . date( 'c' ) . '"';
			file_put_contents( 'stats.txt', $line, FILE_APPEND );
		}

		/**
		 * Returns a simple array containing those tags we deem can contain translatable content
		 * @return array
		 */
		private function get_content_tags() {
			return array( 'text', 'tspan' );
		}

		/**
		 * Returns those translatable elements of the SVG, untrimmed
		 * @return array
		 */
		public function get_translatable() {
			if( !isset( $this->name ) ){
				$this->error( _( 'error-unexpected' ) );
			}
			if( strlen( $this->name ) < 4 || strtolower( substr( $this->name, -4 ) ) != ".svg" ){
				$this->error( _( 'error-notsvg' ) );
			}

			if( strpos( $this->name, "/" ) === false ){
				// On Wikimedia Commons
				if( !$this->file_exists( $this->name ) ){
					$this->error( _( 'error-notfound' ) );
				}
				$site = Peachy::newWiki( null, null, null, 'http://commons.wikimedia.org/w/api.php' );
				$image = new Image( $site, $this->name );
				$url = $image->get_url();
				if( $url == null ){
					$this->error( $url . _( 'error-notfound' ) );
				}
				$this->wikimedia = true;
			} else {
				if( strpos( $this->name, "http://" ) !== 0 ){
					$url = 'http://' . $this->name;
				} else {
					$url = $this->name;
				}
			}
			$this->url = $url;
			$in = file_get_contents( $url );
			$in = str_replace( chr( 0 ), "", $in ); // Strip null characters
			if( stripos( $in, "<svg" ) === false ){
				$this->error( _( 'error-notsvg' ) . "$in" );
			}
			$tags = $this->get_content_tags(); // array( 'text', 'tspan' ...);
			$matched = array();
			foreach( $tags as $tag ){
				// For simplicity's sake, the following regex allows for any tag name which *begins* with the content tags listed. Hence,
				// the rather unlikely prospect of a tspannial content tag would count, even if that was a meta tag unsuitable for translation.
				preg_match_all( "/< *" . $tag . "[^>]*>([^<]*[^ <][^<]*)< *\/ *" . $tag . '/', $in, $newmatches );
				$matched = array_merge( $matched, $newmatches[1] );
			}
			$matched = array_map( 'trim', $matched );
			return $matched;
		}

		/**
		 * Helper function calling global error() function defined in global.php, which die()s.
		 * @param string $message The error message
		 * @return void
		 */
		public function error( $message ) {
			echo "<!-- SVG Translate encountered an error. This error was generated by: \n";
			debug_print_backtrace();
			echo "\n-->";
			error( _html( 'error-tryagain', array( 'variables' => array( $message ) ) ), _html( 'title' ), $this->get_footer_text() );
		}

		/**
		 * Whether or not the current filename given already exists
		 * @param string $filename Name of the file to query, with or without namespace prefix
		 * @return bool
		 */
		private function file_exists( $filename ) {
			$site = Peachy::newWiki( null, null, null, 'http://commons.wikimedia.org/w/api.php' );
			$image = new Image( $site, $filename );
			return $image->get_exists();
		}

		/**
		 * Extra HTML footer text including statistics and disclaimer.
		 * @global TsIntuition $I18N The I18N object from the Toolserver Intuition section of the I18N
		 * @return string
		 */
		public function get_footer_text() {
			global $I18N;
			$html = "\t\t<p>";
			$html .= _html(
				'stats-footer', array(
					'variables' => array(
						$this->get_counter(), $I18N->dateFormatted( 'March 2011', 'F Y' )
					)
				)
			);
			$html .= ' ' . _html( 'disclaimer' );
			$html .= '</p>';
			return $html;
		}

		/**
		 * Generate a help link on a given theme
		 * @global string $language The interface language being used in the Luxo section of the I18N
		 * @param string $theme
		 * @return string
		 */
		private function get_help_link( $theme ) {
			global $language;
			$url = "http://toolserver.org/~luxo/derivativeFX/help/helpdesk.php?theme=" . $theme . "&lang=" . $language;
			$html = "<sup><a href='" . $url . "' title='help' onclick=\"helpwindow(this.href); return false\" target=\"blank\">?</a></sup>";
			return $html;
		}

		/**
		 * Put together our translations into a new SVG
		 * @return string
		 */
		private function generate_svg() {
			if( isset( $this->tempname ) ){
				return file_get_contents( $this->tempname );
			}
			if( !isset( $this->originals, $this->translations, $this->url ) ){
				$this->error( _( 'error-unexpected' ) );
			}
			if( count( $this->translations ) !== count( $this->originals ) ){
				$this->error( _( 'error-unexpected' ) );
			}
			$count = count( $this->originals );
			$file = trim( file_get_contents( $this->url ) );
			for( $i = 0; $i < $count; $i++ ){
				$original = $this->originals[$i];
				$translation = $this->translations[$i];
				$tags = implode( '|', $this->get_content_tags() );
				$file = preg_replace( '/(< *(' . $tags . ')[^>]*> *)' . preg_quote( $original ) . '( *< *\/)/', "$1" . $translation . "$3", $file );
			}
			$file .= ( substr( $file, -1 ) == "\n" ? "" : "\n" );
			$file .= "<!-- Translated by SVGTranslate 2. If Unicode characters appear oddly, try to change the character being used by your viewer to UTF-8. -->\n";
			// $file = utf8_decode( $file );
			$bom = pack( "CCC", 0xef, 0xbb, 0xbf );
			if( 0 == strncmp( $file, $bom, 3 ) ){
				$file = substr( $file, 3 );
			}
			$this->tempname = tempnam( "/tmp", "svgt_fin_" );
			file_put_contents( $this->tempname, $file );
			return $file;
		}

		/**
		 * Generate a handy hidden form element encapsulating our SVGtranslate object (i.e. $this)
		 * @return string
		 */
		private function serialise() {
			$serialised = serialize( $this );
			$serialised = str_replace( array( '"', "|" ), array( '&quot;', '&pipe;' ), $serialised );
			$serialised = addslashes( $serialised );
			$serialised = str_replace( '\0SVGtranslate\0', '', $serialised );
			return '<input type="hidden" value="' . $serialised . '" name="cache"/>';
		}

		/**
		 * Turn the value of a hidden form element encapsulating our SVGtranslate object (i.e. $this)
		 * back into an object.
		 * @param string $serialised
		 * @return array
		 */
		private function unserialise( $serialised ) {
			$serialised = stripslashes( $serialised );
			$serialised = str_replace( '&quot;', '"', $serialised );
			$serialised = preg_replace( '!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $serialised );
			$serialised = unserialize( $serialised );
			return $serialised;
		}

		/* MAIN METHODS */

		/**
		 * Generate HTML output for the first form (select SVG name)
		 * @global TsIntuition $I18N The I18N object from the Toolserver Intuition section of the I18N
		 * @return string
		 */
		private function generate_first_form() {
			global $I18N;
			$svg = isset( $this->name ) ? $this->name : '';

			$html = '<h3>' . _html( 'begin-translation' ) . '</h3>';
			$html .= '<p>' . $I18N->msg(
					'translate-instructions', array(
						'variables' => array(
							'<em>' . _html( 'format-filename-example' ) . '</em>',
							'<em>' . _html( 'format-fullurl-example' ) . '</em>'
						)
					)
				) . '</p>';
			$html .= '<form method="POST">' . _html( 'svginput-label' ) . _g( 'colon-separator' ) . '&nbsp;&nbsp;';
			$html .= '<input type="text" id="svg" name="svg" value="' . htmlspecialchars( $svg ) . '" required="required" />&nbsp;&nbsp;';
			$html .= '<input type="submit" value="' . _g( 'form-submit' ) . '"/>&nbsp;<input type="reset" value="' . _html( 'form-reset', 'general' ) . '"/>';
			$html .= $this->serialise() . '</form>';
			return $html;
		}

		/**
		 * Generate HTML output for the second form (translations, method, TUSC details)
		 * @global TsIntuition $I18N The I18N object from the Toolserver Intuition section of the I18N
		 * @return string
		 */
		private function generate_second_form() {
			global $I18N;

			$this->originals = $this->get_translatable();

			if( count( $this->originals ) === 0 ){
				$this->error( _( 'error-nothing' ) );
			}

			$html = '';
			// Thumbnail
			if( $this->wikimedia ){
				$site = Peachy::newWiki( null, null, null, 'http://commons.wikimedia.org/w/api.php' );
				$image = new Image( $site, $this->name );
				$ii = $image->imageinfo( 1, 800 );
				$ii = array_pop( $ii );
				if( isset( $ii['imageinfo'] ) && isset( $ii['imageinfo'][0]['thumburl'] ) ){
					$html .= '<h3>' . _html( 'preview' ) . '</h3>';
					$html .= '<p><img src="' . $ii['imageinfo'][0]['thumburl'] . '"/><p>';
				}
			}

			$html .= '<h3>' . _html( 'translate' ) . '</h3>';
			$html .= '<form method="POST" id="translateform">';
			$html .= '<table><tr>';
			$html .= '<th>' . _html( 'th-original' ) . '</th>';
			$html .= '<th>' . _html( 'th-translation' ) . '</th>';
			$html .= '</tr>';
			$i = 0;
			foreach( $this->originals as $original ){
				$prefill = preg_match( '/^[0-9\W+]$/', $original ) ? $original : '';
				// Print translations
				$html .= "\t<tr><td align=\"right\">" .
				         $original .
				         "</td><td><input type=\"text\" name=\"translation$i\" size=\"40\" required=\"required\" value=\"$prefill\" /></td></tr>\n";
				$i++;
			}
			$html .= '<tr><th align="right">' . _html( 'th-language' ) . _g( 'colon-separator' ) . '</th><td>';
			$html .= "<select name=\"targetlanguage\" style=\"width: 40em\">\n";
			$langnames = $I18N->getLangNames();
			$default = $I18N->getLang();
			foreach( $langnames as $code => $name ){
				$html .= "\t<option value=\"$code\"";
				if( $code == $default ){
					$html .= " selected='selected'";
				}
				$html .= ">$name</option>\n";
			}
			$html .= "</select></td></tr>";

			$html .= '<tr><th>' . _html( 'th-method' ) . '</th><td><input type="radio" id="manual" name="method" value="manual" checked="checked"/>';
			$html .= _html( 'option-manual' ) . '<br />';
			if( $this->wikimedia ){
				$html .= '<input type="radio" name="method" value="tusc" /><a href="http://toolserver.org/~magnus/tusc.php">' . _html( 'option-tusc' ) . '</a>';
			}
			$html .= '</td></tr><tr><td>&nbsp;</td>';
			$html .= '<td><input type="submit" value="' . _g( 'form-submit' ) . '"/><input type="reset" value="' . _html( 'form-reset', 'general' ) . '"/></td></tr>';
			if( $this->wikimedia ){
				$html .= '<tr class="tusc"><th>' . _html( 'th-username' ) . '</th><td><input type="text" name="tuscuser"/></td></tr>';
				$html .= '<tr class="tusc"><th>' . _html( 'th-password' ) . '</th><td><input type="password" name="tuscpass"/></td></tr>';
			}
			$html .= '</table>' . $this->serialise() . '</form>';

			if( $this->wikimedia !== null ){
				$html .= '<script type="text/javascript">
						$( document ).ready( function(){
							$( "input[name*=\'method\']" ).click( function() {
								if( this.value == "manual" ){
									$( "tr.tusc" ).css( {"visibility":"hidden"} );
								} else {
									$( "tr.tusc" ).css( {"visibility":"visible"} );
								}
							} );
							if( $( "input#manual" ).attr( "checked" ) ) {
								$( "tr.tusc" ).css( {"visibility":"hidden"} );
							}
						} );
					</script>';
			}
			return $html;
		}

		/**
		 * Process a request to supply a downloadable SVG for manual upload
		 * @return void
		 */
		private function attach_svg() {

			if( !isset( $this->name, $this->targetlanguage, $this->originals, $this->translations ) ){
				$this->error( _( 'error-unexpected' ) . $this->originals . "-" . $this->translations );
			}
			$name = $this->name;
			if( strpos( $this->name, ':' ) !== false ) {
				$name = substr( $this->name, ( strpos( $this->name, ':' ) + 1 ) ); // Trim namespace
			}
			$newfilename = preg_replace( "/[.]([^.]+)$/", "_" . $this->targetlanguage . ".\\1", $name );
			$finalsvg = $this->generate_svg();
			header( "Content-Type: image/svg+xml; charset=UTF-8" );
			header( "Content-Length: " . ( strlen( $finalsvg ) + 11 ) );
			header( "Content-Disposition: attachment; filename=" . $newfilename );
			$this->log( $this->name, $this->targetlanguage );
			die( $finalsvg );
		}

		/**
		 * Generate HTML output for the third form (description, licence)
		 * @global string $lng The interface messages being used in the Luxo section of the I18N
		 * @return string
		 */
		private function generate_third_form() {
			global $lng;

			if( !isset( $this->name ) ){
				$this->error( _( 'error-unexpected' ) );
			}

			$licences = licencehelper::get_licences( $this->name );
			$possible_licences = $licences['possible'];
			$original_licences = $licences['original'];
			if( $possible_licences === false ){
				// There was some problem ascertaining what licensing options were available.
				$this->error( _html( 'error-licensing' ) );
			} else {
				$possible_licences = array_unique( $possible_licences );
				$possible_licences[] = "self|" . substr( implode( "|", $original_licences ), 0, -1 );
			}

			$this->original_description = file_get_contents( "http://commons.wikimedia.org/w/index.php?action=raw&title=" . urlencode( $this->name ) );

			// Lizenzen auch in dieses Array um auf nächster Seite zu haben
			$this->original_licences = $original_licences;

			// Nach {{information}} suchen
			$description = $this->original_description;
			preg_match_all( "/\[\[Category.*?\]\]/", $description, $categories );
			$this->categories = implode( "\n", $categories[0] );
			$start = stripos( $description, "|Description" );
			$end = stripos( $description, "|Source" );
			if( $start && $end ){
				$desc = substr( $description, $start, $end - $start );
				$desc = trim( substr( strstr( $desc, "=" ), 1 ) );
			} else {
				// offenbar kein Information verwenet;
				$desc = preg_replace( "/\={2, }.*?.\={2, }/", "", $description ); // Titel entfernen
				$desc = preg_replace( "/\{{2, }.*?.\}{2, }/", "", $desc ); // Templates entfernen
				$desc = preg_replace( "/\[{2, }Category.*?.\]{2, }/", "", $desc ); // Categorys entfernen
				$desc = trim( $desc );
			}
			$newlines = array( "\r\n", "\n", "\r" );
			$replace = ' ';
			$desc = str_replace( $newlines, $replace, $desc );
			$outputdescription = $desc . "\n";

			$licence_sel = '';
			$default = ( isset( $this->licence ) ) ? $this->licence : null;
			foreach( $possible_licences as $licence ){
				$selected = ( $licence == $default ) ? "selected='selected'" : "";
				$licence_sel .= "<option value=\"" . $licence . "\" $selected>{{" . $licence . "}}</option>\n";
			}

			// loading-mitteilungen bis hier!

			return "<h3>" . _html( 'description-license' ) . "</h3>
			<form enctype='multipart/form-data' method='post' name='sendform'>" . $lng['x']['descri'] . ":" . $this->get_help_link( "description" ) . " <br />
			<font style='font-style: italic;' size='-1'>" . $lng['x']['forpar'] . "</font><br />
			<textarea cols='70' rows='10' name='description'>" . htmlspecialchars( $outputdescription ) . "</textarea><br />
			<br />
		<hr style=\"width: 50%;\">
		" . $lng['x']['licens'] . ":" . $this->get_help_link( "license" ) . "<br />
			<select name='licence'>
			$licence_sel
			</select>
			<br />
			<hr style=\"width: 50%;\">
		" . $lng['x']['hincan'] . ".<br />
			<br />
			<input value='" . _g( 'form-submit' ) . "' type='submit' />" . $this->serialise()
			       . "</form>";
		}

		/**
		 * Generate HTML output for the third form (full dewscription page tweaks, confirm acceptance)
		 * @global string $lng The interface messages being used in the Luxo section of the I18N
		 * @return string
		 */
		private function generate_fourth_form() {
			global $lng;

			if( !isset( $this->tuscuser, $this->name, $this->licence, $this->categories, $this->original_licences ) ){
				$this->error( _( 'error-unexpected' ) );
			}

			$site = Peachy::newWiki( null, null, null, 'http://commons.wikimedia.org/w/api.php' );
			$image = new Image( $site, $this->name );
			$imagedata = $image->imageinfo( 999 );
			$imagedata = array_pop( $imagedata );
			$imagedata = $imagedata['imageinfo'];

			$author = false;
			// |Author= auslesen
			$description = $this->original_description;
			$start = stripos( $description, "|Author" );
			$end1 = stripos( $description, "|Permission" );
			$end2 = stripos( $description, "|Date" );
			$originaluploadindex = count( $imagedata ) - 1;

			if( ( $end2 - $start ) < ( $end1 - $start ) && $end2 > $start ){
				$end = $end2;
			} else {
				$end = $end1;
			}

			if( $start && $end ){
				$author = substr( $description, $start, $end - $start );
				$author = stripslashes( trim( substr( strstr( $author, "=" ), 1 ) ) );
			} else {
				// bekannte lizenzen filtern
				foreach( $this->original_licences as $licence ){
					if( stripos( $licence, "PD-USGov-NASA" ) !== false ){
						$author = "created by [[en:NASA|NASA]]";
					}
					if( stripos( $licence, "PD-USGov-NPS" ) !== false ){
						$author = "work of a [[:en:National Park Service|National Park Service]] employee";
					}
					if( stripos( $description, "{{Agência Brasil}}" ) !== false ){
						$author = "produced by [[:en:Agência Brasil|Agência Brasil]], a public Brazilian news agency";
					}
					if( stripos( $description, "{{MdB}}" ) !== false ){
						$author = "produced by the [[:en:Brazilian Navy|Brazillian navy]]";
					}
					if( stripos( $description, "{{CC-AR-Presidency}}" ) !== false ){
						$author = "taken from the [http://www.presidencia.gov.ar/ Presidency of Argentina web site]";
					}
					if( stripos( $licence, "PD-PDphoto.org" ) !== false ){
						$author = "image from [http://pdphoto.org/ PD Photo.org]";
					}
				}
				if( !$author ){
					// Try to rescue one from the description
					if( stripos( $description, "myself" ) !== false || stripos( $description, "own work" ) !== false || stripos( $description, "selfmade" ) !== false || stripos( $description, "self made" ) !== false || stripos( $description, "self-made" ) !== false ){
						$author = "[[User:" . $imagedata[$originaluploadindex]["user"] . "|" . $imagedata[$originaluploadindex]["user"] . "]]";
					} else {
						$author = "'''" . mb_strtoupper( _html( 'author-complete' ) ) . "'''";
					}
				}
			}
			$authorlist = "*[[:" . $this->name . "|]]: " . trim( $author ) . "\n";

			// Look for credit lines
			$creditline = preg_match( '/\{\{[^}]*[Cc]redit line[^}]+\}\}/', $description, $matches ) ? $matches[0] : '';

			// Start putting together full page text afresh
			$pagetext = "{{Information\n|Description=" . trim( stripslashes( $this->description ) ) . "\n";
			$pagetext .= "|Source=translated from [[:" . $this->name . "|]]\n";
			$pagetext .= "|Date=" . date( "Y-m-d", time() ) . " (translation), ";
			$pagetext .= date( "Y-m-d", strtotime( $imagedata[$originaluploadindex]["timestamp"] ) ) . " (upload to Commons)\n";
			$pagetext .= "|Author=" . $authorlist . "*derivative work (translation): [[User:" . $this->tuscuser . "|" . $this->tuscuser . "]]\n";
			$pagetext .= "|Permission=$creditline\n{{" . $this->licence . "}}\n";
			$pagetext .= "|other_versions=\n}}\n\n";
			$pagetext .= "{{Translation possible}}\n\n";
			$pagetext .= "== {{Original upload log}} ==\nThis image is a derivative work of the following images:\n";
			$pagetext .= "\n*[[:" . $this->name . "]] licensed with ";
			$pagetext .= implode( ", ", $this->original_licences );
			$pagetext .= "\n";
			foreach( $imagedata as $vkey => $cntns ){
				$pagetext .= "**" . $imagedata[$vkey]["timestamp"] . " [[User:" . $imagedata[$vkey]["user"] . "|" . $imagedata[$vkey]["user"] . "]] " . $imagedata[$vkey]["width"] . "x" . $imagedata[$vkey]["height"] . " (" . $imagedata[$vkey]["size"] . " bytes) ''<nowiki>" . substr( strip_tags( str_replace( "\n", " ", $imagedata[$vkey]["comment"] ) ), 0, 225 ) . "</nowiki>''\n";
			}
			$pagetext .= "\n";
			$pagetext .= "''Translated using [[:tools:~jarry/svgtranslate/|SVG Translate]]''\n\n";
			$pagetext .= $this->categories;

			$finalsvg = $this->generate_svg();

			// Suggest a new filename
			$onlyname = substr( $this->name, 0, strrpos( $this->name, "." ) );
			$extension = substr( $this->name, strrpos( $this->name, "." ) + 1 );
			$targetfile = htmlspecialchars( $onlyname . '_' . $this->targetlanguage . '.' . $extension );

			// Issue warning if we couldn't locate the author of the source file automatically.
			$html = "";
			if( strpos( $pagetext, mb_strtoupper( _html( 'author-complete' ) ) ) !== false ){
				$html .= "<div class='error'><h3>" . _html( 'warning', 'general' ) . "</h3>";
				$html .= '<p>' . $lng['x']['plscom'] . ' <a target="_blank" href="http://commons.wikimedia.org/w/index.php?title=' . urlencode( $this->name ) . '">' . $this->name . '</a>.</p>';
				$html .= "</div>";
			}

			$html .= "<h3>" . _html( 'finalise' ) . "</h3>";
			$html .= "<form method='post' enctype='multipart/form-data'>";
			$html .= $lng['x']['destin'];
			$html .= ': <br /><input type="text" name="wpDestFile" size="50" id="newfilename" value="' . $targetfile . '" required="required"/><br /><br />';
			$html .= $lng['x']['summar'];
			$html .= ":<br /><textarea rows='25' cols='70' name='pagetext' id='pagetext' required='required'>" . htmlspecialchars( $pagetext ) . "</textarea><br />";
			$html .= '<input name="accbut" value="true" id="accbut" type="checkbox"> <label for="accbut">';
			$html .= $lng['x']['accept'];
			$html .= '</label><br /><br /><input id="prev" name="previewbutton" value="' . _html( 'preview' ) . '" onclick="preview(\'' . addslashes( htmlspecialchars( $this->tempname ) ) . '\')" type="button" style="display:none;">&nbsp;<input id="hideprev" name="hidepreviewbutton" value="' . _html( 'preview-hide' ) . '" onclick="hideprevx()" type="button" style="display:none;">&nbsp;<input id="startupload" value="' . _g( 'form-submit' ) . '" type="submit" >';
			$html .= $this->serialise() . '</form>
		
		<script type="text/javascript">		
			$( document ).ready( function(){
				$( "#prev" ).show();
				$( "#accbut" ).before( \'<iframe id="prevframe" src="preview.php" name="prev" width="100%" height="500" align="center" scrolling="yes" marginheight="0" marginwidth="0" frameborder="2" style="display:none;"></iframe><br />\' );
				$( "#accbut" ).click( function() { enableupload(); } );
				enableupload();
				validate("newfilename", "checkimg", "' . addslashes( _html( 'error-filename', 'svgtranslate' ) ) . '");
				validate("pagetext", "checkdesc", "' . addslashes( $lng['x']['plscom'] . ' <a target="_blank" href="http://commons.wikimedia.org/w/index.php?title=' . urlencode( $this->name ) . '">' . $this->name . '</a>' ) . '");
			} );
		</script>';
			return $html;
		}

		/**
		 * Perform the direct upload, return output of the upload bot
		 * @global string $lng The interface messages being used in the Luxo section of the I18N
		 * @return string
		 */
		private function do_direct_upload() {
			global $lng;

			if( !isset( $this->name, $this->targetlanguage, $this->originals,
			$this->translations, $this->destination, $this->pagetext )
			){
				$this->error( _( 'error-unexpected' ) );
			}

			$new_name = $this->destination;
			$desc = $this->pagetext;
			$finalsvg = $this->generate_svg();
			if( $this->file_exists( $new_name ) || !preg_match( '/^(File|Image):(.*)[.]svg$/i', $new_name ) ){
				$this->error( _( 'error-filename' ) );
			}
			if( strpos( $desc, _( 'author-complete' ) ) !== false ){
				$this->error( $lng['x']['plscom'] . ' <a target="_blank" href="http://commons.wikimedia.org/w/index.php?title=' . urlencode( $this->name ) . '">' . $this->name . '</a>' );
			}

			$output = '';
			$new_name = substr( $new_name, ( strpos( $new_name, ':' ) + 1 ) ); // Trim namespace
			$cwd = getcwd();
			do{
				$temp_name = tempnam( "/tmp", "svgt_" );
				$temp = @fopen( $temp_name, "w" );
			} while( $temp === false );
			$temp_dir = $temp_name . "-dir";
			mkdir( $temp_dir );

			$short_file = str_replace( " ", "_", $new_name );
			$short_file = str_replace( ":", "_", $short_file );
			$short_file = str_replace( "/", "_", $short_file );
			$short_file = str_replace( "\\", "_", $short_file );
			$short_file = str_replace( "'", "", $short_file );

			$newfile = $temp_dir . "/" . $short_file;
			if( file_put_contents( $newfile, $finalsvg ) === 0 ){
				rmdir( $temp_dir );
				unlink( $temp_name );
				$this->error( _( 'error-unexpected' ) );
			}

			$desc = trim( $desc );

			$newname = $short_file;

			// Create meta file
			$meta_file = $temp_dir . '/meta.txt';
			$meta = @fopen( $meta_file, "w" );
			fwrite( $meta, $newfile . "\n" );
			fwrite( $meta, $newname . "\n" );
			fwrite( $meta, $desc );
			fclose( $meta );

			// Run upload bot
			$upload_bot = "upload_bot.pl";
			$command = "/usr/bin/perl {$upload_bot} {$temp_dir}";

			$output .= shell_exec( $command );

			// Cleanup
			$debug_file = $temp_dir . "/debug.txt";
			@unlink( $debug_file );
			unlink( $meta_file );
			unlink( $newfile );
			rmdir( $temp_dir );
			fclose( $temp );
			unlink( $temp_name );

			// Output
			$ret = "<h3>" . _html( 'uploading' ) . "</h3>";
			if( $output == 'RESPONSE-OKAY' ){
				$temp = '<p>' . _html( 'uploaded', array( 'variables' => array( $newname ) ) ) . '</p>';
				$ret .= str_replace( $temp, $newname, "<a target='blank' href='http://commons.wikimedia.org/wiki/File:$newname'>$newname</a>" );
				$ret .= " (<a href=\"http://commons.wikimedia.org/w/index.php?action=edit&title=File:$newname\" target=\"_blank\">" . _html( 'description-edit' ) . '</a>)';
				$this->log( $this->name, $this->targetlanguage );
			} else {
				$this->error( _( 'error-upload' ) );
			}
			return $ret;
		}

		/**
		 * Handles $_REQUEST-style array passed to it, by setting local parameters as appropriate,
		 * deciding which form we should display, that sort of thing. Returns the name of the step we're at.
		 * @param array $request $_REQUEST (or -style) associative array.
		 * @return string
		 */
		public function handle_post( &$request ) {
			if( isset ( $request['cache'] ) ){
				$cache = $this->unserialise( $request['cache'] );
				foreach( $cache as $k => $v ){
					$v = str_replace( '&pipe;', '|', $v );
					$this->$k = !empty( $v ) ? $v : null;
				}
			}
			if( isset( $request['wpDestFile'] ) ){
				$step = 'upload';
				$this->destination = $request['wpDestFile'];
				$this->pagetext = $request['pagetext'];
				if( !isset( $request['accbut'] ) || $request['accbut'] != 'true' ){
					$this->error( _( 'error-must-accept' ) );
				}
			} else {
				if( isset( $request['licence'] ) ){
					$step = 'finalise_direct_upload';
					$this->description = $request['description'];
					$this->licence = $request['licence'];
				} else {
					if( isset( $request['method'] ) ){
						if( $request['method'] == 'manual' ){
							$step = "attachsvg";
						} else {
							if( $request['method'] == 'tusc' ){
								$step = 'getdetails';
								$this->tuscuser = $request['tuscuser'];
								$this->tuscpass = $request['tuscpass'];
							}
						}
						foreach( $request as $key => $value ){
							if( strpos( $key, "translation" ) === 0 ){
								$this->translations[intval( substr( $key, 11 ) )] = $value;
							}
						}
						$this->targetlanguage = $request['targetlanguage'];
					} else {
						if( isset( $request['svg'] ) ){
							$step = "translating";
							// Some browsers will send $_GET in utf8, others not.
							$this->name = trim( to_utf8( $request['svg'] ) );
						} else {
							$step = "start";
						}
					}
				}
			}

			if( $step == 'getdetails' || $step == 'upload' ){
				if( !validateTUSC( $this->tuscuser, $this->tuscpass ) ){
					$this->error( _( 'error-tusc-failed' ) );
				}
			}

			return $step;
		}

		/**
		 * Return the correct output for the name of the step passed
		 * @param string $step , the name of a step, generated by {@link handle_post}
		 * @return string
		 */
		public function do_step( $step ) {
			$output = '';
			switch( $step ){
				case 'start':
					$output = $this->generate_first_form();
					break;
				case 'translating':
					$output = $this->generate_second_form();
					break;
				case 'attachsvg':
					$this->attach_svg(); // Will trigger die()
					break;
				case 'getdetails':
					$output = $this->generate_third_form();
					break;
				case 'finalise_direct_upload':
					$output = $this->generate_fourth_form();
					break;
				case 'preview':
					$output = $this->generate_svg();
					break;
				case 'upload':
					$output = $this->do_direct_upload();
					break;
			}
			return $output;
		}

	}

?>