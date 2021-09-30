<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Core
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Schachbulle\ContaoLinkscollectionBundle\Klassen;

class Linkscollection
{

	/**
	 * Liefert die URL zurück, ggfs. mit Prefix Webarchiv
	 * @param string	ID des Links
	 * @param string	Webarchiv-Status des Links
	 * @return string	URL
	 */
	public static function getWeblink($url, $archiv)
	{
		return ($archiv) ? $GLOBALS['TL_CONFIG']['linkscollection_webarchiv'].$url : $url;
	}

	/**
	 * Liefert das Sprachicon zurück
	 * @param string	Sprache des Links
	 * @return string	Icon als <img ... > oder false
	 */
	public static function getLanguageIcon($language)
	{
		if($language)
		{
			$url_web = 'bundles/contaolinkscollection/images/flags/'.$language.'.png';
			$url_file = 'vendor/schachbulle/contao-linkscollection-bundle/src/Resources/public/images/flags/'.$language.'.png';
			if(file_exists(TL_ROOT.'/'.$url_file))
			{
				return ' <img src="'.$url_web.'" width=16" height="11" title="Seitensprache: '.$language.'">';
			}
		}

		return '';
	}

	/**
	 * Liefert das Favicon zurück
	 * @param string    ID des Links
	 * @return string   Icon-Pfad im Web
	 */
	public static function getFavicon($id)
	{
		$suffixes = array('ico','gif','png','jpg','svg'); // Erlaubte Favicon-Endungen
		$string = 'bundles/contaolinkscollection/images/favicon.png'; // Standardicon setzen

		foreach($suffixes as $suffix)
		{
			$url_web = 'bundles/contaolinkscollection/favicons/'.$id.'.'.$suffix;
			$url_file = 'vendor/schachbulle/contao-linkscollection-bundle/src/Resources/public/favicons/'.$id.'.'.$suffix;
			if(file_exists(TL_ROOT.'/'.$url_file))
			{
				$string = $url_web;
				break;
			}
		}

		return $string;
	}

	/**
	 * Download des Favicons und Informationen zur URL-Erreichbarkeit
	 * @param array     Datensatz des Links
	 * @return array    Modifizierter Datensatz
	 */
	public static function saveFavicon($arrRow)
	{

		// URL prüfen und ggfs. Favicon neu laden
		$objRequest = new \Request(); // Instanz der Contao-Klasse anlegen
		$objRequest->send(self::getWeblink($arrRow['url'], $arrRow['webarchiv']));

		$strError = '';
		$language = '';

		if(!$objRequest->hasError())
		{
			// Kein Fehler, deshalb Favicon-Link ermitteln
			$favicon = new \Schachbulle\ContaoLinkscollectionBundle\Klassen\FaviconDownloader(self::getWeblink($arrRow['url'], $arrRow['webarchiv']));
			if($favicon->icoExists)
			{
				$favicon_dir = TL_ROOT.'/vendor/schachbulle/contao-linkscollection-bundle/src/Resources/public/favicons/';
				if(!file_exists($favicon_dir)) mkdir($favicon_dir); // favicon-Verzeichnis anlegen, wenn nicht vorhanden
				// Favicon als Datei speichern
				$filename = $favicon_dir.$arrRow['id'].'.'.$favicon->icoType;
				$icon = 'bundles/contaolinkscollection/favicons/'.$arrRow['id'].'.'.$favicon->icoType;
				file_put_contents($filename, $favicon->icoData);
			}
			$language = self::checkLanguage($objRequest->response); // Sprache des Links ermitteln
			$cms = self::checkCMS($objRequest->response); // CMS des Links ermitteln
		}

		// Datenbank aktualisieren
		$arrRow['statedate'] = time();
		$arrRow['statecode'] = $objRequest->code;
		$arrRow['statetext'] = ($objRequest->error) ? : 'OK';
		$arrRow['language']  = $language;
		$arrRow['cms']       = $cms;
		$set = array
		(
			'statedate' => $arrRow['statedate'],
			'statecode' => $arrRow['statecode'] ? $arrRow['statecode'] : '',
			'statetext' => $arrRow['statetext'] ? $arrRow['statetext'] : '',
			'language'  => $arrRow['language'],
			'cms'       => $arrRow['cms'] ? $arrRow['cms'] : '',
			'cmsdate'   => $arrRow['statedate']
		);
		\Database::getInstance()->prepare('UPDATE tl_linkscollection_links %s WHERE id = ?')
		                        ->set($set)
		                        ->execute($arrRow['id']);

		return $arrRow;
	}

