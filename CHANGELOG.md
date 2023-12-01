# Linksammlung

## Version 1.4.0 (2023-12-01)

* Add: Freigabe für PHP 8

## Version 1.3.1 (2023-11-13)

* Fix: Linkscollection-Klasse -> ini_set mbstring.regex_retry_limit hochgesetzt auf 10 Millionen

## Version 1.3.0 (2022-03-09)

* Fix: Menü fehlt auf der Suchseite
* Add: Abhängigkeit components/flag-icon-css für die Anzeige der Länderflaggen
* Delete: public/images/flags

## Version 1.2.0 (2021-10-04)

* Fix: Bei Formular neuen Link melden wird die aktuelle Kategorie nicht angezeigt -> falsche Variable und URL-Parameter category fehlte
* Add: Suche nach Kategorien
* Fix: Kosmetische Verbesserungen in der Suche

## Version 1.1.15 (2021-09-30)

* Fix: tl_linkscollection_links.statecode - 1048 Column 'statecode' cannot be null
* Fix: tl_linkscollection_links.statetext - 1048 Column 'statetext' cannot be null
* Fix: tl_linkscollection_links.cms - 1048 Column 'cms' cannot be null

## Version 1.1.14 (2021-09-30)

* Fix: An exception occurred while executing 'UPDATE tl_linkscollection_links SET `language`='de-de'' - 1406 Data too long for column 'language' at row 1"

## Version 1.1.13 (2021-09-10)

* Fix: 1048 Column 'links_new' cannot be null bei UPDATE tl_linkscollection

## Version 1.1.12 (2021-07-15)

* Fix: Übersetzungen Headerlinks fehlen in den BE-Kategorien -> tl_linkscollection_links
* Fix: Link bearbeiten: Abschnitt Meldungen hat Textüberlagerungen
* Fix: A non-numeric value encountered in public/go.php on line 30 -> "+ 0" durch intval-Funktion ersetzt

## Version 1.1.11 (2021-01-13)

* Add: rel=nofollow bei ausgehenden Links

## Version 1.1.10 (2020-06-11)

* Fix: Datum "Eingetragen am" wurde bei 0 als 01.01.1970 ausgegeben
* Fix: mb_eregi_replace() expects parameter 3 to be string, null given (Klassen\Linkscollection.php)

## Version 1.1.9 (2020-05-02)

* Fix: Im Formular Link-Bearbeitung wurden in der Infobox Zeichen nicht angezeigt
* Fix: CSS in den Templates über TL_CSS eingebunden
* Fix: Backend-CSS wieder in config.php geschoben
* Fix: Linkliste im Backend war auf 18px Höhe eingestellt
* Fix: Abhängigkeit paquettg/php-html-parser auf >=2.2 geändert

## Version 1.1.8 (2020-04-28)

* Add: rebuild.php in public wiederhergestellt

## Version 1.1.7 (2020-04-25)

* Fix: CSS von TL_CSS nach lokal verschoben

## Version 1.1.6 (2020-03-23)

* Fix: Abhängigkeit paquettg/php-html-parser von ^2.0 auf ^2 geändert

## Version 1.1.5 (2019-10-10)

* Fix: favicon-Verzeichnis wurde nicht angelegt, wenn noch nicht vorhanden

## Version 1.1.4 (2019-10-01)

* Fix: Bundle hat FE_MOD-Array "applications" überschrieben

## Version 1.1.3 (2019-09-30)

* Fix: Abhängigkeit Symfony entfernt

## Version 1.1.2 (2019-09-27)

* Fix: Abhängigkeit core-bundle auf ^4 geändert

## Version 1.1.1 (2019-08-27)

* Fix: Toplinks hatten noch alte URL

## Version 1.1.0 (2019-08-17)

* Neu: Suche nach CMS in der Linkliste
* Neu: Linkliste jetzt mit den gleichen Infos wie die Links in der normalen Kategorieliste
* Neu: Umstellung der Formulare auf Haste
* Neu: DOM-Klasse durch externes Bundle ersetzt
* Fix: Formulare für Linkmeldungen
* Fix: Suchfunktion
* Fix: Fehler bei nicht vorhandenen Kategorien
* Fix: Verschiedene Korrekturen im Backend

## Version 1.0.0 (2019-08-14) - 1.0.7 (2019-08-15)

* Initialversion als Bundle für Contao 4 - in dieser Version noch ohne Code

## Version 0.5.1 (2019-08-14)

* Add: Webarchiv-Links werden jetzt am Schluß im Frontend angezeigt
* Fix: CMS-Erkennung wiederhergestellt (22.07.2019)

## Version 0.5.0 (2019-05-15)

* Neu: Anzeige der geprüften Links einer Kategorie im BE
* Fix: Anpassung auf Contao 4

## Version 0.4.0 (2017-08-20)

* Fix: Bei neuen Links im FE, wurde der erste Link nicht angezeigt
* Fix: initdate bei neuen Links im BE wurde nicht erstellt
* Neu: Zurück-Button in Prüfbericht ergänzt + Übersetzungen Bearbeitungsbuttons
* Neu: Zurück-Button in Linkliste ergänzt + Übersetzungen Bearbeitungsbuttons
* Fix: In Linkliste wurde der erste Suchtreffer nicht angezeigt
* Neu: Formfield-Hook ergänzt (noch im Aufbau)
 
## Version 0.3.0 (2016-03-14)

* Add: Frontendsuche

## Version 0.2.0 (2016-03-14)

* Add: Neue Sprache

## Version 0.1.0 (2016-03-13)

* Add: Webarchiv-Link

## Version 0.0.1 (2016-03-12)

* Erste Alphaversion
