<?php
/* ----------------------------------------------------------------------
 * lookup/list_hierarchy_level_json.php : 
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
 
 	$va_list = $this->getVar('list_item_list');
 	foreach($va_list as $vn_key => $va_info) {
 		if (!is_numeric($vn_key)) { continue; }
 		if ($va_info['use_as_vocabulary']) {
 			$va_list[$vn_key]['name'] .= ' ['._t('VOC').'] ';
 		}
 		if ($va_info['is_system_list']) {
 			$va_list[$vn_key]['name'] .= ' ['._t('SYS').'] ';
 		}
 	}
	print json_encode($va_list);
?>