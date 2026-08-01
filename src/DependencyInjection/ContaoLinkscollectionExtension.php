<?php

declare(strict_types=1);

/**
 * Linksammlung für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoLinkscollectionBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Bindet die Dienstbeschreibung der Erweiterung in den Container ein.
 */
class ContaoLinkscollectionExtension extends Extension
{
	/**
	 * Lädt src/Resources/config/services.yml in den Container.
	 *
	 * @param array            $mergedConfig Zusammengeführte Konfiguration; die
	 *                                       Erweiterung hat keine eigene und
	 *                                       wertet den Wert nicht aus
	 * @param ContainerBuilder $container    Container, der die Dienste aufnimmt
	 *
	 * @return void
	 */
	public function load(array $mergedConfig, ContainerBuilder $container): void
	{
		$loader = new YamlFileLoader(
			$container,
			new FileLocator(__DIR__.'/../Resources/config')
		);

		$loader->load('services.yml');
	}
}
