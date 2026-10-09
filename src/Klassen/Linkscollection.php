<?php

declare(strict_types=1);

/**
 * Linksammlung für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoLinkscollectionBundle\Klassen;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\Controller;
use Contao\Database;
use Contao\Environment;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;

/**
 * Sammelstelle für alles, was Backend-Listen, DCA-Callbacks und das
 * Frontend-Modul gemeinsam brauchen: Favicons, Sprachfähnchen, die Prüfung
 * der hinterlegten URLs und die eigenen Backend-Ansichten.
 */
class Linkscollection
{
	/**
	 * Grafik, die neue Kategorien und Links im Frontend kennzeichnet.
	 *
	 * Stand bis Version 1.5 als Konstante NEWICON in der config.php. Globale
	 * Konstanten sind schlecht auffindbar und lassen sich nicht überschreiben;
	 * als Klassenkonstante ist der Wert dagegen eindeutig zuzuordnen.
	 */
	public const NEUICON = '<img src="bundles/contaolinkscollection/images/new.gif" width="28" height="11" alt="neu">';

	/**
	 * Prüft, ob die aktuelle Anfrage zum Backend gehört.
	 *
	 * Ersetzt die in Contao 5 entfallene Konstante TL_MODE. Ohne Container
	 * oder ohne laufende Anfrage — etwa auf der Kommandozeile — gilt die
	 * Anfrage nicht als Backend-Anfrage.
	 *
	 * @return bool true bei einer Backend-Anfrage
	 */
	public static function istBackend(): bool
	{
		$container = System::getContainer();

		if (null === $container || !$container->has('request_stack'))
		{
			return false;
		}

		$request = $container->get('request_stack')->getCurrentRequest();

		if (null === $request)
		{
			return false;
		}

		return $container->get('contao.routing.scope_matcher')->isBackendRequest($request);
	}

	/**
	 * Liefert den Pfad zum Stylesheet der Sprachfähnchen.
	 *
	 * Die Fähnchen stammen aus dem Composer-Paket components/flag-icon-css.
	 * Dieses Paket ist kein Contao-Bundle, seine Dateien landen deshalb unter
	 * vendor/ und sind von außen nicht erreichbar. Damit der Browser sie laden
	 * kann, wird beim ersten Aufruf eine Verknüpfung im Web-Verzeichnis
	 * angelegt (unter Contao 5 heißt es public, unter Contao 4 web).
	 *
	 * Schlägt das Anlegen fehl — etwa unter Windows oder bei fehlenden
	 * Rechten —, kommt ein leerer String zurück und die Linksammlung
	 * funktioniert ohne Fähnchen weiter. In dem Fall hilft eine von Hand
	 * angelegte Verknüpfung, siehe README.
	 *
	 * @return string Pfad des Stylesheets relativ zum Web-Verzeichnis, oder
	 *                ein leerer String, wenn es nicht erreichbar ist
	 */
	public static function flaggenStil(): string
	{
		$container = System::getContainer();

		if (null === $container)
		{
			return '';
		}

		$projekt = $container->getParameter('kernel.project_dir');
		$quelle = $projekt.'/vendor/components/flag-icon-css';

		if (!is_dir($quelle))
		{
			return '';
		}

		$webVerzeichnis = is_dir($projekt.'/public') ? 'public' : 'web';
		$ziel = $projekt.'/'.$webVerzeichnis.'/bundles/flag-icon-css';

		if (!is_link($ziel) && !is_dir($ziel))
		{
			@symlink($quelle, $ziel);
		}

		if (!is_link($ziel) && !is_dir($ziel))
		{
			return '';
		}

		return 'bundles/flag-icon-css/css/flag-icon.min.css';
	}

	/**
	 * Liefert die aufzurufende URL, bei Bedarf mit dem Webarchiv davor.
	 *
	 * Ist eine Seite dauerhaft offline, wird der Link im Backend als
	 * "URL offline" markiert; Besucher landen dann nicht mehr auf der toten
	 * Adresse, sondern auf dem Schnappschuss bei archive.org.
	 *
	 * @param string $url    Die gespeicherte Ziel-URL des Links
	 * @param mixed  $archiv Feldwert "webarchiv"; alles was PHP als wahr
	 *                       ansieht (also '1') schaltet die Umleitung ein
	 *
	 * @return string Die fertige URL, im Zweifel unverändert die übergebene
	 */
	public static function getWeblink(string $url, $archiv): string
	{
		return $archiv ? (string) Config::get('linkscollection_webarchiv').$url : $url;
	}

	/**
	 * Liefert das Sprachfähnchen zu einem Link als HTML.
	 *
	 * Die Grafik selbst kommt aus dem Paket components/flag-icon-css und wird
	 * über eine CSS-Klasse eingebunden; ohne dieses Stylesheet bleibt an der
	 * Stelle schlicht ein leerer Kasten.
	 *
	 * @param string|null $language Länderkürzel in Kleinbuchstaben, z. B. "de"
	 *
	 * @return string Das <span> mit der Flaggenklasse, oder ein leerer String,
	 *                wenn für den Link keine Sprache hinterlegt ist
	 */
	public static function getLanguageIcon(?string $language): string
	{
		if (!$language)
		{
			return '';
		}

		return '&nbsp;<span class="flag-icon flag-icon-'.$language.'">&nbsp;</span>';
	}

