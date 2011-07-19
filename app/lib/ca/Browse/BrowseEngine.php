<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Browse/BrowseEngine.php : Base class for browse interfaces
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
 * @subpackage Browse
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
  
 	require_once(__CA_LIB_DIR__.'/core/BaseObject.php');
 	require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
 	require_once(__CA_LIB_DIR__.'/core/Db.php');
 	require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 	require_once(__CA_LIB_DIR__.'/ca/Browse/BrowseResult.php');
 	require_once(__CA_LIB_DIR__.'/ca/Browse/BrowseCache.php');
 	require_once(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser.php');
 	require_once(__CA_LIB_DIR__.'/ca/Search/ObjectSearch.php');
 	require_once(__CA_LIB_DIR__.'/ca/Search/ObjectLotSearch.php');
 	require_once(__CA_LIB_DIR__.'/ca/Search/EntitySearch.php');
 	require_once(__CA_LIB_DIR__.'/ca/Search/PlaceSearch.php');
 	require_once(__CA_LIB_DIR__.'/ca/Search/OccurrenceSearch.php');
 	require_once(__CA_LIB_DIR__.'/ca/Search/CollectionSearch.php');
 	require_once(__CA_LIB_DIR__.'/ca/Search/StorageLocationSearch.php');
 	require_once(__CA_LIB_DIR__.'/ca/Search/LoanSearch.php');
 	require_once(__CA_LIB_DIR__.'/ca/Search/MovementSearch.php');
 	require_once(__CA_LIB_DIR__.'/ca/Search/ListSearch.php');
 	require_once(__CA_LIB_DIR__.'/ca/Search/ListItemSearch.php');
 
	class BrowseEngine extends BaseObject {
		# ------------------------------------------------------
		# Properties
		# ------------------------------------------------------
		private $opn_browse_table_num;
		private $ops_browse_table_name;
		private $opo_ca_browse_cache;
		
		/**
		 * @var subject type_id to limit browsing to (eg. only browse ca_objects with type_id = 10)
		 */
		private $opa_browse_type_ids = null;	
		
		private $opo_datamodel;
		private $opo_db;
		
		private $opo_config;
		private $opo_ca_browse_config;
		private $opa_browse_settings;
		
		private $opa_result_filters;
		
		private $opb_criteria_have_changed = false;
		# ------------------------------------------------------
		static $s_type_id_cache = array();
		# ------------------------------------------------------
		/**
		 *
		 */
		public function __construct($pm_subject_table_name_or_num, $pn_browse_id=null, $ps_browse_context='') {
			$this->opo_datamodel = Datamodel::load();
			$this->opo_db = new Db();
			
			$this->opa_result_filters = array();
			
			if (is_numeric($pm_subject_table_name_or_num)) {
				$this->opn_browse_table_num = intval($pm_subject_table_name_or_num);
				$this->ops_browse_table_name = $this->opo_datamodel->getTableName($this->opn_browse_table_num);
			} else {
				$this->opn_browse_table_num = $this->opo_datamodel->getTableNum($pm_subject_table_name_or_num);
				$this->ops_browse_table_name = $pm_subject_table_name_or_num;
			}
			
			$this->opo_config = new Configuration();
			$this->opo_ca_browse_config = new Configuration($this->opo_config->get('browse_config'));
			$this->opa_browse_settings = $this->opo_ca_browse_config->getAssoc($this->ops_browse_table_name);
			
			// Add "virtual" search facet - allows one to seed a browse with a search
			$this->opa_browse_settings['facets']['_search'] = array(
				'label_singular' => _t('Search'),
				'label_plural' => _t('Searches')
			);
			$this->_processBrowseSettings();
			
			$this->opo_ca_browse_cache = new BrowseCache();
			if ($pn_browse_id) {
				$this->opo_ca_browse_cache->load($pn_browse_id);
			} else {
				$this->opo_ca_browse_cache->setParameter('table_num', $this->opn_browse_table_num);
				$this->setContext($ps_browse_context);
			}
		}
		# ------------------------------------------------------
		/**
		 * Forces reload of the browse instance (ie. a cached browse) from the database
		 *
		 * @param int $pn_browse_id The id of the browse to release
		 * @return bool true if reload succeeded, false if browse_id was invalid
		 */
		public function reload($pn_browse_id) {
			$this->opo_ca_browse_cache = new BrowseCache();
			
			return (bool)$this->opo_ca_browse_cache->load($pn_browse_id);
		}
		# ------------------------------------------------------
		/**
		 * Rewrite browse config settings as needed before starting actual processing of browse
		 */
		private function _processBrowseSettings() {
			$va_revised_facets = array();
			foreach($this->opa_browse_settings['facets'] as $vs_facet_name => $va_facet_info) {
			
				// generate_facets_for_types config directive triggers auto-generation of facet config for each type of an authority item
				// it's typically employed to provide browsing of occurrences where the various types are unrelated
				// you can also use this on other authorities to provide a finer-grained browse without having to know the type hierarchy ahead of time
				if (($va_facet_info['type'] === 'authority') && isset($va_facet_info['generate_facets_for_types']) && $va_facet_info['generate_facets_for_types']) {
					// get types for authority
					$t_table = $this->opo_datamodel->getInstanceByTableName($va_facet_info['table'], true);
					
					$va_type_list = $t_table->getTypeList();
					
					// auto-generate facets
					foreach($va_type_list as $vn_type_id => $va_type_info) {
						if ($va_type_info['is_enabled']) {
							$va_facet_info = array_merge($va_facet_info, array(
								'label_singular' => $va_type_info['name_singular'],
								'label_singular_with_indefinite_article' => _t('a').' '.$va_type_info['name_singular'],
								'label_plural' => $va_type_info['name_plural'],
								'restrict_to_types' => array($va_type_info['item_id'])
							));
							$va_revised_facets[$vs_facet_name.'_'.$vn_type_id] = $va_facet_info;
						}
					}
				} else {
					$va_revised_facets[$vs_facet_name] = $va_facet_info;
				}
			}
			
			
			// rewrite single_value settings for attribute and fieldList facets
			foreach($va_revised_facets as $vs_facet => $va_facet_info) {
				if (!((isset($va_facet_info['single_value'])) && strlen($va_facet_info['single_value']))) { continue; }
				
				switch($va_facet_info['type']) {
					case 'attribute':
						$t_element = new ca_metadata_elements();
						if ($t_element->load(array('element_code' => $va_facet_info['element_code']))) {
							if (($t_element->get('datatype') == 3) && ($vn_list_id = $t_element->get('list_id'))) { // 3 = list
								if ($vn_item_id = caGetListItemID($vn_list_id, $va_facet_info['single_value'])) {
									$va_revised_facets[$vs_facet]['single_value'] = $vn_item_id;
								}
							}
						}
						break;
					case 'fieldList':
						$t_instance = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true);
						if ($vn_item_id = caGetListItemID($t_instance->getFieldInfo($va_facet_info['field'], 'LIST_CODE'), $va_facet_info['single_value'])) {
							$va_revised_facets[$vs_facet]['single_value'] = $vn_item_id;
						}
						break;
				}
			}
			
			$this->opa_browse_settings['facets'] = $va_revised_facets;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function getBrowseID() {
			return $this->opo_ca_browse_cache->getCacheKey();
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function getSubject() {
			return $this->opn_browse_table_num;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function getSubjectInstance() {
			return $this->opo_datamodel->getInstanceByTableNum($this->opn_browse_table_num, true);
		}
		# ------------------------------------------------------
		/**
		 * Sets the current browse context. 
		 * Separate cache namespaces are maintained for each browse context; this means that
		 * if you do the same browse in different contexts each will be cached separately. This 
		 * is handy when you have multiple interfaces (say the cataloguing back-end and a public front-end)
		 * using the same browse engine and underlying cache tables
		 */
		public function setContext($ps_browse_context) {
			$va_params = $this->opo_ca_browse_cache->setParameter('context', $ps_browse_context);
			
			return true;
		}
		# ------------------------------------------------------
		/**
		 * Returns currently set browse context
		 */
		public function getContext() {
			return ($vs_context = $this->opo_ca_browse_cache->getParameter('context')) ? $vs_context : '';
		}
		# ------------------------------------------------------
		# Add/remove browse criteria
		# ------------------------------------------------------
		/**
		 * @param $ps_facet_name - name of facet for which to add criteria
		 * @param $pa_row_ids - one or more facet values to browse on
		 *
		 * @return boolean - true on success, null on error
		 */
		public function addCriteria($ps_facet_name, $pa_row_ids) {
			if (is_null($pa_row_ids)) { return null;}
			if ($ps_facet_name !== '_search') {
				if (!($va_facet_info = $this->getInfoForFacet($ps_facet_name))) { return null; }
				if (!$this->isValidFacetName($ps_facet_name)) { return null; }
			}
			
			$va_criteria = $this->opo_ca_browse_cache->getParameter('criteria');
			if (!is_array($pa_row_ids)) { $pa_row_ids = array($pa_row_ids); }
			foreach($pa_row_ids as $vn_row_id) {
				$va_criteria[$ps_facet_name][urldecode($vn_row_id)] = true;
			}
			$this->opo_ca_browse_cache->setParameter('criteria', $va_criteria);
			$this->opo_ca_browse_cache->setParameter('sort', null);
			$this->opo_ca_browse_cache->setParameter('facet_html', null);
			
			$this->opb_criteria_have_changed = true;
			return true;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function removeCriteria($ps_facet_name, $pa_row_ids) {
			if (is_null($pa_row_ids)) { return null;}
			if (!($va_facet_info = $this->getInfoForFacet($ps_facet_name))) { return null; }
			if (!$this->isValidFacetName($ps_facet_name)) { return null; }
			
			$va_criteria = $this->opo_ca_browse_cache->getParameter('criteria');
			if (!is_array($pa_row_ids)) { $pa_row_ids = array($pa_row_ids); }
			
			foreach($pa_row_ids as $vn_row_id) {
				unset($va_criteria[$ps_facet_name][urldecode($vn_row_id)]);
				if(is_array($va_criteria[$ps_facet_name]) && !sizeof($va_criteria[$ps_facet_name])) {
					unset($va_criteria[$ps_facet_name]);
				}
			}
			$this->opo_ca_browse_cache->setParameter('criteria', $va_criteria);
			$this->opo_ca_browse_cache->setParameter('sort', null);
			$this->opo_ca_browse_cache->setParameter('facet_html', null);
			
			$this->opb_criteria_have_changed = true;
			return true;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function criteriaHaveChanged() {
			return $this->opb_criteria_have_changed;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function numCriteria() {
			$va_criteria = $this->opo_ca_browse_cache->getParameter('criteria');
			if (isset($va_criteria) && is_array($va_criteria)) {
				$vn_c = 0;
				foreach($va_criteria as $vn_table_num => $va_criteria_list) {
					$vn_c += sizeof($va_criteria_list);
				}
				return $vn_c;
			}
			return 0;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function removeAllCriteria($ps_facet_name=null) {
			if ($ps_facet_name && !$this->isValidFacetName($ps_facet_name)) { return null; }
			
			$va_criteria = $this->opo_ca_browse_cache->getParameter('criteria');
			if($ps_facet_name) {
				$va_criteria[$ps_facet_name] = array();
			} else {
				$va_criteria = array();
			}
			
			$this->opo_ca_browse_cache->setParameter('criteria', $va_criteria);
			$this->opo_ca_browse_cache->setParameter('facet_html', null);
			
			$this->opb_criteria_have_changed = true;
			
			return true;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function getCriteria($ps_facet_name=null) {
			if ($ps_facet_name && (!$this->isValidFacetName($ps_facet_name))) { return null; }
			
			$va_criteria = $this->opo_ca_browse_cache->getParameter('criteria');
			
			if($ps_facet_name) {
				return isset($va_criteria[$ps_facet_name]) ? $va_criteria[$ps_facet_name] : null;
			}
			return isset($va_criteria) ? $va_criteria : null;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function getCriteriaWithLabels($ps_facet_name=null) {
			if ($ps_facet_name && (!$this->isValidFacetName($ps_facet_name))) { return null; }
			
			$va_criteria = $this->opo_ca_browse_cache->getParameter('criteria');
			
			$va_criteria_with_labels = array();
			if($ps_facet_name) {
				$va_criteria = isset($va_criteria[$ps_facet_name]) ? $va_criteria[$ps_facet_name] : null;
				
				foreach($va_criteria as $vm_criterion => $vn_tmp) {
					$va_criteria_with_labels[$vm_criterion] = $this->getCriterionLabel($ps_facet_name, $vm_criterion);
				}
			} else {
				if (is_array($va_criteria)) {
					foreach($va_criteria as $vs_facet_name => $va_criteria_by_facet) {
						foreach($va_criteria_by_facet as $vm_criterion => $vn_tmp) {
							$va_criteria_with_labels[$vs_facet_name][$vm_criterion] = $this->getCriterionLabel($vs_facet_name, $vm_criterion);
						}
					}
				}
			}
			return $va_criteria_with_labels;	
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function getCriterionLabel($ps_facet_name, $pn_row_id) {
			if (!($va_facet_info = $this->getInfoForFacet($ps_facet_name))) { return null; }
			
			switch($va_facet_info['type']) {
				# -----------------------------------------------------
				case 'has':
					$vs_yes_text = (isset($va_facet_info['label_yes']) && $va_facet_info['label_yes']) ? $va_facet_info['label_yes'] : _t('Yes');
					$vs_no_text = (isset($va_facet_info['label_no']) && $va_facet_info['label_no']) ? $va_facet_info['label_no'] : _t('No');
					return ((bool)$pn_row_id) ? $vs_yes_text : $vs_no_text;
					break;
				# -----------------------------------------------------
				case 'label':
					if (!($t_table = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true))) { break; }
					if (!($t_label = $t_table->getLabelTableInstance())) { break; }
					if (!$t_label->load($pn_row_id)) { return '???'; }
					
					return $t_label->get($t_table->getLabelDisplayField());
					break;
				# -----------------------------------------------------
				case 'authority':
					if (!($t_table = $this->opo_datamodel->getInstanceByTableName($va_facet_info['table'], true))) { break; }
					if (!$t_table->load($pn_row_id)) { return '???'; }
					
					return $t_table->getLabelForDisplay();
					break;
				# -----------------------------------------------------
				case 'attribute':
					$t_element = new ca_metadata_elements();
					if (!$t_element->load(array('element_code' => $va_facet_info['element_code']))) {
						return urldecode($pn_row_id);
					}
					
					$vn_element_id = $t_element->getPrimaryKey();
					switch($t_element->get('datatype')) {
						case 3: // list
							$t_list = new ca_lists();
							return $t_list->getItemFromListForDisplayByItemID($t_element->get('list_id'), $pn_row_id , true);
							break;
						default:
							return urldecode($pn_row_id);
							break;
					}
					
					break;
				# -----------------------------------------------------
				case 'normalizedDates':
					return urldecode($pn_row_id);
					break;
				# -----------------------------------------------------
				case 'fieldList':
					if (!($t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true))) { break; }
					$vs_field_name = $va_facet_info['field'];
					$va_field_info = $t_item->getFieldInfo($vs_field_name);
					
					$t_list = new ca_lists();
					
					if ($vs_list_name = $va_field_info['LIST_CODE']) {
						$t_list_item = new ca_list_items($pn_row_id);
						if ($vs_tmp = $t_list_item->getLabelForDisplay()) {
							return $vs_tmp;
						}
						return '???';
					} else {
						if ($vs_list_name = $va_field_info['LIST']) {
							if (is_array($va_list_items = $t_list->getItemsForList($vs_list_name))) {
								$va_list_items = caExtractValuesByUserLocale($va_list_items);
								foreach($va_list_items as $vn_id => $va_list_item) {
									if ($va_list_item['item_value'] == $pn_row_id) {
										return $va_list_item['name_plural'];
									}
								}
							}
						}
					}
						
					if(isset($va_field_info['BOUNDS_CHOICE_LIST'])) {
						$va_choice_list = $va_field_info['BOUNDS_CHOICE_LIST'];
						if (is_array($va_choice_list)) {
							foreach($va_choice_list as $vs_val => $vn_id) {
								if ($vn_id == $pn_row_id) {
									return $vs_val;
								}
							}
						}
					}
					return '???';
					break;
				# -----------------------------------------------------
				default:
					if ($ps_facet_name == '_search') { return $pn_row_id; }
					return 'Invalid type';
					break;
				# -----------------------------------------------------
			}
		}
		# ------------------------------------------------------
		# Facets
		# ------------------------------------------------------
		/**
		 * Returns list of all facets configured for this for browse subject
		 */
		public function getInfoForFacets() {
			return $this->opa_browse_settings['facets'];	
		}
		# ------------------------------------------------------
		/**
		 * Return info for specified facet, or null if facet is not valid
		 */
		public function getInfoForFacet($ps_facet_name) {
			if (!$this->isValidFacetName($ps_facet_name)) { return null; }
			$va_facets = $this->opa_browse_settings['facets'];	
			return $va_facets[$ps_facet_name];
		}
		# ------------------------------------------------------
		/**
		 * Returns true if facet exists, false if not
		 */
		public function isValidFacetName($ps_facet_name) {
			$va_facets = $this->getInfoForFacets();
			return (isset($va_facets[$ps_facet_name])) ? true : false;
		}
		# ------------------------------------------------------
		/**
		 * Returns list of all valid facet names
		 */
		public function getFacetList() {
			if (!is_array($this->opa_browse_settings)) { return null; }
			
			// Facets can be restricted such that they are applicable only to certain types when browse type restrictions are in effect.
			// These restrictions are distinct from per-facet 'restrict_to_type' and 'restrict_to_relationship_types' settings, which affect
			// what items and *included* in the browse. 'restrict_to_type' restricts a browse to specific types of items (eg. only entities of type "individual" are returned in the facet);
			// 'restrict_to_relationship_types' restricts authority facets to items related to the browse subject by specific relationship types. By contrast, the
			// 'type_restrictions' setting indicates that a facet is only valid for specific types when a browse is limited to specific types (eg. there is a browse type
			// restriction in effect. Browse type restrictions apply to the browse result, not the facet content (eg. on an object browse, a type restriction of "documents" would limit 
			// the browse to only consider and return object of that type).
			$va_type_restrictions = $this->getTypeRestrictionList();
			
			$t_list = new ca_lists();
			$t_subject = $this->opo_datamodel->getInstanceByTableNum($this->opn_browse_table_num, true);
			$vs_type_list_code = $t_subject->getTypeListCode();
			
			$va_criteria_facets = is_array($va_tmp = $this->getCriteria()) ? array_keys($this->getCriteria()) : array(); 
			
			// 
			if (is_array($va_type_restrictions) && sizeof($va_type_restrictions)) {
				$va_facets = array();
				foreach($this->opa_browse_settings['facets'] as $vs_facet_name => $va_facet_info) {
					//
					// enforce "requires" setting, which allows one to specify that a given facet should old appear if any one
					// of the specified "required" facets is present in the criteria
					//
					$vb_facet_is_meets_requirements = true;
					if (isset($va_facet_info['requires']) && is_array($va_facet_info['requires'])) {
						$vb_facet_is_meets_requirements = false;
						foreach($va_facet_info['requires'] as $vs_req_facet) {
							if (in_array($vs_req_facet, $va_criteria_facets)) {
								$vb_facet_is_meets_requirements = true;
								break; 
							}
						}
					}
					if ($vb_facet_is_meets_requirements) {
						if (isset($va_facet_info['type_restrictions']) && is_array($va_facet_restrictions = $va_facet_info['type_restrictions']) && sizeof($va_facet_restrictions)) {
							foreach($va_facet_restrictions as $vs_code) {
								if ($va_item = $t_list->getItemFromList($vs_type_list_code, $vs_code)) {
									if (in_array($va_item['item_id'], $va_type_restrictions)) {
										$va_facets[] = $vs_facet_name;
										break;
									}
								}
							}
						} else {
							$va_facets[] = $vs_facet_name;
						}
					}
				}
				return $va_facets;
			} else {
				//
				// enforce "requires" setting, which allows one to specify that a given facet should only appear if any one
				// of the specified "required" facets is present in the criteria
				//
				$va_facets = array();
				
				foreach($this->opa_browse_settings['facets'] as $vs_facet_name => $va_facet_info) {
					if (isset($va_facet_info['requires']) && is_array($va_facet_info['requires'])) {
						foreach($va_facet_info['requires'] as $vs_req_facet) {
							if (in_array($vs_req_facet, $va_criteria_facets)) {
								$va_facets[] = $vs_facet_name;
								continue; 
							}
						}
					} else {
						$va_facets[] = $vs_facet_name;
					}
				}
				return $va_facets;
			}
		}
		# ------------------------------------------------------
		/**
		 * Returns list of all facets that currently have content (ie. that can refine the browse further)
		 * with full facet info included
		 */
		public function getInfoForAvailableFacets() {
			if (!is_array($this->opa_browse_settings)) { return null; }
			$va_facets = $this->opa_browse_settings['facets'];	
			$va_facet_with_content = $this->opo_ca_browse_cache->getFacets();
			foreach($va_facets as $vs_facet_name => $va_facet_info) {
				if (!isset($va_facet_with_content[$vs_facet_name]) || !$va_facet_with_content[$vs_facet_name]) {
					unset($va_facets[$vs_facet_name]);
				}
			}
			
			return $va_facets;
		}
		# ------------------------------------------------------
		/**
		 * Returns an HTML <select> of all facets that currently have content (ie. that can refine the browse further)
		 *
		 * Options:
		 *		select_message = Message to display as default message on <select> (default is "Browse by..." or localized equivalent)
		 *		dont_add_select_message = if true, no select_message is added to <select> (default is false)
		 *		use_singular = if true singular version of facet name is used, otherwise plural version is used
		 *		
		 */
		public function getAvailableFacetListAsHTMLSelect($ps_name, $pa_attributes=null, $pa_options=null) {
			if (!is_array($this->opa_browse_settings)) { return null; }
			if (!is_array($pa_options)) { $pa_options = array(); }
			$va_facets = $this->getInfoForAvailableFacets();
			
			$va_options = array();
			
			$vs_select_message = (isset($pa_options['select_message'])) ? $pa_options['select_message'] : _t('Browse by...');
			if (!isset($pa_options['dont_add_select_message']) || !$pa_options['dont_add_select_message']) {
				$va_options[$vs_select_message] = '';
			}
			
			foreach($va_facets as $vs_facet_code => $va_facet_info) {
				$va_options[(isset($pa_options['use_singular']) && $pa_options['use_singular']) ? $va_facet_info['label_singular'] : $va_facet_info['label_plural']] = $vs_facet_code;
			}
			
			return caHTMLSelect($ps_name, $va_options, $pa_attributes);
		}
		# ------------------------------------------------------
		/**
		 * Returns list of facets that will return content for the current browse table assuming no criteria
		 * It's the list of facets returned as "available" when no criteria are specific, in other words.
		 *
		 * Note that this method does NOT take into account type restrictions
		 */
		public function getFacetsWithContentList() {
			$t_browse = new BrowseEngine($this->opn_browse_table_num, null, $this->getContext());
			return $t_browse->getFacetList();
		}
		# ------------------------------------------------------
		/**
		 * Returns list of all facets that will return content for the current browse table assuming no criteria
		 * with full facet info included
		 * It's the list of facets returned as "available" when no criteria are specific, in other words.
		 */
		public function getInfoForFacetsWithContent() {
			if (!($va_facets_with_content = $this->opo_ca_browse_cache->getGlobalParameter('facets_with_content'))) {
				$t_browse = new BrowseEngine($this->opn_browse_table_num, null, $this->getContext());
				$t_browse->execute();
				
			}
			return is_array($va_facets_with_content) ? $va_facets_with_content : array();
		}
		# ------------------------------------------------------
		# Generation of browse results
		# ------------------------------------------------------
		/**
		 * Actually do the browse
		 *
		 * Options:
		 *		checkAccess = array of access values to filter facets that have an 'access' field by
		 *		no_cache = don't use cached browse results
		 */
		public function execute($pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if (!is_array($this->opa_browse_settings)) { return null; }
			
			$va_params = $this->opo_ca_browse_cache->getParameters();
			
			$vb_need_to_cache_facets = false;
			$vb_results_cached = false;
			$vb_need_to_save_in_cache = false;
			
			$vs_cache_key = $this->opo_ca_browse_cache->getCurrentCacheKey();
			
			if ($this->opo_ca_browse_cache->load($vs_cache_key)) {
			
				$vn_created_on = $this->opo_ca_browse_cache->getParameter('created_on'); //$t_new_browse->get('created_on', array('GET_DIRECT_DATE' => true));
		
				$va_criteria = $this->getCriteria();
				if ((!isset($pa_options['no_cache']) || (!$pa_options['no_cache'])) && (intval(time() - $vn_created_on) < $this->opo_ca_browse_config->get('cache_timeout'))) {
					$vb_results_cached = true;
					//print "cache hit for [$vs_cache_key]<br>";
				} else {
					$va_criteria = $this->getCriteria();
					$this->opo_ca_browse_cache->remove();
					$this->opo_ca_browse_cache->setParameter('criteria', $va_criteria);
					
					//print "cache expire for [$vs_cache_key]<br>";
					$vb_need_to_save_in_cache = true;
					$vb_need_to_cache_facets = true;
				}
			} else {
				$va_criteria = $this->getCriteria();
				//print "cache miss for [$vs_cache_key]<br>";
			}
			if (!$vb_results_cached) {
				$this->opo_ca_browse_cache->setParameter('sort', null); 
				$this->opo_ca_browse_cache->setParameter('created_on', time()); 
				$this->opo_ca_browse_cache->setParameter('table_num', $this->opn_browse_table_num); 
				$vb_need_to_cache_facets = true;
			}
			$this->opb_criteria_have_changed = false;
			
			$t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true);
			
			$va_results = array();
			
			if (is_array($va_criteria) && (sizeof($va_criteria) > 0)) {		
				if (!$vb_results_cached) {
				
					// generate results
					$this->_createTempTable('ca_browses_acc');
					$this->_createTempTable('ca_browses_tmp');	
					
					$vn_i = 0;
					foreach($va_criteria as $vs_facet_name => $va_row_ids) {
						$va_facet_info = $this->getInfoForFacet($vs_facet_name);
						$va_row_ids = array_keys($va_row_ids);
						
							switch($va_facet_info['type']) {
								# -----------------------------------------------------
								case 'has':
									$vs_rel_table_name = $va_facet_info['table'];
									if (!is_array($va_restrict_to_relationship_types = $va_facet_info['restrict_to_relationship_types'])) { $va_restrict_to_relationship_types = array(); }
									$va_restrict_to_relationship_types = $this->_getRelationshipTypeIDs($va_restrict_to_relationship_types, $va_facet_info['relationship_table']);
					
									$vn_table_num = $this->opo_datamodel->getTableNum($vs_rel_table_name);
									$vs_rel_table_pk = $this->opo_datamodel->getTablePrimaryKeyName($vn_table_num);
										
										switch(sizeof($va_path = array_keys($this->opo_datamodel->getPath($this->ops_browse_table_name, $vs_rel_table_name)))) {
											case 3:
												$t_item_rel = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
												$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[2], true);
												$vs_key = 'relation_id';
												break;
											case 2:
												$t_item_rel = null;
												$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
												$vs_key = $t_rel_item->primaryKey();
												break;
											default:
												// bad related table
												return null;
												break;
										}
										
										$vs_cur_table = array_shift($va_path);
										$va_joins = array();
										
										$vn_state = array_pop($va_row_ids);
										
										foreach($va_path as $vs_join_table) {
											$va_rel_info = $this->opo_datamodel->getRelationships($vs_cur_table, $vs_join_table);
											$va_joins[] = ($vn_state ? 'INNER' : 'LEFT').' JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
											$vs_cur_table = $vs_join_table;
										}
										
										$vs_join_sql = join("\n", $va_joins);
										
										$va_wheres = array();
										if ((sizeof($va_restrict_to_relationship_types) > 0) && is_object($t_item_rel)) {
											$va_wheres[] = "(".$t_item_rel->tableName().".type_id IN (".join(',', $va_restrict_to_relationship_types)."))";
										}
										
										
										if (!(bool)$vn_state) {
											$va_wheres[] = "(".$t_rel_item->tableName().".".$t_rel_item->primaryKey()." IS NULL)";
										}
										
										$vs_where_sql = '';
										if (sizeof($va_wheres) > 0) {
											$vs_where_sql = ' WHERE '.join(' AND ', $va_wheres);	
										}

										if ($vn_i == 0) {
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_acc
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_join_sql}
													{$vs_where_sql}
											";
											//print "$vs_sql<hr>";
											$qr_res = $this->opo_db->query($vs_sql);
										} else {
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_tmp");
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_tmp
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												INNER JOIN ca_browses_acc ON ca_browses_acc.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												{$vs_join_sql}
													{$vs_where_sql}";
											//print "$vs_sql<hr>";
											$qr_res = $this->opo_db->query($vs_sql);
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_acc");
											$qr_res = $this->opo_db->query("INSERT IGNORE INTO ca_browses_acc SELECT row_id FROM ca_browses_tmp");
										}
										$vn_i++;
									
									break;
								# -----------------------------------------------------
								case 'label':
									if (!($t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true))) { break; }
									if (!($t_label = $t_item->getLabelTableInstance())) { break; }
									
									$vs_item_pk = $t_item->primaryKey();
									$vs_label_table_name = $t_label->tableName();
									$vs_label_pk = $t_label->primaryKey();
									$vs_label_display_field = $t_item->getLabelDisplayField();
									
									
									foreach($va_row_ids as $vn_row_id) {
										if (!$t_label->load($vn_row_id)) { continue; }
										
										if ($vn_i == 0) {
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_acc
												SELECT {$vs_label_table_name}.{$vs_item_pk}
												FROM {$vs_label_table_name}
												WHERE
													{$vs_label_display_field} = ?";
											//print "$vs_sql [".intval($this->opn_browse_table_num)."]<hr>";
											$qr_res = $this->opo_db->query($vs_sql, $t_label->get($vs_label_display_field));
										} else {
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_tmp");
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_tmp
												SELECT {$vs_label_table_name}.{$vs_item_pk}
												FROM {$vs_label_table_name}
												WHERE
													{$vs_label_display_field} = ?";
											//print "$vs_sql [".intval($this->opn_browse_table_num)."]<hr>";
											$qr_res = $this->opo_db->query($vs_sql, $t_label->get($vs_label_display_field));
											
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_acc");
											$qr_res = $this->opo_db->query("INSERT IGNORE INTO ca_browses_acc SELECT row_id FROM ca_browses_tmp");
										} 
										
										$vn_i++;
									}
									break;
								# -----------------------------------------------------
								case 'attribute':
									$t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true);
									$t_element = new ca_metadata_elements();
									if (!$t_element->load(array('element_code' => $va_facet_info['element_code']))) {
										return array();
									}
									
									// TODO: check that it is a *single-value* (ie. no hierarchical ca_metadata_elements) Text or Number attribute
									// (do we support other types as well?)
									
									
									$vn_element_id = $t_element->getPrimaryKey();
									
									foreach($va_row_ids as $vn_row_id) {
										$vn_row_id = urldecode($vn_row_id);
										$vn_row_id = str_replace('&#47;', '/', $vn_row_id);
										if ($vn_i == 0) {
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_acc
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												INNER JOIN ca_attributes ON ca_attributes.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()." AND ca_attributes.table_num = ?
												INNER JOIN ca_attribute_values ON ca_attribute_values.attribute_id = ca_attributes.attribute_id
												WHERE
													(ca_attribute_values.element_id = ?) AND (ca_attribute_values.value_longtext1 = ?)";
											//print "$vs_sql [".intval($this->opn_browse_table_num)."/".$vn_element_id."/".$vn_row_id."]<hr>";
											$qr_res = $this->opo_db->query($vs_sql, intval($this->opn_browse_table_num), $vn_element_id, $vn_row_id);
										} else {
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_tmp");
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_tmp
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												INNER JOIN ca_attributes ON ca_attributes.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()." AND ca_attributes.table_num = ?
												INNER JOIN ca_attribute_values ON ca_attribute_values.attribute_id = ca_attributes.attribute_id
												INNER JOIN ca_browses_acc ON ca_browses_acc.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												WHERE
													(ca_attribute_values.element_id = ?) AND (ca_attribute_values.value_longtext1 = ?)";
											//print "$vs_sql [".intval($this->opn_browse_table_num)."/".$vn_element_id."/".$vn_row_id."]<hr>";
											$qr_res = $this->opo_db->query($vs_sql, intval($this->opn_browse_table_num), $vn_element_id, $vn_row_id);
											
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_acc");
											$qr_res = $this->opo_db->query("INSERT IGNORE INTO ca_browses_acc SELECT row_id FROM ca_browses_tmp");
										} 
										
										$vn_i++;
									}
									break;
								# -----------------------------------------------------
								case 'normalizedDates':
									$t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true);
									$t_element = new ca_metadata_elements();
									if (!$t_element->load(array('element_code' => $va_facet_info['element_code']))) {
										return array();
									}
									
									// TODO: check that it is a *single-value* (ie. no hierarchical ca_metadata_elements) DateRange attribute
									
									$vs_normalization = $va_facet_info['normalization'];
									$vn_element_id = $t_element->getPrimaryKey();
									$o_tep = new TimeExpressionParser();
									
									foreach($va_row_ids as $vn_row_id) {
										$vn_row_id = urldecode($vn_row_id);
										if (!$o_tep->parse($vn_row_id)) { continue; } // invalid date?
										
										$va_dates = $o_tep->getHistoricTimestamps();
										
										if ($vn_i == 0) {
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_acc
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												INNER JOIN ca_attributes ON ca_attributes.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()." AND ca_attributes.table_num = ?
												INNER JOIN ca_attribute_values ON ca_attribute_values.attribute_id = ca_attributes.attribute_id
												WHERE
													(ca_attribute_values.element_id = ?) AND
													
													(
														(
															(ca_attribute_values.value_decimal1 <= ?) AND
															(ca_attribute_values.value_decimal2 >= ?)
														)
														OR
														(ca_attribute_values.value_decimal1 BETWEEN ? AND ?)
														OR 
														(ca_attribute_values.value_decimal2 BETWEEN ? AND ?)
													)
											";
											
											$qr_res = $this->opo_db->query($vs_sql, intval($this->opn_browse_table_num), $vn_element_id, $va_dates['start'], $va_dates['end'], $va_dates['start'], $va_dates['end'], $va_dates['start'], $va_dates['end']);
										} else {
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_tmp");
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_tmp
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												INNER JOIN ca_attributes ON ca_attributes.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()." AND ca_attributes.table_num = ?
												INNER JOIN ca_attribute_values ON ca_attribute_values.attribute_id = ca_attributes.attribute_id
												INNER JOIN ca_browses_acc ON ca_browses_acc.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												WHERE
													(ca_attribute_values.element_id = ?) AND
													
													(
														(
															(ca_attribute_values.value_decimal1 <= ?) AND
															(ca_attribute_values.value_decimal2 >= ?)
														)
														OR
														(ca_attribute_values.value_decimal1 BETWEEN ? AND ?)
														OR 
														(ca_attribute_values.value_decimal2 BETWEEN ? AND ?)
													)
											";
											$qr_res = $this->opo_db->query($vs_sql, intval($this->opn_browse_table_num), $vn_element_id, $va_dates['start'], $va_dates['end'], $va_dates['start'], $va_dates['end'], $va_dates['start'], $va_dates['end']);
											
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_acc");
											$qr_res = $this->opo_db->query("INSERT IGNORE INTO ca_browses_acc SELECT row_id FROM ca_browses_tmp");
										} 
										
										$vn_i++;
									}
									break;
								# -----------------------------------------------------
								case 'authority':
									$vs_rel_table_name = $va_facet_info['table'];
									if (!is_array($va_restrict_to_relationship_types = $va_facet_info['restrict_to_relationship_types'])) { $va_restrict_to_relationship_types = array(); }
									$va_restrict_to_relationship_types = $this->_getRelationshipTypeIDs($va_restrict_to_relationship_types, $va_facet_info['relationship_table']);
					
									$vn_table_num = $this->opo_datamodel->getTableNum($vs_rel_table_name);
									$vs_rel_table_pk = $this->opo_datamodel->getTablePrimaryKeyName($vn_table_num);
									foreach($va_row_ids as $vn_row_id) {
										
										switch(sizeof($va_path = array_keys($this->opo_datamodel->getPath($this->ops_browse_table_name, $vs_rel_table_name)))) {
											case 3:
												$t_item_rel = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
												$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[2], true);
												$vs_key = 'relation_id';
												break;
											case 2:
												$t_item_rel = null;
												$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
												$vs_key = $t_rel_item->primaryKey();
												break;
											default:
												// bad related table
												return null;
												break;
										}
										
										$vs_cur_table = array_shift($va_path);
										$va_joins = array();
										
										foreach($va_path as $vs_join_table) {
											$va_rel_info = $this->opo_datamodel->getRelationships($vs_cur_table, $vs_join_table);
											$va_joins[] = 'INNER JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
											$vs_cur_table = $vs_join_table;
										}
										
										$vs_join_sql = join("\n", $va_joins);
										
										$va_wheres = array();
										if ((sizeof($va_restrict_to_relationship_types) > 0) && is_object($t_item_rel)) {
											$va_wheres[] = "(".$t_item_rel->tableName().".type_id IN (".join(',', $va_restrict_to_relationship_types)."))";
										}
										
										$vs_where_sql = '';
										if (sizeof($va_wheres) > 0) {
											$vs_where_sql = ' AND '.join(' AND ', $va_wheres);	
										}
										
										if ($t_rel_item->isHierarchical() && $t_rel_item->load((int)$vn_row_id)) {
											$vs_hier_left_fld = $t_rel_item->getProperty('HIERARCHY_LEFT_INDEX_FLD');
											$vs_hier_right_fld = $t_rel_item->getProperty('HIERARCHY_RIGHT_INDEX_FLD');
										
											$vs_get_item_sql = "{$vs_rel_table_name}.{$vs_hier_left_fld} >= ".$t_rel_item->get($vs_hier_left_fld). " AND {$vs_rel_table_name}.{$vs_hier_right_fld} <= ".$t_rel_item->get($vs_hier_right_fld);
											if ($vn_hier_id_fld = $t_rel_item->getProperty('HIERARCHY_ID_FLD')) {
												$vs_get_item_sql .= " AND {$vs_rel_table_name}.{$vn_hier_id_fld} = ".(int)$t_rel_item->get($vn_hier_id_fld);
											}
											$vs_get_item_sql = "({$vs_get_item_sql})";
										} else {
											$vs_get_item_sql = "({$vs_rel_table_name}.{$vs_rel_table_pk} = ".(int)$vn_row_id.")";
										}
										
										if ($vn_i == 0) {
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_acc
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_join_sql}
												WHERE
													{$vs_get_item_sql}
													{$vs_where_sql}";
											//print "$vs_sql<hr>";
											$qr_res = $this->opo_db->query($vs_sql);
										} else {
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_tmp");
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_tmp
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_join_sql}
												INNER JOIN ca_browses_acc ON ca_browses_acc.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												WHERE
													{$vs_get_item_sql}
													{$vs_where_sql}";
											//print "$vs_sql<hr>";
											$qr_res = $this->opo_db->query($vs_sql);
											
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_acc");
											$qr_res = $this->opo_db->query("INSERT IGNORE INTO ca_browses_acc SELECT row_id FROM ca_browses_tmp");
										} 
										
										$vn_i++;
									}
									
								break;
							# -----------------------------------------------------
								case 'fieldList':
									$t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true);
									$vs_field_name = $va_facet_info['field'];
									
									foreach($va_row_ids as $vn_row_id) {
										$vn_row_id = urldecode($vn_row_id);
										if ($vn_i == 0) {
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_acc
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												WHERE
													(".$this->ops_browse_table_name.".{$vs_field_name} = ?)";
											//print "$vs_sql [".intval($this->opn_browse_table_num)."/".$vn_element_id."/".$vn_row_id."]<hr>";
											$qr_res = $this->opo_db->query($vs_sql, $vn_row_id);
										} else {
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_tmp");
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_tmp
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												INNER JOIN ca_browses_acc ON ca_browses_acc.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												WHERE
													(".$this->ops_browse_table_name.".{$vs_field_name} = ?)";
											//print "$vs_sql [".intval($this->opn_browse_table_num)."/".$vn_element_id."/".$vn_row_id."]<hr>";
											$qr_res = $this->opo_db->query($vs_sql, $vn_row_id);
											
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_acc");
											$qr_res = $this->opo_db->query("INSERT IGNORE INTO ca_browses_acc SELECT row_id FROM ca_browses_tmp");
										} 
										
										$vn_i++;
									}
									break;
							# -----------------------------------------------------
							default:
								// handle "search" criteria - search engine queries that can be browsed
								if ($vs_facet_name === '_search') {
									$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_acc");
									switch($this->ops_browse_table_name) {
										case 'ca_objects':
											$o_search = new ObjectSearch();
											$vs_pk = 'object_id';
											break;
										case 'ca_object_lots':
											$o_search = new ObjectLotSearch();
											$vs_pk = 'lot_id';
											break;
										case 'ca_entities':
											$o_search = new EntitySearch();
											$vs_pk = 'entity_id';
											break;
										case 'ca_places':
											$o_search = new PlaceSearch();
											$vs_pk = 'place_id';
											break;
										case 'ca_occurrences':
											$o_search = new OccurrenceSearch();
											$vs_pk = 'occurrence_id';
											break;
										case 'ca_collections':
											$o_search = new CollectionSearch();
											$vs_pk = 'collection_id';
											break;
										case 'ca_storage_locations':
											$o_search = new StorageLocationSearch();
											$vs_pk = 'location_id';
											break;
										case 'ca_loans':
											$o_search = new LoanSearch();
											$vs_pk = 'loan_id';
											break;
										case 'ca_movements':
											$o_search = new MovementSearch();
											$vs_pk = 'movement_id';
											break;
										case 'ca_lists':
											$o_search = new ListSearch();
											$vs_pk = 'list_id';
											break;
										case 'ca_list_items':
											$o_search = new ListItemSearch();
											$vs_pk = 'item_id';
											break;
										default:
											$this->postError(2900, _t("Invalid search type"), "BrowseEngine->execute()");
											break(2);
									}
									if (is_array($va_type_ids = $this->getTypeRestrictionList()) && sizeof($va_type_ids)) {
										$o_search->setTypeRestrictions($va_type_ids);
									}
									$va_options = $pa_options;
									unset($va_options['sort']);		// browse engine takes care of sort so there is no reason to waste time having the search engine do so

									$qr_res = $o_search->search($va_row_ids[0], $va_options);

									if ($qr_res->numHits() > 0) {
										$va_ids = array();
										while($qr_res->nextHit()) {
											$va_ids[] = '('.(int)$qr_res->get($vs_pk).')';
										}
					
										$this->opo_db->query("INSERT IGNORE INTO ca_browses_acc VALUES ".join(",", $va_ids));
						
										$vn_i++;
									}
								} else {
									$this->postError(2900, _t("Invalid criteria type"), "BrowseEngine->execute()");
								}
								break;
							# -----------------------------------------------------
						}
					}
					$vs_filter_join_sql = $vs_filter_where_sql = '';
					$va_wheres = array();
					$va_joins = array();
					if (sizeof($this->opa_result_filters)) {
						$va_joins[$this->ops_browse_table_name] = "INNER JOIN ".$this->ops_browse_table_name." ON ".$this->ops_browse_table_name.'.'.$t_item->primaryKey().' = ca_browses_acc.row_id';
						
						$va_tmp = array();
						foreach($this->opa_result_filters as $va_filter) {
							$vm_val = $this->_filterValueToQueryValue($va_filter);
							
							$va_wheres[] = $this->ops_browse_table_name.'.'.$va_filter['field']." ".$va_filter['operator']." ".$vm_val;
						}
						
					}
					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_joins[$this->ops_browse_table_name] = "INNER JOIN ".$this->ops_browse_table_name." ON ".$this->ops_browse_table_name.'.'.$t_item->primaryKey().' = ca_browses_acc.row_id';
						$va_wheres[] = "(".$this->ops_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}
					
					if (($va_browse_type_ids = $this->getTypeRestrictionList()) && sizeof($va_browse_type_ids)) {
						$t_subject = $this->getSubjectInstance();
						$va_joins[$this->ops_browse_table_name] = "INNER JOIN ".$this->ops_browse_table_name." ON ".$this->ops_browse_table_name.'.'.$t_item->primaryKey().' = ca_browses_acc.row_id';
						$va_wheres[] = '('.$this->ops_browse_table_name.'.'.$t_subject->getTypeFieldName().' IN ('.join(', ', $va_browse_type_ids).'))';
					}
					
					if (sizeof($va_wheres)) {
						$vs_filter_where_sql = 'WHERE '.join(' AND ', $va_wheres);
					}
					if (sizeof($va_joins)) {
						$vs_filter_join_sql = join(' AND ', $va_joins);
					}
					$qr_res = $this->opo_db->query("
						SELECT row_id
						FROM ca_browses_acc
						{$vs_filter_join_sql}
						{$vs_filter_where_sql}
					");
					while($qr_res->nextRow()) {
						$va_results[] = $qr_res->get('row_id');
					}
					$this->_dropTempTable('ca_browses_acc');
					$this->_dropTempTable('ca_browses_tmp');
					
					$this->opo_ca_browse_cache->setResults($va_results);
					$vb_need_to_save_in_cache = true;
				}
			} else {
				// no criteria - don't try to find anything
			}
			
			if ($vb_need_to_cache_facets) {
				$va_facets_with_content = array();
				if (sizeof($va_results) != 1) {
					$va_facets = $this->getFacetList();
					$o_browse_cache = new BrowseCache();
					$va_parent_browse_params = $va_params;
					
					
					//
					// Get facets in parent browse (browse with all criteria except the last)
					// for availability checking. If a facet wasn't available for the parent browse it won't be
					// available for this one either.
					//
					$va_facets = null;
					if (is_array($va_cur_criteria = $va_parent_browse_params['criteria'])) {
						array_pop($va_parent_browse_params['criteria']);
						if ($o_browse_cache->load(BrowseCache::makeCacheKey($va_parent_browse_params, is_array($this->opa_browse_type_ids) ? $this->opa_browse_type_ids : array()))) {
							if (is_array($va_facet_list = $o_browse_cache->getFacets())) {
								$va_facets = array_keys($va_facet_list);
							}
						}
					}
					
					//
					// If we couldn't get facets for a parent browse then use full facet list
					//
					if (!$va_facets) {
						$va_facets = $this->getFacetList();
					}
					
					//
					// Loop through facets to see if they are available
					//
					foreach($va_facets as $vs_facet_name) {
						if ($this->getFacetContent($vs_facet_name, array_merge($pa_options, array('checkAvailabilityOnly' => true)))) {
							$va_facets_with_content[$vs_facet_name] = true;
						}
					}
				}
				
				if ((!$va_criteria) || (is_array($va_criteria) && (sizeof($va_criteria) == 0))) {	
					// for the "starting" facets (no criteria) we need to stash some statistics
					// so getInfoForFacetsWithContent() can operate efficiently
					$this->opo_ca_browse_cache->setGlobalParameter('facets_with_content', array_keys($va_facets_with_content));
				}
				
				$this->opo_ca_browse_cache->setFacets($va_facets_with_content);
			}
		
			return true;
		}
		# ------------------------------------------------------
		# Get facet
		# ------------------------------------------------------
		/**
		 * Return list of items from the specified table that are related to the current browse set
		 *
		 * Options:
		 *		checkAccess = array of access values to filter facets that have an 'access' field by
		 */
		public function getFacet($ps_facet_name, $pa_options=null) {
			if (!is_array($this->opa_browse_settings)) { return null; }
			$va_facet_cache = $this->opo_ca_browse_cache->getFacets();
			
			// is facet cached?
			if (isset($va_facet_cache[$ps_facet_name]) && is_array($va_facet_cache[$ps_facet_name])) { return $va_facet_cache[$ps_facet_name]; }
			
			return $this->getFacetContent($ps_facet_name, $pa_options);
		}
		# ------------------------------------------------------
		/**
		 * Return list of items from the specified table that are related to the current browse set
		 *
		 * Options:
		 *		checkAccess = array of access values to filter facets that have an 'access' field by
		 *		checkAvailabilityOnly = if true then content is not actually fetch - only the availablility of content is verified
		 */
		public function getFacetContent($ps_facet_name, $pa_options=null) {
			if (!is_array($this->opa_browse_settings)) { return null; }
			if (!isset($this->opa_browse_settings['facets'][$ps_facet_name])) { return null; }
			if (!is_array($pa_options)) { $pa_options = array(); }
			$vb_check_availability_only = (isset($pa_options['checkAvailabilityOnly'])) ? (bool)$pa_options['checkAvailabilityOnly'] : false;
			
			$va_all_criteria = $this->getCriteria();
			if (isset($va_all_criteria[$ps_facet_name])) { return ($vb_check_availability_only) ? false : array(); }
			$va_criteria = $this->getCriteria($ps_facet_name);
			
			$va_facet_info = $this->opa_browse_settings['facets'][$ps_facet_name];
			
			$vs_browse_type_limit_sql = '';
			if (($va_browse_type_ids = $this->getTypeRestrictionList()) && sizeof($va_browse_type_ids)) {		// type restrictions
				$t_subject = $this->getSubjectInstance();
				$vs_browse_type_limit_sql = '('.$this->ops_browse_table_name.'.'.$t_subject->getTypeFieldName().' IN ('.join(', ', $va_browse_type_ids).'))';
				
				if (is_array($va_facet_info['type_restrictions'])) { 		// facet type restrictions bind a facet to specific types; we check them here 
					$va_restrict_to_types = $this->_convertTypeCodesToIDs($va_facet_info['type_restrictions']);
					$vb_is_ok_to_browse = false;
					foreach($va_browse_type_ids as $vn_type_id) {
						if (in_array($vn_type_id, $va_restrict_to_types)) {
							$vb_is_ok_to_browse = true;
							break;
						}
					}
					
					if (!$vb_is_ok_to_browse) { return array(); }
				}
			}
			
			$va_results = $this->opo_ca_browse_cache->getResults();
			
			$vb_single_value_is_present = false;
			$vs_single_value = isset($va_facet_info['single_value']) ? $va_facet_info['single_value'] : null;
			
			$va_wheres = array();
			switch($va_facet_info['type']) {
				# -----------------------------------------------------
				case 'has':
					if (isset($va_all_criteria[$ps_facet_name])) { break; }		// only one instance of this facet allowed per browse 
					
					if (!($t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true))) { break; }
					
					$vs_yes_text = (isset($va_facet_info['label_yes']) && $va_facet_info['label_yes']) ? $va_facet_info['label_yes'] : _t('Yes');
					$vs_no_text = (isset($va_facet_info['label_no']) && $va_facet_info['label_no']) ? $va_facet_info['label_no'] : _t('No');
					
					// Actually check that both yes and no values will result in something
					
					$vs_rel_table_name = $va_facet_info['table'];
					if (!is_array($va_restrict_to_relationship_types = $va_facet_info['restrict_to_relationship_types'])) { $va_restrict_to_relationship_types = array(); }
					$va_restrict_to_relationship_types = $this->_getRelationshipTypeIDs($va_restrict_to_relationship_types, $va_facet_info['relationship_table']);

					$vn_table_num = $this->opo_datamodel->getTableNum($vs_rel_table_name);
					$vs_rel_table_pk = $this->opo_datamodel->getTablePrimaryKeyName($vn_table_num);
					
					switch(sizeof($va_path = array_keys($this->opo_datamodel->getPath($this->ops_browse_table_name, $vs_rel_table_name)))) {
						case 3:
							$t_item_rel = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
							$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[2], true);
							$vs_key = 'relation_id';
							break;
						case 2:
							$t_item_rel = null;
							$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
							$vs_key = $t_rel_item->primaryKey();
							break;
						default:
							// bad related table
							return null;
							break;
					}
					
					$va_facet_values = array(
						 'yes' => array(
							'id' => 1,
							'label' => $vs_yes_text
						),
						'no' => array(
							'id' => 0,
							'label' => $vs_no_text
						)
					);
					
					$vs_cur_table = array_shift($va_path);
					$va_joins = array();
					
					foreach($va_path as $vs_join_table) {
						$va_rel_info = $this->opo_datamodel->getRelationships($vs_cur_table, $vs_join_table);
						$va_joins[] = ($vn_state ? 'INNER' : 'LEFT').' JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
						$vs_cur_table = $vs_join_table;
					}
					
					$va_facet = array();
					$va_counts = array();
					foreach($va_facet_values as $vs_state_name => $va_state_info) {
						$va_wheres = array();
						
						$vs_join_sql = join("\n", $va_joins);
						
						if ((sizeof($va_restrict_to_relationship_types) > 0) && is_object($t_item_rel)) {
							$va_wheres[] = "(".$t_item_rel->tableName().".type_id IN (".join(',', $va_restrict_to_relationship_types)."))";
						}
						
						if (!(bool)$va_state_info['id']) {
							$va_wheres[] = "(".$t_rel_item->tableName().".".$t_rel_item->primaryKey()." IS NULL)";
						}
						
						if (sizeof($va_results)) {
							$va_wheres[] = $this->ops_browse_table_name.".".$t_item->primaryKey()." IN (".join(",", $va_results).")";
						}
						
						$vs_where_sql = '';
						if (sizeof($va_wheres) > 0) {
							$vs_where_sql = ' WHERE '.join(' AND ', $va_wheres);	
						}
	
						if ($vb_check_availability_only) {
							$vs_sql = "
								SELECT count(*) c
								FROM ".$this->ops_browse_table_name."
								{$vs_join_sql}
									{$vs_where_sql}
								LIMIT 1
							";
							$qr_res = $this->opo_db->query($vs_sql);
							if ($qr_res->nextRow()) {
								if ($vn_c = (int)$qr_res->get('c')) {
									$va_counts[$vs_state_name] = $vn_c;
								}
							}
						} else {
							$vs_sql = "
								SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
								FROM ".$this->ops_browse_table_name."
								{$vs_join_sql}
									{$vs_where_sql}
							";
							//print "$vs_sql<hr>";
							$qr_res = $this->opo_db->query($vs_sql);
							if ($qr_res->numRows() > 0) {
								$va_facet[$vs_state_name] = $va_state_info;
							} else {
								return array();		// if either option in a "has" facet fails then don't show the facet
							}
						}
					}
					
					if ($vb_check_availability_only) {
						return (sizeof($va_counts) > 1) ? true : false;
					}
					
					return $va_facet;
					break;
				# -----------------------------------------------------
				case 'label':
					if (!($t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true))) { break; }
					if (!($t_label = $t_item->getLabelTableInstance())) { break; }
					
					$vs_item_pk = $t_item->primaryKey();
					$vs_label_table_name = $t_label->tableName();
					$vs_label_pk = $t_label->primaryKey();
					$vs_label_display_field = $t_item->getLabelDisplayField();
					$vs_label_sort_field = $t_item->getLabelSortField();

					$vs_where_sql = '';
					$va_where_sql = array();
					if (sizeof($va_results)) {
						$va_where_sql[] = "l.{$vs_item_pk} IN (".join(",", $va_results).")";
					}
					
					if (isset($va_facet_info['preferred_labels_only']) && $va_facet_info['preferred_labels_only'] && $t_label->hasField('is_preferred')) {
						$va_where_sql[] = "l.is_preferred = 1";
					}
					
					if (sizeof($va_where_sql)) {
						$vs_where_sql = "WHERE ".join(" AND ", $va_where_sql);
					}
					
					if ($vb_check_availability_only) {
						$vs_sql = "
							SELECT  count(*) c
							FROM {$vs_label_table_name} l
								{$vs_where_sql}
							LIMIT 1
						";
						//print $vs_sql;
						$qr_res = $this->opo_db->query($vs_sql);
						
						if ($qr_res->nextRow()) {
							return ((int)$qr_res->get('c') > 0) ? true : false;
						}
						return false;
					} else {
						$vs_sql = "
							SELECT  l.*
							FROM {$vs_label_table_name} l
								{$vs_where_sql}
						";
						//print $vs_sql;
						$qr_res = $this->opo_db->query($vs_sql);
						
						$va_values = array();
						while($qr_res->nextRow()) {
							$va_values[$vn_id = $qr_res->get($vs_label_pk)][$qr_res->get('locale_id')] = array_merge($qr_res->getRow(), array(
								'id' => $vn_id,
								'label' => $qr_res->get($vs_label_display_field)
							));
							if (!is_null($vs_single_value) && ($vn_id == $vs_single_value)) {
								$vb_single_value_is_present = true;
							}
						}
						
						if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
							return array();
						}
						
						$va_values = caExtractValuesByUserLocale($va_values);
						return $va_values;
					}
					break;
				# -----------------------------------------------------
				case 'attribute':
					$t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true);
					$t_element = new ca_metadata_elements();
					if (!$t_element->load(array('element_code' => $va_facet_info['element_code']))) {
						return array();
					}
					
					$vn_element_id = $t_element->getPrimaryKey();
					
					$va_joins = array(
						'INNER JOIN ca_attribute_values ON ca_attributes.attribute_id = ca_attribute_values.attribute_id',
						'INNER JOIN '.$this->ops_browse_table_name.' ON '.$this->ops_browse_table_name.'.'.$t_item->primaryKey().' = ca_attributes.row_id AND ca_attributes.table_num = '.intval($this->opn_browse_table_num)
					);
					
					$va_wheres = array();
					if (sizeof($va_results) && ($this->numCriteria() > 0)) {
						$va_wheres[] = "(".$this->ops_browse_table_name.'.'.$t_item->primaryKey()." IN (".join(',', $va_results)."))";
					}
					
					
					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_wheres[] = "(".$this->ops_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}
					
					if ($vs_browse_type_limit_sql) {
						$va_wheres[] = $vs_browse_type_limit_sql;
					}
					
					$vs_join_sql = join("\n", $va_joins);
					if (is_array($va_wheres) && sizeof($va_wheres) && ($vs_where_sql = join(' AND ', $va_wheres))) {
						$vs_where_sql = ' AND ('.$vs_where_sql.')';
					}
					
					if ($vb_check_availability_only) {
						// exclude criteria values
						$vs_criteria_exclude_sql = '';
						if (is_array($va_criteria) && sizeof($va_criteria)) { 
							$vs_criteria_exclude_sql = ' AND (ca.attribute_values.value_longtext1 NOT IN ('.caQuoteList(array_keys($va_criteria)).') ';
						}
						
						$vs_sql = "
							SELECT count(DISTINCT value_longtext1) c
							FROM ca_attributes
							
							{$vs_join_sql}
							WHERE
								(ca_attribute_values.element_id = ?) {$vs_criteria_exclude_sql} {$vs_where_sql}
							LIMIT 1";
						//print $vs_sql;
						$qr_res = $this->opo_db->query($vs_sql,$vn_element_id);
						
						if ($qr_res->nextRow()) {
							return ((int)$qr_res->get('c') > 0) ? true : false;
						}
						return false;
					} else {
						$vs_sql = "
							SELECT DISTINCT value_longtext1
							FROM ca_attributes
							
							{$vs_join_sql}
							WHERE
								ca_attribute_values.element_id = ? {$vs_where_sql}";
						
						$qr_res = $this->opo_db->query($vs_sql, $vn_element_id);
						
						$va_values = array();
						
						$vn_element_type = $t_element->get('datatype');
						
						$va_list_items = null;
						if ($vn_element_type == 3) { // list
							$t_list = new ca_lists();
							$va_list_items = caExtractValuesByUserLocale($t_list->getItemsForList($t_element->get('list_id')));
						}
						
						while($qr_res->nextRow()) {
							if(!($vs_val = trim($qr_res->get('value_longtext1')))) { continue; }
							switch($vn_element_type) {
								case 3:	// list
									if ($va_criteria[$vs_val]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on
									
									$va_values[$vs_val] = array(
										'id' => $vs_val,
										'label' => $va_list_items[$vs_val]['name_plural'] ? $va_list_items[$vs_val]['name_plural'] : $va_list_items[$vs_val]['item_value']
									);
									break;
								default:
									if ($va_criteria[$vs_val]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on
									
									$va_values[$vs_val] = array(
										'id' => str_replace('/', '&#47;', $vs_val),
										'label' => $vs_val
									);
									break;
							}
							
							if (!is_null($vs_single_value) && ($vs_val == $vs_single_value)) {
								$vb_single_value_is_present = true;
							}
						}
						
						if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
							return array();
						}
						
						if ($vn_element_type == 3) { // list
							// preserve order of list
							$va_values_sorted_by_list_order = array();
							foreach($va_list_items as $vn_item_id => $va_item) {
								if(isset($va_values[$vn_item_id])) {
									$va_values_sorted_by_list_order[$vn_item_id] = $va_values[$vn_item_id];
								}
							}
							return $va_values_sorted_by_list_order;
						}
						ksort($va_values);
						return $va_values;
					}
					break;
				# -----------------------------------------------------
				case 'fieldList':
					$t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true);
					$vs_field_name = $va_facet_info['field'];
					$va_field_info = $t_item->getFieldInfo($vs_field_name);
					
					$t_list = new ca_lists();
					$t_list_item = new ca_list_items();
					
					$va_joins = array();
					$va_wheres = array();
					$vs_where_sql = '';
					
					if (isset($va_field_info['LIST_CODE']) && ($vs_list_name = $va_field_info['LIST_CODE'])) {
						// Handle fields containing ca_list_item.item_id's
						$va_joins = array(
							'INNER JOIN '.$this->ops_browse_table_name.' ON '.$this->ops_browse_table_name.'.'.$vs_field_name.' = li.item_id',
							'INNER JOIN ca_lists ON ca_lists.list_id = li.list_id'
						);
						if (sizeof($va_results) && ($this->numCriteria() > 0)) {
							$va_wheres[] = "(".$this->ops_browse_table_name.'.'.$t_item->primaryKey()." IN (".join(',', $va_results)."))";
						}
						
						if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
							$va_wheres[] = "(".$this->ops_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
						}
						
						if ($vs_browse_type_limit_sql) {
							$va_wheres[] = $vs_browse_type_limit_sql;
						}
						
						$vs_join_sql = join("\n", $va_joins);
						
						if (is_array($va_wheres) && sizeof($va_wheres) && ($vs_where_sql = join(' AND ', $va_wheres))) {
							$vs_where_sql = ' AND ('.$vs_where_sql.')';
						}
						
						if ($vb_check_availability_only) {
							$vs_sql = "
								SELECT count(*) c
								FROM ca_list_items li
								INNER JOIN ca_list_item_labels lil ON lil.item_id = li.item_id
								{$vs_join_sql}
								WHERE
									ca_lists.list_code = ? {$vs_where_sql}
								LIMIT 1";
								
							$qr_res = $this->opo_db->query($vs_sql, $vs_list_name);
						
							if ($qr_res->nextRow()) {
								return ((int)$qr_res->get('c') > 0) ? true : false;
							}
							return false;
						} else {
							$vs_sql = "
								SELECT DISTINCT lil.item_id, lil.name_singular, lil.name_plural
								FROM ca_list_items li
								INNER JOIN ca_list_item_labels AS lil ON lil.item_id = li.item_id
								{$vs_join_sql}
								WHERE
									ca_lists.list_code = ? {$vs_where_sql}";
							//print $vs_sql." [$vs_list_name]";
							$qr_res = $this->opo_db->query($vs_sql, $vs_list_name);
							
							$va_values = array();
							while($qr_res->nextRow()) {
								$vn_id = $qr_res->get('item_id');
								if ($va_criteria[$vn_id]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on
							
								$va_values[$vn_id][$qr_res->get('locale_id')] = array(
									'id' => $vn_id,
									'label' => $qr_res->get('name_plural')
								);
								if (!is_null($vs_single_value) && ($vn_id == $vs_single_value)) {
									$vb_single_value_is_present = true;
								}
							}
							
							if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
								return array();
							}
							return caExtractValuesByUserLocale($va_values);
						}
					} else {
					
						if ($vs_list_name = $va_field_info['LIST']) {
							$va_list_items_by_value = array();
								
							// fields with values set according to ca_list_items (not a foreign key ref)
							if ($va_list_items = caExtractValuesByUserLocale($t_list->getItemsForList($vs_list_name))) {
								foreach($va_list_items as $vn_id => $va_list_item) {
									$va_list_items_by_value[$va_list_item['item_value']] = $va_list_item['name_plural'];
								}
								
							} else {
								foreach($va_field_info['BOUNDS_CHOICE_LIST'] as $vs_val => $vn_id) {
									$va_list_items_by_value[$vn_id] = $vs_val;
								}
							}
							
							if (sizeof($va_results) && ($this->numCriteria() > 0)) {
								$va_wheres[] = "(".$this->ops_browse_table_name.'.'.$t_item->primaryKey()." IN (".join(',', $va_results)."))";
							}
							
							if ($vs_browse_type_limit_sql) {
								$va_wheres[] = $vs_browse_type_limit_sql;
							}
							
							if (is_array($va_wheres) && sizeof($va_wheres) && ($vs_where_sql = join(' AND ', $va_wheres))) {
								$vs_where_sql = '('.$vs_where_sql.')';
							}
							
							$vs_join_sql = join("\n", $va_joins);
							
							if ($vb_check_availability_only) {
								$vs_sql = "
									SELECT count(*) c
									FROM ".$this->ops_browse_table_name."
									{$vs_join_sql}
									".($vs_where_sql ? 'WHERE' : '')."
									{$vs_where_sql}
									LIMIT 1";
								$qr_res = $this->opo_db->query($vs_sql);
							
								if ($qr_res->nextRow()) {
									return ((int)$qr_res->get('c') > 0) ? true : false;
								}
								return false;
							} else {
								$vs_sql = "
									SELECT DISTINCT ".$this->ops_browse_table_name.'.'.$vs_field_name."
									FROM ".$this->ops_browse_table_name."
									{$vs_join_sql}
									".($vs_where_sql ? 'WHERE' : '')."
										{$vs_where_sql}";
								//print $vs_sql." [$vs_list_name]";
								
								$qr_res = $this->opo_db->query($vs_sql);
								$va_values = array();
								while($qr_res->nextRow()) {
									$vn_id = $qr_res->get($vs_field_name);
									if ($va_criteria[$vn_id]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on
								
									if (isset($va_list_items_by_value[$vn_id])) { 
										$va_values[$vn_id] = array(
											'id' => $vn_id,
											'label' => $va_list_items_by_value[$vn_id]
										);
										if (!is_null($vs_single_value) && ($vn_id == $vs_single_value)) {
											$vb_single_value_is_present = true;
										}
									}
								}
								
								if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
									return array();
								}
								return $va_values;
							}
						} else {
							if ($t_browse_table = $this->opo_datamodel->getInstanceByTableName($vs_facet_table = $va_facet_info['table'], true)) {
								// Handle fields containing ca_list_item.item_id's
								$va_joins = array(
									'INNER JOIN '.$this->ops_browse_table_name.' ON '.$this->ops_browse_table_name.'.'.$vs_field_name.' = '.$vs_facet_table.'.'.$t_browse_table->primaryKey()
								);
								
								
								$vs_display_field_name = null;
								if (method_exists($t_browse_table, 'getLabelTableInstance')) {
									$t_label_instance = $t_browse_table->getLabelTableInstance();
									$vs_display_field_name = (isset($va_facet_info['display']) && $va_facet_info['display']) ? $va_facet_info['display'] : $t_label_instance->getDisplayField();
									$va_joins[] = 'INNER JOIN '.$t_label_instance->tableName()." AS lab ON lab.".$t_browse_table->primaryKey().' = '.$t_browse_table->tableName().'.'.$t_browse_table->primaryKey();
								}
								
								if (sizeof($va_results) && ($this->numCriteria() > 0)) {
									$va_wheres[] = "(".$this->ops_browse_table_name.'.'.$t_item->primaryKey()." IN (".join(',', $va_results)."))";
								}
								
								if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
									$va_wheres[] = "(".$this->ops_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
								}
								
								if ($vs_browse_type_limit_sql) {
									$va_wheres[] = $vs_browse_type_limit_sql;
								}
								
								$vs_join_sql = join("\n", $va_joins);
								
								if (is_array($va_wheres) && sizeof($va_wheres) && ($vs_where_sql = join(' AND ', $va_wheres))) {
									$vs_where_sql = 'WHERE ('.$vs_where_sql.')';
								}
								
								if ($vb_check_availability_only) {
									$vs_sql = "
										SELECT count(*)
										FROM {$vs_facet_table}
										
										{$vs_join_sql}
										{$vs_where_sql}
										LIMIT 1";
									$qr_res = $this->opo_db->query($vs_sql);
								
									if ($qr_res->nextRow()) {
										return ((int)$qr_res->get('c') > 0) ? true : false;
									}
									return false;
								} else {
									$vs_sql = "
										SELECT DISTINCT *
										FROM {$vs_facet_table}
										
										{$vs_join_sql}
										{$vs_where_sql}";
									//print $vs_sql;
									$qr_res = $this->opo_db->query($vs_sql);
									
									$va_values = array();
									$vs_pk = $t_browse_table->primaryKey();
									while($qr_res->nextRow()) {
										$vn_id = $qr_res->get($vs_pk);
										if ($va_criteria[$vn_id]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on
									
										$va_values[$vn_id][$qr_res->get('locale_id')] = array(
											'id' => $vn_id,
											'label' => $qr_res->get($vs_display_field_name)
										);
										if (!is_null($vs_single_value) && ($vn_id == $vs_single_value)) {
											$vb_single_value_is_present = true;
										}
									}
									
									if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
										return array();
									}
									return caExtractValuesByUserLocale($va_values);
								}
							}
						}
					}
					return array();
					break;
				# -----------------------------------------------------
				case 'normalizedDates':
					$t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true);
					$t_element = new ca_metadata_elements();
					if (!$t_element->load(array('element_code' => $va_facet_info['element_code']))) {
						return array();
					}
					
					$va_wheres = array();
					
					$vn_element_id = $t_element->getPrimaryKey();
					
					$vs_normalization = $va_facet_info['normalization'];	// how do we construct the date ranges presented to uses. In other words - how do we want to allow users to browse dates? By year, decade, century?
					
					$va_joins = array(
						'INNER JOIN ca_attribute_values ON ca_attributes.attribute_id = ca_attribute_values.attribute_id',
						'INNER JOIN '.$this->ops_browse_table_name.' ON '.$this->ops_browse_table_name.'.'.$t_item->primaryKey().' = ca_attributes.row_id AND ca_attributes.table_num = '.intval($this->opn_browse_table_num)
					);
					if (sizeof($va_results) && ($this->numCriteria() > 0)) {
						$va_wheres[] = "(".$this->ops_browse_table_name.'.'.$t_item->primaryKey()." IN (".join(',', $va_results)."))";
					}
					
					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_wheres[] = "(".$this->ops_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}
					
					if ($vs_browse_type_limit_sql) {
						$va_wheres[] = $vs_browse_type_limit_sql;
					}
					
					$vs_join_sql = join("\n", $va_joins);
					
					$vs_where_sql = '';
					if (is_array($va_wheres) && sizeof($va_wheres) && ($vs_where_sql = join(' AND ', $va_wheres))) {
						$vs_where_sql = ' AND ('.$vs_where_sql.')';
					}
					
					$vs_dir = (strtoupper($va_facet_info['sort']) === 'DESC') ? "DESC" : "ASC";
					
					$o_tep = new TimeExpressionParser();
					$vn_min_date = $vn_max_date = null;
					$vs_min_sql = $vs_max_sql = '';
					if (isset($va_facet_info['minimum_date'])) {
						if ($o_tep->parse($va_facet_info['minimum_date'])) {
							$va_tmp = $o_tep->getHistoricTimestamps();
							$vn_min_date = (float)$va_tmp['start'];
							$vs_min_sql = " AND (ca_attribute_values.value_decimal1 >= {$vn_min_date})";
						}
					}
					if (isset($va_facet_info['maximum_date'])) {
						if ($o_tep->parse($va_facet_info['maximum_date'])) {
							$va_tmp = $o_tep->getHistoricTimestamps();
							$vn_max_date = (float)$va_tmp['end'];
							$vs_max_sql = " AND (ca_attribute_values.value_decimal2 <= {$vn_max_date})";
						}
					}
					
					if ($vb_check_availability_only) {
						$vs_sql = "
							SELECT count(*) c
							FROM ca_attributes
							
							{$vs_join_sql}
							WHERE
								ca_attribute_values.element_id = ? 
								{$vs_min_sql}
								{$vs_max_sql}
								{$vs_where_sql}
								LIMIT 1";
						//print $vs_sql;
						$qr_res = $this->opo_db->query($vs_sql, $vn_element_id);
						
						if ($qr_res->nextRow()) {
							return ((int)$qr_res->get('c') > 0) ? true : false;
						}
						return false;
					} else {
						$vs_sql = "
							SELECT DISTINCT ca_attribute_values.value_decimal1, ca_attribute_values.value_decimal2
							FROM ca_attributes
							
							{$vs_join_sql}
							WHERE
								ca_attribute_values.element_id = ? 
								{$vs_min_sql}
								{$vs_max_sql}
								{$vs_where_sql}
						";
						$qr_res = $this->opo_db->query($vs_sql, $vn_element_id);
					
						$va_values = array();
						while($qr_res->nextRow()) {
							$vn_start = $qr_res->get('value_decimal1');
							$vn_end = $qr_res->get('value_decimal2');
							
							if (!($vn_start && $vn_end)) { continue; }
							$va_normalized_values = $o_tep->normalizeDateRange($vn_start, $vn_end, $vs_normalization);
							foreach($va_normalized_values as $vn_sort_value => $vs_normalized_value) {
								if ($va_criteria[$vs_normalized_value]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on
									
								if (is_numeric($vs_normalized_value) && (int)$vs_normalized_value === 0) { continue; }		// don't include year=0
								$va_values[$vn_sort_value][$vs_normalized_value] = array(
									'id' => $vs_normalized_value,
									'label' => $vs_normalized_value
								);	
								if (!is_null($vs_single_value) && ($vs_normalized_value == $vs_single_value)) {
									$vb_single_value_is_present = true;
								}
							}
						}
						
						if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
							return array();
						}
						
						ksort($va_values);
						
						if ($vs_dir == 'DESC') { $va_values = array_reverse($va_values); }
						$va_sorted_values = array();
						foreach($va_values as $vn_sort_value => $va_values_for_sort_value) {
							$va_sorted_values = array_merge($va_sorted_values, $va_values_for_sort_value);
						}
						return $va_sorted_values;
					}
					break;
				# -----------------------------------------------------
				case 'authority':
					$vs_rel_table_name = $va_facet_info['table'];
					$va_params = $this->opo_ca_browse_cache->getParameters();
					if (!is_array($va_restrict_to_types = $va_facet_info['restrict_to_types'])) { $va_restrict_to_types = array(); }
					if (!is_array($va_restrict_to_relationship_types = $va_facet_info['restrict_to_relationship_types'])) { $va_restrict_to_relationship_types = array(); }
					
					$t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true);
					
					switch(sizeof($va_path = array_keys($this->opo_datamodel->getPath($this->ops_browse_table_name, $vs_rel_table_name)))) {
						case 3:
							$t_item_rel = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
							$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[2], true);
							$vs_key = 'relation_id';
							break;
						case 2:
							$t_item_rel = null;
							$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
							$vs_key = $t_rel_item->primaryKey();
							break;
						default:
							// bad related table
							return null;
							break;
					}
					
					$vb_rel_is_hierarchical = (bool)$t_rel_item->isHierarchical();
					
					//
					// Convert related item type_code specs in restrict_to_types list to numeric type_ids we need for the query
					//
					$t_list = new ca_lists();
					$t_list_item = new ca_list_items();
					$va_type_list = $va_restrict_to_types;
					$va_restrict_to_types = array();
					foreach($va_type_list as $vn_i => $vm_type) {
						if (!trim($vm_type)) { unset($va_type_list[$vn_i]); continue; }
						if (!is_numeric($vm_type)) {
							// try to translate item_value code into numeric id
							if (!($va_item = $t_list->getItemFromList($t_rel_item->getTypeListCode(), $vm_type))) { continue; }
							unset($va_restrict_to_types[$vn_i]);
							$va_restrict_to_types[] = $vn_item_id = $va_item['item_id'];
						}  else {
							if (!$t_list_item->load($vm_type)) { continue; }
							if ($vn_item_id = $t_list_item->getPrimaryKey()) {
								$va_restrict_to_types[] = $vn_item_id;
							}
						}
						
						$va_ids = $t_list_item->getHierarchyChildren($vn_item_id, array('idsOnly' => true));
						
						if (is_array($va_ids)) {
							foreach($va_ids as $vn_id) {
								$va_restrict_to_types[] = $vn_id;
							}
						}
					}
			
					// look up relationship type restrictions
					$va_restrict_to_relationship_types = $this->_getRelationshipTypeIDs($va_restrict_to_relationship_types, $va_facet_info['relationship_table']);
					$va_joins = array();
					$va_selects = array();
					$va_wheres = array();
					$va_orderbys = array();
					
