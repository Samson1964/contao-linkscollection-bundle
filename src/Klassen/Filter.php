<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Cliff Parnitzky 2013-2014
 * @author     Cliff Parnitzky
 * @package    BackendExtendedCssEditorFilter
 * @license    LGPL
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace Schachbulle\ContaoLinkscollectionBundle\Klassen;

/**
 * Class CssEditorFilter
 *
 * Provide methods use special filter
 * @copyright  Cliff Parnitzky 2013-2014
 * @author     Cliff Parnitzky
 * @package    BackendExtendedCssEditorFilter
 */
class Filter extends \Backend
{
	const CSS_FILTER_PARAM_FIELD_NAME = 'FilterField';
	const CSS_FILTER_PARAM_VALUE_NAME = 'FilterValue';
	
	/**
	 * Returns the widget for the css filter
	 */
	public static function getFilterWidget($url, $label, $strTable)
	{
		$url = self::removeUrlParams($url, self::CSS_FILTER_PARAM_FIELD_NAME);
		$url = self::removeUrlParams($url, self::CSS_FILTER_PARAM_VALUE_NAME);
		
		$actFilterField = \Input::get(self::CSS_FILTER_PARAM_FIELD_NAME);
		$actFilterValue = urldecode(\Input::get(self::CSS_FILTER_PARAM_VALUE_NAME));
		
		$returnString  = '<div class="tl_subpanel tl_search" style="clear: both; margin-bottom: 15px; float: none; margin-top: 10px;">';
		$returnString .= '<strong>' . $label . '</strong>';
		$returnString .= '<select id="' . self::CSS_FILTER_PARAM_FIELD_NAME . '" style="width: 90px;" class="tl_select">';
		$returnString .= '<option value="">---</option>';
		foreach ($GLOBALS[strtoupper($strTable) . '_FILTER'] as $filter=>$definition)
		{
			$returnString .= '<option value="' . $filter . '"' . ($actFilterField == $filter ? ' selected="selected"' : '') . '>' . $GLOBALS['TL_LANG'][$strTable]['extendedFilterOption'][$filter] . '</option>';
		}
		$returnString .= '</select>';
		$returnString .= '<span> = </span>';
		$returnString .= '<input type="text" value="' . $actFilterValue . '" class="tl_text" id="' . self::CSS_FILTER_PARAM_VALUE_NAME . '" onchange="location.href = \'' . $url . '&amp;' . self::CSS_FILTER_PARAM_FIELD_NAME . '=\' + document.getElementById(\'' . self::CSS_FILTER_PARAM_FIELD_NAME . '\').options[document.getElementById(\'' . self::CSS_FILTER_PARAM_FIELD_NAME . '\').options.selectedIndex].value + \'&' . self::CSS_FILTER_PARAM_VALUE_NAME . '=\' + encodeURIComponent(document.getElementById(\'' . self::CSS_FILTER_PARAM_VALUE_NAME . '\').value) + \'\';">';
		$returnString .= '</div>';

		return $returnString;
	}
	
	/**
	 * Remove the given param from the url
	 */
	private static function removeUrlParams ($url, $strParamName)
	{
		$arrUrlComponents = parse_url($url);
		
		$arrQuery = explode('&amp;', $arrUrlComponents['query']);
		foreach ($arrQuery as $key => $query)
		{
			if (strpos($query, $strParamName) !== FALSE)
			{
				unset($arrQuery[$key]);
			}
		}
		
		return $arrUrlComponents['path'] . "?" . implode('&amp;', $arrQuery);
	}
	
	/**
	 * Returns the current style sheet filter definition
	 */
	public static function getCurrentFilterDefinition($strTable)
	{
		$arrFilterDefinitions = array();
		
		// adding special filter definitions
		$filterDefinition = $GLOBALS[strtoupper($strTable) . '_FILTER'][\Input::get(self::CSS_FILTER_PARAM_FIELD_NAME)];
		$value = str_replace('&#35;', '#', rawurldecode(\Input::get(self::CSS_FILTER_PARAM_VALUE_NAME)));
		$value = str_replace('*', '%', $value);
		
		if (strlen($filterDefinition) > 0 && strlen($value) > 0)
		{
			$arrFilterDefinitions[] = array($filterDefinition, $value);
		}
		
		return $arrFilterDefinitions;
	}
}

?>