	/**
	 * Liefert den Web-Pfad zum Favicon eines Links.
	 *
	 * Die Favicons liegen als <ID>.<Endung> im öffentlichen Verzeichnis der
	 * Erweiterung; welche Endung es geworden ist, hängt davon ab, was die
	 * fremde Seite ausgeliefert hat. Deshalb wird der Reihe nach gesucht.
	 *
	 * @param int|string $id Datensatz-ID des Links
	 *
	 * @return string Pfad relativ zum Web-Verzeichnis. Wurde nichts gefunden,
	 *                kommt das mitgelieferte Standardicon zurück
	 */
	public static function getFavicon($id): string
	{
		$suffixes = array('ico', 'gif', 'png', 'jpg', 'svg'); // Erlaubte Favicon-Endungen
		$verzeichnis = self::faviconVerzeichnis();

		foreach ($suffixes as $suffix)
		{
			if (file_exists($verzeichnis.'/'.$id.'.'.$suffix))
			{
				return 'bundles/contaolinkscollection/favicons/'.$id.'.'.$suffix;
			}
		}

		return 'bundles/contaolinkscollection/images/favicon.png'; // Standardicon
	}

	/**
	 * Nennt das Verzeichnis im Dateisystem, in dem die Favicons liegen.
	 *
	 * Das ist das Verzeichnis "favicons" innerhalb der öffentlichen Dateien
	 * dieser Erweiterung — also genau der Ort, den Contao beim Einrichten nach
	 * public/bundles/contaolinkscollection verlinkt. Der Pfad wird aus dem
	 * Speicherort dieser Klasse abgeleitet und nicht mehr aus der in Contao 5
	 * entfallenen Konstanten TL_ROOT zusammengesetzt.
	 *
	 * @return string Absoluter Pfad ohne abschließenden Schrägstrich
	 */
	public static function faviconVerzeichnis(): string
	{
		return \dirname(__DIR__).'/Resources/public/favicons';
	}

	/**
	 * Ruft eine URL ab und meldet zurück, wie die Gegenstelle geantwortet hat.
	 *
	 * Ersetzt die Contao-Klasse Request, die es seit Contao 5 nicht mehr gibt.
	 * Der Abruf läuft über cURL, weil der FaviconDownloader dieser Erweiterung
	 * ohnehin cURL benutzt und beide Aufrufe damit dieselben Einstellungen
	 * (Weiterleitungen folgen, Zertifikat nicht prüfen) verwenden.
	 *
	 * @param string $url Die zu prüfende Adresse, inklusive Schema
	 *
	 * @return array{code:int,fehler:string,html:string} Der HTTP-Statuscode
	 *               (0, wenn der Host gar nicht erreichbar war), eine
	 *               Fehlerbeschreibung (leer bei Erfolg) und der Seiteninhalt
	 */
	public static function urlPruefen(string $url): array
	{
		$info = array();
		$inhalt = FaviconDownloader::downloadAs($url, $info);

		$code = (int) ($info['http_code'] ?? 0);
		$fehler = '';

		if (0 !== (int) ($info['curl_errno'] ?? 0))
		{
			$fehler = (string) ($info['curl_error'] ?? 'Fehler beim Abruf');
		}
		elseif ($code >= 400)
		{
			$fehler = 'HTTP '.$code;
		}

		return array
		(
			'code'   => $code,
			'fehler' => $fehler,
			'html'   => \is_string($inhalt) ? $inhalt : ''
		);
	}