if (!$va_facet_info['show_all_when_first_facet'] || ($this->numCriteria() > 0)) {	
					$vs_cur_table = array_shift($va_path);
					
					foreach($va_path as $vs_join_table) {
						$va_rel_info = $this->opo_datamodel->getRelationships($vs_cur_table, $vs_join_table);
						$va_joins[] = 'INNER JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
						$vs_cur_table = $vs_join_table;
					}
} else {
					if ($va_facet_info['show_all_when_first_facet']) {
						$va_path = array_reverse($va_path);		// in "show_all" mode we turn the browse on it's head and grab records by the "subject" table, rather than the browse table
						$vs_cur_table = array_shift($va_path);
						$vs_join_table = $va_path[0];
						$va_rel_info = $this->opo_datamodel->getRelationships($vs_cur_table, $vs_join_table);
						$va_joins[] = 'LEFT JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
										
					}
}

					if (sizeof($va_results) && ($this->numCriteria() > 0)) {
						$va_wheres[] = "(".$this->ops_browse_table_name.'.'.$t_item->primaryKey()." IN (".join(',', $va_results)."))";
					}
					
					if ((sizeof($va_restrict_to_types) > 0) && method_exists($t_rel_item, "getTypeList")) {
						$va_wheres[] = "{$vs_rel_table_name}.type_id IN (".join(',', $va_restrict_to_types).")";
					}
					
					if ((sizeof($va_restrict_to_relationship_types) > 0) && is_object($t_item_rel)) {
						$va_wheres[] = $t_item_rel->tableName().".type_id IN (".join(',', $va_restrict_to_relationship_types).")";
					}
					
					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access')) {
						$va_wheres[] = "(".$t_rel_item->tableName().".access IN (".join(',', $pa_options['checkAccess'])."))";				// exclude non-accessible authority items
						if (!$va_facet_info['show_all_when_first_facet'] || ($this->numCriteria() > 0)) {	
							$va_wheres[] = "(".$this->ops_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";		// exclude non-accessible browse items
						}
					}
					
					
					if (sizeof($va_restrict_to_relationship_types) > 0) {
						$va_wheres[] = $va_facet_info['relationship_table'].".type_id IN (".join(',', $va_restrict_to_relationship_types).")";
					}
					
					if ($vs_browse_type_limit_sql) {
						$va_wheres[] = $vs_browse_type_limit_sql;
					}
					
					$vs_rel_pk = $t_rel_item->primaryKey();
					$va_rel_attr_elements = $t_rel_item->getApplicableElementCodes(null, true, false);
					
					$va_attrs_to_fetch = array();
