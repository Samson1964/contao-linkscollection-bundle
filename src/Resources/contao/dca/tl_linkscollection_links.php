<?php

declare(strict_types=1);

/**
 * Linksammlung für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Database;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Schachbulle\ContaoLinkscollectionBundle\Klassen\Filter;
use Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection;
use Schachbulle\ContaoLinkscollectionBundle\Klassen\Protokoll;

/**
 * Tabelle tl_linkscollection_links — die Links innerhalb einer Kategorie
 */
$GLOBALS['TL_DCA']['tl_linkscollection_links'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'                => DC_Table::class,
		'ptable'                       => 'tl_linkscollection',
		'enableVersioning'             => true,
		'sql' => array
		(
			'keys' => array
			(
				'id'                   => 'primary',
				'pid'                  => 'index',
				'title'                => 'index',
				'url'                  => 'index'
			)
		),
		'onsubmit_callback'            => array
		(
			array('tl_linkscollection_links', 'saveRecord')
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_PARENT,
			'fields'                  => array('title ASC'),
			'headerFields'            => array('title'),
			'panelLayout'             => 'search,limit',
			'disableGrouping'         => true,
			'child_record_callback'   => array('tl_linkscollection_links', 'listLinks'),
			'child_record_class'      => 'no_padding',
			'filter'                  => Filter::getCurrentFilterDefinition('tl_linkscollection_links'),
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
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			),
			'filter' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['extendedFilter'],
				'class'               => 'header_special_filter',
				'button_callback'     => array('tl_linkscollection_links', 'getFilterWidget')
			),
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['toggle'],
				'href'                => 'act=toggle&amp;field=published',
				'icon'                => 'visible.svg',
				'showInHeader'        => true,
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('protected'),
		'default'                     => '{infobox_legend},infobox;{title_legend},title,url,language,webarchiv,popular,newWindow,text;{history_legend:hide},history;{problem_legend},problem,problemdate,problemcount,warnings;{name_legend:hide},name,email;{hits_legend:hide},hits;{guests_legend:hide},ip,ipdate;{state_legend:hide},statecode,statetext,statedate;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;{published_legend},published,start,stop'
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
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		// Eintragsdatum, wird beim ersten Speichern gesetzt
		'initdate' => array
		(
			'save_callback'           => array
			(
				array('tl_linkscollection_links', 'saveInitdate')
			),
			'flag'                    => 5,
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'infobox' => array
		(
			'exclude'                 => true,
			'input_field_callback'    => array('tl_linkscollection_links', 'getInfobox')
		),
		'title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['title'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>128, 'tl_class'=>'long'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'url' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['url'],
			'exclude'                 => true,
			'default'                 => '',
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'tl_class'=>'w50', 'maxlength'=>255),
			'sql'                     => "varchar(255) NOT NULL default ''",
		),
		'language' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['language'],
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_linkscollection_links', 'getLaender'),
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(5) NOT NULL default ''"
		),
		// Link offline, aber noch auf archive.org verfügbar
		'webarchiv' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['webarchiv'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		// Das Feld "icon" ist entfallen: es stand in keiner Palette, wurde
		// nirgends ausgelesen und sein Label verwies auf eine Sprachvariable,
		// die es nicht gibt. Die Favicons kommen über Linkscollection::getFavicon()
		// aus dem Verzeichnis Resources/public/favicons.
		'text' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['text'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr'),
			'sql'                     => "mediumtext NULL"
		),
		// Populärer Link (wird vorn einsortiert)
		'popular' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['popular'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		// Link im neuen Fenster öffnen
		'newWindow' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['newWindow'],
			'exclude'                 => true,
			'default'                 => 1,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		// Name des Linkeinreichers
		'name' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['name'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		// E-Mail des Linkeinreichers
		'email' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['email'],
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'rgxp'=>'emails', 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'history' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['history'],
			'exclude'                 => true,
			'inputType'               => 'multiColumnWizard',
			'eval'                    => array
			(
				'tl_class'            => 'long clr',
				'buttonPos'           => 'top',
				'buttons'             => array
				(
					'up'              => false,
					'down'            => false
				),
				'columnFields'        => array
				(
					'date' => array
					(
						'label'       => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['history_date'],
						'exclude'     => true,
						'inputType'   => 'text',
						'eval'        => array
						(
							'style'             => 'width: 90%',
							'rgxp'              => 'date',
							'mandatory'         => false,
							'datepicker'        => true,
							'tl_class'          => 'w50 wizard'
						),
						'load_callback' => array
						(
							array('tl_linkscollection_links', 'loadDate')
						),
					),
					'url' => array
					(
						'label'                 => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['history_url'],
						'inputType'             => 'text',
						'eval'                  => array
						(
							'mandatory'         => false,
							'style'             => 'width: 90%',
						)
					),
				)
			),
			'sql'                   => "blob NULL"
		),
		// Anzahl der Zugriffe
		'hits' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['hits'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'rgxp'=>'numeric'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		// Letzter Besucher: IP
		'ip' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['ip'],
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		// Letzter Besucher: Datum
		'ipdate' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['ipdate'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'rgxp'=>'datim', 'tl_class'=>'w50'),
			'flag'                    => 5,
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		// Statuscode des Links
		'statecode' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['statecode'],
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
			'sql'                     => "varchar(3) NOT NULL default ''"
		),
		// Statustext des Links (optional)
		'statetext' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['statetext'],
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		// Zeitpunkt der letzten Prüfung
		'statedate' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['statedate'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'rgxp'=>'datim'),
			'flag'                    => 5,
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		// Verwendetes CMS
		'cms' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['cms'],
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		// Zeitpunkt der CMS-Erkennung
		'cmsdate' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['cmsdate'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'rgxp'=>'datim'),
			'flag'                    => 5,
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'problem' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['problem'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr'),
			'sql'                     => "mediumtext NULL"
		),
		'problemdate' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['problemdate'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50', 'rgxp'=>'datim'),
			'flag'                    => 5,
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'problemcount' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['problemcount'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50', 'rgxp'=>'numeric'),
			'sql'                     => "int(4) unsigned NOT NULL default '0'"
		),
		'warnings' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['warnings'],
			'exclude'                 => true,
			'inputType'               => 'multiColumnWizard',
			'eval'                    => array
			(
				'tl_class'            => 'long clr',
				'buttonPos'           => 'top',
				'buttons'             => array
				(
					'up'              => false,
					'down'            => false
				),
				'columnFields'        => array
				(
					'done' => array
					(
						'label'       => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['warning_done'],
						'exclude'     => true,
						'inputType'   => 'checkbox',
						'eval'        => array
						(
							'style'             => 'width: 20px',
							'valign'            => 'top',
							'columnPos'         => '1'
						)
					),
					'date' => array
					(
						'label'       => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['warning_date'],
						'exclude'     => true,
						'inputType'   => 'text',
						'eval'        => array
						(
							'style'             => 'width: 180px',
							'columnPos'         => '2'
						),
						'load_callback'         => array
						(
							array('tl_linkscollection_links', 'getDate')
						),
						'save_callback'         => array
						(
							array('tl_linkscollection_links', 'saveDate')
						)
					),
					'name' => array
					(
						'label'                 => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['warning_name'],
						'exclude'               => true,
						'inputType'             => 'text',
						'eval'                  => array
						(
							'style'             => 'width: 180px',
							'columnPos'         => '2'
						)
					),
					'newtitle' => array
					(
						'label'                 => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['warning_newtitle'],
						'exclude'               => true,
						'inputType'             => 'text',
						'eval'                  => array
						(
							'style'             => 'width: 180px',
							'columnPos'         => '2'
						)
					),
					'error' => array
					(
						'label'                 => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['warning_error'],
						'exclude'               => true,
						'inputType'             => 'text',
						'eval'                  => array
						(
							'style'             => 'width: 230px',
							'columnPos'         => '3'
						)
					),
					'email' => array
					(
						'label'                 => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['warning_email'],
						'inputType'             => 'text',
						'eval'                  => array
						(
							'mandatory'         => false,
							'style'             => 'width: 230px',
							'columnPos'         => '3'
						)
					),
					'newurl' => array
					(
						'label'                 => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['warning_newurl'],
						'inputType'             => 'text',
						'eval'                  => array
						(
							'mandatory'         => false,
							'style'             => 'width: 230px',
							'columnPos'         => '3'
						)
					),
					'comment' => array
					(
						'label'                 => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['warning_comment'],
						'exclude'               => true,
						'inputType'             => 'textarea',
						'eval'                  => array
						(
							'style'             => 'width: 350px; height: 100px',
							'valign'            => 'top',
							'cols'              => 80,
							'rows'              => 4,
							'columnPos'         => '4'
						)
					),
				)
			),
			'sql'                   => "blob NULL"
		),
		'protected' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['protected'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'groups' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['groups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),
		'guests' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['guests'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'cssID' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['cssID'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'space' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['space'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['published'],
			'exclude'                 => true,
			'toggle'                  => true,
			'inputType'               => 'checkbox',
			'default'                 => 1,
			'eval'                    => array('doNotCopy'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'start' => array
		(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['start'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'stop' => array
		(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['stop'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		)
	)
);

/**
 * Rückruffunktionen der Tabelle tl_linkscollection_links.
 */
class tl_linkscollection_links extends Backend
{
	/**
	 * Prüft nach dem Speichern die URL und schreibt abgeleitete Werte fort.
	 *
	 * Beim Speichern eines Links wird dessen Adresse sofort abgerufen: Das
	 * aktualisiert Statuscode, Statustext, Sprache und CMS und lädt das
	 * Favicon neu. Außerdem wird gezählt, wie viele Besuchermeldungen noch
	 * unerledigt sind — diese Zahl steht in der Kategorieliste.
	 *
	 * Nebenwirkung: greift auf die fremde Webseite zu und schreibt erneut in
	 * den gerade gespeicherten Datensatz.
	 *
	 * @param DataContainer $dc Aufrufender Datencontainer
	 *
	 * @return void
	 */
	public function saveRecord(DataContainer $dc): void
	{
		// Unerledigte Meldungen zählen
		$w = 0;
		$warnings = StringUtil::deserialize($dc->activeRecord->warnings ?? null);

		if (\is_array($warnings))
		{
			foreach ($warnings as $warning)
			{
				if (empty($warning['done']) && !empty($warning['date']))
				{
					++$w;
				}
			}
		}

		// URL prüfen und Favicon herunterladen
		$arrRow = array
		(
			'id'        => $dc->id,
			'webarchiv' => Input::post('webarchiv'),
			'url'       => Input::post('url')
		);
		$arrRow = Linkscollection::saveFavicon($arrRow);

		$set = array
		(
			'initdate'      => $dc->activeRecord->initdate ?: $dc->activeRecord->tstamp,
			'statedate'     => $arrRow['statedate'],
			'statecode'     => $arrRow['statecode'],
			'statetext'     => $arrRow['statetext'],
			'problemcount'  => $w
		);

		Database::getInstance()->prepare('UPDATE tl_linkscollection_links %s WHERE id=?')
		                       ->set($set)
		                       ->execute($dc->id);
	}

	/**
	 * Liefert die Länderliste für das Auswahlfeld "Sprache".
	 *
	 * Die Kürzel werden kleingeschrieben gespeichert, weil daraus die
	 * CSS-Klasse des Sprachfähnchens entsteht (flag-icon-de). Der Dienst
	 * contao.intl.countries liefert sie großgeschrieben; System::getCountries()
	 * hat diese Umwandlung früher übernommen, gibt es in Contao 5 aber nicht mehr.
	 *
	 * Der Name lautet bewusst nicht getCountries(): Diese Klasse erbt von
	 * Contao\Backend, und in Contao 4.13 gibt es dort noch die statische
	 * Methode System::getCountries() — eine gleichnamige Objektmethode wäre
	 * ein Compile-Fehler.
	 *
	 * @return array<string,string> Länderkürzel => Landesname
	 */
	public function getLaender(): array
	{
		$laender = System::getContainer()->get('contao.intl.countries')->getCountries();

		return array_combine(array_map('strtolower', array_keys($laender)), $laender);
	}

	/**
	 * Formatiert einen Zeitstempel für die Anzeige im Meldungs-Assistenten.
	 *
	 * @param mixed $arrValue Zeitstempel als Unixzeit
	 *
	 * @return string Datum und Uhrzeit, oder ein leerer String ohne Wert
	 */
	public function getDate($arrValue): string
	{
		return $arrValue ? date('d.m.Y H:i', (int) $arrValue) : '';
	}

	/**
	 * Wandelt die Eingabe im Meldungs-Assistenten zurück in einen Zeitstempel.
	 *
	 * @param mixed $arrValue Datum und Uhrzeit im Format TT.MM.JJJJ HH:MM
	 *
	 * @return int Die Unixzeit, oder 0 bei leerer oder unlesbarer Eingabe
	 */
	public function saveDate($arrValue): int
	{
		if (!$arrValue)
		{
			return 0;
		}

		$dtime = \DateTime::createFromFormat('d.m.Y H:i', (string) $arrValue);

		return $dtime ? $dtime->getTimestamp() : 0;
	}

	/**
	 * Erzeugt die Anzeige eines Links in der Kategorieliste.
	 *
	 * @param array $arrRow Datensatz des Links
	 *
	 * @return string Das HTML der Zeile
	 */
	public function listLinks($arrRow): string
	{
		return Linkscollection::ViewLinkrow($arrRow);
	}

	/**
	 * Erzeugt den Informationskasten über dem Bearbeitungsformular.
	 *
	 * Er zeigt, in welcher Kategorie der Link liegt (mit Verweisen dorthin)
	 * und bietet einen Verweis auf die Zieladresse in einem neuen Fenster.
	 *
	 * @param DataContainer $dc Aufrufender Datencontainer
	 *
	 * @return string Das HTML des Kastens
	 */
	public function getInfobox(DataContainer $dc): string
	{
		$kategorie = Linkscollection::Breadcrumb($dc->activeRecord->pid ?? 0);
		$adresse = (string) ($dc->activeRecord->url ?? '');
		$url = \strlen($adresse) > 7 ? '<br><b>URL in neuem Fenster öffnen:</b> <a href="'.StringUtil::specialchars($adresse).'" target="_blank" rel="noreferrer noopener">'.StringUtil::specialchars($adresse).'</a>' : '';

		return '<div class="long" style="margin-left: 15px; margin-right: 15px;"><b>Kategorie:</b> '.$kategorie.$url.'</div>';
	}

	/**
	 * Setzt beim ersten Speichern das Eintragsdatum.
	 *
	 * Nebenwirkung: schreibt einen Eintrag in das Systemprotokoll, je nachdem
	 * ob ein Link neu angelegt oder geändert wurde.
	 *
	 * @param mixed $varValue Bisheriges Eintragsdatum als Unixzeit
	 *
	 * @return int Das Eintragsdatum; bei einem neuen Link der aktuelle Zeitpunkt
	 */
	public function saveInitdate($varValue): int
	{
		if (!$varValue)
		{
			Protokoll::schreiben('[Linkscollection] New Link created: '.Input::post('title').' ('.Input::post('url').')', __METHOD__, ContaoContext::GENERAL);

			return time();
		}

		Protokoll::schreiben('[Linkscollection] Link ID '.Input::get('id').' edited: '.Input::post('title').' ('.Input::post('url').')', __METHOD__, ContaoContext::ACCESS);

		return (int) $varValue;
	}

	/**
	 * Liefert die Bedienelemente des erweiterten Filters.
	 *
	 * Der Filter ist derzeit ausgeschaltet — siehe die Erläuterung in der
	 * Klasse Filter. Zum Einschalten genügt es, hier den Aufruf von
	 * Filter::getFilterWidget() zurückzugeben.
	 *
	 * @param string $href       Adresse der Schaltfläche
	 * @param string $label      Beschriftung
	 * @param string $title      Titel-Attribut
	 * @param string $class      CSS-Klasse
	 * @param string $attributes Weitere Attribute
	 *
	 * @return string Immer ein leerer String
	 */
	public function getFilterWidget($href, $label, $title, $class, $attributes): string
	{
		return '';
	}

	/**
	 * Setzt die Uhrzeit eines Datums im Assistenten auf 00:00:00.
	 *
	 * @param mixed $value Unixzeit des gespeicherten Datums
	 *
	 * @return int Unixzeit desselben Tages um Mitternacht
	 */
	public function loadDate($value): int
	{
		return (int) strtotime(date('d.m.Y', (int) $value).' 00:00:00');
	}
}
