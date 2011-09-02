<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/BaseService.php
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */
  
class BaseService {
	# -------------------------------------------------------
	protected $opo_request;
	# -------------------------------------------------------
	public function  __construct($po_request) {
		$this->opo_request = $po_request;

    // we set this to parse our date well, configurable in app.conf
		$config = Configuration::load();
		if ($vs_locale = $this->opo_request->config->get("default_service_locale")) {
			global $g_ui_locale;
			$g_ui_locale = $vs_locale;
		}
	}
	# -------------------------------------------------------
	/**
	 * Handles authentification
	 *
	 * @param string $username
	 * @param string $password
	 * @return int
	 */
	public function auth($username="",$password=""){
		if(($username != "") && ($password != "")){
			$va_options = array(
				"no_headers" => true,
				"dont_redirect" => true,
				"options" => array(),
				"user_name" => $username,
				"password" => $password,
			);
		} else {
			$va_options = array(
				"no_headers" => true,
				"dont_redirect" => true,
				"options" => array()
			);
		}
		
		$this->opo_request->doAuthentication($va_options);
		return $this->opo_request->getUserID();
	}
	# -------------------------------------------------------
	/**
	 * Log out
	 * 
	 * @return boolean
	 */
	public function deauthenticate(){
		$this->opo_request->deauthenticate();
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Fetches the user ID of the current user (neq zero if logged in)
	 *
	 * @return int
	 */
	public function getUserID(){
		return $this->opo_request->getUserID();
	}
	# -------------------------------------------------------
	/**
	 * we do it this way because nusoap or zend soap can't handle keys in array so we can't send the return_options directly. In stead we'll send an array with things we need.
	 *
	 * If the array is empty we'll return everything
	 *
	 * If you only want basic info use 'basic' or something that doesn't exist
	 */
	protected function generateReturnOptions($to_return = array(), $representation_versions = array()) {
		if(!isset($to_return) || !is_array($to_return) || empty($to_return)) {
			return array();
		}

		$items = array();
		foreach($to_return as $item) {
			$items[$item] = TRUE;
		}

		$result = array_merge(array(
			'primary_representation_only' => FALSE,
			'representations' => FALSE,
			'representation_annotations' => FALSE,
			'relations' => FALSE,
			'labels' => FALSE,
			'meta_data' => FALSE,
			'hierarchy' => FALSE,
			'tags' => FALSE,
			'comments' => FALSE,
			'rating' => FALSE
		), $items);

		if(isset($representation_versions) && is_array($representation_versions) && !empty($representation_versions)) {
			$result['representation_versions'] = $representation_versions;
		}

		return $result;
	}
	# -------------------------------------------------------
}

?>