	/**
	 * Prüft die URL eines Links, lädt sein Favicon und schreibt das Ergebnis fort.
	 *
	 * Wird sowohl beim Speichern im Backend als auch beim Anzeigen einer
	 * länger nicht geprüften Zeile aufgerufen. Der Aufruf geht ins Internet
	 * und dauert entsprechend; die Aufrufer begrenzen ihn deshalb über die
	 * Einstellung "Anzahl Tage Prüfung".
	 *
	 * Seiteneffekte: schreibt die Favicon-Datei in das Verzeichnis aus
	 * faviconVerzeichnis() und aktualisiert den Datensatz in
	 * tl_linkscollection_links.
	 *
	 * @param array $arrRow Datensatz des Links, mindestens mit den Schlüsseln
	 *                      id, url und webarchiv
	 *
	 * @return array Derselbe Datensatz, ergänzt um statedate, statecode,
	 *               statetext, language und cms — auch dann, wenn die Seite
	 *               nicht erreichbar war
	 */
	public static function saveFavicon(array $arrRow): array
	{
		$url = self::getWeblink((string) ($arrRow['url'] ?? ''), $arrRow['webarchiv'] ?? '');
		$antwort = self::urlPruefen($url);

		$language = '';
		$cms = '';

		if ('' === $antwort['fehler'])
		{
			// Kein Fehler, deshalb Favicon-Link ermitteln
			$favicon = new FaviconDownloader($url);

			if ($favicon->icoExists)
			{
				self::faviconSpeichern((string) $arrRow['id'], (string) $favicon->icoType, (string) $favicon->icoData);
			}

			$language = self::sprachKennung(self::checkLanguage($antwort['html'])); // Sprache des Links ermitteln
			$cms = self::checkCMS($antwort['html']); // CMS des Links ermitteln
		}

		// Datenbank aktualisieren. Der Statustext stammt von der fremden Gegenstelle
		// (cURL-Fehlermeldung) und wird auf die Spaltenbreite gekürzt: Contao läuft im
		// Strict-Mode, ein zu langer Wert wäre ein Datenbankfehler statt einer Kürzung.
		$arrRow['statedate'] = time();
		$arrRow['statecode'] = (string) $antwort['code'];
		$arrRow['statetext'] = mb_substr((string) ($antwort['fehler'] ?: 'OK'), 0, 255);
		$arrRow['language']  = $language;
		$arrRow['cms']       = $cms;

		$set = array
		(
			'statedate' => $arrRow['statedate'],
			'statecode' => $arrRow['statecode'],
			'statetext' => $arrRow['statetext'],
			'language'  => $arrRow['language'],
			'cms'       => $arrRow['cms'],
			'cmsdate'   => $arrRow['statedate']
		);

		Database::getInstance()->prepare('UPDATE tl_linkscollection_links %s WHERE id = ?')
		                       ->set($set)
		                       ->execute($arrRow['id']);

		return $arrRow;
	}

	/**
	 * Legt ein heruntergeladenes Favicon als Datei ab.
	 *
	 * Bricht still ab, wenn sich das Verzeichnis nicht anlegen lässt oder
	 * nicht beschreibbar ist. Das ist Absicht: Ein fehlendes Favicon ist ein
	 * Schönheitsfehler und darf das Speichern eines Links nicht verhindern.
	 *
	 * @param string $id     Datensatz-ID des Links, sie wird zum Dateinamen
	 * @param string $endung Dateiendung ohne Punkt, vom Downloader ermittelt
	 * @param string $daten  Binärinhalt der Icondatei
	 *
	 * @return bool true, wenn die Datei geschrieben wurde
	 */
	protected static function faviconSpeichern(string $id, string $endung, string $daten): bool
	{
		if ('' === $endung || '' === $daten)
		{
			return false;
		}

		$verzeichnis = self::faviconVerzeichnis();

		if (!is_dir($verzeichnis) && !@mkdir($verzeichnis, 0775, true) && !is_dir($verzeichnis))
		{
			return false;
		}

		return false !== @file_put_contents($verzeichnis.'/'.$id.'.'.$endung, $daten);
	}

	/**
	 * Liest die Sprache aus dem HTML einer fremden Seite.
	 *
	 * Ausgewertet werden nacheinander das Meta-Feld Content-Language, das
	 * lang-Attribut des <html>-Tags und das Meta-Feld language. Die Auswertung
	 * arbeitet bewusst mit regulären Ausdrücken statt mit einem HTML-Parser:
	 * Fremde Seiten sind oft fehlerhaft ausgezeichnet, und ein Parser war
	 * dafür in der Vergangenheit sowohl langsamer als auch zerbrechlicher.
	 *
	 * @param mixed $html Quelltext der Seite; alles außer einer Zeichenkette
	 *                    gilt als "nichts gefunden"
	 *
	 * @return string Sprachkennung wie "de" oder "de-DE", sonst ein leerer String
	 */
	public static function checkLanguage($html): string
	{
		if (!\is_string($html))
		{
			return '';
		}

		// 1) meta http-equiv="Content-Language"
		if (preg_match('/<meta\s+[^>]*http-equiv=["\']Content-Language["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i', $html, $m))
		{
			$lang = trim($m[1]);

			if ('' !== $lang)
			{
				// Falls der Wert mehrere Sprachen enthält, nur die erste nehmen
				$parts = preg_split('/[,\s]+/', $lang);

				return $parts[0];
			}
		}

		// 2) <html lang="...">
		if (preg_match('/<html[^>]*\blang=["\']([^"\']+)["\'][^>]*>/i', $html, $m))
		{
			return trim($m[1]);
		}

		// 3) meta name="language" oder name="Languages"
		if (preg_match('/<meta\s+[^>]*name=["\'](?:language|Languages)["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i', $html, $m))
		{
			$lang = trim($m[1]);

			if ('' !== $lang)
			{
				$parts = preg_split('/[,\s]+/', $lang);

				return $parts[0];
			}
		}

		// Kein Sprachhinweis gefunden
		return '';
	}

