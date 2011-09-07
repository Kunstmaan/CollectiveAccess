<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BundlableLabelableBaseModelWithAttributes.php : base class for models that take application of bundles
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2011 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__."/ca/IBundleProvider.php");
require_once(__CA_LIB_DIR__."/ca/LabelableBaseModelWithAttributes.php");
require_once(__CA_MODELS_DIR__."/ca_editor_uis.php");
require_once(__CA_LIB_DIR__."/core/Plugins/SearchEngine/CachedResult.php");

require_once(__CA_LIB_DIR__."/ca/IDNumbering.php");

class BundlableLabelableBaseModelWithAttributes extends LabelableBaseModelWithAttributes implements IBundleProvider {
	# ------------------------------------------------------
	protected $BUNDLES = array(
		
	);
	
	protected $opo_idno_plugin_instance;
	# ------------------------------------------------------
	public function __construct($pn_id=null) {
		parent::__construct($pn_id);	# call superclass constructor
		
		$this->initLabelDefinitions();
	}
	# ------------------------------------------------------
	/**
	 * Overrides load() to initialize bundle specifications
	 */
	public function load ($pm_id=null) {
		$vn_rc = parent::load($pm_id);
		$this->initLabelDefinitions();
		
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Override insert() to check type_id (or whatever the type key is called in the table as returned by getTypeFieldName())
	 * against the ca_lists list for the table (as defined by getTypeListCode())
	 */ 
	public function insert($pa_options=null) {
		$vb_we_set_transaction = false;
		
		if (!$this->inTransaction()) {
			$this->setTransaction(new Transaction($this->getDb()));
			$vb_we_set_transaction = true;
		}
		
		$this->opo_app_plugin_manager->hookBeforeBundleInsert(array('id' => null, 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this));
		
		$vb_web_set_change_log_unit_id = BaseModel::setChangeLogUnitID();
		
		// check that type_id is valid for this table
		$t_list = new ca_lists();
		$vn_type_id = $this->get($this->getTypeFieldName());
		$va_field_info = $this->getFieldInfo($this->getTypeFieldName());
		
		$vb_error = false;
		if ($this->getTypeFieldName() && !(!$vn_type_id && $va_field_info['IS_NULL'])) {
			if (!($vn_ret = $t_list->itemIsEnabled($this->getTypeListCode(), $vn_type_id))) {
				if(is_null($vn_ret)) {
					$this->postError(2510, _t("Type id %1 is invalid", $vn_type_id), "BundlableLabelableBaseModelWithAttributes->insert()");
				} else {
					$this->postError(2510, _t("Type id %1 is not enabled", $vn_type_id), "BundlableLabelableBaseModelWithAttributes->insert()");
				}
				$vb_error = true;
			}
		}
		
		if (!$this->_validateIncomingAdminIDNo(true, true)) { $vb_error =  true; }
		
		if ($vb_error) {			
			// push all attributes onto errored list
			$va_inserted_attributes_that_errored = array();
			foreach($this->opa_attributes_to_add as $va_info) {
				$va_inserted_attributes_that_errored[$va_info['element']][] = $va_info['values'];
			}
			foreach($va_inserted_attributes_that_errored as $vs_element => $va_list) {
				$this->setFailedAttributeInserts($vs_element, $va_list);
			}
			
			if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
			if ($vb_we_set_transaction) { $this->removeTransaction(false); }
			$this->_FIELD_VALUES[$this->primaryKey()] = null;		// clear primary key set by BaseModel::insert()
			return false;
		}
		
		$this->_generateSortableIdentifierValue();
		
		// stash attributes to add
		$va_attributes_added = $this->opa_attributes_to_add;
		if (!parent::insert($pa_options)) {	
			// push all attributes onto errored list
			$va_inserted_attributes_that_errored = array();
			foreach($va_attributes_added as $va_info) {
				if (isset($this->opa_failed_attribute_inserts[$va_info['element']])) { continue; }
				$va_inserted_attributes_that_errored[$va_info['element']][] = $va_info['values'];
			}
			foreach($va_inserted_attributes_that_errored as $vs_element => $va_list) {
				$this->setFailedAttributeInserts($vs_element, $va_list);
			}
			
			if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
			if ($vb_we_set_transaction) { $this->removeTransaction(false); }
			$this->_FIELD_VALUES[$this->primaryKey()] = null;		// clear primary key set by BaseModel::insert()
			return false;
		}
		
		if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
	
		$this->opo_app_plugin_manager->hookAfterBundleInsert(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this));
		
		if ($vb_we_set_transaction) { $this->removeTransaction(true); }
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Override update() to generate sortable version of user-defined identifier field
	 */ 
	public function update($pa_options=null) {
		$vb_web_set_change_log_unit_id = BaseModel::setChangeLogUnitID();
		
		$this->opo_app_plugin_manager->hookBeforeBundleUpdate(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this));
		
		$va_errors = array();
		if (!$this->_validateIncomingAdminIDNo(true, false)) { 
			 $va_errors = $this->errors();
			 // don't save number if it's invalid
			 if ($vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD')) {
			 	$this->set($vs_idno_field, $this->getOriginalValue($vs_idno_field));
			 }
		} else {
			$this->_generateSortableIdentifierValue();
		}
	
		$vn_rc = parent::update($pa_options);
		$this->errors = array_merge($this->errors, $va_errors);
		
		$this->opo_app_plugin_manager->hookAfterBundleUpdate(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this));
		
