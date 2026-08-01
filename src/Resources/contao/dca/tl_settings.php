<?php

declare(strict_types=1);

/**
 * Linksammlung für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

/**
 * Palette der Systemeinstellungen um den eigenen Bereich erweitern.
 *
 * Die Werte landen in system/config/localconfig.php und überschreiben die
 * Voreinstellungen aus der config.php dieser Erweiterung.
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{linkscollection_legend:hide},linkscollection_new_duration,linkscollection_test_duration,linkscollection_webarchiv';

/**
 * Felder
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
