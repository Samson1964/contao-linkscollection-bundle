<?php

declare(strict_types=1);

/**
 * Linksammlung für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoLinkscollectionBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Prüft im Auftrag der Sammelprüfung (Backend, &key=pruefen) genau einen Link.
 *
 * Erreichbar über die Route linkscollection_pruefen
 * (/contao/linkscollection/pruefen/{id}). Der Pfad beginnt mit "/contao/" und
 * liegt damit in der Firewall-Zone, die Contao ohnehin nur angemeldeten
 * Backend-Benutzern öffnet — genau das Muster, das schon
 * IccfImportController im Fernschach-Bundle für einen häppchenweisen
 * Hintergrund-Import verwendet.
 *
 * Bewusst ein Link je Aufruf: Ruft die Sammelprüfung im Browser hunderte Links
 * in einem einzigen Aufruf ab, würde eine einzige nicht antwortende Zielseite
 * den ganzen Durchlauf an ein serverseitiges Zeitlimit laufen lassen. So bleibt
 * jede Anfrage auf die Prüfzeit eines einzelnen Links begrenzt, und der
 * JavaScript-Teil (Resources/public/js/pruefen.js) macht bei jedem Ergebnis —
 * auch einem Fehler — beim nächsten Link weiter.
 */
class PruefenController
{
	/**
	 * @var ContaoFramework Wird gebraucht, um vor dem Datenbankzugriff das
	 *                      Contao-Framework hochzufahren
	 */
	private $framework;

	/**
	 * @param ContaoFramework $framework Vom Container gestellt
	 */
	public function __construct(ContaoFramework $framework)
	{
		$this->framework = $framework;
	}

	/**
	 * Prüft die URL des angegebenen Links neu und liefert das Ergebnis als JSON.
	 *
	 * Setzt keinen erhöhten Zugriffszähler und keine IP-Vermerke — anders als
	 * die Klickweiterleitung ist das hier kein Besucherklick, sondern eine
	 * administrative Prüfung.
	 *
	 * @param int $id Datensatz-ID des zu prüfenden Links
	 *
	 * @return JsonResponse Bei Erfolg: ok, statecode, statetext, language, cms.
	 *                      Existiert der Link nicht (mehr), lediglich ok:false
	 */
	public function __invoke(int $id): JsonResponse
	{
		$this->framework->initialize();

		// Ein hängender Downloadversuch darf die Anfrage nicht an ein zu knappes
		// Standardzeitlimit der Hosting-Umgebung laufen lassen. Schlägt der Aufruf
		// fehl (z. B. weil deaktiviert), läuft die Prüfung mit dem Standardlimit weiter.
		if (\function_exists('set_time_limit'))
		{
			@set_time_limit(90);
		}

		$objLink = Database::getInstance()->prepare('SELECT id, url, webarchiv FROM tl_linkscollection_links WHERE id = ?')
		                                  ->execute($id);

		if (!$objLink->numRows)
		{
			return new JsonResponse(array('ok' => false));
		}

		$arrRow = Linkscollection::saveFavicon($objLink->row());

		return new JsonResponse(array
		(
			'ok'        => true,
			'id'        => $id,
			'statecode' => $arrRow['statecode'],
			'statetext' => $arrRow['statetext'],
			'language'  => $arrRow['language'],
			'cms'       => $arrRow['cms'],
		));
	}
}
