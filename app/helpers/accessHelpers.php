<?php
/** ---------------------------------------------------------------------
 * app/helpers/accessHelpers.php : utility functions for checking user access
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
 * @package CollectiveAccess
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */ 
 
  /**
   *
   */
   
 require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 
	 # --------------------------------------------------------------------------------------------
	 /**
	  * Return list of values to validate object/entity/place/etc 'access' field against
	  * when considering whether to display the item or not. This method is intended to be used
	  * in web front-ends such as Pawtucket, not Providence, and so it does not currently consider the
	  * user's login status or login-based roles. Rather, it only considers whether access settings checks 
	  * are enabled (via the 'dont_enforce_access_settings' configuration directive) and whether the user
	  * is considered privileged.
	  *
	  * @param $po_request - the current request
	  * @return (array) - an array of integer values that, if present in a record, indicate that the record should be displayed to the current user
	  */
	function caGetUserAccessValues($po_request) {
		if (!(bool)$po_request->config->get('dont_enforce_access_settings')) {
			$vb_is_privileged = caUserIsPrivileged($po_request);
			if($vb_is_privileged) {
				return (array)$po_request->config->get('privileged_access_settings');
			} else {
				return (array)$po_request->config->get('public_access_settings');
			}
		}
		return array();
	}
	 # --------------------------------------------------------------------------------------------
	 /**
	  * Checks if current user is privileged. Currently only checks if IP address of user is on
	  * a privileged network, as defined by the 'privileged_networks' configuration directive. May 
	  * be expanded in the future to consider user's access rights and/or other parameters.
	  *
	  * @param $po_request - the current request
	  * @return (bool) - true if user is privileged, false if not
	  */
	function caUserIsPrivileged($po_request) {
		$o_config = Configuration::load();
		if (!($va_priv_ips = $o_config->getList('privileged_networks'))) {
			$va_priv_ips = array();
		}
		
		$va_user_ip = explode('.', $po_request->getClientIP());
		
		foreach($va_priv_ips as $vs_priv_ip) {
			$va_priv_ip = explode('.', $vs_priv_ip);
			
			$vb_is_match = true;
			for($vn_i=0; $vn_i < sizeof($va_priv_ip); $vn_i++) {
				if (($va_priv_ip[$vn_i] != '*') && ($va_priv_ip[$vn_i] != $va_user_ip[$vn_i])) {
					continue(2);
				}
			}
			return true;
		}
		return false;
	}
	# ---------------------------------------------------------------------------------------------
 ?>