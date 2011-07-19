<?php
/* ----------------------------------------------------------------------
 * themes/default/views/administrate/setup/screen_edit_html.php :
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

	$t_screen = $this->getVar('t_screen');
	$vn_screen_id = $this->getVar('screen_id');
	$vn_ui_id = $this->getVar('ui_id');
?>
<div class="sectionBox">
<?php print
	"<div class='delete-control-box'>".
	caFormControlBox(
		'<div class="delete_warning_box">'._t('Really delete').' "'.$t_screen->getLabelForDisplay(false).'"?</div>',
		'',
		caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'administrate/setup', 'Interfaces', 'DeleteScreen', array('screen_id'=> $vn_screen_id, 'ui_id' => $vn_ui_id, 'confirm' => 1)).' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'administrate/setup', 'Interfaces', 'Edit', array('ui_id' => $vn_ui_id))
	)."</div>\n";
?>
</div>