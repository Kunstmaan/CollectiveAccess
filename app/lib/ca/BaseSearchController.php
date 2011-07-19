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
	require_once(__CA_LIB_DIR__."/ca/BaseRefineableSearchController.php");
	require_once(__CA_LIB_DIR__."/ca/Browse/ObjectBrowse.php");
	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
	require_once(__CA_MODELS_DIR__."/ca_search_forms.php");
 	require_once(__CA_APP_DIR__.'/helpers/accessHelpers.php');
 	
 	class BaseSearchController extends BaseRefineableSearchController {
 		# -------------------------------------------------------
 		protected $opb_uses_hierarchy_browser = false;
 		protected $opo_datamodel;
 		protected $ops_find_type;
 		
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			if ($this->ops_tablename) {
				if ($va_items_per_page_config = $po_request->config->getList('items_per_page_options_for_'.$this->ops_tablename.'_search')) {
					$this->opa_items_per_page = $va_items_per_page_config;
				}
				if (($vn_items_per_page_default = (int)$po_request->config->get('items_per_page_default_for_'.$this->ops_tablename.'_search')) > 0) {
					$this->opn_items_per_page_default = $vn_items_per_page_default;
				} else {
					$this->opn_items_per_page_default = $this->opa_items_per_page[0];
				}
				
				$this->ops_view_default = null;
				if ($vs_view_default = $po_request->config->get('view_default_for_'.$this->ops_tablename.'_search')) {
					$this->ops_view_default = $vs_view_default;
				}
	
				$va_sortable_elements = ca_metadata_elements::getSortableElements($this->ops_tablename, $this->opn_type_restriction_id);
	
				$this->opa_sorts = array();
				foreach($va_sortable_elements as $vn_element_id => $va_sortable_element) {
					$this->opa_sorts[$this->ops_tablename.'.'.$va_sortable_element['element_code']] = $va_sortable_element['display_label'];
				}
			}
 		}
 		# -------------------------------------------------------
 		/**
 		 * Options:
 		 *		appendToSearch = optional text to be AND'ed wuth current search expression
 		 *		output_format = determines format out search result output. "PDF" and "HTML" are currently supported; "HTML" is the default
 		 *		view = view with path relative to controller to use overriding default ("search/<table_name>_search_basic_html.php")
 		 *		vars = associative array with key value pairs to assign to the view
 		 */
 		public function Index($po_search, $pa_options=null) {
 			if (isset($pa_options['saved_search']) && $pa_options['saved_search']) {
 				$this->opo_result_context->setSearchExpression($pa_options['saved_search']['search']);
 			}
 			parent::Index($po_search, $pa_options);
 			JavascriptLoadManager::register('browsable');	// need this to support browse panel when filtering/refining search results
 			$t_model = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
 			$va_access_values = caGetUserAccessValues($this->request);
 			
 			// Get elements of result context
 			$vn_page_num 			= $this->opo_result_context->getCurrentResultsPageNumber();
 			$vs_search 				= $this->opo_result_context->getSearchExpression();
 			$vb_is_new_search		= $this->opo_result_context->isNewSearch();
			if (!($vn_items_per_page = $this->opo_result_context->getItemsPerPage())) { 
 				$vn_items_per_page = $this->opn_items_per_page_default; 
 				$this->opo_result_context->setItemsPerPage($vn_items_per_page);
 			}
 			
 			if (!($vs_view 			= $this->opo_result_context->getCurrentView())) { 
 				$vs_view = $this->ops_view_default ? $this->ops_view_default : array_shift(array_keys($this->opa_views)); 
 				$this->opo_result_context->setCurrentView($vs_view);
 			}
 			if (!isset($this->opa_views[$vs_view])) { $vs_view = array_shift(array_keys($this->opa_views)); }
 			
 			if (!($vs_sort 	= $this->opo_result_context->getCurrentSort())) { $vs_sort = array_shift(array_keys($this->opa_sorts)); }
 			$vs_sort_direction = $this->opo_result_context->getCurrentSortDirection();
			$vn_display_id 	= $this->opo_result_context->getCurrentBundleDisplay();
 			
 			if (!$this->opn_type_restriction_id) { $this->opn_type_restriction_id = ''; }
 			$this->view->setVar('type_id', $this->opn_type_restriction_id);
 			// Get attribute sorts
 			$va_sortable_elements = ca_metadata_elements::getSortableElements($this->ops_tablename, $this->opn_type_restriction_id);
 			
 			if (!is_array($this->opa_sorts)) { $this->opa_sorts = array(); }
 			foreach($va_sortable_elements as $vn_element_id => $va_sortable_element) {
 				$this->opa_sorts[$this->ops_tablename.'.'.$va_sortable_element['element_code']] = $va_sortable_element['display_label'];
 			}
 			
 			if ($pa_options['appendToSearch']) {
 				$vs_append_to_search .= " AND (".$pa_options['appendToSearch'].")";
 			}
			//
			// Execute the search
			//
			if($vs_search && ($vs_search != "")){ /* any request? */
				$va_search_opts = array(
					'sort' => $vs_sort, 
					'sort_direction' => $vs_sort_direction, 
					'appendToSearch' => $vs_append_to_search,
					'checkAccess' => $va_access_values,
					'no_cache' => $vb_is_new_search
				);
				
				if ($vb_is_new_search ||isset($pa_options['saved_search']) || (is_subclass_of($po_search, "BrowseEngine") && !$po_search->numCriteria()) ) {
					$vs_browse_classname = get_class($po_search);
 					$po_search = new $vs_browse_classname;
 					if (is_subclass_of($po_search, "BrowseEngine")) {
 						$po_search->addCriteria('_search', $vs_search);
 					}
 				}
 				
 				if ($this->opn_type_restriction_id) {
 					$po_search->setTypeRestrictions(array($this->opn_type_restriction_id));
 				}
 				
 				if (is_subclass_of($po_search, "BrowseEngine")) {
					$po_search->execute($va_search_opts);
					$this->opo_result_context->setParameter('browse_id', $po_search->getBrowseID());
					
					$vo_result = $po_search->getResults($va_search_opts);
				} else {
					$vo_result = $po_search->search($vs_search, $va_search_opts);
				}
				
				// Only prefetch what we need
				$vo_result->setOption('prefetch', $vn_items_per_page);
				$vo_result->setOption('dontPrefetchAttributes', true);
 		
				
				//
				// Handle details of partitioning search results by type, if required
				//
				if ((bool)$this->request->config->get('search_results_partition_by_type')) {
					$va_type_counts = $vo_result->getResultCountForFieldValues(array('ca_objects.type_id'));
					$this->view->setVar('counts_by_type', $va_type_counts['ca_objects.type_id']);
					
					$vn_show_type_id = $this->opo_result_context->getParameter('show_type_id');
					if (!isset($va_type_counts['ca_objects.type_id'][$vn_show_type_id])) {
						$vn_show_type_id = array_shift(array_keys($va_type_counts['ca_objects.type_id']));
					}
					$this->view->setVar('show_type_id', $vn_show_type_id);
					$vo_result->filterResult('ca_objects.type_id', $vn_show_type_id);
				}
 				if($vb_is_new_search) {
					$va_found_item_ids = array();
					$vs_table_pk = $t_model->primaryKey();
					while($vo_result->nextHit()) {
						$va_found_item_ids[] = $vo_result->get($vs_table_pk);
					}
					$this->opo_result_context->setResultList($va_found_item_ids);
					
					$vn_page_num = 1;
				}
 				$this->view->setVar('num_hits', $vo_result->numHits());
 				$this->view->setVar('num_pages', $vn_num_pages = ceil($vo_result->numHits()/$vn_items_per_page));
 				if ($vn_page_num > $vn_num_pages) { $vn_page_num = 1; }
 				
 				$vo_result->seek(($vn_page_num - 1) * $vn_items_per_page);
 				$this->view->setVar('page', $vn_page_num);
 				$this->view->setVar('search', $vs_search);
 				$this->view->setVar('result', $vo_result);
 			}
 			//
 			// Set up view for display of results
 			//
 			switch($pa_options['output_format']) {
 				# ------------------------------------
 				case 'PDF':
 					$this->_genPDF($vo_result, $this->request->getParameter("label_form", pString), $vs_search, $vs_search);
 					break;
 				# ------------------------------------
 				case 'EXPORT':
 					$this->_genExport($vo_result, $this->request->getParameter("export_format", pString), $vs_search, $vs_search);
 					break;
 				# ------------------------------------
 				case 'HTML': 
				default:
					// generate type menu and type value list
					$t_model = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
					if (method_exists($t_model, "getTypeList")) {
						$this->view->setVar('type_list', $t_model->getTypeList());
					}
					if ($this->opb_uses_hierarchy_browser) {
						if (sizeof($t_model->getHierarchyList()) > 0) {
							JavascriptLoadManager::register('hierBrowser');
							
							// only for interfaces that use the hierarchy browser
							$t_list = new ca_lists();
							if ($vs_type_list_code = $t_model->getTypeListCode()) {
								$this->view->setVar('num_types', $t_list->numItemsInList($vs_type_list_code));
								$this->view->setVar('type_menu',  $t_list->getListAsHTMLFormElement($vs_type_list_code, 'type_id', array('id' => 'hierTypeList')));
							}
							
							// set last browse id for hierarchy browser
							$this->view->setVar('browse_last_id', intval($this->request->session->getVar($this->ops_tablename.'_browse_last_id')));
						} else {
							$this->view->setVar('no_hierarchies_defined', 1);
							$this->notification->addNotification(_t("No hierarchies are configured for %1", $t_model->getProperty('NAME_PLURAL')), __NOTIFICATION_TYPE_ERROR__);
						}
					}
					
					$this->view->setVar('views', $this->opa_views);	// pass view list to view for rendering
					$this->view->setVar('current_view', $vs_view);
					
					$this->view->setVar('sorts', $this->opa_sorts);	// pass sort list to view for rendering
					$this->view->setVar('current_sort', $vs_sort);
					$this->view->setVar('current_sort_direction', $vs_sort_direction);
					
 					$this->view->setVar('current_items_per_page', $vn_items_per_page);
					$this->view->setVar('items_per_page', $this->opa_items_per_page);
					
					$this->view->setVar('t_subject', $t_model);
					
					$this->view->setVar('mode_name', _t('search'));
					$this->view->setVar('mode', 'search');
					$this->view->setVar('mode_type_singular', $this->searchName('singular'));
					$this->view->setVar('mode_type_plural', $this->searchName('plural'));
					
					$this->view->setVar('search_history', $this->opo_result_context->getSearchHistory());
			
					$this->view->setVar('result_context', $this->opo_result_context);
					$this->view->setVar('uses_hierarchy_browser', $this->usesHierarchyBrowser());
					
					$this->view->setVar('access_values', $va_access_values);
					
					$this->opo_result_context->setAsLastFind();
					$this->opo_result_context->saveContext();
					$this->view->setVar('browse', $po_search);
				
					if (isset($pa_options['vars']) && is_array($pa_options['vars'])) { 
						foreach($pa_options['vars'] as $vs_key => $vs_val) {
							$this->view->setVar($vs_key, $vs_val);
						}
					}
					if (isset($pa_options['view']) && $pa_options['view']) { 
						$this->render($pa_options['view']);
					} else {
						$this->render('Search/'.$this->ops_tablename.'_search_basic_html.php');
					}
					break;
				# ------------------------------------
			}
 		}
 		# -------------------------------------------------------
		# Navigation (menu bar)
		# -------------------------------------------------------
 		public function _genTypeNav($pa_params) {
 			$t_subject = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
 			
 			$t_list = new ca_lists();
 			$t_list->load(array('list_code' => $t_subject->getTypeListCode()));
 			
 			$t_list_item = new ca_list_items();
 			$t_list_item->load(array('list_id' => $t_list->getPrimaryKey(), 'parent_id' => null));
 			$va_hier = caExtractValuesByUserLocale($t_list_item->getHierarchyWithLabels());
 			
 			$va_types = array();
 			if (is_array($va_hier)) {
 				
 				$va_types_by_parent_id = array();
 				$vn_root_id = null;
				foreach($va_hier as $vn_item_id => $va_item) {
					if (!$vn_root_id) { $vn_root_id = $va_item['parent_id']; continue; }
					$va_types_by_parent_id[$va_item['parent_id']][] = $va_item;
				}
				foreach($va_hier as $vn_item_id => $va_item) {
					if ($va_item['parent_id'] != $vn_root_id) { continue; }
					// does this item have sub-items?
					if (isset($va_item['item_id']) && isset($va_types_by_parent_id[$va_item['item_id']]) && is_array($va_types_by_parent_id[$va_item['item_id']])) {
						$va_subtypes = $this->_getSubTypes($va_types_by_parent_id[$va_item['item_id']], $va_types_by_parent_id);
					} else {
						$va_subtypes = array();
					}
					$va_types[] = array(
						'displayName' =>$va_item['name_plural'],
						'parameters' => array(
							'type_id' => $va_item['item_id']
						),
						'is_enabled' => 1,
						'navigation' => $va_subtypes
					);
				}
			}
 			return $va_types;
 		}
 		# ------------------------------------------------------------------
		private function _getSubTypes($pa_subtypes, $pa_types_by_parent_id) {
			$va_subtypes = array();
			foreach($pa_subtypes as $vn_i => $va_type) {
				if (isset($pa_types_by_parent_id[$va_type['item_id']]) && is_array($pa_types_by_parent_id[$va_type['item_id']])) {
					$va_subsubtypes = $this->_getSubTypes($pa_types_by_parent_id[$va_type['item_id']], $pa_types_by_parent_id);
				} else {
					$va_subsubtypes = array();
				}
				$va_subtypes[$va_type['item_id']] = array(
					'displayName' => $va_type['name_singular'],
					'parameters' => array(
						'type_id' => $va_type['item_id']
					),
					'is_enabled' => 1,
					'navigation' => $va_subsubtypes
				);
			}
			
			return $va_subtypes;
		}
 		# -------------------------------------------------------
 		/**
 		 * Returns string representing the name of the item the search will return
 		 *
 		 * If $ps_mode is 'singular' [default] then the singular version of the name is returned, otherwise the plural is returned
 		 */
 		public function searchName($ps_mode='singular') {
 			// MUST BE OVERRIDDEN 
 			return "undefined";
 		}
 		# -------------------------------------------------------
 		public function usesHierarchyBrowser() {
 			return (bool)$this->opb_uses_hierarchy_browser;
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function Tools($pa_parameters, $po_search) {
 			parent::Tools($pa_parameters, $po_search);
 			
			$this->view->setVar('mode_name', _t('search'));
			$this->view->setVar('mode_type_singular', $this->searchName('singular'));
			$this->view->setVar('mode_type_plural', $this->searchName('plural'));
			
			$this->view->setVar('table_name', $this->ops_tablename);
			$this->view->setVar('find_type', $this->ops_find_type);
 			
			$this->view->setVar('search_history', $this->opo_result_context->getSearchHistory());
 			
 			return $this->render('Search/widget_'.$this->ops_tablename.'_search_tools.php', true);
 		}
 		# -------------------------------------------------------
 	}
?>