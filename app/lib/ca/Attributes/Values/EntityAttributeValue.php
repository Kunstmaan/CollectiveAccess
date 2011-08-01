<?php

/* ----------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/EntityAttributeValue :
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

$_ca_attribute_settings['EntityAttributeValue'] = array(
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
    'restrictToEntityTypeIdno' => array(
        'formatType' => FT_TEXT,
        'displayType' => DT_FIELD,
        'default' => '',
        'width' => 50, 'height' => 1,
        'label' => _t('Entity type restriction'),
        'description' => _t('Insert idno of a entity type here to restrict the lookup mechanism to that type. (The default is empty.)')
    ),
    'quickAddItemType' => array(
        'formatType' => FT_TEXT,
        'displayType' => DT_FIELD,
        'default' => '',
        'width' => 50, 'height' => 1,
        'label' => _t('Preferred Entity type'),
        'description' => _t('The preferred entity type that can be created on the fly.')
    )
);

class EntityAttributeValue extends AttributeValue implements IAttributeValue {
    # ------------------------------------------------------------------

    private $opn_entity_id;
    private $ops_text;
    # ------------------------------------------------------------------

    public function __construct($pa_value_array=null) {
        parent::__construct($pa_value_array);
    }

    # ------------------------------------------------------------------

    public function loadTypeSpecificValueFromRow($pa_value_array) {
        $this->opn_entity_id = $pa_value_array['value_integer1'];
        $this->ops_text = $pa_value_array['value_longtext1'];
    }

    # ------------------------------------------------------------------

    public function getDisplayValue($pa_options=null) {
        return $this->ops_text;
    }

    # ------------------------------------------------------------------

    public function getEntityID() {
        return $this->opn_entity_id;
    }

    # ------------------------------------------------------------------

    public function parseValue($ps_value, $pa_element_info) {
        $va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('canBeEmpty'));

        $ps_value = trim(preg_replace("![\t\n\r]+!", ' ', $ps_value));
        if (!$ps_value) {
            if (intval($va_settings["canBeEmpty"]) != 1) {
                $this->postError(1970, _t('Entry was blank.'), 'EntityAttributeValue->parseValue()');
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
                    $this->postError(1970, _t('Entry was blank.'), 'EntityAttributeValue->parseValue()');
                    return false;
                }
                return array();
            }
        }
    }

    # ------------------------------------------------------------------

    public function htmlFormElement($pa_element_info, $pa_options=null) {
        $o_config = Configuration::load();

        JavascriptLoadManager::register('entityAttr');
        $va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'quickAddItemType', 'restrictToEntityTypeIdno'));
        $vs_element = //'<div id="entity_'.$pa_element_info['element_id'].'_input{n}">'.
                caHTMLTextInput(
                        '{fieldNamePrefix}' . $pa_element_info['element_id'] . '_autocomplete{n}',
                        array(
                            'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
                            'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : 1,
                            'value' => '{{' . $pa_element_info['element_id'] . '}}',
                            'maxlength' => 512,
                            'id' => "entity_" . $pa_element_info['element_id'] . "_autocomplete{n}"
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
            if ($va_settings['restrictToEntityTypeIdno'] && $va_settings['restrictToEntityTypeIdno'] != '') {
                $va_params = array("type" => $va_settings['restrictToEntityTypeIdno']);
            }
            else {
                $va_params = null;
            }
            $vs_url = caNavUrl($pa_options['po_request'], 'lookup', 'Entity', 'Get', $va_params);
        }
        else {
            // hardcoded default for testing.
            $vs_url = '/index.php/lookup/Entity/Get';
        }


        $va_itemid = 0;
        if ($item_code = $va_settings['quickAddItemType']) {
            $va_itemid = $this->getItemIDByIDNo($item_code, $pa_options['t_subject']);
        }
        if ($va_itemid < 1) {
            // TODO Create a dropdown for this
            $va_itemid = 71;
        }

        $vs_overlay_url = "/providence/index.php/editor/entities/EntityEditor/Edit/type_id/" . $va_itemid; //caNavUrl($this->request, 'editor/entities', 'EntityEditor', 'Edit', array("type_id" => 70));
        $vs_element .= " <a href='#' style='display: none;' id='{fieldNamePrefix}" . $pa_element_info['element_id'] . "_link{n}' target='_entity_details'>" . _t("More") . "</a>";
        $vs_element .= " <a href='$vs_overlay_url' style='display: none;' id='entity_" . $pa_element_info['element_id'] . "_newentitylink{n}' rel='#onthefly_overlay'>" . _t("New entity") . "</a>";

        $vs_element .= "
			<script type='text/javascript'>
				jQuery(document).ready(function() {
					jQuery('#entity_" . $pa_element_info['element_id'] . "_autocomplete{n}').autocomplete('" . $vs_url . "', { max: 50, minChars: 3, matchSubset: 1, matchContains: 1, delay: 800});
					jQuery('#entity_" . $pa_element_info['element_id'] . "_autocomplete{n}').result(function(event, data, formatted) {
							jQuery('#{fieldNamePrefix}" . $pa_element_info['element_id'] . "_{n}').val(data[0] + '|' + data[1]);
						}
					);
					var id = '{n}';
					var overlayoptions = {
						fieldNamePrefix: 'entity_" . $pa_element_info['element_id'] . "_',
						inputid : 'autocomplete',
						linkid : 'newentitylink',
						textfieldname : 'displayname',
						identifierid : 'idno_entity_number',
						idfieldname : 'entity_id'
					}
					initializeOnTheFlyOverlay(overlayoptions);
					addNewOnTheFlyOverlay(id, overlayoptions);
				});
			</script>
		";
        return $vs_element;
    }

    # ------------------------------------------------------------------
    // inline function
    private function getItemIDByIDNo($item_code, $t_subject) {
        $ca_list_items = $t_subject->getAppDatamodel()->getTableInstance('ca_list_items');
        $o_db = $t_subject->getDb();
        $qr_res = $o_db->query("SELECT item_id FROM ca_list_items WHERE idno = ?", $item_code);
        if ($qr_res->nextRow()) {
            $itemid = $qr_res->get('item_id');
            return $itemid;
        }
        return 0;
    }

    # ------------------------------------------------------------------

    public function getAvailableSettings() {
        global $_ca_attribute_settings;

        return $_ca_attribute_settings['EntityAttributeValue'];
    }

    # ------------------------------------------------------------------
}
?>