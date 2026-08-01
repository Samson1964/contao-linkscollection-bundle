<?php

declare(strict_types=1);

/**
 * Linksammlung für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoLinkscollectionBundle\Module;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\Email;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Idna;
use Contao\Input;
use Contao\Module;
use Contao\System;
use Schachbulle\ContaoLinkscollectionBundle\Klassen\Formular;
use Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection;
use Schachbulle\ContaoLinkscollectionBundle\Klassen\Protokoll;

/**
 * Frontend-Modul der Linksammlung.
 *
 * Das Modul bedient alle Ansichten der Linksammlung über eine einzige Seite.
 * Welche Ansicht erscheint, steuern zusätzliche Adressbestandteile, die Contao
 * als Parameter durchreicht:
 *
 *   /category/12  Kategorie 12 mit ihren Unterkategorien und Links
 *   /link/345     Einzelansicht des Links 345 samt Meldeformular
 *   /view/toplinks, /view/newlinks, /view/search, /view/sendlink
 *
 * Ohne Parameter erscheint die oberste Kategorieebene.
 */
class Linksammlung extends Module
{
	/**
	 * @var string Haupttemplate der Kategorieansicht
	 */
	protected $strTemplate = 'mod_linkscollection';

	/**
	 * @var string Template für die Linkliste, wird in mehrere Ansichten eingebettet
	 */
	protected $subTemplate = 'mod_linkscollection_linkrow';

	/**
	 * @var FrontendTemplate Template der Linkliste
	 */
	protected $Subtemplate;

	/**
	 * @var array<int,array<int,string>> Hilfsstruktur für den Kategoriebaum: pid => [id => Titel]
	 */
	protected $baum = array();

	/**
	 * @var int Aktuelle Einrücktiefe beim Aufbau des Kategoriebaums
	 */
	protected $level = 0;

	/**
	 * @var array<int,string> Kategoriebaum als flache Liste: ID => eingerückter Titel
	 */
	protected $tree = array();

	/**
	 * @var int Anzahl aller veröffentlichten Kategorien
	 */
	protected $numberCategories = 0;

	/**
	 * @var int Anzahl aller veröffentlichten Links
	 */
	protected $numberLinks = 0;

	/**
	 * @var int ID der aktuell angezeigten Kategorie, 0 für die oberste Ebene
	 */
	protected $currentCategory = 0;

	/**
	 * @var int Zeitpunkt, ab dem ein Link als neu gilt
	 */
	protected $duration_new = 0;

	/**
	 * @var int Zeitpunkt, ab dem ein Link erneut geprüft wird
	 */
	protected $duration_test = 0;

	/**
	 * Erzeugt das Modul.
	 *
	 * Im Backend erscheint nur ein Platzhalter, weil die Ansichten dort weder
	 * sinnvoll noch gefahrlos sind (das Aufrufen einer Ansicht kann eine
	 * Linkprüfung auslösen). Im Frontend wird der Kategoriebaum aufgebaut und
	 * das Stylesheet der Sprachfähnchen eingebunden.
	 *
	 * @return string Das gerenderte Modul
	 */
	public function generate()
	{
		if (Linkscollection::istBackend())
		{
			$objTemplate = new BackendTemplate('be_linkscollection');

			$objTemplate->wildcard = '### LINKSAMMLUNG ###';
			$objTemplate->title = $this->name;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}

		$this->getCategories(); // Kategoriebaum anlegen und Kategorien/Links zählen
		$this->Subtemplate = new FrontendTemplate($this->subTemplate);

		$stil = Linkscollection::flaggenStil();

		if ('' !== $stil)
		{
			$GLOBALS['TL_CSS'][] = $stil.'|static';
		}

		$this->duration_new = time() - ((int) Config::get('linkscollection_new_duration') * 86400);
		$this->duration_test = time() - ((int) Config::get('linkscollection_test_duration') * 86400);

		return parent::generate(); // Weitermachen mit dem Modul
	}

