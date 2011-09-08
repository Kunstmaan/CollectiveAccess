<?php
/* ----------------------------------------------------------------------
 * collectionBasedRolesPlugin.php :
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

	class collectionBasedRolesPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		private $ops_plugin_path;
		private $o_db;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->ops_plugin_path = $ps_plugin_path;
			$this->description = _t('Provides the possibility to create roles based on collections.');
			parent::__construct();
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/collectionBasedRoles.conf');
			$this->o_db = new Db();
		}
		# -------------------------------------------------------
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => ((bool)$this->opo_config->get('enabled'))
			);
		}
		# -------------------------------------------------------
		private function canDoCollection($collection_id) {
			if ($o_req = $this->getRequest()) {
				while(isset($collection_id) && !empty($collection_id) && $collection_id != null) {
					if($o_req->user->canDoAction('can_edit_'.$collection_id)) {
						return true;
					}
					require_once(__CA_MODELS_DIR__.'/ca_collections.php');
					$ca_collection = new ca_collections($collection_id);
					$collection_id = $ca_collection->get('parent_id');
				}
				return false;
			}
			return true;
		}
		# -------------------------------------------------------
		public function hookFilterLookup($pa_items) {
			$to_remove = array();
			$o_req = $this->getRequest();
			if (isset($o_req) && !$o_req->user->canDoAction('can_edit_all')) {
				foreach($pa_items as $key => $value) {
					if('ca_collections' == $value['table_name']) {
						if (!$this->canDoCollection($key)) {
							$to_remove[] = $key;
						}
					}
				}
			}
			foreach($to_remove as $key) {
				$pa_items[$key] = null;
			}
			return $pa_items;
		}
		# -------------------------------------------------------
		public function hookDeleteItem($pa_params) {
			$this->hookEditItem($pa_params);
		}
		# -------------------------------------------------------
		public function hookEditItem($pa_params) {
			$access = true;
			$o_req = $this->getRequest();
			if (isset($o_req) && !$o_req->user->canDoAction('can_edit_all')) {
				$collection_to_check = null;
				if('ca_objects' == $pa_params['table_name']) {
					$instance = $pa_params['instance'];
					$object_collection = $instance->getAttributesByElement('object_collection');
					foreach($object_collection as $attr) {
						foreach($attr->getValues() as $value) {
							$collection_to_check = $value->getCollectionID();
						}
					}
				}
				if('ca_collections' == $pa_params['table_name']) {
					$instance = $pa_params['instance'];
					$collection_to_check = $instance->getPrimaryKey();
				}
				if(isset($collection_to_check) && !empty($collection_to_check)) {
					if (!$this->canDoCollection($collection_to_check)) {
						$access = false;
					}
				}
			}
			if(!$access) {
				if (($o_app = AppController::getInstance()) && ($o_resp = $o_app->getResponse())) {
					$o_resp->setRedirect($this->getRequest()->config->get('error_display_url').'/n/8888?r='.urlencode($this->getRequest()->getFullUrlPath()));
				}
			}
		}
		# -------------------------------------------------------
		public function hookGetRoleActionList($pa_role_list) {
			global $g_ui_locale_id;

			$t_locale = new ca_locales($g_ui_locale_id);
			$vs_lang = $t_locale->get("language");

			$actions = array();

			$qr_res = $this->o_db->query("
				SELECT ca_collections.collection_id as id, name
				FROM ca_collections
				LEFT JOIN ca_collection_labels
				ON ca_collections.collection_id = ca_collection_labels.collection_id
				WHERE ca_collection_labels.is_preferred = true;
			");

			$actions['can_edit_all'] = array(
				'label' => _t('All'),
				'description' => _t('Can edit the objects from all the collections')
			);

			while($qr_res->nextRow()) {
				$actions['can_edit_'.$qr_res->get('id')] = array(
					'label' => $qr_res->get('name'),
					'description' => _t('Can edit the objects from this collection')
				);
			}

			$pa_role_list['plugin_collectionRoles'] = array(
				'label' => _t('Collection based roles'),
				'description' => _t('Actions for collection based roles plugin'),
				'actions' => $actions
			);

			return $pa_role_list;
		}

	}
?>