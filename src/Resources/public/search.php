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
	protected $strTemplate = 'mod_linkscollection_linkrow';
	var $duration_new;

	public function __construct()
	{
		$this->keywords = \Input::get('s');
		$this->Template = new \FrontendTemplate($this->strTemplate);
		$this->duration_new = time() - ($GLOBALS['TL_CONFIG']['linkscollection_new_duration'] * 86400);
	}

	public function run()
	{
		if(strlen($this->keywords) >= 3)
		{
			$objLinks = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ? AND (title LIKE ? OR url LIKE ? OR text LIKE ?) ORDER BY hits DESC')
			                                    ->execute(1, '%'.$this->keywords.'%', '%'.$this->keywords.'%', '%'.$this->keywords.'%');

			$links = array();
			if($objLinks->numRows > 0)
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
				$this->Template->links = $links;
				echo $objLinks->numRows.' Link(s) gefunden';
				echo '<ul>';
				echo $this->Template->parse();
				echo '<ul>';
			}
			else
			{
				echo 'Keine Links mit "'.$this->keywords.'" gefunden';
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