	/**
	 * Füllt das Template der jeweils angeforderten Ansicht.
	 *
	 * @return void
	 */
	protected function compile()
	{
		global $objPage;

		if (Input::get('view'))
		{
			switch (Input::get('view'))
			{
				case 'toplinks':
					$this->Toplinks();
					break;

				case 'newlinks':
					$this->Newlinks();
					break;

				case 'search':
					$this->Search();
					break;

				case 'sendlink':
					$this->ViewFormularLinkNeu();
					break;
			}

			return;
		}

		if (Input::get('link'))
		{
			$this->Linkinfo();

			return;
		}

		// Kategorie zuweisen
		$this->currentCategory = (int) Input::get('category');

		// Breadcrumb-Navigation erstellen
		$breadcrumb = array();
		$pagetitle = array();

		if ($this->currentCategory) // Nur bei Unterkategorien
		{
			// Infos zur aktuellen Kategorie laden
			$objActual = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection WHERE published = ? AND id = ?')
			                                    ->execute(1, $this->currentCategory);

			if ($objActual->numRows > 0)
			{
				$breadcrumb[] = array
				(
					'title' => $objActual->title,
					'url'   => '',
					'class' => 'last'
				);
				$pagetitle[] = $objActual->title;

				// Navigation über die Elternkategorien vervollständigen
				$pid = (int) $objActual->pid;

				while ($pid > 0)
				{
					$objTemp = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection WHERE published = ? AND id = ?')
					                                  ->execute(1, $pid);

					if (!$objTemp->numRows)
					{
						break;
					}

					$breadcrumb[] = array
					(
						'title' => $objTemp->title,
						'url'   => $objPage->getFrontendUrl('/category/'.$objTemp->id),
						'class' => 'sibling'
					);
					$pagetitle[] = $objTemp->title;
					$pid = (int) $objTemp->pid;
				}

				$breadcrumb[] = array
				(
					'title' => 'Startseite',
					'url'   => $objPage->getFrontendUrl(),
					'class' => 'first sibling'
				);
			}
			else
			{
				// Kategorie nicht gefunden, dann die Startseite der Linksammlung laden
				Controller::redirect($objPage->getFrontendUrl());
			}
		}

		// Seitentitel um die Kategoriekette ergänzen
		if ($pagetitle)
		{
			$objPage->pageTitle = ($objPage->pageTitle ?: $objPage->title).' | '.implode(' | ', array_reverse($pagetitle));
		}

		// Unterkategorien laden
		$objCats = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection WHERE published = ? AND pid = ? ORDER BY title ASC')
		                                  ->execute(1, $this->currentCategory);

		$categories = array();
		$class = 'odd';

		while ($objCats->next())
		{
			$categories[] = array
			(
				'title' => $objCats->title,
				'link'  => $objPage->getFrontendUrl('/category/'.$objCats->id),
				'links' => $objCats->links_all,
				'new'   => $objCats->links_new ? Linkscollection::NEUICON : '',
				'class' => $class
			);
			$class = ('odd' === $class) ? 'even' : 'odd';
		}

		// Links der Kategorie laden
		$objLinks = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ? AND pid = ? ORDER BY popular DESC, webarchiv ASC, title ASC')
		                                   ->execute(1, $this->currentCategory);

		// Template füllen
		$this->Template->menu = $this->Menu();
		$this->Template->breadcrumb = array_reverse($breadcrumb);
		$this->Template->categories = $categories;
		$this->Subtemplate->links = $this->linkListe($objLinks);
		$this->Template->links = $this->Subtemplate->parse();
		$this->Template->counter = array('categories' => $this->numberCategories, 'links' => $this->numberLinks);
	}

	/**
	 * Wandelt ein Abfrageergebnis in die Liste auf, die das Linktemplate erwartet.
	 *
	 * Ausgelagert, weil die Kategorieansicht, die Toplinks und die neuen Links
	 * dieselben Angaben je Link brauchen und sich nur die Abfrage unterscheidet.
	 *
	 * @param \Contao\Database\Result $objLinks Ergebnis einer Abfrage auf
	 *                                          tl_linkscollection_links
	 * @param bool $mitDatum true ergänzt jede Zeile um das Eintragsdatum, das
	 *                       nur die Ansicht "Neue Links" ausgibt
	 *
	 * @return array<int,array<string,mixed>> Die Links in der Reihenfolge der
	 *                                        Abfrage; leer, wenn nichts gefunden wurde
	 */
	protected function linkListe($objLinks, bool $mitDatum = false): array
	{
		global $objPage;

		$links = array();
		$class = 'odd';

		while ($objLinks->next())
		{
			$zeile = array
			(
				'title'       => $objLinks->title,
				'url'         => $this->klickUrl((int) $objLinks->id),
				'icon'        => Linkscollection::getFavicon($objLinks->id),
				'language'    => Linkscollection::getLanguageIcon((string) $objLinks->language),
				'new'         => $objLinks->newWindow,
				'text'        => $objLinks->text,
				'popular'     => $objLinks->popular,
				'hits'        => $objLinks->hits,
				'problem_url' => $objPage->getFrontendUrl('/link/'.$objLinks->id),
				'class'       => $class,
				'newLink'     => ($objLinks->initdate >= $this->duration_new) ? Linkscollection::NEUICON : '',
				'webarchiv'   => $objLinks->webarchiv
			);

			if ($mitDatum)
			{
				$zeile['date'] = date('d.m.Y', (int) $objLinks->initdate);
				$zeile['newLink'] = ''; // In dieser Ansicht ist ohnehin alles neu
			}

			$links[] = $zeile;
			$class = ('odd' === $class) ? 'even' : 'odd';
		}

		return $links;
	}