	/**
	 * Bringt eine Sprachangabe einer fremden Seite in die Form, die das Feld
	 * "language" aufnimmt.
	 *
	 * Fremde Seiten schreiben ihre Sprache beliebig: "de", "de-DE", aber auch
	 * "zh-Hans-CN", "en_US.UTF-8" oder "de-DE-1996". Die Spalte fasst nur fünf
	 * Zeichen, und Contao läuft im Strict-Mode — ein längerer Wert ließ den
	 * Speichervorgang mit "Data too long for column 'language'" scheitern und
	 * damit auch den Aufbau der Backend-Liste, die ihn auslöste.
	 *
	 * Behalten wird, was in die Spalte passt und wie eine Sprachkennung aussieht:
	 * "de" bleibt, "de_de" wird zu "de-DE"; bei längeren Angaben zählt allein der
	 * Sprachteil vorn ("zh-Hans-CN" → "zh").
	 *
	 * @param string $sprache Rohe Angabe, wie checkLanguage() sie liefert
	 *
	 * @return string Kennung mit höchstens fünf Zeichen, oder ein leerer String,
	 *                wenn die Angabe nicht wie eine Sprachkennung aussieht
	 */
	public static function sprachKennung(string $sprache): string
	{
		// Zeichensatzanhang ("en_US.UTF-8") abschneiden, Unterstrich wie Bindestrich behandeln
		$teile = explode('-', strtolower(str_replace('_', '-', trim(explode('.', $sprache)[0]))));

		if (!preg_match('/^[a-z]{2,3}$/', $teile[0]))
		{
			return '';
		}

		// Sprache mit Land, wenn die Angabe genau so aufgebaut ist (de-DE)
		if (2 === \count($teile) && preg_match('/^[a-z]{2}$/', $teile[1]))
		{
			return $teile[0].'-'.strtoupper($teile[1]);
		}

		return $teile[0];
	}

	/**
	 * Ermittelt das Content-Management-System der verlinkten Seite.
	 *
	 * Gesucht wird zuerst im Meta-Feld "generator", danach nach typischen
	 * Spuren im Quelltext (etwa wp-content für WordPress). Das Ergebnis ist
	 * eine Vermutung und dient im Backend nur der Übersicht.
	 *
	 * @param mixed $html Quelltext der Seite
	 *
	 * @return string Name des erkannten Systems, sonst ein leerer String
	 */
	public static function checkCMS($html): string
	{
		if (!\is_string($html))
		{
			return '';
		}

		$lower = strtolower($html);

		// Häufige Generator-/Meta-Tags
		$patterns = array
		(
			'/<meta\s+name=["\']generator["\']\s+content=["\']([^"\']+)["\']/',
			'/<meta\s+content=["\'](?:wordpress|drupal|contao|joomla|magento|prestashop|cyclone|www|x) cms/i',
			'/<meta\s+name=["\']generator["\']\s+content=["\']([^"\']+)["\']/i',
			'/generator.*(wordpress|drupal|contao|joomla|magento|prestashop|woocommerce)/i',
			'/wp-content|wp-admin|wp-includes/i' // Hinweise auf WordPress
		);

		foreach ($patterns as $pat)
		{
			if (preg_match($pat, $lower))
			{
				// Den CMS-Namen aus dem Treffer herauslösen
				if (preg_match('/(wordpress|drupal|joomla|magento|prestashop|typo3|laravel|opencart|shopware|woocommerce)/i', $lower, $m))
				{
					return ucfirst(strtolower($m[1]));
				}
			}
		}

		// Falls kein Meta-Tag gefunden, weitere Heuristik
		if (preg_match('/wp-content|wp-admin|wp-includes/i', $lower))
		{
			return 'WordPress';
		}

		if (preg_match('/joomla/', $lower))
		{
			return 'Joomla';
		}

		if (preg_match('/drupal/', $lower))
		{
			return 'Drupal';
		}

		if (preg_match('/magento|commerce/', $lower))
		{
			return 'Magento';
		}

		return '';
	}

	/**
	 * Baut eine Adresse in das Backend-Modul der Linksammlung.
	 *
	 * Der frühere feste Pfad contao/main.php existiert seit Contao 4 nicht
	 * mehr; die Adresse des Backends kommt deshalb aus dem Router. Das
	 * Anfrage-Token (rt) wird angehängt, weil Contao Backend-Aufrufe sonst
	 * auf die Bestätigungsseite umleitet.
	 *
	 * @param array $parameter Zusätzliche Parameter, z. B. array('table' =>
	 *                         'tl_linkscollection_links', 'act' => 'edit')
	 * @param bool  $maskiert  true maskiert das kaufmännische Und als &amp;,
	 *                         wie es innerhalb von HTML-Attributen nötig ist
	 *
	 * @return string Die fertige Adresse, beginnend mit dem Backend-Pfad
	 */
	public static function backendLink(array $parameter = array(), bool $maskiert = true): string
	{
		$container = System::getContainer();
		$basis = $container->get('router')->generate('contao_backend');

		$parameter = array_merge(array('do' => 'linkscollection'), $parameter);
		$parameter['rt'] = $container->get('contao.csrf.token_manager')->getDefaultTokenValue();

		$abfrage = http_build_query($parameter, '', $maskiert ? '&amp;' : '&');

		return $basis.'?'.$abfrage;
	}

