<?php

/* ----------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/KeywordsAttributeValue.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__ . '/core/Configuration.php');
require_once(__CA_LIB_DIR__ . '/ca/Attributes/Values/IAttributeValue.php');
require_once(__CA_LIB_DIR__ . '/ca/Attributes/Values/AttributeValue.php');
require_once(__CA_LIB_DIR__ . '/core/Configuration.php');
require_once(__CA_LIB_DIR__ . '/core/BaseModel.php'); // we use the BaseModel field type (FT_*) and display type (DT_*) constants

global $_ca_attribute_settings;

$_ca_attribute_settings['KeywordsAttributeValue'] = array(// global
    'fieldWidth' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_FIELD,
        'default' => 50,
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
    'keyWordType' => array(
        'formatType' => FT_TEXT,
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
        'description' => _t('Check this option if you don\'t want your LCSH values to be locale-specific. (The default is to not be.)')
    ),
    'canBeUsedInSort' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 0,
        'width' => 1, 'height' => 1,
        'label' => _t('Can be used for sorting'),
        'description' => _t('Check this option if this attribute value can be used for sorting of search results. (The default is not to be.)')
    )
);

class KeywordsAttributeValue extends AttributeValue implements IAttributeValue {
    # ------------------------------------------------------------------

    private $ops_text_value;
    private $ops_uri_value;
    # ------------------------------------------------------------------

    public function __construct($pa_value_array=null) {
        parent::__construct($pa_value_array);
    }

    # ------------------------------------------------------------------

    public function loadTypeSpecificValueFromRow($pa_value_array) {
        $this->ops_text_value = $pa_value_array['value_longtext1'];
        $this->ops_uri_value = $pa_value_array['value_longtext2'];
    }

    # ------------------------------------------------------------------

    public function getDisplayValue() {
        return $this->ops_text_value;
    }

    # ------------------------------------------------------------------

    public function getTextValue() {
        return $this->ops_text_value;
    }

    # ------------------------------------------------------------------

    public function getUri() {
        return $this->ops_uri_value;
    }

    # ------------------------------------------------------------------

    public function parseValue($ps_value, $pa_element_info) {
        $o_config = Configuration::load();

        $va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('keyWordType'));

        if (trim($ps_value)) {

            $va_tmp1 = explode('/', $va_tmp[1]);
            $vs_id = array_pop($va_tmp1);
            return array(
                'value_longtext1' => $ps_value,
                'source_info' => $va_settings['keyWordType']
            );
        }
        return array('value_longtext1' => '');
    }

    # ------------------------------------------------------------------

    public function htmlFormElement($pa_element_info, $pa_options=null) {
        $o_config = Configuration::load();
        $va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'fieldHeight', 'keyWordType'));

        $vs_element = '<div id="keyword_' . $pa_element_info['element_id'] . '_input{n}">' .
                caHTMLTextInput(
                        '{fieldNamePrefix}' . $pa_element_info['element_id'] . '_autocomplete{n}',
                        array(
                            'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
                            'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : $va_settings['fieldHeight'],
                            'value' => '{{' . $pa_element_info['element_id'] . '}}',
                            'maxlength' => 512,
                            'id' => "keyword_" . $pa_element_info['element_id'] . "_autocomplete{n}",
                        )
                ) .
                /* caHTMLHiddenInput(
                  '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}_type',
                  array(
                  'value' => $va_settings['keyWordType'],
                  'id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}_type'
                  )
                  ). */
                caHTMLHiddenInput(
                        '{fieldNamePrefix}' . $pa_element_info['element_id'] . '_{n}',
                        array(
                            'value' => '{{' . $pa_element_info['element_id'] . '}}',
                            'id' => '{fieldNamePrefix}' . $pa_element_info['element_id'] . '_{n}'
                        )
        );

        $vs_url = caNavUrl($pa_options['po_request'], 'lookup', 'Keywords', 'Get') . '?type=' . $va_settings['keyWordType'];


        $vs_element .= " <a href='#' style='display: none;' id='{fieldNamePrefix}" . $pa_element_info['element_id'] . "_link{n}' target='_lcsh_details'>" . _t("More") . "</a>";

        $vs_element .= '</div>';
        $vs_element .= "
				<script type='text/javascript'>
					jQuery(document).ready(function() {
						jQuery('#keyword_" . $pa_element_info['element_id'] . "_autocomplete{n}').autocomplete('" . $vs_url . "', {minChars: 3, matchSubset: 1, matchContains: 1, delay: 800});
						jQuery('#keyword_" . $pa_element_info['element_id'] . "_autocomplete{n}').result(function(event, data, formatted) {
								jQuery('#{fieldNamePrefix}" . $pa_element_info['element_id'] . "_{n}').val(data[0] );
							}
						);
						jQuery('#keyword_" . $pa_element_info['element_id'] . "_autocomplete{n}').keydown(function (e) {
							jQuery(this).stopTime('suggestKeyword').oneTime(500, 'suggestKeyword', function() {
								jQuery('#{fieldNamePrefix}" . $pa_element_info['element_id'] . "_{n}').val(jQuery(this).val());
							});
						});
					});

				</script>
			";
        return $vs_element;
    }

    # ------------------------------------------------------------------

    public function getAvailableSettings() {
        global $_ca_attribute_settings;

        return $_ca_attribute_settings['KeywordsAttributeValue'];
    }

    # ------------------------------------------------------------------
}
?>