	/**
	 * Liefert die Adresse, über die ein Link angeklickt wird.
	 *
	 * Der Klick läuft nicht direkt auf das Ziel, sondern über eine eigene
	 * Route. Sie zählt den Zugriff mit und leitet anschließend weiter — bei
	 * offline gegangenen Seiten auf den Schnappschuss im Webarchiv.
	 *
	 * @param int $id Datensatz-ID des Links
	 *
	 * @return string Die erzeugte Adresse
	 */
	protected function klickUrl(int $id): string
	{
		return System::getContainer()->get('router')->generate('linkscollection_go', array('id' => $id));
	}

	/**
	 * Zeigt die 100 meistgeklickten Links.
	 *
	 * @return void
	 */
	protected function Toplinks(): void
	{
		$this->Template = new FrontendTemplate('mod_linkscollection_toplinks');

		$objLinks = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ? ORDER BY hits DESC, title ASC')
		                                   ->limit(100)
		                                   ->execute(1);

		$this->Template->menu = $this->Menu();
		$this->Subtemplate->links = $this->linkListe($objLinks);
		$this->Template->links = $this->Subtemplate->parse();
		$this->Template->counter = array('categories' => $this->numberCategories, 'links' => $this->numberLinks);
	}

	/**
	 * Zeigt die zuletzt aufgenommenen Links.
	 *
	 * Wie viele Tage ein Link als neu gilt, steht in den Einstellungen unter
	 * "Anzahl Tage Neuer Link".
	 *
	 * @return void
	 */
	protected function Newlinks(): void
	{
		$this->Template = new FrontendTemplate('mod_linkscollection_newlinks');

		$objLinks = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ? AND initdate >= ? ORDER BY initdate DESC, title ASC')
		                                   ->execute(1, $this->duration_new);

		$this->Template->menu = $this->Menu();
		$this->Subtemplate->links = $this->linkListe($objLinks, true);
		$this->Template->links = $this->Subtemplate->parse();
		$this->Template->counter = array('categories' => $this->numberCategories, 'links' => $this->numberLinks);
	}

	/**
	 * Zeigt das Formular zum Melden eines neuen Links.
	 *
	 * @return void
	 */
	protected function ViewFormularLinkNeu(): void
	{
		$this->Template = new FrontendTemplate('mod_linkscollection_newlinks');

		$this->Template->menu = $this->Menu();
		$this->Template->form = $this->FormularLinkNeu();
	}

	/**
	 * Zeigt die Suchmaske.
	 *
	 * Die Suche selbst läuft per JavaScript gegen die Route
	 * linkscollection_suche. Damit die Treffer auf die richtige Seite
	 * verlinken können, bekommt das Template die ID der aktuellen Seite mit —
	 * früher lag diese Angabe in der Sitzung, was bei mehreren geöffneten
	 * Linksammlungen zu Verwechslungen führen konnte.
	 *
	 * @return void
	 */
	protected function Search(): void
	{
		global $objPage;

		$this->Template = new FrontendTemplate('mod_linkscollection_search');
		$this->Template->menu = $this->Menu();
		$this->Template->seite = (int) $objPage->id;
		$this->Template->sucheUrl = System::getContainer()->get('router')->generate('linkscollection_suche');
	}