	/**
	 * Liefert eine Kategorie-Breadcrumb als HTML-Kette von Backend-Links.
	 *
	 * Läuft von der übergebenen Kategorie über deren Elternkategorien nach
	 * oben und dreht die Reihenfolge zum Schluss um, sodass die oberste
	 * Kategorie zuerst steht.
	 *
	 * @param int|string $category ID der Kategorie, bei 0 kommt ein leerer
	 *                             String zurück
	 *
	 * @return string Die Kategorien als Links, getrennt durch einen Pfeil
	 */
	public static function Breadcrumb($category): string
	{
		$breadcrumb = array();
		$category = (int) $category;

		while ($category > 0)
		{
			$objTemp = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection WHERE published = ? AND id = ?')
			                                  ->execute(1, $category);

			if (!$objTemp->numRows)
			{
				break;
			}

			$url = self::backendLink(array('table' => 'tl_linkscollection_links', 'id' => $objTemp->id));
			$breadcrumb[] = '<a href="'.$url.'" title="Links der Kategorie '.StringUtil::specialchars($objTemp->title).' bearbeiten">'.$objTemp->title.'</a>';

			$category = (int) $objTemp->pid;
		}

		return implode(' &#10140; ', array_reverse($breadcrumb));
	}

	/**
	 * Erzeugt den Prüfbericht der Linksammlung (Backend, &key=analyse).
	 *
	 * Listet auf, wie viele Kategorien und Links veröffentlicht sind, welche
	 * Links Besucher als fehlerhaft gemeldet haben und welche Links noch nicht
	 * freigeschaltet sind — das sind in der Regel die von Besuchern über das
	 * Frontend-Formular eingereichten.
	 *
	 * @return string Das fertige HTML der Ansicht, oder ein leerer String,
	 *                wenn die Adresse gar nicht den Prüfbericht anfordert
	 */
	public function Linkanalyse(): string
	{
		if (Input::get('key') != 'analyse')
		{
			return '';
		}

		$content = '
		<div id="tl_buttons">
			<a href="'.self::backendLink().'" class="header_back" title="" accesskey="b" onclick="Backend.getScrollOffset()">'.($GLOBALS['TL_LANG']['MSC']['backBT'] ?? 'Zurück').'</a>
		</div>';

		$content .= '<div id="tl_listing" class="tl_listing_container"><ul class="tl_listing">';

		// Veröffentlichte Kategorien/Links laden
		$objCats = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection WHERE published = ?')
		                                  ->execute(1);
		$objLinks = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ?')
		                                   ->execute(1);

		// Ausgabe des 1. Kopfes
		$content .= '
		<li class="tl_linkanalyse_head">
			<div class="tl_left">
				<label>Veröffentlicht:</label>
				<b>'.$objCats->numRows.'</b> Kategorien und <b>'.$objLinks->numRows.'</b> Links
			</div>
			<div class="tl_right">&nbsp;</div>
			<div style="clear:both"></div>
		</li>';

		// Fehlerhafte Links
		$content .= '
		<li class="tl_linkanalyse_top">
			<div class="tl_left">
				Fehlerhafte Links
			</div>
			<div class="tl_right">&nbsp;</div>
			<div style="clear:both"></div>
		</li>';

		while ($objLinks->next())
		{
			if ($objLinks->problemcount > 0)
			{
				$content .= self::analyseZeile($objLinks->row(), date('d.m.Y, H:i', (int) $objLinks->problemdate).'</i> <i>- '.$objLinks->problemcount.' Meldungen');
			}
		}

		// Unveröffentlichte Kategorien/Links laden
		$objCats = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection WHERE published = ?')
		                                  ->execute('');
		$objLinks = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ?')
		                                   ->execute('');

		// Unveröffentlichte Kategorien/Links
		$content .= '
		<li class="tl_linkanalyse_head">
			<div class="tl_left">
				<label>Unveröffentlicht:</label>
				<b>'.$objCats->numRows.'</b> Kategorien und <b>'.$objLinks->numRows.'</b> Links
			</div>
			<div class="tl_right">&nbsp;</div>
			<div style="clear:both"></div>
		</li>';

		$content .= '
		<li class="tl_linkanalyse_top">
			<div class="tl_left">
				Unveröffentlichte Links
			</div>
			<div class="tl_right">&nbsp;</div>
			<div style="clear:both"></div>
		</li>';

		while ($objLinks->next())
		{
			$content .= self::analyseZeile($objLinks->row(), date('d.m.Y H:i', (int) $objLinks->initdate).'</i> <i>von '.$objLinks->name);
		}

		$content .= '</ul></div>';

		return $content;
	}