	/**
	 * Liest die Sprache aus dem DOM
	 * @param string    HTML der Webseite
	 * @return string   Sprache, z.B. de-DE
	 */
	public static function checkLanguage($string)
	{
		// DOM auf <html lang=""> untersuchen
		$dom = new \PHPHtmlParser\Dom;
		$string = iconv('windows-1251', 'utf-8', $string); // Fix: mb_eregi_replace() expects parameter 3 to be string, null given
		$dom->load($string);
		$html = $dom->find('html')[0];
		if($html) $lang = $html->getAttribute('lang');

		return $lang ? $lang : ''; // Sprache zurückgeben
	}

	/**
	 * Liest den Generator aus dem DOM
	 * @param string    HTML der Webseite
	 * @return string
	 */
	public static function checkCMS($string)
	{
		// DOM auf <meta name="generator"> untersuchen und dort das Attribut content auslesen
		$dom = new \PHPHtmlParser\Dom;
		$string = iconv('windows-1251', 'utf-8', $string); // Fix: mb_eregi_replace() expects parameter 3 to be string, null given
		$dom->load($string);
		foreach($dom->find('meta') as $meta)
		{
			if($meta->getAttribute('name') == 'generator')
			{
				return $meta->getAttribute('content'); // CMS gefunden
			}
		}
		return ''; // Kein CMS gefunden
	}

	/**
	 * Liefert eine Breadcrumb-Navigation der Kategorien
	 * @param string	Serialisiertes Array mit den Daten
	 * @return array
	 */
	public static function Breadcrumb($category)
	{
		// Breadcrumb-Navigation erstellen
		$breadcrumb = array();
		$x = 0;
		while($category > 0)
		{
			$objTemp = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection WHERE published = ? AND id = ?')
			                                   ->execute(1, $category);
			$breadcrumb[] = '<a href="contao/main.php?do=linkscollection&table=tl_linkscollection_links&id='.$objTemp->id.'" title="Links der Kategorie '.$objTemp->title.' bearbeiten">'.$objTemp->title.'</a>';
			$category = $objTemp->pid;
			$x++;
		}

		$string = implode(' &#10140; ', array_reverse($breadcrumb));
		return $string;
	}

