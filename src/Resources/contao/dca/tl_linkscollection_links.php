<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2013 Leo Feyer
 *
 * @package Core
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

/**
 * Table tl_linkscollection_links
 */
$GLOBALS['TL_DCA']['tl_linkscollection_links'] = array
(

		// Config
		'config' => array
		(
			'dataContainer'            => 'Table',
			'ptable'                   => 'tl_linkscollection',
			'enableVersioning'         => true,
			'sql' => array
			(
				'keys' => array
				(
					'id'               => 'primary',
					'pid'              => 'index',
					'title'            => 'index',
					'url'              => 'index'
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
			'mode'                    => 4,
			'fields'                  => array('title ASC'),
			'headerFields'            => array('title'),
			'panelLayout'             => 'search,limit',
			//'root'                    => array(194),
			'disableGrouping'         => true,
			'child_record_callback'   => array('tl_linkscollection_links', 'listLinks'),
			'child_record_class'      => 'no_padding',
			'filter'                  => \Schachbulle\ContaoLinkscollectionBundle\Klassen\Filter::getCurrentFilterDefinition('tl_linkscollection_links'),
			//'rootPaste'               => false
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
				'icon'                => 'edit.gif'
			),
			//'copy' => array
			//(
			//    'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['copy'],
			//    'href'                => 'act=paste&mode=copy',
			//    'icon'                => 'copy.gif'
			//),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['cut'],
				'href'                => 'act=paste&mode=cut',
				'icon'                => 'cut.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['toggle'],
				'attributes'           => 'onclick="Backend.getScrollOffset()"',
				'haste_ajax_operation' => array
				(
					'field'            => 'published',
					'options'          => array
					(
						array('value' => '', 'icon' => 'invisible.svg'),
						array('value' => '1', 'icon' => 'visible.svg'),
					),
				),
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('protected'),
		'default'                     => '{infobox_legend},infobox;{title_legend},title,url,language,webarchiv,popular,newWindow,text;{problem_legend},problem,problemdate,problemcount,warnings;{name_legend:hide},name,email;{hits_legend:hide},hits;{guests_legend:hide},ip,ipdate;{state_legend:hide},statecode,statetext,statedate;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;{published_legend},published,start,stop'
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
			'options'                 => System::getCountries(),
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(5) NOT NULL default ''"
		),
		// Link offline, aber auf www.archive.org
		'webarchiv' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['webarchiv'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'icon' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection']['icon'],
			'exclude'                 => true,
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array
			(
				'files'               => true,
				'fieldType'           => 'radio',
				'filesOnly'           => true,
				'extensions'          => 'jpg,jpeg,png,gif,ico'
			),
			'sql'                     => "binary(16) NULL"
		),
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
		// Email des Linkeinreichers
		'email' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['email'],
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'rgxp'=>'emails', 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
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
		// Statusdatum
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
		// Datum der CMS-Prüfung
		'cmsdate' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linkscollection_links']['cmsdate'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'rgxp'=>'dateim'),
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
 * Provide miscellaneous methods that are used by the data configuration array
 */
class tl_linkscollection_links extends Backend
{

	var $oldurl; // Speichert die alte URL

	/**
	 * Beim Speichern eines Datensatzes zusätzliche Änderungen vornehmen
	 * @param DataContainer
	 * @return -
	 */
	public function saveRecord(DataContainer $dc)
	{

		// Unerledigte Warnungen zählen
		$w = 0;
		if($dc->activeRecord->warnings)
		{
			// $dc->activeRecord->warnings ggfs. umwandeln
			$warnings = (is_array($dc->activeRecord->warnings)) ? $dc->activeRecord->warnings : unserialize($dc->activeRecord->warnings);
			foreach($warnings as $warning)
			{
				if(!$warning['done'] && $warning['date']) $w++;
			}
		}

		// URL neu prüfen und Favicon downloaden
		$arrRow = array
		(
			'id'        => $dc->id,
			'webarchiv' => \Input::post('webarchiv'),
			'url'       => \Input::post('url')
		);
		$arrRow = \Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection::saveFavicon($arrRow);

		// Update Datenbank
		$set = array
		(
			'initdate'      => $dc->activeRecord->initdate ? $dc->activeRecord->initdate : $dc->activeRecord->tstamp,
			'statedate'     => $arrRow['statedate'],
			'statecode'     => $arrRow['statecode'] ? $arrRow['statecode'] : '',
			'statetext'     => $arrRow['statetext'] ? $arrRow['statetext'] : '',
			'problemcount'  => $w
		);
		$this->Database->prepare("UPDATE tl_linkscollection_links %s WHERE id=?")
		               ->set($set)
		               ->execute($dc->id);
	}

	public function getDate($arrValue)
	{
		return ($arrValue) ? date("d.m.Y H:i", $arrValue) : '';
	}

	public function saveDate($arrValue)
	{
		if($arrValue)
		{
			$dtime = \DateTime::createFromFormat("d.m.Y H:i", $arrValue);
			return $dtime->getTimestamp();
		}
		else return 0;
	}

	/**
	 * Generiere eine Zeile als HTML
	 * @param array
	 * @return string
	 */
	public function listLinks($arrRow)
	{

		return \Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection::ViewLinkrow($arrRow);

	}

	public function getInfobox(DataContainer $dc)
	{
		$kategorie = \Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection::Breadcrumb($dc->activeRecord->pid);
		$url = strlen($dc->activeRecord->url) > 7 ? '<br><b>URL in neuem Fenster öffnen:</b> <a href="'.$dc->activeRecord->url.'" target="_blank">'.$dc->activeRecord->url.'</a>' : '';

		return '<div class="long" style="margin-left: 15px; margin-right: 15px;"><b>Kategorie:</b> '.$kategorie.$url.'</div>';
	}

	/**
	 * Eintragsdatum schreiben
	 * @param mixed
	 * @return mixed
	 */
	public function saveInitdate($varValue)
	{
		if(!$varValue)
		{
			\System::log('[Linkscollection] New Link created: '.\Input::post('title').' ('.\Input::post('url').')', __CLASS__.'::'.__FUNCTION__, TL_CRON);
			return time();
		}
		else
		{
			\System::log('[Linkscollection] Link ID '.\Input::post('id').' edited: '.\Input::post('title').' ('.\Input::post('url').')', __CLASS__.'::'.__FUNCTION__, TL_ACCESS);
			return $varValue;
		}
	}

	/**
	 * Returning the drop down for the special filter
	 */
	public function getFilterWidget($href, $label, $title, $class, $attributes)
	{
		//return BackendLinkscollectionFilter\Filter::getFilterWidget($this->addToUrl(''), $label, 'tl_linkscollection_links');
	}

}