	/**
	 * Formatiert eine einzelne Zeile des Prüfberichts.
	 *
	 * Ausgelagert, weil der Bericht dieselbe Zeile zweimal braucht — einmal
	 * für gemeldete und einmal für unveröffentlichte Links — und sich nur der
	 * Zusatztext unterscheidet.
	 *
	 * @param array  $row     Datensatz des Links aus tl_linkscollection_links
	 * @param string $zusatz  Bereits fertiges HTML für die zweite Zeile,
	 *                        etwa Meldedatum und Anzahl der Meldungen
	 *
	 * @return string Das <li> der Zeile inklusive der beiden Bearbeiten-Symbole
	 */
	protected static function analyseZeile(array $row, string $zusatz): string
	{
		$titelEdit = str_replace('%s', (string) $row['id'], $GLOBALS['TL_LANG']['tl_linkscollection_list']['edit'] ?? 'Link %s bearbeiten');
		$titelEditHeader = str_replace('%s', (string) $row['pid'], $GLOBALS['TL_LANG']['tl_linkscollection_list']['edit_header'] ?? 'Kategorie %s bearbeiten');

		return '
		<li onmouseout="Theme.hoverDiv(this,0)" onmouseover="Theme.hoverDiv(this,1)" class="tl_linkanalyse_row">
			<div class="tl_left">
				<strong>'.$row['title'].'</strong> ('.$row['url'].')
				<br><i>'.$zusatz.'</i>
			</div>
			<div class="tl_right">
				'.self::bearbeitenLinks((int) $row['id'], (int) $row['pid'], $titelEdit, $titelEditHeader).'
			</div>
			<div style="clear:both"></div>
		</li>';
	}

	/**
	 * Erzeugt die beiden Symbole "Link bearbeiten" und "Kategorie bearbeiten".
	 *
	 * @param int    $id           ID des Links
	 * @param int    $pid          ID der Kategorie, in der der Link liegt
	 * @param string $titel        Titel-Attribut des ersten Symbols
	 * @param string $titelKategorie Titel-Attribut des zweiten Symbols
	 *
	 * @return string HTML mit zwei <a>-Elementen
	 */
	protected static function bearbeitenLinks(int $id, int $pid, string $titel, string $titelKategorie): string
	{
		$urlEdit = self::backendLink(array('table' => 'tl_linkscollection_links', 'act' => 'edit', 'id' => $id, 'ref' => Input::get('ref')));
		$urlKategorie = self::backendLink(array('table' => 'tl_linkscollection_links', 'id' => $pid, 'ref' => Input::get('ref')));

		return '<a class="edit" title="'.StringUtil::specialchars($titel).'" href="'.$urlEdit.'">'.Image::getHtml('edit.svg', 'Link bearbeiten').'</a>'
			.'<a class="editheader" title="'.StringUtil::specialchars($titelKategorie).'" href="'.$urlKategorie.'">'.Image::getHtml('header.svg', 'Links der Kategorie bearbeiten').'</a>';
	}

	/**
	 * Formatiert einen Link als Zeile für die Backend-Listen.
	 *
	 * Nebenwirkung: Ist die letzte Prüfung des Links länger her als in den
	 * Einstellungen hinterlegt, wird die URL hier neu geprüft und das Favicon
	 * nachgeladen. Damit der Aufbau einer Liste nicht in hunderte Aufrufe ins
	 * Internet ausartet, begrenzt die Einstellung "linkscollection_maxlinkcheck"
	 * die Anzahl der Prüfungen je Seitenaufruf.
	 *
	 * @param array $record Datensatz des Links; die Schlüssel statedate,
	 *                      statecode, statetext, cms, language, title, url und
	 *                      text werden ausgewertet
	 * @param bool  $buttons Ohne Funktion, nur aus Gründen der Abwärts-
	 *                       kompatibilität in der Signatur belassen
	 *
	 * @return string Das HTML der Zeile, immer mit abschließendem Zeilenumbruch
	 */
	public static function ViewLinkrow(array $record, bool $buttons = false): string
	{
		$refreshtime = time() - ((int) Config::get('linkscollection_test_duration') * 86400);
		$maxCheck = (int) Config::get('linkscollection_maxlinkcheck');

		if ((int) ($record['statedate'] ?? 0) < $refreshtime && ($GLOBALS['linkscollection_linkcheck_count'] ?? 0) <= $maxCheck)
		{
			$GLOBALS['linkscollection_linkcheck_count'] = ($GLOBALS['linkscollection_linkcheck_count'] ?? 0) + 1;

			// URL neu prüfen und Favicon herunterladen
			$record = self::saveFavicon($record);
		}

		// Letzte Prüfung ausgeben
		$sekunden = time() - (int) ($record['statedate'] ?? 0);

		if ($sekunden < 61)
		{
			$check_time = $sekunden.' Sek.'; // bis 1 Minute
		}
		elseif ($sekunden < 7201)
		{
			$check_time = ceil($sekunden / 60).' Min.'; // bis 2 Stunden
		}
		elseif ($sekunden < 259201)
		{
			$check_time = ceil($sekunden / 3600).' Std.'; // bis 3 Tage
		}
		else
		{
			$check_time = ceil($sekunden / 86400).' Tg.';
		}

		// Nächster Zeitpunkt für eine Prüfung
		$refreshtime_next = (int) ($record['statedate'] ?? 0) + ((int) Config::get('linkscollection_test_duration') * 86400);

		$icon = self::getFavicon($record['id']);
		$language = self::getLanguageIcon((string) ($record['language'] ?? ''));
		$statecode = (int) ($record['statecode'] ?? 0);
		$info = '';

		// Statuscode einfärben. Die Auswertung läuft bewusst über if/elseif:
		// Das frühere switch verglich den Code mit Wahrheitswerten, was seit
		// PHP 8 anders ausgewertet wird ('' == 0 ist dort nicht mehr wahr).
		if (0 === $statecode)
		{
			$info = ' <span style="color:red">invalid host</span>';
			$style = 'background-color:#FF0000; color:white; padding-left:2px; padding-right:2px;';
			$record['statecode'] = '000';
		}
		elseif ($statecode >= 400)
		{
			$info = ' <span style="color:red">not found ('.$record['statetext'].')</span>';
			$style = 'background-color:#FF0000; color:white; padding-left:2px; padding-right:2px;';
		}
		elseif ($statecode >= 300)
		{
			$info = ' <span style="color:blue">redirect ('.$record['statetext'].')</span>';
			$style = 'background-color:#FFFF00; padding-left:2px; padding-right:2px;';
		}
		elseif ($statecode >= 200)
		{
			$style = 'background-color:#00FF00; padding-left:2px; padding-right:2px;';
		}
		else
		{
			$style = '';
		}

		$archivclass = !empty($record['webarchiv']) ? ' webarchiv' : ''; // Webarchiv-Klasse hinzufügen

		$line = '<div class="tl_content_right height18">';
		$line .= '<span style="margin-right:5px; color:#9F5000;" title="Verwendetes CMS">'.($record['cms'] ?? '').'</span>';
		$line .= '<span style="'.$style.' font-weight:bold;">'.$record['statecode'].'</span>';
		$line .= '<span style="font-size:0.6rem; margin-left:3px; display:inline-block; width:60px;" title="Zeitpunkt der letzten Prüfung. Nächste Prüfung: '.date('d.m.Y H:i', $refreshtime_next).'"> vor '.$check_time.'</span>';
		$line .= '</div>';
		$line .= '<div class="favicon-img height18'.$archivclass.'" style="background-image: url('.$icon.');">';
		$line .= '<a href="'.$record['url'].'" target="_blank"><b>'.$record['title'].'</b></a> - '.$language.' '.$record['url'].$info;

		if (!empty($record['text']))
		{
			$line .= '<div class="description">'.$record['text'].'</div>';
		}

		$line .= '</div>'."\n";

		return $line;
	}

