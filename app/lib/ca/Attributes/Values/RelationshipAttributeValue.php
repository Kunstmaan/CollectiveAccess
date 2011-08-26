<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/RelationshipAttribute.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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

 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/IAttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/AttributeValue.php');

 	global $_ca_attribute_settings;
 	$_ca_attribute_settings['RelationshipAttributeValue'] = array(		// global
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

	class  RelationshipAttributeValue extends AttributeValue implements IAttributeValue {
		// $pa_value_array is used to initialize the value object by calling loadValueFromRow() and
		// is an associative array containing *all* of the ca_attribute_values table value_* fields
		//
		// If you are using the AttributeValue instance to represent an existing value you pass it either
		// in the constructor or by a subsequent call to loadValueFromRow()

		public function __construct($pa_value_array=null) {

		}

		public function loadValueFromRow($pa_value_array) {

		}

		// returns displayable value for attribute; this value can be used in form elements for editing (eg. for dates, this value is parse-able)
 		public function getDisplayValue($pa_options=null) {

 		}

 		public function getValueInstance() {

 		}

 		// ----

 		// Parses the value and, if valid, returns a populated associative array with keys equal to the value fields
 		// in the ca_attribute_values table. The returned value is intended to be written into a ca_attribute_values
 		// row. If the row is not valid, will return null and set errors
 		public function parseValue($ps_value, $pa_element_info) {

 		}

 		public function renderDataType() {
 			return true;
 		}
        
  		// Return an HTML form element for the attribute value with the passed element info (an associative array
 		// containing a row from the ca_metadata_elements table
 		// $pa_options is an optional associative array of form options; these are type-specific.
 		public function htmlFormElement($pa_element_info, $pa_options=null) {
  			$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('RelTable', 'RelType', 'CreateLink', 'RightItemType','RefOnly','quickAddItemTypes','enableQuickAdd'));
  			$t_subject = $pa_options['t_subject'];
 			$rel_table  = $t_subject->getAppDatamodel()->getTableInstance($va_settings['RelTable']);
 			$right_table_name = $rel_table->getRightTableName();
 			$right_table_field = $rel_table->getRightTableFieldName();
 			$left_table_name = $rel_table->getLeftTableName();
 			$left_table_field = $rel_table->getLeftTableFieldName();
 			if($t_subject->primaryKey() == $right_table_field) {
 				$item_table_name = $left_table_name;
 				$item_field_name = $left_table_field;
 			} else {
 				$item_table_name = $right_table_name;
 				$item_field_name = $right_table_field;
 			}
 			//check for relation to subject
 			$subject_table = $t_subject->tableName();


 			if($right_table_name != $subject_table && $left_table_name != $subject_table) {
 				return null;
 			}
 			$t_item = $t_subject->getAppDatamodel()->getTableInstance($item_table_name);
   			$pa_options['RelType'] = $va_settings['RelType'];
   			$pa_options['RefOnly'] = $va_settings['RefOnly'];
   			$pa_options['enableQuickAdd'] = $va_settings['enableQuickAdd'];
   			$pa_options['element_id'] = $pa_element_info['element_id'];
   			$po_request = $pa_options['po_request'];
			$ps_form_name = $pa_options['ps_form_name'].'_relationship_'.$pa_element_info['element_id'];

 		 	if($t_label_name = $t_item->getLabelTableName()) {
				$t_label = $t_subject->getAppDatamodel()->getTableInstance($t_label_name);
				$rel_label_display_field = $t_label->getDisplayField();
			}

  			$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
			$o_view->setVar('id_prefix', $ps_form_name);

			if (method_exists($rel_table, 'getRelationshipTypes')) {
				$o_view->setVar('relationship_types', $rel_table->getRelationshipTypes(null, null));
				$o_view->setVar('relationship_type_select', $rel_table->getRelationshipTypesAsHTMLSelect(null, null));
				//parse out rel sub types
				$rel_sub_types_full = $rel_table->getRelationshipTypesBySubtype($t_subject->tableName(), $t_subject->get('type_id'));
				$rel_sub_types = array();
				foreach($rel_sub_types_full as $rel_key=>$rel_info) {
					foreach($rel_info as $sub_rel_key=>$sub_rel_info) {
					if(array_key_exists('type_id',$sub_rel_info)) {
						$rel_sub_types[] = $sub_rel_info;
					}
					}
				}
				$o_view->setVar('relationship_types_by_sub_type', $rel_sub_types );
			}
			$o_view->setVar('t_subject', $t_subject);

			$va_initial_values = array();
			//add left and right fields to pa_options
			$pa_options['fields'] = array($va_settings['RelTable'].".".$right_table_field,$va_settings['RelTable'].".".$left_table_field);
			if ($rel_table->hasField('type_id')) {
				$pa_options['fields'][] = $va_settings['RelTable'].".type_id";
			}
			$va_items = $t_subject->getRelatedItems($va_settings['RelTable'], $pa_options);


			if (sizeof($va_items)) {
				foreach ($va_items as $va_item) {
					$t_item->load($va_item[$item_field_name]);
					if ($t_label) {
                         $va_tmp = caExtractValuesByUserLocale(array(1 => $va_item['labels']));
                         $va_item[$t_label->getDisplayField()] = $va_tmp[1];
                         if(!$va_item[$t_label->getDisplayField()]) {
							$all_rel_labels = $t_item->getPreferredLabels();
							$rel_labels_array = array_pop($all_rel_labels);
							$pref_label = $rel_labels_array[1][0][$rel_label_display_field];
							$va_item[$rel_label_display_field] = $pref_label;
                         }
			//handles self relationships with 'left' and 'right' keys
			 if(!array_key_exists($t_item->primaryKey(),$va_item)) {
				$va_item[$t_item->primaryKey()] = $t_item->getPrimaryKey();
			}
                    }
                    if(!$va_item['item_type_id']) $va_item['item_type_id'] = $t_item->getTypeID();
                    if(!$va_item['relationship_type_id']) $va_item['relationship_type_id'] = $va_item['type_id'];
					$va_initial_values[$va_item[$rel_table->primaryKey()]] = array_merge($va_item, array('id' => $va_item[$item_field_name], 'item_type_id' => $va_item['item_type_id'], 'relationship_type_id' => $va_item['relationship_type_id']));
				}
			}

			require_once(__CA_APP_DIR__.'/models/'.$right_table_name.'.php');
			$vs_right_table_instance = new $right_table_name();
			
			$arr_overlay_url = caEditorUrl($pa_options['po_request'], $right_table_name, null, true);
			$vs_overlay_url = caNavUrl($pa_options['po_request'], $arr_overlay_url['module'], $arr_overlay_url['controller'], $arr_overlay_url['action']).'/type_id/';
			
			$vs_label_display_field_name = $vs_right_table_instance->getLabelDisplayField();
			$vs_singular_name = $vs_right_table_instance->getProperty('NAME_SINGULAR');

	 		$arr_list_items = array();
			// get all possible type codes
			$ol_listcode = $vs_right_table_instance->getTypeListCode();
			$t_list = new ca_lists();
			if ($t_list->load(array('list_code' => $ol_listcode))) {
				$arr_list_items = $t_list->getItemsForList($ol_listcode);
			}
			
			$arr_new_list_items = array(); // to prevent dificulties in javascript 
			if($quickAddItemTypes = $va_settings['quickAddItemTypes']){
				foreach($quickAddItemTypes as $item_code) {
					$arr_new_list_items[] = $arr_list_items[$this->getItemIDByIDNo($item_code, $pa_options['t_subject'])][1];
				} 
			}
			else{
				foreach ($arr_list_items as $key => $value){
					$arr_new_list_items[] = $arr_list_items[$key][1];
				}
			}
			
 			// hack to check for vocabulary_terms
			if($right_table_name == 'ca_list_items' && strpos($va_settings['RelTable'], "vocabulary_terms")) {
				$pa_options['onlyvoc'] = true;
			}

			$pa_options['quickAddItemTypes'] = $arr_new_list_items;
 		
			$pa_options['idfieldname'] = $right_table_field;
			$pa_options['textfieldname'] = $vs_label_display_field_name;
			$pa_options['overlay_base_url'] = $vs_overlay_url;
			$pa_options['singular_name'] = $vs_singular_name;
      
			$o_view->setVar('label_display_field',$rel_label_display_field);
			$o_view->setVar('t_item',$t_item);
			$o_view->setVar('initialValues', $va_initial_values);
			$o_view->setVar('request', $po_request);
			$o_view->setVar('pa_options',$pa_options);

			$vs_form_prefix = $pa_options['ps_form_name'];

			$failed_inserts = $this->getAttributeValuesForFailedInsert($this, $pa_element_info, $vs_form_prefix, $po_request);
			$o_view->setVar('failed_insert_attribute_list', $failed_inserts);

      $html = $o_view->render('relationship.php');
			return $html;
 		}

    private function getItemIDByIDNo($item_code,$t_subject) {
			$ca_list_items  = $t_subject->getAppDatamodel()->getTableInstance('ca_list_items');
			$o_db = $t_subject->getDb();
			$qr_res = $o_db->query("SELECT item_id FROM ca_list_items WHERE idno = ?",$item_code);
			if($qr_res->nextRow()) {
				$itemid = $qr_res->get('item_id');
				return $itemid;
	        }
	        return 0;
		}

		private function getRelTypeByTypeCode($type_code,$t_subject) {
			$ca_relationship_types  = $t_subject->getAppDatamodel()->getTableInstance('ca_relationship_types');
			$o_db = $t_subject->getDb();

                        $qr_res = $o_db->query("SELECT type_id FROM ca_relationship_types WHERE type_code = '".$type_code."'");

			$type_ids = array();
                        while($qr_res->nextRow()) {
                                $type_ids[] =  $qr_res->get('type_id');
                        }
			//should only be one type id
			$ca_relationship_types->load(array_pop($type_ids));
			return $ca_relationship_types;

		}

		public function getAttributeValuesForFailedInsert($t_subject,$vs_element,$vs_form_prefix,$po_request) {
			$repopulate = Array();
			$vs_prefix_stub = $vs_form_prefix.'_relationship_'.$vs_element[element_id];
		    foreach($_REQUEST as $vs_key => $vs_value ) {
		    if (!preg_match('/^'.$vs_prefix_stub.'_idnew_([\d]+)/', $vs_key, $va_matches)) {continue; }
				if($vn_new_id = $po_request->getParameter($va_matches[0], pString)) {
					$vn_c = intval($va_matches[1]); //numbers items in array
					$vn_new_type_id = $po_request->getParameter($vs_prefix_stub.'_type_idnew_'.$vn_c,pInteger); //type of relation
					$vn_new_id = $po_request->getParameter($vs_prefix_stub.'_idnew_'.$vn_c, pInteger);
					$vn_new_name = $po_request->getParameter($vs_prefix_stub.'_autocomplete_new_'.$vn_c, pString);

					$repopulate[] =  array(
					  "displayname" => $vn_new_name,
					  "type_id" => $vn_new_type_id,
					  "id" => $vn_new_id
					);
				}
			}
			return $repopulate;
		}

		public function saveElement($t_subject,$vs_element,$vs_form_prefix,$po_request) {
			$element_settings = $vs_element->getSettings();
			$rel_table  = $t_subject->getAppDatamodel()->getTableInstance($element_settings['RelTable']);
			$rel_type_instance = $this->getRelTypeByTypeCode($element_settings['RelType'],$t_subject);
			$right_table_name = $rel_table->getRightTableName();
 			$right_table_field = $rel_table->getRightTableFieldName();
 			$left_table_name = $rel_table->getLeftTableName();
 			$left_table_field = $rel_table->getLeftTableFieldName();
			if($left_table_name == $right_table_name) {
				//self relationship
				$item_table_name = $t_subject->tableName();
				$item_instance = $t_subject->getAppDatamodel()->getTableInstance($item_table_name);
				$item_table_field = $right_table_field;
				$subject_type_id = $t_subject->get('type_id');
				$left_id = $rel_type_instance->get('sub_type_left_id');
				$right_id = $rel_type_instance->get('sub_type_right_id');
				if(!$left_id) {
					if($subject_type_id != $right_id) {
						$item_field_name = $right_table_field;
					}
				}
				if(!$right_id) {
					if($subject_type_id != $left_id) {
                                                $item_field_name = $left_table_field;
                                        }
				}
			} elseif($t_subject->primaryKey() == $right_table_field) {
 				$item_table_name = $left_table_name;
 				$item_field_name = $left_table_field;
 			} else {
 				$item_table_name = $right_table_name;
 				$item_field_name = $right_table_field;
 			}

			$va_rel_items = $t_subject->getRelatedItems($item_table_name);
			$vs_prefix_stub = $vs_form_prefix.'_relationship_'.$vs_element->getPrimaryKey();

			// We have to specify this because we want the count in the row, not the id of the object
			$arr_index = 0;
			foreach($va_rel_items as $va_rel_item) {
				$t_subject->clearErrors();
				$vn_id = $po_request->getParameter($vs_prefix_stub.'_id'.$arr_index, pString);
				if ($vn_id) {
					$vn_type_id = $po_request->getParameter($vs_prefix_stub.'_type_id'.$arr_index, pInteger);
					$t_subject->editRelationship($item_table_name,$va_rel_item['relation_id'], $vn_id, $vn_type_id);
					if ($t_subject->numErrors()) {
						$po_request->addActionErrors($t_subject->errors(), 'RelationshipAttributeValue::saveElement');
					}
				} else {
					// is it a delete key?
					$t_subject->clearErrors();
					if (($po_request->getParameter($vs_prefix_stub.'_'.$arr_index.'_delete', pInteger)) > 0) {
						$relationidvalue = $po_request->getParameter($vs_prefix_stub.'_'.$arr_index.'_delete', pInteger);
						// delete!
						$t_subject->removeRelationship($item_table_name, $relationidvalue);
						if ($t_subject->numErrors()) {
							$po_request->addActionErrors($t_subject->errors(), 'RelationshipAttributeValue::saveElement');
						}
					}
				}
				$arr_index++;
			}

	 		foreach($_REQUEST as $vs_key => $vs_value ) {

				if (!preg_match('/^'.$vs_prefix_stub.'_idnew_([\d]+)/', $vs_key, $va_matches)) { continue; }
				if($vn_new_id = $po_request->getParameter($va_matches[0], pString)) {
					$vn_c = intval($va_matches[1]);
					$vn_new_type_id = $po_request->getParameter($vs_prefix_stub.'_type_idnew_'.$vn_c,pInteger);
					if(($left_table_name == $right_table_name) && ($item_field_name == $left_table_field)) {
						//add relationship to item
						$item_instance->load($vn_new_id);
						$item_instance->addRelationship($item_table_name,$t_subject->getPrimaryKey(),$vn_new_type_id);
                                                if ($item_instance->numErrors()) {
                                                        $po_request->addActionErrors($this->errors(), $vs_key);
                                                }
					} else {
						//add relationship to subject
						$t_subject->addRelationship($item_table_name,$vn_new_id,$vn_new_type_id);
		 				if ($t_subject->numErrors()) {
							$po_request->addActionErrors($this->errors(), $vs_key);
						}
					}
				}
			}

		}

 		// Loads type specific data into the value object out of the same associative array you'd pass to loadValueFromRow()
 		// The default implementation of loadValueFromRow() in the AttributeValue baseclass calls this so there is
 		// normally no need to call this yourself
 		public function loadTypeSpecificValueFromRow($pa_value_array) {

 		}

		public function getAvailableSettings() {
 			global $_ca_attribute_settings;
 			return $_ca_attribute_settings['RelationshipAttributeValue'];
 		}

		public function getFailedAttributeInserts($pm_element_code_or_id) {
			return $this->opa_failed_attribute_inserts[$pm_element_code_or_id];
		}

		public function cleanUpFailedInserts($failed_inserts) {
			if(!is_array($failed_inserts)) {
				return $failed_inserts;
			}
			$res = array();
			foreach($failed_inserts as $key => $value) {
				$newvalue = $value;
				if(is_array($value)) {
					$newvalue = $this->cleanUpFailedInserts($value);
				} else {
					if(isset($value) && is_string($value)) {
						$newvalue = '';
					}
				}
				$res[$key] = $newvalue;
			}
			return $res;
		}

		/**
		 * Returns name of field in ca_attribute_values to use for sort operations
		 *
		 * @return string Name of sort field
		 */
		public function sortField() {
			return null;
		}
	}
?>