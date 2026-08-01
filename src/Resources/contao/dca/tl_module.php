<?php

declare(strict_types=1);

/**
 * Linksammlung für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

/**
 * Palette des Frontend-Moduls.
 *
 * Das Feld "guests" (nur Gästen anzeigen) gibt es in Contao 5 nicht mehr,
 * "space" ist schon in Contao 4 entfallen. Deshalb wird die Palette aus den
 * Feldern zusammengesetzt, die die jeweilige Contao-Fassung tatsächlich
 * mitbringt — die DCA-Datei des Kerns ist an dieser Stelle bereits geladen.
 */
$palette = '{title_legend},name,headline,type;{protected_legend:hide},protected;{expert_legend:hide}';

if (isset($GLOBALS['TL_DCA']['tl_module']['fields']['guests']))
{
	$palette .= ',guests';
}

$palette .= ',cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['linkscollection'] = $palette;

unset($palette);