	/**
	 * Listet eine Analyse der Links auf
	 * @param string	Serialisiertes Array mit den Daten
	 * @return array
	 */
	public function Linkanalyse()
	{
		if(\Input::get('key') != 'analyse')
		{
			return '';
		}

		$content = '
		<div id="tl_buttons">
			<a href="contao/main.php?do=linkscollection&amp;rt='.REQUEST_TOKEN.'" class="header_back" title="" accesskey="b" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>
		</div>';

		$content .= '<div id="tl_listing" class="tl_listing_container"><ul class="tl_listing">';

		// Veröffentlichte Kategorien/Links laden
		$objCats = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection WHERE published = ?')
		                                   ->execute(1);
		$objLinks = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ?')
		                                    ->execute(1);

		// Ausgabe des 1. Kopfes
		$content .= '
		<li class="tl_linkanalyse_head">
			<div class="tl_left">
				<label>Veröffentlicht:</label>
				<b>'.$objCats->numRows.'</b> Kategorien und <b>'.$objLinks->numRows.'</b> Links
			</div>
			<div class="tl_right">&nbsp;</div>
			<div style="clear:both"></div>
		</li>';

		// Fehlerhafte Links
		$content .= '
		<li class="tl_linkanalyse_top">
			<div class="tl_left">
				Fehlerhafte Links
			</div>
			<div class="tl_right">&nbsp;</div>
			<div style="clear:both"></div>
		</li>';

		if($objLinks->numRows > 0)
		{
			while($objLinks->next())
			{
				if($objLinks->problemcount > 0)
				{
					$titel_edit  = str_replace('%s', $objLinks->id, $GLOBALS['TL_LANG']['tl_linkscollection_list']['edit']);
					$titel_edit_header  = str_replace('%s', $objLinks->pid, $GLOBALS['TL_LANG']['tl_linkscollection_list']['edit_header']);

					$content .= '
					<li onmouseout="Theme.hoverDiv(this,0)" onmouseover="Theme.hoverDiv(this,1)" class="tl_linkanalyse_row" style="">
						<div class="tl_left">
							<strong>'.$objLinks->title.'</strong> ('.$objLinks->url.')
							<br><i>'.date("d.m.Y, H:i", $objLinks->problemdate).'</i>
							<i>- '.$objLinks->problemcount.' Meldungen</i>
						</div>
						<div class="tl_right">
							<a class="edit" title="'.$titel_edit.'" href="contao/main.php?do=linkscollection&amp;table=tl_linkscollection_links&amp;act=edit&amp;id='.$objLinks->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.\Input::get('ref').'"><img width="12" height="16" alt="Link bearbeiten" src="system/themes/'.\Controller::getTheme().'/images/edit.gif"></a>
							<a class="editheader" title="'.$titel_edit_header.'" href="contao/main.php?do=linkscollection&amp;table=tl_linkscollection_links&amp;id='.$objLinks->pid.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.\Input::get('ref').'"><img width="16" height="16" alt="Links der Kategorie bearbeiten" src="system/themes/'.\Controller::getTheme().'/images/header.gif"></a>
						</div>
						<div style="clear:both"></div>
					</li>';
				}
			}
		}

		// Unveröffentlichte Kategorien/Links laden
		$objCats = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection WHERE published = ?')
		                                   ->execute('');
		$objLinks = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ?')
		                                    ->execute('');

		// Unveröffentlichte Links
		$content .= '
		<li class="tl_linkanalyse_head">
			<div class="tl_left">
				<label>Unveröffentlicht:</label>
				<b>'.$objCats->numRows.'</b> Kategorien und <b>'.$objLinks->numRows.'</b> Links
			</div>
			<div class="tl_right">&nbsp;</div>
			<div style="clear:both"></div>
		</li>';

		$content .= '
		<li class="tl_linkanalyse_top">
			<div class="tl_left">
				Unveröffentlichte Links
			</div>
			<div class="tl_right">&nbsp;</div>
			<div style="clear:both"></div>
		</li>';

		// Unveröffentlichte ausgeben
		if($objLinks->numRows > 0)
		{
			while($objLinks->next())
			{
				$titel_edit  = str_replace('%s', $objLinks->id, $GLOBALS['TL_LANG']['tl_linkscollection_list']['edit']);
				$titel_edit_header  = str_replace('%s', $objLinks->pid, $GLOBALS['TL_LANG']['tl_linkscollection_list']['edit_header']);

				$content .= '
				<li onmouseout="Theme.hoverDiv(this,0)" onmouseover="Theme.hoverDiv(this,1)" class="tl_linkanalyse_row" style="">
					<div class="tl_left">
						<strong>'.$objLinks->title.'</strong> ('.$objLinks->url.')
						<br><i>'.date("d.m.Y H:i", $objLinks->initdate).'</i>
						<i>von '.$objLinks->name.'</i>
					</div>
					<div class="tl_right">
						<a class="edit" title="'.$titel_edit.'" href="contao/main.php?do=linkscollection&amp;table=tl_linkscollection_links&amp;act=edit&amp;id='.$objLinks->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.\Input::get('ref').'"><img width="12" height="16" alt="Link bearbeiten" src="system/themes/'.\Controller::getTheme().'/images/edit.gif"></a>
						<a class="editheader" title="'.$titel_edit_header.'" href="contao/main.php?do=linkscollection&amp;table=tl_linkscollection_links&amp;id='.$objLinks->pid.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.\Input::get('ref').'"><img width="16" height="16" alt="Links der Kategorie bearbeiten" src="system/themes/'.\Controller::getTheme().'/images/header.gif"></a>
					</div>
					<div style="clear:both"></div>
				</li>';
			}
		}

		$content .= '</ul></div>';

		return $content;
	}


