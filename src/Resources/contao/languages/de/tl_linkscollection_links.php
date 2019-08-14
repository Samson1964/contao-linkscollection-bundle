<?php 

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 */

/**
 * Buttons für Operationen
 */

$GLOBALS['TL_LANG']['tl_linkscollection_links']['new'] = array('Neuer Link', 'Neuen Link erstellen');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['edit'] = array('Link bearbeiten', 'Link %s bearbeiten');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['copy'] = array('Link kopieren', 'Link %s kopieren');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['cut'] = array('Link verschieben', 'Link %s verschieben');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['delete'] = array('Link löschen', 'Link %s löschen');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['toggle'] = array('Link aktivieren/deaktivieren', 'Link %s aktivieren/deaktivieren');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['show'] = array('Linkdetails anzeigen' , 'Details des Link %s anzeigen');

/**
 * Back end modules
 */

$GLOBALS['TL_LANG']['tl_linkscollection_links']['maintitle'] = 'Kategorien';

$GLOBALS['TL_LANG']['tl_linkscollection_links']['infobox_legend'] = 'Information';

$GLOBALS['TL_LANG']['tl_linkscollection_links']['title_legend'] = 'Link-Angaben';
$GLOBALS['TL_LANG']['tl_linkscollection_links']['title'] = array('Titel', 'Titel des Links');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['url'] = array('URL', 'URL des Links');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['language'] = array('Sprache', 'Sprache des Links');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['webarchiv'] = array('URL offline', 'Weiterleitung zu archive.org aktivieren');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['popular'] = array('Populärer Link', 'Link als populär kennzeichnen');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['newWindow'] = array('Neues Fenster', 'Link im neuen Fenster öffnen');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['text'] = array('Beschreibung', 'Linkbeschreibung, HTML erlaubt');

$GLOBALS['TL_LANG']['tl_linkscollection_links']['problem_legend'] = 'Meldungen';
$GLOBALS['TL_LANG']['tl_linkscollection_links']['problem'] = array('Probleme und Änderungswünsche', 'Von Besuchern gemeldete Probleme und Korrekturen');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['problemdate'] = array('Letzte Meldung', 'Datum des letzten gemeldeten Problems oder Korrekturen');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['problemcount'] = array('Anzahl Meldungen', 'Anzahl gemeldeter Probleme und Korrekturen');

$GLOBALS['TL_LANG']['tl_linkscollection_links']['warnings'] = array('Meldungen', 'Meldungen');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['warning_done'] = array('Erledigt', 'Status');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['warning_date'] = array('Datum', 'Datum der Meldung');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['warning_error'] = array('Fehler', 'Fehlertitel');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['warning_name'] = array('Name', 'Name des Meldenden');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['warning_email'] = array('E-Mail', 'E-Mail des Meldenden');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['warning_newurl'] = array('Neue URL', 'Neue URL des Links');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['warning_newtitle'] = array('Neuer Titel', 'Neuer Titel des Links');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['warning_comment'] = array('Kommentar', 'Kommentar');

$GLOBALS['TL_LANG']['tl_linkscollection_links']['name_legend'] = 'Wer hat den Link gemeldet?';
$GLOBALS['TL_LANG']['tl_linkscollection_links']['name'] = array('Name', 'Name des Melders');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['email'] = array('E-Mail', 'E-Mail-Adresse des Melders');

$GLOBALS['TL_LANG']['tl_linkscollection_links']['hits_legend'] = 'Zugriffe';
$GLOBALS['TL_LANG']['tl_linkscollection_links']['hits'] = array('Zugriffe', 'Anzahl der Zugriffe');

$GLOBALS['TL_LANG']['tl_linkscollection_links']['guests_legend'] = 'Letzter Aufruf';
$GLOBALS['TL_LANG']['tl_linkscollection_links']['ip'] = array('Adresse', 'IP des letzten Besuchers');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['ipdate'] = array('Datum', 'Datum des letzten Besuches');

$GLOBALS['TL_LANG']['tl_linkscollection_links']['state_legend'] = 'Erreichbarkeit';
$GLOBALS['TL_LANG']['tl_linkscollection_links']['statecode'] = array('Status', 'Status des Links');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['statetext'] = array('HTTP-Code', 'HTTP-Code der bei der letzten Prüfung zurückgeliefert wurde.');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['statedate'] = array('Datum', 'Status zuletzt aktualisiert');

$GLOBALS['TL_LANG']['tl_linkscollection_links']['protected_legend'] = 'Zugriffsschutz';
$GLOBALS['TL_LANG']['tl_linkscollection_links']['protected'] = array('Link schützen', 'Den Link nur bestimmten Gruppen anzeigen.');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['groups'] = array('Erlaubte Mitgliedergruppen', 'Diese Gruppen können den Link sehen.');

$GLOBALS['TL_LANG']['tl_linkscollection_links']['expert_legend'] = 'Experten-Einstellungen';
$GLOBALS['TL_LANG']['tl_linkscollection_links']['guests'] = array('Nur Gästen anzeigen', 'Den Link verstecken, sobald ein Mitglied angemeldet ist.');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['cssID'] = array('CSS-ID/Klasse', 'Hier können Sie eine ID und beliebig viele Klassen eingeben.');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['space'] = array('Abstand davor und dahinter', 'Hier können Sie den Abstand vor und nach dem Link in Pixeln eingeben. Sie sollten Inline-Styles jedoch nach Möglichkeit vermeiden und den Abstand in einem Stylesheet definieren.');

$GLOBALS['TL_LANG']['tl_linkscollection_links']['published_legend'] = 'Veröffentlichung';
$GLOBALS['TL_LANG']['tl_linkscollection_links']['published'] = array('Link veröffentlichen', 'Den Link auf der Webseite anzeigen.');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['start'] = array('Anzeigen ab', 'Den Link erst ab diesem Tag auf der Webseite anzeigen.');
$GLOBALS['TL_LANG']['tl_linkscollection_links']['stop'] = array('Anzeigen bis', 'Den Link nur bis zu diesem Tag auf der Webseite anzeigen.');

/**
 * Special filter label
 */
$GLOBALS['TL_LANG']['tl_linkscollection_links']['extendedFilter'] = 'Erweiterter Filter: ';

/**
 * Special filter option names
 */
$GLOBALS['TL_LANG']['tl_linkscollection_links']['extendedFilterOption']['title'] = 'Titel';
$GLOBALS['TL_LANG']['tl_linkscollection_links']['extendedFilterOption']['url']  = 'URL';
$GLOBALS['TL_LANG']['tl_linkscollection_links']['extendedFilterOption']['text'] = 'Beschreibung';
