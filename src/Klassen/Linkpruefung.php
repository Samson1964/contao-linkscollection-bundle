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
use Contao\Database;
use Contao\Input;
use Contao\System;

/**
 * Sammelprüfung: prüft auf Zuruf alle Links der aktuell im Backend
 * angezeigten Struktur neu, statt auf die schleichende Prüfung beim
 * Durchblättern der Listen zu warten.
 *
 * Der eigentliche Abruf jedes einzelnen Links läuft im Browser per AJAX gegen
 * die Route linkscollection_pruefen (Controller\PruefenController) — ein Link
 * je Anfrage. Das ist Absicht: Antwortet eine Zielseite nicht, bremst das nur
 * diesen einen Abruf aus, ohne den gesamten Durchlauf durch ein serverseitiges
 * Zeitlimit abzuwürgen. Diese Klasse liefert dem Browser nur die Liste der zu
 * prüfenden Links; das eigentliche Prüfen übernimmt Linkscollection::saveFavicon().
 */
class Linkpruefung
{
	/**
	 * Erzeugt die Ansicht zur Sammelprüfung (Backend, &key=pruefen).
	 *
	 * Welche Links geprüft werden, hängt davon ab, von wo aus der Aufruf kam:
	 * aus der Linkliste einer Kategorie (Parameter table=tl_linkscollection_links
	 * und id=<Kategorie>) nur deren eigene Links, sonst — aus dem Kategoriebaum
	 * — die Links des in der Backend-Sitzung gewählten Knotens einschließlich
	 * aller Unterkategorien, oder ohne gewählten Knoten die gesamte Sammlung.
	 *
	 * @return string Die Ansicht als HTML, oder ein leerer String, wenn die
	 *                Adresse gar nicht die Sammelprüfung anfordert
	 */
	public function Pruefen(): string
	{
		if (Input::get('key') != 'pruefen')
		{
			return '';
		}

		$links = self::sammleLinks();

		$container = System::getContainer();
		$basisUrl = $container->get('router')->generate('linkscollection_pruefen', array('id' => 0));

		// Die Route erwartet eine feste ID im Pfad; der Browser hängt die
		// tatsächliche ID selbst an, deshalb hier die Test-ID wieder abtrennen
		if (str_ends_with($basisUrl, '/0'))
		{
			$basisUrl = substr($basisUrl, 0, -2);
		}

		$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaolinkscollection/js/pruefen.js|static';

		$Template = new BackendTemplate('be_linkpruefung');
		$Template->backlink = Linkscollection::backendLink();
		$Template->titel = $GLOBALS['TL_LANG']['tl_linkscollection']['pruefen'][0] ?? 'Prüfen';
		$Template->anzahl = \count($links);
		$Template->basisUrl = $basisUrl;
		$Template->links = array_map(
			static fn (int $id, string $titel): array => array('id' => $id, 'title' => $titel),
			array_keys($links),
			array_values($links)
		);

		return $Template->parse();
	}

	/**
	 * Ermittelt die Links, die zur aktuell angezeigten Struktur gehören.
	 *
	 * Geprüft werden bewusst auch unveröffentlichte Links: Genau deren Status
	 * zu kennen, bevor man sie veröffentlicht, ist einer der Anwendungsfälle
	 * für die Sammelprüfung.
	 *
	 * @return array<int,string> Datensatz-ID => Titel, sortiert nach Titel
	 */
	private static function sammleLinks(): array
	{
		if ('tl_linkscollection_links' === Input::get('table'))
		{
			$kategorie = (int) Input::get('id');
			$objLinks = Database::getInstance()->prepare('SELECT id, title FROM tl_linkscollection_links WHERE pid = ? ORDER BY title ASC')
			                                   ->execute($kategorie);
		}
		else
		{
			$kategorien = self::strukturKategorien();

			if (!$kategorien)
			{
				return array();
			}

			$platzhalter = implode(',', array_fill(0, \count($kategorien), '?'));
			$objLinks = Database::getInstance()->prepare('SELECT id, title FROM tl_linkscollection_links WHERE pid IN ('.$platzhalter.') ORDER BY title ASC')
			                                   ->execute(...$kategorien);
		}

		$links = array();

		while ($objLinks->next())
		{
			$links[(int) $objLinks->id] = (string) $objLinks->title;
		}

		return $links;
	}

	/**
	 * Ermittelt die Kategorien der aktuell im Kategoriebaum angezeigten Struktur.
	 *
	 * Ist im Baum ein Knoten gewählt (siehe tl_linkscollection::addBreadcrumb),
	 * sind das dieser Knoten und alle seine Unterkategorien. Ohne gewählten
	 * Knoten ist die "angezeigte Struktur" die gesamte Sammlung.
	 *
	 * @return array<int> Kategorie-IDs, unsortiert
	 */
	private static function strukturKategorien(): array
	{
		$sitzung = Linkscollection::backendSitzung();
		$knoten = $sitzung ? (int) $sitzung->get('tl_linkscollection_node') : 0;

		if ($knoten > 0)
		{
			return self::nachkommen($knoten);
		}

		$objCats = Database::getInstance()->prepare('SELECT id FROM tl_linkscollection')
		                                  ->execute();

		$ids = array();

		while ($objCats->next())
		{
			$ids[] = (int) $objCats->id;
		}

		return $ids;
	}

	/**
	 * Liefert eine Kategorie und alle ihre Unterkategorien.
	 *
	 * @param int $id ID der Kategorie, ab der abwärts gesammelt wird
	 *
	 * @return array<int> Die übergebene ID gefolgt von allen Nachkommen, in
	 *                    Fundreihenfolge
	 */
	private static function nachkommen(int $id): array
	{
		$ids = array($id);

		$objKinder = Database::getInstance()->prepare('SELECT id FROM tl_linkscollection WHERE pid = ?')
		                                    ->execute($id);

		while ($objKinder->next())
		{
			$ids = array_merge($ids, self::nachkommen((int) $objKinder->id));
		}

		return $ids;
	}
}
