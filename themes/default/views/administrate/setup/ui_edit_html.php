<?php
/* ----------------------------------------------------------------------
 * themes/default/views/administrate/setup/ui_edit_html.php :
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

	$t_editor_ui = $this->getVar('t_editor_ui');
	$t_screen = $this->getVar('t_screen');
	$vn_ui_id = $this->getVar('ui_id');
?>
<div class="sectionBox">
<?php
	print $vs_control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'UIsForm').' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'administrate/setup', 'Interfaces', 'ListUIs', array('ui_id' => 0)),
		'',
		caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'administrate/setup', 'Interfaces', 'Delete', array('ui_id' => $vn_ui_id))
	);
?>
<?php
	print caFormTag($this->request, 'Save', 'UIsForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => false));
?>
<div class='formLabel'><span id="_ca_editor_ui_labels_"><?php print _t("Labels"); ?></span><br/></div>
<?php
	print $t_editor_ui->getPreferredLabelHTMLFormBundle($this->request,'ui_labels', '');
	
	foreach($t_editor_ui->getFormFields() as $vs_f => $va_user_info) {
		print $t_editor_ui->htmlFormElement($vs_f, null, array('field_errors' => $this->request->getActionErrors('field_'.$vs_f)));
	}
?>
	<div class='formLabel'><span id="_ca_editor_ui_labels_"><?php print _t("Screens"); ?></span><br/></div>
	<div class="bundleContainer">
		<div class="caLabelList">
<?php
	if(!is_array($va_screens = $t_editor_ui->getScreens())) {
?>
	<div class='formLabel'><?php print _t('You will be able to configure screens for the user interface after saving it for the first time.');?></div>
<?php
	} else {

		foreach($va_screens as $va_screen) {
?>
		<div class="labelInfo">
			<!--<a href="<?php print caNavUrl($this->request,'administrate/setup','Interfaces','MoveScreenUp',array('ui_id' => $vn_ui_id, 'screen_id' => $va_screen['screen_id'])); ?>" class="caDeleteLabelButton">
				<?php print caNavIcon($this->request, __CA_NAV_BUTTON_ADD__); ?>
			</a>
			<a href="<?php print caNavUrl($this->request,'administrate/setup','Interfaces','MoveScreenDown',array('ui_id' => $vn_ui_id, 'screen_id' => $va_screen['screen_id'])); ?>" class="caDeleteLabelButton">
				<?php print caNavIcon($this->request, __CA_NAV_BUTTON_CANCEL__); ?>
			</a>-->
			<a href="<?php print caNavUrl($this->request,'administrate/setup','Interfaces','EditScreen',array('ui_id' => $vn_ui_id, 'screen_id' => $va_screen['screen_id'])); ?>" class="caDeleteLabelButton">
				<?php print caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__); ?>
			</a>
			<a href="<?php print caNavUrl($this->request,'administrate/setup','Interfaces','DeleteScreen',array('ui_id' => $vn_ui_id, 'screen_id' => $va_screen['screen_id'])); ?>" class="caDeleteLabelButton">
				<?php print caNavIcon($this->request, __CA_NAV_BUTTON_DELETE__); ?>
			</a>
			<span class="labelDisplay">
				<?php print intval($va_screen['is_default']) ? "<b>" : null; ?>
				<?php print $va_screen['name']; ?>
				<?php print intval($va_screen['is_default']) ? "("._t("default").")</b>" : null; ?>
			</span>
		</div>
<?php
		}
?>
		</div>
		<div class="button labelInfo caAddLabelButton">
			<a href="<?php print caNavUrl($this->request,'administrate/setup','Interfaces','EditScreen',array('ui_id' => $vn_ui_id, 'screen_id' => 0)); ?>">
				<?php print caNavIcon($this->request, __CA_NAV_BUTTON_ADD__); ?> <?php print _t("Add screen"); ?>
			</a>
<?php
	}
?>
		</div>
	</div>
	</form>
</div>
<div class="editorBottomPadding"><!-- empty --></div>