	/**
	 * Zeigt die Einzelansicht eines Links mit dem Formular zur Fehlermeldung.
	 *
	 * @return void
	 */
	protected function Linkinfo(): void
	{
		$this->Template = new FrontendTemplate('mod_linkscollection_link');

		$objLink = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ? AND id = ?')
		                                  ->execute(1, (int) Input::get('link'));

		if ($objLink->numRows)
		{
			$this->Template->id = $objLink->id;
			$this->Template->title = $objLink->title;
			$this->Template->url_raw = $objLink->url;
			$this->Template->url_go = $this->klickUrl((int) $objLink->id);
			$this->Template->newWindow = $objLink->newWindow;
			$this->Template->text = $objLink->text;
			$this->Template->hits = $objLink->hits;
			$this->Template->average = $this->klicksJeTag((int) $objLink->hits, (int) $objLink->initdate);
			$this->Template->popular = $objLink->popular;
			$this->Template->icon = Linkscollection::getFavicon($objLink->id);
			$this->Template->initdate = $objLink->initdate ? date('d.m.Y, H:i', (int) $objLink->initdate) : 'unbekannt';
			$this->Template->tstamp = date('d.m.Y, H:i', (int) $objLink->tstamp);
			$this->Template->statedate = date('d.m.Y, H:i', (int) $objLink->statedate);
			$this->Template->statecode = $objLink->statecode;
			$this->Template->statetext = $objLink->statetext;
			$this->Template->problemdate = $objLink->problemdate ? date('d.m.Y, H:i', (int) $objLink->problemdate) : '';
			$this->Template->problemcount = $objLink->problemcount;
			$this->Template->webarchiv = $objLink->webarchiv;
		}

		$this->Template->menu = $this->Menu();
		$this->Template->counter = array('categories' => $this->numberCategories, 'links' => $this->numberLinks);
		$this->Template->form = $this->FormularLinkFehler($objLink);
	}

	/**
	 * Berechnet die durchschnittlichen Klicks je Tag seit Aufnahme des Links.
	 *
	 * @param int $hits     Anzahl der bisherigen Klicks
	 * @param int $initdate Zeitpunkt der Aufnahme als Unixzeit
	 *
	 * @return string Der Durchschnitt mit einer Nachkommastelle und Komma als
	 *                Trennzeichen; "0" bei noch nicht geklickten Links oder
	 *                wenn kein Aufnahmedatum hinterlegt ist
	 */
	protected function klicksJeTag(int $hits, int $initdate): string
	{
		$tage = ($initdate > 0) ? (time() - $initdate) / 86400 : 0;

		if (0 === $hits || $tage <= 0)
		{
			return '0';
		}

		return str_replace('.', ',', sprintf('%01.1f', $hits / $tage));
	}

	/**
	 * Baut das Menü, das in jeder Ansicht über dem Inhalt steht.
	 *
	 * @return array<int,array{title:string,link:string}> Die Menüeinträge in
	 *                                                    Anzeigereihenfolge
	 */
	protected function Menu(): array
	{
		global $objPage;

		return array
		(
			array
			(
				'title' => 'Startseite',
				'link'  => $objPage->getFrontendUrl(),
			),
			array
			(
				'title' => 'Toplinks',
				'link'  => $objPage->getFrontendUrl('/view/toplinks'),
			),
			array
			(
				'title' => 'Neue Links',
				'link'  => $objPage->getFrontendUrl('/view/newlinks'),
			),
			array
			(
				'title' => 'Suchen',
				'link'  => $objPage->getFrontendUrl('/view/search'),
			),
			array
			(
				'title' => 'Neuen Link melden',
				'link'  => $objPage->getFrontendUrl('/view/sendlink/category/'.(int) Input::get('category')),
			),
		);
	}

	/**
	 * Lädt alle veröffentlichten Kategorien und zählt Kategorien und Links.
	 *
	 * Das Ergebnis landet in $this->tree (Auswahlliste für das Meldeformular)
	 * sowie in $this->numberCategories und $this->numberLinks (Fußzeile).
	 *
	 * @return void
	 */
	protected function getCategories(): void
	{
		$objCats = Database::getInstance()->prepare('SELECT id,pid,title FROM tl_linkscollection WHERE published = ? ORDER BY title ASC')
		                                  ->execute(1);

		while ($objCats->next())
		{
			$this->baum[(int) $objCats->pid][(int) $objCats->id] = $objCats->title;
		}

		$this->getTree(0); // Baum $this->tree anlegen

		$objLinks = Database::getInstance()->prepare('SELECT id FROM tl_linkscollection_links WHERE published = ?')
		                                   ->execute(1);

		$this->numberCategories = (int) $objCats->numRows;
		$this->numberLinks = (int) $objLinks->numRows;
	}

