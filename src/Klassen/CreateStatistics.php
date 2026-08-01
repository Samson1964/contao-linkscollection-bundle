<?php

declare(strict_types=1);

/**
 * Linksammlung für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoLinkscollectionBundle\Klassen;

use Contao\Config;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;

/**
 * Zählt die Links je Kategorie und schreibt die Zahlen in tl_linkscollection.
 *
 * Die Kategorieliste im Backend und die Kategorieübersicht im Frontend zeigen
 * pro Kategorie an, wie viele Links darin liegen, wie viele davon neu sind und
 * wie hoch der Anteil erreichbarer Links ist. Diese Werte werden nicht bei
 * jeder Änderung fortgeschrieben, sondern hier in einem Durchgang neu ermittelt.
 */
class CreateStatistics
{
	/**
	 * Berechnet sämtliche Zähler der Kategorien neu.
	 *
	 * Gezählt werden je Kategorie: die Links unmittelbar darin (links_self),
	 * die Links einschließlich aller Unterkategorien (links_all), die in den
	 * letzten Tagen hinzugekommenen (links_new) und der Anteil der Links, die
	 * bei der letzten Prüfung mit Status 200 geantwortet haben und deren
	 * Prüfung nicht älter als ein Jahr ist (links_checkquote).
	 *
	 * Nebenwirkung: schreibt in alle Datensätze von tl_linkscollection und
	 * hinterlässt einen Eintrag im Systemprotokoll.
	 *
	 * @return void
	 */
	public static function run(): void
	{
		// Startzeitpunkt, ab dem ein Link als neu gilt
		$duration = time() - ((int) Config::get('linkscollection_new_duration') * 86400);

		// Unixzeit vor einem Jahr
		$einjahr = time() - 31556926;

		// Alle veröffentlichten Links laden
		$objLinks = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ?')
		                                   ->execute(1);

		$links_all = array();    // Links inklusive Unterkategorien
		$links_self = array();   // Links unmittelbar in der Kategorie
		$links_new = array();    // Neue Links unmittelbar in der Kategorie
		$links_newbie = array(); // Neue Links inklusive Unterkategorien
		$links_check = array();  // Erreichbare Links in der Kategorie

		while ($objLinks->next())
		{
			$pid = (int) $objLinks->pid;

			$links_self[$pid] = ($links_self[$pid] ?? 0) + 1;

			// Nur ein frischer Status 200 zählt als geprüft
			if ($objLinks->statecode == 200 && $objLinks->statedate >= $einjahr)
			{
				$links_check[$pid] = ($links_check[$pid] ?? 0) + 1;
			}

			if ($objLinks->initdate >= $duration)
			{
				$links_new[$pid] = ($links_new[$pid] ?? 0) + 1;
			}

			foreach (self::foundParents($pid) as $cat)
			{
				$links_all[$cat] = ($links_all[$cat] ?? 0) + 1;
			}
		}

		foreach (array_keys($links_new) as $key)
		{
			foreach (self::foundParents($key) as $cat)
			{
				$links_newbie[$cat] = ($links_newbie[$cat] ?? 0) + 1;
			}
		}

		// Anzahl neuer Links überall zurücksetzen, damit auch Kategorien
		// ohne neue Links wieder auf 0 stehen
		Database::getInstance()->prepare('UPDATE tl_linkscollection SET links_new = ?')
		                       ->execute(0);

		foreach ($links_all as $key => $value)
		{
			if (!$key)
			{
				continue; // Die Wurzel (pid 0) ist keine Kategorie
			}

			$geprueft = $links_check[$key] ?? 0;
			$eigene = $links_self[$key] ?? 0;

			// Anteil geprüfter Links; ohne eigene Links gilt die Kategorie als vollständig geprüft
			$prozent = 0 === $eigene ? 100 : (int) (($geprueft / $eigene) * 100);

			$set = array
			(
				'links_all'        => $value,
				'links_self'       => $eigene,
				'links_new'        => $links_newbie[$key] ?? 0,
				'links_checkquote' => $prozent
			);

			Database::getInstance()->prepare('UPDATE tl_linkscollection %s WHERE id = ?')
			                       ->set($set)
			                       ->execute($key);
		}

		Protokoll::schreiben('[Linkscollection] Rebuild categories', __METHOD__, ContaoContext::CRON);
	}

	/**
	 * Ermittelt eine Kategorie und alle ihre übergeordneten Kategorien.
	 *
	 * Damit lässt sich ein Link in jeder Kategorie oberhalb seiner eigenen
	 * mitzählen, ohne den Baum von oben durchlaufen zu müssen.
	 *
	 * @param int $id ID der Kategorie, von der aus nach oben gelaufen wird
	 *
	 * @return array<int> Die übergebene ID gefolgt von den IDs aller
	 *                    Elternkategorien; bricht ab, sobald eine Kategorie
	 *                    nicht veröffentlicht oder nicht vorhanden ist
	 */
	protected static function foundParents(int $id): array
	{
		$katids = array($id);

		while ($id > 0)
		{
			$objTemp = Database::getInstance()->prepare('SELECT pid FROM tl_linkscollection WHERE published = ? AND id = ?')
			                                  ->execute(1, $id);

			if (!$objTemp->numRows)
			{
				break;
			}

			$id = (int) $objTemp->pid;
			$katids[] = $id;
		}

		return $katids;
	}
}
