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

//Tool um die lizenz eines Bildes herauszufinden.
//Benutze Kategorien, in dem das Bild ist (nicht in dem die vorlagen sind)
//Vorlagen zurückverfolgen bis zur [[Category:Copyright statuses]]

ini_set('user_agent', 'SVG Translate by Jarry on the Toolserver / PHP');

/**
 * Helper class
 * @package SVGTranslate
 */
class licencehelper { 
	public static function get_licences($image){
		//Returns an array (['originals'], array, and ['possible'], an array of licensing options
		//for derivative works of a given file) , or false if there are none, for whatever reason.
		
		$output = "";
		$image = trim($image);
		$image = str_replace(" ", "_",$image);
		$islicence = array();

		$url = "http://commons.wikimedia.org/w/api.php?action=query&prop=templates&format=php&tllimit=500&titles=".urlencode($image);
		$raw = file_get_contents($url);
		$unserialized = unserialize($raw);
		//Array key (pageid) herausfinden

		$pageid = array_keys($unserialized['query']['pages']);

		//NOTE: haven't been able to get the files for these from Luxo yet.
		//whitelist erstellen (Templates, die sicher keine Lizenzen sind)
		$whitelist = array();
		//blacklist erstellen (Templates, die sicher Lizenzen sind)
		$suretemplatelist = array();

		foreach( $unserialized['query']['pages'][$pageid['0']]['templates'] as $tmpl ) {
			$template = $tmpl['title']; //easier
			$arraytemplates[] = $template;

			if( !in_array($template,$whitelist) && preg_match("/\/[a-z][a-z]$/",$template) === 0
				&& strlen($template) > 11 && $template != "Template:PD" && substr($template,0,14) != "Template:Potd/" ) {
				//nicht in whitelist und keine sprachvariante "/en" , "/de", ...
				//Jede Vorlage prüfen: ist es eine Lizenz?

				$islicence[$template] = false; //default
				//Kategorien prüfen, dann noch Subkategorien auf "licence tags" prüfen.
				
				//Kats der Vorlage laden.
				$url = "http://commons.wikimedia.org/w/api.php?action=query&prop=categories&format=php&cllimit=500&titles=".urlencode($template);
				$raw = file_get_contents($url);
				$catunserialized = unserialize($raw);
				$catid = array_keys($catunserialized['query']['pages']);
				//Vorlagen durchgehen
				if(	isset($catunserialized['query']['pages'][$catid['0']]['categories'])	) {
					foreach( $catunserialized['query']['pages'][$catid['0']]['categories'] as $katofTemp ) {
						if( $katofTemp['title'] == "Category:License tags" ) {
							$islicence[$template] = true;
						}
					}
				}
				
				//Noch nicht als Lizenz identifiziert. Nun Kategorien der Kategorie durchsuchen.
				if( $islicence[$template] == false && isset( $catunserialized['query']['pages'][$catid['0']]['categories'] ) ) {
					foreach( $catunserialized['query']['pages'][$catid['0']]['categories'] as $katofTemp ) {
						$url = "http://commons.wikimedia.org/w/api.php?action=query&prop=categories&format=php&cllimit=500&titles=".urlencode($katofTemp['title']);
						$raw = file_get_contents($url);
						$catunserialized2 = unserialize($raw);
						$catid = array_keys($catunserialized2['query']['pages']);
						if( $catunserialized2['query']['pages'][$catid['0']]['categories'] ) {
							foreach( $catunserialized2['query']['pages'][$catid['0']]['categories'] as $KatOfKat ) {
								if( $KatOfKat['title'] == "Category:License tags" ) {
								 $islicence[$template] = true;
								}
							}
						}
					}
				}
				
				//Noch nicht als Lizenz identifiziert. Nun Kategorien der Unterkategorie durchsuchen.
				if( $islicence[$template] == false && isset( $catunserialized2['query']['pages'][$catid['0']]['categories'] ) ) {
					foreach( $catunserialized2['query']['pages'][$catid['0']]['categories'] as $katofTemp ) {
						$url = "http://commons.wikimedia.org/w/api.php?action=query&prop=categories&format=php&cllimit=500&titles=".urlencode($katofTemp['title']);
						$raw = file_get_contents($url);
						$catunserialized3 = unserialize($raw);
						$catid = array_keys($catunserialized3['query']['pages']);
						
						if( $catunserialized3['query']['pages'][$catid['0']]['categories'] ) {
							foreach( $catunserialized3['query']['pages'][$catid['0']]['categories'] as $KatOfKat ) {
								if( $KatOfKat['title'] == "Category:License tags" ) {
								 $islicence[$template] = true;
								}
							}
						}
					}
				}
			}
		}

		if( $islicence ) {
			$blacklist = array( "Template:Nonderivative", "Template:Speedy delete text", "Template:Speedydelete", 	"Template:Delete", "Template:Copyvio", "Template:Nld", "Template:Own work", "Template:No licence");
			$save = true;
			foreach( $blacklist as $blacklisted){
				if( in_array($blacklisted,$arraytemplates) ) {
					$save = false;
				}
			}
			if( $save ){
				foreach( $islicence as $lizenz => $isit ) {
					if( $isit ){
						$output .=	substr($lizenz,9)."|";
					}
				}
			} else {
				return false;
			}
		}


		if( trim($output) == "" ) {
			return false;
		}

		$licarray = explode( "|", $output );
		$possible_licences = array();
		foreach( $licarray as $licencecode ) {
			if( $licencecode !== "" ) {
				$possible_licences = array_merge( $possible_licences, self::map_to_licences( $licencecode ) );
			}
		}
		return array('original' => $licarray, 'possible' => $possible_licences);
	}

