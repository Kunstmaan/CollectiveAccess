<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/objects/ObjectEditorController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2011 Whirl-i-Gig
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
 
 	require_once(__CA_MODELS_DIR__."/ca_objects.php"); 
 	require_once(__CA_MODELS_DIR__."/ca_object_lots.php");
 	require_once(__CA_MODELS_DIR__."/ca_object_representation_multifiles.php");
 	require_once(__CA_LIB_DIR__."/core/Media.php");
 	require_once(__CA_LIB_DIR__."/core/Media/MediaProcessingSettings.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseEditorController.php");
 	
 
 	class ObjectEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_objects';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			JavascriptLoadManager::register('panel');
 		}
 		# -------------------------------------------------------
 		public function Edit() {
 			$va_values = array();
 			
 			if ($vn_lot_id = $this->request->getParameter('lot_id', pInteger)) {
 				$t_lot = new ca_object_lots($vn_lot_id);
 				
 				if ($t_lot->getPrimaryKey()) {
					$va_values['lot_id'] = $vn_lot_id;
					$va_values['idno'] = $t_lot->get('idno_stub');
				}
 			}
 			
 			return parent::Edit($va_values);
 		}
 		# -------------------------------------------------------
 		# AJAX handlers
 		# -------------------------------------------------------
 		/**
 		 * Returns content for overlay containing details for object representation
 		 */ 
 		public function GetRepresentationInfo($ps_view_name='ajax_object_representation_info_html.php') {
 			list($vn_object_id, $t_object) = $this->_initView();
 			$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
 			$ps_version = $this->request->getParameter('version', pString);
 			
 			$this->view->setVar('representation_id', $pn_representation_id);
 			$t_rep = new ca_object_representations($pn_representation_id);
 			$this->view->setVar('t_object_representation', $t_rep);
 			
 			$this->view->setVar('versions', $va_versions = $t_rep->getMediaVersions('media'));
 			
 			$va_info = $t_rep->getMediaInfo('media');
 			if (!in_array($ps_version, $va_versions)) { 
 				$o_settings = new MediaProcessingSettings($t_rep, 'media');
 				if (!($ps_version = $o_settings->getMediaDefaultViewingVersion($va_info['INPUT']['MIMETYPE']))) {
 					$ps_version = $va_versions[0]; 
 				}
 			}
 			$this->view->setVar('version', $ps_version);
 			
 			$va_rep_info = $t_rep->getMediaInfo('media', $ps_version);
 			$this->view->setVar('version_info', $va_rep_info);
 			
 			$t_media = new Media();
 			$this->view->setVar('version_type', $t_media->getMimetypeTypename($va_rep_info['MIMETYPE']));
 			
 			
 			$vn_num_multifiles = $t_rep->numFiles();
 			$this->view->setVar('num_multifiles', $vn_num_multifiles);
 			$this->view->setVar('num_pages', ceil($vn_num_multifiles/100));
 			
 			$va_reps = $t_object->getRepresentations(array('icon'));
 			$this->view->setVar('reps', $va_reps);
 			
 			return $this->render($ps_view_name);
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns content for single representation version
 		 */ 
 		public function GetRepresentationForDisplay() {
 			return $this->GetRepresentationInfo('ajax_object_representation_for_display_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns content for overlay containing details for object representation multifiles
 		 */ 
 		public function GetRepresentationMultifileInfo() {
 			list($vn_object_id, $t_object) = $this->_initView();
 			$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
 			$ps_version = $this->request->getParameter('version', pString);
 			
 			$this->view->setVar('representation_id', $pn_representation_id);
 			$t_rep = new ca_object_representations($pn_representation_id);
 			$this->view->setVar('t_object_representation', $t_rep);
 			
 			$this->view->setVar('versions', $va_versions = $t_rep->getMediaVersions('media'));
 			
 			$va_info = $t_rep->getMediaInfo('media');
 			if (!in_array($ps_version, $va_versions)) { 
 				$o_settings = new MediaProcessingSettings($t_rep, 'media');
 				if (!($ps_version = $o_settings->getMediaDefaultViewingVersion($va_info['INPUT']['MIMETYPE']))) {
 					$ps_version = $va_versions[0]; 
 				}
 			}
 			$this->view->setVar('version', $ps_version);
 			
 			$va_rep_info = $t_rep->getMediaInfo('media', $ps_version);
 			$this->view->setVar('version_info', $va_rep_info);
 			
 			$t_media = new Media();
 			$this->view->setVar('version_type', $t_media->getMimetypeTypename($va_rep_info['MIMETYPE']));
 			
 			
 			$vn_num_multifiles = $t_rep->numFiles();
 			$this->view->setVar('num_multifiles', $vn_num_multifiles);
 			$this->view->setVar('num_pages', ceil($vn_num_multifiles/100));
 			
 			return $this->render('ajax_object_representation_multifile_info_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */ 
 		public function GetRepresentationMultifileFileList() {
 			$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
 			$this->view->setVar('representation_id', $pn_representation_id);
 			$t_rep = new ca_object_representations($pn_representation_id);
 			$this->view->setVar('t_object_representation', $t_rep);
 			
 			$vn_num_multifiles = $t_rep->numFiles();
 			if (($pn_page = $this->request->getParameter('mp', pInteger)) < 1) { $pn_page = 1; }
 			if (($vn_start = ($pn_page-1) * 100) > $vn_num_multifiles) {
 				$vn_start = 0;
 				$pn_page = 1;
 			}
 			$this->view->setVar('page', $pn_page);
 			$this->view->setVar('num_pages', ceil($vn_num_multifiles/100));
 			$this->view->setVar('num_multifiles', $vn_num_multifiles);
 			$this->view->setVar('multifiles', $t_rep->getFileList(null, $vn_start, 100));
 			return $this->render('ajax_object_representation_multifile_filelist_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */ 
 		public function GetRepresentationMultifileMetadataDisplay() {
 			$vn_multifile_id = $this->request->getParameter('multifile_id', pInteger);
 			$t_multifile = new ca_object_representation_multifiles($vn_multifile_id);
 			$this->view->setVar('t_multifile', $t_multifile);
 			return $this->render('ajax_object_representation_multifile_metadata_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */ 
 		public function DownloadRepresentation() {
 			list($vn_object_id, $t_object) = $this->_initView();
 			$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
 			$ps_version = $this->request->getParameter('version', pString);
 			
 			$this->view->setVar('representation_id', $pn_representation_id);
 			$t_rep = new ca_object_representations($pn_representation_id);
 			$this->view->setVar('t_object_representation', $t_rep);
 			
 			$va_versions = $t_rep->getMediaVersions('media');
 			
 			if (!in_array($ps_version, $va_versions)) { $ps_version = $va_versions[0]; }
 			$this->view->setVar('version', $ps_version);
 			
 			$va_rep_info = $t_rep->getMediaInfo('media', $ps_version);
 			$this->view->setVar('version_info', $va_rep_info);
 			$this->view->setVar('version_path', $t_rep->getMediaPath('media', $ps_version));
 			
 			$va_info = $t_rep->getMediaInfo('media');
 			$vs_idno_proc = preg_replace('![^A-Za-z0-9_\-]+!', '_', $t_object->get('idno'));
 			switch($this->request->user->getPreference('downloaded_file_naming')) {
 				case 'idno':
 					$this->view->setVar('version_download_name', $vs_idno_proc.'.'.$va_rep_info['EXTENSION']);
					break;
 				case 'idno_and_version':
 					$this->view->setVar('version_download_name', $vs_idno_proc.'_'.$ps_version.'.'.$va_rep_info['EXTENSION']);
 					break;
 				case 'idno_and_rep_id_and_version':
 					$this->view->setVar('version_download_name', $vs_idno_proc.'_representation_'.$pn_representation_id.'_'.$ps_version.'.'.$va_rep_info['EXTENSION']);
 					break;
 				case 'original_name':
 				default:
 					if ($va_info['ORIGINAL_FILENAME']) {
 						if ($ps_version == 'original') {
 							$this->view->setVar('version_download_name', $va_info['ORIGINAL_FILENAME']);
 						} else {
 							$va_tmp = explode('.', $va_info['ORIGINAL_FILENAME']);
 							array_pop($va_tmp);
 							$this->view->setVar('version_download_name', join('_', $va_tmp).'.'.$va_rep_info['EXTENSION']);
 						}
 					} else {
 						$this->view->setVar('version_download_name', $vs_idno_proc.'_representation_'.$pn_representation_id.'_'.$ps_version.'.'.$va_rep_info['EXTENSION']);
 					}
 					break;
 			} 
 			
 			return $this->render('object_representation_download_binary.php');
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function info($pa_parameters) {
 			parent::info($pa_parameters);
 			return $this->render('widget_object_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>