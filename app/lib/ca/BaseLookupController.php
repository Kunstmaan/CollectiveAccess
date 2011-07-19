<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseSearchController.php : base controller for search interface
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
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 	
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 	require_once(__CA_MODELS_DIR__.'/ca_list_items.php');
 	
 	class BaseLookupController extends ActionController {
 		# -------------------------------------------------------
 		protected $opb_uses_hierarchy_browser = false;
 		protected $ops_table_name = '';
 		protected $ops_name_singular = '';
 		protected $ops_search_class = '';
 		protected $opo_item_instance;
 		
 		/**
 		 * @property $opa_filtera Criteria to filter list Get() return with; array keys are <tablename>.<fieldname> 
 		 * bundle specs; array values are *array* lists of values. If an item is not equal to a value in the array it will not be 
 		 * returned. Leave set to null or empty array if you don't want to filter.
 		 */
 		protected $opa_filters = array(); 
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
			if ($this->ops_search_class) { require_once(__CA_LIB_DIR__."/ca/Search/".$this->ops_search_class.".php"); }
			require_once(__CA_MODELS_DIR__."/".$this->ops_table_name.".php");
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			$this->opo_item_instance = new $this->ops_table_name();
 		}
 		# -------------------------------------------------------
 		# AJAX handlers
 		# -------------------------------------------------------
		public function Get($pa_additional_query_params=null, $pa_options=null) {
			if (!$this->ops_search_class) { return null; }
			$ps_query = $this->request->getParameter('q', pString);
			$pb_exact = $this->request->getParameter('exact', pInteger);
			$ps_type = $this->request->getParameter('type', pString);
			if (!($pn_limit = $this->request->getParameter('limit', pInteger))) { $pn_limit = 100; }
			$va_items = array();
			if (($vn_str_len = unicode_strlen($ps_query)) > 0) {
				if ($vn_str_len < 3) { $pb_exact = true; }		// force short strings to be an exact match (using a very short string as a stem would perform badly and return too many matches in most cases)
				
				$o_search = new $this->ops_search_class();
				
				// Get type_ids
				$vs_type_query = '';
				$va_ids = array();
				if ($ps_type) {
					$t_list = new ca_lists();
					if ($vn_type_id = $t_list->getItemIDFromList($this->opo_item_instance->getTypeListCode(), $ps_type)) {
						$t_list_item = new ca_list_items();
						$va_ids = $t_list_item->getHierarchyChildren($vn_type_id, array('idsOnly' => true));
						$va_ids[] = $vn_type_id;
					}
				} else {
					$va_ids = null;
				}
			
				// add any additional search elements
				$vs_additional_query_params = '';
				if (is_array($pa_additional_query_params) && sizeof($pa_additional_query_params)) {
					$vs_additional_query_params = ' AND ('.join(' AND ', $pa_additional_query_params).')';
				}
				
				// do search
				$qr_res = $o_search->search('('.$ps_query.(intval($pb_exact) ? '' : '*').')'.$vs_type_query.$vs_additional_query_params, array('search_source' => 'Lookup', 'no_cache' => true));
		
				$qr_res->setOption('prefetch', $pn_limit);
				$qr_res->setOption('dontPrefetchAttributes', true);
				
				if (!method_exists($this->opo_item_instance, "getLabelTableName")) {
					$vs_label_table_name = $vs_label_display_field_name = null;
				} else {
					$vs_label_table_name = $this->opo_item_instance->getLabelTableName();
					$vs_label_display_field_name = $this->opo_item_instance->getLabelDisplayField();
				}
				$vs_pk = $this->opo_item_instance->primaryKey();
	
				$vs_hier_parent_id_fld 		= $this->opo_item_instance->getProperty('HIERARCHY_PARENT_ID_FLD');
				$vs_hier_fld 						= $this->opo_item_instance->getProperty('HIERARCHY_ID_FLD');
				
				$va_parent_ids = array();
				$vs_type_fld_name = (method_exists($this->opo_item_instance, "getTypeFieldName") ? $this->opo_item_instance->getTypeFieldName() : null);
				$vo_dm = Datamodel::load();
				
				if ($vs_label_table_name) {
					$t_label_instance = $vo_dm->getInstanceByTableName($vs_label_table_name, true);
					$vb_has_preferred = ($t_label_instance->hasField('is_preferred')) ? true : false;
				
					$vb_return_vocabulary_only = (isset($pa_options['returnVocabularyOnly']) && $pa_options['returnVocabularyOnly']) ? true : false;
					$vb_is_hierarchical = $this->opo_item_instance->isHierarchical();
				}
				
				$vs_display_format = $this->request->config->get($this->ops_table_name.'_lookup_settings');
					
				if (!preg_match_all('!\^{1}([A-Za-z0-9\._]+)!', $vs_display_format, $va_matches)) {
					$vs_display_format = '^'.$this->ops_table_name.'.preferred_labels';
					$va_bundles = array($this->ops_table_name.'.preferred_labels');
				} else {
					$va_bundles = $va_matches[1];
				}
				$va_values = array();
				
				$vn_c = 0;
				while($qr_res->nextHit()) {
					if (is_array($this->opa_filters) && sizeof($this->opa_filters)) {
						foreach($this->opa_filters as $vs_filter_on_field => $va_filter_values) {
							if (!in_array($qr_res->get($vs_filter_on_field), $va_filter_values)) { continue(2); }
						}
					}
					if (is_array($va_ids) && $vs_type_fld_name && !in_array($qr_res->get($this->ops_table_name.'.'.$vs_type_fld_name), $va_ids)) { continue; }	// skip items without a type we want
					if ($vb_return_vocabulary_only && ($qr_res->get('ca_lists.use_as_vocabulary') != 1)) { continue; }
					
					$vn_id = $qr_res->get($this->ops_table_name.'.'.$vs_pk); 
					
					foreach($va_bundles as $vn_i => $vs_bundle_name) {
						$va_values[$vn_id][$vs_bundle_name] = $qr_res->get($vs_bundle_name);
					}
					
					if ($vb_is_hierarchical) {
						if ($vn_parent_id = $qr_res->get($this->ops_table_name.'.'.$vs_hier_parent_id_fld)) {
							$va_parent_ids[] = $va_values[$vn_id][$this->ops_table_name.'.'.$vs_hier_parent_id_fld] = $vn_parent_id;
						}
						
						if ($vs_hier_fld) {
							$va_values[$vn_id][$this->ops_table_name.'.'.$vs_hier_fld] = $qr_res->get($this->ops_table_name.'.'.$vs_hier_fld);
						}
					}
					$vn_c++;
					if (($pn_limit > 0) && ($vn_c >= $pn_limit)) { break; }
				}
				
				if ($vb_is_hierarchical) { 
					$va_parent_labels = $this->opo_item_instance->getPreferredDisplayLabelsForIDs($va_parent_ids);
					$va_hiers = (method_exists($this->opo_item_instance, "getHierarchyList")) ? $this->opo_item_instance->getHierarchyList() : array();
					
					foreach($va_values as $vn_id => $va_value_list) 	{
						if ($vs_hier_fld && isset($va_hiers[$vn_hier_id = $va_values[$vn_id][$this->ops_table_name.'.'.$vs_hier_fld]])) {
							$va_values[$vn_id]['_hierarchy'] = preg_replace("![\r\n\t]+!", " ", $va_hiers[$vn_hier_id]['name_plural']);
						}
						if ($vs_parent_label = $va_parent_labels[$va_values[$vn_id][$this->ops_table_name.'.'.$vs_hier_parent_id_fld]]) {
							$va_values[$vn_id]['_parent'] = preg_replace("![\r\n\t]+!", " ", $vs_parent_label);
						}
					}
				}
				
				//
				//
				//
				foreach($va_values as $vn_id => $va_value_list) 	{
					// create display string;
					$vs_display_value = $vs_display_format;
					$va_tmp = array();
					foreach($va_value_list as $vs_bundle_name => $vs_value) {
						if ($vs_display_format) {
							$vs_display_value = str_replace("^{$vs_bundle_name}", $vs_value, $vs_display_value);
						} else {
							$vs_display_value .= $vs_value.' ';
						}
					}
					$va_items[$vn_id] = array_merge(
						$va_value_list,
						array('_display' => trim($vs_display_value))
					);
				}
			
			}
			$this->view->setVar(str_replace(' ', '_', $this->ops_name_singular).'_list', $va_items);
 			return $this->render(str_replace(' ', '_', 'ajax_'.$this->ops_name_singular.'_list_html.php'));
		}
 		# -------------------------------------------------------
 		/**
 		 * Given a item_id (request parameter 'id') returns a list of direct children for use in the hierarchy browser
 		 * Returned data is JSON format
 		 */
 		public function GetHierarchyLevel() {
			$t_item = $this->opo_item_instance;
			if (!$t_item->isHierarchical()) { return; }
			
			$va_items_for_locale = array();
 			if ((!($pn_id = $this->request->getParameter('id', pInteger))) && method_exists($t_item, "getHierarchyList")) { 
 				$pn_id = $this->request->getParameter('root_item_id', pInteger);
 				$t_item->load($pn_id);
 				// no id so by default return list of available hierarchies
 				$va_items_for_locale = $t_item->getHierarchyList();
 			} else {
				if ($t_item->load($pn_id)) {		// id is the id of the parent for the level we're going to return
				
					$vs_label_table_name = $this->opo_item_instance->getLabelTableName();
					$vs_label_display_field_name = $this->opo_item_instance->getLabelDisplayField();
					$vs_pk = $this->opo_item_instance->primaryKey();
					
					$va_additional_wheres = array();
					$t_label_instance = $this->opo_item_instance->getLabelTableInstance();
					if ($t_label_instance && $t_label_instance->hasField('is_preferred')) {
						$va_additional_wheres[] = "(({$vs_label_table_name}.is_preferred = 1) OR ({$vs_label_table_name}.is_preferred IS NULL))";
					}
					
					$qr_children = $t_item->getHierarchyChildrenAsQuery(
										$t_item->getPrimaryKey(), 
										array(
											'additionalTableToJoin' => $vs_label_table_name,
											'additionalTableJoinType' => 'LEFT',
											'additionalTableSelectFields' => array($vs_label_display_field_name, 'locale_id'),
											'additionalTableWheres' => $va_additional_wheres,
											'returnChildCounts' => true
										)
					);
					
					$va_items = array();
					while($qr_children->nextRow()) {
						$va_tmp = $qr_children->getRow();
						
						if (!$va_tmp[$vs_label_display_field_name]) { $va_tmp[$vs_label_display_field_name] = $va_tmp['idno']; }
						if (!$va_tmp[$vs_label_display_field_name]) { $va_tmp[$vs_label_display_field_name] = '???'; }
						$va_tmp['name'] = $va_tmp[$vs_label_display_field_name];
						
						// Child count is only valid if has_children is not null
						$va_tmp['children'] = $qr_children->get('has_children') ? $qr_children->get('child_count') : 0;
						$va_items[$qr_children->get($this->ops_table_name.'.'.$vs_pk)][$qr_children->get($this->ops_table_name.'.'.'locale_id')] = $va_tmp;
					}
					
					$va_items_for_locale = caExtractValuesByUserLocale($va_items);
					
					$va_sorted_items = array();
					foreach($va_items_for_locale as $vn_id => $va_node) {
						$va_sorted_items[($vs_key = preg_replace('![^A-Za-z0-9]!', '_', $va_node['name']).'_'.$vn_id) ? $vs_key : '000'] = $va_node;
					}
					ksort($va_sorted_items);
					$va_items_for_locale = $va_sorted_items;
				}
 			}
 			
 			if (!$this->request->getParameter('init', pInteger)) {
 				// only set remember "last viewed" if the load is done interactively
 				// if the GetHierarchyLevel() call is part of the initialization of the hierarchy browser
 				// then all levels are loaded, sometimes out-of-order; if we record these initialization loads
 				// as the 'last viewed' we can end up losing the true 'last viewed' value
 				//
 				// ... so the hierbrowser passes an extra 'init' parameters set to 1 if the GetHierarchyLevel() call
 				// is part of a browser initialization
 				$this->request->session->setVar($this->ops_table_name.'_browse_last_id', $pn_id);
 			}
 			
 			$va_items_for_locale['_primaryKey'] = $t_item->primaryKey();	// pass the name of the primary key so the hierbrowser knows where to look for item_id's
 			
 			$this->view->setVar($this->ops_name_singular.'_list', $va_items_for_locale);
 			
 			return $this->render($this->ops_name_singular.'_hierarchy_level_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Given a item_id (request parameter 'id') returns a list of ancestors for use in the hierarchy browser
 		 * Returned data is JSON format
 		 */
 		public function GetHierarchyAncestorList() {
 			$pn_id = $this->request->getParameter('id', pInteger);
 			$t_item = new $this->ops_table_name($pn_id);
 			
 			$va_ancestors = array();
 			if ($t_item->getPrimaryKey()) { 
 				$va_ancestors = array_reverse($t_item->getHierarchyAncestors(null, array('includeSelf' => true, 'idsOnly' => true)));
 			}
 			$this->view->setVar('ancestors', $va_ancestors);
 			return $this->render($this->ops_name_singular.'_hierarchy_ancestors_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
		public function IDNo() {
			$va_ids = array();
			if ($vs_idno_field = $this->opo_item_instance->getProperty('ID_NUMBERING_ID_FIELD')) {
				$pn_id =  $this->request->getParameter('id', pInteger);
				
				if ($vs_idno_context_field = $this->opo_item_instance->getProperty('ID_NUMBERING_CONTEXT_FIELD')) {		// want to set context before doing identifier lookup, if the table supports contexts (ca_list_items and ca_place do, others don't)
					if($pn_context_id =  $this->request->getParameter('_context_id', pInteger)) {
						$this->opo_item_instance->load(array($vs_idno_context_field => $pn_context_id));
					} else {
						$this->opo_item_instance->load($pn_id);
					}
				}
				if ($ps_idno = $this->request->getParameter('n', pString)) {
					$va_ids = $this->opo_item_instance->checkForDupeAdminIdnos($ps_idno, false, $pn_id);
				}
			}
			$this->view->setVar('id_list', $va_ids);
			return $this->render('idno_json.php');
		}
		# -------------------------------------------------------
 		/**
 		 * Checks value of instrinsic field and return list of primary keys that use the specified value
 		 * Can be used to determine if a value that needs to be unique is actually unique.
 		 */
		public function Intrinsic() {
			$pn_table_num 	=  $this->request->getParameter('table_num', pInteger);
			$ps_field 				=  $this->request->getParameter('field', pString);
			$ps_val 				=  $this->request->getParameter('n', pString);
			$pn_id 					=  $this->request->getParameter('id', pInteger);
			$pa_within_fields	=  $this->request->getParameter('withinFields', pArray); 
			
			$vo_dm = Datamodel::load();
			if (!($t_instance = $vo_dm->getInstanceByTableNum($pn_table_num, true))) {
				return null;	// invalid table number
			}
			
			if (!$t_instance->hasField($ps_field)) {
				return null;	// invalid field
			}
			
			$o_db = new Db();
			$vs_pk = $t_instance->primaryKey();
			
			
			// If "unique within" fields are specified then we limit our query to values that have those fields
			// set similarly to the row we're checking.
			$va_unique_within = $t_instance->getFieldInfo($ps_field, 'UNIQUE_WITHIN');
			
			$va_extra_wheres = array();
			$vs_extra_wheres = '';
			$va_params = array((string)$ps_val, (int)$pn_id);
			if (sizeof($va_unique_within)) {
				foreach($va_unique_within as $vs_within_field) {
					$va_extra_wheres[] = "({$vs_within_field} = ?)";
					$va_params[] = $pa_within_fields[$vs_within_field];
				}
				$vs_extra_wheres = ' AND '.join(' AND ', $va_extra_wheres);
			}
		
			$qr_res = $o_db->query("
				SELECT {$vs_pk}
				FROM ".$t_instance->tableName()."
				WHERE
					({$ps_field} = ?) AND ({$vs_pk} <> ?)
					{$vs_extra_wheres}
			", $va_params);
			
			$va_ids = array();
			while($qr_res->nextRow()) {
				$va_ids[] = (int)$qr_res->get($vs_pk);
			}
			
			$this->view->setVar('id_list', $va_ids);
			return $this->render('intrinsic_json.php');
		}
 		# -------------------------------------------------------
 	}