<?php
/* ----------------------------------------------------------------------
 * themes/default/views/administrate/setup/ui_edit_html.php :
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
 * ----------------------------------------------------------------------
 */

	$t_editor_ui = $this->getVar('t_editor_ui');
	$vn_ui_id = $this->getVar('ui_id');
?>
<div class="sectionBox">
<?php
	print caDeleteWarningBox($this->request, $t_editor_ui->getLabelForDisplay(false), 'administrate/setup', 'Interfaces', 'Edit', array('ui_id' => $vn_ui_id));
?>
</div>