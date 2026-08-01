<?php

declare(strict_types=1);

/**
 * Linksammlung für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoLinkscollectionBundle\Klassen;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\System;
use Psr\Log\LogLevel;

/**
 * Schreibt Meldungen in das Contao-Systemprotokoll.
 *
 * Bis Version 1.5 lief das über System::log() und die Konstanten TL_CRON,
 * TL_ACCESS und TL_ERROR. Beides gibt es in Contao 5 nicht mehr; der Kern
 * schreibt seither direkt über den Monolog-Kanal "contao" und hängt einen
 * ContaoContext an, aus dem das Backend Kategorie und Verursacher liest.
 * Diese Klasse kapselt das, damit der Aufruf in der Erweiterung kurz bleibt.
 */
class Protokoll
{
	/**
	 * Schreibt eine Zeile in das Systemprotokoll (Backend: System -> Systemlog).
	 *
	 * Schlägt bewusst nie fehl: Ist kein Container vorhanden (etwa in einem
	 * Unit-Test) oder fehlt der Protokolldienst, wird die Meldung verworfen,
	 * statt den eigentlichen Vorgang abzubrechen.
	 *
	 * @param string $text      Der Meldungstext, wie er im Backend erscheint
	 * @param string $funktion  Verursacher im Format Klasse::Methode; erscheint
	 *                          im Systemlog als Quelle und darf nicht leer sein
	 * @param string $kategorie Eine der Konstanten von ContaoContext, z. B.
	 *                          ContaoContext::CRON oder ContaoContext::ERROR.
	 *                          Steuert die Filterung im Backend
	 *
	 * @return void
	 */
	public static function schreiben(string $text, string $funktion, string $kategorie = ContaoContext::GENERAL): void
	{
		$container = System::getContainer();

		if (null === $container || !$container->has('monolog.logger.contao'))
		{
			return;
		}

		$container->get('monolog.logger.contao')->log(
			LogLevel::INFO,
			$text,
			['contao' => new ContaoContext($funktion ?: __METHOD__, $kategorie)]
		);
	}
}
