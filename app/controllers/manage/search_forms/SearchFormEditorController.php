<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/SearchFormEditorController.php : 
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
 * ----------------------------------------------------------------------
 */
 
 	require_once(__CA_MODELS_DIR__."/ca_search_forms.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseEditorController.php");
 	
 
 	class SearchFormEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_search_forms';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		protected function _initView() {
 			JavascriptLoadManager::register('bundleableEditor');
 			JavascriptLoadManager::register('sortableUI');
 			JavascriptLoadManager::register('bundleListEditorUI');
 			
 			$va_init = parent::_initView();
 			if (!$va_init[1]->getPrimaryKey()) {
 				$va_init[1]->set('user_id', $this->request->getUserID());
 				$va_init[1]->set('table_num', $this->request->getParameter('table_num', pInteger));
 			}
 			
 			return $va_init;
 		}
 		# -------------------------------------------------------
 		protected function _isFormEditable() {
 			$pn_form_id = $this->request->getParameter('form_id', pInteger);
 			if ($pn_form_id == 0) { return true; }		// allow creation of new forms
 			$t_form = new ca_search_forms();
 			if (!$t_form->haveAccessToForm($this->request->getUserID(), __CA_BUNDLE_DISPLAY_EDIT_ACCESS__, $pn_form_id)) {		// is user allowed to edit form?
 				$this->notification->addNotification(_t("You cannot edit that form"), __NOTIFICATION_TYPE_ERROR__);
 				$this->response->setRedirect(caNavUrl($this->request, 'manage', 'SearchForm', 'ListForms'));
 				return false; 
 			} else {
 				return true;
 			}
 		}
 		# -------------------------------------------------------
 		public function Edit() {
 			if ($this->_isFormEditable()) { return parent::Edit(); } 
 			return false;
 		}
 		# -------------------------------------------------------
 		public function Delete() {
 			if ($this->_isFormEditable()) { return parent::Delete(); } 
 			return false;
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function Info($pa_parameters) {
 			parent::info($pa_parameters);
 			
 			
 			return $this->render('widget_search_form_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>