<?php
/* ----------------------------------------------------------------------
 * bundles/ca_object_lots.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
 
	$vs_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
	$t_item 				= $this->getVar('t_item');			// object_lot
	$t_subject 			= $this->getVar('t_subject');		// object
	$va_settings 		= 	$this->getVar('settings');
	$vs_add_label 		= $this->getVar('add_label');
	
	$t_item->load($vn_lot_id = $t_subject->get('lot_id'));
	
	
	$va_force_new_values = $this->getVar('forceNewValues');
	$va_initial_values = $this->getVar('initialValues');
	
	// put brackets around idno_stub for presentation
	foreach($va_initial_values as $vn_i => $va_lot_info) {
		if ($va_initial_values[$vn_i]['idno_stub']) {
			$va_initial_values[$vn_i]['idno_stub'] = '['.$va_initial_values[$vn_i]['idno_stub'].'] ';
		}
	}
	
	// put brackets around idno_stub for presentation
	foreach($va_force_new_values as $vn_i => $va_lot_info) {
		if ($va_force_new_values[$vn_i]['idno_stub']) {
			$va_force_new_values[$vn_i]['idno_stub'] = '['.$va_force_new_values[$vn_i]['idno_stub'].'] ';
		}
	}
?>
<div id="<?php print $vs_id_prefix.$t_item->tableNum().'_rel'; ?>">
<?php
	//
	// The bundle template - used to generate each bundle in the form
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo">
			<table class="caListItem">
				<tr>
					<td>
						<input type="text" size="60" name="<?php print $vs_id_prefix; ?>_autocomplete{n}" value="{{_display}}" id="<?php print $vs_id_prefix; ?>_autocomplete{n}" class="lookupBg"/>
					</td>
					<td>
<?php
	if (sizeof($this->getVar('relationship_types_by_sub_type'))) {
?>
						<select name="<?php print $vs_id_prefix; ?>_type_id{n}" id="<?php print $vs_id_prefix; ?>_type_id{n}" style="display: none;"></select>
<?php
	}
?>
						<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
					</td>
					<td>
						<a href="#" class="caDeleteItemButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
						
						<a href="<?php print urldecode(caEditorUrl($this->request, 'ca_object_lots', '{lot_id}')); ?>" class="caEditItemButton" id="<?php print $vs_id_prefix; ?>_edit_related_{n}"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_GO__); ?></a>
					</td>
				</tr>
			</table>
			<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
		</div>
	</textarea>
	
	<div class="bundleContainer">
		<div class="caItemList">
		
		</div>
		<div class='button labelInfo caAddItemButton'><a href='#'><?php print caNavIcon($this->request, __CA_NAV_BUTTON_ADD__); ?> <?php print $vs_add_label ? $vs_add_label : _t("Add lot"); ?></a></div>
	</div>
</div>
			
<script type="text/javascript">
	jQuery(document).ready(function() {
		caUI.initRelationBundle('#<?php print $vs_id_prefix.$t_item->tableNum().'_rel'; ?>', {
			fieldNamePrefix: '<?php print $vs_id_prefix; ?>_',
			initialValues: <?php print json_encode($va_initial_values); ?>,
			forceNewValues: <?php print json_encode($va_force_new_values); ?>,
			itemID: '<?php print $vs_id_prefix; ?>Item_',
			templateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			hideOnNewIDList: ['<?php print $vs_id_prefix; ?>_edit_related_'],
			autocompleteUrl: '<?php print caNavUrl($this->request, 'lookup', 'ObjectLot', 'Get', array()); ?>',
<?php
	if ($t_subject->tableName() == 'ca_objects') {
?>
			minRepeats: 0,
			maxRepeats: 1,
			templateValues: ['_display', 'idno_stub', 'id'],
			relationshipTypes: {},
<?php
	} else {
?>
			relationshipTypes: <?php print json_encode($this->getVar('relationship_types_by_sub_type')); ?>,
			templateValues: ['_display', 'idno_stub', 'id', 'type_id'],
<?php
	}
?>
			showEmptyFormsOnLoad: 0
		});
	});
</script>