	/**
	 * Baut den Kategoriebaum ab einer Kategorie rekursiv als flache Liste auf.
	 *
	 * Die Einrückung wird über vorangestellte Striche dargestellt, weil die
	 * Liste in einem Auswahlfeld landet und dort keine Verschachtelung möglich ist.
	 *
	 * @param int $id ID der Kategorie, ab der aufgebaut wird; 0 für die Wurzel
	 *
	 * @return void
	 */
	public function getTree(int $id): void
	{
		if (!isset($this->baum[$id]))
		{
			return;
		}

		foreach ($this->baum[$id] as $key => $val)
		{
			$this->tree[$key] = str_repeat('- ', $this->level).$val;
			++$this->level;
			$this->getTree($key);
			--$this->level;
		}
	}

	/**
	 * Speichert einen von einem Besucher gemeldeten neuen Link.
	 *
	 * Der Link wird unveröffentlicht angelegt und muss im Backend freigegeben
	 * werden. Zusätzlich geht eine E-Mail an die im Seitenstartpunkt
	 * hinterlegte Administratoradresse.
	 *
	 * @param array $data Die geprüften Formulardaten mit den Schlüsseln
	 *                    category, title, url, description, name und email
	 *
	 * @return void
	 */
	protected function saveNewlink(array $data): void
	{
		$zeit = time();

		$set = array
		(
			'pid'       => (int) $data['category'],
			'tstamp'    => $zeit,
			'initdate'  => $zeit,
			'title'     => $data['title'],
			'url'       => $data['url'],
			'text'      => $data['description'],
			'newWindow' => 1,
			'name'      => $data['name'],
			'email'     => $data['email']
		);

		$objLink = Database::getInstance()->prepare('INSERT INTO tl_linkscollection_links %s')
		                                  ->set($set)
		                                  ->execute();

		Protokoll::schreiben('[Linkscollection] New Link submitted: '.$data['title'].' ('.$data['url'].')', __METHOD__, ContaoContext::FORMS);

		// Kommentar zusammenbauen
		$strComment = 'Titel: '.$data['title']."\n";
		$strComment .= 'URL: '.$data['url']."\n";
		$strComment .= 'Kategorie: '.$data['category']."\n";
		$strComment .= 'Beschreibung: '.$data['description'];

		$this->adminMail(
			sprintf($GLOBALS['TL_LANG']['MSC']['linkscollection_subject'], Idna::decode(Environment::get('host'))),
			sprintf(
				$GLOBALS['TL_LANG']['MSC']['linkscollection_message'],
				$data['name'].' ('.$data['email'].')',
				$strComment,
				Idna::decode(Environment::get('base')).Environment::get('request'),
				Linkscollection::backendLink(array('table' => 'tl_linkscollection_links', 'act' => 'edit', 'id' => $objLink->insertId), false)
			)
		);
	}

