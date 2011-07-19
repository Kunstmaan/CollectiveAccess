<?php
/* ----------------------------------------------------------------------
 * app/views/manage/sets/widget_set_item_info_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source places management software
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
 	
 	$t_item = $this->getVar('t_item');
 	
 	if (!$t_item->getPrimaryKey()) {
 		$vn_element_count = ca_metadata_elements::getElementCount(true);
?>
<h3><?php print _t('Metadata elements'); ?>:
<div><?php
	if ($vn_element_count == 1) {
		print _t("1 element is defined");
	} else {
		print _t("%1 elements are defined", $vn_element_count);
	}
?></div>
</h3><?php
 	} else {
	 	print caEditorInspector($this, array('backText' => _t('Back to list')));
	 }
 ?>