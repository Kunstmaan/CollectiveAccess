<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/LCSHController.php : 
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
 	require_once(__CA_APP_DIR__."/helpers/displayHelpers.php");
 	require_once(__CA_LIB_DIR__."/core/Zend/Feed.php");
 	require_once(__CA_LIB_DIR__."/core/Zend/Feed/Atom.php");
 	
 
 	class LCSHController extends ActionController {
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		# AJAX handlers
 		# -------------------------------------------------------
		public function Get() {
			$ps_query = $this->request->getParameter('q', pString);
			$ps_type = $this->request->getParameter('type', pString);
			$va_items = array();
			if (unicode_strlen($ps_query) >= 3) {
				try {
					$vn_page = 0;
					
					//
					// Get up to 100 suggestions
					//
					while(true) {
						$vn_page++;
					
						$va_data = json_decode(file_get_contents("http://id.loc.gov/authorities/suggest/?q=".urlencode($ps_query).'&offset='.(50 * ($vn_page - 1))));
						
						if (isset($va_data[1]) && is_array($va_data[1])) {
							foreach($va_data[1] as $vn_i => $vs_term) {
								$vs_url = $va_data[3][$vn_i];
								$vs_idno = $vs_url;
								if (preg_match('!/(sh[\d]+)!', $vs_url, $va_matches)) { 
									$vs_idno = $va_matches[1];
								}
								$va_items[$vs_url] = array('displayname' => $vs_term, 'idno' => $vs_idno);
							}
						}
						if ($vn_page >= 2) { break; }
					}
				} catch (Exception $e) {
					$va_items['error'] = array('displayname' => _t('ERROR').':'.$e->getMessage(), 'idno' => '');
				}
			}
			
			$this->view->setVar('lcsh_list', $va_items);
 			return $this->render('ajax_lcsh_list_html.php');
		}
		# -------------------------------------------------------
 	}
 ?>