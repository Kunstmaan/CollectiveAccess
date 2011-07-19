<?php
/** ---------------------------------------------------------------------
 * app/helpers/gisHelpers.php : GIS/mapping utility  functions
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
 * @package CollectiveAccess
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

 /**
   *
   */
    
 	/**
 	 * Converts $ps_value from degrees minutes seconds format to decimal
 	 */
	function caGISminutesToSignedDecimal($ps_value){
		$ps_value = trim($ps_value);
		$vs_value = preg_replace('/[^0-9A-Za-z\.\-]+/', ' ', $ps_value);
		
		if ($vs_value === $ps_value) { return $ps_value; }
		list($vn_deg, $vn_min, $vn_sec, $vs_dir) = explode(' ',$vs_value);
		$vn_pos = ($vn_deg < 0) ? -1:1;
		if (in_array(strtoupper($vs_dir), array('S', 'W'))) { $vn_pos = -1; }
		
		$vn_deg = abs(round($vn_deg,6));
		$vn_min = abs(round($vn_min,6));
		$vn_sec = abs(round($vn_sec,6));
		return round($vn_deg+($vn_min/60)+($vn_sec/3600),6)*$vn_pos;
	}
	
	/**
 	 * Converts $ps_value from decimal with N/S/E/W to signed decimal
 	 */
	function caGISDecimalToSignedDecimal($ps_value){
		$ps_value = trim($ps_value);
		list($vn_left_of_decimal, $vn_right_of_decimal, $vs_dir) = preg_split('![\. ]{1}!',$ps_value);
		if (preg_match('!([A-Za-z]+)$!', $vn_right_of_decimal, $va_matches)) {
			$vs_dir = $va_matches[1];
			$vn_right_of_decimal = preg_replace('!([A-Za-z]+)$!', '', $vn_right_of_decimal);
		}
		$vn_pos = 1;
		if (in_array(strtoupper($vs_dir), array('S', 'W'))) { $vn_pos = -1; }
		
		return floatval($vn_left_of_decimal.'.'.$vn_right_of_decimal) * $vn_pos;
	}
	
	/**
	 * Returns true if $ps_value is in degrees minutes seconds format
	 */ 
	function caGISisDMS($ps_value){
		if(preg_match('/[^0-9A-Za-z\.\- ]+/', $ps_value)) {
			return true;
		}
		return false;
	}
?>