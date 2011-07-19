<?php
/* ----------------------------------------------------------------------
 * bundles/hierarchy_location.php : 
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
 
	JavascriptLoadManager::register('hierBrowser');
	JavascriptLoadManager::register('tabUI');
	
	$t_subject 			= $this->getVar('t_subject');
	$vs_subject_label	= $t_subject->getLabelForDisplay();
	if (($vs_priv_table = $t_subject->tableName()) == 'ca_list_items') { $vs_priv_table = 'ca_lists'; }		// actions happen to be on names for ca_lists for ca_list_items
	
	$pn_parent_id 		= $this->getVar('parent_id');
	$pa_ancestors 		= $this->getVar('ancestors');
	$pn_id 				= $this->getVar('id');
	$ps_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix').'HierLocation';
	$va_lookup_urls 	= caJSONLookupServiceUrl($this->request, $t_subject->tableName());
	
	$va_errors = array();
	if(is_array($va_action_errors = $this->request->getActionErrors('hierarchy_location'))) {
		foreach($va_action_errors as $o_error) {
			$va_errors[] = $o_error->getErrorDescription();
		}
	}
?>	
<script type="text/javascript">
	//
	// Handle browse header scrolling
	//
	jQuery(document).ready(function() {
		if (jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').width() > jQuery('#<?php print $ps_id_prefix; ?>HierarchyPanelHeader').width()) {
			//
			// The content is wider than the content viewer area so set up the scroll
			//
			
			jQuery('#<?php print $ps_id_prefix; ?>NextPrevControls').show(); // show controls
			
			var hierarchyHeaderContentWidth = jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').width();
			var hierarchyPanelHeaderWidth = jQuery('#<?php print $ps_id_prefix; ?>HierarchyPanelHeader').width();
			
			if (hierarchyHeaderContentWidth > hierarchyPanelHeaderWidth) {
				jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left', ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1) + "px"); // start at right side
			}
			
			//
			// Handle click on "specific" button
			//
			jQuery('#<?php print $ps_id_prefix; ?>NextControl').click(function() {		
				if ((parseInt(jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left'))) >  ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1)) {
					//
					// If we're not already at the right boundary then scroll right
					//
					var dl = (parseInt(jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left')) - hierarchyPanelHeaderWidth);
					if (dl < ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1)) { dl = ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1); }
					jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').stop().animate({'left': dl + "px" }, { duration: 300, easing: 'swing', queue: false, complete: function() {			
						if ((parseInt(jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left'))) <=  ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1)) {
							jQuery('#<?php print $ps_id_prefix; ?>NextControl').hide();
						}
						if ((parseInt(jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left'))) < 0) {
							jQuery('#<?php print $ps_id_prefix; ?>PrevControl').show();
						} 
					}}); 
				}
				return false;
			});
			
			//
			// Handle click on "broader" button
			// 
			jQuery('#<?php print $ps_id_prefix; ?>PrevControl').click(function() {			
				if ((parseInt(jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left'))) < 0) {
					//
					// Only scroll if we're not showing the extreme left side of the content area already.
					//
					var dl = parseInt(jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left')) + hierarchyPanelHeaderWidth;
					if (dl > 0) { dl = 0; }
					jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').stop().animate({'left': dl + "px"}, { duration: 300, easing: 'swing', queue: false, complete: function() {
						if ((parseInt(jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left'))) >= 0) {
							jQuery('#<?php print $ps_id_prefix; ?>PrevControl').hide();
						} 
						if ((parseInt(jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left'))) > ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1)) {
							jQuery('#<?php print $ps_id_prefix; ?>NextControl').show();
						}
					}}); 
				}
				return false;
			});
			jQuery('#<?php print $ps_id_prefix; ?>NextControl').hide();
		} else {
			// 
			// Everything can fit without scrolling so hide the controls
			//
			jQuery('#<?php print $ps_id_prefix; ?>NextPrevControls').hide();
		}
	});
</script>
<div class="bundleContainer">
	<div class="hierNav" >
<?php
			if(sizeof($va_errors)) {
				print "<div class='formLabel'><span class='formLabelError'>".join('; ', $va_errors)."</span></div>\n";
			}
	
			if ($pn_id > 0) {
?>
				<div class="buttonPosition">
					<a href="#" id="<?php print $ps_id_prefix; ?>browseToggle" class="form-button"><span class="form-button"><?php print _t('Show Hierarchy'); ?></span></a>
				</div>			
<?php	
			}

			print '<div id="'.$ps_id_prefix.'HierarchyPanelHeader" class="hierarchyPanelHeader"><div id="'.$ps_id_prefix.'HierarchyHeaderContent" class="hierarchyHeaderContent">';
			if (is_array($pa_ancestors) && sizeof($pa_ancestors) > 0) {
				$va_path = array();
				foreach($pa_ancestors as $vn_id => $va_item) {
					if($vn_id === '') {
						$va_path[] = "<a href='#'>"._t('New %1', $t_subject->getTypeName())."</a>";
					} else {
						$vs_name = htmlspecialchars($va_item['name'], ENT_QUOTES, 'UTF-8');
						if ($va_item[$t_subject->primaryKey()] && ($va_item[$t_subject->primaryKey()] != $pn_id)) {
							$va_path[] = '<a href="'.caEditorUrl($this->request, $t_subject->tableName(), $va_item[$t_subject->primaryKey()]).'">'.$vs_name.'</a>';
						} else {
							$va_path[] = "<a href='#' onclick='jQuery(\"#".$ps_id_prefix."HierarchyBrowserContainer\").slideDown(250); o".$ps_id_prefix."HierarchyBrowser.setUpHierarchy(".intval($va_item[$t_subject->primaryKey()])."); return false;'>".$vs_name."</a>";
						}
					}
				}
				
				print join(' ➔ ', $va_path);
			}
			print '</div></div>';
?>
			<div id="<?php print $ps_id_prefix; ?>HierarchyHeaderScrollButtons" class="hierarchyHeaderScrollButtons">
				<div id="<?php print $ps_id_prefix; ?>NextPrevControls" class="nextPrevControls"><a href="#" id="<?php print $ps_id_prefix; ?>PrevControl" class="prevControl">&larr;</a> <a href="#" id="<?php print $ps_id_prefix; ?>NextControl" class="nextControl">&rarr;</a></div>
			</div>
	</div><!-- end hiernav -->
<?php
	if ($pn_id > 0) {
?>
		<div id="<?php print $ps_id_prefix; ?>HierarchyBrowserContainer" class="editorHierarchyBrowserContainer">		
			<div  id="<?php print $ps_id_prefix; ?>HierarchyBrowserTabs">
				<ul>
					<li><a href="#<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-explore" onclick='_init<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser();'><span>Explore</span></a></li>
					<li><a href="#<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-move" onclick='_init<?php print $ps_id_prefix; ?>MoveHierarchyBrowser();'><span>Move</span></a></li>
<?php
	if ($this->request->user->canDoAction('can_create_'.$vs_priv_table)) {
?>
					<li><a href="#<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-add" onclick='_init<?php print $ps_id_prefix; ?>AddHierarchyBrowser();'><span>Add</span></a></li>
<?php
	}
?>
				</ul>

				<div id="<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-explore" class="hierarchyBrowseTab">	
					<div class="hierarchyBrowserFind">
						<?php print _t('Find'); ?>: <input type="text" id="<?php print $ps_id_prefix; ?>ExploreHierarchyBrowserSearch" name="search" value="" size="25"/>
					</div>
					<div class="hierarchyBrowserMessageContainer">
							<?php print _t('Click %1 names to explore. Click on an arrow icon to open a %1 for editing.', $t_subject->getProperty('NAME_SINGULAR')); ?>
					</div>
					<div class="clear"><!-- empty --></div>
					<div id="<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser" class="hierarchyBrowserSmall">
						<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
					</div><!-- end hierbrowser -->
				</div>
				<div id="<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-move" class="hierarchyBrowseTab">
					<div class="hierarchyBrowserFind">
						<?php print _t('Find'); ?>: <input type="text" id="<?php print $ps_id_prefix; ?>MoveHierarchyBrowserSearch" name="search" value="" size="25"/>
					</div>
					<div class="hierarchyBrowserMessageContainer">
						<?php print _t('Click on a %1 name to move this record under.', $t_subject->getProperty('NAME_SINGULAR')); ?>
						<div id='<?php print $ps_id_prefix; ?>HierarchyBrowserSelectionMessage' class='hierarchyBrowserNewLocationMessage'><!-- Message specifying move destination is dynamically inserted here by ca.hierbrowser --></div>	
					</div>
					<div class="clear"><!-- empty --></div>
					<div id="<?php print $ps_id_prefix; ?>MoveHierarchyBrowser" class="hierarchyBrowserSmall">
						<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
					</div><!-- end hierbrowser -->
				</div>
<?php
	if ($this->request->user->canDoAction('can_create_'.$vs_priv_table)) {
?>
			<div id="<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-add"  class="hierarchyBrowseTab">
				<div class="hierarchyBrowserMessageContainer">
					<?php print _t('Use the controls below to create new %1 relative to this record in the hierarchy.', $t_subject->getProperty('NAME_PLURAL'), $vs_subject_label); ?>
				</div>
				
				<div id='<?php print $ps_id_prefix; ?>HierarchyBrowseTypeMenu' style="margin-top: 15px;">
					<div style="float: left; width: 700px">
<?php
						$va_add_types = array(_t('under (child)') => 'under');
						if ($pn_parent_id > 0) { $va_add_types[_t('next to (sibling)')] = 'next_to'; }
						
						if ($vs_sel = $t_subject->getTypeListAsHTMLFormElement("{$ps_id_prefix}type_id", array('id' => "{$ps_id_prefix}typeList"), array('childrenOfCurrentTypeOnly' => false, 'includeSelf' => true, 'directChildrenOnly' => false))) {
							// for items that take types
							print "<div id='{$ps_id_prefix}HierarchyBrowseAdd'>"._t("Add a new %1 %2 <em>%3</em>", $vs_sel, caHTMLSelect('add_type', $va_add_types, array('id' => "{$ps_id_prefix}addType")), $vs_subject_label);
		
							// Note the jQuery(\"#{$ps_id_prefix}childTypeList\").val() which grabs the value of the type
							print " <a href='#' onclick='_navigateToNewForm(jQuery(\"#{$ps_id_prefix}typeList\").val(), (jQuery(\"#{$ps_id_prefix}addType\").val() == \"under\") ? ".intval($pn_id)." : ".intval($pn_parent_id).")'>".caNavIcon($this->request, __CA_NAV_BUTTON_ADD__)."</a></div>";
						} else {
							// for items with out types
							print "<div id='{$ps_id_prefix}HierarchyBrowseAdd'>"._t("Add a new %1 %2 <em>%3</em>",  $t_subject->getProperty('NAME_SINGULAR'), caHTMLSelect('add_type', $va_add_types, array('id' => "{$ps_id_prefix}addType")), $vs_subject_label);
							print " <a href='#' onclick='_navigateToNewForm(0, (jQuery(\"#{$ps_id_prefix}addType\").val() == \"under\") ? ".intval($pn_id)." : ".intval($pn_parent_id).")'>".caNavIcon($this->request, __CA_NAV_BUTTON_ADD__)."</a></div>";
						}
	?>
					</div>
				</div>
				<br class="clear"/>
				<div id="<?php print $ps_id_prefix; ?>AddHierarchyBrowser" class="hierarchyBrowserSmall">
					<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end hierbrowser -->
		</div>
<?php
	}
?>
		</div>
	</div>
	<input type='hidden' name='<?php print $ps_id_prefix; ?>_new_parent_id' id='<?php print $ps_id_prefix; ?>_new_parent_id' value=''/>

<script type="text/javascript">
	jQuery(document).ready(function() {	
		// Set up "move" hierarchy browse search
		jQuery('#<?php print $ps_id_prefix; ?>MoveHierarchyBrowserSearch').autocomplete(
			'<?php print $va_lookup_urls['search']; ?>', {minChars: 3, matchSubset: 1, matchContains: 1, delay: 800}
		);
		jQuery('#<?php print $ps_id_prefix; ?>MoveHierarchyBrowserSearch').result(function(event, data, formatted) {
			jQuery("#<?php print $ps_id_prefix; ?>HierarchyBrowserContainer").slideDown(350);
			o<?php print $ps_id_prefix; ?>MoveHierarchyBrowser.setUpHierarchy(data[1]);	// jump browser to selected item
		});
		
		jQuery("#<?php print $ps_id_prefix; ?>browseToggle").click(function() {
			_init<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser();
			jQuery("#<?php print $ps_id_prefix; ?>HierarchyBrowserContainer").slideToggle(350, function() { 
				jQuery("#<?php print $ps_id_prefix; ?>browseToggle").html((this.style.display == 'block') ? '<?php print '<span class="form-button">'._t('Close browser').'</span>';?>' : '<?php print '<span class="form-button">'._t('Show Hierarchy').'</span>';?>');
			}); 
			return false;
		});
		
		// Set up "explore" hierarchy browse search
		jQuery('#<?php print $ps_id_prefix; ?>ExploreHierarchyBrowserSearch').autocomplete(
			'<?php print $va_lookup_urls['search']; ?>', {minChars: 3, matchSubset: 1, matchContains: 1, delay: 800}
		);
		jQuery('#<?php print $ps_id_prefix; ?>ExploreHierarchyBrowserSearch').result(function(event, data, formatted) {
			jQuery("#<?php print $ps_id_prefix; ?>HierarchyBrowserContainer").slideDown(350);
			o<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser.setUpHierarchy(data[1]);	// jump browser to selected item
		});
		
		// Disable form change warnings to add type drop-downs
		jQuery('#<?php print $ps_id_prefix; ?>HierarchyBrowseAddUnder select').unbind('change');
		jQuery('#<?php print $ps_id_prefix; ?>HierarchyBrowseAddNextTo select').unbind('change');		
		
		jQuery("#<?php print $ps_id_prefix; ?>HierarchyBrowserTabs").tabs({ selected: 0 });					// Activate tabs
		jQuery('#<?php print $ps_id_prefix; ?>HierarchyBrowserContainer').hide(0);			// Hide extended options
	});
	
	if (typeof  _navigateToNewForm != 'function') {
		function _navigateToNewForm(type_id, parent_id) {
			document.location = '<?php print caEditorUrl($this->request, $t_subject->tableName(), 0); ?>/type_id/' + type_id + '/parent_id/' + parent_id;
		}
	}
	
	// Set up "explore" hierarchy browser
	var o<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser = null;
	
	function _init<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser() {
		if (!o<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser) {
			o<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser = caUI.initHierBrowser('<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser', {
				levelDataUrl: '<?php print $va_lookup_urls['levelList']; ?>',
				initDataUrl: '<?php print $va_lookup_urls['ancestorList']; ?>',
				
				dontAllowEditForFirstLevel: <?php print (in_array($t_subject->tableName(), array('ca_places', 'ca_storage_locations', 'ca_list_items')) ? 'true' : 'false'); ?>,
				
				readOnly: false,
				
				editUrl: '<?php print caEditorUrl($this->request, $t_subject->tableName()); ?>',
				editButtonIcon: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/buttons/arrow_grey_right.gif" border="0" title="Edit">',
				
				initItemID: '<?php print $pn_id; ?>',
				indicatorUrl: '<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif',
				displayCurrentSelectionOnLoad: false
			});
		}
	}
	
	// Set up "move" hierarchy browser
	var o<?php print $ps_id_prefix; ?>MoveHierarchyBrowser = null;
	
	function _init<?php print $ps_id_prefix; ?>MoveHierarchyBrowser() {
		if (!o<?php print $ps_id_prefix; ?>MoveHierarchyBrowser) {
			o<?php print $ps_id_prefix; ?>MoveHierarchyBrowser = caUI.initHierBrowser('<?php print $ps_id_prefix; ?>MoveHierarchyBrowser', {
				levelDataUrl: '<?php print $va_lookup_urls['levelList']; ?>',
				initDataUrl: '<?php print $va_lookup_urls['ancestorList']; ?>',
				
				readOnly: false,
				
				initItemID: '<?php print $pn_id; ?>',
				indicatorUrl: '<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif',
					
				currentSelectionIDID: '<?php print $ps_id_prefix; ?>_new_parent_id',
				currentSelectionDisplayID: '<?php print $ps_id_prefix; ?>HierarchyBrowserSelectionMessage',
				currentSelectionDisplayFormat: '<?php print _t('Will be moved under <em>%1</em> after next save.'); ?>',
				
				onSelection: function(id, parent_id, name, formattedDisplay) {
					// Update "move" status message
					jQuery("#<?php print $ps_id_prefix; ?>HierarchyBrowserSelectionMessage").html(formattedDisplay);
					if (caUI.utils.showUnsavedChangesWarning) { caUI.utils.showUnsavedChangesWarning(true); }
				},
				
				displayCurrentSelectionOnLoad: false
			});
		}
	}
	
	// Set up "add" hierarchy browser
	var o<?php print $ps_id_prefix; ?>AddHierarchyBrowser = null;
	
	function _init<?php print $ps_id_prefix; ?>AddHierarchyBrowser() {
		if (!o<?php print $ps_id_prefix; ?>AddHierarchyBrowser) {
			o<?php print $ps_id_prefix; ?>AddHierarchyBrowser = caUI.initHierBrowser('<?php print $ps_id_prefix; ?>AddHierarchyBrowser', {
				levelDataUrl: '<?php print $va_lookup_urls['levelList']; ?>',
				initDataUrl: '<?php print $va_lookup_urls['ancestorList']; ?>',
				
				readOnly: true,
				
				initItemID: '<?php print $pn_id; ?>',
				indicatorUrl: '<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif',
				displayCurrentSelectionOnLoad: true,
				
				className: 'explore',
				classNameSelected: 'exploreSelected'
			});
		}
	}
</script>
<?php
}
?>
</div><!-- bundleContainer -->