<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/CurrencyAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *
 * This source code is free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 	
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/IAttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/AttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/core/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants
 
 	global $_ca_attribute_settings;
 	
 	$_ca_attribute_settings['CurrencyAttributeValue'] = array(		// global
		'minValue' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 5, 'height' => 1,
			'default' => 0,
			'label' => _t('Minimum value'),
			'description' => _t('The minimum value allowed. Input less than the required value will be rejected.')
		),
		'maxValue' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 5, 'height' => 1,
			'default' => 0,
			'label' => _t('Maximum value'),
			'description' => _t('The maximum value allowed. Input greater than the required value will be rejected.')
		),
		'fieldWidth' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'default' => 40,
			'width' => 5, 'height' => 1,
			'label' => _t('Width of data entry field in user interface'),
			'description' => _t('Width, in characters, of the field when displayed in a user interface.')
		),
		'fieldHeight' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'default' => 1,
			'width' => 5, 'height' => 1,
			'label' => _t('Height of data entry field in user interface'),
			'description' => _t('Height, in characters, of the field when displayed in a user interface.')
		),
		'doesNotTakeLocale' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Does not use locale setting'),
			'description' => _t('Check this option if you don\'t want your currency values to be locale-specific. (The default is to not be.)')
		),
		'canBeUsedInSort' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used for sorting'),
			'description' => _t('Check this option if this attribute value can be used for sorting of search results. (The default is to be.)')
		),
		'canBeUsedInSearchForm' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used in search form'),
			'description' => _t('Check this option if this attribute value can be used in search forms. (The default is to be.)')
		),
		'canBeUsedInDisplay' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used in display'),
			'description' => _t('Check this option if this attribute value can be used for display in search results. (The default is to be.)')
		),
		'displayTemplate' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 4,
			'label' => _t('Display template'),
			'validForRootOnly' => 1,
			'description' => _t('Layout for value when used in a display (can include HTML). Element code tags prefixed with the ^ character can be used to represent the value in the template. For example: <i>^my_element_code</i>.')
		),
		'displayDelimiter' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => ',',
			'width' => 10, 'height' => 1,
			'label' => _t('Value delimiter'),
			'validForRootOnly' => 1,
			'description' => _t('Delimiter to use between multiple values when used in a display.')
		)
	);
 
	class CurrencyAttributeValue extends AttributeValue implements IAttributeValue {
 		# ------------------------------------------------------------------
 		private $ops_currency_specifier;
 		private $opn_value;
 		# ------------------------------------------------------------------
 		public function __construct($pa_value_array=null) {
 			parent::__construct($pa_value_array);
 		}
 		# ------------------------------------------------------------------
 		public function loadTypeSpecificValueFromRow($pa_value_array) {
 			$this->ops_currency_specifier = $pa_value_array['value_longtext1'];
 			$this->opn_value = $pa_value_array['value_decimal1'];
 		}
 		# ------------------------------------------------------------------
		public function getDisplayValue($pa_options=null) {
			return trim($this->ops_currency_specifier.' '.number_format($this->opn_value, 2));
		}
 		# ------------------------------------------------------------------
 		public function parseValue($ps_value, $pa_element_info) {
 			$ps_value = trim($ps_value);
 			$va_settings = $this->getSettingValuesFromElementArray(
 				$pa_element_info, 
 				array('minValue', 'maxValue')
 			);
 			
 			$ps_value = str_replace(',', '.', $ps_value);
 			$ps_value = str_replace(' ', '', $ps_value);
 			
 			if (preg_match("!^([^\d]+)(.*)$!", trim($ps_value), $va_matches)) {
 				$vs_currency_specifier = trim($va_matches[1]);
 				$vs_value = preg_replace('/[^\d\.\-]/', '', trim($va_matches[2]));
 			} else {
 				if (preg_match("!^([\d\.]+)([^\d]+)$!", trim($ps_value), $va_matches)) {
 					$vs_currency_specifier = trim($va_matches[2]);
 				$vs_value = preg_replace('/[^\d\.\-]/', '', trim($va_matches[1]));
 				} else {
 					$this->postError(1970, _t('%1 is not a valid currency value; be sure to include a currency symbol', $pa_element_info['displayLabel']), 'CurrencyAttributeValue->parseValue()');
					return false;
 				}
 			}
 				 				
			// TODO: take into account locale settings for decimal point and separator characters
			// Right now we hardcode in US standard: eg. $1,500,000.00 
			
			$vn_value = floatval($vs_value);
			
			switch($vs_currency_specifier) {
				case '$':
					$vs_currency_specifier = 'USD';
					break;
				case '¥':
					$vs_currency_specifier = 'JPY';
					break;
				case '£':
					$vs_currency_specifier = 'GBP';
					break;
				case '€':
					$vs_currency_specifier = 'EUR';
					break;
				default:
					$vs_currency_specifier = strtoupper($vs_currency_specifier);
					break;
			}
			
			if (strlen($vs_currency_specifier) != 3) {
				$this->postError(1970, _t('Currency specified for %1 does not appear to be valid', $pa_element_info['displayLabel']), 'CurrencyAttributeValue->parseValue()');
				return false;
			}
			
			if ($vn_value < 0) {
				$this->postError(1970, _t('%1 must not be negative', $pa_element_info['displayLabel']), 'CurrencyAttributeValue->parseValue()');
				return false;
			}
			if ($vn_value < floatval($va_settings['minValue'])) {
				// value is too low
				$this->postError(1970, _t('%1 must be at least %2', $pa_element_info['displayLabel'], $va_settings['minValue']), 'CurrencyAttributeValue->parseValue()');
				return false;
			}
			if ((floatval($va_settings['maxValue']) > 0) && ($vn_value > floatval($va_settings['maxValue']))) {
				// value is too high
				$this->postError(1970, _t('%1 must be less than %2', $pa_element_info['displayLabel'], $va_settings['maxValue']), 'CurrencyAttributeValue->parseValue()');
				return false;
			}
			
			return array(
				'value_longtext1' => $vs_currency_specifier,
				'value_decimal1' => $vn_value
			);
 		}
 		# ------------------------------------------------------------------
 		public function htmlFormElement($pa_element_info, $pa_options=null) {
 			$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'fieldHeight', 'minValue', 'maxValue'));
 			
 			// try to get currency conversion
 			
 			
 			return caHTMLTextInput(
 				'{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}', 
 				array(
 					'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
 					'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : $va_settings['fieldHeight'], 
 					'value' => '{{'.$pa_element_info['element_id'].'}}', 
 					'maxlength' => $va_settings['maxChars'],
 					'id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
					'class' => 'currencyBg'
 				)
 			);
 		}
 		# ------------------------------------------------------------------
 		public function getAvailableSettings() {
 			global $_ca_attribute_settings;
 			
 			return $_ca_attribute_settings['CurrencyAttributeValue'];
 		}
 		# ------------------------------------------------------------------
		/**
		 * Returns name of field in ca_attribute_values to use for sort operations
		 * 
		 * @return string Name of sort field
		 */
		public function sortField() {
			return 'value_decimal1';
		}
 		# ------------------------------------------------------------------
	}
 ?>