<?php

declare(strict_types=1);

/**
 * Linksammlung für Contao Open Source CMS
 *
 * Der erweiterte Filter geht auf den "BackendExtendedCssEditorFilter" von
 * Cliff Parnitzky (2013-2014, LGPL) zurück und wurde für die Linksammlung
 * angepasst.
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoLinkscollectionBundle\Klassen;

use Contao\Backend;
use Contao\Input;
use Contao\StringUtil;

/**
 * Zusätzlicher Filter für die Linkliste im Backend.
 *
 * Der Filter schränkt die Liste über eine der in
 * $GLOBALS['TL_LINKSCOLLECTION_LINKS_FILTER'] hinterlegten Bedingungen ein
 * (Titel, URL, Beschreibung oder CMS). Die Bedienelemente dazu sind derzeit
 * nicht eingeblendet — der button_callback in tl_linkscollection_links liefert
 * bewusst nichts zurück. Die Auswertung bleibt trotzdem aktiv, damit sich der
 * Filter über die Adresszeile ansteuern lässt und beim Wiedereinschalten der
 * Bedienelemente nichts fehlt.
 */
class Filter extends Backend
{
	public const CSS_FILTER_PARAM_FIELD_NAME = 'FilterField';
	public const CSS_FILTER_PARAM_VALUE_NAME = 'FilterValue';

	/**
	 * Baut die Bedienelemente des Filters als HTML.
	 *
	 * @param string $url      Adresse der aktuellen Ansicht; die eigenen
	 *                         Filterparameter werden daraus entfernt, damit sie
	 *                         sich beim Umschalten nicht doppeln
	 * @param string $label    Beschriftung vor dem Auswahlfeld
	 * @param string $strTable Name der Tabelle; daraus wird der Name des
	 *                         Filterarrays in $GLOBALS abgeleitet
	 *
	 * @return string Das fertige HTML des Filterbereichs
	 */
	public static function getFilterWidget(string $url, string $label, string $strTable): string
	{
		$url = self::removeUrlParams($url, self::CSS_FILTER_PARAM_FIELD_NAME);
		$url = self::removeUrlParams($url, self::CSS_FILTER_PARAM_VALUE_NAME);

		$actFilterField = (string) Input::get(self::CSS_FILTER_PARAM_FIELD_NAME);
		$actFilterValue = urldecode((string) Input::get(self::CSS_FILTER_PARAM_VALUE_NAME));

		$feldId = self::CSS_FILTER_PARAM_FIELD_NAME;
		$wertId = self::CSS_FILTER_PARAM_VALUE_NAME;

		$returnString  = '<div class="tl_subpanel tl_search" style="clear: both; margin-bottom: 15px; float: none; margin-top: 10px;">';
		$returnString .= '<strong>'.$label.'</strong>';
		$returnString .= '<select id="'.$feldId.'" style="width: 90px;" class="tl_select">';
		$returnString .= '<option value="">---</option>';

		foreach (array_keys($GLOBALS[strtoupper($strTable).'_FILTER'] ?? array()) as $filter)
		{
			$beschriftung = $GLOBALS['TL_LANG'][$strTable]['extendedFilterOption'][$filter] ?? $filter;
			$returnString .= '<option value="'.$filter.'"'.($actFilterField === $filter ? ' selected="selected"' : '').'>'.$beschriftung.'</option>';
		}

		$returnString .= '</select>';
		$returnString .= '<span> = </span>';
		$returnString .= '<input type="text" value="'.StringUtil::specialchars($actFilterValue).'" class="tl_text" id="'.$wertId.'"'
			.' onchange="location.href = \''.$url.'&amp;'.$feldId.'=\' + document.getElementById(\''.$feldId.'\').value'
			.' + \'&'.$wertId.'=\' + encodeURIComponent(document.getElementById(\''.$wertId.'\').value);">';
		$returnString .= '</div>';

		return $returnString;
	}

	/**
	 * Entfernt einen Parameter aus einer Adresse.
	 *
	 * @param string $url          Die zu bereinigende Adresse
	 * @param string $strParamName Name des zu entfernenden Parameters
	 *
	 * @return string Die Adresse ohne diesen Parameter; hat die Adresse gar
	 *                keine Parameter, kommt sie unverändert zurück
	 */
	private static function removeUrlParams(string $url, string $strParamName): string
	{
		$arrUrlComponents = parse_url($url);

		if (empty($arrUrlComponents['query']))
		{
			return $url;
		}

		$arrQuery = explode('&amp;', $arrUrlComponents['query']);

		foreach ($arrQuery as $key => $query)
		{
			if (str_contains($query, $strParamName))
			{
				unset($arrQuery[$key]);
			}
		}

		return ($arrUrlComponents['path'] ?? '').'?'.implode('&amp;', $arrQuery);
	}

	/**
	 * Liefert die aktuell gewählte Filterbedingung für den DCA.
	 *
	 * Contao erwartet an dieser Stelle eine Liste aus Paaren von
	 * SQL-Bedingung und einzusetzendem Wert. Ein Sternchen im Suchwert wird
	 * als Platzhalter für beliebige Zeichen verstanden und in das SQL-übliche
	 * Prozentzeichen übersetzt.
	 *
	 * @param string $strTable Name der Tabelle, deren Filterarray gilt
	 *
	 * @return array<array{0:string,1:string}> Die Bedingung mit ihrem Wert,
	 *               oder ein leeres Array, wenn kein gültiger Filter anliegt
	 */
	public static function getCurrentFilterDefinition(string $strTable): array
	{
		$feld = (string) Input::get(self::CSS_FILTER_PARAM_FIELD_NAME);
		$definitionen = $GLOBALS[strtoupper($strTable).'_FILTER'] ?? array();

		// Nur bekannte Felder zulassen — der Wert landet unverändert im SQL
		if ('' === $feld || !isset($definitionen[$feld]))
		{
			return array();
		}

		$value = str_replace('&#35;', '#', rawurldecode((string) Input::get(self::CSS_FILTER_PARAM_VALUE_NAME)));
		$value = str_replace('*', '%', $value);

		if ('' === $value)
		{
			return array();
		}

		return array(array($definitionen[$feld], $value));
	}
}