	/**
	 * Listet den Datensatz eines Links im Backend auf
	 * @param array     $record           Array mit dem Datensatz
	 * @param boolean   $buttons          true/false = Buttons generieren (nicht nötig bei Standardausgabe)
	 * @return string   HTML-Ausgabe
	 */
	public function ViewLinkrow($record, $buttons = false)
	{
		$refreshtime = time() - ($GLOBALS['TL_CONFIG']['linkscollection_test_duration'] * 86400);

		if($record['statedate'] < $refreshtime && $GLOBALS['linkscollection_linkcheck_count'] <= $GLOBALS['TL_CONFIG']['linkscollection_maxlinkcheck'])
		{
			$GLOBALS['linkscollection_linkcheck_count']++;
			log_message('GLOBALS linkscollection_linkcheck_count nach Inkrementierung='.$GLOBALS['linkscollection_linkcheck_count'], 'linkscollection.log');
			log_message('TL_CONFIG linkscollection_maxlinkcheck='.$GLOBALS['TL_CONFIG']['linkscollection_maxlinkcheck'], 'linkscollection.log');
			log_message('Prüfe mit diesen Einstellungen:', 'linkscollection.log');
			log_message(print_r($record, true), 'linkscollection.log');
			// URL neu prüfen und Favicon downloaden
			$record = self::saveFavicon($record);
			log_message('Ergebnis:', 'linkscollection.log');
			log_message(print_r($record, true), 'linkscollection.log');
		}
		// Letzte Prüfung ausgeben
		$sekunden = time() - $record['statedate'];
		if($sekunden < 61) $check_time = $sekunden.' Sek.'; // <= 1 Minute
		elseif($sekunden < 7201) $check_time = ceil($sekunden / 60).' Min.'; // <= 2 Stunden
		elseif($sekunden < 259201) $check_time = ceil($sekunden / 3600).' Std.'; // <= 3 Tage
		else $check_time = ceil($sekunden / 86400).' Tg.';

		// Nächster Zeitpunkt für eine Prüfung
		$refreshtime_next = $record['statedate'] + ($GLOBALS['TL_CONFIG']['linkscollection_test_duration'] * 86400);

		// Favicon suchen
		$icon = self::getFavicon($record['id']);
		// Sprache-Icon zurückgeben
		$language = self::getLanguageIcon($record['language']);

		switch($record['statecode'])
		{
			case 0:
				$info = ' <span style="color:red">invalid host</span>';
				$style = 'background-color:#FF0000; color:white; padding-left:2px; padding-right:2px;';
				$record['statecode'] = '000';
				break;
			case ($record['statecode'] >= 400):
				$info = ' <span style="color:red">not found ('.$record['statetext'].')</span>';
				$style = 'background-color:#FF0000; color:white; padding-left:2px; padding-right:2px;';
				break;
			case ($record['statecode'] >= 300):
				$info = ' <span style="color:blue">redirect ('.$record['statetext'].')</span>';
				$style = 'background-color:#FFFF00; padding-left:2px; padding-right:2px;';
				break;
			case ($record['statecode'] >= 200):
				$style = 'background-color:#00FF00; padding-left:2px; padding-right:2px;';
				break;
			default:
				$info = '';
		}

		$archivclass = ($record['webarchiv']) ? ' webarchiv' : ''; // Webarchiv-Klasse hinzufügen

		$line = '';
		$line .= '<div class="tl_content_right height18">';
		$line .= '<span style="margin-right:5px; color:#9F5000;" title="Verwendetes CMS">'.$record['cms'].'</span>';
		$line .= '<span style="'.$style.' font-weight:bold;">'.$record['statecode'].'</span>';
		$line .= '<span style="font-size:0.6rem; margin-left:3px; display:inline-block; width:60px;" title="Zeitpunkt der letzten Prüfung. Nächste Prüfung: '.date('d.m.Y H:i',$refreshtime_next).'"> vor '.$check_time.'</span>';
		$line .= '</div>';
		$line .= '<div class="favicon-img height18'.$archivclass.'" style="background-image: url('.$icon.');">';
		$line .= '<a href="'.$record['url'].'" target="_blank"><b>'.$record['title'].'</b></a> - '.$language.' '.$record['url'].$info;
		if($record['text']) $line .= '<div class="description">'.$record['text'].'</div>';
		$line .= "</div>";
		$line .= "\n";

		return $line;
	}

