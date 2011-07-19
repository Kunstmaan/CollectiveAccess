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

	$t_editor_ui = $this->getVar('t_editor_ui');
	$t_screen = $this->getVar('t_screen');
	$vn_screen_id = $this->getVar('screen_id');
	$vn_ui_id = $this->getVar('ui_id');
	$t_instance = $this->getVar('t_instance');
?>
<div class="sectionBox">
<?php
	print $vs_control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'ScreensForm').' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'administrate/setup', 'Interfaces', 'Edit', array('ui_id' => $vn_ui_id)),
		'',
		caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'administrate/setup', 'Interfaces', 'DeleteScreen', array('screen_id' => $vn_screen_id,'ui_id' => $vn_ui_id))
	);
?>
<?php
	print caFormTag($this->request, 'SaveScreen', 'ScreensForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => false));
?>
<div class='formLabel'><span id="_ca_editor_ui_labels_"><?php print _t("Screen labels"); ?></span><br/></div>
<?php
	print $t_screen->getPreferredLabelHTMLFormBundle($this->request, 'screen_labels', '');

	foreach($t_screen->getFormFields() as $vs_f => $va_user_info) {
		print $t_screen->htmlFormElement($vs_f, null, array('field_errors' => $this->request->getActionErrors('field_'.$vs_f)));
	}
?>
	<input type="hidden" name="ui_id" value="<?php print $vn_ui_id; ?>"/>
<?php
	if(is_array($va_bundle_placements = $t_screen->getBundlePlacements())):
?>
	<div class='formLabel'><span id="_ca_editor_ui_labels_"><?php print _t("Bundle placements"); ?></span><br/></div>
	<div class="bundleContainer">
		<div class="caLabelList">
<?php
		foreach($va_bundle_placements as $va_bundle_placement):
?>
		<div class="labelInfo">
			<a href="<?php print caNavUrl($this->request,'administrate/setup','Interfaces','MovePlacementUp',array('ui_id' => $vn_ui_id, 'screen_id' => $vn_screen_id, 'placement_id' => $va_bundle_placement['placement_id'])); ?>" class="caDeleteLabelButton">
				<?php print caNavIcon($this->request, __CA_NAV_BUTTON_ADD__); ?>
			</a>
			<a href="<?php print caNavUrl($this->request,'administrate/setup','Interfaces','MovePlacementDown',array('ui_id' => $vn_ui_id, 'screen_id' => $vn_screen_id, 'placement_id' => $va_bundle_placement['placement_id'])); ?>" class="caDeleteLabelButton">
				<?php print caNavIcon($this->request, __CA_NAV_BUTTON_CANCEL__); ?>
			</a>
			<a href="<?php print caNavUrl($this->request,'administrate/setup','Interfaces','DeletePlacement',array('ui_id' => $vn_ui_id, 'screen_id' => $vn_screen_id, 'placement_id' => $va_bundle_placement['placement_id'])); ?>" class="caDeleteLabelButton">
				<?php print caNavIcon($this->request, __CA_NAV_BUTTON_DELETE__); ?>
			</a>
			<span class="labelDisplay"><?php print $t_instance->getDisplayLabel($t_instance->tableName().'.'.$va_bundle_placement['bundle_name']);  ?></span>
		</div>
<?php
		endforeach;
?>
		</div>
		<div class="button labelInfo caAddLabelButton">
			<a href="#" onclick="jQuery('#placementAddForm').show(200); return false;" ><?php print caNavIcon($this->request, __CA_NAV_BUTTON_ADD__); ?> <?php print _t("Add bundle"); ?></a>
		</div>
	</div>
<?php
	endif;
?>

	</form>
<?php
	if ($t_instance) {
?>
	<div id="placementAddForm" class="labelInfo">
		<?php print caFormTag($this->request, 'AddPlacement', 'PlacementAddForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); ?>
		<a onclick="jQuery('#placementAddForm').hide(200);" href="#">
			<?php print caNavIcon($this->request, __CA_NAV_BUTTON_DELETE__); ?>
		</a>
<?php
			$va_bundle_select = array();
			foreach ($t_instance->getBundleList(array('includeBundleInfo' => true)) as $vs_bundle => $va_bundle_info) {
				$va_bundle_select[$vs_bundle] = $va_bundle_info['label'] ? $va_bundle_info['label'] : $vs_bundle;
			}
			
			natcasesort($va_bundle_select);
			$va_bundle_select = array_flip($va_bundle_select);
			
			print caHTMLSelect('placement_name', $va_bundle_select);
?>
		<input type="hidden" name="ui_id" value="<?php print $vn_ui_id; ?>"/>
		<input type="hidden" name="screen_id" value="<?php print $vn_screen_id; ?>"/>
		<?php print caFormSubmitLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_GO__), 'caDeleteLabelButton', 'PlacementAddForm'); ?>
		</form>
	</div>
	<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery('#placementAddForm').hide();
		});
	</script>
<?php
	}
	
	print $vs_control_box;
?>
</div>
<div class="editorBottomPadding"><!-- empty --></div>