	/**
	 * Speichert die Meldung eines Besuchers zu einem fehlerhaften Link.
	 *
	 * Die Meldung wird als HTML-Block dem Feld "Probleme und Änderungswünsche"
	 * vorangestellt, sodass die jüngste Meldung im Backend oben steht. Der
	 * Link selbst bleibt unverändert; über die Korrektur entscheidet die
	 * Redaktion.
	 *
	 * @param array $data Die geprüften Formulardaten
	 *
	 * @return void
	 */
	protected function saveProblemlink(array $data): void
	{
		$zeit = time();

		// Meldung für die E-Mail zusammenbauen
		$content = 'Titel: '.$data['title']."\n";
		$content .= 'URL: '.$data['url']."\n";
		$content .= 'Fehler: '.$data['error']."\n\n";
		$content .= 'Name: '.$data['name']."\n";
		$content .= 'E-Mail: '.$data['email']."\n\n";
		$content .= 'Neuer Titel: '.$data['new_title']."\n";
		$content .= 'Neue URL: '.$data['new_url']."\n\n";
		$content .= 'Neue Beschreibung: '.$data['new_description']."\n";
		$content .= 'Kommentar: '.$data['comment']."\n\n";
		$content .= 'Gemeldet am '.date('d.m.Y, H:i:s', $zeit);

		$objLink = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE id = ?')
		                                  ->execute((int) $data['id']);

		if ($objLink->numRows)
		{
			$anzahl = (int) $objLink->problemcount + 1;

			// Meldung als HTML in die Datenbank schreiben
			$problem = '<h2>Meldung Nr. '.$anzahl.'</h2>';
			$problem .= '<p><b>Gemeldet am '.date('d.m.Y, H:i', $zeit).'</b></p>';
			$problem .= '<ul>';
			$problem .= '<li><b>Titel:</b> '.$data['title'].'</li>';
			$problem .= '<li><b>URL:</b> '.$data['url'].'</li>';
			$problem .= '<li><b>Fehler:</b> '.$data['error'].'</li>';
			$problem .= '<li><b>Name:</b> '.$data['name'].'</li>';
			$problem .= '<li><b>E-Mail:</b> '.$data['email'].'</li>';
			$problem .= '<li><b>Neuer Titel:</b> '.$data['new_title'].'</li>';
			$problem .= '<li><b>Neue URL:</b> '.$data['new_url'].'</li>';
			$problem .= '<li><b>Neue Beschreibung:</b> '.$data['new_description'].'</li>';
			$problem .= '<li><b>Kommentar:</b> '.$data['comment'].'</li>';
			$problem .= '</ul>';
			$problem .= $objLink->problem; // Vorherige Meldungen anhängen

			$set = array
			(
				'problem'      => $problem,
				'problemdate'  => $zeit,
				'problemcount' => $anzahl
			);

			Database::getInstance()->prepare('UPDATE tl_linkscollection_links %s WHERE id = ?')
			                       ->set($set)
			                       ->execute((int) $data['id']);
		}

		Protokoll::schreiben('[Linkscollection] Error Link ID '.$data['id'].' submitted: '.$data['title'].' ('.$data['url'].')', __METHOD__, ContaoContext::FORMS);

		$this->adminMail(
			sprintf($GLOBALS['TL_LANG']['MSC']['linkscollection_error_subject'], Idna::decode(Environment::get('host'))),
			sprintf(
				$GLOBALS['TL_LANG']['MSC']['linkscollection_error_message'],
				$data['name'].' ('.$data['email'].')',
				$content,
				Idna::decode(Environment::get('base')).Environment::get('request'),
				Linkscollection::backendLink(array('table' => 'tl_linkscollection_links', 'act' => 'edit', 'id' => (int) $data['id']), false)
			)
		);
	}

	/**
	 * Verschickt eine Nachricht an die Administratoradresse der Webseite.
	 *
	 * Absender und Empfänger sind dieselbe Adresse — sie stammt aus dem
	 * Seitenstartpunkt beziehungsweise aus den Systemeinstellungen. Ist keine
	 * hinterlegt, unterbleibt der Versand stillschweigend, damit ein
	 * eingereichter Link trotzdem gespeichert bleibt.
	 *
	 * @param string $betreff Betreffzeile
	 * @param string $text    Nachrichtentext als reiner Text
	 *
	 * @return void
	 */
	protected function adminMail(string $betreff, string $text): void
	{
		$adresse = $GLOBALS['TL_ADMIN_EMAIL'] ?? Config::get('adminEmail');
		$name = $GLOBALS['TL_ADMIN_NAME'] ?? '';

		if (!$adresse)
		{
			return;
		}

		$objEmail = new Email();
		$objEmail->from = $adresse;
		$objEmail->fromName = $name;
		$objEmail->subject = $betreff;
		$objEmail->text = $text;
		$objEmail->sendTo($name ? $name.' <'.$adresse.'>' : $adresse);
	}

