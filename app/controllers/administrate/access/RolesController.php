<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/access/RolesController.php :
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
 * ----------------------------------------------------------------------
 */

 	require_once(__CA_MODELS_DIR__.'/ca_user_roles.php');

 	class RolesController extends ActionController {
 		# -------------------------------------------------------
 		private $pt_role;
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function Edit() {
 			$t_role = $this->getRoleObject();
 			$this->render('role_edit_html.php');
 		}
 		# -------------------------------------------------------
 		public function Save() {
 			JavascriptLoadManager::register('tableList');
 			
 			$t_role = $this->getRoleObject();
 			$t_role->setMode(ACCESS_WRITE);
 			foreach($t_role->getFormFields() as $vs_f => $va_field_info) {
 				$t_role->set($vs_f, $_REQUEST[$vs_f]);
 				if ($t_role->numErrors()) {
 					$this->request->addActionErrors($t_role->errors(), 'field_'.$vs_f);
 				}
 			}
 			// save actions
			$va_role_action_list = $t_role->getRoleActionList(true);
			$va_new_role_action_settings = array();
			foreach($va_role_action_list as $vs_action => $va_action_info) {
				if ($this->request->getParameter($vs_action, pInteger) > 0) {
					$va_new_role_action_settings[] = $vs_action;
				}
			}
			$t_role->setRoleActions($va_new_role_action_settings);
			
			AppNavigation::clearMenuBarCache($this->request);	// clear menu bar cache since role changes may affect content
			
 			if($this->request->numActionErrors() == 0) {
				if (!$t_role->getPrimaryKey()) {
					$t_role->insert();
					$vs_message = _t("Added role");
				} else {
					$t_role->update();
					$vs_message = _t("Saved changes to role");
				}

				if ($t_role->numErrors()) {
					foreach ($t_role->errors() as $o_e) {
						$this->request->addActionError($o_e, 'general');
						
						$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
					}
				} else {
					$this->notification->addNotification($vs_message, __NOTIFICATION_TYPE_INFO__);
				}
			} else {
				$this->notification->addNotification(_t("Your entry has errors. See below for details."), __NOTIFICATION_TYPE_ERROR__);
			}

			if ($this->request->numActionErrors()) {
				$this->render('role_edit_html.php');
			} else {
 				$this->view->setVar('role_list', $t_role->getRoleList());

 				$this->render('role_list_html.php');
 			}
 		}
 		# -------------------------------------------------------
 		public function ListRoles() {
 			JavascriptLoadManager::register('tableList');
 			
 			$t_role = $this->getRoleObject();
 			$vs_sort_field = $this->request->getParameter('sort', pString);
 			$this->view->setVar('role_list', $t_role->getRoleList($vs_sort_field, 'asc'));

 			$this->render('role_list_html.php');
 		}
 		# -------------------------------------------------------
 		public function Delete() {
 			$t_role = $this->getRoleObject();
 			if ($this->request->getParameter('confirm', pInteger)) {
 				$t_role->setMode(ACCESS_WRITE);
 				$t_role->delete(true);

 				if ($t_role->numErrors()) {
 					foreach ($t_role->errors() as $o_e) {
						$this->request->addActionError($o_e, 'general');
					}
 				} else {
 					$this->notification->addNotification(_t("Deleted role"), __NOTIFICATION_TYPE_INFO__);
 				}
 				$this->ListRoles();
 				return;
 			} else {
 				$this->render('role_delete_html.php');
 			}
 		}
 		# -------------------------------------------------------
 		# Utilities
 		# -------------------------------------------------------
 		private function getRoleObject($pb_set_view_vars=true, $pn_role_id=null) {
 			if (!($t_role = $this->pt_role)) {
				if (!($vn_role_id = $this->request->getParameter('role_id', pInteger))) {
					$vn_role_id = $pn_role_id;
				}
				$t_role = new ca_user_roles($vn_role_id);
			}
 			if ($pb_set_view_vars){
 				$this->view->setVar('role_id', $vn_role_id);
 				$this->view->setVar('t_role', $t_role);
 			}
 			$this->pt_role = $t_role;
 			return $t_role;
 		}
 		# -------------------------------------------------------
 	}
 ?>