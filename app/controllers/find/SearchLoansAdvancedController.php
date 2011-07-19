<?php
/* ----------------------------------------------------------------------
 * app/controllers/find/SearchLoansAdvancedController.php : controller for "advanced" loan search request handling
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
 	require_once(__CA_LIB_DIR__."/ca/Browse/LoanBrowse.php");
	require_once(__CA_MODELS_DIR__."/ca_loans.php");
	require_once(__CA_MODELS_DIR__."/ca_sets.php");
 	
 	class SearchLoansAdvancedController extends BaseAdvancedSearchController {
 		# -------------------------------------------------------
 		/**
 		 * Name of subject table (ex. for an object search this is 'ca_objects')
 		 */
 		protected $ops_tablename = 'ca_loans';
 		
 		/** 
 		 * Number of items per search results page
 		 */
 		protected $opa_items_per_page = array(10, 20, 30, 40, 50);
 		 
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
				'list' => _t('list')
			 );
			 
			 $this->opa_sorts = array_merge(array(
			 	'_natural' => _t('relevance'),
			 	'ca_loan_labels.name_sort' => _t('short description'),
			 	'ca_loans.type_id' => _t('type'),
			 	'ca_loans.idno_sort' => _t('idno')
			 ), $this->opa_sorts);
		
			$this->opo_browse = new LoanBrowse($this->opo_result_context->getParameter('browse_id'), 'providence');
		}
 		# -------------------------------------------------------
 		/**
 		 * Advanced search handler (returns search form and results, if any)
 		 * Most logic is contained in the BaseAdvancedSearchController->Index() method; all you usually
 		 * need to do here is instantiate a new subject-appropriate subclass of BaseSearch 
 		 * (eg. ObjectSearch for objects, LoanSearch for loans) and pass it to BaseAdvancedSearchController->Index() 
 		 */ 
 		public function Index($pa_options=null) {
 			JavascriptLoadManager::register('imageScroller');
 			JavascriptLoadManager::register('tabUI');
 			return parent::Index($this->opo_browse, $pa_options);
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns string representing the name of the item the search will return
 		 *
 		 * If $ps_mode is 'singular' [default] then the singular version of the name is returned, otherwise the plural is returned
 		 */
 		public function searchName($ps_mode='singular') {
 			return ($ps_mode == 'singular') ? _t("loan") : _t("loans");
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		/**
 		 * Returns "search tools" widget
 		 */ 
 		public function Tools($pa_parameters) {
 			return parent::Tools($pa_parameters, new LoanSearch());
 		}
 		# -------------------------------------------------------
 	}
 ?>
