<?php

declare(strict_types=1);

/**
 * Linksammlung für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoLinkscollectionBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony-Bundle-Klasse der Linksammlung.
 *
 * Sie enthält bewusst keine eigene Logik: Contao findet über den Bundle-Pfad
 * die Ressourcen unter src/Resources/contao (DCA, Sprachdateien, Templates)
 * und src/Resources/public (Bilder, CSS, JavaScript).
 */
class ContaoLinkscollectionBundle extends Bundle
{
}
