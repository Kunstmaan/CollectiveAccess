<?php
/* ----------------------------------------------------------------------
 * bundles/ca_representation_annotations.php : 
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
 
	$t_subject 			= $this->getVar('t_subject');		// object representation
	if (	// don't show bundle if this representation doesn't use bundles to edit annotations
		!method_exists($t_subject, "getAnnotationType") || 
		!$t_subject->getAnnotationType() ||
		!method_exists($t_subject, "useBundleBasedAnnotationEditor") || 
		!$t_subject->useBundleBasedAnnotationEditor()
	) { 	
?>
		<span class='heading'><?php print _t('Annotations are not supported for this type of media'); ?></span>
<?php
			return; 
	}
	
	$vs_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
	$t_item 			= $this->getVar('t_item');				// object representation annotation
	$t_item_label 		= $this->getVar('t_item_label');	// object representation annotation_labels
	
	$vs_annotation_type = $t_subject->getAnnotationType();
	$o_properties 		= $t_subject->getAnnotationPropertyCoderInstance($vs_annotation_type);
	$vs_goto_property 	= $o_properties->getAnnotationGotoProperty();
	$va_prop_list 		= $va_init_props = array();

	if ($vs_annotation_type == 'ImageBased') {
		$hasdeletehandler = true;
		$addbuttonclassname = "cAddItemButton";
	} else {
		$addbuttonclassname = "caAddItemButton";
	}

	foreach(($va_properties = $o_properties->getPropertyList()) as $vs_property) { 
		$va_prop_list[] = "'".$vs_property."'"; $va_init_props[$vs_property] = ''; 
	}
	
	// get existing annotations
	$va_inital_values = $this->getVar('initialValues');
	$va_errors = array();
	
	if (sizeof($va_inital_values)) {
		foreach ($va_inital_values as $vn_annotation_id => $va_info) {
			if(is_array($va_action_errors = $this->request->getActionErrors('ca_representation_annotations', $vn_annotation_id))) {
				foreach($va_action_errors as $o_error) {
					$va_errors[$vn_annotation_id][] = array('errorDescription' => $o_error->getErrorDescription(), 'errorCode' => $o_error->getErrorNumber());
				}
			}
		}
	}
	
	$va_failed_inserts = array();
	foreach($this->request->getActionErrorSubSources('ca_representation_annotations') as $vs_error_subsource) {
		if (substr($vs_error_subsource, 0, 4) === 'new_') {
			$va_action_errors = $this->request->getActionErrors('ca_representation_annotations', $vs_error_subsource);
			foreach($va_action_errors as $o_error) {
				$va_failed_inserts[] = array_merge($va_init_props, array('_errors' => array(array('errorDescription' => $o_error->getErrorDescription(), 'errorCode' => $o_error->getErrorNumber()))));
			}
		}
	}
?>

<!-- BEGIN Media Player -->
<?php
print $t_subject->getMediaTag('media', $o_properties->getDisplayMediaVersion(), array('request' => $this->request, 'viewer_width' => 725, 'viewer_height' => 370, 'viewer_base_url' => $this->request->getBaseUrlPath(), 'viewer_theme_url' => $this->request->getThemeUrlPath(), 'id' => 'annotation_media_player', 'poster_frame_url' => $t_subject->getMediaUrl('media', 'medium'), 'name' => 'media_'.$t_subject->getPrimaryKey(), 'annotate' => 'true', 'addButtonClassName' => 'caAddItemButton', 'object_id' => $t_subject->getPrimaryKey()));
?>
<!-- END Media Player -->

<!-- BEGIN Annotation List -->
<div id="<?php print $vs_id_prefix.$t_item->tableNum().'_annotations'; ?>">
<?php
	//
	// The bundle template - used to generate each bundle in the form
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="annotationItem">
			<span class="formLabelError">{error}</span>
			<table class="representationAnnotationListItem">
				<tr>
<?php
		// annotation-type specific fields
		if(is_array($va_properties) && (sizeof($va_properties) > 0)){
			print "<td><table border='0' class='representationAnnotationListItem'><tr>";
			$vn_col_count = 0;
			foreach($va_properties as $vs_property) {
				print '<td>'.$o_properties->htmlFormElement($vs_property, array('classname' => 'labelLocale', 'id' => "{fieldNamePrefix}".$vs_property."_{n}",  'name' => "{fieldNamePrefix}".$vs_property."_{n}", "value" => "{{".$vs_property."}}"))."</td>\n";
				$vn_col_count++;
			}
			if ($vs_goto_property) {
?>
					</tr><tr><td <?php print ($vn_col_count > 1) ? "colspan='".$vn_col_count."'" : ""; ?>><a href="#" onclick="jQuery('#annotation_media_player').jPlayer('playHeadTime', parseInt({{startTimecode_raw}} * 1000) ? parseInt({{startTimecode_raw}} * 1000) : 0); return false;" class="button" id="{fieldNamePrefix}gotoButton_{n}"><?php print _t('Play Clip'); ?> &rsaquo;</a></td>
<?php
			}
			print "</tr></table></td>";
		}
?>
					<td><?php print $t_item_label->htmlFormElement('name', null, array('classname' => 'labelLocale', 'id' => "{fieldNamePrefix}label_{n}", 'name' => "{fieldNamePrefix}label_{n}", "value" => "{{label}}", 'no_tooltips' => false, 'width' => 35,'textAreaTagName' => 'textentry')); ?></td>
					<td><a href="#" onclick="jQuery('#{fieldNamePrefix}moreOptions_{n}').slideToggle(250); return false;" class="button"><?php print _t('More'); ?> &rsaquo;</a></td>

					<td>
						<a href="#" class="caDeleteItemButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
					</td>
				</tr>
			</table>
			<div style="display:none;" id="{fieldNamePrefix}moreOptions_{n}">
				<table class="representationAnnotationListItem">
					<tr>
						<td><?php print $t_item_label->htmlFormElement('locale_id', null, array('classname' => 'labelLocale', 'id' => "{fieldNamePrefix}locale_id_{n}", 'name' => "{fieldNamePrefix}locale_id_{n}", "value" => "", 'no_tooltips' => false, 'WHERE' => array('(dont_use_for_cataloguing = 0)'))); ?></td>
						<td><?php print $t_item->htmlFormElement('status', null, array('classname' => 'labelLocale', 'id' => "{fieldNamePrefix}status_{n}", 'name' => "{fieldNamePrefix}status_{n}", "value" => "", 'no_tooltips' => false)); ?></td>
						<td><?php print $t_item->htmlFormElement('access', null, array('classname' => 'labelLocale', 'id' => "{fieldNamePrefix}access_{n}", 'name' => "{fieldNamePrefix}access_{n}", "value" => "", 'no_tooltips' => false)); ?></td>
						<td><?php print urldecode(caNavLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__), '', 'editor/representation_annotations', 'RepresentationAnnotationEditor', 'Edit', array('annotation_id' => "{n}"), array('id' => "{fieldNamePrefix}edit_{n}"))); ?></td>
					</tr>
				</table>
			</div>
		</div>
	</textarea>

	<div class="bundleContainer">
		<div class='button labelInfo caAddItemButton'><a href='#'><?php print caNavIcon($this->request, __CA_NAV_BUTTON_ADD__); ?> <?php print _t("Add annotation"); ?> &rsaquo;</a></div>
		<div class="caItemList" style="width: 100%; overflow-y: auto; min-height: 300px;";>

		</div>
	</div>
</div>

<script type="text/javascript">
	var bundle = caUI.initBundle('#<?php print $vs_id_prefix.$t_item->tableNum().'_annotations'; ?>', {
		fieldNamePrefix: '<?php print $vs_id_prefix; ?>_',
		templateValues: ['status', 'access', 'locale_id', 'label', <?php print join(',', $va_prop_list); ?>],
		initialValues: <?php print json_encode($va_inital_values); ?>,
		errors: <?php print json_encode($va_errors); ?>,
		forceNewValues: <?php print json_encode($va_failed_inserts); ?>,
		itemID: '<?php print $vs_id_prefix; ?>Item_',
		templateClassName: 'caItemTemplate',
		itemListClassName: 'caItemList',
		addButtonClassName: '<?php print $addbuttonclassname; ?>',
		deleteButtonClassName: 'caDeleteItemButton',
		showOnNewIDList: [],
		hideOnNewIDList: ['<?php print $vs_id_prefix; ?>_gotoButton_'],
		addMode: 'prepend',
		showEmptyFormsOnLoad:0,
		incrementLocalesForNewBundles: false,
		onDeleteItem:function(id){ if('<?php print $hasdeletehandler ?>'=='1'){ deleteHandler(id); } }
	});

if('<?php print $hasdeletehandler; ?>'=='1'){
	function deleteHandler(id){
		var id_prefix='<?php print $vs_id_prefix; ?>';
		var fl_top = id_prefix+'_top_'+id;
		var fl_left =id_prefix+'_left_'+id;

 		var remove_top=$('#'+fl_top).val();
 		var remove_left=$('#'+fl_left).val();

		$(".image-annotate-area").each(function(){

			var element = this;
			var left = element.style.left;
			var top = element.style.top;

			if((remove_top==parseInt(top)) && (remove_left==parseInt(left))){
				$(this).remove();
				return false;
			}
		});
	}
}

</script>
<!-- END Annotation List -->