		if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Overrides set() to check that the type field is not being set improperly
	 */
	public function set($pa_fields, $pm_value="", $pa_options=null) {
		if (!is_array($pa_fields)) {
			$pa_fields = array($pa_fields => $pm_value);
		}
		
		if ($this->getPrimaryKey() && isset($pa_fields[$this->getTypeFieldName()]) && !defined('__CA_ALLOW_SETTING_OF_PRIMARY_KEYS__')) {
			$this->postError(2520, _t("Type id cannot be set after insert"), "BundlableLabelableBaseModelWithAttributes->set()");
			return false;
		}
		
		if (in_array($this->getProperty('ID_NUMBERING_ID_FIELD'), $pa_fields)) {
			if (!$this->_validateIncomingAdminIDNo(true, true)) { return false; }
		}
		
		return parent::set($pa_fields, "", $pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Overrides get() to support bundleable-level fields (relationships)
	 *
	 * Options:
	 *		All supported by BaseModelWithAttributes::get() plus:
	 *		retrictToRelationshipTypes - array of ca_relationship_types.type_id values to filter related items on. *MUST BE INTEGER TYPE_IDs, NOT type_code's* This limitation is for performance reasons. You have to convert codes to integer type_id's before invoking get
	 */
	public function get($ps_field, $pa_options=null) {
		if(!is_array($pa_options)) { $pa_options = array(); }
		$vs_template = 				(isset($pa_options['template'])) ? $pa_options['template'] : null;
		$vb_return_as_array = 		(isset($pa_options['returnAsArray'])) ? (bool)$pa_options['returnAsArray'] : false;
		$vb_return_all_locales = 	(isset($pa_options['returnAllLocales'])) ? (bool)$pa_options['returnAllLocales'] : false;
		$vs_delimiter = 			(isset($pa_options['delimiter'])) ? $pa_options['delimiter'] : ' ';
		$va_restrict_to_rel_types = (isset($pa_options['restrictToRelationshipTypes']) && is_array($pa_options['restrictToRelationshipTypes'])) ? $pa_options['restrictToRelationshipTypes'] : false;
		if ($vb_return_all_locales && !$vb_return_as_array) { $vb_return_as_array = true; }
		
		// does get refer to an attribute?
		$va_tmp = explode('.', $ps_field);
		
		switch(sizeof($va_tmp)) {
			# -------------------------------------
			case 1:		// table_name
				if ($t_instance = $this->_DATAMODEL->getInstanceByTableName($va_tmp[0], true)) {
					$va_related_items = $this->getRelatedItems($va_tmp[0], $pa_options);
					if (!is_array($va_related_items)) { return null; }
					
					if($vb_return_as_array) {
						 if ($vb_return_all_locales) {
						 	return $va_related_items;
						 } else {
						 	$va_proc_labels = array();
							foreach($va_related_items as $vn_relation_id => $va_relation_info) {
								$va_relation_info['labels'] = caExtractValuesByUserLocale(array(0 => $va_relation_info['labels']));	
								$va_related_items[$vn_relation_id]['labels'] = $va_relation_info['labels'];
							}
							return $va_related_items;
						 }
					} else {
						$va_proc_labels = array();
						foreach($va_related_items as $vn_relation_id => $va_relation_info) {
							$va_proc_labels = array_merge($va_proc_labels, caExtractValuesByUserLocale(array($vn_relation_id => $va_relation_info['labels'])));
							
						}
						
						return join($vs_delimiter, $va_proc_labels);
					}
				}
				break;
			# -------------------------------------
			case 2:		// table_name.field_name
			case 3:		// table_name.field_name.sub_element
			case 4:		// table_name.related.field_name.sub_element
				//
				// TODO: this code is compact, relatively simple and works but is slow since it
				// generates a lot more identical database queries than we'd like
				// We will need to add some notion of caching so that multiple calls to get() 
				// for various fields in the same list of related items don't cause repeated queries
				//
				$vb_is_related = false;
				if ($va_tmp[1] === 'related') {
					array_splice($va_tmp, 1, 1);
					$vb_is_related = true;
				}
				
				if ($vb_is_related || ($va_tmp[0] !== $this->tableName())) {		// must be related table			
					$t_instance = $this->_DATAMODEL->getInstanceByTableName($va_tmp[0], true);
					$va_related_items = $this->getRelatedItems($va_tmp[0], array_merge($pa_options, array('returnLabelsAsArray' => true)));
				
					if (is_array($va_restrict_to_rel_types) && sizeof($va_restrict_to_rel_types)) {
						require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
						$t_rel_types = new ca_relationship_types();
						
						$va_restrict_to_rel_types = $t_rel_types->relationshipTypeListToIDs($t_rel_types->getRelationshipTypeTable($this->tableName(), $va_tmp[0]), $va_restrict_to_rel_types, array('includeChildren' => true));
					}
					
					$va_items = array();
					if(is_array($va_related_items) && (sizeof($va_related_items) > 0)) {
						foreach($va_related_items as $vn_rel_id => $va_related_item) {
							if (is_array($va_restrict_to_rel_types) && !in_array($va_related_item['relationship_type_id'], $va_restrict_to_rel_types)) { continue; }
							
							if ($va_tmp[1] == 'relationship_typename') {
								$va_items[] = $va_related_item['relationship_typename'];
								continue;
							}
							
							if ($va_tmp[1] == 'hierarchy') {
								if ($t_instance->load($va_related_item[$t_instance->primaryKey()])) {
									$va_items[] = $t_instance->get($ps_field, $pa_options);
								}
								//return $t_instance->get($ps_field, $pa_options);
								continue;
							}
							
							// is field directly returned by getRelatedItems()?
							if (isset($va_tmp[1]) && isset($va_related_item[$va_tmp[1]]) && $t_instance->hasField($va_tmp[1])) {
								if ($vb_return_as_array) {
									if ($vb_return_all_locales) {
										// for return as locale-index array
										$va_items[$va_related_item['relation_id']][$va_related_item['locale_id']][] = $va_related_item[$va_tmp[1]];
									} else {
										// for return as simple array
										$va_items[] = $va_related_item[$va_tmp[1]];
									}
								} else {
									// for return as string
									$va_items[] = $va_related_item[$va_tmp[1]];
								}
								continue;
							}
						
							// is field preferred labels?
							if ($va_tmp[1] === 'preferred_labels') {
								if (!isset($va_tmp[2])) {
									if ($vb_return_as_array) {
										if ($vb_return_all_locales) {
											// for return as locale-index array
											$va_items[$va_related_item['relation_id']][] = $va_related_item['labels'];
										} else {
											// for return as simple array
											$va_items = array_merge($va_items, caExtractValuesByUserLocale(array($va_related_item['labels'])));
										}
									} else {
										// for return as string
										$va_items[] = $va_related_item['label'][$t_instance->getLabelDisplayField()];
									}
								} else {
									if ($vb_return_as_array && $vb_return_all_locales) {
										// for return as locale-index array
										foreach($va_related_item['labels'] as $vn_locale_id => $va_label) {
											$va_items[$va_related_item['relation_id']][$vn_locale_id][] = $va_label[$va_tmp[2]];
										}
									} else {
										foreach(caExtractValuesByUserLocale(array($va_related_item['labels'])) as $vn_i => $va_label) {
											// for return as string or simple array
											$va_items[] = $va_label[$va_tmp[2]];
										}
									}
								}
								
								continue;
							}
							
							// TODO: add support for nonpreferred labels
							
							if ($t_instance->load($va_related_item[$t_instance->primaryKey()])) {
								if (isset($va_tmp[1]) && ($vm_val = $t_instance->get($va_tmp[1], $pa_options))) {
									if ($vb_return_as_array) {
										if ($vb_return_all_locales) {
											// for return as locale-index array
											$va_items = $vm_val;
										} else {
											// for return as simple array
											$va_items = array_merge($va_items, $vm_val);
										}
									} else {
										// for return as string
										$va_items[] = $vm_val;
									}	
									continue;
								}
							}
						}
					}
					
					if($vb_return_as_array) {
						return $va_items;
					} else {
						return join($vs_delimiter, $va_items);
					}
				}
				break;
			# -------------------------------------
		}
		
			
		return parent::get($ps_field, $pa_options);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function _validateIncomingAdminIDNo($pb_post_errors=true, $pb_dont_validate_idnos_for_parents_in_mono_hierarchies=false) {
	
		// we should not bother to validate
		$vn_hier_type = $this->getHierarchyType();
		if ($pb_dont_validate_idnos_for_parents_in_mono_hierarchies && in_array($vn_hier_type, array(__CA_HIER_TYPE_SIMPLE_MONO__, __CA_HIER_TYPE_MULTI_MONO__)) && ($this->get('parent_id') == null)) { return true; }
		
		if ($vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD')) {
			$va_idno_errors = $this->validateAdminIDNo($this->get($vs_idno_field));
			if (sizeof($va_idno_errors) > 0) {
				if ($pb_post_errors) {
					foreach($va_idno_errors as $vs_e) {
						$this->postError(1100, $vs_e, "BundlableLabelableBaseModelWithAttributes->insert()");
					}
				}
				return false;
			}
		}
		return true;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function checkForDupeAdminIdnos($ps_idno=null, $pb_dont_remove_self=false) {
		if ($vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD')) {
			$o_db = $this->getDb();
			
			if (!$ps_idno) { $ps_idno = $this->get($vs_idno_field); }
			
			$vs_remove_self_sql = '';
			if (!$pb_dont_remove_self) {
				$vs_remove_self_sql = ' AND ('.$this->primaryKey().' <> '.intval($this->getPrimaryKey()).')';
			}
			
			$vs_idno_context_sql = '';
			if ($vs_idno_context_field = $this->getProperty('ID_NUMBERING_CONTEXT_FIELD')) {
				if ($vn_context_id = $this->get($vs_idno_context_field)) {
					$vs_idno_context_sql = ' AND ('.$vs_idno_context_field.' = '.$this->quote($vs_idno_context_field, $vn_context_id).')';
				} else {
					if ($this->getFieldInfo($vs_idno_context_field, 'IS_NULL')) {
						$vs_idno_context_sql = ' AND ('.$vs_idno_context_field.' IS NULL)';
					}
				}
			}
			
			$qr_idno = $o_db->query("
				SELECT ".$this->primaryKey()." 
				FROM ".$this->tableName()." 
				WHERE {$vs_idno_field} = ? {$vs_remove_self_sql} {$vs_idno_context_sql}
			", $ps_idno);
			
			$va_ids = array();
			while($qr_idno->nextRow()) {
				$va_ids[] = $qr_idno->get($this->primaryKey());
			}
			return $va_ids;
		} 
		
		return array();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function _generateSortableIdentifierValue() {
		if (($vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD')) && ($vs_idno_sort_field = $this->getProperty('ID_NUMBERING_SORT_FIELD'))) {
			$va_tmp = preg_split('![^A-Za-z0-9]+!',  $this->get($vs_idno_field));
			
			$va_output = array();
			$va_zeroless_output = array();
			foreach($va_tmp as $vs_piece) {
				if (preg_match('!^([\d]+)!', $vs_piece, $va_matches)) {
					$vs_piece = $va_matches[1];
				}
				$vn_pad_len = 12 - unicode_strlen($vs_piece);
				
				if ($vn_pad_len >= 0) {
					if (is_numeric($vs_piece)) {
						$va_output[] = str_repeat(' ', $vn_pad_len).$va_matches[1];
					} else {
						$va_output[] = $vs_piece.str_repeat(' ', $vn_pad_len);
					}
				} else {
					$va_output[] = $vs_piece;
				}
				if ($vs_tmp = preg_replace('!^[0]+!', '', $vs_piece)) {
					$va_zeroless_output[] = $vs_tmp;
				} else {
					$va_zeroless_output[] = $vs_piece;
				}
			}
		
			$this->set($vs_idno_sort_field, join('', $va_output).' '.join('.', $va_zeroless_output));
		}
	}
	# ------------------------------------------------------
	/**
	 * Check if a record already exists with the specified label
	 */
	 public function checkForDupeLabel($pn_locale_id, $pa_label_values) {
	 	$o_db = $this->getDb();
	 	$t_label = $this->getLabelTableInstance();
	 	
	 	unset($pa_label_values['displayname']);
	 	$va_sql = array();
	 	foreach($pa_label_values as $vs_field => $vs_value) {
	 		$va_sql[] = "({$vs_field} = ?)";
	 	}
	 	
	 	if ($t_label->hasField('is_preferred')) { $va_sql[] = "(is_preferred = 1)"; }
	 	if ($t_label->hasField('locale_id')) { $va_sql[] = "(locale_id = ?)"; }
	 	$va_sql[] = "(".$this->primaryKey()." <> ?)";
	 	
	 	$vs_sql = "SELECT ".$t_label->primaryKey()." FROM ".$t_label->tableName()." WHERE ".join(' AND ', $va_sql);
	
	 	$va_values = array_values($pa_label_values);
	 	$va_values[] = (int)$pn_locale_id;
	 	$va_values[] = (int)$this->getPrimaryKey();
	 	$qr_res = $o_db->query($vs_sql, $va_values);
	 	
	 	if ($qr_res->numRows() > 0) {
	 		return true;
	 	}
	 
	 	return false;
	 }
	# ------------------------------------------------------
	/**
	 *
	 */
	public function reloadLabelDefinitions() {
		$this->initLabelDefinitions();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	protected function initLabelDefinitions() {
		$this->BUNDLES = array(
			'preferred_labels' 			=> array('type' => 'preferred_label', 'repeating' => true, 'label' => _t("Preferred labels")),
			'nonpreferred_labels' 		=> array('type' => 'nonpreferred_label', 'repeating' => true,  'label' => _t("Non-preferred labels")),
		);
		
		// add form fields to bundle list
		foreach($this->getFormFields() as $vs_f => $va_info) {
			$vs_type_id_fld = isset($this->ATTRIBUTE_TYPE_ID_FLD) ? $this->ATTRIBUTE_TYPE_ID_FLD : null;
			if ($vs_f === $vs_type_id_fld) { continue; } 	// don't allow type_id field to be a bundle (isn't settable in a form)
			if (isset($va_info['DONT_USE_AS_BUNDLE']) && $va_info['DONT_USE_AS_BUNDLE']) { continue; }
			$this->BUNDLES[$vs_f] = array(
				'type' => 'intrinsic', 'repeating' => false, 'label' => $this->getFieldInfo($vs_f, 'LABEL')
			);
		}
		
		$vn_type_id = $this->getTypeID();
		
		// Create instance of idno numbering plugin (if table supports it)
		if ($vs_field = $this->getProperty('ID_NUMBERING_ID_FIELD')) {
			$o_db = $this->getDb();		// have to do direct query here... if we use ca_list_items model we'll just endlessly recurse
			
			$vs_list_idno = '__default__';
			if ($vn_type_id) {
				$qr_res = $o_db->query("SELECT idno FROM ca_list_items WHERE item_id = ?", intval($vn_type_id));
				if ($qr_res->nextRow()) {
					$vs_list_idno = $qr_res->get('idno');
				}
			}
			$this->opo_idno_plugin_instance = IDNumbering::newIDNumberer($this->tableName(), $vs_list_idno, null, $this->getDb());
		} else {
			$this->opo_idno_plugin_instance = null;
		}
		
		// add metadata elements
		foreach($this->getApplicableElementCodes($vn_type_id, false, false) as $vs_code) {
			$this->BUNDLES['ca_attribute_'.$vs_code] = array(
				'type' => 'attribute', 'repeating' => false, 'label' => $vs_code //$this->getAttributeLabel($vs_code)
			);
		}
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getBundleFormHTML($ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_options) {
		global $g_ui_locale;
		$va_info = $this->getBundleInfo($ps_bundle_name);
		if (!$vs_type = $va_info['type']) { return null; }
		
		$vs_label = $vs_label_text = null;
		
		// is label for this bundle forced in bundle settings?
		if (isset($pa_bundle_settings['label']) && isset($pa_bundle_settings['label'][$g_ui_locale]) && ($pa_bundle_settings['label'][$g_ui_locale])) {
			$vs_label = $vs_label_text = $pa_bundle_settings['label'][$g_ui_locale];
		}
		
		$vs_element = '';
		$va_errors = array();
		switch($vs_type) {
			# -------------------------------------------------
			case 'preferred_label':
			case 'nonpreferred_label':
				if (is_array($va_error_objects = $pa_options['request']->getActionErrors($ps_bundle_name)) && sizeof($va_error_objects)) {
					$vs_display_format = $this->getAppConfig()->get('bundle_element_error_display_format');
					foreach($va_error_objects as $o_e) {
						$va_errors[] = $o_e->getErrorDescription();
					}
				} else {
					$vs_display_format = $this->getAppConfig()->get('bundle_element_display_format');
				}
				
				$pa_options['dontCache'] = true;	// we *don't* want to cache labels here
				$vs_element = ($vs_type === 'preferred_label') ? $this->getPreferredLabelHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options) : $this->getNonPreferredLabelHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
			
				if (!$vs_label_text) {  $vs_label_text = $va_info['label']; } 
				$vs_label = '<span style="padding-left:10px" id="'.$pa_options['formName'].'_'.$ps_placement_code.'">'.$vs_label_text.'</span>'; 
				
				$vs_description = isset($pa_bundle_settings['description'][$g_ui_locale]) ? $pa_bundle_settings['description'][$g_ui_locale] : null;
				
				if (($vs_label_text) && ($vs_description)) {
					TooltipManager::add('#'.$pa_options['formName'].'_'.$ps_placement_code, "<h3>{$vs_label}</h3>{$vs_description}");
				}
				break;
			# -------------------------------------------------
			case 'intrinsic':
				if (isset($pa_bundle_settings['label'][$g_ui_locale]) && $pa_bundle_settings['label'][$g_ui_locale]) {
					$pa_options['label'] = $pa_bundle_settings['label'][$g_ui_locale];
				}
				if (!$pa_options['label']) {
					$pa_options['label'] = $this->getFieldInfo($ps_bundle_name, 'LABEL');
				}
				
				$o_view = new View($pa_options['request'], $pa_options['request']->getViewsDirectoryPath().'/bundles/');
				
						
				$va_lookup_url_info = caJSONLookupServiceUrl($pa_options['request'], $this->tableName());
				$o_view->setVar('form_element', $this->htmlFormElement($ps_bundle_name, ($this->getProperty('ID_NUMBERING_ID_FIELD') == $ps_bundle_name) ? $this->getAppConfig()->get('idno_element_display_format_without_label') : $this->getAppConfig()->get('bundle_element_display_format_without_label'), 
					array_merge(
						array(							
							'error_icon' 					=> $pa_options['request']->getThemeUrlPath()."/graphics/icons/warning_small.gif",
							'progress_indicator'		=> $pa_options['request']->getThemeUrlPath()."/graphics/icons/indicator.gif",
							'lookup_url' 					=> $va_lookup_url_info['intrinsic']
						),
						$pa_options
					)
				));
				$o_view->setVar('errors', $pa_options['request']->getActionErrors($ps_bundle_name));
				
				
				$vs_field_id = 'ca_intrinsic_'.$pa_options['formName'].'_'.$ps_placement_code;
				$vs_label = '<span style="padding-left:10px" id="'.$vs_field_id.'">'.$pa_options['label'].'</span>'; 
				$vs_element = $o_view->render('intrinsic.php', true);
				if (($pa_options['label']) && ($vs_description = $this->getFieldInfo($ps_bundle_name, 'DESCRIPTION'))) {
					TooltipManager::add('#'.$vs_field_id, "<h3>".$pa_options['label']."</h3>{$vs_description}");
				}
				
				$vs_display_format = $this->getAppConfig()->get('bundle_element_display_format');
				break;
			# -------------------------------------------------
			case 'attribute':
				// bundle names for attributes are simply element codes prefixed with 'ca_attribute_'
				// since getAttributeHTMLFormBundle() takes a straight element code we have to strip the prefix here
				$vs_attr_element_code = str_replace('ca_attribute_', '', $ps_bundle_name);
				
				//if (is_array($va_error_objects = $pa_options['request']->getActionErrors($ps_bundle_name))) {
				//	$vs_display_format = $this->getAppConfig()->get('form_element_error_display_format');
				//	foreach($va_error_objects as $o_e) {
				//		$va_errors[] = $o_e->getErrorDescription();
				//	}
				//} else {
					$vs_display_format = $this->getAppConfig()->get('bundle_element_display_format');
				//}
				$vs_element = $this->getAttributeHTMLFormBundle($pa_options['request'], $pa_options['formName'], $vs_attr_element_code, $ps_placement_code, $pa_bundle_settings, $pa_options);
				
				$vs_field_id = 'ca_attribute_'.$pa_options['formName'].'_'.$vs_attr_element_code;
				
				if (!$vs_label_text) { $vs_label_text = $this->getAttributeLabel($vs_attr_element_code); }
				$vs_label = '<span style="padding-left:10px" id="'.$vs_field_id.'">'.$vs_label_text.'</span>'; 
				$vs_description = $this->getAttributeDescription($vs_attr_element_code);
				
				if (($vs_label_text) && ($vs_description)) {
					TooltipManager::add('#'.$vs_field_id, "<h3>{$vs_label_text}</h3>{$vs_description}");
				}
		
				break;
			# -------------------------------------------------
			case 'related_table':
				if (is_array($va_error_objects = $pa_options['request']->getActionErrors($ps_bundle_name, 'general')) && sizeof($va_error_objects)) {
					$vs_display_format = $this->getAppConfig()->get('bundle_element_error_display_format');
					foreach($va_error_objects as $o_e) {
						$va_errors[] = $o_e->getErrorDescription();
					}
				} else {
					$vs_display_format = $this->getAppConfig()->get('bundle_element_display_format');
				}
				
				switch($ps_bundle_name) {
					# -------------------------------
					case 'ca_object_representations':
					case 'ca_entities':
					case 'ca_places':
					case 'ca_occurrences':
					case 'ca_objects':
					case 'ca_collections':
					case 'ca_list_items':
					case 'ca_storage_locations':
					case 'ca_loans':
					case 'ca_movements':
						if ($this->_CONFIG->get($ps_bundle_name.'_disable')) { return ''; }		// don't display if master "disable" switch is set
						$vs_element = $this->getRelatedHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $ps_bundle_name, $ps_placement_code, $pa_bundle_settings);	
						break;
					# -------------------------------
					case 'ca_object_lots':
						if ($this->_CONFIG->get($ps_bundle_name.'_disable')) { break; }		// don't display if master "disable" switch is set
						
						$pa_lot_options = array();
						if ($vn_lot_id = $pa_options['request']->getParameter('lot_id', pInteger)) {
							$pa_lot_options['force'][] = $vn_lot_id;
						}
						$vs_element = $this->getRelatedHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_lot_options);	
						break;
					# -------------------------------
					case 'ca_representation_annotations':
						//if (!method_exists($this, "getAnnotationType") || !$this->getAnnotationType()) { continue; }	// don't show bundle if this representation doesn't support annotations
						//if (!method_exists($this, "useBundleBasedAnnotationEditor") || !$this->useBundleBasedAnnotationEditor()) { continue; }	// don't show bundle if this representation doesn't use bundles to edit annotations
						
						$pa_options['fields'] = array('ca_representation_annotations.status', 'ca_representation_annotations.access', 'ca_representation_annotations.props', 'ca_representation_annotations.representation_id');
						
						$vs_element = $this->getRepresentationAnnotationHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_options);	

						break;
					# -------------------------------
					default:
						$vs_element = "'{$ps_bundle_name}' is not a valid related-table bundle name";
						break;
					# -------------------------------
				}
				
				if (!$vs_label_text) { $vs_label_text = $va_info['label']; }				
				$vs_label = '<span style="padding-left:10px" id="'.$pa_options['formName'].'_'.$ps_bundle_name.'">'.$vs_label_text.'</span>'; 
				
				$vs_description = isset($pa_bundle_settings['description'][$g_ui_locale]) ? $pa_bundle_settings['description'][$g_ui_locale] : null;
				
				if (($vs_label_text) && ($vs_description)) {
					TooltipManager::add('#'.$pa_options['formName'].'_'.$ps_bundle_name, "<h3>{$vs_label}</h3>{$vs_description}");
				}
				break;
			# -------------------------------------------------
			case 'special':
				if (is_array($va_error_objects = $pa_options['request']->getActionErrors($ps_bundle_name, 'general')) && sizeof($va_error_objects)) {
					$vs_display_format = $this->getAppConfig()->get('bundle_element_error_display_format');
					foreach($va_error_objects as $o_e) {
						$va_errors[] = $o_e->getErrorDescription();
					}
				} else {
					$vs_display_format = $this->getAppConfig()->get('bundle_element_display_format');
				}
				
				switch($ps_bundle_name) {
					# -------------------------------
					// This bundle is only available when editing objects of type ca_representation_annotations
					case 'ca_representation_annotation_properties':
						foreach($this->getPropertyList() as $vs_property) {
							$vs_element .= $this->getPropertyHTMLFormElement($vs_property);
						}
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_sets
					case 'ca_set_items':
						$vs_element .= $this->getSetItemHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name);
						break;
					# -------------------------------
					// This bundle is only available for types which support set membership
					case 'ca_sets':
						require_once(__CA_MODELS_DIR__."/ca_sets.php");	// need to include here to avoid dependency errors on parse/compile
						$t_set = new ca_sets();
						$vs_element .= $t_set->getItemSetMembershipHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $this->tableNum(), $this->getPrimaryKey(), $pa_options['request']->getUserID());
						break;
					# -------------------------------
					// Hierarchy navigation bar for hierarchical tables
					case 'hierarchy_navigation':
						if ($this->isHierarchical()) {
							$vs_element .= $this->getHierarchyNavigationHTMLFormBundle($pa_options['request'], $pa_options['formName'], array());
						}
						break;
					# -------------------------------
					// Hierarchical item location control
					case 'hierarchy_location':
						if ($this->isHierarchical()) {
							$vs_element .= $this->getHierarchyLocationHTMLFormBundle($pa_options['request'], $pa_options['formName'], array());
						}
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_search_forms
					case 'ca_search_form_placements':
						//if (!$this->getPrimaryKey()) { return ''; }
						$vs_element .= $this->getSearchFormHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_bundle_displays
					case 'ca_bundle_display_placements':
						//if (!$this->getPrimaryKey()) { return ''; }
						$vs_element .= $this->getBundleDisplayHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name);
						break;
					# -------------------------------
					// 
					case 'ca_users':
						if ($pa_options['request']->getUserID() != $this->get('user_id')) { return ''; }	// don't allow setting of per-user access if user is not owner
						$vs_element .= $this->getUserHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $this->tableNum(), $this->getPrimaryKey(), $pa_options['request']->getUserID(), $pa_options);
						break;
					# -------------------------------
					// 
					case 'ca_user_groups':
						if ($pa_options['request']->getUserID() != $this->get('user_id')) { return ''; }	// don't allow setting of group access if user is not owner
						$vs_element .= $this->getUserGroupHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $this->tableNum(), $this->getPrimaryKey(), $pa_options['request']->getUserID(), $pa_options);
						break;
					# -------------------------------
					case 'settings':
						$vs_element .= $this->getHTMLSettingFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name);
						break;
					# -------------------------------
					default:
						$vs_element = "'{$ps_bundle_name}' is not a valid related-table bundle name";
						break;
					# -------------------------------
				}
				
				
				if (!$vs_label_text) { 
					$vs_label_text = $va_info['label']; 
				}
				$vs_label = '<span style="padding-left:10px" id="'.$pa_options['formName'].'_'.$ps_bundle_name.'">'.$vs_label_text.'</span>'; 
				
				$vs_description = isset($pa_bundle_settings['description'][$g_ui_locale]) ? $pa_bundle_settings['description'][$g_ui_locale] : null;
				
				if (($vs_label_text) && ($vs_description)) {
					TooltipManager::add('#'.$pa_options['formName'].'_'.$ps_bundle_name, "<h3>{$vs_label}</h3>{$vs_description}");
				}
				
				break;
			# -------------------------------------------------
			default:
				return "'{$ps_bundle_name}' is not a valid bundle name";
				break;
			# -------------------------------------------------
		}
		