	/**
	 * Listet alle Links auf
	 * @param string	Serialisiertes Array mit den Daten
	 * @return array
	 */
	public function GenerateLinklist()
	{
		// Hilfsarrays für Filter/Suche
		$where = array();
		$value = array();

		// Standardabfrage
		$query = "SELECT * FROM tl_linkscollection_links";
		$Template = new \BackendTemplate('be_linklist');
		$Template->request = ampersand(\Environment::getInstance()->request, true);
		$Template->links = array();

		// Set default variables
		$Template->apply = $GLOBALS['TL_LANG']['MSC']['apply'];

		// Session laden
		$session = \Session::getInstance()->getData();
		$filter = 'tl_linkscollection_list'; // Sessionname

		// Filter, Suche und Limit setzen
		if (\Input::post('FORM_SUBMIT') == 'tl_filters' || \Input::post('FORM_SUBMIT') == 'tl_filters_limit')
		{

			// Suchfeld in Session schreiben, Suchbegriff vorerst nicht
			$session['search'][$filter]['value'] = '';
			$session['search'][$filter]['field'] = \Input::post('tl_field', true);

			// Validität des Regex im Suchbegriff prüfen
			if (\Input::postRaw('tl_value') != '')
			{
				try
				{
					\Database::getInstance()->prepare("SELECT * FROM tl_linkscollection_links WHERE ".\Input::post('tl_field', true)." REGEXP ?")
					                        ->limit(1)
					                        ->execute(\Input::postRaw('tl_value'));

					$session['search'][$filter]['value'] = \Input::postRaw('tl_value');
				}
				catch (\Exception $e) {}
			}

			// Limit prüfen
			$strLimit = \Input::post('tl_limit');
			if ($strLimit == 'tl_limit')
			{
				unset($session['filter'][$filter]['limit']);
			}
			else
			{
				// Benutzereingaben validieren
				if ($strLimit == 'all' || preg_match('/^[0-9]+,[0-9]+$/', $strLimit))
				{
					$session['filter'][$filter]['limit'] = $strLimit;
				}
			}

			\Session::getInstance()->setData($session);
			\Controller::reload();
		}

		// Suchbegriff zur Abfrage hinzufügen
		if ($session['search'][$filter]['value'] != '')
		{
			$where[] = "CAST(" . $session['search'][$filter]['field'] . " AS CHAR) REGEXP ?";
			$value[] = $session['search'][$filter]['value'];

			$Template->searchClass = ' active';
		}

		// Suchoptionen
		$fields = array('title', 'url', 'text', 'cms');
		$options = '';

		foreach ($fields as $field)
		{
			$options .= sprintf('<option value="%s"%s>%s</option>', $field, (($field == $session['search'][$filter]['field']) ? ' selected' : ''), (is_array($GLOBALS['TL_LANG']['tl_linkscollection_list'][$field]) ? $GLOBALS['TL_LANG']['tl_linkscollection_list'][$field][0] : $GLOBALS['TL_LANG']['tl_linkscollection_list'][$field]));
		}

		$Template->searchOptions = $options;
		$Template->keywords = specialchars($session['search'][$filter]['value']);
		$Template->search = specialchars($GLOBALS['TL_LANG']['MSC']['search']);
		$Template->showOnly = specialchars($GLOBALS['TL_LANG']['MSC']['showOnly']);

		// Where
		if (!empty($where))
		{
			$query .= " WHERE ".implode(' AND ',$where);
		}

		// Order by
		$query .= " ORDER BY title ASC";

		// Execute query
		$objLinks = \Database::getInstance()->prepare($query)
		                                    ->limit(500)
		                                    ->execute($value);

		$GLOBALS['TL_CONFIG']['linkscollection_maxlinkcheck'] = -1; // Keine Links checken in dieser Funktion

		$arrLinks = array();
		if($objLinks->numRows > 0)
		{
			while($objLinks->next())
			{
				$item = array
				(
					'id'           => $objLinks->id,
					'pid'          => $objLinks->pid,
					'title'        => $objLinks->title,
					'url'          => $objLinks->url,
					'text'         => $objLinks->text,
					'cms'          => $objLinks->cms,
					'statecode'    => $objLinks->statecode,
					'statedate'    => $objLinks->statedate,
					'statetext'    => $objLinks->statetext,
					'ref'          => \Input::get('ref'),
					'icon'         => $this->getFavicon($objLinks->id),
					'edit'         => str_replace('%s', $objLinks->id, $GLOBALS['TL_LANG']['tl_linkscollection_list']['edit']),
					'edit_header'  => str_replace('%s', $objLinks->pid, $GLOBALS['TL_LANG']['tl_linkscollection_list']['edit_header']),
					'language'     => $objLinks->language,
				);
				$header = '<div class="tl_content no_padding even click2edit toggle_select hover-div">';
				$header .= '<div class="tl_content_right height18">';
				$header .= '<a class="edit" title="'.$item['edit'].'" href="contao/main.php?do=linkscollection&amp;table=tl_linkscollection_links&amp;act=edit&amp;id='.$item['id'].'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$item['ref'].'"><img width="12" height="16" alt="Link bearbeiten" src="system/themes/'.\Controller::getTheme().'/images/edit.gif"></a>';
				$header .= '<a class="editheader" title="'.$item['edit_header'].'" href="contao/main.php?do=linkscollection&amp;table=tl_linkscollection_links&amp;id='.$item['pid'].'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$item['ref'].'"><img width="12" height="16" alt="Links der Kategorie bearbeiten" src="system/themes/'.\Controller::getTheme().'/images/header.gif"></a>';
				$header .= '</div>';
				$header .= self::ViewLinkrow($item);
				$header .= '</div>';
				$arrLinks[] = $header;
			}
		}

		$Template->numRows = ($objLinks->numRows) ? $objLinks->numRows . ' Treffer' : 'Not found';
		$Template->links = $arrLinks;
		return $Template->parse();
	}

	/**
	 * Berechnet die Statistiken der Linksammlung neu
	 * @param string	Serialisiertes Array mit den Daten
	 * @return array
	 */
	public function Statistik()
	{
		if(\Input::get('key') != 'statistik')
		{
			return '';
		}

		\Schachbulle\ContaoLinkscollectionBundle\Klassen\CreateStatistics::run();

		// Zurücklink generieren, ab C4 ist das ein symbolischer Link zu "contao"
		if (version_compare(VERSION, '4.0', '>='))
		{
			$backlink = \System::getContainer()->get('router')->generate('contao_backend');
		}
		else
		{
			$backlink = 'main.php';
		}
		$backlink .= '?do=linkscollection&rt='.REQUEST_TOKEN;
		\Controller::redirect($backlink); // Linksammlung Startseite aufrufen
	}

}
