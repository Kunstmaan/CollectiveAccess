<?php
/** ---------------------------------------------------------------------
 * app/lib/core/BaseModelWithAttributes.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2010 Whirl-i-Gig
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
 
 require_once(__CA_LIB_DIR__.'/core/ITakesAttributes.php');
 require_once(__CA_LIB_DIR__.'/core/BaseModel.php');
 require_once(__CA_APP_DIR__.'/models/ca_attributes.php');
 require_once(__CA_APP_DIR__.'/models/ca_attribute_values.php');
 require_once(__CA_APP_DIR__.'/models/ca_metadata_type_restrictions.php');

 
	class BaseModelWithAttributes extends BaseModel implements ITakesAttributes {
		# ------------------------------------------------------------------
		static $s_applicable_element_code_cache = array();
		static $s_element_label_cache = array();
		static $s_element_id_lookup_cache = array();
 		static $s_element_instance_cache = array();
 		static $s_element_code_lookup_cache = array();
		# ------------------------------------------------------------------
		protected $opa_failed_attribute_inserts;
		protected $opa_failed_attribute_updates;
		
		protected $opa_attributes_to_add;
		protected $opa_attributes_to_edit;
		protected $opa_attributes_to_remove;
		
		
		# ------------------------------------------------------------------
		public function __construct($pn_id=null) {
			parent::__construct($pn_id);
			$this->init();
		}
		# ------------------------------------------------------------------
		public function init() {
			$this->opa_failed_attribute_inserts = array();
			$this->opa_failed_attribute_updates = array();
			$this->_initAttributeQueues();
		}
		# ------------------------------------------------------------------
		private function _initAttributeQueues() {
			$this->opa_attributes_to_add = array();
			$this->opa_attributes_to_edit = array();
			$this->opa_attributes_to_remove = array();
		}
		# ------------------------------------------------------------------
		// create an attribute linked to the current row using values in $pa_values
		public function addAttribute($pa_values, $pm_element_code_or_id, $ps_error_source=null) {
			if (!($t_element = $this->_getElementInstance($pm_element_code_or_id))) { return false; }
			if ($t_element->get('parent_id') > 0) { return false; }
			$vn_element_id = $t_element->getPrimaryKey();
			
			// check restriction min/max settings
			$t_restriction = $t_element->getTypeRestrictionInstance($this->tableNum(), $this->getTypeID());
			if (!$t_restriction) { return null; }		// attribute not bound to this type
			$vn_min = $t_restriction->getSetting('minAttributesPerRow');
			$vn_max = $t_restriction->getSetting('maxAttributesPerRow');
			
			$vn_add_cnt = 0;
			foreach($this->opa_attributes_to_add as $va_attr) {
				if ($this->_getElementID($va_attr['element']) == $vn_element_id) {
					$vn_add_cnt++;
				}
			}
			$vn_del_cnt = 0;
			foreach($this->opa_attributes_to_remove as $va_attr) {
				if ($va_attr['element_id'] == $vn_element_id) {
					$vn_del_cnt++;
				}
			}
			
			$vn_count = $this->getAttributeCountByElement($vn_element_id)  + $vn_add_cnt - $vn_del_cnt;
			if (($vn_max > 0) && $vn_count >= $vn_max) { return null; }	// # attributes is at upper limit
			
			$this->opa_attributes_to_add[] = array(
				'values' => $pa_values,
				'element' => $pm_element_code_or_id,
				'error_source' => $ps_error_source.'/'.sizeof($this->opa_attributes_to_add)
			);
			$this->_FIELD_VALUE_CHANGED['_ca_attribute_'.$vn_element_id] = true;
		}
		# ------------------------------------------------------------------
		// create an attribute linked to the current row using values in $pa_values
		public function _addAttribute($pa_values, $pm_element_code_or_id, $po_trans=null, $pa_options=null) {
			if (!($t_element = $this->_getElementInstance($pm_element_code_or_id))) { return false; }
			if ($t_element->get('parent_id') > 0) { return false; }
			
			$t_attr = new ca_attributes();
			if ($po_trans) { $t_attr->setTransaction($po_trans); }
			$vn_attribute_id = $t_attr->addAttribute($this->tableNum(), $this->getPrimaryKey(), $t_element->getPrimaryKey(), $pa_values);
			if ($t_attr->numErrors()) {
				foreach($t_attr->errors as $o_error) {
					$this->postError($o_error->getErrorNumber(), $o_error->getErrorDescription(), $o_error->getErrorContext(), $pa_options['error_source']);
				}
				return false;
			}
						
			return $vn_attribute_id;
		}
		# ------------------------------------------------------------------
		public function editAttribute($pn_attribute_id, $pm_element_code_or_id, $pa_values, $ps_error_source=null) {
			$t_attr = new ca_attributes($pn_attribute_id);
			if (!$t_attr->getPrimaryKey()) { return false; }
			$vn_attr_element_id = $t_attr->get('element_id');
			
			$va_attr_values = $t_attr->getAttributeValues();
			
			if (sizeof($va_attr_values) != (sizeof($pa_values) - 1)) {		// -1 to remove locale_id which is not in attribute values array
				// Value arrays are different sizes - probably means the elements in the set have been reconfigured (sub-elements added or removed)
				// so we need to force a save.
				$this->_FIELD_VALUE_CHANGED['_ca_attribute_'.$vn_attr_element_id] = true;
			} else {
				// Have any of the values changed?
				foreach($va_attr_values as $o_attr_value) {
					$vn_element_id = $o_attr_value->getElementID();
					$vs_element_code = $this->_getElementCode($vn_element_id);
					
					if (
						(
							isset($pa_values[$vn_element_id]) && ($pa_values[$vn_element_id] !== $o_attr_value->getDisplayValue()) 
							&& 
							!(($pa_values[$vn_element_id] == "") && (is_null($o_attr_value->getDisplayValue())))
						)
						||
						(
							isset($pa_values[$vs_element_code]) && ($pa_values[$vs_element_code] !== $o_attr_value->getDisplayValue()) 
							&&
							!(($pa_values[$vs_element_code] == "") && (is_null($o_attr_value->getDisplayValue())))
						)
					) {
						$this->_FIELD_VALUE_CHANGED['_ca_attribute_'.$vn_attr_element_id] = true;
						break;
					}
				}
			}
			
			if (isset($this->_FIELD_VALUE_CHANGED['_ca_attribute_'.$vn_attr_element_id]) && $this->_FIELD_VALUE_CHANGED['_ca_attribute_'.$vn_attr_element_id]) {
				$this->opa_attributes_to_edit[] = array(
					'values' => $pa_values,
					'attribute_id' => $pn_attribute_id,
					'element' => $pm_element_code_or_id,
					'error_source' => $ps_error_source.'/'.$pn_attribute_id
				);
			}
		}
		# ------------------------------------------------------------------
		// edit attribute from current row
		public function _editAttribute($pn_attribute_id, $pa_values, $po_trans=null, $pa_options=null) {
			$t_attr = new ca_attributes($pn_attribute_id);
			if ($po_trans) { $t_attr->setTransaction($po_trans); }
			if ((!$t_attr->getPrimaryKey()) || ($t_attr->get('table_num') != $this->tableNum()) || ($this->getPrimaryKey() != $t_attr->get('row_id'))) {
				$this->postError(1969, _t('Can\'t edit invalid attribute'), 'BaseModelWithAttributes->editAttribute()', $pa_options['error_source']);
				return false;
			}
			
			if (!$t_attr->editAttribute($pa_values)) {
				foreach($t_attr->errors as $o_error) {
					$this->postError($o_error->getErrorNumber(), $o_error->getErrorDescription(), $o_error->getErrorContext(), $pa_options['error_source']);
				}
				return false;
			}
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Replaces first attribute value with specified values; will add attribute value if no attributes are defined 
		 * This is handy for doing editing on non-repeating attributes
		 */
		public function replaceAttribute($pa_values, $pm_element_code_or_id, $ps_error_source=null) {
			$va_attrs = $this->getAttributesByElement($pm_element_code_or_id);
			
			if (sizeof($va_attrs)) {
				return $this->editAttribute(
					$va_attrs[0]->getAttributeID(),
					$pm_element_code_or_id, $pa_values, $ps_error_source
				);
			} else {
				return $this->addAttribute(
					$pa_values, $pm_element_code_or_id, $ps_error_source
				);
			}
		}
		# ------------------------------------------------------------------
		public function removeAttribute($pn_attribute_id, $ps_error_source=null, $pa_extra_info=null) {
			$t_attr = new ca_attributes($pn_attribute_id);
			if (!$t_attr->getPrimaryKey()) { return false; }
			
			$vn_add_cnt = 0;
			if (isset($pa_extra_info['pending_adds']) && is_array($pa_extra_info['pending_adds'])) {
				$vn_add_cnt = sizeof($pa_extra_info['pending_adds']);
			} else {
				foreach($this->opa_attributes_to_add as $va_attr) {
					if ($this->_getElementID($va_attr['element']) == $vn_element_id) {
						$vn_add_cnt++;
					}
				}
			}
			
			
			// check restriction min/max settings
			if (!($t_element = $this->_getElementInstance($t_attr->get('element_id')))) { return false; }
			$t_restriction = $t_element->getTypeRestrictionInstance($this->tableNum(), $this->getTypeID());
			if (!$t_restriction) { return null; }		// attribute not bound to this type
			$vn_min = $t_restriction->getSetting('minAttributesPerRow');
			$vn_max = $t_restriction->getSetting('maxAttributesPerRow');
			
			
			$vn_del_cnt = 0;
			foreach($this->opa_attributes_to_remove as $va_attr) {
				if ($va_attr['element_id'] == $vn_element_id) {
					$vn_del_cnt++;
				}
			}
			
			$vn_count = $this->getAttributeCountByElement($t_element->getPrimaryKey())  + $vn_add_cnt - $vn_del_cnt;
			if ($vn_count <= $vn_min) { return null; }	// # attributes is at lower limit
			
			$this->opa_attributes_to_remove[] = array(
				'attribute_id' => $pn_attribute_id,
				'error_source' => $ps_error_source,
				'element_id' => $t_attr->get('element_id')
			);
			
			$this->_FIELD_VALUE_CHANGED['_ca_attribute_'.$t_attr->get('element_id')] = true;
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * remove attribute from current row
		 */
		public function _removeAttribute($pn_attribute_id, $po_trans=null, $pa_options=null) {
			$t_attr = new ca_attributes($pn_attribute_id);
			if ($po_trans) { $t_attr->setTransaction($po_trans); }
			if ((!$t_attr->getPrimaryKey()) || ($t_attr->get('table_num') != $this->tableNum()) || ($this->getPrimaryKey() != $t_attr->get('row_id'))) {
				$this->postError(1969, _t('Can\'t edit invalid attribute'), 'BaseModelWithAttributes->editAttribute()', $pa_options['error_source']);
				return false;
			}
			if (!$t_attr->removeAttribute()) {
				foreach($t_attr->errors as $o_error) {
					$this->postError($o_error->getErrorNumber(), $o_error->getErrorDescription(), $o_error->getErrorContext(), $pa_options['error_source']);
				}
				return false;
			}
			$this->setFieldValuesArray($this->addAttributesToFieldValuesArray());
			return true;
		}
		# ------------------------------------------------------------------
		/** 
		 * removes all attributes from current row of specified element, or all attributes regardless of 
		 * element if $pm_element_code_or_id is omitted
		 *
		 * Note that this method does not respect the minAttributesPerRow type restriction setting. It always
		 * removes *all* attributes
		 */
		public function removeAttributes($pm_element_code_or_id=null) {
			if(!$this->getPrimaryKey()) { return null; }
			
			if ($pm_element_code_or_id) {
				$va_attributes = $this->getAttributesByElement($pm_element_code_or_id);
			} else {
				$va_attributes = $this->getAttributesByElement();
			}
			
			foreach($va_attributes as $o_attribute) {
				$this->removeAttribute($o_attribute->getAttributeID());
			}
			return $this->update();
		}
		# ------------------------------------------------------------------
		private function _commitAttributes($po_trans=null) {
			$va_attribute_change_list = array();
			$va_inserted_attributes_that_errored = array();
			foreach($this->opa_attributes_to_add as $va_info) {
				if (!($vn_attribute_id = $this->_addAttribute($va_info['values'], $va_info['element'], $po_trans, $va_info))) {
					$va_info['values']['_errors'] = $this->_getErrorsForBundleUI($va_info['error_source']);
					$va_inserted_attributes_that_errored[$va_info['element']][] = $va_info['values'];
				} else {
					// noop
				}
			}
			foreach($va_inserted_attributes_that_errored as $vs_element => $va_list) {
				$this->setFailedAttributeInserts($vs_element, $va_list);
			}
			
			$va_updated_attributes_that_errored = array();
			foreach($this->opa_attributes_to_edit as $va_info) {
				if (!$this->_editAttribute($va_info['attribute_id'], $va_info['values'], $po_trans, $va_info)) {
					$va_updated_attributes_that_errored[$va_info['element']][$va_info['attribute_id']] = $va_info['values'];
				}
			}
			foreach($va_updated_attributes_that_errored as $vs_element => $va_list) {
				$this->setFailedAttributeUpdates($vs_element, $va_list);
			}
			
			foreach($this->opa_attributes_to_remove as $va_info) {
				$this->_removeAttribute($va_info['attribute_id'], $po_trans, $va_info);
			}
			$this->_initAttributeQueues();
			
			// set the field values array for this instance
			$this->setFieldValuesArray($this->addAttributesToFieldValuesArray());
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns an array of errors is the specified source. Each element of the array is an associative array
		 * with keys for 'errorDescription' and 'errorCode'; serialized into a JSON format array, this array can be
		 * passed directly to a generic bundle-based UI component (eg. as defined in js/ca.genericbundle.js)
		 */
		private function _getErrorsForBundleUI($ps_source=null) {
			$va_errors = array();
			foreach($this->errors($ps_source) as $o_error) {
				$va_errors[] = array('errorDescription' => $o_error->getErrorDescription(), 'errorCode' => $o_error->getErrorNumber());
			}
			return $va_errors;
		}
		# ------------------------------------------------------------------
		#
		# ------------------------------------------------------------------
		private function addAttributesToFieldValuesArray() {
			$va_field_values = $this->getFieldValuesArray();
			
			// clear out all attribute values
			foreach($va_field_values as $vs_k => $vs_v) {
				if ((substr($vs_k, 0, 14) == '_ca_attribute_')) { $va_field_values[$vs_k] = null; }
			}
			
			$va_attributes = $this->getAttributes(array());
			$va_field_content = array();
			foreach($va_attributes as $o_attr) {
				foreach($o_attr->getValues() as $o_attr_value) {
					$va_field_content['_ca_attribute_'.$o_attr->getElementID()][$o_attr->getAttributeID()][$o_attr_value->getValueID()] = $o_attr_value->getDisplayValue();
				}
			}
			foreach($va_field_content as $vs_attr_fld => $va_attributes) {
				$va_tmp = array();
				foreach($va_attributes as $vn_attribute_id => $va_values) {
					$va_tmp[] = join("\n", array_values($va_values));
				}
				$va_field_values[$vs_attr_fld] = join("; ",$va_tmp);
			}
			return $va_field_values;
		}
		# ------------------------------------------------------------------
		public function load($pm_id=null) {
			$this->init();
			$this->setFieldValuesArray(array());
			if ($vn_c = parent::load($pm_id)) {
				// Copy attributes into field values array in BaseModel
				$this->setFieldValuesArray($this->addAttributesToFieldValuesArray());
			}
			return $vn_c;
		}
		# ------------------------------------------------------------------
		public function insert($pa_options=null) {
			$vb_we_set_transaction = false;
			if (!$this->inTransaction()) {
				$this->setTransaction(new Transaction($this->getDb()));
				$vb_we_set_transaction = true;
			}
			$vb_web_set_change_log_unit_id = BaseModel::setChangeLogUnitID();
			
			if (!is_array($pa_options)) { $pa_options = array(); }
			$pa_options['dont_do_search_indexing'] = true;
			
			$va_field_values = $this->getFieldValuesArray();		// get pre-insert field values (including attribute values)
			
			// change status for attributes is only available **before** insert
			$va_fields_changed_array = $this->_FIELD_VALUE_CHANGED;
			if(parent::insert($pa_options)) {
				$this->_commitAttributes($this->getTransaction());
				
				if (sizeof($this->opa_failed_attribute_inserts)) {
					if ($vb_we_set_transaction) { $this->removeTransaction(false); }
					$this->_FIELD_VALUES[$this->primaryKey()] = null;		// clear primary key set by BaseModel::insert()
					return false;
				}
				
				$va_field_values_with_updated_attributes = $this->addAttributesToFieldValuesArray();	// copy committed attribute values to field values array
				
				// set changed flag for attributes that have changed
				foreach($va_field_values_with_updated_attributes as $vs_k => $vs_v) {
					if (!isset($va_field_values[$vs_k])) { $va_field_values[$vs_k] = null; }
					if ($va_field_values[$vs_k] != $vs_v) {
						$this->_FIELD_VALUE_CHANGED[$vs_k] = true;
					}
				}
				
				// set the field values array for this instance
				$this->setFieldValuesArray($va_field_values_with_updated_attributes);
				
				$this->doSearchIndexing($va_fields_changed_array);
				
				
				if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
				if ($this->numErrors() > 0) {
					if ($vb_we_set_transaction) { $this->removeTransaction(false); }
					$this->_FIELD_VALUES[$this->primaryKey()] = null;		// clear primary key set by BaseModel::insert()
					return false;
				}
				
				if ($vb_we_set_transaction) { $this->removeTransaction(true); }
				return true;
			} else {
				// push all attributes onto errored list
				$va_inserted_attributes_that_errored = array();
				foreach($this->opa_attributes_to_add as $va_info) {
					$va_inserted_attributes_that_errored[$va_info['element']][] = $va_info['values'];
				}
				foreach($va_inserted_attributes_that_errored as $vs_element => $va_list) {
					$this->setFailedAttributeInserts($vs_element, $va_list);
				}
			}
		
			if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
			if ($vb_we_set_transaction) { $this->removeTransaction(false); }
			$this->_FIELD_VALUES[$this->primaryKey()] = null;		// clear primary key set by BaseModel::insert()
			return false;
		}
		# ------------------------------------------------------------------
		public function update($pa_options=null) {
			$vb_web_set_change_log_unit_id = BaseModel::setChangeLogUnitID();
			
			if (!is_array($pa_options)) { $pa_options = array(); }
			$pa_options['dont_do_search_indexing'] = true;
			
			$va_field_values = $this->getFieldValuesArray();		// get pre-update field values (including attribute values)
			// change status for attributes is only available **before** update
			$va_fields_changed_array = $this->_FIELD_VALUE_CHANGED;
			if(parent::update($pa_options)) {
				$this->_commitAttributes($this->getTransaction());
				
				$va_field_values_with_updated_attributes = $this->addAttributesToFieldValuesArray();	// copy committed attribute values to field values array
				
				// set the field values array for this instance
				$this->setFieldValuesArray($va_field_values_with_updated_attributes);
				
				$this->doSearchIndexing($va_fields_changed_array);
				
				if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
				if ($this->numErrors() > 0) {
					return false;
				}
				return true;
			}
			
			if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
			return false;
		}
		# ------------------------------------------------------------------
		public function delete($pb_delete_related=false, $pa_options=null) {
			$vb_web_set_change_log_unit_id = BaseModel::setChangeLogUnitID();
			
			if (!$this->inTransaction()) {
				$o_trans = new Transaction($this->getDb());
				$this->setTransaction($o_trans);
			}
			if (!is_array($pa_options)) { $pa_options = array(); }
			
			$vn_id = $this->getPrimaryKey();
			if(parent::delete($pb_delete_related)) {
				// Delete any associated attributes and attribute_values
				if (!($qr_res = $this->getDb()->query("
					DELETE FROM ca_attribute_values 
					USING ca_attributes 
					INNER JOIN ca_attribute_values ON ca_attribute_values.attribute_id = ca_attributes.attribute_id 
					WHERE ca_attributes.table_num = ? AND ca_attributes.row_id = ?
				", (int)$this->tableNum(), (int)$vn_id))) { 
					$this->errors = $this->getDb()->errors();
					if ($o_trans) { $o_trans->rollback(); }
					
					if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
					return false; 
				}
				
				if (!($qr_res = $this->getDb()->query("
					DELETE FROM ca_attributes
					WHERE
						table_num = ? AND row_id = ?
				", (int)$this->tableNum(), (int)$vn_id))) {
					$this->errors = $this->getDb()->errors();
					if ($o_trans) { $o_trans->rollback(); }
					
					if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
					return false;
				}
				
				if ($o_trans) { $o_trans->commit(); }
					
				if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
				return true;
			}
			
			if ($o_trans) { $o_trans->rollback(); }
			if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
			return false;
		}
		# ------------------------------------------------------------------
		/**
		 * Get value(s) for specified attribute. $ps_field specifies the value to fetch in <table_name>.<element_code> or <table_name>.<element_code>.<subelement_code>
		 * Will return a string containing the retrieved value or values (since attributes can repeat). The values will
		 * be formatted using the 'template' option with values separated by a delimiter as set in the 'delimiter'
		 * option (default is a space). 
		 *
		 * If the 'returnAsArray' option is set the an array containing all values will be returned.
		 * The array will be keyed on the current row primary key, and then attribute_id, with each attribute_id value containing an array keyed on element code and having
		 * values set to attribute values (this is a bit more complicated than one might hope since not only can
		 * values repeat, but they can be composed of many sub-values... the final array key'ed on element_code may have several values if the attribute is complex). 
		 *
		 * If the 'returnAllLocales' option is set *and* 'returnAsArray' is set then the returned array will include an extra dimension (or key if that's what you prefer to call it)
		 * that separates values by numeric locale_id. Thus the returned array will have several layers of keys: current row primary key, then locale_id, then attribute_id and then
		 * finally, element codes. This format is, incidentally, compatible with the caExtractValuesByUserLocale() helper function, which would strip all values not needed for
		 * display in the current locale.
		 *
		 * @param $pa_options array - array of options for get; in addition to the standard get() options, will also pass through options to attribute value handlers
		 *		Supported options include:
		 *				locale = 
		 *				returnAsArray = if true, return an array, otherwise return a string (default is false)
		 *				returnAllLocales = 
		 *				template = 
		 *				delimiter = 
		 *				convertCodesToDisplayText =
		 * @return mixed - 
		 *
		 * 
		 */
		public function get($ps_field, $pa_options=null) {
			
			$vs_template = 				(isset($pa_options['template'])) ? $pa_options['template'] : null;
			$vs_delimiter = 				(isset($pa_options['delimiter'])) ? $pa_options['delimiter'] : ' ';
			$vb_return_as_array = 		(isset($pa_options['returnAsArray'])) ? (bool)$pa_options['returnAsArray'] : false;
			$vb_return_all_locales = 	(isset($pa_options['returnAllLocales'])) ? (bool)$pa_options['returnAllLocales'] : false;
			if ($vb_return_all_locales && !$vb_return_as_array) { $vb_return_as_array = true; }
		
			// does get refer to an attribute?
			$va_tmp = explode('.', $ps_field);
			
			if (!is_array($pa_options)) { $pa_options = array(); }
			$pa_options = array_merge($pa_options, array('indexByRowID' => true));		// force arrays to be indexed by current row_id
			
			$t_instance = $this;
			if ((sizeof($va_tmp) >= 2) && (!$this->hasField($va_tmp[2]))) {
				if (($va_tmp[1] == 'parent') && ($this->isHierarchical()) && ($vn_parent_id = $this->get($this->getProperty('HIERARCHY_PARENT_ID_FLD')))) {
					$t_instance = $this->getAppDatamodel()->getInstanceByTableNum($this->tableNum());
					if (!$t_instance->load($vn_parent_id)) {
						$t_instance = $this;
					} else {
						unset($va_tmp[1]);
						$va_tmp = array_values($va_tmp);
					}
				} else {
					if (($va_tmp[1] == 'children') && ($this->isHierarchical())) {
						unset($va_tmp[1]);					// remove 'children' from field path
						$va_tmp = array_values($va_tmp);
						$vs_childless_path = join('.', $va_tmp);
						
						$va_data = array();
						$va_children_ids = $this->getHierarchyChildren(null, array('idsOnly' => true));
						
						$t_instance = $this->getAppDatamodel()->getInstanceByTableNum($this->tableNum());
						
						foreach($va_children_ids as $vn_child_id) {
							if ($t_instance->load($vn_child_id)) {
								$vm_val = $t_instance->get($vs_childless_path, $pa_options);
								$va_data = array_merge($va_data, is_array($vm_val) ? $vm_val : array($vm_val));
							}
						}
						
						if ($vb_return_as_array) {
							return $va_data;
						} else {
							return join($vs_delimiter, $va_data);
						}
					} 
				}
			}
			
			switch(sizeof($va_tmp)) {
				# -------------------------------------
				case 1:		// simple name
					if (!$t_instance->hasField($va_tmp[0])) {	// is it intrinsic?
						// nope... so try it as an attribute
						if (!$vb_return_as_array) {
							return $t_instance->getAttributesForDisplay($va_tmp[0], $vs_template, $pa_options);
						} else {
							$va_values = $t_instance->getAttributeDisplayValues($va_tmp[0], $t_instance->getPrimaryKey(), $pa_options);
							if (!$vb_return_all_locales) {
								$va_values = array_shift($va_values);
							}
							return $va_values;
						}
					}
					break;
				# -------------------------------------
				case 2:		// table_name.field_name
					if ($va_tmp[0] === $t_instance->tableName()) {
						if (!$t_instance->hasField($va_tmp[1])) {
							// try it as an attribute
							if (!$vb_return_as_array) {
								return $t_instance->getAttributesForDisplay($va_tmp[1], $vs_template, $pa_options);
							} else {
								$va_values = $t_instance->getAttributeDisplayValues($va_tmp[1], $t_instance->getPrimaryKey(), $pa_options);
								if (!$vb_return_all_locales) {
									$va_values = array_shift($va_values);
								}
								return $va_values;
							}
						}
					}
					break;
				# -------------------------------------
				case 3:		// table_name.field_name.sub_element
					if(!$this->hasField($va_tmp[2])) {
						if ($va_tmp[0] === $t_instance->tableName()) {
							if (!$t_instance->hasField($va_tmp[1])) {
								// try it as an attribute
								if (!$vb_return_as_array) {
									if (!$vs_template) { $vs_template = '^'.$va_tmp[2]; }
									return $t_instance->getAttributesForDisplay($va_tmp[1], $vs_template, $pa_options);
								} else {
									$va_values = $t_instance->getAttributeDisplayValues($va_tmp[1], $t_instance->getPrimaryKey(), $pa_options);
									$va_subvalues = array();
									
									if ($vb_return_all_locales) {
										foreach($va_values as $vn_attribute_id => $va_attributes_by_locale) {
											foreach($va_attributes_by_locale as $vn_locale_id => $va_attribute_values) {
												foreach($va_attribute_values as $vn_attribute_id => $va_data) {
													if(isset($va_data[$va_tmp[2]])) {
														$va_subvalues[$vn_attribute_id][$vn_locale_id][$vn_attribute_id] = $va_data[$va_tmp[2]];
													}
												}
											}
										}	
									} else {
										foreach($va_values as $vn_id => $va_attribute_values) {
											foreach($va_attribute_values as $vn_attribute_id => $va_data) {
												if(isset($va_data[$va_tmp[2]])) {
													$va_subvalues[$vn_attribute_id] = $va_data[$va_tmp[2]];
												}
											}
										}
									}
									
									return $va_subvalues;
								}
							}
						}
					}
					break;
				# -------------------------------------
			}
			return parent::get($ps_field, $pa_options);
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function getTypeID() {
			if (!isset($this->ATTRIBUTE_TYPE_ID_FLD) || !$this->ATTRIBUTE_TYPE_ID_FLD) { return null; }
			return $this->get($this->ATTRIBUTE_TYPE_ID_FLD);
		}
		# ------------------------------------------------------------------
		/**
		 * Field in this table that defines the type of the row; the type determines which attributes are applicable to the row
		 */
		public function getTypeFieldName() {
			return $this->ATTRIBUTE_TYPE_ID_FLD;
		}
		# ------------------------------------------------------------------
		/**
		 * List code (from ca_lists.list_code) of list defining types for this table
		 */
		public function getTypeListCode() {
			return isset($this->ATTRIBUTE_TYPE_LIST_CODE) ? $this->ATTRIBUTE_TYPE_LIST_CODE : null;
		}
		# ------------------------------------------------------------------
		public function getTypeName() {
			if ($t_list_item = $this->getTypeInstance()) {
				return $t_list_item->getLabelForDisplay(false);
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns ca_list_items.idno (aka "item code") for the type of the currently loaded row
		 *
		 * @return string - idno (aka "item code") for current row's type or null if no row is loaded or model does not support types
		 */
		public function getTypeCode() {
			if ($t_list_item = $this->getTypeInstance()) {
				return $t_list_item->get('idno');
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns ca_list_items.item_id (aka "type_id") for $ps_type_code
		 *
		 * @param string $ps_type_code Alphanumeric code for the type
		 * @return int - item_id (aka "type_id") for specified list item idno (aka "type code")
		 */
		public function getTypeIDForCode($ps_type_code) {
			$va_types = $this->getTypeList();
			
			foreach($va_types as $vn_type_id => $va_type_info) {
				if ($va_type_info['idno'] === $ps_type_code) {
					return $vn_type_id;
				}
			}
			
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns list of types for this table with locale-appropriate labels, keyed by type_id
		 */ 
		public function getTypeList() {
			$t_list = new ca_lists();
			
			$va_list = $t_list->getItemsForList($this->getTypeListCode());
			return is_array($va_list) ? caExtractValuesByUserLocale($va_list): array();
		}
		# ------------------------------------------------------------------
		/**
		 * Return ca_list_item instance for the type of the currently loaded row
		 */ 
		public function getTypeInstance() {
			if (!isset($this->ATTRIBUTE_TYPE_ID_FLD) || !$this->ATTRIBUTE_TYPE_ID_FLD) { return null; }
			if (!($vn_type_id = $this->get($this->ATTRIBUTE_TYPE_ID_FLD))) { return null; }
			
			$t_list_item = new ca_list_items($vn_type_id);
			return ($t_list_item->getPrimaryKey()) ? $t_list_item : null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns HTML <select> form element with type list
		 */ 
		public function getTypeListAsHTMLFormElement($ps_name, $pa_attributes=null, $pa_options=null) {
			$t_list = new ca_lists();
			if (isset($pa_options['childrenOfCurrentTypeOnly']) && $pa_options['childrenOfCurrentTypeOnly']) {
				$pa_options['childrenOnlyForItemID'] = $this->get('type_id');
			}
			
			return $t_list->getListAsHTMLFormElement($this->getTypeListCode(), $ps_name, $pa_attributes, $pa_options);
		}
		# ------------------------------------------------------------------
		// --- Forms
		# ------------------------------------------------------------------
		/**
		  * Returns display label for element specified by standard "get" bundle code (eg. <table_name>.<element_code> format)
		  */
		public function getDisplayLabel($ps_field) {
			$va_tmp = explode('.', $ps_field);
			if ($va_tmp[0] != $this->tableName()) { return null; }
			if (!$this->hasField($va_tmp[1])) {
				$va_tmp[1] = preg_replace('!^ca_attribute_!', '', $va_tmp[1]);	// if field space is a bundle placement-style bundlename (eg. ca_attribute_<element_code>) then strip it before trying to pull label
				return $this->getAttributeLabel($va_tmp[1]);	
			}
			return parent::getDisplayLabel($ps_field);
		}
		# --------------------------------------------------------------------------------------------
		/**
		  * Returns display description for element specified by standard "get" bundle code (eg. <table_name>.<bundle_name> format)
		  */
		public function getDisplayDescription($ps_field) {
			$va_tmp = explode('.', $ps_field);
			if ($va_tmp[0] != $this->tableName()) { return null; }
			if (!$this->hasField($va_tmp[1])) {
				$va_tmp[1] = preg_replace('!^ca_attribute_!', '', $va_tmp[1]);	// if field space is a bundle placement-style bundlename (eg. ca_attribute_<element_code>) then strip it before trying to pull label
				return $this->getAttributeDescription($va_tmp[1]);	
			}
			return parent::getDisplayDescription($ps_field);
		}
		# --------------------------------------------------------------------------------------------
		/**
		  * Returns HTML search form input widget for bundle specified by standard "get" bundle code (eg. <table_name>.<bundle_name> format)
		  * This method handles generation of search form widgets for all metadata elements bound to the primary table. If this method can't handle 
		  * the bundle (because it is not a metadata element bound to the primary table...) it will pass the request to the superclass implementation of 
		  * htmlFormElementForSearch()
		  *
		  * @param $po_request HTTPRequest
		  * @param $ps_field string
		  * @param $pa_options array
		  * @return string HTML text of form element. Will return null (from superclass) if it is not possible to generate an HTML form widget for the bundle.
		  * 
		  */
		public function htmlFormElementForSearch($po_request, $ps_field, $pa_options=null) {
			$va_tmp = explode('.', $ps_field);
			if ($va_tmp[0] != $this->tableName()) { return null; }
			if (!$this->hasField($va_tmp[1])) {
				$va_tmp[1] = preg_replace('!^ca_attribute_!', '', $va_tmp[1]);	// if field space is a bundle placement-style bundlename (eg. ca_attribute_<element_code>) then strip it before trying to pull label
				
				return $this->htmlFormElementForAttributeSearch($po_request, $va_tmp[1], array(
							'values' => (isset($pa_options['values']) && is_array($pa_options['values'])) ? $pa_options['values'] : array(),
							'width' => (isset($pa_options['width']) && ($pa_options['width'] > 0)) ? $pa_options['width'] : 20, 
							'height' => (isset($pa_options['height']) && ($pa_options['height'] > 0)) ? $pa_options['height'] : 1, 
							
							'format' => '^ELEMENT',
							'multivalueFormat' => '<i>^LABEL</i><br/>^ELEMENT'
						));
			}
			return parent::htmlFormElementForSearch($po_request, $ps_field, $pa_options);
		}
		# ------------------------------------------------------------------
		/**
		  * Get HTML form element bundle for metadata element
		  */
		public function getAttributeLabel($pm_element_code_or_id) {
			if (isset(BaseModelWithAttributes::$s_element_label_cache[$pm_element_code_or_id])) {
				$va_cached_labels = caExtractValuesByUserLocale(BaseModelWithAttributes::$s_element_label_cache);
				return $va_cached_labels[$pm_element_code_or_id]['name'];
			}
			if (!($t_element = $this->_getElementInstance($pm_element_code_or_id))) {
				return false;
			}
			return $t_element->getLabelForDisplay(false);
		}
		# ------------------------------------------------------------------
		// get HTML form element bundle for metadata element
		public function getAttributeDescription($pm_element_code_or_id) {
			if (isset(BaseModelWithAttributes::$s_element_label_cache[$pm_element_code_or_id])) {
				$va_cached_labels = caExtractValuesByUserLocale(BaseModelWithAttributes::$s_element_label_cache);
				return $va_cached_labels[$pm_element_code_or_id]['description'];
			}
			
			$va_label = $this->getAttributeLabelAndDescription($pm_element_code_or_id);
			
			return isset($va_label['description']) ? $va_label['description'] : '';
		}
		# ------------------------------------------------------------------
		// get HTML form element bundle for metadata element
		public function getAttributeLabelAndDescription($pm_element_code_or_id) {
			if (isset(BaseModelWithAttributes::$s_element_label_cache[$pm_element_code_or_id])) {
				$va_cached_labels = caExtractValuesByUserLocale(BaseModelWithAttributes::$s_element_label_cache);
				return $va_cached_labels[$pm_element_code_or_id];
			}
			if (!($t_element = $this->_getElementInstance($pm_element_code_or_id))) {
				return false;
			}
			$va_labels =  caExtractValuesByUserLocale($t_element->getPreferredLabels(null, false));
			foreach($va_labels as $vn_i => $va_labels_by_locale) {
				foreach($va_labels_by_locale as $vn_j => $va_label_values) {
					return $va_label_values;
				}
			}
			return '';
		}
		# ------------------------------------------------------------------
		// returns number of elements that comprise the attribute
		public function getNumberAttributeElements($pm_element_code_or_id) {
			if (!($t_element = $this->_getElementInstance($pm_element_code_or_id))) {
				return false;
			}
			
			$qr_hier = $t_element->getHierarchy();
			
			return ($qr_hier) ? $qr_hier->numRows() : 0;
		}
		# ------------------------------------------------------------------
		// get HTML form element bundle for metadata element
		public function getAttributeHTMLFormBundle($po_request, $ps_form_name, $pm_element_code_or_id, $ps_placement_code, $pa_bundle_settings, $pa_options) {
			if (!($t_element = $this->_getElementInstance($pm_element_code_or_id))) {
				return false;
			}
			if ($t_element->get('parent_id')) { 
				$this->postError(1930, _t('Element is not the root of the element set'), 'BaseModelWithAttributes->getAttributeHTMLFormBundle()');
				return false;
			}
			
			// get all elements of this element set
			$va_element_set = $t_element->getElementsInSet();
			
			// get attributes of this element attached to this row
			$va_attributes = $this->getAttributesByElement($pm_element_code_or_id);
			
			// TODO: how do we not-hardcode {fieldNamePrefix}? is this even a problem?
			
			$t_attr = new ca_attributes();
			
			$va_element_codes = array();
			$va_elements_by_container = array();
			$vb_should_output_locale_id = ($t_element->getSetting('doesNotTakeLocale')) ? false : true;
			$va_element_value_defaults = array();
			$va_elements_without_break_by_container = array();
			$va_elements_break_by_container = array();
			
			foreach($va_element_set as $va_element) {
				if ($va_element['datatype'] == 0) {		// containers are not active form elements
					$va_elements_break_by_container[$va_element['element_id']] = (int)$va_element["settings"]["lineBreakAfterNumberOfElements"] ? (int)$va_element["settings"]["lineBreakAfterNumberOfElements"] : -1;
					
					continue;
				}
				
				$va_label = $this->getAttributeLabelAndDescription($va_element['element_id']);

				if(!isset($va_elements_without_break_by_container[$va_element['parent_id']])){
					$va_elements_without_break_by_container[$va_element['parent_id']] = 1;
				} else {
					$va_elements_without_break_by_container[$va_element['parent_id']] += 1;
				}

				if($va_elements_without_break_by_container[$va_element['parent_id']] == $va_elements_break_by_container[$va_element['parent_id']]+1){
					$va_elements_without_break_by_container[$va_element['parent_id']] = 1;
					$vs_br = "</td></tr></table><table class=\"attributeListItem\" cellpadding=\"0px\" cellspacing=\"0px\"><tr><td class=\"attributeListItem\">";
				} else {
					$vs_br = "";
				}

				$va_elements_by_container[$va_element['parent_id']][] = $vs_br.ca_attributes::attributeHtmlFormElement($va_element, array(
					'label' => (sizeof($va_element_set) > 1) ? $va_label['name'] : '',
					'description' => $va_label['description'],
					't_subject' => $this,
					'po_request' => $po_request,
					'ps_form_name' => $ps_form_name,
					'format' => ''
				));
				
				//if the elements datatype returns true from renderDataType, then force render the element
				if(Attribute::renderDataType($va_element)) {
					return array_pop($va_elements_by_container[$va_element['element_id']]);
				}
				$va_element_ids[] = $va_element['element_id'];
				
				if ($vs_setting = Attribute::getValueDefault($va_element)) {
					$tmp_element = $this->_getElementInstance($va_element['element_id']);
					$va_element_value_defaults[$va_element['element_id']] = $tmp_element->getSetting($vs_setting);
				}
			}
			
			if ($vb_should_output_locale_id) {	// output locale_id, if necessary, in its' own special '_locale_id' container
				$va_elements_by_container['_locale_id'] = array('hidden' => false, 'element' => $t_attr->htmlFormElement('locale_id', '^ELEMENT', array('classname' => 'labelLocale', 'id' => "{fieldNamePrefix}locale_id_{n}", 'name' => "{fieldNamePrefix}locale_id_{n}", "value" => "", 'no_tooltips' => true, 'dont_show_null_value' => true, 'hide_select_if_only_one_option' => true, 'WHERE' => array('(dont_use_for_cataloguing = 0)'))));
				if (stripos($va_elements_by_container['_locale_id']['element'], "'hidden'")) {
					$va_elements_by_container['_locale_id']['hidden'] = true;
				}
			}
			
			
			$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
			
			$o_view->setVar('request', $po_request);
			$o_view->setVar('id_prefix', $ps_form_name.'_attribute_'.$t_element->get('element_id'));
			$o_view->setVar('elements', $va_elements_by_container);
			$o_view->setVar('error_source_code', 'ca_attribute_'.$t_element->get('element_code'));
			$o_view->setVar('element_ids', $va_element_ids);
			$o_view->setVar('element_set_label', $this->getAttributeLabel($t_element->get('element_id')));
			$o_view->setVar('placement_code', $ps_placement_code);
			$o_view->setVar('render_mode', $t_element->getSetting('render'));	// only set for list attributes (as of 26 Sept 2010 at least)
			
			if ($t_restriction = $this->getTypeRestrictionInstance($t_element->get('element_id'))) {
				$o_view->setVar('min_num_repeats', $t_restriction->getSetting('minAttributesPerRow'));
				$o_view->setVar('max_num_repeats', $t_restriction->getSetting('maxAttributesPerRow'));
				$o_view->setVar('min_num_to_display', $t_restriction->getSetting('minimumAttributeBundlesToDisplay'));
			}
			
			// these are lists of associative arrays representing attributes that were rejected in a save() action
			// during the current request. They are used to maintain the state of the form so the user can modify the
			// input that caused the error
			$o_view->setVar('failed_insert_attribute_list', $this->getFailedAttributeInserts($pm_element_code_or_id));
			$o_view->setVar('failed_update_attribute_list', $this->getFailedAttributeUpdates($pm_element_code_or_id));
		
			// set the list of existing attributes for the current row
			$o_view->setVar('attribute_list', $this->getAttributesByElement($t_element->get('element_id')));
			
			// pass list of element default values
			$o_view->setVar('element_value_defaults', $va_element_value_defaults);
			
			// pass bundle settings to view
			$o_view->setVar('settings', $pa_bundle_settings);
			
			return $o_view->render('ca_attributes.php');
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function htmlFormElementForAttributeSearch($po_request, $pm_element_code_or_id, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if (!($t_element = $this->_getElementInstance($pm_element_code_or_id))) {
				return false;
			}
			
			if ($t_element->get('parent_id')) { 
				$this->postError(1930, _t('Element is not the root of the element set'), 'BaseModelWithAttributes->htmlFormElementForAttributeSearch()');
				return false;
			}
			
			// get all elements of this element set
			$va_element_set = $t_element->getElementsInSet();
			
			$t_attr = new ca_attributes();
			
			$va_element_codes = array();
			$va_elements_by_container = array();
			
			
			if (sizeof($va_element_set) > 1) {
				$vs_format = isset($pa_options['multivalueFormat']) ? $pa_options['multivalueFormat'] : null;
			} else {
				$vs_format = isset($pa_options['format']) ? $pa_options['format'] : null;
			}
			$pa_options['format'] = $vs_format;
			
			if ((sizeof($va_element_set) > 1) && isset($pa_options['width']) && ($pa_options['width'] > 0)) {
				if (($pa_options['width'] = ceil($pa_options['width']/sizeof($va_element_set))) < 20) { 
					$pa_options['width'] = 20;
				}
			}
			
			foreach($va_element_set as $va_element) {
				$va_override_options = array();
				if ($va_element['datatype'] == 0) {		// containers are not active form elements
					continue;
				}
				
				$va_label = $this->getAttributeLabelAndDescription($va_element['element_id']);
				
				$vs_element = $this->tableName().'.'.$va_element['element_code'];
				$vs_value = (isset($pa_options['values']) && isset($pa_options['values'][$vs_element])) ? $pa_options['values'][$vs_element] : '';
				
				$vs_form_element = ca_attributes::attributeHtmlFormElement($va_element, array_merge(array(
					'label' => $va_label['name'],
					'description' => $va_label['description'],
					't_subject' => $this,
					'po_request' => $po_request,
					'nullOption' => '-',
					'value' => $vs_value,
					'forSearch' => true
				), array_merge($pa_options, $va_override_options)));
				//
				// prep element for use as search element
				//
				// ... replace value
				$vs_form_element = str_replace('{{'.$va_element['element_id'].'}}', $vs_value, $vs_form_element);
				
				// ... replace name of form element
				$vs_form_element = str_replace('{fieldNamePrefix}'.$va_element['element_id'].'_{n}', str_replace('.', '_', $this->tableName().'.'.$va_element['element_code']), $vs_form_element);
				
				$va_elements_by_container[$va_element['parent_id'] ? $va_element['parent_id'] : $va_element['element_id']][] = $vs_form_element;
				//if the elements datatype returns true from renderDataType, then force render the element
				if(Attribute::renderDataType($va_element)) {
					return array_pop($va_elements_by_container[$va_element['element_id']]);
				}
				$va_element_ids[] = $va_element['element_id'];
			}
			
			
			$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
			
			$o_view->setVar('request', $po_request);
			$o_view->setVar('elements', $va_elements_by_container);
			$o_view->setVar('element_ids', $va_element_ids);
			$o_view->setVar('element_set_label', $this->getAttributeLabel($t_element->get('element_id')));
			
			return $o_view->render('ca_search_form_attributes.php');
		}
		# ------------------------------------------------------------------
		public function getReferencedAttributeValues($pm_element_code_or_id, $pa_reference_limit_ids) {
			if (!($vn_element_id = $this->_getElementID($pm_element_code_or_id))) { return null; }
			return ca_attributes::getReferencedAttributes($this->getDb(), $this->tableNum(), $pa_reference_limit_ids, array('element_id' => $vn_element_id));s;
		}
		# ------------------------------------------------------------------
		// --- Retrieval
		# ------------------------------------------------------------------
		// returns an array of all attributes attached to the current row
		public function getAttributes($pa_options=null) {
			if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
			
			return ca_attributes::getAttributes($this->getDb(), $this->tableNum(), $vn_row_id, $pa_options);
		}
		# ------------------------------------------------------------------
		// returns an array of all attributes with the specified element_id attached to the current row
		public function getAttributesByElement($pm_element_code_or_id, $pa_options=null) {
			if (isset($pa_options['row_id']) && $pa_options['row_id']) {
				$vn_row_id = $pa_options['row_id'];
			} else {
				$vn_row_id = $this->getPrimaryKey();
			}
			if (!$vn_row_id) { return null; }

			$va_attributes = ca_attributes::getAttributes($this->getDb(), $this->tableNum(), $vn_row_id, array());
		
			$va_attr = array();
			
			$vn_element_id = $this->_getElementID($pm_element_code_or_id);
			foreach($va_attributes as $o_attr) {
				if ($o_attr->getElementID() == $vn_element_id) { $va_attr[] = $o_attr; }
			}
			return $va_attr;
		}
		# ------------------------------------------------------------------
		// returns an array of all attributes with the specified element_id attached to the current row
		public function getAttributeCountByElement($pm_element_code_or_id) {
			if (!($vn_row_id = $this->getPrimaryKey())) { 
				if (isset($pa_options['row_id']) && $pa_options['row_id']) {
					$vn_row_id = $pa_options['row_id'];
				} else {
					return null; 
				}
			}

			$vn_element_id = $this->_getElementID($pm_element_code_or_id);
			return ca_attributes::getAttributeCount($this->getDb(), $this->tableNum(), $vn_row_id, $vn_element_id);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns single attribute value for the loaded row. 
		 * TODO: always returns the first value of the attribute; for simple single-value attributes this is
		 * the right thing to do, but this doesn't work with complex (multi-value) attributes since the first value
		 * is always the root container, which is always value-less.
		 */
		public function getSimpleAttributeValue($pm_element_code_or_id, $pn_index=0, $pa_options=null) {
			$va_attrs = $this->getAttributesByElement($pm_element_code_or_id);
			
			if (sizeof($va_attrs)) {
				if (($pn_index >= 0) && ($pn_index < sizeof($va_attrs))) { $pn_index = 0; }
				
				$va_attr = $va_attrs[$pn_index];
				
				$va_values = $va_attr->getValues();
				$va_value = $va_values[0];
				return $va_value->getDisplayValue($pa_options);
			}
			return null;
		}
		# ------------------------------------------------------------------
		// returns the specific attribute with the specified attribute_id
		// ** assuming it's attached to the current row **
		public function getAttributeByID($pn_attribute_id) {
			// TODO: Implement
			return false;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns array of attributes, each of which is an array of values ready for display. 
		 * The returned array is indexed by attribute element ID. Each array value is an array
		 * indexed by attribute_id. The corresponding values are arrays of attribute values indexed by element_code
		 *
		 * @param $pm_element_code_or_id string|integer -
		 * @param $pn_row_id integer -
		 * @param $pa_options array -
		 *				convertLinkBreaks - if set to true, will attemp to convert line break characters to HTML <p> and <br> tags; default is false.
		 *				locale - if set to a valid locale_id or locale code, values will be returned in locale *if available*, otherwise will fallback to values in languages that are available using the standard fallback mechanism. Default is to use user's current locale.
		 *				returnAllLocales - if set to true, values for all locales are returned, locale option is ignored and the returned array is indexed first by attribute_id and then by locale_id. Default is false.
		 *				indexByRowID - if true first index of returned array is $pn_row_id, otherwise it is the element_id of the retrieved metadata element	
		 *				convertCodesToDisplayText - 
		 * @return array
		 */
		public function getAttributeDisplayValues($pm_element_code_or_id, $pn_row_id, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			$va_attribute_list = $this->getAttributesByElement($pm_element_code_or_id, array('row_id' => $pn_row_id));
			if (!is_array($va_attribute_list)) { return array(); }
			$va_attributes = array();
			
			foreach($va_attribute_list as $o_attribute) {
				$va_values = $o_attribute->getValues();
				
				$va_display_values = array();
				foreach($va_values as $o_value) {
					$vs_element_code = $this->_getElementCode($o_value->getElementID());
					
					if (get_class($o_value) == 'ListAttributeValue') {
						$t_element = $this->_getElementInstance($o_value->getElementID());
						$vn_list_id = (!isset($pa_options['convertCodesToDisplayText']) || !$pa_options['convertCodesToDisplayText']) ? null : $t_element->get('list_id');
					} else {
						$vn_list = null;
					}
					if (isset($pa_options['convertLinkBreaks']) && $pa_options['convertLinkBreaks']) {
						$vs_converted_value = preg_replace("!(\n|\r\n){2}!","<p/>",$o_value->getDisplayValue(array_merge($pa_options, array('list_id' => $vn_list_id))));
						$va_display_values[$vs_element_code] = preg_replace("![\n]{1}!","<br/>",$vs_converted_value);
					} else {
						$va_display_values[$vs_element_code] = $o_value->getDisplayValue(array_merge($pa_options, array('list_id' => $vn_list_id)));
					}
				}
				
				if (isset($pa_options['indexByRowID']) && $pa_options['indexByRowID']) {
					$vs_index = $pn_row_id;
				} else {
					$vs_index = $o_attribute->getElementID();
				}
				$va_attributes[$vs_index][$o_attribute->getLocaleID()][$o_attribute->getAttributeID()] = $va_display_values;
			}
				
			if (!isset($pa_options['returnAllLocales']) || !$pa_options['returnAllLocales']) {
				// if desired try to return values in a preferred language/locale
				$va_preferred_locales = null;
				if (isset($pa_options['locale']) && $pa_options['locale']) {
					$va_preferred_locales = array($pa_options['locale']);
				}
				return caExtractValuesByUserLocale($va_attributes, null, $va_preferred_locales, array());
			}
			return $va_attributes;
		}
		# ------------------------------------------------------------------
		/**
		 * Return raw value of attribute for a given row. The "raw" value is the display value, or values, joined with the specified
		 * delimiter and filtered on the current user locale.
		 *
		 * @param int $pn_row_id row_id attribute is attached to in the table the instance represents
		 * @param mixed $pm_element_code_or_id Element code or element_id of the metadata element to load the attribute for
		 * @param mixed $pm_sub_element_code_or_id Optional sub-element code or element_id to fetch value for. This is used to select a sub-element in complex attributes; if you want the top-level (aka root) element leave this set to null
		 * @param string $ps_delimiter Optional delimiter to use when multiple values are defined. Default is a comma (",").
		 * @return string The "raw" value
		 */
		public function getRawValue($pn_row_id, $pm_element_code_or_id, $pm_sub_element_code_or_id=null, $ps_delimiter=',') {
			$va_attributes = ca_attributes::getAttributes($this->getDb(), $this->tableNum(), $pn_row_id, array());
		
			$vn_element_id = $this->_getElementID($pm_element_code_or_id);
			$vn_sub_element_id = $pm_sub_element_code_or_id ? $this->_getElementID($pm_sub_element_code_or_id) : null;
			
			$va_ret_values = array();
			foreach($va_attributes as $o_attr) {
				if ($o_attr->getElementID() == $vn_element_id) { 
					$va_values = $o_attr->getDisplayValues(true);
					$va_ret_values[0][$o_attr->getLocaleID()] = $va_values[$vn_sub_element_id ? $vn_sub_element_id : $vn_element_id];
				}
			}
			
			$va_ret_values = array_values(caExtractValuesByUserLocale($va_ret_values));
			
			return join($ps_delimiter, $va_ret_values);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns text of attributes in the user's currently selected locale, or else falls back to
	     * whatever locale is available
	     *
	     * Supported options
	     *	delimiter = text to use between attribute values; default is a single space
	     *	convertLinkBreaks = if true will convert line breaks to HTML <br/> tags for display in a web browser; default is false
		 */
		public function getAttributesForDisplay($pm_element_code_or_id, $ps_template=null, $pa_options=null) {
			if (!($vn_row_id = $this->getPrimaryKey())) { 
				if (!($vn_row_id = $pa_options['row_id'])) {
					return null; 
				}
			}
			if (!($t_element = $this->_getElementInstance($pm_element_code_or_id))) { return null; }
			if (!is_array($pa_options)) { $pa_options = array(); }
			
			$va_tmp = $this->getAttributeDisplayValues($pm_element_code_or_id, $vn_row_id, array_merge($pa_options, array('returnAllLocales' => false)));
		
			
			$vs_delimiter = null;
			//if ($t_element->get('datatype') == 0) {
				if ($vs_template_tmp = $t_element->getSetting('displayTemplate', true)) {
					$ps_template = $vs_template_tmp;
				}
				$vs_delimiter = $t_element->getSetting('displayDelimiter', true);
		//	}
			
			if (isset($pa_options['delimiter'])) {
				$vs_delimiter = $pa_options['delimiter'];
			}
			
			if ($ps_template) {
				$va_templated_values = array();
				foreach($va_tmp as $vn_id => $va_value_list) {
					foreach($va_value_list as $va_value) {
						$vs_template = $ps_template;
						
						$va_element_codes = array_keys($va_value);
						usort($va_element_codes, "caLengthSortHelper");
						
						foreach($va_element_codes as $vn_i => $vs_element_code) {
							$vs_value = $va_value[$vs_element_code];
							$vs_template = str_replace("^".$vs_element_code, $vs_value, $vs_template);
						}
						
						if ($vs_template) { $va_templated_values[] = $vs_template; }
					}
				}
				$vs_text = preg_replace('!\^[A-Za-z0-9_\-]+!', '', join($vs_delimiter, $va_templated_values)); // remove un-replaced tags
				if (isset($pa_options['convertLineBreaks']) && $pa_options['convertLineBreaks']) {
					$vs_text = caConvertLineBreaks($vs_text);
				}
				return $vs_text;
			} else {
				// no template
				$va_attribute_list = array();
				foreach($va_tmp as $vn_id => $va_value_list) {
					foreach($va_value_list as $va_value) {
						foreach($va_value as $vs_element_code => $vs_value) {
							$va_attribute_list[] = $vs_value;
						}
					}
				}

				//Allow getAttributesForDisplay to return an array value (for "special" returns such as coordinates or raw dates)
				// if the value returns only a single value and it's an array. This is useful for getting "specials" via SearchResult::get()
				if ((sizeof($va_attribute_list) === 1) && (is_array($va_attribute_list[0]))) { return $va_attribute_list[0]; }
				
				
				$vs_text = join($vs_delimiter, $va_attribute_list);
				
				if (isset($pa_options['convertLineBreaks']) && $pa_options['convertLineBreaks']) {
					$vs_text = caConvertLineBreaks($vs_text);
				}
				return $vs_text;
			}
		}
		# ------------------------------------------------------------------
		/**
		 * Returns associative array, keyed by primary key value with values being
		 * the preferred label of the row from a suitable locale, ready for display 
		 * 
		 * @param $pa_ids indexed array of primary key values to fetch attribute for
		 */
		public function getAttributeForIDs($pm_element_code_or_id, $pa_ids, $pa_options=null) {
			if (!($vn_element_id = $this->_getElementID($pm_element_code_or_id))) { return null; }

			return caExtractValuesByUserLocale(ca_attributes::getAttributeValueForIDs($this->getDb(), $this->tableNum(), $pa_ids, $vn_element_id, $pa_options));
		}
		# ------------------------------------------------------------------
		// --- Utilties
		# ------------------------------------------------------------------
		// copies all attributes attached to the current row to the row specified by $pn_id
		public function copyAttributesTo($pn_row_id) {
			// TODO: Implement
			return false;
		}
		# ------------------------------------------------------------------
		// --- Methods to manage bindings between elements and tables
		# ------------------------------------------------------------------
		// add element to type (or general use when type_id=null) for this table
		public function addMetadataElementToType($pm_element_code_or_id, $pn_type_id) {
			
 			require_once(__CA_APP_DIR__.'/models/ca_metadata_elements.php');
 			require_once(__CA_APP_DIR__.'/models/ca_metadata_type_restrictions.php');
 
			if (!($t_element = $this->_getElementInstance($pm_element_code_or_id))) {
				return false;
			}
			$t_restriction = new ca_metadata_type_restrictions();
			$t_restriction->setMode(ACCESS_WRITE);
			$t_restriction->set('table_num', $this->tableNum());
			$t_restriction->set('element_id', $t_element->getPrimaryKey());
			$t_restriction->set('type_id', $pn_type_id);	// TODO: validate $pn_type_id
			$t_restriction->insert();
			
			if ($t_restriction->numErrors()) {
				$this->postError(1980, _t("Couldn't add element to restriction list: %1", join('; ', $t_restriction->getErrors())), 'BaseModelWithAttributes->addMetadataElementToType()');
				return false;
			}
			return true;
		}
		# ------------------------------------------------------------------
		// remove element from type (or general use when type_id=null) for this table
		public function removeMetadataElementFromType($pm_element_code_or_id, $pn_type_id) {
 			require_once(__CA_APP_DIR__.'/models/ca_metadata_elements.php');
 			require_once(__CA_APP_DIR__.'/models/ca_metadata_type_restrictions.php');
 
			if (!($t_element = $this->_getElementInstance($pm_element_code_or_id))) {
				return false;
			}
			$t_restriction = new ca_metadata_type_restrictions();
			if ($t_restriction->load(array('element_id' => $t_element->getPrimaryKey(), 'type_id' => $type_id, 'table_num' => $this->tableNum()))) {
				$t_restriction->setMode(ACCESS_WRITE);
				$t_restriction->delete();
				if ($t_restriction->numErrors()) {
					$this->postError(1981, _t("Couldn't remove element from restriction list: %1",join('; ', $t_restriction->getErrors())), 'BaseModelWithAttributes->addMetadataElementToType()');
					return false;
				}
			}
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns list of metdata element codes applicable to the current row. If there is no loaded row and $pn_type_id
		 * is not set then all attributes applicable to the model as a whole (regardless of type restrictions) are returned.
		 *
		 * Normally only top-level attribute codes are returned. This is good: in general you should only be dealing with attributes
		 * via the top-level element. However, there are a few cases where you might need an inventory of *all* element codes that can
		 * be attached to a model, even those that are part of an element hierarchy. Setting the $pb_include_sub_element_codes to true
		 * will include sub-elements in the returned list.
		 */
 		public function getApplicableElementCodes($pn_type_id=null, $pb_include_sub_element_codes=false, $pb_dont_cache=true) {
 			 if (!$pb_dont_cache && is_array($va_tmp = BaseModelWithAttributes::$s_applicable_element_code_cache[$this->tableNum().'/'.$pn_type_id.'/'.($pb_include_sub_element_codes ? 1 : 0)])) {
 			 	return $va_tmp;
 			 }
 			
 			if (isset($this->ATTRIBUTE_TYPE_ID_FLD) && (($pn_type_id) || (($pn_type_id = $this->get($this->ATTRIBUTE_TYPE_ID_FLD)) > 0))) {
 				$vs_type_sql = '((camtr.type_id = '.intval($pn_type_id).') OR (camtr.type_id IS NULL)) AND';
 			} else {
 				$vs_type_sql = '';
 			}
 			
 			$o_db = $this->getDb();
 			
 			$qr_res = $o_db->query("
 				SELECT camtr.element_id, came.element_code, cmel.*
 				FROM ca_metadata_type_restrictions camtr
 				INNER JOIN ca_metadata_elements AS came ON camtr.element_id = came.element_id
 				INNER JOIN ca_metadata_element_labels AS cmel ON cmel.element_id = came.element_id
 				WHERE
 					$vs_type_sql (camtr.table_num = ?)
 			", (int)$this->tableNum());
 			
 			$va_codes = array();
 			while($qr_res->nextRow()) {
 				$vn_element_id = (int)$qr_res->get('element_id');
 				$vs_element_code = (string)$qr_res->get('element_code');
 				$vn_locale_id = (int)$qr_res->get('locale_id');
 				
 				BaseModelWithAttributes::$s_element_label_cache[$vn_element_id][$vn_locale_id] = BaseModelWithAttributes::$s_element_label_cache[$vs_element_code][$vn_locale_id] = $qr_res->getRow();
 				if (isset($va_codes[$vn_element_id])) { continue; }
 				$va_codes[$vn_element_id] = $vs_element_code;
 			}
 			
 			if ($pb_include_sub_element_codes && sizeof($va_codes)) {
 				$qr_res = $o_db->query("
					SELECT came.element_id, came.element_code
					FROM ca_metadata_elements came
					WHERE
						came.hier_element_id IN (".join(',', array_keys($va_codes)).")
				");
				while($qr_res->nextRow()) {
					$va_codes[$qr_res->get('element_id')] = $qr_res->get('element_code');
				}
 			}
 			BaseModelWithAttributes::$s_applicable_element_code_cache[$this->tableNum().'/'.$pn_type_id.'/'.($pb_include_sub_element_codes ? 1 : 0)] = $va_codes;
 			return $va_codes;
 		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		 public function isValidMetadataElement($pn_element_code_or_id) {
		 	$vn_element_id = $this->_getElementID($pn_element_code_or_id);
		 	$va_codes = $this->getApplicableElementCodes(null, false, false);
		 	
		 	return (bool)$va_codes[$vn_element_id];
		 }
		# ------------------------------------------------------------------
		/**
		 * Returns an instance of ca_metadata_type_restrictions containing the row (and settings) for the
		 * specified element_set (identified by $pn_element_id) as it relates to the current row; returns
		 * null if element_id is not applicable to the current row
		 */
		public function getTypeRestrictionInstance($pn_element_id) {
			$t_restriction = new ca_metadata_type_restrictions();
			if ($t_restriction->load(array('element_id' => $pn_element_id, 'table_num' => $this->tableNum(), 'type_id' => $this->get($this->ATTRIBUTE_TYPE_ID_FLD)))) {
				return $t_restriction;
			} else {
				if ($t_restriction->load(array('element_id' => $pn_element_id, 'table_num' => $this->tableNum(), 'type_id' => null))) {
					return $t_restriction;
				}
			}
			return null;
		}
		# ------------------------------------------------------------------
		# State maintenance
		# ------------------------------------------------------------------
		public function setFailedAttributeInserts($pm_element_code_or_id, $pa_attribute_values) {
			$this->opa_failed_attribute_inserts[$this->_getElementID($pm_element_code_or_id)] = $pa_attribute_values;
		}
		# ------------------------------------------------------------------
		public function getFailedAttributeInserts($pm_element_code_or_id) {
			$vs_k = $this->_getElementID($pm_element_code_or_id);
			return isset($this->opa_failed_attribute_inserts[$vs_k]) ? $this->opa_failed_attribute_inserts[$vs_k] : null;
		}
		# ------------------------------------------------------------------
		public function setFailedAttributeUpdates($pm_element_code_or_id, $pa_attribute_values) {
			$this->opa_failed_attribute_updates[$this->_getElementID($pm_element_code_or_id)] = $pa_attribute_values;
		}
		# ------------------------------------------------------------------
		public function getFailedAttributeUpdates($pm_element_code_or_id) {
			$vs_k = $this->_getElementID($pm_element_code_or_id);
			return isset($this->opa_failed_attribute_updates[$vs_k]) ? $this->opa_failed_attribute_updates[$vs_k] : null;
		}
		# ------------------------------------------------------------------
		# Private
		# ------------------------------------------------------------------
		protected function _getElementInstance($pm_element_code_or_id) {
			if (!$pm_element_code_or_id) { 
				$this->postError(1950, _t("Element code or id must not be blank"), "BaseModelWithAttributes->_getElementInstance()");
				return false;
			}
 			if (isset(BaseModelWithAttributes::$s_element_instance_cache[$pm_element_code_or_id]) && BaseModelWithAttributes::$s_element_instance_cache[$pm_element_code_or_id]) {
 				return BaseModelWithAttributes::$s_element_instance_cache[$pm_element_code_or_id];
 			}
 			
 			require_once(__CA_APP_DIR__.'/models/ca_metadata_elements.php');
			$t_element = new ca_metadata_elements();
			if (!is_numeric($pm_element_code_or_id)) {
				if ($t_element->load(array('element_code' => $pm_element_code_or_id))) {
					return BaseModelWithAttributes::$s_element_instance_cache[$pm_element_code_or_id] = $t_element;
				}
			}
			if ($t_element->load($pm_element_code_or_id)) {
				return BaseModelWithAttributes::$s_element_instance_cache[$pm_element_code_or_id] = $t_element;
			} else {
				$this->postError(1950, _t("Element code or id '%1' is invalid", $pm_element_code_or_id), "BaseModelWithAttributes->_getElementInstance()");
				return false;
			}
		}
		# ------------------------------------------------------------------
		protected function _getElementID($pm_element_code_or_id) {
			if (isset(BaseModelWithAttributes::$s_element_id_lookup_cache[$pm_element_code_or_id])) { return BaseModelWithAttributes::$s_element_id_lookup_cache[$pm_element_code_or_id]; }
			
			if (!($t_element = $this->_getElementInstance($pm_element_code_or_id))) { return null; }
			
			 BaseModelWithAttributes::$s_element_code_lookup_cache[$t_element->get('element_code')] = BaseModelWithAttributes::$s_element_code_lookup_cache[$t_element->getPrimaryKey()] = $t_element->get('element_code');
			return BaseModelWithAttributes::$s_element_id_lookup_cache[$t_element->getPrimaryKey()] = BaseModelWithAttributes::$s_element_id_lookup_cache[$t_element->get('element_code')] = $t_element->getPrimaryKey();
		}
		# ------------------------------------------------------------------
		protected function _getElementCode($pm_element_code_or_id) {
			if (isset(BaseModelWithAttributes::$s_element_code_lookup_cache[$pm_element_code_or_id])) { return BaseModelWithAttributes::$s_element_code_lookup_cache[$pm_element_code_or_id]; }
		
			if (!($t_element = $this->_getElementInstance($pm_element_code_or_id))) { return null; }
			
			BaseModelWithAttributes::$s_element_id_lookup_cache[$t_element->getPrimaryKey()] = BaseModelWithAttributes::$s_element_id_lookup_cache[$t_element->get('element_code')] = $t_element->getPrimaryKey();
			return BaseModelWithAttributes::$s_element_code_lookup_cache[$t_element->get('element_code')] = BaseModelWithAttributes::$s_element_code_lookup_cache[$t_element->getPrimaryKey()] = $t_element->get('element_code');
		}
		# ------------------------------------------------------------------
	}
?>