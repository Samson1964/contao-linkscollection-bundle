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

define('DEFAULT_FAVICON', 'system/modules/linkscollection/assets/images/favicon.png');
define('NEWICON', '<img src="system/modules/linkscollection/assets/images/new.gif">');
define('DATE_NEWLINK', 60480000); // 100 Tage

/**
 * Backend-Modul BdF anlegen und einfügen
 */

//array_insert($GLOBALS['BE_MOD']['content'], 0, array
//(
//	'linkscollection' => array
//	(
//		'tables'         => array('tl_linkscollection', 'tl_linkscollection_links'),
//		'icon'           => 'system/modules/linkscollection/assets/images/icon.png',
//		'analyse'        => array('Linkscollection', 'Linkanalyse'),
//		'linklist'       => array('Linkscollection', 'Linklist'),
//		'statistik'      => array('Linkscollection', 'Statistik')
//	)
//));

// Linksammlung an das Backend-Menü "Inhalte" anhängen
$GLOBALS['BE_MOD']['content']['linkscollection'] = array
(
	'tables'         => array('tl_linkscollection', 'tl_linkscollection_links'),
	'icon'           => 'system/modules/linkscollection/assets/images/icon.png',
	'analyse'        => array('Linkscollection', 'Linkanalyse'),
	'linklist'       => array('Linkscollection', 'Linklist'),
	'statistik'      => array('Linkscollection', 'Statistik')
);

//
//$GLOBALS['FE_MOD']['linkscollection'] = array
//(
//	//'linkscollection'          => 'Linksammlung',
//);

// Frontend modules
$GLOBALS['FE_MOD']['miscellaneous']['linkscollection'] = 'Schachbulle\ContaoLinkscollectionBundle\Module\LinkscollectionModule'; 

// Cronjobs
//$GLOBALS['TL_CRON']['daily'][] = array('LinkRebuilder', 'run');

// Standard-CSS einbinden
//$GLOBALS['TL_CSS'][] = 'system/modules/linkscollection/assets/css/style.css'; 
//if(TL_MODE == 'BE') $GLOBALS['TL_CSS'][] = 'system/modules/linkscollection/assets/css/be.css'; 

/**
 * -------------------------------------------------------------------------
 * Voreinstellungen
 * -------------------------------------------------------------------------
 */

//$GLOBALS['TL_CONFIG']['linkscollection_new_duration'] = 60; // 60 Tage gilt ein Link als neu
//$GLOBALS['TL_CONFIG']['linkscollection_test_duration'] = 365; // 365 Tage bis zur nächsten Linkprüfung
//$GLOBALS['TL_CONFIG']['linkscollection_webarchiv'] = 'https://web.archive.org/web/*/'; // Link zum Webarchiv (Prefix)

/**
 * Define special style sheet filters
 */
//$GLOBALS['TL_LINKSCOLLECTION_LINKS_FILTER']['title'] = 'id IN (SELECT id FROM tl_linkscollection_links WHERE title LIKE ?)';
//$GLOBALS['TL_LINKSCOLLECTION_LINKS_FILTER']['url']   = 'id IN (SELECT id FROM tl_linkscollection_links WHERE url LIKE ?)';
//$GLOBALS['TL_LINKSCOLLECTION_LINKS_FILTER']['text']  = 'id IN (SELECT id FROM tl_linkscollection_links WHERE text LIKE ?)';

/**
 * Hooks
 */
//$GLOBALS['TL_HOOKS']['loadFormField'][] = array('Schachbulle\Linksammlung\Helper', 'setCategoriesToForm');
