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
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection;
use Schachbulle\ContaoLinkscollectionBundle\Klassen\Protokoll;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Zählt den Klick auf einen Link und leitet zum Ziel weiter.
 *
 * Erreichbar über die Route linkscollection_go (/linkscollection/go/<ID>).
 * Bis Version 1.5 erledigte das die Datei go.php im Web-Verzeichnis, die sich
 * über system/initialize.php selbst in Contao eingeklinkt hat. Diesen Weg gibt
 * es seit Contao 5 nicht mehr, außerdem hat er die Sicherheits- und
 * Zwischenspeicher-Ebenen von Symfony vollständig umgangen.
 */
class WeiterleitungController
{
	/**
	 * @var ContaoFramework Wird gebraucht, um vor dem ersten Datenbankzugriff
	 *                      das Contao-Framework hochzufahren
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
	 * Behandelt den Aufruf der Route.
	 *
	 * Der Zugriffszähler wird nur erhöht, wenn die aufrufende IP-Adresse eine
	 * andere ist als beim letzten Mal. Das ist keine exakte Zählung, hält aber
	 * mehrfaches Klicken derselben Person aus der Statistik heraus.
	 *
	 * Nebenwirkungen: aktualisiert hits, ip und ipdate des Links und schreibt
	 * einen Eintrag in das Systemprotokoll.
	 *
	 * @param Request $request Die eingehende Anfrage; daraus wird die
	 *                         IP-Adresse des Besuchers gelesen
	 * @param int     $id      Datensatz-ID des Links aus der Adresse
	 *
	 * @return Response Eine Weiterleitung auf die Ziel-URL des Links,
	 *                  bei einem offline gegangenen Link auf das Webarchiv
	 *
	 * @throws NotFoundHttpException Wenn es den Link nicht gibt oder er nicht
	 *                               veröffentlicht ist
	 */
	public function __invoke(Request $request, int $id): Response
	{
		$this->framework->initialize();

		$objLink = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ? AND id = ?')
		                                  ->execute(1, $id);

		if (!$objLink->numRows)
		{
			Protokoll::schreiben('[Linkscollection] Forwarding link ID '.$id.' not exist', __METHOD__, ContaoContext::ERROR);

			throw new NotFoundHttpException('Der Link mit der ID '.$id.' existiert nicht.');
		}

		$ip = (string) $request->getClientIp();

		// Nur zählen, wenn zuletzt jemand anderes geklickt hat
		if ($ip !== $objLink->ip)
		{
			$set = array
			(
				'hits'   => (int) $objLink->hits + 1,
				'ip'     => $ip,
				'ipdate' => time(),
			);

			Database::getInstance()->prepare('UPDATE tl_linkscollection_links %s WHERE id = ?')
			                       ->set($set)
			                       ->execute($id);
		}

		$url = Linkscollection::getWeblink((string) $objLink->url, $objLink->webarchiv);

		Protokoll::schreiben('[Linkscollection] Forwarding link ID '.$id.': '.$url, __METHOD__, ContaoContext::ACCESS);

		return new RedirectResponse($url, Response::HTTP_SEE_OTHER);
	}
}
