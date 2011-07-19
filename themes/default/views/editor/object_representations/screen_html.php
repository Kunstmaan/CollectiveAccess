<?php
/* ----------------------------------------------------------------------
 * views/editor/object_representations/screen_html.php : 
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
 	$t_object_representation 	= $this->getVar('t_subject');
	$vn_representation_id 		= $this->getVar('subject_id');

	$vb_can_create		= $this->request->user->canDoAction('can_create_ca_objects');
	$vb_can_edit		= $this->request->user->canDoAction('can_edit_ca_objects');
	$vb_can_delete		= $this->request->user->canDoAction('can_delete_ca_objects');

	$vb_print_buttons = (intval($vn_representation_id) > 0 ? $vb_can_edit : $vb_can_create);
	
	$control_box_top = caShowControlBox($this->request, $this->appconfig,'top');
	$control_box_bottom = caShowControlBox($this->request, $this->appconfig,'bottom');

    $vs_control_box = caFormControlBox(
		($vb_print_buttons ? caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'ObjectRepresentationEditorForm') : '').' '.
		($vb_print_buttons ? caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'editor/object_representations', 'ObjectRepresentationEditor', 'Edit/'.$this->request->getActionExtra(), array('representation_id' => $vn_representation_id)) : ''),
		'', 
		((intval($vn_representation_id) > 0) && $vb_can_delete) ? caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'editor/object_representations', 'ObjectRepresentationEditor', 'Delete/'.$this->request->getActionExtra(), array('representation_id' => $vn_representation_id)) : ''
	);

    if ($control_box_top) {
        print $vs_control_box;
    }
?>
	<div class="sectionBox">
<?php
			print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/representation_id/'.$vn_representation_id, 'ObjectRepresentationEditorForm', null, 'POST', 'multipart/form-data');
		
			$va_form_elements = $t_object_representation->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
									'request' => $this->request, 
									'formName' => 'ObjectRepresentationEditorForm'));
			
			print join("\n", $va_form_elements);
			
            if ($control_box_bottom) {
                print $vs_control_box;
            }
?>
			<input type='hidden' name='representation_id' value='<?php print $vn_representation_id; ?>'/>
		</form>
	</div>

	<div class="editorBottomPadding"><!-- empty --></div>