if (!$va_facet_info['show_all_when_first_facet'] || ($this->numCriteria() > 0)) {				
					$va_selects[] = $t_item->tableName().'.'.$t_item->primaryKey();			// get primary key of subject
}
					$va_selects[] = $t_rel_item->tableName().'.'.$vs_rel_pk;				// get primary key of related
					
					
					$vs_hier_parent_id_fld = null;
					if ($vb_rel_is_hierarchical) {
						$vs_hier_parent_id_fld = $t_rel_item->getProperty('HIERARCHY_PARENT_ID_FLD');
						$va_selects[] = $t_rel_item->tableName().'.'.$vs_hier_parent_id_fld;
					}
					
					// analyze group_fields (if defined) and add them to the query
					$va_groupings_to_fetch = array();
					if (isset($va_facet_info['groupings']) && is_array($va_facet_info['groupings']) && sizeof($va_facet_info['groupings'])) {
						foreach($va_facet_info['groupings'] as $vs_grouping => $vs_grouping_name) {
							// is grouping type_id?
							if (($vs_grouping === 'type') && $t_rel_item->hasField('type_id')) {
								$va_selects[] = $t_rel_item->tableName().'.type_id';
								$va_groupings_to_fetch[] = 'type_id';
							}
							
							// is group field a relationship type?
							if ($vs_grouping === 'relationship_types') {
								$va_selects[] = $va_facet_info['relationship_table'].'.type_id rel_type_id';
								$va_groupings_to_fetch[] = 'rel_type_id';
							}
							
							// is group field an attribute?
							if (preg_match('!^ca_attribute_([^:]*)!', $vs_grouping, $va_matches)) {
								if ($vn_element_id = array_search($va_matches[1], $va_rel_attr_elements)) {
									$va_attrs_to_fetch[] = $vn_element_id;
								}
							}
							
						}
					}
					
					$vs_join_sql = join("\n", $va_joins);
				
				
					if ($vb_check_availability_only) {
	if (!$va_facet_info['show_all_when_first_facet'] || ($this->numCriteria() > 0)) {	
						$vs_sql = "
							SELECT count(*) c
							FROM ".$this->ops_browse_table_name."
							{$vs_join_sql}
								".(sizeof($va_wheres) ? ' WHERE ' : '').join(" AND ", $va_wheres)." LIMIT 1";
	} else {
						$vs_sql = "
							SELECT count(*) c
							FROM ".$t_rel_item->tableName()."
							{$vs_join_sql}
								".(sizeof($va_wheres) ? ' WHERE ' : '').join(" AND ", $va_wheres)." LIMIT 1";
	}
						$qr_res = $this->opo_db->query($vs_sql);
						
						if ($qr_res->nextRow()) {
							return ((int)$qr_res->get('c') > 0) ? true : false;
						}
						return false;
					} else {
						
	if (!$va_facet_info['show_all_when_first_facet'] || ($this->numCriteria() > 0)) {	
						$vs_sql = "
							SELECT DISTINCT ".join(', ', $va_selects)."
							FROM ".$this->ops_browse_table_name."
							{$vs_join_sql}
								".(sizeof($va_wheres) ? ' WHERE ' : '').join(" AND ", $va_wheres)."
								".(sizeof($va_orderbys) ? "ORDER BY ".join(', ', $va_orderbys) : '');
	} else {
						$vs_sql = "
							SELECT DISTINCT ".join(', ', $va_selects)."
							FROM ".$t_rel_item->tableName()."
							{$vs_join_sql}
								".(sizeof($va_wheres) ? ' WHERE ' : '').join(" AND ", $va_wheres)."
								".(sizeof($va_orderbys) ? "ORDER BY ".join(', ', $va_orderbys) : '');
	}
						//print "<hr>$vs_sql<hr>\n";
						
						$qr_res = $this->opo_db->query($vs_sql);
						
						$va_facet = $va_facet_items = array();
						$vs_rel_pk = $t_rel_item->primaryKey();
						
						// First get related ids with type and relationship type values
						// (You could get all of the data we need for the facet in a single query but it turns out to be faster for very large facets to 
						// do it in separate queries, one for the primary ids and another for the labels; a third is done if attributes need to be fetched.
						// There appears to be a significant [~10%] performance for smaller facets and a larger one [~20-25%] for very large facets)
						$va_facet_parents = array();
						while($qr_res->nextRow()) {
							$va_fetched_row = $qr_res->getRow();
							$vn_id = $va_fetched_row[$vs_rel_pk];
							//if (isset($va_facet_items[$vn_id])) { continue; } --- we can't do this as then we don't detect items that have multiple rel_type_ids... argh.
							if (isset($va_criteria[$vn_id])) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on
							
							
							if (!$va_facet_items[$va_fetched_row[$vs_rel_pk]]) {
								$va_facet_items[$va_fetched_row[$vs_rel_pk]] = array(
									'id' => $va_fetched_row[$vs_rel_pk],
									'type_id' => array(),
									'parent_id' => $vb_rel_is_hierarchical ? $va_fetched_row[$vs_hier_parent_id_fld] : null,
									'rel_type_id' => array(),
									'child_count' => 0
								);
								
								if ($va_fetched_row[$vs_hier_parent_id_fld]) {
									$va_facet_parents[$va_fetched_row[$vs_hier_parent_id_fld]] = true;
								}
								if (!is_null($vs_single_value) && ($va_fetched_row[$vs_rel_pk] == $vs_single_value)) {
									$vb_single_value_is_present = true;
								}
							}
							if ($va_fetched_row['type_id']) {
								$va_facet_items[$va_fetched_row[$vs_rel_pk]]['type_id'][] = $va_fetched_row['type_id'];
							}
							if ($va_fetched_row['rel_type_id']) {
								$va_facet_items[$va_fetched_row[$vs_rel_pk]]['rel_type_id'][] = $va_fetched_row['rel_type_id'];
							}
						}
						
						// Expand facet to include ancestors
						while(sizeof($va_ids = array_keys($va_facet_parents))) {
							$vs_sql = "
								SELECT p.".$t_rel_item->primaryKey().", p.{$vs_hier_parent_id_fld}
								FROM ".$t_rel_item->tableName()." p
								WHERE
									p.".$t_rel_item->primaryKey()." IN (?)
							";
							$qr_res = $this->opo_db->query($vs_sql, array($va_ids));
							
							$va_facet_parents = array();
							while($qr_res->nextRow()) {
								$va_fetched_row = $qr_res->getRow();
								$va_facet_items[$va_fetched_row[$vs_rel_pk]] = array(
									'id' => $va_fetched_row[$vs_rel_pk],
									'type_id' => array(),
									'parent_id' => $vb_rel_is_hierarchical ? $va_fetched_row[$vs_hier_parent_id_fld] : null,
									'rel_type_id' => array(),
									'child_count' => 0
								);
								if ($va_fetched_row[$vs_hier_parent_id_fld]) { $va_facet_parents[$va_fetched_row[$vs_hier_parent_id_fld]] = true; }
							}
						}
						
						// Set child counts
						foreach($va_facet_items as $vn_i => $va_item) {
							if ($va_item['parent_id']) {
								$va_facet_items[$va_item['parent_id']]['child_count']++;
							}
						}
						
						// Get labels for facet items
						if (sizeof($va_row_ids = array_keys($va_facet_items))) {	
							if ($vs_label_table_name = $t_rel_item->getLabelTableName()) {
								$t_rel_item_label = $this->opo_datamodel->getInstanceByTableName($vs_label_table_name, true);
								$vs_label_display_field = $t_rel_item_label->getDisplayField();
								
								$vs_rel_pk = $t_rel_item->primaryKey();
								$va_label_wheres = array();
								
								if ($t_rel_item_label->hasField('is_preferred')) {
									$va_label_wheres[] = "({$vs_label_table_name}.is_preferred = 1)";
								}
								$va_label_wheres[] = "({$vs_label_table_name}.{$vs_rel_pk} IN (".join(",", $va_row_ids)."))";
								$va_label_selects[] = "{$vs_label_table_name}.{$vs_rel_pk}";
								$va_label_selects[] = "{$vs_label_table_name}.locale_id";
								
								$va_label_fields = $t_rel_item->getLabelUIFields();
								foreach($va_label_fields as $vs_label_field) {
									$va_label_selects[] = "{$vs_label_table_name}.{$vs_label_field}";
								}
								
								// Get label ordering fields
								$va_ordering_fields_to_fetch = (isset($va_facet_info['order_by_label_fields']) && is_array($va_facet_info['order_by_label_fields'])) ? $va_facet_info['order_by_label_fields'] : array();
		
								$va_orderbys = array();
								foreach($va_ordering_fields_to_fetch as $vs_sort_by_field) {
									if (!$t_rel_item_label->hasField($vs_sort_by_field)) { continue; }
									$va_orderbys[] = $va_label_selects[] = $vs_label_table_name.'.'.$vs_sort_by_field;
								}
								
								// get labels
								$vs_sql = "
									SELECT ".join(', ', $va_label_selects)."
									FROM ".$vs_label_table_name."
										".(sizeof($va_label_wheres) ? ' WHERE ' : '').join(" AND ", $va_label_wheres)."
										".(sizeof($va_orderbys) ? "ORDER BY ".join(', ', $va_orderbys) : '')."";
								//print $vs_sql;
								$qr_labels = $this->opo_db->query($vs_sql);
								
								while($qr_labels->nextRow()) {
									$va_fetched_row = $qr_labels->getRow();
									$va_facet_item = array_merge($va_facet_items[$va_fetched_row[$vs_rel_pk]], array('label' => $va_fetched_row[$vs_label_display_field]));
															
									foreach($va_ordering_fields_to_fetch as $vs_to_fetch) {
										$va_facet_item[$vs_to_fetch] = $va_fetched_row[$vs_to_fetch];
									}
									
									$va_facet[$va_fetched_row[$vs_rel_pk]][$va_fetched_row['locale_id']] = $va_facet_item;
								}
							}
							
							// get attributes for facet items
							if (sizeof($va_attrs_to_fetch)) {
								$qr_attrs = $this->opo_db->query("
									SELECT c_av.*, c_a.locale_id, c_a.row_id
									FROM ca_attributes c_a
									INNER JOIN ca_attribute_values c_av ON c_a.attribute_id = c_av.attribute_id
									WHERE
										c_av.element_id IN (".join(',', $va_attrs_to_fetch).")
										AND
										c_a.table_num = ? 
										AND 
										c_a.row_id IN (".join(',', $va_row_ids).")
								", $t_rel_item->tableNum());
								while($qr_attrs->nextRow()) {
									$va_fetched_row = $qr_attrs->getRow();
									$vn_id = $va_fetched_row['row_id'];
									
									// if no locale is set for the attribute default it to whatever the locale for the item is
									if (!($vn_locale_id = $va_fetched_row['locale_id'])) {
										$va_tmp = array_keys($va_facet[$vn_id]);
										$vn_locale_id = $va_tmp[0];
									}
									$va_facet[$vn_id][$vn_locale_id]['ca_attribute_'.$va_fetched_row['element_id']][] = $va_fetched_row;
								}
							}
						}
						if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
							return array();
						}
						
						return caExtractValuesByUserLocale($va_facet);
					}
					break;
				# -----------------------------------------------------
				default:
					return null;
					break;
				# -----------------------------------------------------
			}
		}
		# ------------------------------------------------------
		# Get browse results
		# ------------------------------------------------------
		/**
		 * Fetch the number of rows found by the current browse (Can be called before getResults())
		 */
		public function numResults() {
			return $this->opo_ca_browse_cache->numResults();
		}
		# ------------------------------------------------------
		/**
		 * Fetch the subject rows found by an execute()'d browse
		 */
		public function getResults($po_result=null, $pa_options=null) {
			if (!is_array($this->opa_browse_settings)) { return null; }
			$t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true);
			$vb_will_sort = (isset($pa_options['sort']) && $pa_options['sort'] && (($this->getCachedSortSetting() != $pa_options['sort']) || ($this->getCachedSortDirectionSetting() != $pa_options['sort_direction'])));
			
			$vs_pk = $t_item->primaryKey();
			$vs_label_display_field = null;
			
			if(sizeof($va_results =  $this->opo_ca_browse_cache->getResults())) {
				if ($vb_will_sort) {
					$va_results = array_keys($this->sortHits(array_flip($va_results), $pa_options['sort'], (isset($pa_options['sort_direction']) ? $pa_options['sort_direction'] : null)));

					$this->opo_ca_browse_cache->setParameter('table_num', $this->opn_browse_table_num); 
					$this->opo_ca_browse_cache->setParameter('sort', $pa_options['sort']);
					$this->opo_ca_browse_cache->setParameter('sort_direction', $pa_options['sort_direction']);
					
					$this->opo_ca_browse_cache->setResults($va_results);
					$this->opo_ca_browse_cache->save();
				}
				
				if (isset($pa_options['limit']) && ($vn_limit = $pa_options['limit'])) {
					if (isset($pa_options['start']) && ($vn_start = $pa_options['start'])) {
						$va_results = array_slice($va_results, $vn_start, $vn_limit);
					}
				}
			}
			if (!is_array($va_results)) { $va_results = array(); }
			
			if ($po_result) {
				$po_result->init($this->opn_browse_table_num, new WLPlugSearchEngineBrowseEngine($va_results, $this->ops_browse_table_name), array());
				
				return $po_result;
			} else {
				$o_results = new WLPlugSearchEngineBrowseEngine();
				$o_results->init($this->opn_browse_table_num, new WLPlugSearchEngineBrowseEngine($va_results, $this->ops_browse_table_name), array());
				return $o_results;
			}
		}
		# ------------------------------------------------------------------
		/**
		 * Returns string indicating what field the cached browse result is sorted on
		 */
		public function getCachedSortSetting() {
			return $this->opo_ca_browse_cache->getParameter('sort');
		}
		# ------------------------------------------------------------------
		/**
		 * Returns string indicating in which order the cached browse result is sorted
		 */
		public function getCachedSortDirectionSetting() {
			return $this->opo_ca_browse_cache->getParameter('sort_direction');
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function sortHits(&$pa_hits, $ps_field, $ps_direction='asc') {
			if (!in_array($ps_direction, array('asc', 'desc'))) { $ps_direction = 'asc'; }
			if (!is_array($pa_hits) || !sizeof($pa_hits)) { return $pa_hits; }
				
			$t_table = $this->opo_datamodel->getInstanceByTableNum($this->opn_browse_table_num, true);
			$vs_table_pk = $t_table->primaryKey();
			$vs_table_name = $this->ops_browse_table_name;
			
			$va_fields = explode(';', $ps_field);
			$va_joins = array();
			$va_orderbys = array();
			$vs_is_preferred_sql = '';
			
			foreach($va_fields as $vs_field) {
				$va_tmp = explode('.', $vs_field);
				
				if ($va_tmp[0] == $vs_table_name) {
					// sort field is in search table
					
					if (!$t_table->hasField($va_tmp[1])) { 
						// is it an attribute?
						$t_element = new ca_metadata_elements();
						if ($t_element->load(array('element_code' => $va_tmp[1]))) {
							$vn_element_id = $t_element->getPrimaryKey();
							
							if (!($vs_sort_field = Attribute::getSortFieldForDatatype($t_element->get('datatype')))) {
								return $pa_hits;
							}
							
							$vs_sql = "
								SELECT t.*
								FROM {$vs_table_name} t
								INNER JOIN ca_attributes AS attr ON attr.row_id = t.{$vs_table_pk}
								INNER JOIN ca_attribute_values AS attr_vals ON attr_vals.attribute_id = attr.attribute_id
								WHERE
									(t.{$vs_table_pk} IN (".join(", ", array_keys($pa_hits))."))
									AND
									(attr_vals.element_id = ?) AND (attr.table_num = ?) AND (attr_vals.{$vs_sort_field} IS NOT NULL)
								ORDER BY
									attr_vals.{$vs_sort_field} {$ps_direction}
							";
							//print $vs_sql;
							
							$qr_sort = $this->opo_db->query($vs_sql, $vn_element_id, $this->opn_browse_table_num);
			
							$va_sorted_hits = array();
							while($qr_sort->nextRow()) {
								$va_sorted_hits[$vn_id = $qr_sort->get($vs_table_pk, array('binary' => true))] = $qr_sort->getRow();
								unset($pa_hits[$vn_id]);
							}
							
							// Add on hits that aren't sorted because they don't have an attribute associated
							foreach($pa_hits as $vn_id => $va_row) {
								$va_sorted_hits[$vn_id] = $va_row;
							}
							return $va_sorted_hits;
						}
					
						return $pa_hits; 	// return hits unsorted if field is not valid
					} else {	
						$va_field_info = $t_table->getFieldInfo($va_tmp[1]);
						if ($va_field_info['START'] && $va_field_info['END']) {
							$va_orderbys[] = $va_field_info['START'].' '.$ps_direction;
							$va_orderbys[] = $va_field_info['END'].' '.$ps_direction;
						} else {
							$va_orderbys[] = $vs_field.' '.$ps_direction;
						}
					}
				} else {
					// sort field is in related table (only many-one relations are supported)
					$va_rels = $this->opo_datamodel->getRelationships($vs_table_name, $va_tmp[0]);
					if (!$va_rels) { return $pa_hits; }							// return hits unsorted if field is not valid
					$t_rel = $this->opo_datamodel->getInstanceByTableName($va_tmp[0], true);
					if (!$t_rel->hasField($va_tmp[1])) { return $pa_hits; }
					$va_joins[$va_tmp[0]] = 'INNER JOIN '.$va_tmp[0].' ON '.$va_tmp[0].'.'.$va_rels[$vs_table_name][$va_tmp[0]][0][1].' = '.$vs_table_name.'.'.$va_rels[$vs_table_name][$va_tmp[0]][0][0]."\n";
					$va_orderbys[] = $vs_field.' '.$ps_direction;
					
					// if the related supports preferred values (eg. *_labels tables) then only consider those in the sort
					if ($t_rel->hasField('is_preferred')) {
						$vs_is_preferred_sql = " AND ".$va_tmp[0].".is_preferred = 1";
					}
				}
			}
			$vs_join_sql = join("\n", $va_joins);
			
			$vs_sql = "
				SELECT {$vs_table_name}.*
				FROM {$vs_table_name}
				{$vs_join_sql}
				WHERE
					{$vs_table_name}.{$vs_table_pk} IN (".join(", ", array_keys($pa_hits)).")
					{$vs_is_preferred_sql}
				ORDER BY
					".join(', ', $va_orderbys)."
			";
			//print $vs_sql;
			$qr_sort = $this->opo_db->query($vs_sql);
			$va_sorted_hits = array();
			while($qr_sort->nextRow()) {
				$va_sorted_hits[$qr_sort->get($vs_table_pk, array('binary' => true))] = $qr_sort->getRow();
			}
			return $va_sorted_hits;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */
		public function setCachedFacetHTML($ps_cache_key, $ps_content) {
			if (!is_array($va_cache = $this->opo_ca_browse_cache->getParameter('facet_html'))) { $va_cache = array(); }
			$va_cache[$ps_cache_key] =$ps_content;
			$this->opo_ca_browse_cache->setParameter('facet_html', $va_cache);
			$this->opo_ca_browse_cache->save();
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */
		public function getCachedFacetHTML($ps_cache_key) {
			if (!is_array($va_cache = $this->opo_ca_browse_cache->getParameter('facet_html'))) { return null; }
			return isset($va_cache[$ps_cache_key]) ? $va_cache[$ps_cache_key] : null;
		}
		# ------------------------------------------------------------------
		
		# ------------------------------------------------------
		# Browse results buffer
		# ------------------------------------------------------
		/**
		 * Created temporary table for use while performing browse
		 */
		private function _createTempTable($ps_name) {
			$this->opo_db->query("
				CREATE TEMPORARY TABLE {$ps_name} (
					row_id int unsigned not null,
					
					unique key i_row_id (row_id)
				) engine=memory;
			");
			if ($this->opo_db->numErrors()) {
				return false;
			}
			return true;
		}
		# ------------------------------------------------------
		/**
		 * Drops temporary table created while performing browse
		 */
		private function _dropTempTable($ps_name) {
			$this->opo_db->query("
				DROP TABLE {$ps_name};
			");
			if ($this->opo_db->numErrors()) {
				return false;
			}
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Result filters are criteria through which the results of a browse are passed before being
		 * returned to the caller. They are often used to restrict the domain over which browses operate
		 * (for example, ensuring that a browse only returns rows with a certain "status" field value)
		 * You can only filter on actual fields in the subject table (ie. ca_objects.access, ca_objects.status)
		 * not attributes or fields in related tables
		 *
		 * $ps_access_point is the name of an indexed *intrinsic* field
		 * $ps_operator is one of the following: =, <, >, <=, >=, in, not in
		 * $pm_value is the value to apply; this is usually text or a number; for the "in" and "not in" operators this is a comma-separated list of string or numeric values
		 *			
		 *
		 */
		public function addResultFilter($ps_field, $ps_operator, $pm_value) {
			$ps_operator = strtolower($ps_operator);
			if (!in_array($ps_operator, array('=', '<', '>', '<=', '>=', 'in', 'not in'))) { return false; }
			$t_table = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
			if (!$t_table->hasField($ps_field)) { return false; }
			
			$this->opa_result_filters[] = array(
				'field' => $ps_field,
				'operator' => $ps_operator,
				'value' => $pm_value
			);
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function clearResultFilters() {
			$this->opa_result_filters = array();
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function getResultFilters() {
			return $this->opa_result_filters;
		}
		# ------------------------------------------------------
		# Browse subject name (eg. the name of the table we're browsing as configured in browse.conf
		# ------------------------------------------------------
		/**
		 * Returns the display name of the table the engine is configured to browse, or optionally a specified table name
		 *
		 * @param string $ps_subject_table_name Optional table name to get display name for (eg. ca_objects might return "objects"); if not specified the table the instance is configured to browse is used
		 * @return string display name of table
		 */
		public function getBrowseSubjectName($ps_subject_table_name=null) {
			if (!$ps_subject_table_name) { $ps_subject_table_name = $this->ops_browse_table_name; }
			if (is_array($va_tmp = $this->opo_ca_browse_config->getAssoc($ps_subject_table_name)) && (isset($va_tmp['name']) && $va_tmp['name'])){
				return $va_tmp['name'];
			}
			return $this->ops_browse_table_name;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		private function _filterValueToQueryValue($pa_filter) {
			switch(strtolower($pa_filter['operator'])) {
				case '>':
				case '<':
				case '=':
				case '>=':
				case '<=':
					return (int)$pa_filter['value'];
					break;
				case 'in':
				case 'not in':
					$va_tmp = explode(',', $pa_filter['value']);
					$va_values = array();
					foreach($va_tmp as $vs_tmp) {
						$va_values[] = (int)$vs_tmp;
					}
					return "(".join(",", $va_values).")";
					break;
				default:
					return $pa_filter['value'];
					break;
			}
		}
		# ------------------------------------------------------
		# Type filtering
		# ------------------------------------------------------
		/**
		 * When type restrictions are specified, the browse will only browse upon items of the given types. 
		 * If you specify a type that has hierarchical children then the children will automatically be included
		 * in the restriction. You may pass numeric type_id and alphanumeric type codes interchangeably.
		 *
		 * @param array $pa_type_codes_or_ids List of type_id or code values to filter browse by. When set, the browse will only consider items of the specified types. Using a hierarchical parent type will automatically include its children in the restriction. 
		 * @return boolean True on success, false on failure
		 */
		public function setTypeRestrictions($pa_type_codes_or_ids) {
			$this->opa_browse_type_ids = $this->_convertTypeCodesToIDs($pa_type_codes_or_ids);
			$this->opo_ca_browse_cache->setTypeRestrictions($this->opa_browse_type_ids);
			return true;
		}
		# ------------------------------------------------------
		/**
		 *
		 *
		 * @param array $pa_type_codes_or_ids List of type_id or code values to filter browse by. When set, the browse will only consider items of the specified types. Using a hierarchical parent type will automatically include its children in the restriction. 
		 * @return boolean True on success, false on failure
		 */
		private function _convertTypeCodesToIDs($pa_type_codes_or_ids) {
			$vs_md5 = md5(print_r($pa_type_codes_or_ids, true));
			
			if (isset(BrowseEngine::$s_type_id_cache[$vs_md5])) { return BrowseEngine::$s_type_id_cache[$vs_md5]; }
			
			$t_instance = $this->getSubjectInstance();
			$va_type_ids = array();
			
			if (!$pa_type_codes_or_ids) { return false; }
			if (is_array($pa_type_codes_or_ids) && !sizeof($pa_type_codes_or_ids)) { return false; }
			if (!is_array($pa_type_codes_or_ids)) { $pa_type_codes_or_ids = array($pa_type_codes_or_ids); }
			
			$t_list = new ca_lists();
			if (!method_exists($t_instance, 'getTypeListCode')) { return false; }
			if (!($vs_list_name = $t_instance->getTypeListCode())) { return false; }
			$va_type_list = $t_instance->getTypeList();
			
			foreach($pa_type_codes_or_ids as $vs_code_or_id) {
				if (!$vs_code_or_id) { continue; }
				if (!is_numeric($vs_code_or_id)) {
					$vn_type_id = $t_list->getItemIDFromList($vs_list_name, $vs_code_or_id);
				} else {
					$vn_type_id = (int)$vs_code_or_id;
				}
				
				if (!$vn_type_id) { return false; }
				
				if (isset($va_type_list[$vn_type_id]) && $va_type_list[$vn_type_id]) {	// is valid type for this subject
					// See if there are any child types
					$t_item = new ca_list_items($vn_type_id);
					$va_ids = $t_item->getHierarchyChildren(null, array('idsOnly' => true));
					$va_ids[] = $vn_type_id;
					$va_type_ids = array_merge($va_type_ids, $va_ids);
				}
			}
			
			BrowseEngine::$s_type_id_cache[$vs_md5] = $va_type_ids;
			return $va_type_ids;
		}
		# ------------------------------------------------------
		/**
		 * Returns list of type_id values to restrict browse to. Return values are always numeric types, 
		 * never codes, and will include all type_ids to filter on, including children of hierarchical types.
		 *
		 * @return array List of type_id values to restrict browse to.
		 */
		public function getTypeRestrictionList() {
			return $this->opa_browse_type_ids;
		}
		# ------------------------------------------------------
		/**
		 * Removes any specified type restrictions on the browse
		 *
		 * @return boolean Always returns true
		 */
		public function clearTypeRestrictionList() {
			$this->opa_browse_type_ids = null;
			return true;
		}
		# ------------------------------------------------------------------
		#
		# ------------------------------------------------------------------
		/**
		 * 
		 */
		public function getCountsByFieldForSearch($ps_search, $pa_options=null) {
			require_once(__CA_LIB_DIR__.'/core/Search/SearchCache.php');
			
			$vn_tablenum = $this->opo_datamodel->getTableNum($this->ops_tablename);
			
			$o_cache = new SearchCache();
			
			if ($o_cache->load($ps_search, $vn_tablenum, $pa_options)) {
				return $o_cache->getCounts();
			}
			return array();
		}
		# ------------------------------------------------------
		/**
		 * Converts list of relationships type codes and/or numeric ids to an id-only list
		 */
		private function _getRelationshipTypeIDs($pa_relationship_types, $pm_relationship_table_or_id) {
			$t_rel_type = new ca_relationship_types();
			$va_type_list = $pa_relationship_types;
			foreach($va_type_list as $vn_i => $vm_type) {
				if (!trim($vm_type)) { unset($pa_relationship_types[$vn_i]);}
				if (!is_numeric($vm_type)) {
					// try to translate item_value code into numeric id
					if (!($vn_type_id = $t_rel_type->getRelationshipTypeID($pm_relationship_table_or_id, $vm_type))) { continue; }
					unset($pa_relationship_types[$vn_i]);
					$pa_relationship_types[] = $vn_type_id;
				}  else {
					if (!$t_rel_type->load($vm_type)) { continue; }
					$vn_type_id = $t_rel_type->getPrimaryKey();
				}
				
				$va_ids = $t_rel_type->getHierarchyChildren($vn_type_id, array('idsOnly' => true));
				
				if (is_array($va_ids)) {
					foreach($va_ids as $vn_id) {
						$pa_relationship_types[] = $vn_id;
					}
				}
			}
			
			return $pa_relationship_types;
		}
		# ------------------------------------------------------
		# Utilities
		# ------------------------------------------------------
		/**
		 *
		 */
		
		# ------------------------------------------------------
	}
?>