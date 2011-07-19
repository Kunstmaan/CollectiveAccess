<?php
/* ----------------------------------------------------------------------
 * app/views/manage/widget_group_info_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source places management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 
	$vn_group_count 	= $this->getVar('group_count');
	$t_group			= $this->getVar('t_item');
	
	if ($t_group && $t_group->getPrimaryKey()) {
		print caEditorInspector($this, array('backText' => _t('Back to list')));
	} else {
?>
	<h3><?php print _t('Your project teams'); ?>:
	<div><?php
			if ($vn_group_count == 1) {
				print _t("1 team is available");
			} else {
				print _t("%1 teams are available", $vn_group_count);
			}
	?></div>
	</h3>
<?php
	}
?>