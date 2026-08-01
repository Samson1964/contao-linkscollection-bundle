<?php

declare(strict_types=1);

/**
 * Linksammlung für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\Controller;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Database;
use Contao\Environment;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;

/**
 * Tabelle tl_linkscollection — die Kategorien der Linksammlung
 */
$GLOBALS['TL_DCA']['tl_linkscollection'] = array
(
	// Config
	'config' => array
	(
		'label'                       => &$GLOBALS['TL_LANG']['tl_linkscollection']['maintitle'],
		'dataContainer'               => DC_Table::class,
		'ctable'                      => array('tl_linkscollection_links'),
		'enableVersioning'            => true,
		'onload_callback'             => array
		(
			array('tl_linkscollection', 'addBreadcrumb')
		),
		'sql'                         => array
		(
			'keys'                    => array
			(
				'id'                  => 'primary',
				'pid'                 => 'index',
				'alias'               => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_TREE,
			'fields'                  => array('sorting'),
			'icon'                    => 'pagemounts.svg',
			'panelLayout'             => 'filter,search',
		),
		'label' => array
		(
			'fields'                  => array('title'),
			'format'                  => '%s',
			'label_callback'          => array('tl_linkscollection', 'addIcon')
		),
		'global_operations' => array
		(
			'linklist' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection']['linklist'],
				'href'                => 'key=linklist',
				'icon'                => 'bundles/contaolinkscollection/images/favicon_16.png',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			),
			'analyse' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection']['analyse'],
				'href'                => 'key=analyse',
				'icon'                => 'bundles/contaolinkscollection/images/problem_16.png',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			),
			'statistik' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection']['statistik'],
				'href'                => 'key=statistik',
				'icon'                => 'bundles/contaolinkscollection/images/statistik.png',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			),
			'toggleNodes' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['toggleAll'],
				'href'                => 'ptg=all',
				'class'               => 'header_toggle',
				'showOnSelect'        => true
			),
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection']['edit'],
				'href'                => 'table=tl_linkscollection_links',
				'icon'                => 'edit.svg',
			),
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'header.svg',
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"',
			),
			// Kategorien lassen sich bewusst nicht löschen: Mit der Kategorie
			// verschwänden auch alle Links darin. Zum Ausblenden dient das
			// Auge, zum endgültigen Entfernen der Weg über die Datenbank.
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection']['toggle'],
				'href'                => 'act=toggle&amp;field=published',
				'icon'                => 'visible.svg',
				'showInHeader'        => true,
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('protected'),
		'default'                     => '{title_legend},title,alias;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;{published_legend},published,start,stop'
	),

	// Subpalettes
	'subpalettes' => array
	(
		'protected'                   => 'groups'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'label'                   => array('ID'),
			'search'                  => true,
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'sorting' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection']['title'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'search'                  => true,
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'decodeEntities'=>true),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'alias' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection']['alias'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'search'                  => true,
			'eval'                    => array('rgxp'=>'folderalias', 'doNotCopy'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
			'sql'                     => "varbinary(128) NOT NULL default ''",
			'save_callback' => array
			(
				array('tl_linkscollection', 'generateAlias')
			),
		),
		// Anzahl der Links ohne Unterkategorien (zählt die Statistik)
		'links_self' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['links_self'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'rgxp'=>'numeric'),
			'sql'                     => "int(6) unsigned NOT NULL default '0'"
		),
		// Anzahl der Links inklusive Unterkategorien (zählt die Statistik)
		'links_all' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['links_all'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'rgxp'=>'numeric'),
			'sql'                     => "int(6) unsigned NOT NULL default '0'"
		),
		// Anzahl der neuen Links inklusive Unterkategorien (zählt die Statistik)
		'links_new' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['links_new'],
			'exclude'                 => true,
			'default'                 => 0,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'rgxp'=>'numeric'),
			'sql'                     => "int(6) unsigned NOT NULL default '0'"
		),
		// Anteil geprüfter Links in Prozent (zählt die Statistik)
		'links_checkquote' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['links_checkquote'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'rgxp'=>'numeric'),
			'sql'                     => "int(3) unsigned NOT NULL default '0'"
		),
		'protected' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection']['protected'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'groups' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection']['groups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),
		'guests' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection']['guests'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'cssID' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection']['cssID'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'space' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection']['space'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection']['published'],
			'exclude'                 => true,
			'toggle'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'start' => array
		(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection']['start'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'stop' => array
		(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection']['stop'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		)
	)
);

