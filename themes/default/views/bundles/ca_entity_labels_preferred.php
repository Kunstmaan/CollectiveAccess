<?php
/* ----------------------------------------------------------------------
 * bundles/ca_entity_labels_preferred.php : 
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
 
	$vs_id_prefix 						= $this->getVar('placement_code').$this->getVar('id_prefix');
	$va_labels 							= $this->getVar('labels');
	$t_label 								= $this->getVar('t_label');
	$va_initial_values 					= $this->getVar('label_initial_values');
	if (!$va_force_new_labels 		= $this->getVar('new_labels')) { $va_force_new_labels = array(); }	// list of new labels not saved due to error which we need to for onto the label list as new

?>
<div id="<?php print $vs_id_prefix; ?>Labels">
<?php
	//
	// The bundle template - used to generate each bundle in the form
	//
?>
	<textarea class='caLabelTemplate' style='display: none;'>
		<div id="{fieldNamePrefix}Label_{n}" class="labelInfo">
			<div style="float: right;">
				<a href="#" class="caDeleteLabelButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
			</div>
			<table class="objectRepresentationListItem">
				<tr valign="middle">
					<td>
						<table>
							<tr>
								<td>
									<?php print $t_label->htmlFormElement('prefix', null, array('name' => "{fieldNamePrefix}prefix_{n}", 'id' => "{fieldNamePrefix}prefix_{n}", "value" => "{{prefix}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td>
									<?php print $t_label->htmlFormElement('forename', null, array('name' => "{fieldNamePrefix}forename_{n}", 'id' => "{fieldNamePrefix}forename_{n}", "value" => "{{forename}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td>
									<?php print $t_label->htmlFormElement('middlename', null, array('name' => "{fieldNamePrefix}middlename_{n}", 'id' => "{fieldNamePrefix}middlename_{n}", "value" => "{{middlename}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td>
									<?php print $t_label->htmlFormElement('surname', null, array('name' => "{fieldNamePrefix}surname_{n}", 'id' => "{fieldNamePrefix}surname_{n}", "value" => "{{surname}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td>
									<?php print $t_label->htmlFormElement('suffix', null, array('name' => "{fieldNamePrefix}suffix_{n}", 'id' => "{fieldNamePrefix}suffix_{n}", "value" => "{{suffix}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
							</tr>
							<tr>
								<td>
									<?php print '<div class="formLabel">'.$t_label->htmlFormElement('locale_id', "^LABEL ^ELEMENT", array('classname' => 'labelLocale', 'id' => "{fieldNamePrefix}locale_id_{n}", 'name' => "{fieldNamePrefix}locale_id_{n}", "value" => "{locale_id}", 'no_tooltips' => true, 'dont_show_null_value' => true, 'hide_select_if_only_one_option' => true, 'WHERE' => array('(dont_use_for_cataloguing = 0)'))); ?>
									<?php print $t_label->htmlFormElement('type_id', "^LABEL ^ELEMENT", array('classname' => 'labelType', 'id' => "{fieldNamePrefix}type_id_{n}", 'name' => "{fieldNamePrefix}type_id_{n}", "value" => "{type_id}", 'no_tooltips' => true, 'list_code' => 'entity_label_types_preferred', 'dont_show_null_value' => true, 'hide_select_if_no_options' => true)).'</div>'; ?>
								</td>
								<td>
									<?php print $t_label->htmlFormElement('other_forenames', null, array('name' => "{fieldNamePrefix}other_forenames_{n}", 'id' => "{fieldNamePrefix}other_forenames_{n}", "value" => "{{other_forenames}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td colspan="3"><?php print $t_label->htmlFormElement('displayname', null, array('name' => "{fieldNamePrefix}displayname_{n}", 'id' => "{fieldNamePrefix}displayname_{n}", "value" => "{{displayname}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred', 'textAreaTagName' => 'textentry')); ?><td>
							<tr>
						</table>
					</td>
				</tr>
			</table>
		</div>
<?php
	print TooltipManager::getLoadHTML('bundle_ca_entity_labels_preferred');
?>
	</textarea>
	
	<div class="bundleContainer">
		<div class="caLabelList">
		
		</div>
		<div class='button labelInfo caAddLabelButton'><a href='#'><?php print caNavIcon($this->request, __CA_NAV_BUTTON_ADD__); ?> <?php print _t("Add entity label"); ?></a></div>
	</div>
</div>
<script type="text/javascript">
	caUI.initLabelBundle('#<?php print $vs_id_prefix; ?>Labels', {
		mode: 'preferred',
		fieldNamePrefix: '<?php print $vs_id_prefix; ?>',
		templateValues: ['displayname', 'prefix', 'forename', 'other_forenames', 'middlename', 'surname', 'suffix', 'locale_id', 'type_id'],
		initialValues: <?php print json_encode($va_initial_values); ?>,
		forceNewValues: <?php print json_encode($va_force_new_labels); ?>,
		labelID: 'Label_',
		localeClassName: 'labelLocale',
		templateClassName: 'caLabelTemplate',
		labelListClassName: 'caLabelList',
		addButtonClassName: 'caAddLabelButton',
		deleteButtonClassName: 'caDeleteLabelButton'
	});
</script>