		$vs_output = str_replace("^ELEMENT", $vs_element, $vs_display_format);
		$vs_output = str_replace("^ERRORS", join('; ', $va_errors), $vs_output);
		$vs_output = str_replace("^LABEL", $vs_label, $vs_output);
		
		return $vs_output;
	}
	# ------------------------------------------------------
	public function getBundleList($pa_options=null) {
		if (isset($pa_options['includeBundleInfo']) && $pa_options['includeBundleInfo']) { 
			return $this->BUNDLES;
		}
		return array_keys($this->BUNDLES);
	}
	# ------------------------------------------------------
	public function isValidBundle($ps_bundle_name) {
		return (isset($this->BUNDLES[$ps_bundle_name]) && is_array($this->BUNDLES[$ps_bundle_name])) ? true : false;
	}
	# ------------------------------------------------------
 	/** 
 	  * Returns associative array with descriptive information about the bundle
 	  */
 	public function getBundleInfo($ps_bundle_name) {
 		return isset($this->BUNDLES[$ps_bundle_name]) ? $this->BUNDLES[$ps_bundle_name] : null;
 	}
 	# --------------------------------------------------------------------------------------------
	/**
	  * Returns display label for element specified by standard "get" bundle code (eg. <table_name>.<bundle_name> format)
	  */
	public function getDisplayLabel($ps_field) {
		$va_tmp = explode('.', $ps_field);

		switch(sizeof($va_tmp)) {
			# -------------------------------------
			case 1:		// table_name
				if ($t_instance = $this->_DATAMODEL->getInstanceByTableName($va_tmp[0], true)) {
					return _t("Related %1", $t_instance->getProperty('NAME_PLURAL'));
				}
				break;
			# -------------------------------------
			case 2:		// table_name.field_name
			case 3:		// table_name.field_name.sub_element	
				if (!($t_instance = $this->_DATAMODEL->getInstanceByTableName($va_tmp[0], true))) { return null; }
				$vs_prefix = $vs_suffix = '';
				$vs_suffix_string = ' ('._t('from related %1', $t_instance->getProperty('NAME_PLURAL')).')';
				if ($va_tmp[0] !== $this->tableName()) {
					$vs_suffix = $vs_suffix_string;
				}
				switch($va_tmp[1]) {
					# --------------------
					case 'related':
						unset($va_tmp[1]);
						$vs_label = $this->getDisplayLabel(join('.', $va_tmp));
						if ($va_tmp[0] != $this->tableName()) {
							return $vs_label.$vs_suffix_string;
						} 
						return $vs_label;
						break;
					# --------------------
					case 'preferred_labels':		
						if (method_exists($t_instance, 'getLabelTableInstance') && ($t_label_instance = $t_instance->getLabelTableInstance())) {
							if (!isset($va_tmp[2])) {
								return unicode_ucfirst($t_label_instance->getProperty('NAME_PLURAL')).$vs_suffix;
							} else {
								return unicode_ucfirst($t_label_instance->getDisplayLabel($t_label_instance->tableName().'.'.$va_tmp[2])).$vs_suffix;
							}
						}
						break;
					# --------------------
					case 'nonpreferred_labels':
						if (method_exists($t_instance, 'getLabelTableInstance') && ($t_label_instance = $t_instance->getLabelTableInstance())) {
							if ($va_tmp[0] !== $this->tableName()) {
								$vs_suffix = ' ('._t('alternates from related %1', $t_instance->getProperty('NAME_PLURAL')).')';
							} else {
								$vs_suffix = ' ('._t('alternates').')';
							}
							if (!isset($va_tmp[2])) {
								return unicode_ucfirst($t_label_instance->getProperty('NAME_PLURAL')).$vs_suffix;
							} else {
								return unicode_ucfirst($t_label_instance->getDisplayLabel($t_label_instance->tableName().'.'.$va_tmp[2])).$vs_suffix;
							}
						}
						break;
					# --------------------
					case 'media':		
						if ($va_tmp[0] === 'ca_object_representations') {
							if ($va_tmp[2]) {
								return _t('Object media representation (%1)', $va_tmp[2]);
							} else {
								return _t('Object media representation (default)');
							}
						}
						break;
					# --------------------
					default:
						if ($va_tmp[0] !== $this->tableName()) {
							return unicode_ucfirst($t_instance->getDisplayLabel($ps_field)).$vs_suffix;
						}
						break;
					# --------------------
				}	
					
				break;
			# -------------------------------------
		}
		
		// maybe it's a special bundle name?
		if (($va_tmp[0] === $this->tableName()) && isset($this->BUNDLES[$va_tmp[1]]) && $this->BUNDLES[$va_tmp[1]]['label']) {
			return $this->BUNDLES[$va_tmp[1]]['label'];
		}
		
		return parent::getDisplayLabel($ps_field);
	}
	# --------------------------------------------------------------------------------------------
	/**
	  * Returns display description for element specified by standard "get" bundle code (eg. <table_name>.<bundle_name> format)
	  */
	public function getDisplayDescription($ps_field) {
		$va_tmp = explode('.', $ps_field);

		switch(sizeof($va_tmp)) {
			# -------------------------------------
			case 1:		// table_name
				if ($t_instance = $this->_DATAMODEL->getInstanceByTableName($va_tmp[0], true)) {
					return _t("A list of related %1", $t_instance->getProperty('NAME_PLURAL'));
				}
				break;
			# -------------------------------------
			case 2:		// table_name.field_name
			case 3:		// table_name.field_name.sub_element	
				if (!($t_instance = $this->_DATAMODEL->getInstanceByTableName($va_tmp[0], true))) { return null; }
				
				$vs_suffix = '';
				if ($va_tmp[0] !== $this->tableName()) {
					$vs_suffix = ' '._t('from related %1', $t_instance->getProperty('NAME_PLURAL'));
				}
				switch($va_tmp[1]) {
					# --------------------
					case 'related':
						unset($va_tmp[1]);
						return _t('A list of related %1', $t_instance->getProperty('NAME_PLURAL'));
						break;
					# --------------------
					case 'preferred_labels':								
						if (method_exists($t_instance, 'getLabelTableInstance') && ($t_label_instance = $t_instance->getLabelTableInstance())) {
							if (!isset($va_tmp[2])) {
								return _t('A list of %1 %2', $t_label_instance->getProperty('NAME_PLURAL'), $vs_suffix);
							} else {
								return _t('A list of %1 %2', $t_label_instance->getDisplayLabel($t_label_instance->tableName().'.'.$va_tmp[2]), $vs_suffix);
							}
						}
						break;
					# --------------------
					case 'nonpreferred_labels':						
						if (method_exists($t_instance, 'getLabelTableInstance') && ($t_label_instance = $t_instance->getLabelTableInstance())) {
							if (!isset($va_tmp[2])) {
								return _t('A list of alternate %1 %2', $t_label_instance->getProperty('NAME_PLURAL'), $vs_suffix);
							} else {
								return _t('A list of alternate %1 %2', $t_label_instance->getDisplayLabel($t_label_instance->tableName().'.'.$va_tmp[2]), $vs_suffix);
							}
						}
						break;
					# --------------------
					case 'media':		
						if ($va_tmp[0] === 'ca_object_representations') {
							if ($va_tmp[2]) {
								return _t('A list of related media representations using version "%1"', $va_tmp[2]);
							} else {
								return _t('A list of related media representations using the default version');
							}
						}
						break;
					# --------------------
					default:
						if ($va_tmp[0] !== $this->tableName()) {
							return _t('A list of %1 %2', $t_instance->getDisplayLabel($ps_field), $vs_suffix);
						}
						break;
					# --------------------
				}	
					
				break;
			# -------------------------------------
		}
		
		return parent::getDisplayDescription($ps_field);
	}
	# --------------------------------------------------------------------------------------------
	/**
	  * Returns HTML search form input widget for bundle specified by standard "get" bundle code (eg. <table_name>.<bundle_name> format)
	  * This method handles generation of search form widgets for (1) related tables (eg. ca_places),  preferred and non-preferred labels for both the 
	  * primary and related tables, and all other types of elements for related tables. If this method can't handle the bundle it will pass the request to the 
	  * superclass implementation of htmlFormElementForSearch()
	  *
	  * @param $po_request HTTPRequest
	  * @param $ps_field string
	  * @param $pa_options array
	  * @return string HTML text of form element. Will return null (from superclass) if it is not possible to generate an HTML form widget for the bundle.
	  * 
	  */
	public function htmlFormElementForSearch($po_request, $ps_field, $pa_options=null) {
		$va_tmp = explode('.', $ps_field);
		
		switch(sizeof($va_tmp)) {
			# -------------------------------------
			case 1:		// table_name
				if ($va_tmp[0] != $this->tableName()) {
					if (!is_array($pa_options)) { $pa_options = array(); }
					if (!isset($pa_options['width'])) { $pa_options['width'] = 30; }
					if (!isset($pa_options['values'])) { $pa_options['values'] = array(); }
					if (!isset($pa_options['values'][$ps_field])) { $pa_options['values'][$ps_field] = ''; }
				
					return caHTMLTextInput($ps_field, array('value' => $pa_options['values'][$ps_field], 'size' => $pa_options['width'], 'id' => str_replace('.', '_', $ps_field)));
				}
				break;
			# -------------------------------------
			case 2:		// table_name.field_name
			case 3:		// table_name.field_name.sub_element	
				if (!($t_instance = $this->_DATAMODEL->getInstanceByTableName($va_tmp[0], true))) { return null; }
				
				switch($va_tmp[1]) {
					# --------------------
					case 'preferred_labels':		
					case 'nonpreferred_labels':
						return caHTMLTextInput($ps_field, array('value' => $pa_options['values'][$ps_field], 'size' => $pa_options['width'], 'id' => str_replace('.', '_', $ps_field)));
						break;
					# --------------------
					default:
						if ($va_tmp[0] != $this->tableName()) {
							return caHTMLTextInput($ps_field, array('value' => $pa_options['values'][$ps_field], 'size' => $pa_options['width'], 'id' => str_replace('.', '_', $ps_field)));
						}
						break;
					# --------------------
				}	
					
				break;
			# -------------------------------------
		}
		
		return parent::htmlFormElementForSearch($po_request, $ps_field, $pa_options);
	}
 	# ------------------------------------------------------
 	// returns a list of HTML fragments implementing all bundles in an HTML form for the specified screen
 	// $pm_screen can be a screen tag (eg. "Screen5") or a screen_id (eg. 5)
 	public function getBundleFormHTMLForScreen($pm_screen, $pa_options) {
 		if (!$pa_options['request']->isLoggedIn()) { return false; }
 		$t_ui = new ca_editor_uis();
 		$t_ui->loadDefaultUI($this->tableName(), $pa_options['request']);
 		
 		$va_bundles = $t_ui->getScreenBundlePlacements($pm_screen);
 
 		$va_bundle_html = array();
 		
 		$vn_pk_id = $this->getPrimaryKey();
		
		if (is_array($va_bundles)) {
			$vs_type_id_fld = isset($this->ATTRIBUTE_TYPE_ID_FLD) ? $this->ATTRIBUTE_TYPE_ID_FLD : null;
			$vs_hier_parent_id_fld = isset($this->HIERARCHY_PARENT_ID_FLD) ? $this->HIERARCHY_PARENT_ID_FLD : null;
			foreach($va_bundles as $va_bundle) {
				if ($va_bundle['bundle_name'] === $vs_type_id_fld) { continue; }	// skip type_id
				if ((!$vn_pk_id) && ($va_bundle['bundle_name'] === $vs_hier_parent_id_fld)) { continue; }
				
				// Test for user action restrictions on intrinsic fields
				$vb_output_bundle = true;
				if ($this->hasField($va_bundle['bundle_name'])) {
					if (is_array($va_requires = $this->getFieldInfo($va_bundle['bundle_name'], 'REQUIRES'))) {
						foreach($va_requires as $vs_required_action) {
							if (!$pa_options['request']->user->canDoAction($vs_reqired_action)) { 
								$vb_output_bundle = false;
								break;
							}
						}
					}
				}
				if (!$vb_output_bundle) { continue; }
				$va_bundle_html[$va_bundle['placement_code']] = $this->getBundleFormHTML($va_bundle['bundle_name'], $va_bundle['placement_code'], $va_bundle['settings'], $pa_options);
			}
		}
		
		// is this a form to create a new item?
		if (!$vn_pk_id) {
			// auto-add mandatory fields if this is a new object
			$va_mandatory_fields = $this->getMandatoryFields();
			foreach($va_mandatory_fields as $vs_field) {
				if (!isset($va_bundle_html[$vs_field]) || !$va_bundle_html[$vs_field]) {
					$va_bundle_html[$vs_field] = $this->getBundleFormHTML($vs_field, 'mandatory_'.$vs_field, array(), $pa_options);
				}
			}
			
			// add type_id
			if (isset($this->ATTRIBUTE_TYPE_ID_FLD) && $this->ATTRIBUTE_TYPE_ID_FLD) {
				$va_bundle_html[$this->ATTRIBUTE_TYPE_ID_FLD] = caHTMLHiddenInput($this->ATTRIBUTE_TYPE_ID_FLD, array('value' => $pa_options['request']->getParameter($this->ATTRIBUTE_TYPE_ID_FLD, pInteger)));
			}
			
			// add parent_id
			if (isset($this->HIERARCHY_PARENT_ID_FLD) && $this->HIERARCHY_PARENT_ID_FLD) {
				$va_bundle_html[$this->HIERARCHY_PARENT_ID_FLD] = caHTMLHiddenInput($this->HIERARCHY_PARENT_ID_FLD, array('value' => $pa_options['request']->getParameter($this->HIERARCHY_PARENT_ID_FLD, pInteger)));
			}
		}
		
 		return $va_bundle_html;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
	public function getHierarchyNavigationHTMLFormBundle($po_request, $ps_form_name, $pa_options=null) {
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if (!($vs_label_table_name = $this->getLabelTableName())) { return ''; }
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('t_subject', $this);
		if (!($vn_id = $this->getPrimaryKey())) {
			$vn_id = $po_request->getParameter($this->HIERARCHY_PARENT_ID_FLD, pString);
		} 
		
		$vs_display_fld = $this->getLabelDisplayField();
		if (!($va_ancestor_list = $this->getHierarchyAncestors($vn_id, array(
			'additionalTableToJoin' => $vs_label_table_name, 
			'additionalTableJoinType' => 'LEFT',
			'additionalTableSelectFields' => array($vs_display_fld, 'locale_id'),
			'additionalTableWheres' => array('('.$vs_label_table_name.'.is_preferred = 1 OR '.$vs_label_table_name.'.is_preferred IS NULL)'),
			'includeSelf' => true
		)))) {
			$va_ancestor_list = array();
		}
		
		$va_ancestors_by_locale = array();
		$vs_pk = $this->primaryKey();
		
		$vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD');
		foreach($va_ancestor_list as $vn_ancestor_id => $va_info) {
			if (!$va_info['NODE']['parent_id']) { continue; }
			if (!($va_info['NODE']['name'] =  $va_info['NODE'][$vs_display_fld])) {		// copy display field content into 'name' which is used by bundle for display
				if (!($va_info['NODE']['name'] = $va_info['NODE'][$vs_idno_field])) { $va_info['NODE']['name'] = '???'; }
			}
			$va_ancestors_by_locale[$va_info['NODE'][$vs_pk]][$va_info['NODE']['locale_id']] = $va_info['NODE'];
		}
		$va_ancestor_list = array_reverse(caExtractValuesByUserLocale($va_ancestors_by_locale));
		
		// push hierarchy name onto front of list
		if ($vs_hier_name = $this->getHierarchyName($vn_id)) {
			array_unshift($va_ancestor_list, array(
				'name' => $vs_hier_name
			));
		}
		
		if (!$this->getPrimaryKey()) {
			$va_ancestor_list[null] = array(
				$this->primaryKey() => '',
				$this->getLabelDisplayField() => _t('New %1', $this->getProperty('NAME_SINGULAR'))
			);
		}
		
		if (method_exists($this, "getTypeList")) {
			$o_view->setVar('type_list', $this->getTypeList());
		}
		
		$o_view->setVar('ancestors', $va_ancestor_list);
		$o_view->setVar('id', $this->getPrimaryKey());
		
		return $o_view->render('hierarchy_navigation.php');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getHierarchyLocationHTMLFormBundle($po_request, $ps_form_name, $pa_options=null) {
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		if (!($vs_label_table_name = $this->getLabelTableName())) { return ''; }
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('t_subject', $this);
		if (!($vn_id = $this->getPrimaryKey())) {
			$vn_parent_id = $vn_id = $po_request->getParameter($this->HIERARCHY_PARENT_ID_FLD, pString);
		} else {
			$vn_parent_id = $this->get($this->HIERARCHY_PARENT_ID_FLD);
		}
		$vs_display_fld = $this->getLabelDisplayField();
		
		if (!($va_ancestor_list = $this->getHierarchyAncestors($vn_id, array(
			'additionalTableToJoin' => $vs_label_table_name, 
			'additionalTableJoinType' => 'LEFT',
			'additionalTableSelectFields' => array($vs_display_fld, 'locale_id'),
			'additionalTableWheres' => array('('.$vs_label_table_name.'.is_preferred = 1 OR '.$vs_label_table_name.'.is_preferred IS NULL)'),
			'includeSelf' => true
		)))) {
			$va_ancestor_list = array();
		}
		
		
		$va_ancestors_by_locale = array();
		$vs_pk = $this->primaryKey();
		
		
		$vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD');
		foreach($va_ancestor_list as $vn_ancestor_id => $va_info) {
			if (!$va_info['NODE']['parent_id']) { continue; }
			if (!($va_info['NODE']['name'] =  $va_info['NODE'][$vs_display_fld])) {		// copy display field content into 'name' which is used by bundle for display
				if (!($va_info['NODE']['name'] = $va_info['NODE'][$vs_idno_field])) { $va_info['NODE']['name'] = '???'; }
			}
			$vn_locale_id = isset($va_info['NODE']['locale_id']) ? $va_info['NODE']['locale_id'] : null;
			$va_ancestors_by_locale[$va_info['NODE'][$vs_pk]][$vn_locale_id] = $va_info['NODE'];
		}
		
		$va_ancestor_list = array_reverse(caExtractValuesByUserLocale($va_ancestors_by_locale));
		
		// push hierarchy name onto front of list
		if ($vs_hier_name = $this->getHierarchyName($vn_id)) {
			array_unshift($va_ancestor_list, array(
				'name' => $vs_hier_name
			));
		}
		if (!$this->getPrimaryKey()) {
			$va_ancestor_list[null] = array(
				$this->primaryKey() => '',
				$this->getLabelDisplayField() => _t('New %1', $this->getProperty('NAME_SINGULAR'))
			);
		}
		
		$o_view->setVar('parent_id', $vn_parent_id);
		$o_view->setVar('ancestors', $va_ancestor_list);
		$o_view->setVar('id', $this->getPrimaryKey());
		
		return $o_view->render('hierarchy_location.php');
	}
 	# ------------------------------------------------------
	public function getRelatedHTMLFormBundle($po_request, $ps_form_name, $ps_related_table, $ps_placement_code=null, $pa_bundle_settings=null, $pa_options=null) {
		global $g_ui_locale;
		
		if(!is_array($pa_bundle_settings)) { $pa_bundle_settings = array(); }
		if(!is_array($pa_options)) { $pa_options = array(); }
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		$t_item = $this->getAppDatamodel()->getTableInstance($ps_related_table);
		
		switch(sizeof($va_path = array_keys($this->getAppDatamodel()->getPath($this->tableName(), $ps_related_table)))) {
			case 3:
				// many-many relationship
				$t_item_rel = $this->getAppDatamodel()->getTableInstance($va_path[1]);
				break;
			case 2:
				// many-one relationship
				$t_item_rel = $this->getAppDatamodel()->getTableInstance($va_path[1]);
				break;
			default:
				$t_item_rel = null;
				break;
		}
	
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('t_item', $t_item);
		$o_view->setVar('t_item_rel', $t_item_rel);
		
		// pass bundle settings
		$o_view->setVar('settings', $pa_bundle_settings);
		
		// pass placement code
		$o_view->setVar('placement_code', $ps_placement_code);
		
		$o_view->setVar('add_label', isset($pa_bundle_settings['add_label'][$g_ui_locale]) ? $pa_bundle_settings['add_label'][$g_ui_locale] : null);
		
		$t_label = null;
		if ($t_item->getLabelTableName()) {
			$t_label = $this->_DATAMODEL->getInstanceByTableName($t_item->getLabelTableName(), true);
		}
		
		if (method_exists($t_item_rel, 'getRelationshipTypes')) {
			$o_view->setVar('relationship_types', $t_item_rel->getRelationshipTypes(null, null,  array_merge($pa_options, $pa_bundle_settings)));
			$o_view->setVar('relationship_types_by_sub_type', $t_item_rel->getRelationshipTypesBySubtype($this->tableName(), $this->get('type_id'),  array_merge($pa_options, $pa_bundle_settings)));
		}
		$o_view->setVar('t_subject', $this);
		
		
		$vs_display_format = $po_request->config->get($t_item->tableName().'_lookup_settings');					
		if ($vs_display_format && is_string($vs_display_format) && !preg_match_all('!\^{1}([A-Za-z0-9\._]+)!', $vs_display_format, $va_matches)) {
			$vs_display_format = '^'.$t_item->tableName().'.preferred_labels';
			$va_bundles = array($t_item->tableName().'.preferred_labels');
		} else {
			$va_bundles = $va_matches[1];
		}
		if (!is_array($va_bundles)) { $va_bundles = array(); }
			
		$va_initial_values = array();
		$vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD');
		if (sizeof($va_items = $this->getRelatedItems($ps_related_table, array_merge($pa_options, $pa_bundle_settings)))) {
			$t_rel 								= $this->getAppDatamodel()->getInstanceByTableName($ps_related_table, true);
			$vb_is_hierarchical 			= $t_rel->isHierarchical();
			$vs_hier_parent_id_fld 		= $t_rel->getProperty('HIERARCHY_PARENT_ID_FLD');
			$vs_hier_fld 						= $t_rel->getProperty('HIERARCHY_ID_FLD');
			$vs_rel_pk 						= $t_rel->primaryKey();
			$vs_rel_table						= $t_rel->tableName();
			
			$va_ids = caExtractArrayValuesFromArrayOfArrays($va_items, $vs_rel_pk);
			$qr_rel_items = $this->makeSearchResult($t_rel->tableNum(), $va_ids);		
			
			$va_related_item_info = $va_parent_ids = $va_hierarchy_ids = array();
			while($qr_rel_items->nextHit()) {
				$vn_id = $qr_rel_items->get("{$vs_rel_table}.{$vs_rel_pk}");
				
				$vs_display_value = $vs_display_format;
				
				foreach($va_bundles as $vs_bundle_name) {
					if (in_array($vs_bundle_name, array('_parent', '_hierarchy'))) { continue;}
					$vs_value = $qr_rel_items->get($vs_bundle_name);
					if ($vs_display_format) {
						$vs_display_value = str_replace("^{$vs_bundle_name}", $vs_value, $vs_display_value);
					} else {
						$vs_display_value .= $vs_value.' ';
					}
				}
				
				if ($vb_is_hierarchical) {
					if ($vn_parent_id = $qr_rel_items->get("{$vs_rel_table}.{$vs_hier_parent_id_fld}")) {
						$va_parent_ids[$vn_id] = $vn_parent_id;
					}
					
					if ($vs_hier_fld) {
						$va_hierarchy_ids[$vn_id] = $qr_rel_items->get("{$vs_rel_table}.{$vs_hier_fld}");
					}
				}
				
				$va_related_item_info[$vn_id] = $vs_display_value;
			}
			
			$va_parent_labels = $t_rel->getPreferredDisplayLabelsForIDs($va_parent_ids, $va_hierarchy_ids);
			$va_hierarchies = (method_exists($t_rel, "getHierarchyList")) ? $t_rel->getHierarchyList() : array();
					
			
			foreach ($va_items as $va_item) {
				$vn_id = $va_item[$vs_rel_pk];
				
				$va_related_item_info[$vn_id] = str_replace('^_parent',  $va_parent_labels[$va_parent_ids[$vn_id]], $va_related_item_info[$vn_id]);
				$va_related_item_info[$vn_id] = str_replace('^_hierarchy',  $va_hierarchies[$va_hierarchy_ids[$vn_id]]['name_plural'], $va_related_item_info[$vn_id]);
				
				
				$va_initial_values[$va_item['relation_id'] ? $va_item['relation_id'] : $va_item[$vs_rel_pk]] = array_merge(
					$va_item, 
					array(
						'id' => $va_item[$vs_rel_pk], 
						'idno' => isset($va_item['idno']) ? $va_item['idno'] : null, 
						'idno_stub' => isset($va_item['idno_stub']) ? $va_item['idno_stub'] : null, 
						'relationship_type_id' => $va_item['relationship_type_id'],
						'_display' => trim(strip_tags($va_related_item_info[$vn_id]))
					)
				);
			}
		}
		
		//t_item
		$va_force_new_values = array();
		if (isset($pa_options['force']) && is_array($pa_options['force'])) {
			foreach($pa_options['force'] as $vn_id) {
				if ($t_item->load($vn_id)) {
					$va_item = $t_item->getFieldValuesArray();
					if ($t_label) {
						$va_item[$t_label->getDisplayField()] =  $t_item->getLabelForDisplay();
					}
					$va_force_new_values[$vn_id] = array_merge(
						$va_item, 
						array(
							'id' => $vn_id, 
							'idno' => ($vn_idno = $t_item->get('idno')) ? $vn_idno : null, 
							'idno_stub' => ($vn_idno_stub = $t_item->get('idno_stub')) ? $vn_idno_stub : null, 
							'relationship_type_id' => null
						)
					);
				}
			}
		}
		
		$o_view->setVar('initialValues', $va_initial_values);
		$o_view->setVar('forceNewValues', $va_force_new_values);
		
		return $o_view->render($ps_related_table.'.php');
	}
	# ------------------------------------------------------
	// saves all bundles on the specified screen in the database by extracting 
	// required data from the supplied request
	// $pm_screen can be a screen tag (eg. "Screen5") or a screen_id (eg. 5)
	public function saveBundlesForScreen($pm_screen, $po_request) {
		$vb_we_set_transaction = false;
		
		if (!$this->inTransaction()) {
			$this->setTransaction(new Transaction($this->getDb()));
			$vb_we_set_transaction = true;
		}
		
		BaseModel::setChangeLogUnitID();
		// get items on screen
		$t_ui = new ca_editor_uis();
		$t_ui->loadDefaultUI($this->tableName(), $po_request);
		$va_bundles = $t_ui->getScreenBundlePlacements($pm_screen);
			
		// sort fields by type
		$va_fields_by_type = array();
		if (is_array($va_bundles)) {
			foreach($va_bundles as $vs_bundle_name => $va_tmp) {
				$va_info = $this->getBundleInfo($va_tmp['bundle_name']);
				$va_fields_by_type[$va_info['type']][$va_tmp['placement_code']] = $va_tmp['bundle_name'];
			}
		}
		
		$vs_form_prefix = $po_request->getParameter('_formName', pString);
		
		// auto-add mandatory fields if this is a new object
		if (!is_array($va_fields_by_type['intrinsic'])) { $va_fields_by_type['intrinsic'] = array(); }
		if (!$this->getPrimaryKey()) {
			if (is_array($va_mandatory_fields = $this->getMandatoryFields())) {
				foreach($va_mandatory_fields as $vs_field) {
					if (!in_array($vs_field, $va_fields_by_type['intrinsic'])) {
						$va_fields_by_type['intrinsic'][] = $vs_field;
					}
				}
			}
			
			// add parent_id
			if ($this->HIERARCHY_PARENT_ID_FLD) {
				$va_fields_by_type['intrinsic'][] = $this->HIERARCHY_PARENT_ID_FLD;
			}
		}
		
		// save intrinsic fields
		if (is_array($va_fields_by_type['intrinsic'])) {
			$vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD');
			foreach($va_fields_by_type['intrinsic'] as $vs_f) {
				if (isset($_FILES[$vs_f]) && $_FILES[$vs_f]) {
					// media field
					$this->set($vs_f, $_FILES[$vs_f]['tmp_name']);
				} else {
					switch($vs_f) {
						case $vs_idno_field:
							if ($this->opo_idno_plugin_instance) {
								$this->opo_idno_plugin_instance->setDb($this->getDb());
								$this->set($vs_f, $vs_tmp = $this->opo_idno_plugin_instance->htmlFormValue($vs_idno_field));
							} else {
								$this->set($vs_f, $po_request->getParameter($vs_f, pString));
							}
							break;
						default:
							$this->set($vs_f, $po_request->getParameter($vs_f, pString));
							break;
					}
				}
				if ($this->numErrors() > 0) {
					foreach($this->errors() as $o_e) {
						switch($o_e->getErrorNumber()) {
							case 795:
								// field conflicts
								foreach($this->getFieldConflicts() as $vs_conflict_field) {
									$po_request->addActionError($o_e, $vs_conflict_field);
								}
								break;
							default:
								$po_request->addActionError($o_e, $vs_f);
								break;
						}
					}
				}
			}
		}
		
		// save attributes
		if (isset($va_fields_by_type['attribute']) && is_array($va_fields_by_type['attribute'])) {
			//
			// name of attribute request parameters are:
			// 	For new attributes
			// 		{$vs_form_prefix}_attribute_{element_set_id}_{element_id|'locale_id'}_new_{n}
			//		ex. ObjectBasicForm_attribute_6_locale_id_new_0 or ObjectBasicForm_attribute_6_desc_type_new_0
			//
			// 	For existing attributes:
			// 		{$vs_form_prefix}_attribute_{element_set_id}_{element_id|'locale_id'}_{attribute_id}
			//
			
			// look for newly created attributes; look for attributes to delete
			$va_inserted_attributes = array();
			$reserved_elements = array();
			foreach($va_fields_by_type['attribute'] as $vs_placement_code => $vs_f) {
				$vs_element_set_code = preg_replace("/^ca_attribute_/", "", $vs_f);
				//does the attribute's datatype have a saveElement method - if so, use that instead
				$vs_element = $this->_getElementInstance($vs_element_set_code);
				$vn_element_id = $vs_element->getPrimaryKey();
				$vs_element_datatype = $vs_element->get('datatype');
				$vs_datatype = Attribute::getValueInstance($vs_element_datatype);
				if(method_exists($vs_datatype,'saveElement')) {
					$reserved_elements[] = $vs_element;
					continue;
				}
				
				$va_attributes_to_insert = array();
				$va_attributes_to_delete = array();
				$va_locales = array();
				foreach($_REQUEST as $vs_key => $vs_val) {
					// is it a newly created attribute?
					if (preg_match('/'.$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_([\w\d\-_]+)_new_([\d]+)/', $vs_key, $va_matches)) { 
						$vn_c = intval($va_matches[2]);
						// yep - grab the locale and value
						$vn_locale_id = isset($_REQUEST[$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_locale_id_new_'.$vn_c]) ? $_REQUEST[$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_locale_id_new_'.$vn_c] : null;
						
						if(strlen(trim($vs_val))>0) {
							$va_attributes_to_insert[$vn_c]['locale_id'] = $vn_locale_id; 
							$va_attributes_to_insert[$vn_c][$va_matches[1]] = $vs_val;
						}
					} else {
						// is it a delete key?
						if (preg_match('/'.$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_([\d]+)_delete/', $vs_key, $va_matches)) {
							$vn_attribute_id = intval($va_matches[1]);
							$va_attributes_to_delete[$vn_attribute_id] = true;
						}
					}
				}
				
				// look for uploaded files as attributes
				foreach($_FILES as $vs_key => $va_val) {
					if (preg_match('/'.$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_locale_id_new_([\d]+)/', $vs_key, $va_locale_matches)) { 
						$vn_locale_c = intval($va_locale_matches[1]);
						$va_locales[$vn_locale_c] = $vs_val;
						continue; 
					}
					// is it a newly created attribute?
					if (preg_match('/'.$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_([\w\d\-_]+)_new_([\d]+)/', $vs_key, $va_matches)) { 
						if (!$va_val['size']) { continue; }	// skip empty files
						
						// yep - grab the value
						$vn_c = intval($va_matches[2]);
						$va_attributes_to_insert[$vn_c]['locale_id'] = $va_locales[$vn_c]; 
						$va_val['_uploaded_file'] = true;
						$va_attributes_to_insert[$vn_c][$va_matches[1]] = $va_val;
					}
				}
				
				
				// do deletes
				$this->clearErrors();
				foreach($va_attributes_to_delete as $vn_attribute_id => $vb_tmp) {
					$this->removeAttribute($vn_attribute_id, $vs_f, array('pending_adds' => $va_attributes_to_insert));
				}
				
				// do inserts
				foreach($va_attributes_to_insert as $va_attribute_to_insert) {
					$this->clearErrors();
					$this->addAttribute($va_attribute_to_insert, $vn_element_id, $vs_f);
				}
			
				// check for attributes to update
				if (is_array($va_attrs = $this->getAttributesByElement($vn_element_id))) {
					$t_element = new ca_metadata_elements();
							
					$va_attrs_update_list = array();
					foreach($va_attrs as $o_attr) {
						$this->clearErrors();
						$vn_attribute_id = $o_attr->getAttributeID();
						if (in_array($vn_attribute_id, $va_inserted_attributes)) { continue; }
						if (in_array($vn_attribute_id, $va_attributes_to_delete)) { continue; }
						
						$vn_element_set_id = $o_attr->getElementID();
						
						$va_attr_update = array(
							'locale_id' =>  $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_set_id.'_locale_id_'.$vn_attribute_id, pString) 
						);
						
						//
						// Check to see if there are any values in the element set that are not in the  attribute we're editing
						// If additional sub-elements were added to the set after the attribute we're updating was created
						// those sub-elements will not have corresponding values returned by $o_attr->getValues() above.
						// Because we use the element_ids in those values to pull request parameters, if an element_id is missing
						// it effectively becomes invisible and cannot be set. This is a fairly unusual case but it happens, and when it does
						// it's really annoying. It would be nice and efficient to simply create the missing values at configuration time, but we wouldn't
						// know what to set the values to. So what we do is, after setting all of the values present in the attribute from the request, grab
						// the configuration for the element set and see if there are any elements in the set that we didn't get values for.
						//
						$va_sub_elements = $t_element->getElementsInSet($vn_element_set_id);
						//foreach($o_attr->getValues() as $o_attr_val) {
						foreach($va_sub_elements as $vn_i => $va_element_info) {
							if ($va_element_info['datatype'] == 0) { continue; }
							//$vn_element_id = $o_attr_val->getElementID();
							$vn_element_id = $va_element_info['element_id'];
							
							$vs_k = $vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_set_id.'_'.$vn_element_id.'_'.$vn_attribute_id;
							if (isset($_FILES[$vs_k]) && ($va_val = $_FILES[$vs_k])) {
								if ($va_val['size'] > 0) {	// is there actually a file?
									$va_val['_uploaded_file'] = true;
									$va_attr_update[$vn_element_id] = $va_val;
									continue;
								}
							} 
							$vs_attr_val = $po_request->getParameter($vs_k, pString);
							$va_attr_update[$vn_element_id] = $vs_attr_val;
						}
						
						$this->clearErrors();
						$this->editAttribute($vn_attribute_id, $vn_element_set_id, $va_attr_update, $vs_f);
					}
				}
			}
		}
		
		if ($this->HIERARCHY_PARENT_ID_FLD && $vn_parent_id = $po_request->getParameter($vs_form_prefix.'HierLocation_new_parent_id', pInteger)) {
			$this->set($this->HIERARCHY_PARENT_ID_FLD, $vn_parent_id);
		}
		
		$this->setMode(ACCESS_WRITE);
			
		$vb_is_insert = false;
		if ($this->getPrimaryKey()) {
			$this->update();
		} else {
			$this->insert();
			$vb_is_insert = true;
		}
		if ($this->numErrors() > 0) {
			$va_errors = array();
			foreach($this->errors() as $o_e) {
				switch($o_e->getErrorNumber()) {
					case 2010:
						$po_request->addActionErrors(array($o_e), 'hierarchy_location');
						break;
					case 795:
						// field conflict
						foreach($this->getFieldConflicts() as $vs_conflict_field) {
							$po_request->addActionError($o_e, $vs_conflict_field);
						}
						break;
					case 1100:
						if ($vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD')) {
							$po_request->addActionError($o_e, $this->getProperty('ID_NUMBERING_ID_FIELD'));
						}
						break;
					default:
						$va_errors[] = $o_e;
						break;
				}
			}
			//print_r($this->getErrors());
			$po_request->addActionErrors($va_errors);
			
			if ($vb_is_insert) {
			 	BaseModel::unsetChangeLogUnitID();
			 	if ($vb_we_set_transaction) { $this->removeTransaction(false); }
				return false;	// bail on insert error
			}
		}
		
		if (!$this->getPrimaryKey()) { 
			BaseModel::unsetChangeLogUnitID(); 
			if ($vb_we_set_transaction) { $this->removeTransaction(false); }
			return false; 
		}	// bail if insert failed
		
		$this->clearErrors();
		
		//save reserved elements -  those with a saveElement method
		if (isset($reserved_elements) && is_array($reserved_elements)) {
			foreach($reserved_elements as $res_element) {
				$res_element_id = $res_element->getPrimaryKey();
				$res_element_datatype = $res_element->get('datatype');
				$res_datatype = Attribute::getValueInstance($res_element_datatype);
				$res_datatype->saveElement($this,$res_element,$vs_form_prefix,$po_request);
			}
		}
		
		// save preferred labels
		$vb_check_for_dupe_labels = $this->_CONFIG->get('allow_duplicate_labels_for_'.$this->tableName()) ? false : true;
		if (is_array($va_fields_by_type['preferred_label'])) {
			foreach($va_fields_by_type['preferred_label'] as $vs_placement_code => $vs_f) {
				// check for existing labels to update (or delete)
				$va_preferred_labels = $this->getPreferredLabels(null, false);
				foreach($va_preferred_labels as $vn_item_id => $va_labels_by_locale) {
					foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
						foreach($va_label_list as $va_label) {
							if ($vn_label_locale_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'locale_id_'.$va_label['label_id'], pString)) {
							
								if(is_array($va_label_values = $this->getLabelUIValuesFromRequest($po_request, $vs_placement_code.$vs_form_prefix, $va_label['label_id'], true))) {
									
									if ($vb_check_for_dupe_labels && $this->checkForDupeLabel($vn_label_locale_id, $va_label_values)) {
										$this->postError(1125, _t('Value is already used and duplicates are not allowed'), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()");
										$po_request->addActionErrors($this->errors(), 'preferred_labels');
										continue;
									}
									$vn_label_type_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'type_id_'.$va_label['label_id'], pInteger);
									$this->editLabel($va_label['label_id'], $va_label_values, $vn_label_locale_id, $vn_label_type_id, true);
									if ($this->numErrors()) {
										foreach($this->errors() as $o_e) {
											switch($o_e->getErrorNumber()) {
												case 795:
													// field conflicts
													$po_request->addActionError($o_e, 'preferred_labels');
													break;
												default:
													$po_request->addActionError($o_e, $vs_f);
													break;
											}
										}
									}
								}
							} else {
								if ($po_request->getParameter($vs_placement_code.$vs_form_prefix.'_PrefLabel_'.$va_label['label_id'].'_delete', pString)) {
									// delete
									$this->removeLabel($va_label['label_id']);
								}
							}
						}
					}
				}
				
				// check for new labels to add
				foreach($_REQUEST as $vs_key => $vs_value ) {
					if (!preg_match('/'.$vs_placement_code.$vs_form_prefix.'_Pref'.'locale_id_new_([\d]+)/', $vs_key, $va_matches)) { continue; }
					$vn_c = intval($va_matches[1]);
					if ($vn_new_label_locale_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'locale_id_new_'.$vn_c, pString)) {
						if(is_array($va_label_values = $this->getLabelUIValuesFromRequest($po_request, $vs_placement_code.$vs_form_prefix, 'new_'.$vn_c, true))) {
							if ($vb_check_for_dupe_labels && $this->checkForDupeLabel($vn_new_label_locale_id, $va_label_values)) {
								$this->postError(1125, _t('Value is already used and duplicates are not allowed'), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()");
								$po_request->addActionErrors($this->errors(), 'preferred_labels');
								continue;
							}
							
							$this->addLabel($va_label_values, $vn_new_label_locale_id, null, true);	
							if ($this->numErrors()) {
								$po_request->addActionErrors($this->errors(), $vs_f);
							}
						}
					}
				}
			}
		}
		
		// Add default label if needed (ie. if the user has failed to set at least one label or if they have deleted all existing labels)
		// This ensures at least one label is present for the record. If no labels are present then the 
		// record may not be found in queries
		$this->addDefaultLabel();
		
		// save non-preferred labels
		if (isset($va_fields_by_type['nonpreferred_label']) && is_array($va_fields_by_type['nonpreferred_label'])) {
			foreach($va_fields_by_type['nonpreferred_label'] as $vs_placement_code => $vs_f) {
				// check for existing labels to update (or delete)
				$va_nonpreferred_labels = $this->getNonPreferredLabels(null, false);
				foreach($va_nonpreferred_labels as $vn_item_id => $va_labels_by_locale) {
					foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
						foreach($va_label_list as $va_label) {
							if ($vn_label_locale_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPref'.'locale_id_'.$va_label['label_id'], pString)) {
								if (is_array($va_label_values = $this->getLabelUIValuesFromRequest($po_request, $vs_placement_code.$vs_form_prefix, $va_label['label_id'], false))) {
									$vn_label_type_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPref'.'type_id_'.$va_label['label_id'], pInteger);
									$this->editLabel($va_label['label_id'], $va_label_values, $vn_label_locale_id, $vn_label_type_id, false);
									if ($this->numErrors()) {
										foreach($this->errors() as $o_e) {
											switch($o_e->getErrorNumber()) {
												case 795:
													// field conflicts
													$po_request->addActionError($o_e, 'nonpreferred_labels');
													break;
												default:
													$po_request->addActionError($o_e, $vs_f);
													break;
											}
										}
									}
								}
							} else {
								if ($po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPrefLabel_'.$va_label['label_id'].'_delete', pString)) {
									// delete
									$this->removeLabel($va_label['label_id']);
								}
							}
						}
					}
				}
				
				// check for new labels to add
				foreach($_REQUEST as $vs_key => $vs_value ) {
					if (!preg_match('/'.$vs_placement_code.$vs_form_prefix.'_NPref'.'locale_id_new_([\d]+)/', $vs_key, $va_matches)) { continue; }
					$vn_c = intval($va_matches[1]);
					if ($vn_new_label_locale_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPref'.'locale_id_new_'.$vn_c, pString)) {
						if (is_array($va_label_values = $this->getLabelUIValuesFromRequest($po_request, $vs_placement_code.$vs_form_prefix, 'new_'.$vn_c, false))) {
							$vn_new_label_type_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPref'.'type_id_new_'.$vn_c, pInteger);
							$this->addLabel($va_label_values, $vn_new_label_locale_id, $vn_new_label_type_id, false);	
							
							if ($this->numErrors()) {
								$po_request->addActionErrors($this->errors(), $vs_f);
							}
						}
					}
				}
			}
		}
		
		
		// save data in related tables
		if (isset($va_fields_by_type['related_table']) && is_array($va_fields_by_type['related_table'])) {
			foreach($va_fields_by_type['related_table'] as $vs_placement_code => $vs_f) {
				$vn_table_num = $this->_DATAMODEL->getTableNum($vs_f);
				$vs_prefix_stub = $vs_placement_code.$vs_form_prefix.'_'.$vs_f.'_';
				
				switch($vs_f) {
					# -------------------------------------
					case 'ca_object_representations':
						// check for existing representations to update (or delete)
						
						$vb_allow_fetching_of_urls = (bool)$this->_CONFIG->get('allow_fetching_of_media_from_remote_urls');
						$va_rep_ids_sorted = $va_rep_sort_order = explode(';',$po_request->getParameter($vs_prefix_stub.'ObjectRepresentationBundleList', pString));
						sort($va_rep_ids_sorted, SORT_NUMERIC);
						
						
						$va_reps = $this->getRepresentations();
						foreach($va_reps as $va_rep) {
							$this->clearErrors();
							if (($vn_status = $po_request->getParameter($vs_prefix_stub.'status_'.$va_rep['representation_id'], pInteger)) != '') {
								if ($vb_allow_fetching_of_urls && ($vs_path = $_REQUEST[$vs_prefix_stub.'media_url_'.$va_rep['representation_id']])) {
									$va_tmp = explode('/', $vs_path);
									$vs_original_name = array_pop($va_tmp);
								} else {
									$vs_path = $_FILES[$vs_prefix_stub.'media_'.$va_rep['representation_id']]['tmp_name'];
									$vs_original_name = $_FILES[$vs_prefix_stub.'media_'.$va_rep['representation_id']]['name'];
								}
								
								$vn_locale_id = $po_request->getParameter($vs_prefix_stub.'locale_id_'.$va_rep['representation_id'], pInteger);
								$vn_access = $po_request->getParameter($vs_prefix_stub.'access_'.$va_rep['representation_id'], pInteger);
								$vn_is_primary = $po_request->getParameter($vs_prefix_stub.'is_primary_'.$va_rep['representation_id'], pInteger);
								
								$vn_rank = null;
								if (($vn_rank_index = array_search($va_rep['representation_id'], $va_rep_sort_order)) !== false) {
									$vn_rank = $va_rep_ids_sorted[$vn_rank_index];
								}
								
								$this->editRepresentation($va_rep['representation_id'], $vs_path, $vn_locale_id, $vn_status, $vn_access, $vn_is_primary, array(), array('original_filename' => $vs_original_name, 'rank' => $vn_rank));
								if ($this->numErrors()) {
									//$po_request->addActionErrors($this->errors(), $vs_f, $va_rep['representation_id']);
									foreach($this->errors() as $o_e) {
										switch($o_e->getErrorNumber()) {
											case 795:
												// field conflicts
												$po_request->addActionError($o_e, $vs_f, $va_rep['representation_id']);
												break;
											default:
												$po_request->addActionError($o_e, $vs_f, $va_rep['representation_id']);
												break;
										}
									}
								}
								
							} else {
								// is it a delete key?
								$this->clearErrors();
								if (($po_request->getParameter($vs_prefix_stub.$va_rep['representation_id'].'_delete', pInteger)) > 0) {
									// delete!
									$this->removeRepresentation($va_rep['representation_id']);
									if ($this->numErrors()) {
										$po_request->addActionErrors($this->errors(), $vs_f, $va_rep['representation_id']);
									}
								}
							}
						}
						
						// check for new representations to add 
						foreach($_FILES as $vs_key => $vs_value) {
							$this->clearErrors();
							if (!preg_match('/^'.$vs_prefix_stub.'media_new_([\d]+)$/', $vs_key, $va_matches)) { continue; }
							if ($vb_allow_fetching_of_urls && ($vs_path = $_REQUEST[$vs_prefix_stub.'media_url_new_'.$va_matches[1]])) {
								$va_tmp = explode('/', $vs_path);
								$vs_original_name = array_pop($va_tmp);
							} else {
								$vs_path = $_FILES[$vs_prefix_stub.'media_new_'.$va_matches[1]]['tmp_name'];
								$vs_original_name = $_FILES[$vs_prefix_stub.'media_new_'.$va_matches[1]]['name'];
							}
							if (!$vs_path) { continue; }
							
							$vn_locale_id = $po_request->getParameter($vs_prefix_stub.'locale_id_new_'.$va_matches[1], pInteger);
							$vn_type_id = $po_request->getParameter($vs_prefix_stub.'type_id_new_'.$va_matches[1], pInteger);
							$vn_status = $po_request->getParameter($vs_prefix_stub.'status_new_'.$va_matches[1], pInteger);
							$vn_access = $po_request->getParameter($vs_prefix_stub.'access_new_'.$va_matches[1], pInteger);
							$vn_is_primary = $po_request->getParameter($vs_prefix_stub.'is_primary_new_'.$va_matches[1], pInteger);
							$this->addRepresentation($vs_path, $vn_type_id, $vn_locale_id, $vn_status, $vn_access, $vn_is_primary, array(), array('original_filename' => $vs_original_name));
							
							if ($this->numErrors()) {
								$po_request->addActionErrors($this->errors(), $vs_f, 'new_'.$va_matches[1]);
							}
						}
						break;
					# -------------------------------------
					case 'ca_entities':
					case 'ca_places':
					case 'ca_objects':
					case 'ca_collections':
					case 'ca_occurrences':
					case 'ca_list_items':
					case 'ca_object_lots':
					case 'ca_storage_locations':
					case 'ca_loans':
					case 'ca_movements':
						$this->_processRelated($po_request, $vs_f, $vs_placement_code.$vs_form_prefix);
						break;
					# -------------------------------------
					case 'ca_representation_annotations':
						$this->_processRepresentationAnnotations($po_request, $vs_form_prefix, $vs_placement_code);
						break;
					# -------------------------------------
				}
			}	
		}
		
		
		// save data for "specials"
		if (isset($va_fields_by_type['special']) && is_array($va_fields_by_type['special'])) {
			foreach($va_fields_by_type['special'] as $vs_f) {
				switch($vs_f) {
					# -------------------------------------
					// This bundle is only available when editing objects of type ca_representation_annotations
					case 'ca_representation_annotation_properties':
						foreach($this->getPropertyList() as $vs_property) {
							$this->setPropertyValue($vs_property, $po_request->getParameter($vs_property, pString));
						}
						if (!$this->validatePropertyValues()) {
							$po_request->addActionErrors($this->errors(), 'ca_representation_annotation_properties', 'general');
						}
						break;
					# -------------------------------------
					// This bundle is only available for types which support set membership
					case 'ca_sets':
						// check for existing labels to delete (no updating supported)
						require_once(__CA_MODELS_DIR__.'/ca_sets.php');
						require_once(__CA_MODELS_DIR__.'/ca_set_items.php');
	
						$t_set = new ca_sets();
						$va_sets = caExtractValuesByUserLocale($t_set->getSetsForItem($this->tableNum(), $this->getPrimaryKey(), array('user_id' => $po_request->getUserID()))); 
	
						foreach($va_sets as $vn_set_id => $va_set_info) {
							$vn_item_id = $va_set_info['item_id'];
							
							if ($po_request->getParameter($vs_form_prefix.'_ca_sets_set_id_'.$vn_item_id.'_delete', pString)) {
								// delete
								$t_set->load($va_set_info['set_id']);
								$t_set->removeItem($this->getPrimaryKey(), $po_request->getUserID());	// remove *all* instances of the item in the set, not just the specified id
								if ($t_set->numErrors()) {
									$po_request->addActionErrors($t_set->errors(), $vs_f);
								}
							}
						}
						
						foreach($_REQUEST as $vs_key => $vs_value) {
							if (!preg_match('/'.$vs_form_prefix.'_ca_sets_set_id_new_([\d]+)/', $vs_key, $va_matches)) { continue; }
							$vn_c = intval($va_matches[1]);
							if ($vn_new_set_id = $po_request->getParameter($vs_form_prefix.'_ca_sets_set_id_new_'.$vn_c, pString)) {
								$t_set->load($vn_new_set_id);
								$t_set->addItem($this->getPrimaryKey(), null, $po_request->getUserID());
								if ($t_set->numErrors()) {
									$po_request->addActionErrors($t_set->errors(), $vs_f);
								}
							}
						}
						break;
					# -------------------------------------
					// This bundle is only available for types which support set membership
					case 'ca_set_items':
						// check for existing labels to delete (no updating supported)
						require_once(__CA_MODELS_DIR__.'/ca_sets.php');
						require_once(__CA_MODELS_DIR__.'/ca_set_items.php');
						
						$va_row_ids = explode(';', $po_request->getParameter($vs_form_prefix.'_ca_set_itemssetRowIDList', pString));
						$this->reorderItems($va_row_ids, array('user_id' => $po_request->getUserID()));
						break;
					# -------------------------------------
					// This bundle is only available for ca_search_forms 
					case 'ca_search_form_elements':
						// save settings
						$va_settings = $this->getAvailableSettings();
						foreach($va_settings as $vs_setting => $va_setting_info) {
							if(isset($_REQUEST['setting_'.$vs_setting])) {
								$vs_setting_val = $po_request->getParameter('setting_'.$vs_setting, pString);
								$this->setSetting($vs_setting, $vs_setting_val);
								$this->update();
							}
						}
						break;
					# -------------------------------------
					// This bundle is only available for ca_bundle_displays 
					case 'ca_bundle_display_placements':
						require_once(__CA_MODELS_DIR__.'/ca_bundle_displays.php');
						require_once(__CA_MODELS_DIR__.'/ca_bundle_display_placements.php');
						if ($vs_bundles = $po_request->getParameter($vs_form_prefix.'_ca_bundle_display_placementsdisplayBundleList', pString)) {
							$va_bundles = explode(';', $vs_bundles);
							
							$t_display = new ca_bundle_displays($this->getPrimaryKey());
							$va_placements = $t_display->getPlacements(array('user_id' => $po_request->getUserID()));
							
							// remove deleted bundles
							
							foreach($va_placements as $vn_placement_id => $va_bundle_info) {
								if (!in_array($va_bundle_info['bundle_name'].'_'.$va_bundle_info['placement_id'], $va_bundles)) {
									$t_display->removePlacement($va_bundle_info['placement_id'], array('user_id' => $po_request->getUserID()));
									if ($t_display->numErrors()) {
										$this->errors = $t_display->errors;
										return false;
									}
								}
							}
							
							$t_locale = new ca_locales();
							$va_locale_list = $t_locale->getLocaleList(array('index_by_code' => true));
							
							$va_available_bundles = $t_display->getAvailableBundles();
							foreach($va_bundles as $vn_i => $vs_bundle) {
								// get settings
								
								if (preg_match('!^(.*)_([\d]+)$!', $vs_bundle, $va_matches)) {
									$vn_placement_id = (int)$va_matches[2];
									$vs_bundle = $va_matches[1];
								} else {
									$vn_placement_id = null;
								}
								$vs_bundle_proc = str_replace(".", "_", $vs_bundle);
								
								$va_settings = array();
								
								foreach($_REQUEST as $vs_key => $vs_val) {
									if (preg_match("!^{$vs_bundle_proc}_([\d]+)_(.*)$!", $vs_key, $va_matches)) {
										
										// is this locale-specific?
										if (preg_match('!(.*)_([a-z]{2}_[A-Z]{2})$!', $va_matches[2], $va_locale_matches)) {
											$vn_locale_id = isset($va_locale_list[$va_locale_matches[2]]) ? (int)$va_locale_list[$va_locale_matches[2]]['locale_id'] : 0;
											$va_settings[(int)$va_matches[1]][$va_locale_matches[1]][$vn_locale_id] = $vs_val;
										} else {
											$va_settings[(int)$va_matches[1]][$va_matches[2]] = $vs_val;
										}
									}
								}
								
								if($vn_placement_id === 0) {
									$t_display->addPlacement($vs_bundle, $va_settings[$vn_placement_id], $vn_i + 1, array('user_id' => $po_request->getUserID(), 'additional_settings' => $va_available_bundles[$vs_bundle]['settings']));
									if ($t_display->numErrors()) {
										$this->errors = $t_display->errors;
										return false;
									}
								} else {
									$t_placement = new ca_bundle_display_placements($vn_placement_id, $va_available_bundles[$vs_bundle]['settings']);
									$t_placement->setMode(ACCESS_WRITE);
									$t_placement->set('rank', $vn_i + 1);
									
									if (is_array($va_settings[$vn_placement_id])) {
										//foreach($va_settings[$vn_placement_id] as $vs_setting => $vs_val) {
										foreach($t_placement->getAvailableSettings() as $vs_setting => $va_setting_info) {
											$vs_val = isset($va_settings[$vn_placement_id][$vs_setting]) ? $va_settings[$vn_placement_id][$vs_setting] : null;
										
											$t_placement->setSetting($vs_setting, $vs_val);
										}
									}
									$t_placement->update();
									
									if ($t_placement->numErrors()) {
										$this->errors = $t_placement->errors;
										return false;
									}
								}
							}
						} 
						break;
					# -------------------------------------
					// This bundle is only available for ca_search_forms 
					case 'ca_search_form_placements':
						require_once(__CA_MODELS_DIR__.'/ca_search_forms.php');
						require_once(__CA_MODELS_DIR__.'/ca_search_form_placements.php');
						if ($vs_bundles = $po_request->getParameter($vs_form_prefix.'_ca_search_form_placementsdisplayBundleList', pString)) {
							$va_bundles = explode(';', $vs_bundles);
							
							$t_form = new ca_search_forms($this->getPrimaryKey());
							$va_placements = $t_form->getPlacements(array('user_id' => $po_request->getUserID()));
							
							// remove deleted bundles
							
							foreach($va_placements as $vn_placement_id => $va_bundle_info) {
								if (!in_array($va_bundle_info['bundle_name'].'_'.$va_bundle_info['placement_id'], $va_bundles)) {
									$t_form->removePlacement($va_bundle_info['placement_id'], array('user_id' => $po_request->getUserID()));
									if ($t_form->numErrors()) {
										$this->errors = $t_form->errors;
										return false;
									}
								}
							}
							
							$t_locale = new ca_locales();
							$va_locale_list = $t_locale->getLocaleList(array('index_by_code' => true));
							
							$va_available_bundles = $t_form->getAvailableBundles();
							foreach($va_bundles as $vn_i => $vs_bundle) {
								// get settings
								
								if (preg_match('!^(.*)_([\d]+)$!', $vs_bundle, $va_matches)) {
									$vn_placement_id = (int)$va_matches[2];
									$vs_bundle = $va_matches[1];
								} else {
									$vn_placement_id = null;
								}
								$vs_bundle_proc = str_replace(".", "_", $vs_bundle);
								
								$va_settings = array();
								
								foreach($_REQUEST as $vs_key => $vs_val) {
									if (preg_match("!^{$vs_bundle_proc}_([\d]+)_(.*)$!", $vs_key, $va_matches)) {
										
										// is this locale-specific?
										if (preg_match('!(.*)_([a-z]{2}_[A-Z]{2})$!', $va_matches[2], $va_locale_matches)) {
											$vn_locale_id = isset($va_locale_list[$va_locale_matches[2]]) ? (int)$va_locale_list[$va_locale_matches[2]]['locale_id'] : 0;
											$va_settings[(int)$va_matches[1]][$va_locale_matches[1]][$vn_locale_id] = $vs_val;
										} else {
											$va_settings[(int)$va_matches[1]][$va_matches[2]] = $vs_val;
										}
									}
								}
								
								if($vn_placement_id === 0) {
									$t_form->addPlacement($vs_bundle, $va_settings[$vn_placement_id], $vn_i + 1, array('user_id' => $po_request->getUserID(), 'additional_settings' => $va_available_bundles[$vs_bundle]['settings']));
									if ($t_form->numErrors()) {
										$this->errors = $t_form->errors;
										return false;
									}
								} else {
									$t_placement = new ca_search_form_placements($vn_placement_id, $va_available_bundles[$vs_bundle]['settings']);
									$t_placement->setMode(ACCESS_WRITE);
									$t_placement->set('rank', $vn_i + 1);
									
									if (is_array($va_settings[$vn_placement_id])) {
										//foreach($va_settings[$vn_placement_id] as $vs_setting => $vs_val) {
										foreach($t_placement->getAvailableSettings() as $vs_setting => $va_setting_info) {
											$vs_val = isset($va_settings[$vn_placement_id][$vs_setting]) ? $va_settings[$vn_placement_id][$vs_setting] : null;
										
											$t_placement->setSetting($vs_setting, $vs_val);
										}
									}
									$t_placement->update();
									
									if ($t_placement->numErrors()) {
										$this->errors = $t_placement->errors;
										return false;
									}
								}
							}
						} 
						break;
					# -------------------------------------
					case 'ca_user_groups':
						if ($po_request->getUserID() != $this->get('user_id')) { break; }	// don't save if user is not owner
						require_once(__CA_MODELS_DIR__.'/ca_user_groups.php');
	
						$va_groups = $po_request->user->getGroupList($po_request->getUserID());
						
						$va_groups_to_set = $va_group_effective_dates = array();
						foreach($_REQUEST as $vs_key => $vs_val) { 
							if (preg_match("!^{$vs_form_prefix}_ca_user_groups_id(.*)$!", $vs_key, $va_matches)) {
								$vs_effective_date = $po_request->getParameter($vs_form_prefix.'_ca_user_groups_effective_date_'.$va_matches[1], pString);
								$vn_group_id = (int)$po_request->getParameter($vs_form_prefix.'_ca_user_groups_id'.$va_matches[1], pInteger);
								$vn_access = $po_request->getParameter($vs_form_prefix.'_ca_user_groups_access_'.$va_matches[1], pInteger);
								if ($vn_access > 0) {
									$va_groups_to_set[$vn_group_id] = $vn_access;
									$va_group_effective_dates[$vn_group_id] = $vs_effective_date;
								}
							}
						}
												
						$this->setUserGroups($va_groups_to_set, $va_group_effective_dates);
						
						break;
					# -------------------------------------
					case 'ca_users':
						if ($po_request->getUserID() != $this->get('user_id')) { break; }	// don't save if user is not owner
						require_once(__CA_MODELS_DIR__.'/ca_users.php');
	
						$va_users = $po_request->user->getUserList($po_request->getUserID());
						
						$va_users_to_set = $va_user_effective_dates = array();
						foreach($_REQUEST as $vs_key => $vs_val) { 
							if (preg_match("!^{$vs_form_prefix}_ca_users_id(.*)$!", $vs_key, $va_matches)) {
								$vs_effective_date = $po_request->getParameter($vs_form_prefix.'_ca_users_effective_date_'.$va_matches[1], pString);
								$vn_user_id = (int)$po_request->getParameter($vs_form_prefix.'_ca_users_id'.$va_matches[1], pInteger);
								$vn_access = $po_request->getParameter($vs_form_prefix.'_ca_users_access_'.$va_matches[1], pInteger);
								if ($vn_access > 0) {
									$va_users_to_set[$vn_user_id] = $vn_access;
									$va_user_effective_dates[$vn_user_id] = $vs_effective_date;
								}
							}
						}
						
						$this->setUsers($va_users_to_set, $va_user_effective_dates);
						
						break;
					# -------------------------------------
					case 'settings':
						$this->setSettingsFromHTMLForm($po_request);
						break;
					# -------------------------------------
				}
			}
		}
		
		BaseModel::unsetChangeLogUnitID();
		if ($vb_we_set_transaction) { $this->removeTransaction(true); }
		return true;
	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	private function _processRelated($po_request, $ps_bundlename, $ps_form_prefix) {
 		$va_rel_items = $this->getRelatedItems($ps_bundlename);
 		
		foreach($va_rel_items as $va_rel_item) {
			$vs_key = $va_rel_item['_key'];
			$this->clearErrors();
			$vn_id = $po_request->getParameter($ps_form_prefix.'_'.$ps_bundlename.'_id'.$va_rel_item[$vs_key], pString);
			if ($vn_id) {
				$vn_type_id = $po_request->getParameter($ps_form_prefix.'_'.$ps_bundlename.'_type_id'.$va_rel_item[$vs_key], pString);
				
				$vs_direction = null;
				if (sizeof($va_tmp = explode('_', $vn_type_id)) == 2) {
					$vn_type_id = (int)$va_tmp[1];
					$vs_direction = $va_tmp[0];
				}
				
				$this->editRelationship($ps_bundlename, $va_rel_item[$vs_key], $vn_id, $vn_type_id, null, null, $vs_direction);	
					
				if ($this->numErrors()) {
					$po_request->addActionErrors($this->errors(), $vs_f);
				}
			} else {
				// is it a delete key?
				$this->clearErrors();
				if (($po_request->getParameter($ps_form_prefix.'_'.$ps_bundlename.'_'.$va_rel_item[$vs_key].'_delete', pInteger)) > 0) {
					// delete!
					$this->removeRelationship($ps_bundlename, $va_rel_item[$vs_key]);
					if ($this->numErrors()) {
						$po_request->addActionErrors($this->errors(), $vs_f, $va_rel_item[$vs_key]);
					}
				}
			}
		}
 		
 		// check for new relations to add
 		foreach($_REQUEST as $vs_key => $vs_value ) {
			if (!preg_match('/^'.$ps_form_prefix.'_'.$ps_bundlename.'_idnew_([\d]+)/', $vs_key, $va_matches)) { continue; }
			$vn_c = intval($va_matches[1]);
			if ($vn_new_id = $po_request->getParameter($ps_form_prefix.'_'.$ps_bundlename.'_idnew_'.$vn_c, pString)) {
				$vn_new_type_id = $po_request->getParameter($ps_form_prefix.'_'.$ps_bundlename.'_type_idnew_'.$vn_c, pString);
				
				$vs_direction = null;
				if (sizeof($va_tmp = explode('_', $vn_new_type_id)) == 2) {
					$vn_new_type_id = (int)$va_tmp[1];
					$vs_direction = $va_tmp[0];
				}
				
				$this->addRelationship($ps_bundlename, $vn_new_id, (int)$vn_new_type_id, null, null, $vs_direction);	
				if ($this->numErrors()) {
					$po_request->addActionErrors($this->errors(), $vs_f);
				}
			}
		}
		return true;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns list of items in the specified table related to the currently loaded row.
 	 * 
 	 * @param $pm_rel_table_name_or_num - the table name or table number of the item type you want to get a list of (eg. if you are calling this on an ca_objects instance passing 'ca_entities' here will get you a list of entities related to the object)
 	 * @param $pa_options - array of options. Supported options are:
 	 *
 	 * 		restrict_to_type - restricts returned items to those of the specified type; only supports a single type which can be specified as a list item_code or item_id
 	 *		restrict_to_types - restricts returned items to those of the specified types; pass an array of list item_codes or item_ids
 	 *		restrict_to_relationship_types - restricts returned items to those related to the current row by the specified relationship type(s). You can pass either an array of types or a single type. The types can be relationship type_code's or type_id's.
 	 *		exclude_relationship_types - omits any items related to the current row with any of the specified types from the returned set of its. You can pass either an array of types or a single type. The types can be relationship type_code's or type_id's.
 	 *		fields - array of fields (in table.fieldname format) to include in returned data
 	 *		return_non_preferred_labels - if set to true, non-preferred labels are included in returned data
 	 *		checkAccess - array of access values to filter results by; if defined only items with the specified access code(s) are returned
 	 *		returnLabelsAsArray - if set to true then all labels associated with row are returned in an array, otherwise only a text value in the current locale is returned; default is false - return single label in current locale
 	 * 		row_ids - array of primary key values to use when fetching related items; if omitted or set to a null value the 'row_id' option (single value) will be used; if row_id is also not set then the currently loaded primary key value will be used
 	 *		row_id - primary key value to use when fetching related items; if omitted or set to a false value (eg. null, false, 0) then the currently loaded primary key value is used [default]
 	 *		limit - number of items to limit return set to; default is 1000
 	 *
 	 * @return array - list of related items
 	 */
	 public function getRelatedItems($pm_rel_table_name_or_num, $pa_options=null) {
		 $o_db = $this->getDb();
		 
		$va_row_ids = (isset($pa_options['row_ids']) && is_array($pa_options['row_ids'])) ? $pa_options['row_ids'] : null;
		$vn_row_id = (isset($pa_options['row_id']) && $pa_options['row_id']) ? $pa_options['row_id'] : $this->getPrimaryKey();
		
		if (!$va_row_ids && ($vn_row_id > 0)) {
			$va_row_ids = array($vn_row_id);
		}
		
		if (!$va_row_ids || !is_array($va_row_ids) || !sizeof($va_row_ids)) { return array(); }
		
		$vb_return_labels_as_array = (isset($pa_options['returnLabelsAsArray']) && $pa_options['returnLabelsAsArray']) ? true : false;
		$vn_limit = (isset($pa_options['limit']) && ((int)$pa_options['limit'] > 0)) ? (int)$pa_options['limit'] : 1000;
              
		if (is_numeric($pm_rel_table_name_or_num)) {
			$vs_related_table_name = $this->getAppDataModel()->getTableName($pm_rel_table_name_or_num);
		} else {
			$vs_related_table_name = $pm_rel_table_name_or_num;
		}
		
		if (!is_array($pa_options)) { $pa_options = array(); }

		switch(sizeof($va_path = array_keys($this->getAppDatamodel()->getPath($this->tableName(), $vs_related_table_name)))) {
			case 3:
				$t_item_rel = $this->getAppDatamodel()->getTableInstance($va_path[1]);
				$t_rel_item = $this->getAppDatamodel()->getTableInstance($va_path[2]);
				$vs_key = 'relation_id';
				break;
			case 2:
				$t_item_rel = null;
				$t_rel_item = $this->getAppDatamodel()->getTableInstance($va_path[1]);
				$vs_key = $t_rel_item->primaryKey();
				break;
			default:
				// bad related table
				return null;
				break;
		}
		
		// check for self relationship
		$vb_self_relationship = false;
		if($this->tableName() == $vs_related_table_name) {
			$vb_self_relationship = true;
			$t_rel_item = $this->getAppDatamodel()->getTableInstance($va_path[0]);
			$t_item_rel = $this->getAppDatamodel()->getTableInstance($va_path[1]);
		}
		
		$va_wheres = array();
		$va_selects = array();

		// TODO: get these field names from models
		if ($t_item_rel) {
			//define table names
			$vs_linking_table = $t_item_rel->tableName();
			$vs_related_table = $t_rel_item->tableName();
			if ($t_rel_item->hasField('type_id')) { $va_selects[] = $vs_related_table.'.type_id item_type_id'; }
			
			$va_selects[] = $vs_related_table.'.'.$t_rel_item->primaryKey();
			if ($t_item_rel->hasField('type_id')) {
				$va_selects[] = $vs_linking_table.'.type_id relationship_type_id';
				
				require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
				$t_rel = new ca_relationship_types();
				
				$vb_uses_relationship_types = true;
			}
			
			// limit related items to a specific type
			if ($vb_uses_relationship_types && isset($pa_options['restrict_to_relationship_types']) && $pa_options['restrict_to_relationship_types']) {
				if (!is_array($pa_options['restrict_to_relationship_types'])) {
					$pa_options['restrict_to_relationship_types'] = array($pa_options['restrict_to_relationship_types']);
				}
				
				if (sizeof($pa_options['restrict_to_relationship_types'])) {
					$va_rel_types = array();
					foreach($pa_options['restrict_to_relationship_types'] as $vm_type) {
						if ($vn_type_id = $t_rel->getRelationshipTypeID($vs_linking_table, $vm_type)) {
							$va_rel_types[] = $vn_type_id;
							if (is_array($va_children = $t_rel->getHierarchyChildren($vn_type_id, array('idsOnly' => true)))) {
								$va_rel_types = array_merge($va_rel_types, $va_children);
							}
						}
					}
					
					if (sizeof($va_rel_types)) {
						$va_wheres[] = '('.$vs_linking_table.'.type_id IN ('.join(',', $va_rel_types).'))';
					}
				}
			}
			
			if ($vb_uses_relationship_types && isset($pa_options['exclude_relationship_types']) && $pa_options['exclude_relationship_types']) {
				if (!is_array($pa_options['exclude_relationship_types'])) {
					$pa_options['exclude_relationship_types'] = array($pa_options['exclude_relationship_types']);
				}
				
				if (sizeof($pa_options['exclude_relationship_types'])) {
					$va_rel_types = array();
					foreach($pa_options['exclude_relationship_types'] as $vm_type) {
						if ($vn_type_id = $t_rel->getRelationshipTypeID($vs_linking_table, $vm_type)) {
							$va_rel_types[] = $vn_type_id;
							if (is_array($va_children = $t_rel->getHierarchyChildren($vn_type_id, array('idsOnly' => true)))) {
								$va_rel_types = array_merge($va_rel_types, $va_children);
							}
						}
					}
					
					if (sizeof($va_rel_types)) {
						$va_wheres[] = '('.$vs_linking_table.'.type_id NOT IN ('.join(',', $va_rel_types).'))';
					}
				}
			}
		}
		
		// limit related items to a specific type
		if (isset($pa_options['restrict_to_type']) && $pa_options['restrict_to_type']) {
			if (!isset($pa_options['restrict_to_types']) || !is_array($pa_options['restrict_to_types'])) {
				$pa_options['restrict_to_types'] = array();
			}
			$pa_options['restrict_to_types'][] = $pa_options['restrict_to_type'];
		}
		
		if (isset($pa_options['restrict_to_types']) && $pa_options['restrict_to_types']  && is_array($pa_options['restrict_to_types'])) {
			$t_list = new ca_lists();
			$t_list_item = new ca_list_items();
			
			$va_ids = array();
			foreach($pa_options['restrict_to_types'] as $vs_type) {
				if (!($vn_restrict_to_type_id = (int)$t_list->getItemIDFromList($t_rel_item->getTypeListCode(), $vs_type))) {
					$vn_restrict_to_type_id = (int)$vs_type;
				}
				if ($vn_restrict_to_type_id) {
					$va_children = $t_list_item->getHierarchyChildren($vn_restrict_to_type_id, array('idsOnly' => true));
					$va_ids = array_merge($va_ids, $va_children);
					$va_ids[] = $vn_restrict_to_type_id;
				}
			}
			
			if (sizeof($va_ids) > 0) {
				$va_wheres[] = '('.$vs_related_table.'.type_id IN ('.join(',', $va_ids).'))';
			}
		}
		
		if ($t_rel_item->hasField('idno')) { $va_selects[] = $t_rel_item->tableName().'.idno'; }
		if ($t_rel_item->hasField('idno_stub')) { $va_selects[] = $t_rel_item->tableName().'.idno_stub'; }
	
		$va_selects[] = $va_path[1].'.'.$vs_key;	
		
		if (isset($pa_options['fields']) && is_array($pa_options['fields'])) {
			$va_selects = array_merge($va_selects, $pa_options['fields']);
		}
		
		
		 // if related item is labelable then include the label table in the query as well
		$vs_label_display_field = null;
		if (method_exists($t_rel_item, "getLabelTableName")) {
			if($vs_label_table_name = $t_rel_item->getLabelTableName()) {           // make sure it actually has a label table... (ca_object_representations doesn't because it's only bundleable despite being "BundlableLabelable"
				$va_path[] = $vs_label_table_name;
				$t_rel_item_label = $this->getAppDatamodel()->getTableInstance($vs_label_table_name);
				$vs_label_display_field = $t_rel_item_label->getDisplayField();

				if($vb_return_labels_as_array) {
					$va_selects[] = $vs_label_table_name.'.*';
				} else {
					$va_selects[] = $vs_label_table_name.'.'.$vs_label_display_field;
					$va_selects[] = $vs_label_table_name.'.locale_id';
				}
				
				if ($t_rel_item_label->hasField('is_preferred') && (!isset($pa_options['return_non_preferred_labels']) || !$pa_options['return_non_preferred_labels'])) {
					$va_wheres[] = "(".$vs_label_table_name.'.is_preferred = 1)';
				}
			}
		}
		
		if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access')) {
			$va_wheres[] = "(".$t_rel_item->tableName().'.access IN ('.join(',', $pa_options['checkAccess']).'))';
		}

		if($vb_self_relationship) {
			//
			// START - self relation
			//
			$va_rel_info = $this->getAppDatamodel()->getRelationships($va_path[0], $va_path[1]);
			if ($vs_label_table_name) {
				$va_label_rel_info = $this->getAppDatamodel()->getRelationships($va_path[0], $vs_label_table_name);
			}
			
			$va_rels = array();
			
			$vn_i = 0;
			foreach($va_rel_info[$va_path[0]][$va_path[1]] as $va_possible_keys) {
				$va_joins = array();
				$va_joins[] = "INNER JOIN ".$va_path[1]." ON ".$va_path[1].'.'.$va_possible_keys[1].' = '.$va_path[0].'.'.$va_possible_keys[0]."\n";
				
				if ($vs_label_table_name) {
					$va_joins[] = "INNER JOIN ".$vs_label_table_name." ON ".$vs_label_table_name.'.'.$va_label_rel_info[$va_path[0]][$vs_label_table_name][0][1].' = '.$va_path[0].'.'.$va_label_rel_info[$va_path[0]][$vs_label_table_name][0][0]."\n";
				}
				
				$vs_other_field = ($vn_i == 0) ? $va_rel_info[$va_path[0]][$va_path[1]][1][1] : $va_rel_info[$va_path[0]][$va_path[1]][0][1];
				$vs_direction =  (preg_match('!left!', $vs_other_field)) ? 'ltor' : 'rtol';
				
				$va_selects['row_id'] = $va_path[1].'.'.$vs_other_field.' AS row_id';
				
				$vs_sql = "
					SELECT ".join(', ', $va_selects)."
					FROM ".$va_path[0]."
					".join("\n", $va_joins)."
					WHERE
						".join(' AND ', array_merge($va_wheres, array('('.$va_path[1].'.'.$vs_other_field .' IN ('.join(',', $va_row_ids).'))')))."
				";
				//print "<pre>$vs_sql</pre>\n";
			
				$qr_res = $o_db->query($vs_sql);
				
				if ($vb_uses_relationship_types) { $va_rel_types = $t_rel->getRelationshipInfo($va_path[1]); }
				$vn_c = 0;
				while($qr_res->nextRow()) {
					if ($vn_c >= $vn_limit) { break; }
					$va_row = $qr_res->getRow();
					$vn_id = $va_row[$vs_key].'/'.$va_row['row_id'];
					
					$vs_display_label = $va_row[$vs_label_display_field];
					//unset($va_row[$vs_label_display_field]);
					
					if (!$va_rels[$vn_id]) {
						$va_rels[$vn_id] = $qr_res->getRow();
					}
					$va_rels[$vn_id]['labels'][$qr_res->get('locale_id')] =  ($vb_return_labels_as_array) ? $va_row : $vs_display_label;
					$va_rels[$vn_id]['_key'] = $vs_key;
					$va_rels[$vn_id]['direction'] = $vs_direction;
					
					$vn_c++;
					if ($vb_uses_relationship_types) {
						$va_rels[$vn_id]['relationship_typename'] = ($vs_direction == 'ltor') ? $va_rel_types[$va_row['relationship_type_id']]['typename'] : $va_rel_types[$va_row['relationship_type_id']]['typename_reverse'];
					}
				}
				$vn_i++;
			}
			
			// Set 'label' entry - display label in current user's locale
			foreach($va_rels as $vn_id => $va_rel) {
				$va_tmp = array(0 => $va_rel['labels']);
				$va_rels[$vn_id]['label'] = array_shift(caExtractValuesByUserLocale($va_tmp));
			}
			
			//
			// END - self relation
			//
		} else {
			//
			// BEGIN - non-self relation
			//
			
			
			$va_wheres[] = "(".$this->tableName().'.'.$this->primaryKey()." IN (".join(",", $va_row_ids)."))";
			$vs_cur_table = array_shift($va_path);
			$va_joins = array();
			
			foreach($va_path as $vs_join_table) {
				$va_rel_info = $this->getAppDatamodel()->getRelationships($vs_cur_table, $vs_join_table);
				$va_joins[] = 'INNER JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
				$vs_cur_table = $vs_join_table;
			}
			
			$va_selects[] = $this->tableName().'.'.$this->primaryKey().' AS row_id';
			
			$vs_order_by = '';
			if ($t_rel_item && ($vs_sort = $t_rel_item->getProperty('ID_NUMBERING_SORT_FIELD'))) {
				$vs_order_by = " ORDER BY ".$t_rel_item->tableName().".{$vs_sort}";
			}
			
			$vs_sql = "
				SELECT ".join(', ', $va_selects)."
				FROM ".$this->tableName()."
				".join("\n", $va_joins)."
				WHERE
					".join(' AND ', $va_wheres)."
				{$vs_order_by}
			";
			
			//print "<pre>$vs_sql</pre>\n";
			$qr_res = $o_db->query($vs_sql);
			//print_r($o_db->getErrors());

			if ($vb_uses_relationship_types)  { 
				$va_rel_types = $t_rel->getRelationshipInfo($t_item_rel->tableName()); 
				$vs_left_table = $t_item_rel->getLeftTableName();
				$vs_direction = ($vs_left_table == $this->tableName()) ? 'ltor' : 'rtol';
			}
			$va_rels = array();
			$vn_c = 0;
			while($qr_res->nextRow()) {
				if ($vn_c >= $vn_limit) { break; }
				if (isset($pa_options['returnAsSearchResult']) && $pa_options['returnAsSearchResult']) {
					$va_rels[] = $qr_res->get($t_rel_item->primaryKey());
					continue;
				}
				
				$va_row = $qr_res->getRow();
				$vs_v = $va_row[$vs_key];
				
				$vs_display_label = $va_row[$vs_label_display_field];
				//unset($va_row[$vs_label_display_field]);
				
				if (!isset($va_rels[$vs_v]) || !$va_rels[$vs_v]) {
					$va_rels[$vs_v] = $va_row;
				}
				
				$va_rels[$vs_v]['labels'][$qr_res->get('locale_id')] =  ($vb_return_labels_as_array) ? $va_row : $vs_display_label;
				
				$va_rels[$vs_v]['_key'] = $vs_key;
				$va_rels[$vs_v]['direction'] = $vs_direction;
				
				$vn_c++;
				if ($vb_uses_relationship_types) {
					$va_rels[$vs_v]['relationship_typename'] = ($vs_direction == 'ltor') ? $va_rel_types[$va_row['relationship_type_id']]['typename'] : $va_rel_types[$va_row['relationship_type_id']]['typename_reverse'];
				}
			}
			
			if (!isset($pa_options['returnAsSearchResult']) || !$pa_options['returnAsSearchResult']) {
				// Set 'label' entry - display label in current user's locale
				foreach($va_rels as $vs_v => $va_rel) {
					$va_tmp = array(0 => $va_rel['labels']);
					$va_rels[$vs_v]['label'] = array_shift(caExtractValuesByUserLocale($va_tmp));
				}
			}
			
			//
			// END - non-self relation
			//
		}
		
		return $va_rels;
	}
	# --------------------------------------------------------------------------------------------
	public function getTypeMenu() {
		$t_list = new ca_lists();
		$t_list->load(array('list_code' => $this->getTypeListCode()));
		
		$t_list_item = new ca_list_items();
		$t_list_item->load(array('list_id' => $t_list->getPrimaryKey(), 'parent_id' => null));
		$va_hierarchy = caExtractValuesByUserLocale($t_list_item->getHierarchyWithLabels());
		
		$va_types = array();
		if (is_array($va_hierarchy)) {
			
			$va_types_by_parent_id = array();
			$vn_root_id = null;
			foreach($va_hierarchy as $vn_item_id => $va_item) {
				if (!$vn_root_id) { $vn_root_id = $va_item['parent_id']; continue; }
				$va_types_by_parent_id[$va_item['parent_id']][] = $va_item;
			}
			foreach($va_hierarchy as $vn_item_id => $va_item) {
				if ($va_item['parent_id'] != $vn_root_id) { continue; }
				// does this item have sub-items?
				if (isset($va_types_by_parent_id[$va_item['item_id']]) && is_array($va_types_by_parent_id[$va_item['item_id']])) {
					$va_subtypes = $this->_getSubTypes($va_types_by_parent_id[$va_item['item_id']], $va_types_by_parent_id);
				} else {
					$va_subtypes = array();
				}
				$va_types[] = array(
					'displayName' =>$va_item['name_singular'],
					'parameters' => array(
						'type_id' => $va_item['item_id']
					),
					'navigation' => $va_subtypes
				);
			}
		}
		return $va_types;
	}
	# ------------------------------------------------------------------
	/**
	 * Override's BaseModel method to intercept calls for field 'idno'; uses the specified IDNumbering
	 * plugin to generate HTML for idno. If no plugin is specified then the call is passed on to BaseModel::htmlFormElement()
	 * Calls for fields other than idno are passed to BaseModel::htmlFormElement()
	 */
	public function htmlFormElement($ps_field, $ps_format=null, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		foreach (array(
				'name', 'form_name', 'request', 'field_errors', 'display_form_field_tips', 'no_tooltips', 'label'
				) 
			as $vs_key) {
			if(!isset($pa_options[$vs_key])) { $pa_options[$vs_key] = null; }
		}
		
		if (
			($ps_field == $this->getProperty('ID_NUMBERING_ID_FIELD')) 
			&& 
			($this->opo_idno_plugin_instance)
			&&
			$pa_options['request']
		) {
			$this->opo_idno_plugin_instance->setValue($this->get($ps_field));
			$vs_element = $this->opo_idno_plugin_instance->htmlFormElement(
										$ps_field,  
										$va_errors, 
										array_merge(
											$pa_options,
											array(
												'error_icon' 				=> $pa_options['request']->getThemeUrlPath()."/graphics/icons/warning_small.gif",
												'progress_indicator'		=> $pa_options['request']->getThemeUrlPath()."/graphics/icons/indicator.gif",
												'show_errors'				=> true,
												'context_id'				=> isset($pa_options['context_id']) ? $pa_options['context_id'] : null,
												'table' 					=> $this->tableName(),
												'row_id' 					=> $this->getPrimaryKey(),
												'check_for_dupes'			=> true
											)
										)
			);
			
			if (is_null($ps_format)) {
				if (isset($pa_options['field_errors']) && is_array($pa_options['field_errors']) && sizeof($pa_options['field_errors'])) {
					$ps_format = $this->_CONFIG->get('bundle_element_error_display_format');
					$va_field_errors = array();
					foreach($pa_options['field_errors'] as $o_e) {
						$va_field_errors[] = $o_e->getErrorDescription();
					}
					$vs_errors = join('; ', $va_field_errors);
				} else {
					$ps_format = $this->_CONFIG->get('bundle_element_display_format');
					$vs_errors = '';
				}
			}
			if ($ps_format != '') {
				$ps_formatted_element = $ps_format;
				$ps_formatted_element = str_replace("^ELEMENT", $vs_element, $ps_formatted_element);

				$va_attr = $this->getFieldInfo($ps_field);
				
				foreach (array(
						'DISPLAY_DESCRIPTION', 'DESCRIPTION', 'LABEL', 'DESCRIPTION', 
						) 
					as $vs_key) {
					if(!isset($va_attr[$vs_key])) { $va_attr[$vs_key] = null; }
				}
				
// TODO: should be in config file
$pa_options["display_form_field_tips"] = true;
				if (
					$pa_options["display_form_field_tips"] ||
					(!isset($pa_options["display_form_field_tips"]) && $va_attr["DISPLAY_DESCRIPTION"]) ||
					(!isset($pa_options["display_form_field_tips"]) && !isset($va_attr["DISPLAY_DESCRIPTION"]) && $vb_fl_display_form_field_tips)
				) {
					if (preg_match("/\^DESCRIPTION/", $ps_formatted_element)) {
						$ps_formatted_element = str_replace("^LABEL", isset($pa_options['label']) ? $pa_options['label'] : $va_attr["LABEL"], $ps_formatted_element);
						$ps_formatted_element = str_replace("^DESCRIPTION",$va_attr["DESCRIPTION"], $ps_formatted_element);
					} else {
						// no explicit placement of description text, so...
						$vs_field_id = '_'.$this->tableName().'_'.$this->getPrimaryKey().'_'.$pa_options["name"].'_'.$pa_options['form_name'];
						$ps_formatted_element = str_replace("^LABEL",'<span id="'.$vs_field_id.'">'.(isset($pa_options['label']) ? $pa_options['label'] : $va_attr["LABEL"]).'</span>', $ps_formatted_element);

						if (!$pa_options['no_tooltips']) {
							TooltipManager::add('#'.$vs_field_id, "<h3>".(isset($pa_options['label']) ? $pa_options['label'] : $va_attr["LABEL"])."</h3>".$va_attr["DESCRIPTION"]);
						}
					}
				} else {
					$ps_formatted_element = str_replace("^LABEL", (isset($pa_options['label']) ? $pa_options['label'] : $va_attr["LABEL"]), $ps_formatted_element);
					$ps_formatted_element = str_replace("^DESCRIPTION", "", $ps_formatted_element);
				}

				$ps_formatted_element = str_replace("^ERRORS", $vs_errors, $ps_formatted_element);
				$vs_element = $ps_formatted_element;
			}
			
			
			return $vs_element;
		} else {
			return parent::htmlFormElement($ps_field, $ps_format, $pa_options);
		}
	}
	# ----------------------------------------
	/**
	 * 
	 */
	public function getIDNoPlugInInstance() {
		return $this->opo_idno_plugin_instance;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function validateAdminIDNo($ps_admin_idno) {
		$va_errors = array();
		if ($this->_CONFIG->get('require_valid_id_number_for_'.$this->tableName()) && sizeof($va_admin_idno_errors = $this->opo_idno_plugin_instance->isValidValue($ps_admin_idno))) {
			$va_errors[] = join('; ', $va_admin_idno_errors);
		} else {
			if (!$this->_CONFIG->get('allow_duplicate_id_number_for_'.$this->tableName()) && sizeof($this->checkForDupeAdminIdnos($ps_admin_idno))) {
				$va_errors[] = _t("Identifier %1 already exists and duplicates are not permitted", $ps_admin_idno);
			}
		}
		
		return $va_errors;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getSubTypes($pa_subtypes, $pa_types_by_parent_id) {
		$va_subtypes = array();
		foreach($pa_subtypes as $vn_i => $va_type) {
			if (is_array($pa_types_by_parent_id[$va_type['item_id']])) {
				$va_subsubtypes = $this->_getSubTypes($pa_types_by_parent_id[$va_type['item_id']], $pa_types_by_parent_id);
			} else {
				$va_subsubtypes = array();
			}
			$va_subtypes[$va_type['item_id']] = array(
				'displayName' => $va_type['name_singular'],
				'parameters' => array(
					'type_id' => $va_type['item_id']
				),
				'navigation' => $va_subsubtypes
			);
		}
		
		return $va_subtypes;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function makeSearchResult($pm_rel_table_name_or_num, $pa_ids) {
		if (!is_array($pa_ids) || !sizeof($pa_ids)) { return null; }
		$pn_table_num = $this->getAppDataModel()->getTableNum($pm_rel_table_name_or_num);
		if (!($t_instance = $this->getAppDataModel()->getInstanceByTableNum($pn_table_num))) { return null; }
	
		if (!($vs_search_result_class = $t_instance->getProperty('SEARCH_RESULT_CLASSNAME'))) { return null; }
		require_once(__CA_LIB_DIR__.'/ca/Search/'.$vs_search_result_class.'.php');
		$o_data = new WLPlugSearchEngineCachedResult($pa_ids, array(), $t_instance->primaryKey());
		$o_res = new $vs_search_result_class();
		$o_res->init($t_instance->tableNum(), $o_data, array());
		
		return $o_res;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns an array with as much as info as possible. This is used in the Web services, so that not to many requests need to be made.
	 * Information added:
	 * 	- field values
	 * 	- metadata information
	 * 	- label information
	 *  - relation information
	 */
	public function getItemInformationForService($return_options = array()) {
		$result = parent::getItemInformationForService($return_options);

		if(!isset($return_options['relations']) || $return_options['relations'] == true) {
			$primary_key = $this->getPrimaryKey();

			$relationship_key = $primary_key.'_relations';
			$relations_whole_content = $this->load_from_cache($relationship_key);
			if(!isset($relations_whole_content) || !is_array($relations_whole_content)) {
				$relations_whole_content = array();
				$arr_objects = array(
					"ca_objects" => 'object_id',
					"ca_entities" => 'entity_id',
					"ca_places" => 'place_id',
					"ca_occurences" => 'occurence_id',
					"ca_collections" => 'collection_id',
					"ca_list_items" => 'item_id',
				);
				foreach($arr_objects as $object => $key) {
					$relations = $this->getRelatedItems($object);
					if(!empty($relations)) {
						foreach($relations as $relation) {
							$type_id = $relation["relationship_type_id"];
							$rel_type = new ca_relationship_types($type_id);
							$relation['relationship_type_name'] = $rel_type->get('type_code');
							$relation['related_object_type'] = $object;
							$id = $object.'-'.$relation[$key];
							$relations_whole_content[$id] = $relation;
						}
					}
				}
				$this->save_to_cache($relationship_key, $relations_whole_content);
			}
			$result["relationships"] = $relations_whole_content;
		}

		return $result;
	}
	# --------------------------------------------------------------------------------------------
}
?>