/**
 * Rückruffunktionen der Tabelle tl_linkscollection.
 *
 * Die Klasse trägt bewusst den Tabellennamen: Contao erzeugt sie über
 * System::importStatic(), sobald einer der oben eingetragenen Callbacks
 * ansteht.
 */
class tl_linkscollection extends Backend
{
	/**
	 * Schränkt den Kategoriebaum auf einen Knoten ein und zeigt den Weg dorthin.
	 *
	 * Die Linksammlung kann sehr viele Kategorien enthalten. Über einen Klick
	 * auf einen Kategorietitel merkt sich das Backend diese Kategorie als
	 * Einstiegspunkt; die Navigationsleiste darüber führt wieder heraus.
	 *
	 * Der gewählte Knoten steht in der Backend-Sitzung, damit er einen
	 * Seitenwechsel übersteht. Bis Version 1.5 lief das über die entfallene
	 * Contao-Klasse Session.
	 *
	 * @return void
	 */
	public function addBreadcrumb(): void
	{
		$sitzung = self::sitzung();

		// Angeklickten Knoten merken und den Parameter aus der Adresse entfernen
		if (null !== Input::get('node'))
		{
			if ($sitzung)
			{
				$sitzung->set('tl_linkscollection_node', (int) Input::get('node'));
			}

			Controller::redirect(preg_replace('/[?&]node=[^&]*/', '', Environment::get('request')));
		}

		$cat = $sitzung ? (int) $sitzung->get('tl_linkscollection_node') : 0;

		if (!$cat)
		{
			return;
		}

		// Kategorienbaum einschränken
		$GLOBALS['TL_DCA']['tl_linkscollection']['list']['sorting']['root'] = array($cat);

		// Infos zur aktuellen Kategorie laden
		$objActual = Database::getInstance()->prepare('SELECT * FROM tl_linkscollection WHERE published = ? AND id = ?')
		                                    ->execute(1, $cat);

		if (!$objActual->numRows)
		{
			return;
		}

		$symbol = '<img src="bundles/contaolinkscollection/images/category.png" width="18" height="18" alt=""> ';
		$breadcrumb = array($symbol.$objActual->title);

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

			$breadcrumb[] = $symbol.'<a href="'.Controller::addToUrl('node='.$objTemp->id).'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectNode'] ?? '').'">'.$objTemp->title.'</a>';
			$pid = (int) $objTemp->pid;
		}

		$breadcrumb[] = Image::getHtml('pagemounts.svg').' <a href="'.Controller::addToUrl('node=0').'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectAllNodes'] ?? '').'">'.($GLOBALS['TL_LANG']['MSC']['filterAll'] ?? 'Alle').'</a>';

		$GLOBALS['TL_DCA']['tl_linkscollection']['list']['sorting']['breadcrumb'] = '
		<ul id="tl_breadcrumb">
			<li>'.implode(' &gt; </li><li>', array_reverse($breadcrumb)).'</li>
		</ul>';
	}

	/**
	 * Liefert den Sitzungsspeicher des Backends.
	 *
	 * @return \Symfony\Component\HttpFoundation\Session\SessionBagInterface|null
	 *         Der Sitzungsbereich, oder null wenn es keine Sitzung gibt — etwa
	 *         beim Aufruf von contao:migrate auf der Kommandozeile
	 */
	private static function sitzung()
	{
		$container = System::getContainer();

		if (null === $container || !$container->has('request_stack'))
		{
			return null;
		}

		$request = $container->get('request_stack')->getCurrentRequest();

		if (null === $request || !$request->hasSession())
		{
			return null;
		}

		try
		{
			return $request->getSession()->getBag('contao_backend');
		}
		catch (\Exception $e)
		{
			return null;
		}
	}

	/**
	 * Ergänzt jede Zeile des Kategoriebaums um Symbol und Zahlen.
	 *
	 * Hinter dem Kategorietitel stehen die Anzahl der Links unmittelbar in der
	 * Kategorie, die Anzahl einschließlich aller Unterkategorien und der
	 * Anteil erreichbarer Links. Dessen Farbe wandert von Rot über Gelb nach
	 * Grün, damit vernachlässigte Kategorien ins Auge fallen.
	 *
	 * @param array              $row            Datensatz der Kategorie
	 * @param string             $label          Der von Contao vorbereitete Titel
	 * @param DataContainer|null $dc             Aufrufender Datencontainer
	 * @param string             $imageAttribute Zusätzliche Attribute für das Symbol
	 * @param bool               $blnReturnImage true liefert nur das Symbol zurück
	 * @param bool               $blnProtected   true erzwingt die Anzeige als geschützt
	 *
	 * @return string Die fertige Zeile als HTML
	 */
	public function addIcon($row, $label, ?DataContainer $dc = null, $imageAttribute = '', $blnReturnImage = false, $blnProtected = false): string
	{
		if ($blnProtected)
		{
			$row['protected'] = true;
		}

		// Kategorie-Symbol und dessen Attribute zuweisen
		$image = $row['published'] ? 'bundles/contaolinkscollection/images/category.png' : 'bundles/contaolinkscollection/images/category_inactive.png';
		$imageAttribute = trim($imageAttribute.' data-icon="bundles/contaolinkscollection/images/category.png" data-icon-disabled="bundles/contaolinkscollection/images/category_inactive.png"');

		if ($blnReturnImage)
		{
			return Image::getHtml($image, '', $imageAttribute);
		}

		// Kategorien der obersten Ebene hervorheben
		if (!$row['pid'])
		{
			$label = '<strong>'.$label.'</strong>';
		}

		$quote = (int) $row['links_checkquote'];

		// Farbverlauf von Rot über Gelb nach Grün in Zehnerschritten
		$farben = array(10 => '#ff0000', 19 => '#ff2800', 28 => '#ff5000', 37 => '#ff7800', 46 => '#ffa000', 55 => '#ffd200', 64 => '#d8d32d', 73 => '#c4d62c', 82 => '#7fdf26', 91 => '#39e821');
		$farbe = '#04c400';

		foreach ($farben as $grenze => $wert)
		{
			if ($quote <= $grenze)
			{
				$farbe = $wert;
				break;
			}
		}

		$checkquote = '<span style="color:'.$farbe.';">'.$quote.'%</span> Links in Ordnung';

		return Image::getHtml($image, '', $imageAttribute)
			.'<a href="'.Controller::addToUrl('node='.$row['id']).'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectNode'] ?? '').'"> '.$label.'</a> '
			.'(<b>'.$row['links_self'].'</b>/'.$row['links_all'].' - '.$checkquote.')';
	}

	/**
	 * Erzeugt beim Speichern einen Alias, wenn keiner eingetragen wurde.
	 *
	 * @param mixed         $varValue Der eingegebene Alias, in der Regel leer
	 * @param DataContainer $dc       Aufrufender Datencontainer; liefert den
	 *                                Titel, aus dem der Alias entsteht
	 *
	 * @return string Der gespeicherte Alias
	 *
	 * @throws \Exception Wenn ein von Hand eingegebener Alias bereits vergeben ist
	 */
	public function generateAlias($varValue, DataContainer $dc): string
	{
		$autoAlias = false;

		if (!$varValue)
		{
			$autoAlias = true;

			// Optionen für die Aliasgenerierung setzen
			// Siehe: https://github.com/ausi/slug-generator/blob/master/README.md
			$slugOptionen = (object) array
			(
				'setValidChars' => 'a-z0-9',
				'setLocale'     => 'de',
				'setDelimiter'  => '-'
			);

			$varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->title, $slugOptionen);
		}

		$objAlias = Database::getInstance()->prepare('SELECT id FROM tl_linkscollection WHERE id=? OR alias=?')
		                                   ->execute($dc->id, $varValue);

		// Prüfen, ob der Alias bereits vergeben ist
		if ($objAlias->numRows > 1)
		{
			if (!$autoAlias)
			{
				throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'] ?? 'Der Alias "%s" ist bereits vergeben.', $varValue));
			}

			$varValue .= '-'.$dc->id;
		}

		return (string) $varValue;
	}
}
