<?php

declare(strict_types=1);

/**
 * Linksammlung für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoLinkscollectionBundle\Controller;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\FrontendTemplate;
use Contao\PageModel;
use Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection;
use Schachbulle\ContaoLinkscollectionBundle\Klassen\Protokoll;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * Durchsucht Kategorien und Links und liefert die Treffer als HTML-Schnipsel.
 *
 * Erreichbar über die Route linkscollection_suche (/linkscollection/suche).
 * Das Ergebnis wird vom JavaScript der Suchmaske in die Seite eingesetzt, es
 * ist deshalb ein Ausschnitt und keine vollständige Seite.
 *
 * Bis Version 1.5 lag die Suche in der Datei search.php im Web-Verzeichnis und
 * holte sich die aufrufende Seite aus der Sitzung. Beides ist entfallen: Die
 * Seite kommt jetzt als Parameter, was auch dann stimmt, wenn jemand mehrere
 * Linksammlungen oder mehrere Browserfenster offen hat.
 */
class SucheController
{
	/**
	 * @var ContaoFramework Fährt das Contao-Framework hoch
	 */
	private $framework;

	/**
	 * @var RouterInterface Erzeugt die Adressen der Klickweiterleitung
	 */
	private $router;

	/**
	 * @param ContaoFramework $framework Vom Container gestellt
	 * @param RouterInterface $router    Vom Container gestellt
	 */
	public function __construct(ContaoFramework $framework, RouterInterface $router)
	{
		$this->framework = $framework;
		$this->router = $router;
	}

	/**
	 * Behandelt den Aufruf der Route.
	 *
	 * Erwartete Parameter in der Adresse:
	 *   s      Suchbegriff, mindestens drei Zeichen
	 *   seite  ID der Seite, auf der die Linksammlung eingebunden ist. Sie wird
	 *          gebraucht, um die Treffer zu verlinken. Fehlt sie oder ist sie
	 *          unbekannt, erscheinen die Kategorien ohne Verweis
	 *
	 * @param Request $request Die eingehende Anfrage
	 *
	 * @return Response Die Treffer als HTML-Ausschnitt; bei zu kurzem
	 *                  Suchbegriff ein entsprechender Hinweis
	 */
	public function __invoke(Request $request): Response
	{
		$this->framework->initialize();

		$suchbegriff = trim((string) $request->query->get('s'));

		if (mb_strlen($suchbegriff) < 3)
		{
			Protokoll::schreiben('[Linkscollection] Searching "'.$suchbegriff.'" - to short', __METHOD__, ContaoContext::ERROR);

			return new Response('<p>Suchbegriff zu kurz</p>');
		}

		$objPage = null;
		$seite = (int) $request->query->get('seite');

		if ($seite > 0)
		{
			$objPage = PageModel::findByPk($seite);
		}

		$muster = '%'.$suchbegriff.'%';
		$ausgabe = $this->kategorien($muster, $objPage).$this->links($muster, $objPage);

		return new Response($ausgabe);
	}

	/**
	 * Sucht in den Kategorietiteln und gibt die Treffer aus.
	 *
	 * @param string         $muster  Suchmuster mit Prozentzeichen für die
	 *                                LIKE-Abfrage
	 * @param PageModel|null $objPage Seite der Linksammlung für die Verlinkung
	 *
	 * @return string Überschrift und Trefferliste als HTML
	 */
	private function kategorien(string $muster, ?PageModel $objPage): string
	{
		$objKategorien = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection WHERE published = ? AND title LIKE ? ORDER BY title ASC')
		                                        ->execute(1, $muster);

		if (!$objKategorien->numRows)
		{
			return '<p>Keine Kategorien gefunden.</p>';
		}

		$kategorien = array();
		$class = 'odd';

		while ($objKategorien->next())
		{
			$kategorien[] = array
			(
				'title' => $objKategorien->title,
				'url'   => $objPage ? $objPage->getFrontendUrl('/category/'.$objKategorien->id) : '',
				'class' => $class,
			);
			$class = ('odd' === $class) ? 'even' : 'odd';
		}

		$objTemplate = new FrontendTemplate('mod_linkscollection_kategorierow');
		$objTemplate->kategorien = $kategorien;

		$ueberschrift = 1 === $objKategorien->numRows ? '1 Kategorie gefunden:' : $objKategorien->numRows.' Kategorien gefunden:';

		return $ueberschrift.'<ul>'.$objTemplate->parse().'</ul>';
	}

	/**
	 * Sucht in Titel, URL und Beschreibung der Links und gibt die Treffer aus.
	 *
	 * @param string         $muster  Suchmuster mit Prozentzeichen für die
	 *                                LIKE-Abfrage
	 * @param PageModel|null $objPage Seite der Linksammlung für die Verlinkung
	 *                                der Meldefunktion
	 *
	 * @return string Überschrift und Trefferliste als HTML
	 */
	private function links(string $muster, ?PageModel $objPage): string
	{
		$objLinks = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ? AND (title LIKE ? OR url LIKE ? OR text LIKE ?) ORDER BY hits DESC')
		                                   ->execute(1, $muster, $muster, $muster);

		Protokoll::schreiben('[Linkscollection] Searching - found '.$objLinks->numRows.' row(s)', __METHOD__, ContaoContext::ACCESS);

		if (!$objLinks->numRows)
		{
			return '<p>Keine Links gefunden.</p>';
		}

		$neuAb = time() - ((int) Config::get('linkscollection_new_duration') * 86400);
		$links = array();
		$class = 'odd';

		while ($objLinks->next())
		{
			$links[] = array
			(
				'title'       => $objLinks->title,
				'url'         => $this->router->generate('linkscollection_go', array('id' => (int) $objLinks->id)),
				'icon'        => Linkscollection::getFavicon($objLinks->id),
				'language'    => Linkscollection::getLanguageIcon((string) $objLinks->language),
				'new'         => $objLinks->newWindow,
				'text'        => $objLinks->text,
				'popular'     => $objLinks->popular,
				'hits'        => $objLinks->hits,
				'problem_url' => $objPage ? $objPage->getFrontendUrl('/link/'.$objLinks->id) : '',
				'class'       => $class,
				'newLink'     => ($objLinks->initdate >= $neuAb) ? Linkscollection::NEUICON : '',
				'webarchiv'   => $objLinks->webarchiv
			);
			$class = ('odd' === $class) ? 'even' : 'odd';
		}

		$objTemplate = new FrontendTemplate('mod_linkscollection_linkrow');
		$objTemplate->links = $links;

		$ueberschrift = 1 === $objLinks->numRows ? '1 Link gefunden:' : $objLinks->numRows.' Links gefunden:';

		return $ueberschrift.'<ul>'.$objTemplate->parse().'</ul>';
	}
}
