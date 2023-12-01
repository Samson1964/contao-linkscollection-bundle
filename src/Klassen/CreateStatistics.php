<?php

namespace Schachbulle\ContaoLinkscollectionBundle\Klassen;

/**
 * Class CreateStatistics
 *
 */
class CreateStatistics
{

	public static function run()
	{
		// Startzeit für neue Links
		$duration = time() - ($GLOBALS['TL_CONFIG']['linkscollection_new_duration'] * 86400);
		// Unixtime vor einem Jahr:
		$einjahr = time() - 31556926;

		// Anzahl der Links in jede Kategorie eintragen, dazu zuerst alle veröffentlichten Links laden
		$objLinks = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ?')
		                                    ->execute(1);

		// Links in jeweiligen Array-Wert für die Kategorie hochzählen
		$links_all = array();
		$links_self = array();
		$links_new = array();
		$links_newbie = array();
		$links_check = array();

		while($objLinks->next())
		{
			if(!isset($links_self[$objLinks->pid])) $links_self[$objLinks->pid] = 0; // Initialisieren, wenn nicht vorhanden
			$links_self[$objLinks->pid]++; // Link in eigener Kategorie addieren
			// Status der letzten Linkprüfung eintragen - Wenn i.O. (Status 200, nicht älter als 1 Jahr) dann Zähler hochsetzen
			if($objLinks->statecode == 200 && $objLinks->statedate >= $einjahr) 
			{
				if(!isset($links_check[$objLinks->pid])) $links_check[$objLinks->pid] = 0; // Initialisieren, wenn nicht vorhanden
				$links_check[$objLinks->pid]++; // Status des Links addieren
			}

			if($objLinks->initdate >= $duration) $links_new[$objLinks->pid]++; // Neuen Link zählen
			$cats = self::foundParents($objLinks->pid); // Oberkategorien finden
			foreach($cats as $cat)
			{
				if(!isset($links_all[$cat])) $links_all[$cat] = 0; // Initialisieren, wenn nicht vorhanden
				$links_all[$cat]++;
			}
		}

		foreach($links_new as $key => $value)
		{
			$cats = self::foundParents($key); // Oberkategorien finden
			foreach($cats as $cat)
			{
				$links_newbie[$cat]++;
			}
		}

		// Anzahl neue Links nullen
		\Database::getInstance()->prepare('UPDATE tl_linkscollection SET links_new = ?')
		                        ->execute(0);

		// Anzahl in Kategorien eintragen
		foreach($links_all as $key => $value)
		{
			if($key)
			{
				if(!isset($links_check[$key])) $links_check[$key] = 0; // Initialisieren, wenn nicht vorhanden
				if(!isset($links_self[$key])) $links_self[$key] = 0; // Initialisieren, wenn nicht vorhanden
				if(!isset($links_newbie[$key])) $links_newbie[$key] = 0; // Initialisieren, wenn nicht vorhanden
				// Prozent geprüfter Links ermitteln
				$links_check[$key] += 0;
				$links_self[$key] += 0;
				$prozent = $links_self[$key] == 0 ? 100 : sprintf('%d', ($links_check[$key]/$links_self[$key]) * 100);

				$set = array
				(
					'links_all'        => $value,
					'links_self'       => $links_self[$key],
					'links_new'        => $links_newbie[$key] ? $links_newbie[$key] : 0,
					'links_checkquote' => $prozent
				);
				\Database::getInstance()->prepare('UPDATE tl_linkscollection %s WHERE id = ?')
				                        ->set($set)
				                        ->execute($key);
			}
		}

		\System::log('[Linkscollection] Rebuild categories', __CLASS__.'::'.__FUNCTION__, TL_CRON);

	}

	protected static function foundParents($id)
	{
		$katids = array($id);

		do
		{
			if($id)
			{
				$objTemp = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection WHERE published = ? AND id = ?')
				                                   ->execute(1, $id);
				$katids[] = $objTemp->pid;
				$id = $objTemp->pid;
			}
		}
		while($id > 0);

		return $katids;
	}
}
