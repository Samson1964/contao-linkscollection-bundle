<?php
//ini_set('display_errors', '1');
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
define('TL_SCRIPT', 'bundles/contaolinkscollection/go.php');
require($_SERVER['DOCUMENT_ROOT'].'/../system/initialize.php');

/**
 * Class LinkClick
 *
 */
class LinkClick
{
	protected $id;

	public function __construct()
	{
		$this->id = intval(\Input::get('id'));
	}

	public function run()
	{
		$ip = $_SERVER['REMOTE_ADDR'];

		$objLink = \Database::getInstance()->prepare('SELECT * FROM tl_linkscollection_links WHERE published = ? AND id = ?')
		                                   ->execute(1, $this->id);
		if(!$objLink->next())
		{
			\System::log('[Linkscollection] Forwarding link ID '.$this->id.' not exist', __CLASS__.'::'.__FUNCTION__, TL_ERROR);
			header('HTTP/1.1 501 Not Implemented');
			throw new \ErrorException('Link ID not found',2,1,basename(__FILE__),__LINE__);
		}

		// Update, wenn IP ungleich
		if($ip != $objLink->ip)
		{
			$tstamp = time();
			$set = array
			(
				'hits'    => $objLink->hits + 1,
				'ip'      => $ip,
				'ipdate'  => $tstamp,
			);
			\Database::getInstance()->prepare('UPDATE tl_linkscollection_links %s WHERE id = ?')
			                        ->set($set)
			                        ->executeUncached($this->id);
		}

		$url = \Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection::getWeblink($objLink->url, $objLink->webarchiv);

		\System::log('[Linkscollection] Forwarding link ID '.$objLink->id.': '.$url, __CLASS__.'::'.__FUNCTION__, TL_ACCESS);
		\Controller::redirect($url);

	}
}

/**
 * Instantiate controller
 */
$objClick = new LinkClick();
$objClick->run();

