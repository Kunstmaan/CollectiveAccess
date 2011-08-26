<?php
/* ----------------------------------------------------------------------
 * bundles/ca_occurrences.php : 
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
	$t_item 				= $this->getVar('t_item');				// occurrence
	$t_item_rel 			= $this->getVar('t_item_rel');
	$t_subject 			= $this->getVar('t_subject');		// the item we're linking occurrences to
	$va_settings 		= $this->getVar('settings');		// bundle settings
	$vs_add_label 		= $this->getVar('add_label');
	
	// params to pass during occurrence lookup
	$va_lookup_params = (isset($va_settings['restrict_to_type']) && $va_settings['restrict_to_type']) ? array('type' => $va_settings['restrict_to_type']) : array();
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
						<select name="<?php print $vs_id_prefix; ?>_type_id{n}" id="<?php print $vs_id_prefix; ?>_type_id{n}" style="display: none;"></select>
						<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
					</td>
					<td>
						<a href="#" class="caDeleteItemButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
						
						<a href="<?php print urldecode(caEditorUrl($this->request, 'ca_occurrences', '{occurrence_id}')); ?>" class="caEditItemButton" id="<?php print $vs_id_prefix; ?>_edit_related_{n}"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_GO__); ?></a>
					</td>
					<td>
						<span id="<?php print $vs_id_prefix; ?>_newitemlink{n}" style="display:none;">
						</span>
					</td>
				</tr>
			</table>
		</div>
	</textarea>
	
	<div class="bundleContainer">
		<div class="caItemList">
		
		</div>
		<div class='button labelInfo caAddItemButton'><a href='#'><?php print caNavIcon($this->request, __CA_NAV_BUTTON_ADD__); ?> <?php print $vs_add_label ? $vs_add_label : _t("Add relationship"); ?></a></div>
	</div>
</div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		caUI.initRelationBundle('#<?php print $vs_id_prefix.$t_item->tableNum().'_rel'; ?>', {
			fieldNamePrefix: '<?php print $vs_id_prefix; ?>_',
			templateValues: ['_display', 'type_id', 'id'],
			initialValues: <?php print json_encode($this->getVar('initialValues')); ?>,
			itemID: '<?php print $vs_id_prefix; ?>Item_',
			templateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			hideOnNewIDList: ['<?php print $vs_id_prefix; ?>_edit_related_'],
			showEmptyFormsOnLoad: 1,
			relationshipTypes: <?php print json_encode($this->getVar('relationship_types_by_sub_type')); ?>,
			autocompleteUrl: '<?php print caNavUrl($this->request, 'lookup', 'Occurrence', 'Get', $va_lookup_params); ?>',
			overlay : {
				inputid : 'autocomplete',
				linkid : 'newitemlink',
				textfieldname : 'Prefname',
				overlayurl : '<?php print caNavUrl($this->request, 'editor/occurrences', 'OccurrenceEditor', 'Edit').'/type_id/'; ?>',
				idfieldname : 'occurrence_id',
				availableTypes : <?php print json_encode($this->getVar('quickAddItemTypes')); ?>,
				newtext : '<?php print _t("new %1", _t("occurrence")); ?>'
			}
		});
	});
</script>