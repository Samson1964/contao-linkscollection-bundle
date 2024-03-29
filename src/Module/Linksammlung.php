<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @package   Linkscollection
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2016 - 2017
 */
namespace Schachbulle\ContaoLinkscollectionBundle\Module;

class Linksammlung extends \Module
{

	protected $strTemplate = 'mod_linkscollection';
	protected $subTemplate = 'mod_linkscollection_linkrow';

	var $baum = array(); // Hilfsvariable für Baumerstellung
	var $level = 0;

	var $tree = array(); // Kategoriebaum
	var $numberCategories; // Anzahl der Kategorien
	var $numberLinks; // Anzahl der Links
	var $currentCategory; // ID der aktuellen Kategorie

	var $duration_new;
	var $duration_test;

	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_linkscollection');

			$objTemplate->wildcard = '### LINKSAMMLUNG ###';
			$objTemplate->title = $this->name;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}
		else
		{
			// FE-Modus: URL mit allen möglichen Parametern auflösen
			\Input::setGet('category', \Input::get('category')); // ID der Kategorie
			\Input::setGet('view', \Input::get('view')); // Toplinks u.ä. anzeigen
			\Input::setGet('link', \Input::get('link')); // Einzelnen Link anzeigen

			$this->getCategories(); // Kategoriebaum anlegen und Kategorien/Links zählen
			$this->Template = new \FrontendTemplate($this->strTemplate);
			$this->Subtemplate = new \FrontendTemplate($this->subTemplate);

			$GLOBALS['TL_CSS'][] = 'bundles/flag-icon-css/css/flag-icon.min.css|static';
		}

		$this->duration_new = time() - ($GLOBALS['TL_CONFIG']['linkscollection_new_duration'] * 86400);
		$this->duration_test = time() - ($GLOBALS['TL_CONFIG']['linkscollection_test_duration'] * 86400);

		// Symlink für das externe Bundle components/flag-icon-css erstellen, wenn noch nicht vorhanden
		if(!is_link(TL_ROOT.'/web/bundles/flag-icon-css')) symlink(TL_ROOT.'/vendor/components/flag-icon-css/', TL_ROOT.'/web/bundles/flag-icon-css'); // Ziel, Name

		return parent::generate(); // Weitermachen mit dem Modul
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		global $objPage;

		if(\Input::get('view'))
		{
			switch(\Input::get('view'))
			{
				case 'toplinks':
					self::Toplinks();
					break;
				case 'newlinks':
					self::Newlinks();
					break;
				case 'search':
					self::Search();
					break;
				case 'sendlink':
					self::ViewFormularLinkNeu();
					break;
			}
			return;
		}

		if(\Input::get('link'))
		{
			$this->Linkinfo();
			return;
		}

		// Kategorie zuweisen
		$this->currentCategory = (int)\Input::get('category');

		// Breadcrumb-Navigation erstellen
		$breadcrumb = array();
		$pagetitle = array();
		if($this->currentCategory) // Nur bei Unterkategorien
		{
			// Infos zur aktuellen Kategorie laden
			$objActual = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection WHERE published = ? AND id = ?')
			                                     ->execute(1, $this->currentCategory);

			// Kategorie gefunden, jetzt ausgeben
			if($objActual->numRows > 0)
			{
				$breadcrumb[] = array
				(
					'title'                => $objActual->title,
					'url'                  => '',
					'class'                => 'last'
				);
				$pagetitle[] = $objActual->title;

				// Navigation vervollständigen
				$pid = $objActual->pid;
				while($pid > 0)
				{
					$objTemp = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection WHERE published = ? AND id = ?')
					                                   ->execute(1, $pid);
					$breadcrumb[] = array
					(
						'title'            => $objTemp->title,
						'url'              => \Controller::generateFrontendUrl($objPage->row(), '/category/'.$objTemp->id),
						'class'            => 'sibling'
					);
					$pagetitle[] = $objTemp->title;
					$pid = $objTemp->pid;
				}
				$breadcrumb[] = array
				(
					'title'                => 'Startseite',
					'url'                  => \Controller::generateFrontendUrl($objPage->row()),
					'class'                => 'first sibling'
				);
			}
			else
			{
				// Kategorie nicht gefunden, dann Startseite der Linksammlung laden
				$url = \Controller::generateFrontendUrl($objPage->row());
				\Controller::redirect($url);
			}
		}

		// Seitentitel modifizieren
		if($pagetitle) $objPage->pageTitle = ($objPage->pageTitle ?: $objPage->title) . ' | ' . implode(' | ', array_reverse($pagetitle));

		// Unterkategorien laden
		$objCats = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection WHERE published = ? AND pid = ?')
		                                   ->execute(1, $this->currentCategory);

		$categories = array();
		if($objCats->numRows > 1)
		{
			$class = 'odd';
			// Datensätze anzeigen
			while($objCats->next())
			{
				$categories[] = array
				(
					'title'            => $objCats->title,
					'link'             => \Controller::generateFrontendUrl($objPage->row(), '/category/'.$objCats->id),
					'links'            => $objCats->links_all,
					'new'              => ($objCats->links_new) ? NEWICON : '',
					'class'            => $class
				);
				$class = ($class == 'odd') ? 'even' : 'odd';
			}
		}

		// Links laden
		$objLinks = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ? AND pid = ? ORDER BY popular DESC, webarchiv ASC, title ASC')
		                                    ->execute(1, $this->currentCategory);

		$links = array();
		if($objLinks->numRows > 1)
		{
			$class = 'odd';
			// Datensätze anzeigen
			while($objLinks->next())
			{
				$links[] = array
				(
					'title'            => $objLinks->title,
					'url'              => 'bundles/contaolinkscollection/go.php?id='.$objLinks->id,
					'icon'             => \Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection::getFavicon($objLinks->id),
					'language'         => \Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection::getLanguageIcon($objLinks->language),
					'new'              => $objLinks->newWindow,
					'text'             => $objLinks->text,
					'popular'          => $objLinks->popular,
					'hits'             => $objLinks->hits,
					'problem_url'      => \Controller::generateFrontendUrl($objPage->row(), '/link/'.$objLinks->id),
					'class'            => $class,
					'newLink'          => ($objLinks->initdate >= $this->duration_new) ? NEWICON : '',
					'webarchiv'        => $objLinks->webarchiv
				);
				$class = ($class == 'odd') ? 'even' : 'odd';
			}
		}

		// Template füllen
		$this->Template->menu = $this->Menu();
		$this->Template->breadcrumb = array_reverse($breadcrumb);
		$this->Template->categories = $categories;
		$this->Subtemplate->links = $links;
		$this->Template->links = $this->Subtemplate->parse();
		$this->Template->counter = array('categories'=>$this->numberCategories,'links'=>$this->numberLinks);
		//if($this->currentCategory) $this->Template->form = $this->SendlinkForm();
	}

	/**
	 * Generate Toplinks
	 */
	protected function Toplinks()
	{
		global $objPage;

		$this->Template = new \FrontendTemplate('mod_linkscollection_toplinks');

		// Links laden
		$objLinks = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ? ORDER BY hits DESC, title ASC')
		                                    ->limit(100)
		                                    ->execute(1);

		$links = array();
		if($objLinks->numRows > 1)
		{
			$class = 'odd';
			// Datensätze anzeigen
			while($objLinks->next())
			{
				$links[] = array
				(
					'title'            => $objLinks->title,
					'url'              => 'bundles/contaolinkscollection/go.php?id='.$objLinks->id,
					'icon'             => \Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection::getFavicon($objLinks->id),
					'language'         => \Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection::getLanguageIcon($objLinks->language),
					'new'              => $objLinks->newWindow,
					'text'             => $objLinks->text,
					'popular'          => $objLinks->popular,
					'hits'             => $objLinks->hits,
					'problem_url'      => \Controller::generateFrontendUrl($objPage->row(), '/link/'.$objLinks->id),
					'class'            => $class,
					'newLink'          => ($objLinks->initdate >= $this->duration_new) ? NEWICON : '',
					'webarchiv'        => $objLinks->webarchiv
				);
				$class = ($class == 'odd') ? 'even' : 'odd';
			}
		}

		// Template füllen
		$this->Template->menu = $this->Menu();
		$this->Subtemplate->links = $links;
		$this->Template->links = $this->Subtemplate->parse();
		$this->Template->counter = array('categories'=>$this->numberCategories,'links'=>$this->numberLinks);
	}

	/**
	 * Generate Toplinks
	 */
	protected function Newlinks()
	{
		global $objPage;

		$this->Template = new \FrontendTemplate('mod_linkscollection_newlinks');

		// Links laden
		$objLinks = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ? AND initdate >= ? ORDER BY initdate DESC, title ASC')
		                                    ->execute(1, $this->duration_new);

		$links = array();
		if($objLinks->numRows > 0)
		{
			$class = 'odd';
			// Datensätze anzeigen
			while($objLinks->next())
			{
				$links[] = array
				(
					'title'            => $objLinks->title,
					'url'              => 'bundles/contaolinkscollection/go.php?id='.$objLinks->id,
					'icon'             => \Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection::getFavicon($objLinks->id),
					'language'         => \Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection::getLanguageIcon($objLinks->language),
					'new'              => $objLinks->newWindow,
					'text'             => $objLinks->text,
					'popular'          => $objLinks->popular,
					'hits'             => $objLinks->hits,
					'date'             => date("d.m.Y", $objLinks->initdate),
					'problem_url'      => \Controller::generateFrontendUrl($objPage->row(), '/link/'.$objLinks->id),
					'class'            => $class,
					'newLink'          => ($objLinks->initdate >= $this->duration_new) ? '' : '',
					'webarchiv'        => $objLinks->webarchiv
				);
				$class = ($class == 'odd') ? 'even' : 'odd';
			}
		}

		// Template füllen
		$this->Template->menu = $this->Menu();
		$this->Subtemplate->links = $links;
		$this->Template->links = $this->Subtemplate->parse();
		$this->Template->counter = array('categories'=>$this->numberCategories,'links'=>$this->numberLinks);
	}

	/**
	 * Generate Linkform für neue Links
	 */
	protected function ViewFormularLinkNeu()
	{
		global $objPage;

		$this->Template = new \FrontendTemplate('mod_linkscollection_newlinks');

		// Template füllen
		$this->Template->menu = $this->Menu();
		$this->Template->form = $this->FormularLinkNeu();
	}

	/**
	 * Generate Suche
	 */
	protected function Search()
	{
		global $objPage;

		\Session::getInstance()->set('pageAlias', $objPage->alias); // Alias der Linksammlung in Sitzung speichern
		\Session::getInstance()->set('pageId', $objPage->id); // ID der Linksammlung in Sitzung speichern
		$this->Template = new \FrontendTemplate('mod_linkscollection_search');
		$this->Template->menu = $this->Menu();
	}

	/**
	 * Generate Linkinfo
	 */
	protected function Linkinfo()
	{
		global $objPage;

		$this->Template = new \FrontendTemplate('mod_linkscollection_link');

		// Link laden
		$objLink = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ? AND id = ?')
		                                   ->execute(1, \Input::get('link'));

		if($objLink->numRows)
		{
			// Datensatz anzeigen
			$this->Template->id = $objLink->id;
			$this->Template->title = $objLink->title;
			$this->Template->url_raw = $objLink->url;
			$this->Template->url_go = 'bundles/contaolinkscollection/go.php?id='.$objLink->id;
			$this->Template->newWindow = $objLink->newWindow;
			$this->Template->text = $objLink->text;
			$this->Template->hits = $objLink->hits;
			$this->Template->average = ($objLink->hits == 0) ? 0 : str_replace('.',',',sprintf("%01.1f",$objLink->hits / ((time() - $objLink->initdate) / 86400)));
			$this->Template->popular = $objLink->popular;
			$this->Template->icon = \Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection::getFavicon($objLink->id);
			$this->Template->initdate = $objLink->initdate ? date("d.m.Y, H:i", $objLink->initdate) : 'unbekannt';
			$this->Template->tstamp = date("d.m.Y, H:i", $objLink->tstamp);
			$this->Template->statedate = date("d.m.Y, H:i", $objLink->statedate);
			$this->Template->statecode = $objLink->statecode;
			$this->Template->statetext = $objLink->statetext;
			$this->Template->problemdate = ($objLink->problemdate == 0) ? '' : date("d.m.Y, H:i", $objLink->problemdate);
			$this->Template->problemcount = $objLink->problemcount;
			$this->Template->webarchiv = $objLink->webarchiv;
		}

		// Template füllen
		$this->Template->menu = $this->Menu();
		$this->Template->counter = array('categories'=>$this->numberCategories,'links'=>$this->numberLinks);
		$this->Template->form = self::FormularLinkFehler($objLink);
	}

	protected function Menu()
	{
		global $objPage;

		// Menü erstellen
		$menu = array
		(
			array
			(
				'title' => 'Startseite',
				'link'  => \Controller::generateFrontendUrl($objPage->row()),
			),
			array
			(
				'title' => 'Toplinks',
				'link'  => \Controller::generateFrontendUrl($objPage->row(), '/view/toplinks'),
			),
			array
			(
				'title' => 'Neue Links',
				'link'  => \Controller::generateFrontendUrl($objPage->row(), '/view/newlinks'),
			),
			array
			(
				'title' => 'Suchen',
				'link'  => \Controller::generateFrontendUrl($objPage->row(), '/view/search'),
			),
			array
			(
				'title' => 'Neuen Link melden',
				'link'  => \Controller::generateFrontendUrl($objPage->row(), '/view/sendlink/category/'.\Input::get('category')),
			),
		);
		return $menu;
	}

	/**
	 * Generiert eine Baumstruktur aller Kategorien
	 */
	protected function getCategories()
	{
		// Kategorien laden
		$objCats = \Database::getInstance()->prepare('SELECT id,pid,title FROM tl_linkscollection WHERE published = ? ORDER BY title ASC')
		                                   ->execute(1);
		// Kategoriebaum sichern
		if($objCats->numRows > 1)
		{
			// Datensätze speichern
			while($objCats->next())
			{
				$this->baum[$objCats->pid][$objCats->id] = $objCats->title;
			}
		}

		$this->getTree(0); // Baum $this->tree anlegen

		// Jetzt noch Links zählen
		$objLinks = \Database::getInstance()->prepare('SELECT id FROM tl_linkscollection_links WHERE published = ?')
		                                    ->execute(1);

		$this->numberCategories = $objCats->numRows; // Anzahl der Kategorien
		$this->numberLinks = $objLinks->numRows; // Anzahl der Links

	}

	public function getTree($id)
	{
		// Generiert den Baum ab der gewünschten ID
		if(isset($this->baum[$id]))
		{
			foreach($this->baum[$id] as $key => $val)
			{
				$this->tree[$key] = str_repeat("- ", $this->level) . $val;
				$this->level++;
				$this->getTree($key);
				$this->level--;
			}
			reset($this->baum[$id]);
		}
	}

	protected function saveNewlink($data)
	{
		//print_r($data);
		// Datenbank aktualisieren
		$zeit = time();
		$set = array
		(
			'pid' => $data['category'],
			'tstamp' => $zeit,
			'initdate' => $zeit,
			'title' => $data['title'],
			'url' => $data['url'],
			'text' => $data['description'],
			'newWindow' => 1,
			'name' => $data['name'],
			'email' => $data['email']
		);
		$objLink = \Database::getInstance()->prepare('INSERT INTO tl_linkscollection_links %s')
		                                   ->set($set)
		                                   ->execute();

		\System::log('[Linkscollection] New Link submitted: '.$data['title'].' ('.$data['url'].')', __CLASS__.'::'.__FUNCTION__, TL_CRON);

		// Email an Admin verschicken
		$objEmail = new \Email();
		$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
		$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
		$objEmail->subject = sprintf($GLOBALS['TL_LANG']['MSC']['linkscollection_subject'], \Idna::decode(\Environment::get('host')));

		// Kommentar zusammenbauen
		$strComment = 'Titel: '.$data['title']."\n";
		$strComment .= 'URL: '.$data['url']."\n";
		$strComment .= 'Kategorie: '.$data['category']."\n";
		$strComment .= 'Beschreibung: '.$data['description'];

		// Add the comment details
		$objEmail->text = sprintf($GLOBALS['TL_LANG']['MSC']['linkscollection_message'],
		                          $data['name'] . ' (' . $data['email'] . ')',
		                          $strComment,
		                          \Idna::decode(\Environment::get('base')) . \Environment::get('request'),
		                          \Idna::decode(\Environment::get('base')) . 'contao/main.php?do=linkscollection&table=tl_linkscollection_links&act=edit&id=' . $objLink->insertId);

		$objEmail->sendTo(array($GLOBALS['TL_ADMIN_NAME'].' <'.$GLOBALS['TL_ADMIN_EMAIL'].'>'));
	}

	protected function saveProblemlink($data)
	{
		//print_r($data);

		// Meldung zusammenbauen
		$zeit = time();
		$content = 'Titel: '.$data['title']."\n";
		$content .= 'URL: '.$data['url']."\n";
		$content .= 'Fehler: '.$data['error']."\n\n";
		$content .= 'Name: '.$data['name']."\n";
		$content .= 'E-Mail: '.$data['email']."\n\n";
		$content .= 'Neuer Titel: '.$data['new_title']."\n";
		$content .= 'Neue URL: '.$data['new_url']."\n\n";
		$content .= 'Neue Beschreibung: '.$data['new_description']."\n";
		$content .= 'Kommentar: '.$data['comment']."\n\n";
		$content .= 'Gemeldet am '.date("d.m.Y, H:i:s", $zeit);

		$objLink = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE id = ?')
		                                   ->execute($data['id']);

		if($objLink->numRows)
		{
			// Datenbank aktualisieren
			$anzahl = $objLink->problemcount + 1;

			// Problem in Datenbank schreiben
			$problem = "<h2>Meldung Nr. $anzahl</h2>";
			$problem .= '<p><b>Gemeldet am '.date("d.m.Y, H:i", $zeit).'</b></p>';
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
			$problem .= $objLink->problem; // Vorherige Probleme anfügen

			$set = array
			(
				'problem'      => $problem,
				'problemdate'  => $zeit,
				'problemcount' => $anzahl
			);
			$objLink = \Database::getInstance()->prepare('UPDATE tl_linkscollection_links %s WHERE id = ?')
			                                   ->set($set)
			                                   ->execute($data['id']);
		}

		\System::log('[Linkscollection] Error Link ID '.$data['id'].' submitted: '.$data['title'].' ('.$data['url'].')', __CLASS__.'::'.__FUNCTION__, TL_CRON);

		// Email an Admin verschicken
		$objEmail = new \Email();
		$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
		$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
		$objEmail->subject = sprintf($GLOBALS['TL_LANG']['MSC']['linkscollection_error_subject'], \Idna::decode(\Environment::get('host')));

		// Add the comment details
		$objEmail->text = sprintf($GLOBALS['TL_LANG']['MSC']['linkscollection_error_message'],
		                          $data['name'] . ' (' . $data['email'] . ')',
		                          $content,
		                          \Idna::decode(\Environment::get('base')) . \Environment::get('request'),
		                          \Idna::decode(\Environment::get('base')) . 'contao/main.php?do=linkscollection&table=tl_linkscollection_links&act=edit&id=' . $data['id']);

		$objEmail->sendTo(array($GLOBALS['TL_ADMIN_NAME'].' <'.$GLOBALS['TL_ADMIN_EMAIL'].'>'));
	}

	protected function FormularLinkNeu()
	{

		// Der 1. Parameter ist die Formular-ID (hier "linkform")
		// Der 2. Parameter ist GET oder POST
		// Der 3. Parameter ist eine Funktion, die entscheidet wann das Formular gesendet wird (Third is a callable that decides when your form is submitted)
		// Der optionale 4. Parameter legt fest, ob das ausgegebene Formular auf Tabellen basiert (true)
		// oder nicht (false) (You can pass an optional fourth parameter (true by default) to turn the form into a table based one)
		$objForm = new \Codefog\HasteBundle\Form\Form('linkform', 'POST', function($objHaste)
		{
			return \Input::post('FORM_SUBMIT') === $objHaste->getFormId();
		});
		
		// URL für action festlegen. Standard ist die Seite auf der das Formular eingebunden ist.
		// $objForm->setFormActionFromUri();
		
		$objForm->addFormField('name', array(
			'label'         => 'Vor- und Nachname',
			'inputType'     => 'text',
			'eval'          => array('mandatory'=>true, 'class'=>'form-control')
		));
		$objForm->addFormField('email', array(
			'label'         => 'E-Mail',
			'inputType'     => 'text',
			'eval'          => array('mandatory'=>true, 'rgxp'=>'email', 'class'=>'form-control')
		));
		$objForm->addFormField('title', array(
			'label'         => 'Titel des Links',
			'inputType'     => 'text',
			'eval'          => array('mandatory'=>true, 'class'=>'form-control')
		));
		$objForm->addFormField('url', array(
			'label'         => 'URL des Links',
			'inputType'     => 'text',
			'eval'          => array('mandatory'=>true, 'rgxp'=>'url', 'class'=>'form-control')
		));
		$objForm->addFormField('category', array(
			'label'         => 'Kategorie des Links',
			'inputType'     => 'select',
			'default'       => array(\Input::get('category')),
			'options'       => array_keys($this->tree),
			'reference'     => $this->tree,
			'eval'          => array('mandatory'=>true, 'choosen'=>true, 'class'=>'form-control')
		));
		$objForm->addFormField('description', array(
			'label'         => 'Beschreibung des Links',
			'inputType'     => 'textarea',
			'eval'          => array('mandatory'=>false, 'rte'=>'tinyMCE', 'class'=>'form-control')
		));
		// Submit-Button hinzufügen
		$objForm->addFormField('submit', array(
			'label'         => 'Absenden',
			'inputType'     => 'submit',
			'eval'          => array('class'=>'btn btn-primary')
		));
		$objForm->addCaptchaFormField('captcha');
		// Ausgeblendete Felder FORM_SUBMIT und REQUEST_TOKEN automatisch hinzufügen.
		// Nicht verwenden wenn generate() anschließend verwendet, da diese Felder dort standardmäßig bereitgestellt werden.
		// $objForm->addContaoHiddenFields();
		
		// validate() prüft auch, ob das Formular gesendet wurde
		if($objForm->validate())
		{
			// Alle gesendeten und analysierten Daten holen (funktioniert nur mit POST)
			$arrData = $objForm->fetchAll();
			self::saveNewlink($arrData); // Daten sichern
			// Seite neu laden
			\Controller::addToUrl('send=1'); // Hat keine Auswirkung, verhindert aber das das Formular ausgefüllt ist
			\Controller::reload(); 
		}
		
		// Formular als String zurückgeben
		return $objForm->generate();

	}

	protected function FormularLinkFehler($object)
	{

		// Der 1. Parameter ist die Formular-ID (hier "linkform")
		// Der 2. Parameter ist GET oder POST
		// Der 3. Parameter ist eine Funktion, die entscheidet wann das Formular gesendet wird (Third is a callable that decides when your form is submitted)
		// Der optionale 4. Parameter legt fest, ob das ausgegebene Formular auf Tabellen basiert (true)
		// oder nicht (false) (You can pass an optional fourth parameter (true by default) to turn the form into a table based one)
		$objForm = new \Codefog\HasteBundle\Form\Form('linkform', 'POST', function($objHaste)
		{
			return \Input::post('FORM_SUBMIT') === $objHaste->getFormId();
		});
		
		// URL für action festlegen. Standard ist die Seite auf der das Formular eingebunden ist.
		// $objForm->setFormActionFromUri();
		
		// Formularfelder hinzufügen
		$objForm->addFormField('id', array(
			'default'       => $object->id,
			'inputType'     => 'hidden'
		));
		$objForm->addFormField('title', array(
			'default'       => $object->title,
			'inputType'     => 'hidden'
		));
		$objForm->addFormField('url', array(
			'default'       => $object->url,
			'inputType'     => 'hidden'
		));
		$objForm->addFormField('name', array(
			'label'         => 'Vor- und Nachname',
			'inputType'     => 'text',
			'eval'          => array('mandatory'=>true, 'class'=>'form-control')
		));
		$objForm->addFormField('email', array(
			'label'         => 'E-Mail',
			'inputType'     => 'text',
			'eval'          => array('mandatory'=>true, 'rgxp'=>'email', 'class'=>'form-control')
		));
		$objForm->addFormField('new_title', array(
			'label'         => 'Neuer Titel',
			'inputType'     => 'text',
			'eval'          => array('mandatory'=>false, 'class'=>'form-control')
		));
		$objForm->addFormField('new_url', array(
			'label'         => 'Neue URL',
			'inputType'     => 'text',
			'eval'          => array('mandatory'=>false, 'rgxp'=>'url', 'class'=>'form-control')
		));
		$objForm->addFormField('new_description', array(
			'label'         => 'Neue Detailbeschreibung',
			'inputType'     => 'textarea',
			'eval'          => array('mandatory'=>false, 'rte'=>'tinyMCE', 'class'=>'form-control')
		));
		$objForm->addFormField('error', array(
			'label'         => 'Fehler',
			'inputType'     => 'select',
			'options'       => &$GLOBALS['TL_LANG']['linkscollection']['errors'],
			'eval'          => array('mandatory'=>false, 'choosen'=>true, 'class'=>'form-control')
		));
		$objForm->addFormField('comment', array(
			'label'         => 'Kommentar',
			'inputType'     => 'textarea',
			'eval'          => array('mandatory'=>true, 'rte'=>'tinyMCE', 'class'=>'form-control')
		));
		// Submit-Button hinzufügen
		$objForm->addFormField('submit', array(
			'label'         => 'Absenden',
			'inputType'     => 'submit',
			'eval'          => array('class'=>'btn btn-primary')
		));
		$objForm->addCaptchaFormField('captcha');
		// Ausgeblendete Felder FORM_SUBMIT und REQUEST_TOKEN automatisch hinzufügen.
		// Nicht verwenden wenn generate() anschließend verwendet, da diese Felder dort standardmäßig bereitgestellt werden.
		// $objForm->addContaoHiddenFields();
		
		// validate() prüft auch, ob das Formular gesendet wurde
		if($objForm->validate())
		{
			// Alle gesendeten und analysierten Daten holen (funktioniert nur mit POST)
			$arrData = $objForm->fetchAll();
			self::saveProblemlink($arrData); // Daten sichern
			// Seite neu laden
			\Controller::addToUrl('send=1'); // Hat keine Auswirkung, verhindert aber das das Formular ausgefüllt ist
			\Controller::reload(); 
		}
		
		// Formular als String zurückgeben
		return $objForm->generate();

	}

}
