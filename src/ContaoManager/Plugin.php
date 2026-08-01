<?php

declare(strict_types=1);

/**
 * Linksammlung für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoLinkscollectionBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Schachbulle\ContaoLinkscollectionBundle\ContaoLinkscollectionBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Meldet die Erweiterung beim Contao Manager an.
 */
class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
	/**
	 * Nennt das Bundle und seine Ladereihenfolge.
	 *
	 * Die Linksammlung wird nach dem Contao-Kern geladen, weil sie dessen
	 * DCA-Dateien (tl_module, tl_settings) ergänzt und das erst funktioniert,
	 * wenn die Grundfassung dieser Tabellen bereits steht.
	 *
	 * @param ParserInterface $parser Vom Manager gestellter Parser; wird hier
	 *                                nicht gebraucht, gehört aber zur Schnittstelle
	 *
	 * @return array<BundleConfig> Liste mit der Bundle-Beschreibung
	 */
	public function getBundles(ParserInterface $parser): array
	{
		return [
			BundleConfig::create(ContaoLinkscollectionBundle::class)
				->setLoadAfter([ContaoCoreBundle::class]),
		];
	}

	/**
	 * Bindet die eigenen Routen in die Routensammlung der Anwendung ein.
	 *
	 * Über diese Routen laufen die Klickweiterleitung (/linkscollection/go/…)
	 * und die Suche per JavaScript (/linkscollection/suche). Bis Version 1.5
	 * lagen dafür eigenständige PHP-Dateien im Web-Verzeichnis, die sich über
	 * system/initialize.php in Contao eingeklinkt haben — diese Datei gibt es
	 * seit Contao 5 nicht mehr.
	 *
	 * Die Routen stehen bewusst in einer YAML-Datei und nicht als PHP-Attribute
	 * in den Controllern: Das Einlesen von Attributrouten heißt in Symfony 5.4
	 * noch "annotation" und ab Symfony 6.4 "attribute", ein fest verdrahteter
	 * Typ würde also auf einer der beiden Contao-Versionen scheitern.
	 *
	 * @param LoaderResolverInterface $resolver Sucht den passenden Konfigurationslader
	 * @param KernelInterface         $kernel   Kernel der Anwendung, hier nicht ausgewertet
	 *
	 * @return RouteCollection|null Die geladenen Routen, oder null wenn sich
	 *                              kein Lader für YAML-Dateien finden lässt
	 */
	public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
	{
		$datei = __DIR__.'/../Resources/config/routes.yml';

		$loader = $resolver->resolve($datei);

		if (false === $loader)
		{
			return null;
		}

		return $loader->load($datei);
	}
}
