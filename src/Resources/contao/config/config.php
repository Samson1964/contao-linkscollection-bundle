<?php

declare(strict_types=1);

/**
 * Linksammlung für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

use Contao\ArrayUtil;
use Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection;
use Schachbulle\ContaoLinkscollectionBundle\Module\Linksammlung;

/**
 * Backend-Modul der Linksammlung im Menü "Inhalte" an zweiter Stelle einfügen.
 *
 * Die drei zusätzlichen Schlüssel sind eigene Ansichten des Moduls; Contao
 * ruft sie auf, sobald in der Adresszeile &key=analyse, &key=linklist oder
 * &key=statistik steht. Die Verweise dorthin setzen die DCA-Dateien als
 * globale Operationen.
 */
ArrayUtil::arrayInsert($GLOBALS['BE_MOD']['content'], 1, array
(
	'linkscollection' => array
	(
		'tables'    => array('tl_linkscollection', 'tl_linkscollection_links'),
		'icon'      => 'bundles/contaolinkscollection/images/icon.png',
		'analyse'   => array(Linkscollection::class, 'Linkanalyse'),
		'linklist'  => array(Linkscollection::class, 'GenerateLinklist'),
		'statistik' => array(Linkscollection::class, 'Statistik')
	)
));

/**
 * Frontend-Modul der Linksammlung
 */
$GLOBALS['FE_MOD']['application']['linkscollection'] = Linksammlung::class;

/**
 * -------------------------------------------------------------------------
 * Voreinstellungen
 *
 * Sie gelten, solange unter System -> Einstellungen nichts anderes
 * gespeichert ist.
 * -------------------------------------------------------------------------
 */

$GLOBALS['TL_CONFIG']['linkscollection_new_duration'] = 60;  // 60 Tage gilt ein Link als neu
$GLOBALS['TL_CONFIG']['linkscollection_test_duration'] = 365; // 365 Tage bis zur nächsten Linkprüfung
$GLOBALS['TL_CONFIG']['linkscollection_webarchiv'] = 'https://web.archive.org/web/*/'; // Link zum Webarchiv (Präfix)
$GLOBALS['TL_CONFIG']['linkscollection_maxlinkcheck'] = 5; // So viele Links werden je Seitenaufruf höchstens geprüft

/**
 * Zähler der in diesem Seitenaufruf bereits geprüften Links
 */
$GLOBALS['linkscollection_linkcheck_count'] = 0;

/**
 * Bedingungen des erweiterten Filters in der Linkliste
 */
$GLOBALS['TL_LINKSCOLLECTION_LINKS_FILTER']['title'] = 'id IN (SELECT id FROM tl_linkscollection_links WHERE title LIKE ?)';
$GLOBALS['TL_LINKSCOLLECTION_LINKS_FILTER']['url']   = 'id IN (SELECT id FROM tl_linkscollection_links WHERE url LIKE ?)';
$GLOBALS['TL_LINKSCOLLECTION_LINKS_FILTER']['text']  = 'id IN (SELECT id FROM tl_linkscollection_links WHERE text LIKE ?)';
$GLOBALS['TL_LINKSCOLLECTION_LINKS_FILTER']['cms']   = 'id IN (SELECT id FROM tl_linkscollection_links WHERE cms LIKE ?)';

/**
 * Stile der Backend-Ansichten nur im Backend laden — im Frontend wären sie
 * totes Gewicht auf jeder einzelnen Seite
 */
if (Linkscollection::istBackend())
{
	$GLOBALS['TL_CSS'][] = 'bundles/contaolinkscollection/css/style.css';
	$GLOBALS['TL_CSS'][] = 'bundles/contaolinkscollection/css/be.css';
}
