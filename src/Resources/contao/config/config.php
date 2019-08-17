<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * @package   bdf
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2014
 */

// Include Simple HTML Dom Parser
//require_once(TL_ROOT . '/system/helper/simple_html_dom.php');

define('DEFAULT_FAVICON', 'bundles/contaolinkscollection/images/favicon.png');
define('NEWICON', '<img src="bundles/contaolinkscollection/images/new.gif">');
define('DATE_NEWLINK', 60480000); // 100 Tage

/**
 * Backend-Module der Linksammlung in das Backend-Menü "Inhalte" an Position 1 einfügen
 */
array_insert($GLOBALS['BE_MOD']['content'], 1, array
(
	'linkscollection' => array
	(
		'tables'         => array('tl_linkscollection', 'tl_linkscollection_links'),
		'icon'           => 'bundles/contaolinkscollection/images/icon.png',
		'analyse'        => array('Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection', 'Linkanalyse'),
		'linklist'       => array('Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection', 'GenerateLinklist'),
		'statistik'      => array('Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection', 'Statistik')
	)
));

/**
 * Frontend-Module der Linksammlung an Position 1 einfügen
 */
$GLOBALS['FE_MOD']['application'] = array
(
	'linkscollection'          => 'Schachbulle\ContaoLinkscollectionBundle\Module\Linksammlung',
);

// Cronjobs
//$GLOBALS['TL_CRON']['daily'][] = array('LinkRebuilder', 'run');

// Standard-CSS einbinden
$GLOBALS['TL_CSS'][] = 'bundles/contaolinkscollection/css/style.css'; 
if(TL_MODE == 'BE') $GLOBALS['TL_CSS'][] = 'bundles/contaolinkscollection/css/be.css'; 

/**
 * -------------------------------------------------------------------------
 * Voreinstellungen
 * -------------------------------------------------------------------------
 */

$GLOBALS['TL_CONFIG']['linkscollection_new_duration'] = 60; // 60 Tage gilt ein Link als neu
$GLOBALS['TL_CONFIG']['linkscollection_test_duration'] = 365; // 365 Tage bis zur nächsten Linkprüfung
$GLOBALS['TL_CONFIG']['linkscollection_webarchiv'] = 'https://web.archive.org/web/*/'; // Link zum Webarchiv (Prefix)
$GLOBALS['TL_CONFIG']['linkscollection_maxlinkcheck'] = 5; // 30 Links werden maximal in einem Durchgang geprüft

$GLOBALS['linkscollection_linkcheck_count'] = 0;

/**
 * Define special style sheet filters
 */
$GLOBALS['TL_LINKSCOLLECTION_LINKS_FILTER']['title'] = 'id IN (SELECT id FROM tl_linkscollection_links WHERE title LIKE ?)';
$GLOBALS['TL_LINKSCOLLECTION_LINKS_FILTER']['url']   = 'id IN (SELECT id FROM tl_linkscollection_links WHERE url LIKE ?)';
$GLOBALS['TL_LINKSCOLLECTION_LINKS_FILTER']['text']  = 'id IN (SELECT id FROM tl_linkscollection_links WHERE text LIKE ?)';
$GLOBALS['TL_LINKSCOLLECTION_LINKS_FILTER']['cms']   = 'id IN (SELECT id FROM tl_linkscollection_links WHERE cms LIKE ?)';

/**
 * Hooks
 */
//$GLOBALS['TL_HOOKS']['loadFormField'][] = array('Schachbulle\Linksammlung\Helper', 'setCategoriesToForm');
