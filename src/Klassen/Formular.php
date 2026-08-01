<?php

declare(strict_types=1);

/**
 * Linksammlung für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoLinkscollectionBundle\Klassen;

use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;

/**
 * Kleiner Formularbauer für die beiden Frontend-Formulare der Linksammlung.
 *
 * Bis Version 1.5 kam dafür \Haste\Form\Form aus dem Paket
 * codefog/contao-haste zum Einsatz. Dessen Formularklasse gibt es in der für
 * Contao 5 geeigneten Haste-Fassung nicht mehr, und für zwei Formulare lohnt
 * keine Fremdabhängigkeit: Contao bringt mit den Widget-Klassen aus
 * $GLOBALS['TL_FFL'] alles Nötige mit — inklusive Pflichtfeldprüfung,
 * Fehlermeldungen und Sicherheitsabfrage.
 *
 * Die Widget-Klassen heißen in Contao 4.13 und Contao 5 unterschiedlich
 * (FormTextField gegenüber FormText). Deshalb wird hier grundsätzlich über
 * den Feldtyp ("text", "select", …) gearbeitet und die Klasse aus
 * $GLOBALS['TL_FFL'] geholt.
 */
class Formular
{
	/**
	 * @var string Kennung des Formulars, landet im Feld FORM_SUBMIT
	 */
	private $id;

	/**
	 * @var array<string,array> Felddefinitionen im DCA-Format, in Reihenfolge
	 */
	private $felder = array();

	/**
	 * @var array<string,Widget> Erzeugte Widgets, gefüllt von aufbauen()
	 */
	private $widgets = array();

	/**
	 * @var bool Merker, ob aufbauen() bereits gelaufen ist
	 */
	private $aufgebaut = false;

	/**
	 * @var bool Merker, ob die Prüfung Fehler ergeben hat
	 */
	private $fehlerhaft = false;

	/**
	 * Legt ein neues Formular an.
	 *
	 * @param string $id Kennung des Formulars. Sie steht im versteckten Feld
	 *                   FORM_SUBMIT und entscheidet, ob eine Absendung zu
	 *                   diesem Formular gehört — bei zwei Formularen auf einer
	 *                   Seite müssen sich die Kennungen unterscheiden
	 */
	public function __construct(string $id)
	{
		$this->id = $id;
	}

	/**
	 * Nimmt ein Feld in das Formular auf.
	 *
	 * @param string $name Feldname; er wird zum name-Attribut und ist später
	 *                     der Schlüssel im Ergebnis von werte()
	 * @param array  $dca  Felddefinition im DCA-Format, also mit den
	 *                     Schlüsseln label, inputType, eval, options und
	 *                     wahlweise default für die Vorbelegung
	 *
	 * @return void
	 */
	public function feld(string $name, array $dca): void
	{
		$this->felder[$name] = $dca;
	}

	/**
	 * Meldet, ob dieses Formular gerade abgeschickt wurde.
	 *
	 * @return bool true, wenn die aktuelle Anfrage die Absendung genau dieses
	 *              Formulars ist
	 */
	public function abgeschickt(): bool
	{
		return Input::post('FORM_SUBMIT') === $this->id;
	}

	/**
	 * Prüft die Eingaben und meldet, ob sie vollständig und gültig sind.
	 *
	 * @return bool true nur dann, wenn das Formular abgeschickt wurde und
	 *              kein Feld einen Fehler gemeldet hat. Wurde es nicht
	 *              abgeschickt, kommt immer false zurück
	 */
	public function pruefen(): bool
	{
		if (!$this->abgeschickt())
		{
			return false;
		}

		$this->aufbauen();

		return !$this->fehlerhaft;
	}

	/**
	 * Liefert die geprüften Eingaben.
	 *
	 * @return array<string,mixed> Feldname => Wert. Enthält auch die
	 *                             versteckten Felder, aber weder den
	 *                             Absende-Knopf noch die Sicherheitsabfrage
	 */
	public function werte(): array
	{
		$this->aufbauen();

		$daten = array();

		foreach ($this->widgets as $name => $widget)
		{
			$typ = $this->felder[$name]['inputType'] ?? '';

			if ('submit' === $typ || 'captcha' === $typ)
			{
				continue;
			}

			$daten[$name] = $widget->value;
		}

		return $daten;
	}

	/**
	 * Erzeugt das vollständige Formular als HTML.
	 *
	 * Das Formular sendet immer per POST an die aufrufende Adresse zurück.
	 * Das versteckte Feld REQUEST_TOKEN muss enthalten sein, sonst weist
	 * Contao die Absendung als möglichen Fremdaufruf zurück.
	 *
	 * @return string Das <form>-Element mit allen Feldern
	 */
	public function erzeugen(): string
	{
		$this->aufbauen();

		$felder = '';

		foreach ($this->widgets as $widget)
		{
			$felder .= $widget->parse();
		}

		$token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

		return '<form id="'.$this->id.'" method="post" enctype="application/x-www-form-urlencoded">'
			.'<div class="formbody">'
			.'<input type="hidden" name="FORM_SUBMIT" value="'.StringUtil::specialchars($this->id).'">'
			.'<input type="hidden" name="REQUEST_TOKEN" value="'.StringUtil::specialchars($token).'">'
			.$felder
			.'</div></form>';
	}

	/**
	 * Erzeugt die Widgets und prüft sie bei einer Absendung.
	 *
	 * Läuft nur einmal: pruefen(), werte() und erzeugen() rufen die Methode
	 * jeweils auf, sollen aber gemeinsam auf denselben Widgets arbeiten —
	 * sonst gingen die Fehlermeldungen zwischen Prüfung und Ausgabe verloren.
	 *
	 * @return void
	 */
	private function aufbauen(): void
	{
		if ($this->aufgebaut)
		{
			return;
		}

		$this->aufgebaut = true;
		$abgeschickt = $this->abgeschickt();

		foreach ($this->felder as $name => $dca)
		{
			$typ = $dca['inputType'] ?? 'text';
			$klasse = $GLOBALS['TL_FFL'][$typ] ?? null;

			if (null === $klasse || !class_exists($klasse))
			{
				continue; // Unbekannter Feldtyp: Feld überspringen statt abstürzen
			}

			$daten = Widget::getAttributesFromDca($dca, $name, $dca['default'] ?? null);

			/** @var Widget $widget */
			$widget = new $klasse($daten);

			if ($abgeschickt)
			{
				$widget->validate();

				if ($widget->hasErrors())
				{
					$this->fehlerhaft = true;
				}
			}

			$this->widgets[$name] = $widget;
		}
	}
}
