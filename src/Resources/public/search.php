<?php
ini_set('display_errors', '1');

/**
 * Contao Open Source CMS, Copyright (C) 2005-2013 Leo Feyer
 *
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */
use Contao\Controller;

/**
 * Initialize the system
 */
define('TL_MODE', 'FE');
define('TL_SCRIPT', 'bundles/contaolinkscollection/search.php');
require($_SERVER['DOCUMENT_ROOT'].'/../system/initialize.php');

/**
 * Class LinkSearch
 *
 */
class LinkSearch
{
	protected $keywords;
	protected $kategorienTemplate = 'mod_linkscollection_kategorierow';
	protected $linksTemplate = 'mod_linkscollection_linkrow';
	var $duration_new;

	public function __construct()
	{
		$this->keywords = \Input::get('s');
		$this->duration_new = time() - ($GLOBALS['TL_CONFIG']['linkscollection_new_duration'] * 86400);
	}

	public function run()
	{
		if(strlen($this->keywords) >= 3)
		{

			$pageAlias = \Session::getInstance()->get('pageAlias'); // Seitenalias aus Sitzung laden
			$pageId = \Session::getInstance()->get('pageId'); // Seiten-ID aus Sitzung laden
			echo $pageAlias;
			echo $pageId;
			$arrPage = array('id' => $pageId, 'alias' => $pageAlias);
			
			// Suche nach Kategorien
			$objKategorien = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection WHERE published = ? AND title LIKE ? ORDER BY title ASC')
			                                         ->execute(1, '%'.$this->keywords.'%');

			$kategorien = array();
			if($objKategorien->numRows)
			{
				// Datensätze anzeigen
				while($objKategorien->next())
				{
					$class = ($class == 'odd') ? 'even' : 'odd';
					$kategorien[] = array
					(
						'title'       => $objKategorien->title,
						//'url'         => 'bundles/contaolinkscollection/go.php?id='.$objKategorien->id,
						//'url'         => \Controller::generateFrontendUrl($arrPage, 'category/'.$objKategorien->id),
						'url'         => \Environment::get('base').$pageAlias.'/category/'.$objKategorien->id.'.html',
						'class'       => $class,
					);
				}

				// Template füllen
				$this->Template = new \FrontendTemplate($this->kategorienTemplate);
				$this->Template->kategorien = $kategorien;
				if($objKategorien->numRows == 1) echo $objKategorien->numRows.' Kategorie gefunden:';
				else echo $objKategorien->numRows.' Kategorien gefunden:';
				echo '<ul>';
				echo $this->Template->parse();
				echo '</ul>';
			}
			else
			{
				echo '<p>Keine Kategorien mit "'.$this->keywords.'" gefunden.</p>';
			}

			// Suche nach Links
			$objLinks = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ? AND (title LIKE ? OR url LIKE ? OR text LIKE ?) ORDER BY hits DESC')
			                                    ->execute(1, '%'.$this->keywords.'%', '%'.$this->keywords.'%', '%'.$this->keywords.'%');

			$links = array();
			if($objLinks->numRows)
			{
				// Datensätze anzeigen
				while($objLinks->next())
				{
					$class = ($class == 'odd') ? 'even' : 'odd';
					$links[] = array
					(
						'title'       => $objLinks->title,
						'url'         => 'bundles/contaolinkscollection/go.php?id='.$objLinks->id,
						'icon'        => \Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection::getFavicon($objLinks->id),
						'language'    => \Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection::getLanguageIcon($objLinks->language),
						'new'         => $objLinks->newWindow,
						'text'        => $objLinks->text,
						'popular'     => $objLinks->popular,
						'hits'        => $objLinks->hits,
						'class'       => $class,
						'newLink'     => ($objLinks->initdate >= $this->duration_new) ? NEWICON : '',
						'webarchiv'   => $objLinks->webarchiv
					);
				}

				// Template füllen
				$this->Template = new \FrontendTemplate($this->linksTemplate);
				$this->Template->links = $links;
				if($objLinks->numRows == 1) echo $objLinks->numRows.' Link gefunden:';
				else echo $objLinks->numRows.' Links gefunden:';
				echo '<ul>';
				echo $this->Template->parse();
				echo '</ul>';
			}
			else
			{
				echo '<p>Keine Links mit "'.$this->keywords.'" gefunden.</p>';
			}

			\System::log('[Linkscollection] Searching "'.$this->keywords.'" - found '.$objLink->numRows.' row(s)', __CLASS__.'::'.__FUNCTION__, TL_ACCESS);
		}
		else
		{
			echo 'Suchbegriff zu kurz';
			\System::log('[Linkscollection] Searching "'.$this->keywords.'" - to short', __CLASS__.'::'.__FUNCTION__, TL_ERROR);
		}

	}
}

/**
 * Instantiate controller
 */
$objClick = new LinkSearch();
$objClick->run();