	/**
	 * Baut das Formular zum Melden eines neuen Links und wertet es aus.
	 *
	 * Nach erfolgreicher Absendung wird die Seite neu geladen, damit ein
	 * Aktualisieren im Browser den Link nicht ein zweites Mal einträgt.
	 *
	 * @return string Das Formular als HTML
	 */
	protected function FormularLinkNeu(): string
	{
		$objForm = new Formular('linkform');

		$objForm->feld('name', array(
			'label'     => 'Vor- und Nachname',
			'inputType' => 'text',
			'eval'      => array('mandatory' => true, 'class' => 'form-control')
		));
		$objForm->feld('email', array(
			'label'     => 'E-Mail',
			'inputType' => 'text',
			'eval'      => array('mandatory' => true, 'rgxp' => 'email', 'class' => 'form-control')
		));
		$objForm->feld('title', array(
			'label'     => 'Titel des Links',
			'inputType' => 'text',
			'eval'      => array('mandatory' => true, 'class' => 'form-control')
		));
		$objForm->feld('url', array(
			'label'     => 'URL des Links',
			'inputType' => 'text',
			'eval'      => array('mandatory' => true, 'rgxp' => 'url', 'class' => 'form-control')
		));
		$objForm->feld('category', array(
			'label'     => 'Kategorie des Links',
			'inputType' => 'select',
			'default'   => (int) Input::get('category'),
			'options'   => array_keys($this->tree),
			'reference' => $this->tree,
			'eval'      => array('mandatory' => true, 'class' => 'form-control')
		));
		$objForm->feld('description', array(
			'label'     => 'Beschreibung des Links',
			'inputType' => 'textarea',
			'eval'      => array('mandatory' => false, 'class' => 'form-control')
		));
		$objForm->feld('captcha', array(
			'label'     => 'Sicherheitsfrage',
			'inputType' => 'captcha',
			'eval'      => array('mandatory' => true)
		));
		$objForm->feld('submit', array(
			'label'     => 'Absenden',
			'inputType' => 'submit',
			'eval'      => array('class' => 'btn btn-primary')
		));

		if ($objForm->pruefen())
		{
			$this->saveNewlink($objForm->werte());

			Controller::reload();
		}

		return $objForm->erzeugen();
	}

	/**
	 * Baut das Formular zum Melden eines fehlerhaften Links und wertet es aus.
	 *
	 * @param \Contao\Database\Result $object Datensatz des betroffenen Links;
	 *                                        Titel und URL werden als
	 *                                        versteckte Felder mitgeschickt,
	 *                                        damit sie in der Meldung stehen
	 *
	 * @return string Das Formular als HTML, oder ein leerer String, wenn es
	 *                den Link gar nicht gibt
	 */
	protected function FormularLinkFehler($object): string
	{
		if (!$object->numRows)
		{
			return '';
		}

		$objForm = new Formular('linkform');

		$objForm->feld('id', array(
			'default'   => $object->id,
			'inputType' => 'hidden'
		));
		$objForm->feld('title', array(
			'default'   => $object->title,
			'inputType' => 'hidden'
		));
		$objForm->feld('url', array(
			'default'   => $object->url,
			'inputType' => 'hidden'
		));
		$objForm->feld('name', array(
			'label'     => 'Vor- und Nachname',
			'inputType' => 'text',
			'eval'      => array('mandatory' => true, 'class' => 'form-control')
		));
		$objForm->feld('email', array(
			'label'     => 'E-Mail',
			'inputType' => 'text',
			'eval'      => array('mandatory' => true, 'rgxp' => 'email', 'class' => 'form-control')
		));
		$objForm->feld('new_title', array(
			'label'     => 'Neuer Titel',
			'inputType' => 'text',
			'eval'      => array('mandatory' => false, 'class' => 'form-control')
		));
		$objForm->feld('new_url', array(
			'label'     => 'Neue URL',
			'inputType' => 'text',
			'eval'      => array('mandatory' => false, 'rgxp' => 'url', 'class' => 'form-control')
		));
		$objForm->feld('new_description', array(
			'label'     => 'Neue Detailbeschreibung',
			'inputType' => 'textarea',
			'eval'      => array('mandatory' => false, 'class' => 'form-control')
		));
		$objForm->feld('error', array(
			'label'     => 'Fehler',
			'inputType' => 'select',
			'options'   => $GLOBALS['TL_LANG']['linkscollection']['errors'] ?? array(),
			'eval'      => array('mandatory' => false, 'class' => 'form-control')
		));
		$objForm->feld('comment', array(
			'label'     => 'Kommentar',
			'inputType' => 'textarea',
			'eval'      => array('mandatory' => true, 'class' => 'form-control')
		));
		$objForm->feld('captcha', array(
			'label'     => 'Sicherheitsfrage',
			'inputType' => 'captcha',
			'eval'      => array('mandatory' => true)
		));
		$objForm->feld('submit', array(
			'label'     => 'Absenden',
			'inputType' => 'submit',
			'eval'      => array('class' => 'btn btn-primary')
		));

		if ($objForm->pruefen())
		{
			$this->saveProblemlink($objForm->werte());

			Controller::reload();
		}

		return $objForm->erzeugen();
	}
}
