<?php
/* ----------------------------------------------------------------------
 * app/controllers/find/AdvancedSearchObjectsController.php : controller for "advanced" object search request handling
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__."/ca/BaseAdvancedSearchController.php");
 	require_once(__CA_LIB_DIR__."/ca/Browse/ObjectBrowse.php");
	require_once(__CA_MODELS_DIR__."/ca_objects.php");
	require_once(__CA_MODELS_DIR__."/ca_sets.php");
 	
 	class SearchObjectsAdvancedController extends BaseAdvancedSearchController {
 		# -------------------------------------------------------
 		/**
 		 * Name of subject table (ex. for an object search this is 'ca_objects')
 		 */
 		protected $ops_tablename = 'ca_objects';
 		
 		/** 
 		 * Number of items per search results page
 		 */
 		protected $opa_items_per_page = array(8, 16, 24, 32);
 		 
 		/**
 		 * List of search-result views supported for this find
 		 * Is associative array: values are view labels, keys are view specifier to be incorporated into view name
 		 */ 
 		protected $opa_views;
 		
 		/**
 		 * List of available search-result sorting fields
 		 * Is associative array: values are display names for fields, keys are full fields names (table.field) to be used as sort
 		 */
 		protected $opa_sorts;
 		
 		/**
 		 * Name of "find" used to defined result context for ResultContext object
 		 * Must be unique for the table and have a corresponding entry in find_navigation.conf
 		 */
 		protected $ops_find_type = 'advanced_search';
 		 
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
			$this->opa_views = array(
				'thumbnail' => _t('thumbnails'),
				'full' => _t('full'),
				'list' => _t('list')
			 );
			 
			 $this->opa_sorts = array_merge(array(
			 	'_natural' => _t('relevance'),
			 	'ca_object_labels.name_sort' => _t('title'),
			 	'ca_objects.type_id' => _t('type'),
			 	'ca_objects.idno_sort' => _t('idno')
			 ), $this->opa_sorts);
			 $this->opo_browse = new ObjectBrowse($this->opo_result_context->getParameter('browse_id'), 'providence');
		}
 		# -------------------------------------------------------
 		/**
 		 * Advanced search handler (returns search form and results, if any)
 		 * Most logic is contained in the BaseAdvancedSearchController->Index() method; all you usually
 		 * need to do here is instantiate a new subject-appropriate subclass of BaseSearch 
 		 * (eg. ObjectSearch for objects, EntitySearch for entities) and pass it to BaseAdvancedSearchController->Index() 
 		 */ 
 		public function Index($pa_options=null) {
 			JavascriptLoadManager::register('imageScroller');
 			JavascriptLoadManager::register('tabUI');
 			JavascriptLoadManager::register('panel');
 			return parent::Index($this->opo_browse, $pa_options);
 		}
 		# -------------------------------------------------------
 		/**
 		 * QuickLook
 		 */
 		public function QuickLook() {
 			$vn_object_id = $this->request->getParameter('object_id', pInteger);
 			$this->view->setVar('object_id', $vn_object_id);
 			$t_object = new ca_objects($vn_object_id);
 			$va_reps = $t_object->getRepresentations(array('large', 'medium', 'preview'));
 			$this->view->setVar('reps', $va_reps);
 			
 			$va_labels = $t_object->getPreferredLabels(null, false);
 			$this->view->setVar('labels', $va_labels);
 			
 			$this->view->setVar('idno', $t_object->get('idno'));
 			
 			$this->view->setVar('typename', $t_object->getTypeName());
 			
 			$t_set = new ca_sets();
 			$va_available_sets = caExtractValuesByUserLocale($t_set->getAvailableSetsForItem($t_object->tableNum(), $vn_object_id, array('user_id' => $this->request->getUserID())), null, null, array());
 			$va_item_sets = caExtractValuesByUserLocale($t_set->getSetsForItem($t_object->tableNum(), $vn_object_id, array('user_id' => $this->request->getUserID())), null, null, array());
 			
 			$this->view->setVar('t_set', $t_set);
 			$this->view->setvar('t_set_item', new ca_set_items());
 			$this->view->setvar('t_set_item_label', new ca_set_item_labels());
 			
 			$this->view->setVar('available_sets', $va_available_sets);		// flattened list of sets this item is *not* in
 			$this->view->setVar('item_sets', $va_item_sets);				// flattened list of sets this item *is* in
 			
 			$va_available_set_options = array();
 			foreach($va_available_sets as $va_set) {
 				if(unicode_strlen($vs_name = $va_set['name']) > 40) {
 					$vs_name = unicode_substr($va_set['name'], 0, 40).'...';
 				}
 				$va_available_set_options[$vs_name] = $va_set['set_id'];
 			}
 			$va_item_set_options = array();
 			foreach($va_item_sets as $va_set) {
 				$va_item_set_options[$va_set['name']] = $va_set['set_id'];
 			}
 			$this->view->setVar('item_set_options', $va_item_set_options);
 			$this->view->setVar('available_set_options', $va_available_set_options);
 			
 			$this->render('ca_objects_quick_look_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns string representing the name of the item the search will return
 		 *
 		 * If $ps_mode is 'singular' [default] then the singular version of the name is returned, otherwise the plural is returned
 		 */
 		public function searchName($ps_mode='singular') {
 			return ($ps_mode == 'singular') ? _t("object") : _t("objects");
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		/**
 		 * Returns "search tools" widget
 		 */ 
 		public function Tools($pa_parameters) {
 			return parent::Tools($pa_parameters, new ObjectSearch());
 		}
 		# -------------------------------------------------------
 	}
 ?>
