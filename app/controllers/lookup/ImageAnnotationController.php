<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/ImageAnnotationController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
	require_once(__CA_LIB_DIR__."/core/Db.php");
	require_once(__CA_MODELS_DIR__."/ca_object_representations.php");

 	class ImageAnnotationController extends ActionController {
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		# AJAX handlers
 		# -------------------------------------------------------
		public function Get() {
			$object_id = $this->request->getParameter('object', pString);

			$rep  = new ca_object_representations($object_id);

			if (!($o_coder = $rep->getAnnotationPropertyCoderInstance($rep->getAnnotationType()))) {
				// does not support annotations
				return null;
			}

			$annotations = 	$rep->getAnnotations();

			$output = array();
			foreach($annotations as $annotation) {
				$output[] = array(
					'width' => $this->getValue($annotation,'width_raw'),
					'height' => $this->getValue($annotation,'height_raw'),
					'top' => $this->getValue($annotation,'top_raw'),
					'left' => $this->getValue($annotation,'left_raw'),
					'text' => $this->getLabel($annotation['labels']),
					'editable' => true,
					'id' => $annotation["annotation_id"]
				);
			}
			echo json_encode($output);
			exit;
		}
		function getValue($annotation,$f_name){
			if(isset($annotation[$f_name]) && $annotation[$f_name]!=''){
				return $annotation[$f_name];
			} else {
				return "0";
			}
		}

		function getLabel($array){
			if(count($array)==0){
				return "";
			}else{
				return $array;
			}
		}

 	}
 ?>