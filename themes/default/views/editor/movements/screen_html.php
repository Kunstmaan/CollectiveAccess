<?php
/* ----------------------------------------------------------------------
 * app/views/editor/movements/screen_html.php : 
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
 * ----------------------------------------------------------------------
 */
 	$t_movement 			= $this->getVar('t_subject');
	$vn_movement_id 		= $this->getVar('subject_id');

	$vb_can_create		= $this->request->user->canDoAction('can_create_ca_movements');
	$vb_can_edit		= $this->request->user->canDoAction('can_edit_ca_movements');
	$vb_can_delete		= $this->request->user->canDoAction('can_delete_ca_movements');

	$vb_print_buttons = (intval($vn_movement_id) > 0 ? $vb_can_edit : $vb_can_create);
	
	print $vs_control_box = caFormControlBox(
		($vb_can_edit ? caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'MovementEditorForm') : '').' '.
		($vb_can_edit ? caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'editor/movements', 'MovementEditor', 'Edit/'.$this->request->getActionExtra(), array('movement_id' => $vn_movement_id)) : ''), 
		'', 
		((intval($vn_movement_id) > 0) && $vb_can_delete) ? caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'editor/movements', 'MovementEditor', 'Delete/'.$this->request->getActionExtra(), array('movement_id' => $vn_movement_id)) : ''
	);
?>
	<div class="sectionBox">
<?php
			print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/movement_id/'.$vn_movement_id, 'MovementEditorForm', null, 'POST', 'multipart/form-data');
			
			$va_form_elements = $t_movement->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
									'request' => $this->request, 
									'formName' => 'MovementEditorForm'));
			
			print join("\n", $va_form_elements);
			
			print $vs_control_box;
?>
			<input type='hidden' name='movement_id' value='<?php print $vn_movement_id; ?>'/>
		</form>
	</div>

	<div class="editorBottomPadding"><!-- empty --></div>