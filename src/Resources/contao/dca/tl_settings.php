<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2016 Leo Feyer
 *
 * @package   fen
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2013
 */

/**
 * palettes
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{linkscollection_legend:hide},linkscollection_new_duration,linkscollection_test_duration,linkscollection_webarchiv';

/**
 * fields
 */

$GLOBALS['TL_DCA']['tl_settings']['fields']['linkscollection_new_duration'] = array
(
	'label'         => &$GLOBALS['TL_LANG']['tl_settings']['linkscollection_new_duration'],
	'inputType'     => 'text',
	'eval'          => array
	(
		'tl_class'  => 'w50', 
		'rgxp'      => 'numeric'
	)
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['linkscollection_test_duration'] = array
(
	'label'         => &$GLOBALS['TL_LANG']['tl_settings']['linkscollection_test_duration'],
	'inputType'     => 'text',
	'eval'          => array
	(
		'tl_class'  => 'w50', 
		'rgxp'      => 'numeric'
	)
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['linkscollection_webarchiv'] = array
(
	'label'         => &$GLOBALS['TL_LANG']['tl_settings']['linkscollection_webarchiv'],
	'inputType'     => 'text',
	'eval'          => array
	(
		'tl_class'  => 'long', 
	)
);
