<?php

/* ----------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/CollectionAttributeValue :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2009 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__ . '/ca/Attributes/Values/IAttributeValue.php');
require_once(__CA_LIB_DIR__ . '/ca/Attributes/Values/AttributeValue.php');

global $_ca_attribute_settings;

$_ca_attribute_settings['CollectionAttributeValue'] = array(
    'fieldWidth' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_FIELD,
        'default' => 40,
        'width' => 5, 'height' => 1,
        'label' => _t('Width of field in user interface'),
        'description' => _t('Width, in characters, of the field when displayed in a user interface.')
    ),
    'doesNotTakeLocale' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 1,
        'width' => 1, 'height' => 1,
        'label' => _t('Does not use locale setting'),
        'description' => _t('Check this option if you don\'t want your field values to be locale-specific. (The default is to not be.)')
    ),
    'canBeUsedInSort' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 1,
        'width' => 1, 'height' => 1,
        'label' => _t('Can be used for sorting'),
        'description' => _t('Check this option if this attribute value can be used for sorting of search results. (The default is to be.)')
    ),
    'canBeEmpty' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 1,
        'width' => 1, 'height' => 1,
        'label' => _t('Can be empty'),
        'description' => _t('Check this option if you want to allow empty attribute values. This - of course - only makes sense if you bundle several elements in a container.')
    ),
    'restrictToCollectionTypeIdno' => array(
        'formatType' => FT_TEXT,
        'displayType' => DT_FIELD,
        'default' => '',
        'width' => 50, 'height' => 1,
        'label' => _t('Collection type restriction'),
        'description' => _t('Insert idno of a collection type here to restrict the lookup mechanism to that type. (The default is empty.)')
    ),
    'copyValueToId' => array(
        'formatType' => FT_TEXT,
        'displayType' => DT_FIELD,
        'default' => '',
        'width' => 50, 'height' => 1,
        'label' => _t('Id of the field to copy the selection to'),
        'description' => _t('Insert DOM id where you want the id of the selected collection to be copied to. This is used for the Multipart ID Numbering.')
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
		)
);

class CollectionAttributeValue extends AttributeValue implements IAttributeValue {
    # ------------------------------------------------------------------

    private $opn_collection_id;
    private $ops_text;
    # ------------------------------------------------------------------

    public function __construct($pa_value_array=null) {
        parent::__construct($pa_value_array);
    }

    # ------------------------------------------------------------------

    public function loadTypeSpecificValueFromRow($pa_value_array) {
        $this->opn_collection_id = $pa_value_array['value_integer1'];
        $this->ops_text = $pa_value_array['value_longtext1'];
    }

    # ------------------------------------------------------------------

    public function getDisplayValue($pa_options=null) {
        return $this->ops_text;
    }

    # ------------------------------------------------------------------

    public function getCollectionID() {
        return $this->opn_collection_id;
    }

    # ------------------------------------------------------------------

    public function parseValue($ps_value, $pa_element_info) {
        $va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('canBeEmpty'));

        $ps_value = trim(preg_replace("![\t\n\r]+!", ' ', $ps_value));

        if (!$ps_value) {
            if (intval($va_settings["canBeEmpty"]) != 1) {
                $this->postError(1970, _t('Entry was blank.'), 'CollectionAttributeValue->parseValue()');
                return false;
            }
            return array();
        }
        else {
            $va_tmp = explode('|', $ps_value);
            if ($va_tmp[1]) {
                return array(
                    'value_longtext1' => trim($va_tmp[0]),
                    'value_integer1' => $va_tmp[1],
                );
            }
            else {
                if (!$va_settings["canBeEmpty"]) {
                    $this->postError(1970, _t('Entry was blank.'), 'CollectionAttributeValue->parseValue()');
                    return false;
                }
                return array();
            }
        }
    }

    # ------------------------------------------------------------------

    public function htmlFormElement($pa_element_info, $pa_options=null) {
        $o_config = Configuration::load();

        JavascriptLoadManager::register('autocomplete');

        $va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'restrictToCollectionTypeIdno', 'copyValueToId'));

        $vs_element = //'<div id="coll_'.$pa_element_info['element_id'].'_input{n}">'.
                caHTMLTextInput(
                        '{fieldNamePrefix}' . $pa_element_info['element_id'] . '_autocomplete{n}',
                        array(
                            'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
                            'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : 1,
                            'value' => '{{' . $pa_element_info['element_id'] . '}}',
                            'maxlength' => 512,
                            'id' => "coll_" . $pa_element_info['element_id'] . "_autocomplete{n}"
                        )
                ) .
                caHTMLHiddenInput(
                        '{fieldNamePrefix}' . $pa_element_info['element_id'] . '_{n}',
                        array(
                            'value' => '{{' . $pa_element_info['element_id'] . '}}',
                            'id' => '{fieldNamePrefix}' . $pa_element_info['element_id'] . '_{n}'
                        )
        );

        if ($pa_options['po_request']) {
            if ($va_settings['restrictToCollectionTypeIdno'] && $va_settings['restrictToCollectionTypeIdno'] != '') {
                $va_params = array("type" => $va_settings['restrictToCollectionTypeIdno']);
            }
            else {
                $va_params = null;
            }
            $vs_url = caNavUrl($pa_options['po_request'], 'lookup', 'Collection', 'Get', $va_params);
        }
        else {
            // hardcoded default for testing.
            $vs_url = '/index.php/lookup/Collection/Get';
        }

        $js_on_result = '';
        if ($va_settings['copyValueToId'] && $va_settings['copyValueToId'] != '') {
            $js_on_result = "jQuery('#" . $va_settings['copyValueToId'] . "').val(data[1]);";
        }

        $vs_element .= " <a href='#' style='display: none;' id='{fieldNamePrefix}" . $pa_element_info['element_id'] . "_link{n}' target='_coll_details'>" . _t("More") . "</a>";

        $vs_element .= "
			<script type='text/javascript'>
				jQuery(document).ready(function() {
					jQuery('#coll_" . $pa_element_info['element_id'] . "_autocomplete{n}').autocomplete('" . $vs_url . "', { max: 50, minChars: 3, matchSubset: 1, matchContains: 1, delay: 800});
					jQuery('#coll_" . $pa_element_info['element_id'] . "_autocomplete{n}').result(function(event, data, formatted) {
							jQuery('#{fieldNamePrefix}" . $pa_element_info['element_id'] . "_{n}').val(data[0] + '|' + data[1]);
							" . $js_on_result . "
						}
					);
				});
			</script>
		";
        return $vs_element;
    }

    # ------------------------------------------------------------------

    public function getAvailableSettings() {
        global $_ca_attribute_settings;

        return $_ca_attribute_settings['CollectionAttributeValue'];
    }

    # ------------------------------------------------------------------
}
?>