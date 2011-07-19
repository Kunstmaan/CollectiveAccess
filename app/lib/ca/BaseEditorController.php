<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseEditorController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2010 Whirl-i-Gig
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
 
 	require_once(__CA_MODELS_DIR__."/ca_editor_uis.php");
 	require_once(__CA_MODELS_DIR__."/ca_attribute_values.php");
 	require_once(__CA_MODELS_DIR__."/ca_metadata_elements.php");
 	require_once(__CA_MODELS_DIR__."/ca_bundle_mappings.php");
 	require_once(__CA_MODELS_DIR__."/ca_bundle_displays.php");
 	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
 	require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");
 	require_once(__CA_LIB_DIR__."/ca/ResultContext.php");
	require_once(__CA_LIB_DIR__."/ca/ImportExport/DataExporter.php");
 
 	class BaseEditorController extends ActionController {
 		# -------------------------------------------------------
 		protected $opo_datamodel;
 		protected $opo_app_plugin_manager;
 		protected $opo_result_context;
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
			
 			$this->opo_datamodel = Datamodel::load();
 			$this->opo_app_plugin_manager = new ApplicationPluginManager();
 			$this->opo_result_context = new ResultContext($po_request, $this->ops_table_name, ResultContext::getLastFind($po_request, $this->ops_table_name));
 		}
 		# -------------------------------------------------------
 		public function Edit($pa_values=null) {
 			list($vn_subject_id, $t_subject, $t_ui, $vn_parent_id) = $this->_initView();
 			
 			if ((!$t_subject->getPrimaryKey()) && ($vn_subject_id)) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2500?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			if(is_array($pa_values)) {
 				foreach($pa_values as $vs_key => $vs_val) {
 					$t_subject->set($vs_key, $vs_val);
 				}
 			}
 			
 			// set "context" id from those editors that need to restrict idno lookups to within the context of another field value (eg. idno's for ca_list_items are only unique within a given list_id)
 			if ($vs_idno_context_field = $t_subject->getProperty('ID_NUMBERING_CONTEXT_FIELD')) {
 				if ($vn_subject_id > 0) {
 					$this->view->setVar('_context_id', $t_subject->get($vs_idno_context_field));
 				} else {
 					if ($vn_parent_id > 0) {
 						$t_parent = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name);
 						if ($t_parent->load($vn_parent_id)) {
 							$this->view->setVar('_context_id', $t_parent->get($vs_idno_context_field));
 						}
 					}
 				}
 			}
 			
 			// get default screen
 			
 			if (!$vn_type_id = $t_subject->getTypeID()) {
 				$vn_type_id = $this->request->getParameter($t_subject->getTypeFieldName(), pInteger);
 			}
 			if (!$this->request->getActionExtra()) {
 				$va_nav = $t_ui->getScreensAsNavConfigFragment($this->request, $vn_type_id, $this->request->getModulePath(), $this->request->getController(), $this->request->getAction(),
					array(),
					array()
				);
 				$this->request->setActionExtra($va_nav['defaultScreen']);
 			}
			$this->view->setVar('t_ui', $t_ui);
			if (!$t_ui->getPrimaryKey()) {
				$this->notification->addNotification(_t('There is no configuration available for this editor. Check your system configuration and ensure there is at least one valid configuration for this type of editor.'), __NOTIFICATION_TYPE_ERROR__);
			}
			if ($vn_subject_id) { $this->request->session->setVar($this->ops_table_name.'_browse_last_id', $vn_subject_id); } 	// set last edited
			
			# trigger "EditItem" hook 
			$this->opo_app_plugin_manager->hookEditItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject));
			$this->render('screen_html.php');
 		}
 		# -------------------------------------------------------
 		public function Save() {
 			list($vn_subject_id, $t_subject, $t_ui, $vn_parent_id) = $this->_initView();
 			
 			$vs_auth_table_name = $this->ops_table_name;
 			if (in_array($this->ops_table_name, array('ca_object_representations', 'ca_representation_annotations'))) { $vs_auth_table_name = 'ca_objects'; }
 			 			
 			if(!sizeof($_POST)) {
 				$this->notification->addNotification(_t("Cannot save using empty request. Are you using a bookmark?"), __NOTIFICATION_TYPE_ERROR__);	
 				$this->render('screen_html.php');
 				return;
 			}
 			
 			// set "context" id from those editors that need to restrict idno lookups to within the context of another field value (eg. idno's for ca_list_items are only unique within a given list_id)
 			$vn_context_id = null;
 			if ($vs_idno_context_field = $t_subject->getProperty('ID_NUMBERING_CONTEXT_FIELD')) {
 				if ($vn_subject_id > 0) {
 					$this->view->setVar('_context_id', $vn_context_id = $t_subject->get($vs_idno_context_field));
 				} else {
 					if ($vn_parent_id > 0) {
 						$t_parent = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name);
 						if ($t_parent->load($vn_parent_id)) {
 							$this->view->setVar('_context_id', $vn_context_id = $t_parent->get($vs_idno_context_field));
 						}
 					}
 				}
 				
 				if ($vn_context_id) { $t_subject->set($vs_idno_context_field, $vn_context_id); }
 			}
 			
 			if (!$vs_type_name = $t_subject->getTypeName()) {
 				$vs_type_name = $t_subject->getProperty('NAME_SINGULAR');
 			}
 			
 			if ($vn_subject_id && !$t_subject->getPrimaryKey()) {
 				$this->notification->addNotification(_t("%1 does not exist", $vs_type_name), __NOTIFICATION_TYPE_ERROR__);	
 				return;
 			}
 			
 			# trigger "BeforeSaveItem" hook 
			$this->opo_app_plugin_manager->hookBeforeSaveItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject));
 			
 			
 			$vb_save_rc = $t_subject->saveBundlesForScreen($this->request->getActionExtra(), $this->request);
			$this->view->setVar('t_ui', $t_ui);
		
			if(!$vn_subject_id) {
				$vn_subject_id = $t_subject->getPrimaryKey();
				if (!$vb_save_rc) {
					$vs_message = _t("Could not save %1", $vs_type_name);
				} else {
					$vs_message = _t("Added %1", $vs_type_name);
					$this->request->setParameter($t_subject->primaryKey(), $vn_subject_id, 'GET');
					$this->view->setVar($t_subject->primaryKey(), $vn_subject_id);
					$this->view->setVar('subject_id', $vn_subject_id);
					$this->request->session->setVar($this->ops_table_name.'_browse_last_id', $vn_subject_id);	// set last edited
				}
				
			} else {
 				$vs_message = _t("Saved changes to %1", $vs_type_name);
 			}
 			
 			$va_errors = $this->request->getActionErrors();							// all errors from all sources
 			$va_general_errors = $this->request->getActionErrors('general');		// just "general" errors - ones that are not attached to a specific part of the form
 			if (is_array($va_general_errors) && sizeof($va_general_errors) > 0) {
 				foreach($va_general_errors as $o_e) {
 					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
 				}
			}
 			if(sizeof($va_errors) - sizeof($va_general_errors) > 0) {
 				$va_error_list = array();
 				$vb_no_save_error = false;
 				foreach($va_errors as $o_e) {
 					//$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
 					$va_error_list[$o_e->getErrorDescription()] = "<li>".$o_e->getErrorDescription()."</li>\n";
 					
 					switch($o_e->getErrorNumber()) {
 						case 1100:	// duplicate/invalid idno
 							if (!$vn_subject_id) {		// can't save new record if idno is not valid (when updating everything but idno is saved if it is invalid)
 								$vb_no_save_error = true;
 							}
 							break;
 					}
 				}
 				if ($vb_no_save_error) {
 					$this->notification->addNotification("There are errors preventing <strong>ALL</strong> information from being saved. Correct the problems and click \"save\" again.\n<ul>".join("\n", $va_error_list)."</ul>", __NOTIFICATION_TYPE_ERROR__);
 				} else {
 					$this->notification->addNotification($vs_message, __NOTIFICATION_TYPE_INFO__);	
 					$this->notification->addNotification("There are errors preventing information in specific fields from being saved as noted below.\n<ul>".join("\n", $va_error_list)."</ul>", __NOTIFICATION_TYPE_ERROR__);
 				}
 			} else {
				$this->notification->addNotification($vs_message, __NOTIFICATION_TYPE_INFO__);	
 			}
 			# trigger "SaveItem" hook 
 		
			$this->opo_app_plugin_manager->hookSaveItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject));
 			
 			$this->render('screen_html.php');
 		}
 		# -------------------------------------------------------
 		public function Delete() {
 			list($vn_subject_id, $t_subject, $t_ui) = $this->_initView();
 			
 			if (!$vn_subject_id) { return; }
 			
 			if (!$vs_type_name = $t_subject->getTypeName()) {
 				$vs_type_name = $t_subject->getProperty('NAME_SINGULAR');
 			}
 			
 			// get parent_id, if it exists, prior to deleting so we can
 			// set the browse_last_id parameter to something sensible
 			$vn_parent_id = null;
 			if ($vs_parent_fld = $t_subject->getProperty('HIERARCHY_PARENT_ID_FLD')) {
 				$vn_parent_id = $t_subject->get($vs_parent_fld);
 			}
 			
 			if ($vn_subject_id && !$t_subject->getPrimaryKey()) {
 				$this->notification->addNotification(_t("%1 does not exist", $vs_type_name), __NOTIFICATION_TYPE_ERROR__);	
 				return;
 			}
 			
 			// Don't allow deletion of roots in simple mono-hierarchies... that's bad.
 			if (!$vn_parent_id && (in_array($t_subject->getProperty('HIERARCHY_TYPE'), array(__CA_HIER_TYPE_SIMPLE_MONO__, __CA_HIER_TYPE_MULTI_MONO__)))) {
 				$this->notification->addNotification(_t("Cannot delete root of hierarchy"), __NOTIFICATION_TYPE_ERROR__);	
 				return;
 			}
 			
 			if ($vb_confirm = ($this->request->getParameter('confirm', pInteger) == 1) ? true : false) {
 				$vb_we_set_transation = false;
 				if (!$t_subject->inTransaction()) { 
 					$t_subject->setTransaction($o_t = new Transaction());
 					$vb_we_set_transation = true;
 				}
 				$t_subject->setMode(ACCESS_WRITE);
 				if ($this->_beforeDelete($t_subject)) {
 					$t_subject->delete(true);
 				}
 				$vb_after_res = $this->_afterDelete($t_subject);
 				if ($vb_we_set_transation) {
 					if (!$vb_after_res) {
 						$o_t->rollbackTransaction();	
 					} else {
 						$o_t->commitTransaction();
 					}
 				}
 			}
 			$this->view->setVar('confirmed', $vb_confirm);
 			if ($t_subject->numErrors()) {
 				foreach($t_subject->errors() as $o_e) {
 					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);	
 				}
 			} else {
 				if ($vb_confirm) {
 					$this->notification->addNotification(_t("%1 was deleted", $vs_type_name), __NOTIFICATION_TYPE_INFO__);
 					
 					// update result list since it has changed
 					$this->opo_result_context->removeIDFromResults($vn_subject_id);
  					$this->opo_result_context->saveContext();
  				
  				
 					// clear subject_id - it's no longer valid
 					$t_subject->clear();
 					$this->view->setVar($t_subject->primaryKey(), null);
 					$this->request->setParameter($t_subject->primaryKey(), null, 'PATH');
 					
 					// set last browse id for hierarchy browser
 					$this->request->session->setVar($this->ops_table_name.'_browse_last_id', $vn_parent_id);

					# trigger "DeleteItem" hook 
					$this->opo_app_plugin_manager->hookDeleteItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject));
 				}
 			}
 			
			$this->view->setVar('subject_name', $t_subject->getLabelForDisplay(false));
 			
 			$this->render('delete_html.php');
 		}
 		# -------------------------------------------------------
 		public function Summary() {
 			JavascriptLoadManager::register('tableList');
 			list($vn_subject_id, $t_subject) = $this->_initView();
 			
 			$t_display = new ca_bundle_displays();
 			$va_displays = $t_display->getBundleDisplays(array('table' => $t_subject->tableNum(), 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__));
 			
 			if (!($vn_display_id = $this->request->getParameter('display_id', pInteger))) {
 				if (!($vn_display_id = $this->request->user->getVar($t_subject->tableName().'_summary_display_id'))) {
 					$va_tmp = array_keys($va_displays);
 					$vn_display_id = $va_tmp[0];
 				}
 			}
 			
			$this->view->setVar('bundle_displays', $va_displays);
			$this->view->setVar('t_display', $t_display);
			
			// Check validity and access of specified display
 			if ($t_display->load($vn_display_id) && ($t_display->haveAccessToDisplay($this->request->getUserID(), __CA_BUNDLE_DISPLAY_READ_ACCESS__))) {
				$this->view->setVar('display_id', $vn_display_id);
				
				$va_placements = $t_display->getPlacements(array('returnAllAvailableIfEmpty' => true, 'table' => $t_subject->tableNum(), 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__));
				
				$va_display_list = array();
				foreach($va_placements as $vn_placement_id => $va_display_item) {
					$va_settings = caUnserializeForDatabase($va_display_item['settings']);
					
					// get column header text
					$vs_header = $va_display_item['display'];
					if (isset($va_settings['label']) && is_array($va_settings['label'])) {
						if ($vs_tmp = array_shift(caExtractValuesByUserLocale(array($va_settings['label'])))) { $vs_header = $vs_tmp; }
					}
					
					$va_display_list[$vn_placement_id] = array(
						'placement_id' => $vn_placement_id,
						'bundle_name' => $va_display_item['bundle_name'],
						'display' => $vs_header,
						'settings' => $va_settings
					);
				}
				
				$this->view->setVar('placements', $va_display_list);
				
				$this->request->user->setVar($t_subject->tableName().'_summary_display_id', $vn_display_id);
			} else {
				$this->view->setVar('display_id', null);
				$this->view->setVar('placements', array());
			}
 			$this->render('summary_html.php');
 		}
		# -------------------------------------------------------
		public function PrintSummary() {
			require_once(__CA_LIB_DIR__."/core/Print/html2pdf/html2pdf.class.php");

			JavascriptLoadManager::register('tableList');
 			list($vn_subject_id, $t_subject) = $this->_initView();

 			$t_display = new ca_bundle_displays();
 			$va_displays = $t_display->getBundleDisplays(array('table' => $t_subject->tableNum(), 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__));

 			if (!($vn_display_id = $this->request->getParameter('display_id', pInteger))) {
 				if (!($vn_display_id = $this->request->user->getVar($t_subject->tableName().'_summary_display_id'))) {
 					$va_tmp = array_keys($va_displays);
 					$vn_display_id = $va_tmp[0];
 				}
 			}

 			$this->view->setVar('t_display', $t_display);
 			$this->view->setVar('bundle_displays', $va_displays);
 			
 			// Check validity and access of specified display
 			if ($t_display->load($vn_display_id) && ($t_display->haveAccessToDisplay($this->request->getUserID(), __CA_BUNDLE_DISPLAY_READ_ACCESS__))) {			
				$this->view->setVar('display_id', $vn_display_id);
				
				$va_placements = $t_display->getPlacements(array('returnAllAvailableIfEmpty' => true, 'table' => $t_subject->tableNum(), 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__));
				$va_display_list = array();
				foreach($va_placements as $vn_placement_id => $va_display_item) {
					$va_settings = caUnserializeForDatabase($va_display_item['settings']);
					
					// get column header text
					$vs_header = $va_display_item['display'];
					if (isset($va_settings['label']) && is_array($va_settings['label'])) {
						if ($vs_tmp = array_shift(caExtractValuesByUserLocale(array($va_settings['label'])))) { $vs_header = $vs_tmp; }
					}
					
					$va_display_list[$vn_placement_id] = array(
						'placement_id' => $vn_placement_id,
						'bundle_name' => $va_display_item['bundle_name'],
						'display' => $vs_header,
						'settings' => $va_settings
					);
				}
				
				$this->view->setVar('placements', $va_display_list);
	
				$this->request->user->setVar($t_subject->tableName().'_summary_display_id', $vn_display_id);
				$vs_format = $this->request->config->get("summary_print_format");
			} else {
				$this->view->setVar('display_id', null);
				$this->view->setVar('placements', array());
			}
			
			try {
				$vs_content = $this->render('print_summary_html.php');
				$vo_html2pdf = new HTML2PDF('P',$vs_format,'en');
				$vo_html2pdf->WriteHTML($vs_content);
				$vo_html2pdf->Output('summary.pdf');
				$vb_printed_properly = true;
			} catch (Exception $e) {
				$vb_printed_properly = false;
				$this->postError(3100, _t("Could not generate PDF"),"BaseEditorController->PrintSummary()");
			}
		}
 		# -------------------------------------------------------
 		public function Log() {
 			JavascriptLoadManager::register('tableList');
 			list($vn_subject_id, $t_subject) = $this->_initView();
 			
 			$this->render('log_html.php');
 		}
 		# -------------------------------------------------------
 		protected function _initView() {
 			// load required javascript
 			JavascriptLoadManager::register('bundleableEditor');
 			JavascriptLoadManager::register('imageScroller');
 			$t_subject = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name);
 			$vn_subject_id = (isset($pa_parameters[$t_subject->primaryKey()])) ? $pa_parameters[$t_subject->primaryKey()] : $this->request->getParameter($t_subject->primaryKey(), pInteger);
 			
 			if (!$vn_subject_id || !$t_subject->load($vn_subject_id)) {
 				// empty (ie. new) rows don't have a type_id set, which means we'll have no idea which attributes to display
 				// so we get the type_id off of the request
 				if (!$vn_type_id = $this->request->getParameter($t_subject->getTypeFieldName(), pInteger)) {
 					$vn_type_id = null;
 				}
 				
 				// then set the empty row's type_id
 				$t_subject->set($t_subject->getTypeFieldName(), $vn_type_id);
 				
 				// then reload the definitions (which includes bundle specs)
 				$t_subject->reloadLabelDefinitions();
 			}
 			$t_ui = new ca_editor_uis();
 			$t_ui->loadDefaultUI($this->ops_table_name, $this->request);
 			
 			$this->view->setVar($t_subject->primaryKey(), $vn_subject_id);
 			$this->view->setVar('subject_id', $vn_subject_id);
 			$this->view->setVar('t_subject', $t_subject);
 			
 			if ($vs_parent_id_fld = $t_subject->getProperty('HIERARCHY_PARENT_ID_FLD')) {
 				$this->view->setVar('parent_id', $vn_parent_id = $this->request->getParameter($vs_parent_id_fld, pInteger));
 				return array($vn_subject_id, $t_subject, $t_ui, $vn_parent_id);
 			}
 			
 			return array($vn_subject_id, $t_subject, $t_ui);
 		}
 		# -------------------------------------------------------
 		# File attribute bundle download
 		# -------------------------------------------------------
 		public function DownloadFile() {
 			list($vn_subject_id, $t_subject) = $this->_initView();
 			$pn_attribute_id = $this->request->getParameter('attribute_id', pInteger);
 			$pn_element_id = $this->request->getParameter('element_id', pInteger);
 			
 			$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');
 			
 			// get value
 			$t_element = new ca_metadata_elements($pn_element_id);
 			// check that value is a file attribute
 			if ($t_element->get('datatype') != 15) { 	// 15=file
 				return;
 			}
 			
 			$t_attr = new ca_attributes($pn_attribute_id);
 			
 			// TODO: check that file is part of item user has access rights for
 			
 			$va_values = $t_attr->getAttributeValues();
 			
 			$vn_value_id = null;
 			foreach($va_values as $o_value) {
 				if ($o_value->getElementID() == $pn_element_id) {
 					$vn_value_id = $o_value->getValueID();
 					break;
 				}
 			}
 			if (!$vn_value_id) { return; }
 			$t_attr_val = new ca_attribute_values($vn_value_id);
 			$t_attr_val->useBlobAsFileField(true);
 			
 			$o_view->setVar('file_path', $t_attr_val->getFilePath('value_blob'));
 			$o_view->setVar('file_name', ($vs_name = trim($t_attr_val->get('value_longtext2'))) ? $vs_name : _t("downloaded_file"));
 			
 			// send download
 			$this->response->addContent($o_view->render('ca_attributes_download_file.php'));
 		}
 		# -------------------------------------------------------
 		# Media attribute bundle download
 		# -------------------------------------------------------
 		public function DownloadMedia() {
 			list($vn_subject_id, $t_subject) = $this->_initView();
 			$pn_attribute_id = $this->request->getParameter('attribute_id', pInteger);
 			$pn_element_id = $this->request->getParameter('element_id', pInteger);
 			
 			$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');
 			
 			// get value
 			$t_element = new ca_metadata_elements($pn_element_id);
 			// check that value is a file attribute
 			if ($t_element->get('datatype') != 16) { 	// 15=media
 				return;
 			}
 			
 			$t_attr = new ca_attributes($pn_attribute_id);
 			
 			// TODO: check that file is part of item user has access rights for
 			
 			$va_values = $t_attr->getAttributeValues();
 			
 			$vn_value_id = null;
 			foreach($va_values as $o_value) {
 				if ($o_value->getElementID() == $pn_element_id) {
 					$vn_value_id = $o_value->getValueID();
 					break;
 				}
 			}
 			if (!$vn_value_id) { return; }
 			$t_attr_val = new ca_attribute_values($vn_value_id);
 			$t_attr_val->useBlobAsMediaField(true);
 			
 			$o_view->setVar('file_path', $t_attr_val->getMediaPath('value_blob', 'original'));
 			$o_view->setVar('file_name', ($vs_name = trim($t_attr_val->get('value_longtext2'))) ? $vs_name : _t("downloaded_file"));
 			
 			// send download
 			$this->response->addContent($o_view->render('ca_attributes_download_media.php'));
 		}
 		# -------------------------------------------------------
 		# Dynamic navigation generation
 		# -------------------------------------------------------
 		public function _genDynamicNav($pa_params) {
 			list($vn_subject_id, $t_subject) = $this->_initView();
 			if (!$this->request->isLoggedIn()) { return array(); }
 			
 			$t_ui = new ca_editor_uis();
 			$t_ui->loadDefaultUI($this->ops_table_name, $this->request);
 			
 			if (!$vn_type_id = $t_subject->getTypeID()) {
 				$vn_type_id = $this->request->getParameter($t_subject->getTypeFieldName(), pInteger);
 			}
 			$va_nav = $t_ui->getScreensAsNavConfigFragment($this->request, $vn_type_id, $pa_params['default']['module'], $pa_params['default']['controller'], $pa_params['default']['action'],
 				isset($pa_params['parameters']) ? $pa_params['parameters'] : null,
 				isset($pa_params['requires']) ? $pa_params['requires'] : null,
 				($vn_subject_id > 0) ? false : true,
 				array('hideIfNoAccess' => isset($pa_params['hideIfNoAccess']) ? $pa_params['hideIfNoAccess'] : false)
 			);
 			
 			if (!$this->request->getActionExtra()) {
 				$this->request->setActionExtra($va_nav['defaultScreen']);
 			}
 			
 			return $va_nav['fragment'];
 		}
		# -------------------------------------------------------
		# Navigation (menu bar)
		# -------------------------------------------------------
 		public function _genTypeNav($pa_params) {
 			$t_subject = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name, true);
 			
 			$t_list = new ca_lists();
 			$t_list->load(array('list_code' => $t_subject->getTypeListCode()));
 			
 			$t_list_item = new ca_list_items();
 			$t_list_item->load(array('list_id' => $t_list->getPrimaryKey(), 'parent_id' => null));
 			$va_hier = caExtractValuesByUserLocale($t_list_item->getHierarchyWithLabels());
 			
 			$vn_sort_type = $t_list->get('default_sort');
 			
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
					$va_subtypes = array();
					if (!$this->getRequest()->config->get($this->ops_table_name.'_navigation_new_menu_shows_top_level_types_only')) {
						if (isset($va_item['item_id']) && isset($va_types_by_parent_id[$va_item['item_id']]) && is_array($va_types_by_parent_id[$va_item['item_id']])) {
							$va_subtypes = $this->_getSubTypes($va_types_by_parent_id[$va_item['item_id']], $va_types_by_parent_id, $vn_sort_type);
						}
					} 
					
					switch($vn_sort_type) {
						case 0:			// label
						default:
							$vs_key = $va_item['name_singular'];
							break;
						case 1:			// rank
							$vs_key = sprintf("%08d", (int)$va_item['rank']);
							break;
						case 2:			// value
							$vs_key = $va_item['item_value'];
							break;
						case 3:			// identifier
							$vs_key = $va_item['idno_sort'];
							break;
					}
					$va_types[$vs_key][] = array(
						'displayName' => $va_item['name_singular'],
						'parameters' => array(
							'type_id' => $va_item['item_id']
						),
						'is_enabled' => $va_item['is_enabled'],
						'navigation' => $va_subtypes
					);
				}
				ksort($va_types);
			}
			
			$va_types_proc = array();
			foreach($va_types as $vs_sort_key => $va_items) {
				foreach($va_items as $vn_i => $va_item) {
					$va_types_proc[] = $va_item;
				}
			}
			
 			return $va_types_proc;
 		}
		# ------------------------------------------------------------------
		private function _getSubTypes($pa_subtypes, $pa_types_by_parent_id, $pn_sort_type) {
			$va_subtypes = array();
			foreach($pa_subtypes as $vn_i => $va_type) {
				if (isset($pa_types_by_parent_id[$va_type['item_id']]) && is_array($pa_types_by_parent_id[$va_type['item_id']])) {
					$va_subsubtypes = $this->_getSubTypes($pa_types_by_parent_id[$va_type['item_id']], $pa_types_by_parent_id, $pn_sort_type);
				} else {
					$va_subsubtypes = array();
				}
				
				switch($pn_sort_type) {
					case 0:			// label
					default:
						$vs_key = $va_type['name_singular'];
						break;
					case 1:			// rank
						$vs_key = sprintf("%08d", (int)$va_type['rank']);
						break;
					case 2:			// value
						$vs_key = $va_type['item_value'];
						break;
					case 3:			// identifier
						$vs_key = $va_type['idno_sort'];
						break;
				}
				
				$va_subtypes[$vs_key][$va_type['item_id']] = array(
					'displayName' => $va_type['name_singular'],
					'parameters' => array(
						'type_id' => $va_type['item_id']
					),
					'is_enabled' => $va_type['is_enabled'],
					'navigation' => $va_subsubtypes
				);
			}
			
			ksort($va_subtypes);
			$va_subtypes_proc = array();
			
			foreach($va_subtypes as $vs_sort_key => $va_type) {
				foreach($va_type as $vn_item_id => $va_item) {
					$va_subtypes_proc[$vn_item_id] = $va_item;
				}
			}
			
			
			return $va_subtypes_proc;
		}
		# ------------------------------------------------------------------
		/** 
		 *
		 */
		public function getResultContext() {
			return $this->opo_result_context;
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function exportItem() {
 			list($vn_subject_id, $t_subject) = $this->_initView();
			$pn_mapping_id = $this->request->getParameter('mapping_id', pInteger);
			
			$o_export = new DataExporter();
			$this->view->setVar('export_mimetype', $o_export->exportMimetype($pn_mapping_id));
			$this->view->setVar('export_data', $o_export->export($pn_mapping_id, $t_subject, null, array('returnOutput' => true, 'returnAsString' => true)));
			$this->view->setVar('export_filename', preg_replace('![\W]+!', '_', substr($t_subject->getLabelForDisplay(), 0, 40).'_'.$o_export->exportTarget($pn_mapping_id)).'.'.$o_export->exportFileExtension($pn_mapping_id));
			
			$this->render('../generic/export_xml.php');
		}
		# ------------------------------------------------------------------
		# Watch list actions
 		# ------------------------------------------------------------------
 		/**
 		 * Add item to user's watch list
 		 */
 		public function toggleWatch() {
 			list($vn_subject_id, $t_subject) = $this->_initView();
 			require_once(__CA_MODELS_DIR__.'/ca_watch_list.php');
 			
 			$va_errors = array();
			$t_watch_list = new ca_watch_list();
			$vn_user_id =  $this->request->user->get("user_id");
			
			if ($t_watch_list->isItemWatched($vn_subject_id, $t_subject->tableNum(), $vn_user_id)) {
				if($t_watch_list->load(array('row_id' => $vn_subject_id, 'user_id' => $vn_user_id, 'table_num' => $t_subject->tableNum()))){
					$t_watch_list->setMode(ACCESS_WRITE);
					$t_watch_list->delete();
					if ($t_watch_list->numErrors()) {
						$va_errors = $t_item->errors;
						$this->view->setVar('state', 'watched');
					} else {
						$this->view->setVar('state', 'unwatched');
					}
				}
			} else {
				$t_watch_list->setMode(ACCESS_WRITE);
				$t_watch_list->set('user_id', $vn_user_id);
				$t_watch_list->set('table_num', $t_subject->tableNum());
				$t_watch_list->set('row_id', $vn_subject_id);
				$t_watch_list->insert();
				
				if ($t_watch_list->numErrors()) {
					$this->view->setVar('state', 'unwatched');
					$va_errors = $t_item->errors;
				} else {
					$this->view->setVar('state', 'watched');
				}
			}
			
			$this->view->setVar('errors', $va_errors);
			
			$this->render('../generic/ajax_toggle_item_watch_json.php');
		}
		# ------------------------------------------------------------------
 		# Sidebar info handler
 		# ------------------------------------------------------------------
 		public function info($pa_parameters) {
 			$o_dm 				= Datamodel::load();
 			$t_item 			= $o_dm->getInstanceByTableName($this->ops_table_name, true);
 			$vs_pk 				= $t_item->primaryKey();
 			$vs_label_table 	= $t_item->getLabelTableName();
 			$t_label 			= $t_item->getLabelTableInstance();
 			$vs_display_field	= $t_label->getDisplayField();
 			
 			$vn_item_id 		= (isset($pa_parameters[$vs_pk])) ? $pa_parameters[$vs_pk] : null;
 			$vn_type_id 		= (isset($pa_parameters['type_id'])) ? $pa_parameters['type_id'] : null;
 			
 			$t_ui = new ca_editor_uis();
 			$t_ui->loadDefaultUI($this->ops_table_name, $this->request);
 			$this->view->setVar('t_ui', $t_ui);
 			
 			$t_item->load($vn_item_id);
 			
 			if ($t_item->getPrimaryKey()) {
 				if (method_exists($t_item, "getRepresentations")) {
 					$this->view->setVar('representations', $t_item->getRepresentations(array('preview170', 'preview')));
 				} else {
 					if ($t_item->tableName() === 'ca_object_representations') {
 						$this->view->setVar('representations', array(
 							$t_item->getFieldValuesArray()
 						));
 					}
 				}
 				
 				if ($t_item->isHierarchical()) {
					// get parent objects
					$va_ancestors = array_reverse(caExtractValuesByUserLocaleFromHierarchyAncestorList(
						$t_item->getHierarchyAncestors(null, array(
							'additionalTableToJoin' => $vs_label_table,
							'additionalTableJoinType' => 'LEFT',
							'additionalTableSelectFields' => array($vs_display_field, 'locale_id'),
							'additionalTableWheres' => ($t_label->hasField('is_preferred')) ? array("({$vs_label_table}.is_preferred = 1 OR {$vs_label_table}.is_preferred IS NULL)") : array(),
							'includeSelf' => false
						)
					), $vs_pk, $vs_display_field, 'idno'));
					$this->view->setVar('ancestors', $va_ancestors);
					
					$va_children = caExtractValuesByUserLocaleFromHierarchyChildList(
						$t_item->getHierarchyChildren(null, array(
							'additionalTableToJoin' => $vs_label_table,
							'additionalTableJoinType' => 'LEFT',
							'additionalTableSelectFields' => array($vs_display_field, 'locale_id'),
							'additionalTableWheres' => ($t_label->hasField('is_preferred')) ? array("({$vs_label_table}.is_preferred = 1 OR {$vs_label_table}.is_preferred IS NULL)") : array(),
							'includeSelf' => false
						)
					), $vs_pk, $vs_display_field, 'idno');
					$this->view->setVar('children', $va_children);
				}
 			} else {
 				$t_item->set('type_id', $vn_type_id);
 			}
 			$this->view->setVar('t_item', $t_item);
			$this->view->setVar('screen', $this->request->getActionExtra());						// name of screen
			$this->view->setVar('result_context', $this->getResultContext());
			
			$t_mappings = new ca_bundle_mappings();
			$va_mappings = $t_mappings->getAvailableMappings($t_item->tableNum(), array('E', 'X'));
			
			$va_export_options = array();
			foreach($va_mappings as $vn_mapping_id => $va_mapping_info) {
				$va_export_options[$va_mapping_info['name']] = $va_mapping_info['mapping_id'];
			}
			$this->view->setVar('available_mappings', $va_mappings);
			$this->view->setVar('available_mappings_as_html_select', sizeof($va_export_options) ? caHTMLSelect('mapping_id', $va_export_options) : '');
 		}
		# ------------------------------------------------------------------
		/**
		 * Called just prior to actual deletion of record; allows individual editor controllers to implement
		 * pre-deletion login (eg. moving related records) by overriding this method with their own implementaton.
		 * If the method returns true, the deletion will be performed; if false is returned then the delete will be aborted.
		 *
		 * @param BaseModel Model instance of row being deleted
		 * @return boolean True if delete should be performed, false if it should be aborted
		 */
		protected function _beforeDelete($pt_subject) {
			// override with your own behavior as required
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Called just after record is deleted. Individual editor controllers can override this to implement their
		 * own post-deletion cleanup login.
		 *
		 * @param BaseModel Model instance of row that was deleted
		 * @return boolean True if post-deletion cleanup was successful, false if not
		 */
		protected function _afterDelete($pt_subject) {
			// override with your own behavior as required
			return true;
		}
		# ------------------------------------------------------------------
 	}
 ?>