	/**
	 * Erzeugt die Gesamtliste aller Links (Backend, &key=linklist).
	 *
	 * Anders als die normale Kategorieansicht zeigt diese Liste alle Links
	 * unabhängig von ihrer Kategorie und bietet eine Volltextsuche über
	 * Titel, URL, Beschreibung und CMS. Die Sucheinstellungen liegen in der
	 * Backend-Sitzung, damit sie einen Seitenwechsel überstehen.
	 *
	 * @return string Das geparste Template be_linklist
	 */
	public function GenerateLinklist(): string
	{
		// Hilfsarrays für Filter/Suche
		$where = array();
		$value = array();

		$query = 'SELECT * FROM tl_linkscollection_links';
		$Template = new BackendTemplate('be_linklist');
		$Template->request = StringUtil::ampersand(Environment::get('request'));
		$Template->links = array();
		$Template->searchClass = '';
		$Template->apply = $GLOBALS['TL_LANG']['MSC']['apply'] ?? 'Anwenden';
		$Template->token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
		$Template->backlink = self::backendLink();

		// Sitzungsdaten des Backends laden
		$sitzung = self::backendSitzung();
		$session = $sitzung ? $sitzung->all() : array();
		$filter = 'tl_linkscollection_list'; // Name des Sitzungseintrags

		// Filter, Suche und Limit setzen
		if (Input::post('FORM_SUBMIT') == 'tl_filters' || Input::post('FORM_SUBMIT') == 'tl_filters_limit')
		{
			// Suchfeld in die Sitzung schreiben, den Suchbegriff vorerst nicht
			$session['search'][$filter]['value'] = '';
			$session['search'][$filter]['field'] = Input::post('tl_field', true);

			// Gültigkeit des regulären Ausdrucks im Suchbegriff prüfen
			if (Input::postRaw('tl_value') != '')
			{
				try
				{
					Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE '.Input::post('tl_field', true).' REGEXP ?')
					                       ->limit(1)
					                       ->execute(Input::postRaw('tl_value'));

					$session['search'][$filter]['value'] = Input::postRaw('tl_value');
				}
				catch (\Exception $e)
				{
					// Ungültiger Ausdruck: Der Suchbegriff bleibt leer
				}
			}

			// Limit prüfen
			$strLimit = Input::post('tl_limit');

			if ($strLimit == 'tl_limit')
			{
				unset($session['filter'][$filter]['limit']);
			}
			elseif ($strLimit == 'all' || preg_match('/^[0-9]+,[0-9]+$/', (string) $strLimit))
			{
				$session['filter'][$filter]['limit'] = $strLimit;
			}

			if ($sitzung)
			{
				$sitzung->replace($session);
			}

			Controller::reload();
		}

		$suchfeld = $session['search'][$filter]['field'] ?? '';
		$suchwert = $session['search'][$filter]['value'] ?? '';

		// Suchbegriff zur Abfrage hinzufügen
		if ('' !== $suchwert && '' !== $suchfeld)
		{
			$where[] = 'CAST('.$suchfeld.' AS CHAR) REGEXP ?';
			$value[] = $suchwert;

			$Template->searchClass = ' active';
		}

		// Suchoptionen
		$fields = array('title', 'url', 'text', 'cms');
		$options = '';

		foreach ($fields as $field)
		{
			$beschriftung = $GLOBALS['TL_LANG']['tl_linkscollection_list'][$field] ?? $field;
			$options .= sprintf(
				'<option value="%s"%s>%s</option>',
				$field,
				($field === $suchfeld) ? ' selected' : '',
				\is_array($beschriftung) ? $beschriftung[0] : $beschriftung
			);
		}

		$Template->searchOptions = $options;
		$Template->keywords = StringUtil::specialchars((string) $suchwert);
		$Template->search = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['search'] ?? 'Suchen');
		$Template->showOnly = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['showOnly'] ?? 'Nur anzeigen');

		if (!empty($where))
		{
			$query .= ' WHERE '.implode(' AND ', $where);
		}

		$query .= ' ORDER BY title ASC';

		// Die Parameter müssen einzeln übergeben werden: Ein Array würde
		// Contao seit Version 4 als ein einziger serialisierter Wert deuten.
		$objLinks = Database::getInstance()->prepare($query)
		                                   ->limit(500)
		                                   ->execute(...$value);

		// In dieser Ansicht keine Links prüfen, das würde 500 Abrufe auslösen
		Config::set('linkscollection_maxlinkcheck', -1);

		$arrLinks = array();

		while ($objLinks->next())
		{
			$item = $objLinks->row();
			$item['icon'] = self::getFavicon($objLinks->id);

			$titelEdit = str_replace('%s', (string) $objLinks->id, $GLOBALS['TL_LANG']['tl_linkscollection_list']['edit'] ?? 'Link %s bearbeiten');
			$titelEditHeader = str_replace('%s', (string) $objLinks->pid, $GLOBALS['TL_LANG']['tl_linkscollection_list']['edit_header'] ?? 'Kategorie %s bearbeiten');

			$header = '<div class="tl_content no_padding even click2edit toggle_select hover-div">';
			$header .= '<div class="tl_content_right height18">';
			$header .= self::bearbeitenLinks((int) $objLinks->id, (int) $objLinks->pid, $titelEdit, $titelEditHeader);
			$header .= '</div>';
			$header .= self::ViewLinkrow($item);
			$header .= '</div>';

			$arrLinks[] = $header;
		}

		$Template->numRows = $objLinks->numRows ? $objLinks->numRows.' Treffer' : 'Nichts gefunden';
		$Template->links = $arrLinks;

		return $Template->parse();
	}

	/**
	 * Liefert den Sitzungsspeicher des Backends.
	 *
	 * Ersetzt die in Contao 5 entfallene Klasse Session. Contao legt die
	 * Backend-Daten in einem eigenen Bereich der Symfony-Sitzung ab; ohne
	 * laufende Anfrage (etwa auf der Kommandozeile) gibt es diesen nicht.
	 *
	 * Öffentlich, weil neben dieser Klasse auch tl_linkscollection (Knoten der
	 * Baumnavigation) und Klassen\Linkpruefung (Struktur des Prüfumfangs) auf
	 * denselben Sitzungsbereich zugreifen müssen.
	 *
	 * @return \Symfony\Component\HttpFoundation\Session\SessionBagInterface|null
	 *         Der Sitzungsbereich, oder null wenn keine Sitzung verfügbar ist
	 */
	public static function backendSitzung()
	{
		$container = System::getContainer();

		if (null === $container || !$container->has('request_stack'))
		{
			return null;
		}

		$request = $container->get('request_stack')->getCurrentRequest();

		if (null === $request || !$request->hasSession())
		{
			return null;
		}

		try
		{
			return $request->getSession()->getBag('contao_backend');
		}
		catch (\Exception $e)
		{
			return null;
		}
	}

	/**
	 * Berechnet die Zähler der Kategorien neu (Backend, &key=statistik).
	 *
	 * Die Kategorien führen mit, wie viele Links sie enthalten und wie viele
	 * davon geprüft sind. Diese Zahlen werden nicht bei jeder Änderung
	 * fortgeschrieben, sondern hier in einem Rutsch neu ermittelt.
	 *
	 * Nebenwirkung: Nach getaner Arbeit wird auf die Startseite des
	 * Backend-Moduls umgeleitet, die Methode kehrt also nicht zurück.
	 *
	 * @return string Immer ein leerer String, wenn die Adresse gar nicht die
	 *                Statistik anfordert
	 */
	public function Statistik(): string
	{
		if (Input::get('key') != 'statistik')
		{
			return '';
		}

		CreateStatistics::run();

		Controller::redirect(self::backendLink(array(), false)); // Startseite der Linksammlung aufrufen
	}
}