	private static function map_to_licences( $code ){
		$licences = array();
		//GFDL
		if( substr( $code,0,4 ) == "GFDL" || $code == "Picswiss" || $code == "PolishSenateCopyright" || $code == "PolishPresidentCopyright" || $code == "Pressefotos Die Gruenen"	|| strtolower( $code ) == "attribution" ) {
			$licences[] = 'GFDL';
		}

		//Attribution
		if( strtolower( $code ) == "attribution" ) {
			$licences[] = 'Attribution';
			$licences[] = 'Cc-by-3.0';
			$licences[] = 'Cc-by-sa-3.0';
		}

		//Attribution
		if( strtolower( $code ) == "copyrightbywikimedia" || strtolower( $code ) == "copyright by wikimedia" ) {
			$licences[] = 'CopyrightByWikimedia';
		}

		//GPL
		if( $code == "GPL" || strtolower( $code ) == "attribution" ) {
			$licences[] = 'GPL';
		}

		//GPL v2 only
		if( $code == "GPLv2 only" || $code == "GPL" || strtolower( $code ) == "attribution" ) {
			$licences[] =	"GPLv2 only";
		}

		//Easy ones
		if( $code == "FAL" || $code == "FWL" || $code == "MPL" || $code == "CeCILL" || $code == "LGPL" ) {
			$licences[] = $code;
		}

		//CC-Lizenzen****
		if( strtolower( substr( $code,0,3 ) ) == "cc-" ) {
			//CC-by
			if( stristr( $code,"-by" ) && !stristr( $code,"-sa" ) ) {
			$licences[] = 'Cc-by-3.0';
			$licences[] = 'Cc-by-sa-3.0';
			$licences[] = 'GFDL';
			}
			
			//CC-by-sa
			if( stristr( $code,"-by" ) && stristr( $code,"-sa" ) ) {
			$licences[] = 'Cc-by-sa-3.0';
			}
			
			//CC-sa
			if( !stristr( $code,"-by" ) && stristr( $code,"-sa" ) ) {
			$licences[] = 'Cc-sa-1.0';
			}
		}
		if( strtolower( substr( $code,0,3 ) ) == "pd-" || strtolower( $code ) == "copyrighted free use" || strtolower( $code ) == "cc-pd" || strtolower( $code ) == "cc-zero" ) {
			$possible = array( "PD-self", "Cc-by-sa-3.0", "Cc-by-3.0", "Cc-sa-1.0", "GFDL", "FWL", "MPL", "FAL", "GPL", "GPLv2 only", "LGPL", "CeCILL", "Attribution", "CopyrightByWikimedia" );
			$licences = array_merge( $licences, $possible );
		}
		return $licences;
	}
}
?>
