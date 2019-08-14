<?php

/**
 * Contao Open Source CMS
 * 
 * Copyright (C) 2005-2013 Leo Feyer
 * 
 * Core translations are managed using Transifex. To create a new translation
 * or to help to maintain an existing one, please register at transifex.com.
 * 
 * @link http://help.transifex.com/intro/translating.html
 * @link https://www.transifex.com/projects/p/contao/language/de/
 * 
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

$GLOBALS['TL_LANG']['MSC']['linkscollection_name'] = 'Name';
$GLOBALS['TL_LANG']['MSC']['linkscollection_email'] = 'E-Mail (wird nicht veröffentlicht)';
$GLOBALS['TL_LANG']['MSC']['linkscollection_website'] = 'Webseite';
$GLOBALS['TL_LANG']['MSC']['linkscollection_comment'] = 'Link';
$GLOBALS['TL_LANG']['MSC']['linkscollection_notify'] = 'Über neue Links per E-Mail benachrichtigen';
$GLOBALS['TL_LANG']['MSC']['linkscollection_submit'] = 'Link absenden';
$GLOBALS['TL_LANG']['MSC']['linkscollection_by'] = 'Link von';
$GLOBALS['TL_LANG']['MSC']['linkscollection_reply'] = 'Antwort von';
$GLOBALS['TL_LANG']['MSC']['linkscollection_quote'] = '%s schrieb:';
$GLOBALS['TL_LANG']['MSC']['linkscollection_code'] = 'Code:';
$GLOBALS['TL_LANG']['MSC']['linkscollection_subject'] = 'Neuer Link auf %s';
$GLOBALS['TL_LANG']['MSC']['linkscollection_message'] = "%s hat einen neuen Link auf Ihrer Webseite erstellt.\n\n---\n\n%s\n\n---\n\nAnsehen: %s\nBearbeiten: %s\n\nWenn Sie Links moderieren, müssen Sie sich im Backend anmelden und den Link veröffentlichen.";
$GLOBALS['TL_LANG']['MSC']['linkscollection_confirm'] = 'Ihr Link wurde hinzugefügt und wird nach redaktioneller Prüfung veröffentlicht.';
$GLOBALS['TL_LANG']['MSC']['linkscollection_optInConfirm'] = 'Ihr Abonnement wurde aktiviert';
$GLOBALS['TL_LANG']['MSC']['linkscollection_optInCancel'] = 'Ihr Abonnement wurde storniert';
$GLOBALS['TL_LANG']['MSC']['linkscollection_optInSubject'] = 'Ihr Abonnement auf %s';
$GLOBALS['TL_LANG']['MSC']['linkscollection_optInMessage'] = "Hallo %s,\n\nbitte bestätigen Sie, dass Sie über neue Links auf der folgenden Webseite per E-Mail benachrichtigt werden möchten:\n\n%s\n\n---\n\nBestätigen: %s\nWidersprechen: %s\n";
$GLOBALS['TL_LANG']['MSC']['linkscollection_notifySubject'] = 'Neuer Link auf %s';
$GLOBALS['TL_LANG']['MSC']['linkscollection_notifyMessage'] = "Hallo %s,\n\nauf einer Seite, die Sie abonniert haben, wurde ein neuer Link hinzugefügt:\n\n%s\n\n---\n\nKlicken Sie hier, um Ihr Abonnement zu kündigen:\n%s\n";

$GLOBALS['TL_LANG']['MSC']['linkscollection_error_confirm'] = 'Ihre Meldung wurde gesendet und Korrekturen werden nach redaktioneller Prüfung veröffentlicht.';
$GLOBALS['TL_LANG']['MSC']['linkscollection_error_subject'] = 'Linkfehler auf %s';
$GLOBALS['TL_LANG']['MSC']['linkscollection_error_message'] = "%s hat einen fehlerhaften Link auf Ihrer Webseite gefunden.\n\n---\n\n%s\n\n---\n\nAnsehen: %s\nBearbeiten: %s\n\nWenn Sie Links moderieren, müssen Sie sich im Backend anmelden und den Link bearbeiten.";

$GLOBALS['TL_LANG']['linkscollection']['errors'] = array
(
	'Korrektur',
	'Seite antwortet nicht, Zeitüberschreitung',
	'Serverfehler 500',
	'Seite nicht gefunden, Fehler 404',
	'Spam',
	'Sonstiges'
);

$GLOBALS['TL_LANG']['tl_linkscollection_list']['title'] = array('Titel', 'Titel des Links');
$GLOBALS['TL_LANG']['tl_linkscollection_list']['url'] = array('URL', 'URL des Links');
$GLOBALS['TL_LANG']['tl_linkscollection_list']['text'] = array('Beschreibung', 'Linkbeschreibung, HTML erlaubt');

// Übersetzungen für benutzerdefinierte Backend-Listen
$GLOBALS['TL_LANG']['tl_linkscollection_list']['edit'] = 'Link %s bearbeiten';
$GLOBALS['TL_LANG']['tl_linkscollection_list']['edit_header'] = 'Kategorie %s dieses Links bearbeiten';
