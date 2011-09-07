<?php
/* ----------------------------------------------------------------------
 * plugins/userImageAnnotations/controllers/AnnotationsController.php :
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

 	require_once(__CA_LIB_DIR__.'/core/TaskQueue.php');
 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 	require_once(__CA_LIB_DIR__."/ca/BaseSearchController.php");
 	require_once(__CA_MODELS_DIR__.'/ca_object_representations.php');
 	require_once(__CA_MODELS_DIR__.'/ca_representation_annotations.php');
 	require_once(__CA_MODELS_DIR__.'/ca_locales.php');
 	require_once(__CA_MODELS_DIR__.'/ca_user_annotations.php');
 	include_once(__CA_APP_DIR__."/plugins/userImageAnnotations/lib/Search/UserImageAnnotationSearch.php");

 	class AnnotationsController extends BaseSearchController {
 		# -------------------------------------------------------
 		protected $opo_config;		// plugin configuration file
 		protected $opa_locales;

 		protected $ops_tablename = 'ca_user_annotations';
 		protected $opa_items_per_page = array(10, 20, 30, 40, 50);
 		protected $opa_views;
 		protected $opa_sorts;
 		protected $opa_required_actions = array('can_manage_user_annotations');
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);

 			if (!$this->request->user->canDoAction('can_manage_user_annotations')) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3000?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}

 			$this->opo_config = Configuration::load(__CA_APP_DIR__.'/plugins/userImageAnnotations/conf/userImageAnnotations.conf');

			$t_locale = new ca_locales();
			$this->opa_locales = $t_locale->getLocaleList(array('return_display_values' => true, 'index_by_code' => false, 'sort_field' => 'name', 'sort_direction' => 'asc'));

			$this->opa_views = array(
				'list' => _t('list')
			);

			$this->opa_sorts = array(
				'ca_user_annotations.row_id' => _t('photo'),
				'ca_user_annotations.user_id' => _t('user')
			);

			JavascriptLoadManager::register('tableList');
			JavascriptLoadManager::register('annotate');
 		}
 		# -------------------------------------------------------
 		public function Index() {
 			$t_user_annotations = new ca_user_annotations();
 			$this->view->setVar('t_user_annotations', $t_user_annotations);
 			$annotations_list = $t_user_annotations->getUnmoderatedAnnotations();
 			$this->view->setVar('annotations_list', $annotations_list);
 			if(count($annotations_list) <= 0){
 				$this->notification->addNotification(_t("There are no unmoderated annotations"), __NOTIFICATION_TYPE_INFO__);
 			}
 			$this->render('user_image_annotations_html.php');
 		}
		# ----
 		public function ListModerated() {
 			$t_user_annotations = new ca_user_annotations();
 			$this->view->setVar('t_user_annotations', $t_user_annotations);
 			$annotations_list = $t_user_annotations->getModeratedAnnotations();
 			$this->view->setVar('annotations_list', $annotations_list);
 			$this->view->setVar('moderated', true);
 			if(count($annotations_list) <= 0){
 				$this->notification->addNotification(_t("There are no unmoderated annotations"), __NOTIFICATION_TYPE_INFO__);
 			}
 			$this->render('user_image_annotations_html.php');
 			// This doesn't work because annotations aren't indexed
 			// return parent::Index(new UserImageAnnotationSearch());
 		}
 		# -------------------------------------------------------
 		public function Approve() {
 			$va_errors = array();
 			$pa_annotation_ids = $this->request->getParameter('annotation_id', pArray);

 			if(is_array($pa_annotation_ids) && (sizeof($pa_annotation_ids) > 0)){
				foreach($pa_annotation_ids as $vn_user_annotation_id){

					$t_user_annotation = new ca_user_annotations($vn_user_annotation_id);

					$vn_locale_id = $t_user_annotation->get('locale_id');
					if(is_null($vn_locale_id)) {
						global $g_ui_locale;
						$t_locale = new ca_locales();
						$vn_locale_id = $t_locale->loadLocaleByCode($g_ui_locale);
					}

					if (!$t_user_annotation->getPrimaryKey()) {
						$va_errors[] = _t("The annotation does not exist");
						break;
					}

					$t_representation = new ca_object_representations();
					if ($t_representation->load($t_user_annotation->get('row_id'))) {

						$media_info = $t_representation->get('media');

						$original_width = $media_info["original"]["PROPERTIES"]["width"];
						$original_height = $media_info["original"]["PROPERTIES"]["height"];

						$annotation_width = $media_info["annotation"]["PROPERTIES"]["width"];
						$annotation_height = $media_info["annotation"]["PROPERTIES"]["height"];

						$h_factor = $original_width / $annotation_width;
						$v_factor = $original_height / $annotation_height;

						$original_top = $t_user_annotation->get('original_top');
						$original_left = $t_user_annotation->get('original_left');
						$original_width = $t_user_annotation->get('original_width');
						$original_height = $t_user_annotation->get('original_height');

						$top = $original_top / $v_factor;
						$left = $original_left / $h_factor;
						$width = $original_width / $h_factor;
						$height = $original_height / $v_factor;

						$annotation = utf8_decode($t_user_annotation->get('annotation'));

						$vn_properties = array(
							'top' => $top,
							'left' => $left,
							'width' => $width,
							'height' => $height,
							'original_top' => $original_top,
							'original_left' => $original_left,
							'original_width' => $original_width,
							'original_height' => $original_height,
							'text' => $annotation,
						);

						$vn_new_annotation_id = $t_representation->addAnnotation($vn_locale_id, $this->request->getUserID(), $vn_properties, 1, 1); // Afgewerkt en Afgewerkt (publiceren)

						if($vn_new_annotation_id <= 0) {
							$va_errors[] = _t("Could not approve annotation");
							break;
						}

						$t_new_annotation = new ca_representation_annotations();
						if ($t_new_annotation->load($vn_new_annotation_id)) {
							$label_id = $t_new_annotation->addLabel(array('name' => $annotation), $vn_locale_id, null, true);
							if($label_id <= 0) {
								$va_errors[] = _t("Could not add label to the annotation");
								break;
							}
						} else {
							$va_errors[] = _t("Could not add label to the annotation");
							break;
						}

					} else {
						$va_errors[] = _t("The representation for this annotation doesn't exist");
						break;
					}

					if (!$t_user_annotation->moderate($this->request->getUserID())) {
		 				$va_errors[] = _t("Could not approve annotation");
						break;
					}

				}
				if(sizeof($va_errors) > 0){
					$this->notification->addNotification(implode("; ", $va_errors), __NOTIFICATION_TYPE_ERROR__);
				}else{
					$this->notification->addNotification(_t("Your annotations have been approved"), __NOTIFICATION_TYPE_INFO__);
				}
			}else{
				$this->notification->addNotification(_t("Please use the checkboxes to select annotations for approval"), __NOTIFICATION_TYPE_WARNING__);
			}

 			$this->Index();
 		}
 		# -------------------------------------------------------
 		public function Delete() {
 			$va_errors = array();
 			$pa_annotation_ids = $this->request->getParameter('annotation_id', pArray);

 			if(is_array($pa_annotation_ids) && (sizeof($pa_annotation_ids) > 0)){
				foreach($pa_annotation_ids as $vn_user_annotation_id){

					$t_user_annotation = new ca_user_annotations($vn_user_annotation_id);

					if (!$t_user_annotation->getPrimaryKey()) {
						$va_errors[] = _t("The annotation does not exist");
						break;
					}
					$t_user_annotation->setMode(ACCESS_WRITE);;
					if (!$t_user_annotation->delete()) {
		 				$va_errors[] = _t("Could not delete annotation");
						break;
					}

				}
				if(sizeof($va_errors) > 0){
					$this->notification->addNotification(implode("; ", $va_errors), __NOTIFICATION_TYPE_ERROR__);
				}else{
					$this->notification->addNotification(_t("Your annotations have been deleted"), __NOTIFICATION_TYPE_INFO__);
				}
			}else{
				$this->notification->addNotification(_t("Please use the checkboxes to select annotations for deletion"), __NOTIFICATION_TYPE_WARNING__);
			}

			$this->Index();
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns string representing the name of the item the search will return
 		 *
 		 * If $ps_mode is 'singular' [default] then the singular version of the name is returned, otherwise the plural is returned
 		 */
 		public function searchName($ps_mode='singular') {
 			return ($ps_mode == 'singular') ? _t("userannotation") : _t("user annotations");
 		}
 	}
 ?>