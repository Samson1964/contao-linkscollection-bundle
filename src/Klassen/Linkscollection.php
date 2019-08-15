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
			$url = 'bundles/contaolinkscollection/images/flags/'.$language.'.png';
			if(file_exists(TL_ROOT.'/'.$url))
			{
				return ' <img src="'.$url.'" width=16" height="11" title="Seitensprache: '.$language.'">';
			}
		}

		return '';
	}

	/**
	 * Liefert das Favicon zurück
	 * @param string	ID des Links
	 * @return string	Icon-Pfad
	 */
	public static function getFavicon($id)
	{
		$suffixes = array('ico','gif','png','jpg'); // Erlaubte Favicon-Endungen
		$string = 'bundles/contaolinkscollection/images/favicon.png'; // Standardicon setzen

		foreach($suffixes as $suffix)
		{
			$url = 'bundles/contaolinkscollection/favicons/'.$id.'.'.$suffix;
			if(file_exists(TL_ROOT.'/'.$url))
			{
				$string = $url;
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
				// Saving favicon to file
				$filename = TL_ROOT.'/vendor/schachbulle/contao-linkscollection-bundle/src/Resources/public/favicons/'.$arrRow['id'].'.'.$favicon->icoType;
				$icon = 'bundles/contaolinkscollection/favicons/'.$arrRow['id'].'.'.$favicon->icoType;
				file_put_contents($filename, $favicon->icoData);
			}
			// Sprache der URL ermitteln
			$language = self::checkLanguage($objRequest->response);
			// CMS der URL ermitteln
			$cms = self::checkCMS($objRequest->response);

		}

		// Datenbank aktualisieren
		$arrRow['statedate'] = time();
		$arrRow['statecode'] = $objRequest->code;
		$arrRow['statetext'] = ($objRequest->error) ? : 'OK';
		$arrRow['language'] = $language;
		$arrRow['cms'] = $cms;
		$set = array
		(
			'statedate' => $arrRow['statedate'],
			'statecode' => $arrRow['statecode'],
			'statetext' => $arrRow['statetext'],
			'language'  => $arrRow['language'],
			'cms'       => $arrRow['cms'],
			'cmsdate'   => $arrRow['statedate']
		);
		\Database::getInstance()->prepare('UPDATE tl_linkscollection_links %s WHERE id = ?')
		                        ->set($set)
		                        ->execute($arrRow['id']);

		return $arrRow;
	}

	/**
	 * Liest die Sprache aus dem DOM
	 * @param string	HTML der Webseite
	 * @return string
	 */
	public static function checkLanguage($string)
	{
		//$dom = new \DOMDocument();
		//$dom->loadHTML($string);
		//$html = $dom->getElementsByTagName('html');
		//$lang = $html->getAttribute('lang'); 

		return $lang ? $lang : '';

		$return = '';

		// DOM aus einem String
		$html = str_get_html($string);
		if($html)
		{
			// <html lang="?">
			foreach($html->find('html') as $item)
			{
				if($item->lang)
				{
					$return = $item->lang;
					break;
				}
			}
		}

		return $return;
	}

	/**
	 * Liest den Generator aus dem DOM
	 * @param string	HTML der Webseite
	 * @return string
	 */
	public static function checkCMS($string)
	{
		$return = '';
		return $return;

		// DOM aus einem String
		$html = str_get_html($string);
		if($html)
		{
			// <meta name="generator" content="?">
			foreach($html->find('meta') as $item)
			{
				if($item->name == 'generator')
				{
					$return = $item->content;
					break;
				}
			}
		}

		return $return;
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

		$string = implode(' > ', array_reverse($breadcrumb));
		return $string;
	}

	/**
	 * Listet eine Analyse der Links auf
	 * @param string	Serialisiertes Array mit den Daten
	 * @return array
	 */
	public function Linkanalyse(DataContainer $dc)
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

		if($objLinks->numRows > 1)
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
							<a class="edit" title="'.$titel_edit.'" href="contao/main.php?do=linkscollection&amp;table=tl_linkscollection_links&amp;act=edit&amp;id='.$objLinks->id.'&amp;rt='.\Input::get('rt').'&amp;ref='.\Input::get('ref').'"><img width="12" height="16" alt="Link bearbeiten" src="system/themes/default/images/edit.gif"></a>
							<a class="editheader" title="'.$titel_edit_header.'" href="contao/main.php?do=linkscollection&amp;table=tl_linkscollection_links&amp;id='.$objLinks->pid.'&amp;rt='.\Input::get('rt').'&amp;ref='.\Input::get('ref').'"><img width="16" height="16" alt="Links der Kategorie bearbeiten" src="system/themes/default/images/header.gif"></a>
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
						<a class="edit" title="'.$titel_edit.'" href="contao/main.php?do=linkscollection&amp;table=tl_linkscollection_links&amp;act=edit&amp;id='.$objLinks->id.'&amp;rt='.\Input::get('rt').'&amp;ref='.\Input::get('ref').'"><img width="12" height="16" alt="Link bearbeiten" src="system/themes/default/images/edit.gif"></a>
						<a class="editheader" title="'.$titel_edit_header.'" href="contao/main.php?do=linkscollection&amp;table=tl_linkscollection_links&amp;id='.$objLinks->pid.'&amp;rt='.\Input::get('rt').'&amp;ref='.\Input::get('ref').'"><img width="16" height="16" alt="Links der Kategorie bearbeiten" src="system/themes/default/images/header.gif"></a>
					</div>
					<div style="clear:both"></div>
				</li>';
			}
		}

		$content .= '</ul></div>';

		return $content;
	}


	/**
	 * Listet alle Links auf
	 * @param string	Serialisiertes Array mit den Daten
	 * @return array
	 */
	public function Linklist(DataContainer $dc)
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
		$fields = array('title', 'url', 'text');
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

		$arrLinks = array();
		if($objLinks->numRows > 0)
		{
			while($objLinks->next())
			{
				$arrLinks[] = array
				(
					'id'           => $objLinks->id,
					'pid'          => $objLinks->pid,
					'title'        => $objLinks->title,
					'url'          => $objLinks->url,
					'text'         => $objLinks->text,
					'ref'          => \Input::get('ref'),
					'icon'         => $this->getFavicon($objLinks->id),
					'edit'         => str_replace('%s', $objLinks->id, $GLOBALS['TL_LANG']['tl_linkscollection_list']['edit']),
					'edit_header'  => str_replace('%s', $objLinks->pid, $GLOBALS['TL_LANG']['tl_linkscollection_list']['edit_header']),
				);
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
	public function Statistik(DataContainer $dc)
	{
		if(\Input::get('key') != 'statistik')
		{
			return '';
		}

		//\Linkbuilder::run();
		
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
		header('Location:'.$backlink);

	}

}
