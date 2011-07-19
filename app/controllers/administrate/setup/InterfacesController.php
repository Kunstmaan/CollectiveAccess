<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/setup/InterfacesController.php :
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
 * ----------------------------------------------------------------------
 */
 
	require_once(__CA_MODELS_DIR__.'/ca_editor_uis.php');
	require_once(__CA_MODELS_DIR__.'/ca_editor_ui_labels.php');
	require_once(__CA_MODELS_DIR__.'/ca_editor_ui_screens.php');
	require_once(__CA_MODELS_DIR__.'/ca_editor_ui_screen_labels.php');
	require_once(__CA_MODELS_DIR__.'/ca_editor_ui_bundle_placements.php');
	require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
	require_once(__CA_LIB_DIR__.'/ca/BaseEditorController.php');
	require_once(__CA_LIB_DIR__.'/ca/ResultContext.php');

class InterfacesController extends BaseEditorController {
	# -------------------------------------------------------
	protected $ops_table_name = 'ca_editor_uis';		// name of "subject" table (what we're editing)
	# -------------------------------------------------------
	/**
	 *
	 */
	public function ListUIs(){
		JavascriptLoadManager::register('tableList');
		
		$vo_dm = Datamodel::load();
		$va_uis = ca_editor_uis::getUIList(null, $this->request->user->getPrimaryKey());
		foreach($va_uis as $vs_key => $va_ui){
			$t_instance = $vo_dm->getInstanceByTableNum($va_ui['editor_type'], true);
			$va_uis[$vs_key]['editor_type'] = $t_instance->getProperty('NAME_PLURAL');
		}
		$this->view->setVar('editor_ui_list',$va_uis);
		
		$o_result_context = new ResultContext($this->request, $this->ops_table_name, 'basic_search');
		$o_result_context->setResultList(array_keys($va_uis));
		$o_result_context->setAsLastFind();
		$o_result_context->saveContext();
		
		return $this->render('ui_list_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Edit($pn_ui_id=null) {
		JavascriptLoadManager::register('bundleableEditor');
		
		$t_ui = $this->getEditorUIObject(true, $pn_ui_id);
		$t_screen = new ca_editor_ui_screens();
		$this->view->setVar('t_screen',$t_screen);
		$this->render('ui_edit_html.php');
 	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Save() {
		JavascriptLoadManager::register('tableList');
		$t_editor_ui = $this->getEditorUIObject();
		$t_editor_ui->setMode(ACCESS_WRITE);
		$va_request = $_REQUEST; /* we don't want to modify $_REQUEST since this may cause ugly side-effects */
		foreach($t_editor_ui->getFormFields() as $vs_f => $va_field_info) {
			switch($va_field_info['FIELD_TYPE']) {
				case FT_MEDIA:
					$t_editor_ui->set($vs_f, $_FILES[$vs_f]['tmp_name']);
					break;
				default:
					$t_editor_ui->set($vs_f, $_REQUEST[$vs_f]);
					break;
			}
			unset($va_request[$vs_f]);
 		}

		$t_editor_ui->set('user_id', $this->request->user->getPrimaryKey());
		if (!$t_editor_ui->getPrimaryKey()) {
			$t_editor_ui->insert();
			$vb_new = true;
			$vs_message = _t("Added interface");
		} else {
			$t_editor_ui->update();
			$vb_new = false;
			$vs_message = _t("Saved changes to interface");
		}

		if ($t_editor_ui->numErrors()) {
			foreach ($t_editor_ui->errors() as $o_e) {
				$this->request->addActionError($o_e, 'general');
				$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				$this->render('ui_edit_html.php');
				return;
			}
		} else {
			$this->notification->addNotification($vs_message, __NOTIFICATION_TYPE_INFO__);
		}

		$va_new_labels = array();
		$va_old_labels = array();
		$va_delete_labels = array();
		foreach($va_request as $vs_key => $vs_val){
			if(!(strpos($vs_key,'ui_labels_Pref')===false)) { /* label field */
				$va_matches = array();
				if(!(strpos($vs_key,'_new')===false)){ /* new label field */
					preg_match('/ui_labels_Pref(.*)_new_([0-9]+)/',$vs_key,$va_matches);
					$va_new_labels[$va_matches[2]][$va_matches[1]] = $vs_val;
				} else if(!(strpos($vs_key,'_delete')===false)){ /* delete label */
					preg_match('/ui_labels_PrefLabel_([0-9]+)_delete/',$vs_key,$va_matches);
					$va_delete_labels[] = $va_matches[1];
				} else {/* existing label field */
					preg_match('/ui_labels_Pref(.*)_([0-9]+)/',$vs_key,$va_matches);
					$va_old_labels[$va_matches[2]][$va_matches[1]] = $vs_val;
				}
			}
		}

		/* insert new labels */
		$t_editor_ui_label = new ca_editor_ui_labels();
		foreach($va_new_labels as $va_label){
			$t_editor_ui_label->clear();
			foreach($va_label as $vs_f => $vs_val){
				$t_editor_ui_label->set($vs_f,$vs_val);
			}
			$t_editor_ui_label->set('ui_id',$t_editor_ui->getPrimaryKey());
			$t_editor_ui_label->setMode(ACCESS_WRITE);
			$t_editor_ui_label->insert();
			if ($t_editor_ui_label->numErrors()) {
				foreach ($t_editor_ui_label->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
					$this->render('ui_edit_html.php');
					return;
				}
			}
		}

		/* delete labels */
		foreach($va_delete_labels as $vn_label){
			$t_editor_ui_label->load($vn_label);
			$t_editor_ui_label->setMode(ACCESS_WRITE);
			$t_editor_ui_label->delete(false);
		}

		/* process old labels */
		foreach($va_old_labels as $vn_key => $va_label){
			$t_editor_ui_label->load($vn_key);
			foreach($va_label as $vs_f => $vs_val){
				$t_editor_ui_label->set($vs_f,$vs_val);
			}
			$t_editor_ui_label->set('ui_id',$t_editor_ui->getPrimaryKey());
			$t_editor_ui_label->setMode(ACCESS_WRITE);
			if($vb_new){
				$t_editor_ui_label->insert();
			} else {
				$t_editor_ui_label->update();
			}
			if ($t_editor_ui_label->numErrors()) {
				foreach ($t_editor_ui_label->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
					$this->render('ui_edit_html.php');
					return;
				}
			}
		}

		$this->Edit($t_editor_ui->getPrimaryKey());
		return;
 	}
	# -------------------------------------------------------
	/**
	 *
	 */
 	public function Delete() {
		$t_editor_ui = $this->getEditorUIObject();
		if ($this->request->getParameter('confirm', pInteger)) {
			$t_editor_ui->setMode(ACCESS_WRITE);
			$t_editor_ui->delete(true);

			if ($t_editor_ui->numErrors()) {
				foreach ($t_editor_ui->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			} else {
 				$this->notification->addNotification(_t("Deleted user interface"), __NOTIFICATION_TYPE_INFO__);
 			}

 			$this->ListUIs();
 			return;
 		} else {
 			$this->render('ui_delete_html.php');
 		}
 	}
	# -------------------------------------------------------
	# Screens
	# -------------------------------------------------------
	/**
	 *
	 */
	public function EditScreen($pn_screen_id=null) {
		JavascriptLoadManager::register('bundleableEditor');
		$this->getScreenObject(true, $pn_screen_id);
		$t_ui = $this->getEditorUIObject();
		$vo_dm = Datamodel::load();
		$t_instance = $vo_dm->getInstanceByTableNum($t_ui->get('editor_type'), true);
		$this->view->setVar('t_instance',$t_instance);
		$this->render('screen_edit_html.php');
 	}
 	# -------------------------------------------------------
	/**
	 *
	 */
	public function SaveScreen() {
		$t_screen = $this->getScreenObject();
		$t_editor_ui = $this->getEditorUIObject();
		$t_screen->setMode(ACCESS_WRITE);
		$va_request = $_REQUEST; /* we don't want to modify $_REQUEST since this may cause ugly side-effects */
		foreach($t_screen->getFormFields() as $vs_f => $va_field_info) {
			$t_screen->set($vs_f, $_REQUEST[$vs_f]);
			unset($va_request[$vs_f]);
 		}

		$t_screen->set('ui_id',$t_editor_ui->get('ui_id'));

		if (!$t_screen->getPrimaryKey()) {
			$vo_db = new Db();
			$qr_tmp = $vo_db->query("
				SELECT MAX(rank) AS rank
				FROM ca_editor_ui_screens
				WHERE ui_id=?
			",$t_editor_ui->get('ui_id'));
			if(!$qr_tmp->nextRow()){
				$t_screen->set('rank',1);
			} else {
				$t_screen->set('rank',intval($qr_tmp->get('rank'))+1);
			}

			$t_screen->insert();
			$vb_new = true;
			$vs_message = _t("Added screen");
		} else {
			$t_screen->update();
			$vb_new = false;
			$vs_message = _t("Saved changes to screen");
		}

		if ($t_screen->numErrors()) {
			foreach ($t_screen->errors() as $o_e) {
				$this->request->addActionError($o_e, 'general');
				$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				$this->render('screen_edit_html.php');
				return;
			}
		} else {
			$this->notification->addNotification($vs_message, __NOTIFICATION_TYPE_INFO__);
		}

		$va_new_labels = array();
		$va_old_labels = array();
		$va_delete_labels = array();
		foreach($va_request as $vs_key => $vs_val){
			if(!(strpos($vs_key,'screen_labels_Pref')===false)) { /* label field */
				$va_matches = array();
				if(!(strpos($vs_key,'_new')===false)){ /* new label field */
					preg_match('/screen_labels_Pref(.*)_new_([0-9]+)/',$vs_key, $va_matches);
					$va_new_labels[$va_matches[2]][$va_matches[1]] = $vs_val;
				} else if(!(strpos($vs_key,'_delete')===false)){ /* delete label */
					preg_match('/screen_labels_PrefLabel_([0-9]+)_delete/',$vs_key, $va_matches);
					$va_delete_labels[] = $va_matches[1];
				} else {/* existing label field */
					preg_match('/screen_labels_Pref(.*)_([0-9]+)/',$vs_key, $va_matches);
					$va_old_labels[$va_matches[2]][$va_matches[1]] = $vs_val;
				}
			}
		}

		/* insert new labels */
		$t_screen_label = new ca_editor_ui_screen_labels();
		foreach($va_new_labels as $va_label){
			$t_screen_label->clear();
			foreach($va_label as $vs_f => $vs_val){
				$t_screen_label->set($vs_f,$vs_val);
			}
			$t_screen_label->set('screen_id',$t_screen->getPrimaryKey());
			$t_screen_label->setMode(ACCESS_WRITE);
			$t_screen_label->insert();
			if ($t_screen_label->numErrors()) {
				foreach ($t_screen_label->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
					$this->render('screen_edit_html.php');
					return;
				}
			}
		}

		/* delete labels */
		foreach($va_delete_labels as $vn_label){
			$t_screen_label->load($vn_label);
			$t_screen_label->setMode(ACCESS_WRITE);
			$t_screen_label->delete(false);
		}

		/* process old labels */
		foreach($va_old_labels as $vn_key => $va_label){
			$t_screen_label->load($vn_key);
			foreach($va_label as $vs_f => $vs_val){
				$t_screen_label->set($vs_f,$vs_val);
			}
			$t_screen_label->set('screen_id',$t_screen->getPrimaryKey());
			$t_screen_label->setMode(ACCESS_WRITE);
			if($vb_new){
				$t_screen_label->insert();
			} else {
				$t_screen_label->update();
			}
			if ($t_screen_label->numErrors()) {
				foreach ($t_screen_label->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
					$this->render('screen_edit_html.php');
					return;
				}
			}
		}

		$this->EditScreen($t_screen->getPrimaryKey());
		return;
 	}
	# -------------------------------------------------------
	/**
	 *
	 */
 	public function DeleteScreen() {
		$t_screen = $this->getScreenObject();
		$this->getEditorUIObject();
		if ($this->request->getParameter('confirm', pInteger)) {
			$t_screen->setMode(ACCESS_WRITE);
			$t_screen->delete(true);

			if ($t_screen->numErrors()) {
				foreach ($t_screen->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			} else {
 				$this->notification->addNotification(_t("Deleted screen"), __NOTIFICATION_TYPE_INFO__);
 			}

 			$this->Edit();
 			return;
 		} else {
 			$this->render('screen_delete_html.php');
 		}
 	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function MoveScreenUp() {
		$t_screen = $this->getScreenObject();
		if(is_array($va_ranks_to_stabilize = $this->screenRankStabilizationNeeded($t_screen->get('ui_id')))){
			$this->stabilizeScreenRanks($t_screen->get('ui_id'),$va_ranks_to_stabilize);
		}
		$t_screen = $this->getScreenObject();
		$vo_db = new Db();
		$qr_tmp = $vo_db->query("
			SELECT screen_id,rank
			FROM ca_editor_ui_screens
			WHERE
				(rank < ?)
				AND
				(ui_id = ?)
			ORDER BY
				rank DESC
		",$t_screen->get('rank'),$t_screen->get('ui_id'));
		if(!$qr_tmp->nextRow()){
			$this->notification->addNotification(_t("This screen is at the top of the list"), __NOTIFICATION_TYPE_ERROR__);
		} else { /* swap ranks */
			$t_screen_rankswap = new ca_editor_ui_screens($qr_tmp->get('screen_id'));
			$this->swapRanks($t_screen, $t_screen_rankswap);
			if($t_screen->numErrors()){
				foreach ($t_screen->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			}
			if($t_screen_rankswap->numErrors()){
				foreach ($t_screen_rankswap->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			}
		}
		$this->Edit();
 	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function MoveScreenDown() {
		$t_screen = $this->getScreenObject();
		if(is_array($va_ranks_to_stabilize = $this->screenRankStabilizationNeeded($t_screen->get('ui_id')))){
			$this->stabilizeScreenRanks($t_screen->get('ui_id'),$va_ranks_to_stabilize);
		}
		$t_screen = $this->getScreenObject();
		$vo_db = new Db();
		$qr_tmp = $vo_db->query("
			SELECT screen_id,rank
			FROM ca_editor_ui_screens
			WHERE
				(rank > ?)
				AND
				(ui_id = ?)
			ORDER BY
				rank
		",$t_screen->get('rank'),$t_screen->get('ui_id'));
		if(!$qr_tmp->nextRow()){
			$this->notification->addNotification(_t("This screen is at the bottom of the list"), __NOTIFICATION_TYPE_ERROR__);
		} else { /* swap ranks */
			$t_screen_rankswap = new ca_editor_ui_screens($qr_tmp->get('screen_id'));
			$this->swapRanks($t_screen, $t_screen_rankswap);
			if($t_screen->numErrors()){
				foreach ($t_screen->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			}
			if($t_screen_rankswap->numErrors()){
				foreach ($t_screen_rankswap->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			}
		}
		$this->Edit();
 	}
	# -------------------------------------------------------
	# Bundle placements
	# -------------------------------------------------------
	/**
	 *
	 */
	public function AddPlacement(){
		$t_new_placement = new ca_editor_ui_bundle_placements();
		$t_new_placement->setMode(ACCESS_WRITE);
		$t_new_placement->set('placement_code',$this->request->getParameter('placement_name',pString));
		$t_new_placement->set('bundle_name',$this->request->getParameter('placement_name',pString));
		$t_screen = $this->getScreenObject();
		$t_new_placement->set('screen_id',$t_screen->get('screen_id'));
		$vo_db = new Db();
		$qr_tmp = $vo_db->query("
			SELECT MAX(rank) AS rank
			FROM ca_editor_ui_bundle_placements
			WHERE screen_id=?
		",$t_screen->get('screen_id'));
		if(!$qr_tmp->nextRow()){
			$t_new_placement->set('rank',1);
		} else {
			$t_new_placement->set('rank',intval($qr_tmp->get('rank'))+1);
		}
		$t_new_placement->insert();
		if ($t_new_placement->numErrors()) {
			foreach ($t_new_placement->errors() as $o_e) {
				$this->request->addActionError($o_e, 'general');
				$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
			}
		}
		$this->EditScreen();
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function DeletePlacement(){
		$t_placement = $this->getPlacementObject();
		$t_placement->setMode(ACCESS_WRITE);
		$t_placement->delete();
		if ($t_placement->numErrors()) {
			foreach ($t_placement->errors() as $o_e) {
				$this->request->addActionError($o_e, 'general');
				$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
			}
		} else {
			$this->notification->addNotification(_t("Bundle placement successfully removed"), __NOTIFICATION_TYPE_INFO__);
		}
		$this->EditScreen();
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function MovePlacementUp() {
		$t_placement = $this->getPlacementObject();
		if(is_array($va_ranks_to_stabilize = $this->placementRankStabilizationNeeded($t_placement->get('screen_id')))){
			$this->stabilizePlacementRanks($t_placement->get('screen_id'),$va_ranks_to_stabilize);
		}
		$t_placement = $this->getPlacementObject();
		$vo_db = new Db();
		$qr_tmp = $vo_db->query("
			SELECT placement_id,rank
			FROM ca_editor_ui_bundle_placements
			WHERE
				(rank < ?)
				AND
				(screen_id = ?)
			ORDER BY
				rank DESC
		",$t_placement->get('rank'),$t_placement->get('screen_id'));
		if(!$qr_tmp->nextRow()){
			$this->notification->addNotification(_t("This bundle is at the top of the list"), __NOTIFICATION_TYPE_ERROR__);
		} else { /* swap ranks */
			$t_placement_rankswap = new ca_editor_ui_bundle_placements($qr_tmp->get('placement_id'));
			$this->swapRanks($t_placement, $t_placement_rankswap);
			if($t_placement->numErrors()){
				foreach ($t_placement->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			}
			if($t_placement_rankswap->numErrors()){
				foreach ($t_placement_rankswap->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			}
		}
		$this->EditScreen();
 	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function MovePlacementDown() {
		$t_placement = $this->getPlacementObject();
		if(is_array($va_ranks_to_stabilize = $this->placementRankStabilizationNeeded($t_placement->get('screen_id')))){
			$this->stabilizePlacementRanks($t_placement->get('screen_id'),$va_ranks_to_stabilize);
		}
		$t_placement = $this->getPlacementObject();
		$vo_db = new Db();
		$qr_tmp = $vo_db->query("
			SELECT placement_id,rank
			FROM ca_editor_ui_bundle_placements
			WHERE
				(rank > ?)
				AND
				(screen_id = ?)
			ORDER BY
				rank
		",$t_placement->get('rank'),$t_placement->get('screen_id'));
		if(!$qr_tmp->nextRow()){
			$this->notification->addNotification(_t("This bundle is at the bottom of the list"), __NOTIFICATION_TYPE_ERROR__);
		} else { /* swap ranks */
			$t_placement_rankswap = new ca_editor_ui_bundle_placements($qr_tmp->get('placement_id'));
			$this->swapRanks($t_placement, $t_placement_rankswap);
			if($t_placement->numErrors()){
				foreach ($t_placement->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			}
			if($t_placement_rankswap->numErrors()){
				foreach ($t_placement_rankswap->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			}
		}
		$this->EditScreen();
 	}
	# -------------------------------------------------------
	# Utilities
 	# -------------------------------------------------------
 	/**
	 *
	 */
 	private function getEditorUIObject($pb_set_view_vars=true, $pn_ui_id=null) {
		if (!($vn_ui_id = $this->request->getParameter('ui_id', pInteger))) {
			$vn_ui_id = $pn_ui_id;
		}
		$t_editor_ui = new ca_editor_uis($vn_ui_id);
 		if ($pb_set_view_vars){
 			$this->view->setVar('ui_id', $vn_ui_id);
 			$this->view->setVar('t_editor_ui', $t_editor_ui);
 		}
 		return $t_editor_ui;
 	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function getScreenObject($pb_set_view_vars=true, $pn_screen_id=null) {
 		if (!($vn_screen_id = $this->request->getParameter('screen_id', pInteger))) {
			$vn_screen_id = $pn_screen_id;
		}
		$t_screen = new ca_editor_ui_screens($vn_screen_id);
 		if ($pb_set_view_vars){
 			$this->view->setVar('screen_id', $vn_screen_id);
 			$this->view->setVar('t_screen', $t_screen);
 		}
 		return $t_screen;
 	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function getPlacementObject($pb_set_view_vars=true, $pn_placement_id=null) {
 		if (!($vn_placement_id = $this->request->getParameter('placement_id', pInteger))) {
			$vn_placement_id = $pn_placement_id;
		}
		$t_placement = new ca_editor_ui_bundle_placements($vn_placement_id);
 		if ($pb_set_view_vars){
 			$this->view->setVar('screen_id', $vn_placement_id);
 			$this->view->setVar('t_screen', $t_placement);
 		}
 		return $t_placement;
 	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function swapRanks(&$t_first,&$t_second){
		$vn_first_rank = $t_first->get('rank');
		$vn_second_rank = $t_second->get('rank');
		$t_first->setMode(ACCESS_WRITE);
		$t_first->set('rank',$vn_second_rank);
		$t_first->update();
		$t_second->setMode(ACCESS_WRITE);
		$t_second->set('rank',$vn_first_rank);
		$t_second->update();
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function screenRankStabilizationNeeded($pn_ui_id){
		$vo_db = new Db();
		$qr_res = $vo_db->query("
			SELECT * FROM
				(SELECT rank,count(*) as count
					FROM ca_editor_ui_screens
					WHERE ui_id=?
					GROUP BY rank) as lambda
			WHERE
				count > 1;
		",$pn_ui_id);
		if($qr_res->numRows()){
			$va_return = array();
			while($qr_res->nextRow()){
				$va_return[$qr_res->get('rank')] = $qr_res->get('count');
			}
			return $va_return;
		} else {
			return false;
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function stabilizeScreenRanks($pn_ui_id,$pa_ranks){
		$vo_db = new Db();
		$t_screen = new ca_editor_ui_screens();
		do {
			$va_ranks = array_keys($pa_ranks);
			$vn_rank = $va_ranks[0];
			$qr_res = $vo_db->query("
				SELECT * FROM
					ca_editor_ui_screens
					WHERE 
						(ui_id=?)
						AND
						(rank>?)
					ORDER BY
						rank
			",$pn_ui_id,$vn_rank);
			while($qr_res->nextRow()){
				$t_screen->load($qr_res->get('screen_id'));
				$t_screen->set('rank',intval($t_screen->get('rank'))+$pa_ranks[0]);
				$t_screen->setMode(ACCESS_WRITE);
				$t_screen->update();
			}
			$qr_res = $vo_db->query("
				SELECT * FROM
					ca_editor_ui_screens
					WHERE
						(ui_id=?)
						AND
						(rank=?)
					ORDER BY
						rank
			",$pn_ui_id,$vn_rank);
			$i=0;
			while($qr_res->nextRow()){
				$i++;
				$t_screen->load($qr_res->get('screen_id'));
				$t_screen->set('rank',intval($t_screen->get('rank')) + $i);
				$t_screen->setMode(ACCESS_WRITE);
				$t_screen->update();
			}
			$pa_ranks = $this->screenRankStabilizationNeeded($pn_ui_id);
		} while(is_array($pa_ranks));
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function placementRankStabilizationNeeded($pn_screen_id){
		$vo_db = new Db();
		$qr_res = $vo_db->query("
			SELECT * FROM
				(SELECT rank,count(*) as count
					FROM ca_editor_ui_bundle_placements
					WHERE screen_id=?
					GROUP BY rank) as lambda
			WHERE
				count > 1;
		",$pn_screen_id);
		if($qr_res->numRows()){
			$va_return = array();
			while($qr_res->nextRow()){
				$va_return[$qr_res->get('rank')] = $qr_res->get('count');
			}
			return $va_return;
		} else {
			return false;
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function stabilizePlacementRanks($pn_screen_id,$pa_ranks){
		$vo_db = new Db();
		$t_placement = new ca_editor_ui_bundle_placements();
		do {
			$va_ranks = array_keys($pa_ranks);
			$vn_rank = $va_ranks[0];
			$qr_res = $vo_db->query("
				SELECT * FROM
					ca_editor_ui_bundle_placements
					WHERE
						(screen_id=?)
						AND
						(rank>?)
					ORDER BY
						rank
			",$pn_screen_id,$vn_rank);
			while($qr_res->nextRow()){
				$t_placement->load($qr_res->get('placement_id'));
				$t_placement->set('rank',intval($t_placement->get('rank'))+$pa_ranks[0]);
				$t_placement->setMode(ACCESS_WRITE);
				$t_placement->update();
			}
			$qr_res = $vo_db->query("
				SELECT * FROM
					ca_editor_ui_bundle_placements
					WHERE
						(screen_id=?)
						AND
						(rank=?)
					ORDER BY
						rank
			",$pn_screen_id,$vn_rank);
			$i=0;
			while($qr_res->nextRow()){
				$i++;
				$t_placement->load($qr_res->get('placement_id'));
				$t_placement->set('rank',intval($t_placement->get('rank')) + $i);
				$t_placement->setMode(ACCESS_WRITE);
				$t_placement->update();
			}
			$pa_ranks = $this->screenRankStabilizationNeeded($pn_screen_id);
		} while(is_array($pa_ranks));
	}
	# -------------------------------------------------------
	# Sidebar info handler
	# -------------------------------------------------------
	public function info($pa_parameters) {
		parent::info($pa_parameters);
		
		return $this->render('widget_ui_info_html.php', true);
	}
	# -------------------------------------------------------
}