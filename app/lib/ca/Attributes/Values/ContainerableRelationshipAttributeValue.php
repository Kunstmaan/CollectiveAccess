<?php

/* ----------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/ContainerableRelationshipAttributeValue :
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

$_ca_attribute_settings['ContainerableRelationshipAttributeValue'] = array(
    'RelTable' => array(
        'formatType' => FT_TEXT,
        'displayType' => DT_FIELD,
        'default' => '',
        'width' => 5, 'height' => 1,
        'label' => _t('Relationship Table'),
        'description' => _t('Relationship Table - for example ca_objects_x_entities')
    ),
    'RelType' => array(
        'formatType' => FT_TEXT,
        'displayType' => DT_FIELD,
        'default' => '',
        'width' => 5, 'height' => 1,
        'label' => _t('Relationship Type'),
        'description' => _t('Relationship Type - an existing relationship type defined within the given relationship table')
    ),
    'RefOnly' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_FIELD,
        'default' => '0',
        'width' => 5, 'height' => 1,
        'label' => _t('Reference Only'),
        'description' => _t('Whether or not an autocomplete field is shown.')
    ),
    'CreateLink' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_FIELD,
        'default' => '0',
        'width' => 5, 'height' => 1,
        'label' => _t('Create Item Link'),
        'description' => _t('Whether or not a create item link should be set for this field.')
    ),
    'RightItemType' => array(
        'formatType' => FT_TEXT,
        'displayType' => DT_FIELD,
        'default' => '',
        'width' => 5, 'height' => 1,
        'label' => _t('Right Item Type'),
        'description' => _t('The type name for the right hand item - for example, individual is a typename for ca_entities')
    ),
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
    'restrictToItemTypeIdno' => array(
        'formatType' => FT_TEXT,
        'displayType' => DT_FIELD,
        'default' => '',
        'width' => 50, 'height' => 1,
        'label' => _t('Item type restriction'),
        'description' => _t('Insert idno of an item type here to restrict the lookup mechanism to that type. (The default is empty.)')
    ),
    'quickAddItemTypes' => array(
        'formatType' => FT_TEXT,
        'displayType' => DT_FIELD,
        'default' => '',
        'width' => 50, 'height' => 1,
        'label' => _t('Preferred type'),
        'description' => _t('The preferred item type that can be created on the fly.')
    ),
    'enableQuickAdd' => array(
        'formatType' => FT_NUMBER,
        'displayType' => DT_CHECKBOXES,
        'default' => 0,
        'width' => 1, 'height' => 1,
        'label' => _t('Enable overlay creations'),
        'description' => _t('Enable creating items on the fly.')
    )
);

class ContainerableRelationshipAttributeValue extends AttributeValue implements IAttributeValue {
    # ------------------------------------------------------------------

    private $opn_item_id;
    private $ops_text;
    # ------------------------------------------------------------------

    public function __construct($pa_value_array=null) {
        parent::__construct($pa_value_array);
    }
    # ------------------------------------------------------------------

    public function loadTypeSpecificValueFromRow($pa_value_array) {
        require_once(__CA_APP_DIR__ . '/models/ca_metadata_elements.php');
        // get settings
        $t_element = new ca_metadata_elements($pa_value_array['element_id']);
        $pa_element_info = $t_element->getFieldValuesArray();

        // get required settings out of array and convert
        $t_rel_table = $pa_element_info['settings']['RelTable'];
        require_once(__CA_APP_DIR__ . '/models/' . $t_rel_table . '.php');
        $vs_rel_table_instance = new $t_rel_table();
        $t_right_table = $vs_rel_table_instance->getRightTableName();
        $vs_pk = $vs_rel_table_instance->getRightTableFieldName();

        // get required data from settings by creating an instance
        require_once(__CA_APP_DIR__ . '/models/' . $t_right_table . '.php');
        $vs_right_table_instance = new $t_right_table();
        $vs_label_table_name = $vs_right_table_instance->getLabelTableName();
        $vs_label_display_field_name = $vs_right_table_instance->getLabelDisplayField();

        $o_db = new Db();
        $qr_res = $o_db->query("select b." . $vs_label_display_field_name . ",b." . $vs_pk . " from  " . $t_rel_table . " a inner join " . $vs_label_table_name . " b using(" . $vs_pk . ") where a.relation_id = ?", $pa_value_array['value_integer1']);
        if ($qr_res->nextRow()) {
            $this->ops_text = $qr_res->get($vs_label_display_field_name) . "|" . $qr_res->get($vs_pk) . "|" . $pa_value_array['value_integer1'];
        }
        $this->opn_item_id = $pa_value_array['value_integer1'];
    }
    # ------------------------------------------------------------------

    public function getDisplayValue($pa_options=null) {
        return $this->ops_text;
    }
    # ------------------------------------------------------------------

    public function parseValue($ps_value, $pa_element_info) {
        $va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('canBeEmpty'));
        $ps_value = trim(preg_replace("![\t\n\r]+!", ' ', $ps_value));
        if (!$ps_value) {
            if (intval($va_settings["canBeEmpty"]) != 1) {
                $this->postError(1970, _t('Entry was blank.'), 'ContainerableRelationshipAttributeValue->ParseValue()');
                return false;
            }
            return array();
        }
        else {
            return array(
                'value_longtext1' => $ps_value,
                'value_integer1' => $ps_value
            );
        }
    }
    # ------------------------------------------------------------------

    public function htmlFormElement($pa_element_info, $pa_options=null) {
        $o_config = Configuration::load();
        JavascriptLoadManager::register('ContainerableRelationshipAttr');
        $va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'quickAddItemTypes', 'RelType', 'restrictToItemTypeIdno', 'RelTable', 'enableQuickAdd'));

        $va_relationtype = $this->getRelationTypeByTypeCode($va_settings['RelType']);
        // get the right table name from relation table
        $t_rel_table = $pa_element_info['settings']['RelTable'];
        require_once(__CA_APP_DIR__ . '/models/' . $t_rel_table . '.php');
        $vs_rel_table_instance = new $t_rel_table();
        $t_right_table_name = $vs_rel_table_instance->getRightTableName();

        $vs_element =
                caHTMLTextInput(
                        '{fieldNamePrefix}' . $pa_element_info['element_id'] . '_autocomplete{n}',
                        array(
                            'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
                            'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : 1,
                            'value' => '{{' . $pa_element_info['element_id'] . '}}',
                            'maxlength' => 512,
                            'id' => "item_" . $pa_element_info['element_id'] . "_autocomplete{n}"
                        )
                ) .
                caHTMLHiddenInput(
                        '{fieldNamePrefix}' . $pa_element_info['element_id'] . '_{n}',
                        array(
                            'value' => '{{' . $pa_element_info['element_id'] . '}}',
                            'id' => '{fieldNamePrefix}' . $pa_element_info['element_id'] . '_{n}'
                        )
                ) .
                caHTMLHiddenInput(
                        '{fieldNamePrefix}' . $pa_element_info['element_id'] . '_relation{n}',
                        array(
                            'value' => $va_relationtype,
                            'id' => '{fieldNamePrefix}' . $pa_element_info['element_id'] . '_relation{n}'
                        )
                ) .
                caHTMLHiddenInput(
                        '{fieldNamePrefix}' . $pa_element_info['element_id'] . '_relationid{n}',
                        array(
                            'value' => '{{' . $pa_element_info['element_id'] . '}}',
                            'id' => '{fieldNamePrefix}' . $pa_element_info['element_id'] . '_relationid{n}'
                        )
        );

        if ($pa_options['po_request']) {
            if ($va_settings['restrictToItemTypeIdno'] && $va_settings['restrictToItemTypeIdno'] != '') {
                $va_params = array("type" => $va_settings['restrictToItemTypeIdno']);
            }
            else {
                $va_params = null;
            }

            $vs_url = caNavUrl($pa_options['po_request'], 'lookup', 'Relation', 'Get', array('element' => $pa_element_info['element_id'], 'item' => $t_right_table_name));
            // hack to check for vocabulary_terms
            if ($t_right_table_name == 'ca_list_items' && strpos($t_rel_table, "vocabulary_terms")) {
                $vs_url .= "?onlyvoc=true";
            }
        }
        else {
            // hardcoded default for testing.
            $vs_url = '/index.php/lookup/Entity/Get';
        }

        $arr_list_items = array();
        // get all possible type codes
        require_once(__CA_APP_DIR__ . '/models/' . $t_right_table_name . '.php');
        $vs_right_table_instance = new $t_right_table_name();
        $vs_pk = $vs_right_table_instance->primaryKey();
        $vs_singular_name = $vs_right_table_instance->getProperty('NAME_SINGULAR');

        $ol_listcode = $vs_right_table_instance->getTypeListCode();
        $t_list = new ca_lists();
        if ($t_list->load(array('list_code' => $ol_listcode))) {
            $arr_list_items = $t_list->getItemsForList($ol_listcode);
        }

        $arr_new_list_items = array(); // to prevent dificulties in javascript
        if ($quickAddItemTypes = $va_settings['quickAddItemTypes']) {
            foreach ($quickAddItemTypes as $item_code) {
                $arr_new_list_items[] = $arr_list_items[$this->getItemIDByIDNo($item_code, $pa_options['t_subject'])][1];
            }
        }
        else {
            foreach ($arr_list_items as $key => $value) {
                $arr_new_list_items[] = $arr_list_items[$key][1];
            }
        }

        $arr_overlay_url = caEditorUrl($pa_options['po_request'], $t_right_table_name, null, true);
        $vs_overlay_url = caNavUrl($pa_options['po_request'], $arr_overlay_url['module'], $arr_overlay_url['controller'], $arr_overlay_url['action']) . '/type_id/';

        $vs_label_display_field_name = $vs_right_table_instance->getLabelDisplayField();

        $vs_element .= " <span id='item_" . $pa_element_info['element_id'] . "_newitemlink{n}' style='display: none;'></span>";

        $vs_element .= "
			<script type='text/javascript'>
				jQuery(document).ready(function() {
					var values = jQuery('#{fieldNamePrefix}" . $pa_element_info['element_id'] . "_{n}').val().split('|');
						if(values.length>2){
							jQuery('#item_" . $pa_element_info['element_id'] . "_autocomplete{n}').val(values[0]);
							jQuery('#{fieldNamePrefix}" . $pa_element_info['element_id'] . "_relationid{n}').val(values[2]);
							jQuery('#{fieldNamePrefix}" . $pa_element_info['element_id'] . "_{n}').val(values[0]+'|'+values[1]);
						}
						jQuery('#item_" . $pa_element_info['element_id'] . "_autocomplete{n}').autocomplete('" . $vs_url . "', { max: 50, minChars: 3, matchSubset: 1, matchContains: 1, delay: 800});
						jQuery('#item_" . $pa_element_info['element_id'] . "_autocomplete{n}').result(function(event, data, formatted) {
								jQuery('#{fieldNamePrefix}" . $pa_element_info['element_id'] . "_{n}').val(data[0] + '|' + data[1]);
							}
						);
		";
        if ($va_settings['enableQuickAdd']) {
            $vs_element .= "
						var id = '{n}';
						var overlayoptions = {
							fieldNamePrefix: 'item_" . $pa_element_info['element_id'] . "_',
							inputid : 'autocomplete',
							linkid : 'newitemlink',
							textfieldname : '" . $vs_label_display_field_name . "',
							overlayurl : '" . $vs_overlay_url . "',
							idfieldname : '" . $vs_pk . "',
							availableTypes : " . json_encode($arr_new_list_items) . ",
							newtext : '" . _t("new %1", $vs_singular_name) . "'
						}
						initializeOnTheFlyOverlay(overlayoptions);
						addNewOnTheFlyOverlay(id, overlayoptions);
			";
        }
        $vs_element .= "
						var deletebuttonclass = 'caDeleteItemButton';
						var delfunction = null;
						var FormContainerID = '{fieldNamePrefix}'.substring(0,'{fieldNamePrefix}'.length-1);
						var FormItemID = FormContainerID + 'Item_{n}';
						try{
							var delbuttondata = jQuery.data( jQuery('#'+ FormItemID +' .'+deletebuttonclass).get(0), 'events' );
							jQuery.each(delbuttondata, function(i,o) {
                jQuery.each(o, function(j, h) {
                  var guid = i;
                  delfunction = h.handler;
                });
							});
	 					}
	 					catch(err){
	 						// not initialized
	 					}
						jQuery('#'+FormItemID +' .'+deletebuttonclass).unbind();
						jQuery('#'+FormItemID +' .'+deletebuttonclass).click(function(event){
							var relationid = jQuery('#{fieldNamePrefix}" . $pa_element_info['element_id'] . "_relationid{n}').val();
							if(relationid > 0){
								jQuery('#'+FormContainerID).append('<input type=\"hidden\" value=\"'+relationid+'\" name=\"{fieldNamePrefix}" . $pa_element_info['element_id'] . "_deleterelation{n}\">');
	 						}
		        	if(delfunction != null){
								delfunction();
	 						}
	 						return false;
						});
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

    public function doPostSaveAttribute($t_subject, $vs_form_prefix, $po_request) {
        $va_locale = null;
        if (isset($this->attribute_metadata) && is_array($this->attribute_metadata) && count($this->attribute_metadata) > 0) {
            $vn_attribute_id = $this->attribute_metadata["id"];
            $vs_f = $this->attribute_metadata["placement"];
            $vn_parent_id = $this->attribute_metadata["parent"];
            $vs_matchid = $this->attribute_metadata["matchid"];
            $vn_element_id = $this->attribute_metadata["element_id"];
            $placeholder = $this->attribute_metadata["placeholder"];
            $this->attribute_metadata = array();

            foreach ($this->relations_to_remove as $value) {
                $t_subject->removeRelationship($value['table_name'], $value['relation_id']);
                if ($t_subject->numErrors()) {
                    $po_request->addActionErrors($t_subject->errors(), 'ContainerableRelationship::saveAttribute');
                }
            }
            $this->relations_to_remove = array();

            foreach ($this->relations_to_add as $value) {
                if ($value['reverse']) {
                    $item_table_name = $t_subject->tableName();
                    $item_instance = $t_subject->getAppDatamodel()->getTableInstance($item_table_name);
                    $item_instance->load($value['item_id']);
                    $vs_relationid = $item_instance->addRelationship($value['table_name'], $t_subject->getPrimaryKey(), $value['type_id']);
                }
                else {
                    $vs_relationid = $t_subject->addRelationship($value['table_name'], $value['item_id'], $value['type_id']);
                }
                if ($vs_relationid < 1) {
                    // relation could not be saved
                    return;
                }
                if (strpos($vs_matchid, "new_") === 0) {
                    $va_attribute_to_add = array();
                    $va_attribute_to_add['locale_id'] = $va_locale;
                    $va_attribute_to_add[$vn_attribute_id] = $vs_relationid;

                    // using placeholder to save this newly created relation with the other containered attributes
                    $o_db = $t_subject->getDb();
                    $qr_res = $o_db->query("SELECT attribute_id FROM ca_attribute_values WHERE value_longtext1 like ?", $placeholder . "%");
                    if ($qr_res->nextRow()) {
                        $attr_id = $qr_res->get('attribute_id');
                        $t_attr = new ca_attributes($attr_id);

                        if (!$t_attr->getPrimaryKey()) {
                            $t_subject->addAttribute($va_attribute_to_add, $vn_element_id, $vs_f);
                        }

                        foreach ($t_attr->getAttributeValues() as $o_attr_value) {
                            if ($o_attr_value->getElementID() != $vn_attribute_id) {
                                $va_attribute_to_add[$o_attr_value->getElementID()] = $o_attr_value->getDisplayValue();
                            }
                        }
                        $t_subject->editAttribute($attr_id, $vn_parent_id, $va_attribute_to_add, $vs_f);
                    }
                    else {
                        $t_subject->addAttribute($va_attribute_to_add, $vn_element_id, $vs_f);
                    }
                }
                else {
                    $va_attribute_to_update = array();
                    $va_attribute_to_update['locale_id'] = $va_locale;
                    $va_attribute_to_update[$vn_attribute_id] = $vs_relationid;

                    $t_attr = new ca_attributes($vs_matchid);
                    if (!$t_attr->getPrimaryKey()) {
                        continue;
                    }

                    foreach ($t_attr->getAttributeValues() as $o_attr_value) {
                        // just keep the current values of the other fields!!
                        if ($o_attr_value->getElementID() != $vn_attribute_id) {
                            $va_attribute_to_update[$o_attr_value->getElementID()] = $o_attr_value->getDisplayValue();
                        }
                    }

                    $t_subject->editAttribute($vs_matchid, $vn_parent_id, $va_attribute_to_update, $vs_f);
                }

                if ($t_subject->numErrors()) {
                    $po_request->addActionErrors($this->errors(), 'ContainerableRelationship::saveAttribute');
                }
            }
            $this->relations_to_add = array();

            foreach ($this->relations_to_edit as $value) {
                $t_subject->editRelationship($value['table_name'], $value['relation_id'], $value['item_id'], $value['type_id']);
                // also call the editAttribute function to set this field to changed, so the cached version isn't used ($_FIELD_VALUE_CHANGED)
                $va_attribute_to_update = array();
                $va_attribute_to_update['locale_id'] = $va_locale;
                $va_attribute_to_update[$vn_attribute_id] = $value['relation_id'];

                $t_attr = new ca_attributes($vs_matchid);
                if (!$t_attr->getPrimaryKey()) {
                    continue;
                }

                foreach ($t_attr->getAttributeValues() as $o_attr_value) {
                    if ($o_attr_value->getElementID() != $vn_attribute_id) {
                        $va_attribute_to_update[$o_attr_value->getElementID()] = $o_attr_value->getDisplayValue();
                    }
                }

                $t_subject->editAttribute($vs_matchid, $vn_parent_id, $va_attribute_to_update, $vs_f);
                if ($t_subject->numErrors()) {
                    $po_request->addActionErrors($t_subject->errors(), 'ContainerableRelationship::saveAttribute');
                }
            }
            $this->relations_to_edit = array();
        }
    }
    # ------------------------------------------------------------------

    private $relations_to_add = array();
    private $relations_to_remove = array();
    private $relations_to_edit = array();
    private $attribute_metadata = array();
    private $placeholder_prefix = 'replace_with_relation_id_for_';
    # ------------------------------------------------------------------

    public function doPreSaveAttribute($t_subject, $vs_element, $vs_form_prefix, $po_request, $vs_matchid, $vs_f, $vn_element_id, &$va_attributes_to_insert) {
        // id of the parent of the element
        $field_vals = $vs_element->getFieldValuesArray();

        $vn_parent_id = $field_vals['parent_id'];
        $va_locale = null;
        $vn_attribute_id = $vs_element->getPrimaryKey();

        if (strrpos($vs_matchid, "deleterelation") === 0) {
            $st_to_delete = true;
            $vs_relationid = $po_request->getParameter($vs_form_prefix . '_attribute_' . $vn_parent_id . '_' . $vn_attribute_id . '_' . $vs_matchid, pInteger);
        }
        else {
            // more performant way of searching the request
            $vn_type_id = $po_request->getParameter($vs_form_prefix . '_attribute_' . $vn_parent_id . '_' . $vn_attribute_id . '_relation' . $vs_matchid, pInteger);
            $vs_relationid = $po_request->getParameter($vs_form_prefix . '_attribute_' . $vn_parent_id . '_' . $vn_attribute_id . '_relationid' . $vs_matchid, pInteger);
            $vn_item_id = $po_request->getParameter($vs_form_prefix . '_attribute_' . $vn_parent_id . '_' . $vn_attribute_id . '_' . $vs_matchid, pString);

            $prefill = $po_request->getParameter($vs_form_prefix . '_attribute_' . $vn_parent_id . '_' . $field_vals['element_id'] . '_new_0', pString); //TODO new_0 shouldn't be hardcoded
            $prefill = $prefill . '|';

            if ($vs_relationid < 1) {
                $st_isnew = true;
            }
            // when no relationtype or no element id is given, we can't save
            if ($vn_parent_id == null || $vn_type_id == null || $vn_item_id == null) {
                return true;
            }
        }

        $vn_element_info = explode('|', $prefill);
        if (is_array($prefill) && count($prefill) == 2) {
            $vn_item_id = $vn_element_info[1];
        }

        $element_settings = $vs_element->getSettings();
        $reltabstring = $element_settings['RelTable'];
        $rel_table = $t_subject->getAppDatamodel()->getTableInstance($reltabstring);
        $rel_type_instance = $this->getRelTypeByTypeCode($element_settings['RelType'], $t_subject);
        $right_table_name = $rel_table->getRightTableName();
        $right_table_field = $rel_table->getRightTableFieldName();
        $left_table_name = $rel_table->getLeftTableName();
        $left_table_field = $rel_table->getLeftTableFieldName();
        if ($left_table_name == $right_table_name) {
            //self relationship
            $item_table_name = $t_subject->tableName();
            $item_instance = $t_subject->getAppDatamodel()->getTableInstance($item_table_name);
            $item_table_field = $right_table_field;
            $subject_type_id = $t_subject->get('type_id');
            $left_id = $rel_type_instance->get('sub_type_left_id');
            $right_id = $rel_type_instance->get('sub_type_right_id');

            if (!$left_id) {
                if ($subject_type_id != $right_id) {
                    $item_field_name = $right_table_field;
                }
            }
            if (!$right_id) {
                if ($subject_type_id != $left_id) {
                    $item_field_name = $left_table_field;
                }
            }
        }
        elseif ($t_subject->primaryKey() == $right_table_field) {
            $item_table_name = $left_table_name;
            $item_field_name = $left_table_field;
        }
        else {
            $item_table_name = $right_table_name;
            $item_field_name = $right_table_field;
        }

        $placeholder = $this->placeholder_prefix . $vs_f . '_' . substr($vs_matchid, 4) . '~' . $prefill;

        if ($st_to_delete) {
            // DELETE
            $this->relations_to_remove[] = array(
                "table_name" => $item_table_name,
                "relation_id" => $vs_relationid
            );
        }
        else if ($st_isnew) {
            // ADD
            if (($left_table_name == $right_table_name) && ($item_field_name == $left_table_field)) {
                // create placeholder, because this attribute must be saved together with it's other attributes.
                // substr($vs_matchid, 4)
                if (strpos($vs_matchid, "new_") === 0) {
                    // we only need to do this if it's realy a new attribute, not only the relation can be new!!
                    $va_attributes_to_insert[intval(substr($vs_matchid, 4))][$vn_attribute_id] = $placeholder;
                }
                // add relationship to item
                $this->relations_to_add[] = array(
                    "table_name" => $item_table_name,
                    "item_id" => $vn_item_id,
                    "type_id" => $vn_type_id,
                    "reverse" => true
                );
            }
            else {
                // create placeholder, because this attribute must be saved together with it's other attributes.
                // substr($vs_matchid, 4)
                if (strpos($vs_matchid, "new_") === 0) {
                    // we only need to do this if it's realy a new attribute, not only the relation can be new!!
                    $va_attributes_to_insert[intval(substr($vs_matchid, 4))][$vn_attribute_id] = $placeholder;
                }
                //add relationship to subject
                $this->relations_to_add[] = array(
                    "table_name" => $item_table_name,
                    "item_id" => $vn_item_id,
                    "type_id" => $vn_type_id,
                    "reverse" => false
                );
            }
        }
        else {
            // EDIT
            $this->relations_to_edit[] = array(
                "table_name" => $item_table_name,
                "relation_id" => $vs_relationid,
                "item_id" => $vn_item_id,
                "type_id" => $vn_type_id
            );
        }
        $this->attribute_metadata = array(
            "id" => $vn_attribute_id,
            "placement" => $vs_f,
            "parent" => $vn_parent_id,
            "matchid" => $vs_matchid,
            "element_id" => $vn_element_id,
            "placeholder" => $placeholder
        );

        // don't save the relations here, because the subject doesn't have an id yet!!

        return true;
    }
    # ------------------------------------------------------------------

    public function getAvailableSettings() {
        global $_ca_attribute_settings;

        return $_ca_attribute_settings['ContainerableRelationshipAttributeValue'];
    }
    # ------------------------------------------------------------------

    private function getRelTypeByTypeCode($type_code, $t_subject) {
        $ca_relationship_types = $t_subject->getAppDatamodel()->getTableInstance('ca_relationship_types');
        $o_db = $t_subject->getDb();

        $qr_res = $o_db->query("SELECT type_id FROM ca_relationship_types WHERE type_code = ?", $type_code);
        $type_id = 0;
        if ($qr_res->nextRow()) {
            $type_id = $qr_res->get('type_id');
        }
        //should only be one type id
        $ca_relationship_types->load($type_id);
        return $ca_relationship_types;
    }
    # ------------------------------------------------------------------

    private function getRelationTypeByTypeCode($type_code) {
        $o_db = new Db();
        $qr_res = $o_db->query("SELECT type_id FROM ca_relationship_types WHERE type_code = ?", $type_code);
        $type_id = 0;
        if ($qr_res->nextRow()) {
            $type_id = $qr_res->get('type_id');
        }
        return $type_id;
    }
    # ------------------------------------------------------------------

    private function cleanup() {
        $o_db = new Db();
        $qr_res = $o_db->query("SELECT type_id FROM ca_relationship_types WHERE type_code = ?", $type_code);
    }
}
?>