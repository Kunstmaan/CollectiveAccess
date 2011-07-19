<?php
/** ---------------------------------------------------------------------
 * app/helpers/displayHelpers.php : miscellaneous functions
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
 * @package CollectiveAccess
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

 /**
   *
   */
   	
require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	
	# ------------------------------------------------------------------------------------------------
	/**
	 * @param $ps_item_locale -
	 * @param $pa_preferred_locales -
	 * @return Array - returns an associative array defining which locales should be used when displaying values; suitable for use with caExtractValuesByLocale()
	 */
	function caGetUserLocaleRules($ps_item_locale=null, $pa_preferred_locales=null) {
		global $g_ui_locale, $g_ui_locale_id;
		
		$o_config = Configuration::load();
		$va_default_locales = $o_config->getList('locale_defaults');
		
		//$vs_label_mode = $po_request->user->getPreference('cataloguing_display_label_mode');
		
		$va_preferred_locales = array();
		if ($ps_item_locale) {
			// if item locale is passed as locale_id we need to convert it to a code
			if (is_numeric($ps_item_locale)) {
				$t_locales = new ca_locales();
				if ($t_locales->load($ps_item_locale)) {
					$ps_item_locale = $t_locales->getCode();
				} else {
					$ps_item_locale = null;
				}
			}
			if ($ps_item_locale) {
				$va_preferred_locales[$ps_item_locale] = true;
			}
		}
		
		if (is_array($pa_preferred_locales)) {
			foreach($pa_preferred_locales as $vs_preferred_locale) {
				$va_preferred_locales[$vs_preferred_locale] = true;
			}
		}
		
		$va_fallback_locales = array();
		if (is_array($va_default_locales)) {
			foreach($va_default_locales as $vs_fallback_locale) {
				if (!isset($va_preferred_locales[$vs_fallback_locale]) || !$va_preferred_locales[$vs_fallback_locale]) {
					$va_fallback_locales[$vs_fallback_locale] = true;
				}
			}
		}
		if ($g_ui_locale) {
			if (!isset($va_preferred_locales[$g_ui_locale]) || !$va_preferred_locales[$g_ui_locale]) {
				$va_preferred_locales[$g_ui_locale] = true;
			}
		}
		$va_rules = array(
			'preferred' => $va_preferred_locales,	/* all of these locales will display if available */
			'fallback' => $va_fallback_locales		/* the first of these that is available will display, but only if none of the preferred locales are available */
		);
		
		return $va_rules;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 * @param $pa_locale_rules - Associative array defining which locales to extract, and how to fall back to alternative locales should your preferred locales not exist in $pa_values
	 * @param $pa_values - Associative array keyed by unique item_id and then locale code (eg. en_US) or locale_id; the values can be anything - string, numbers, objects, arrays, etc.
	 * @param $pa_options [optional] - Associative array of options; available options are:
	 *									'returnList' = return an indexed array of found values rather than an associative array keys on unique item_id [default is false]
	 *									'debug' = print debugging information [default is false]
	 * @return Array - an array of found values keyed by unique item_id; or an indexed list of found values if option 'returnList' is passed in $pa_options
	 */
	function caExtractValuesByLocale($pa_locale_rules, $pa_values, $pa_options=null) {
		if (!is_array($pa_values)) { return array(); }
		$t_locales = new ca_locales();
		$va_locales = $t_locales->getLocaleList();
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		if (!isset($pa_options['returnList'])) { $pa_options['returnList'] = false; }
		
		if (isset($pa_options['debug']) && $pa_options['debug']) {
			print_r($pa_values);
		}
		if (!is_array($pa_values)) { return array(); }
		$va_values = array();
		foreach($pa_values as $vm_id => $va_value_list_by_locale) {
			foreach($va_value_list_by_locale as $pm_locale => $vm_value) {
				// convert locale_id to locale string
				if (is_numeric($pm_locale)) {
					if (!$va_locales[$pm_locale]) { continue; }	// invalid locale_id?
					$vs_locale = $va_locales[$pm_locale]['language'].'_'.$va_locales[$pm_locale]['country'];
				} else {
					$vs_locale = $pm_locale;
				}
				
				// try to find values for preferred locale
				if (isset($pa_locale_rules['preferred'][$vs_locale]) && $pa_locale_rules['preferred'][$vs_locale]) {
					$va_values[$vm_id] = $vm_value;
					break;
				}
				
				// try fallback locales
				if (isset($pa_locale_rules['fallback'][$vs_locale]) && $pa_locale_rules['fallback'][$vs_locale]) {
					$va_values[$vm_id] = $vm_value;
				}
			}
			
			if (!isset($va_values[$vm_id])) {
				// desperation mode: pick an available locale
				$va_values[$vm_id] = array_shift($va_value_list_by_locale);
			}
		}
		return ($pa_options['returnList']) ? array_values($va_values) : $va_values;
	}
	# ------------------------------------------------------------------------------------------------
	function caExtractValuesByUserLocale($pa_values, $ps_item_locale=null, $pa_preferred_locales=null, $pa_options=null) {
		$va_values = caExtractValuesByLocale(caGetUserLocaleRules($ps_item_locale, $pa_preferred_locales), $pa_values, $pa_options);
		if (isset($pa_options['debug']) && $pa_options['debug']) {
			//print_r($va_values);
		}
		return $va_values;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Takes the output of BaseModel->getHierarchyAncestors() and tries to extract the appropriate values for the current user's locale.
	 * This is designed for the common case where you want to get a list of ancestors with their labels in the appropriate language,
	 * so you call getHierarchyAncestors() with the 'getHierarchyAncestors' option set to the label table. What you get back is a simple
	 * list where each item is a node with the table fields + the label fields; if a node has labels in several languages then you'll get back
	 * dupes - one for each language. 
	 *
	 * This function takes that list with dupes and returns an array key'ed upon the primary key containing a single entry for each node
	 * and the label set to the appropriate language - no dupes!
	 *
	 * @param array - the list of ancestor hierarchy nodes as returned by BaseModel->getHierarchyAncestors()
	 * @param string - the field name of the primary key of the hierarchy (eg. 'place_id' for ca_places)
	 * @return array - the list of ancestors with labels in the appropriate language; array is indexed by the primary key
	 */
	function caExtractValuesByUserLocaleFromHierarchyAncestorList($pa_list, $ps_primary_key_name, $ps_label_display_field, $ps_use_if_no_label_field, $ps_default_text='???') {
		if (!is_array($pa_list)) { return array(); }
		$va_values = array();
		foreach($pa_list as $vn_i => $va_item) {
			if (!isset($va_item[$ps_label_display_field]) || !$va_item[$ps_label_display_field]) {
				if (!isset($va_item[$ps_use_if_no_label_field]) || !($va_item[$ps_label_display_field] = $va_item[$ps_use_if_no_label_field])) {
					$va_item[$ps_label_display_field] = $ps_default_text;
				}
			}
			$va_values[$va_item['NODE'][$ps_primary_key_name]][$va_item['NODE']['locale_id']] = $va_item;
		}
		
		return caExtractValuesByUserLocale($va_values);
	}
	# ------------------------------------------------------------------------------------------------
	function caExtractValuesByUserLocaleFromHierarchyChildList($pa_list, $ps_primary_key_name, $ps_label_display_field, $ps_use_if_no_label_field, $ps_default_text='???') {
		if (!is_array($pa_list)) { return array(); }
		$va_values = array();
		foreach($pa_list as $vn_i => $va_item) {
			if (!$va_item[$ps_label_display_field]) {
				if (!($va_item[$ps_label_display_field] = $va_item[$ps_use_if_no_label_field])) {
					$va_item[$ps_label_display_field] = $ps_default_text;
				}
			}
			$va_values[$va_item[$ps_primary_key_name]][$va_item['locale_id']] = $va_item;
		}
		
		return caExtractValuesByUserLocale($va_values);
	}
	# ------------------------------------------------------------------------------------------------
	function caFormatFieldErrorsAsHTML($pa_errors, $ps_css_class) {
		
		$vs_output = "<ul class='{$ps_css_class}'>\n";
		foreach($pa_errors as $o_e) {
			$vs_output .= '<li class="'.$ps_css_class.'"><img src=""/> ';
			$vs_output .= $o_e->getErrorMessage()."</li>";
		}
		$vs_output .= "</ul>\n";
		
		
		return $vs_output;
	}
	# ------------------------------------------------------------------------------------------------
	function caFormControlBox($ps_left_content, $ps_middle_content, $ps_right_content, $ps_second_row_content='') {
		$vs_output = '<div class="control-box rounded">
		<div class="control-box-left-content">'.$ps_left_content;
			
		$vs_output .= '</div>
		<div class="control-box-right-content">'.$ps_right_content;

		$vs_output .= '</div><div class="control-box-middle-content">'.$ps_middle_content.'</div>';
		
		if ($ps_second_row_content) {
			$vs_output .= '<div class="clear"><!--empty--></div>'.$ps_second_row_content;
		}
		
	$vs_output .= '</div>
	<div class="clear"><!--empty--></div>'."\n";
	
		return $vs_output;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	function caDeleteWarningBox($po_request, $ps_item_name, $ps_module_path, $ps_controller, $ps_cancel_action, $pa_parameters) {
		if ($vs_warning = isset($pa_parameters['warning']) ? $pa_parameters['warning'] : null) {
			$vs_warning = '<br/>'.$vs_warning;
		}
		$vs_output = "<div class='delete-control-box'>".caFormControlBox(
			'<div class="delete_warning_box">'._t('Really delete').' "'.$ps_item_name.'"?</div>',
			$vs_warning,
			caNavButton($po_request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), $ps_module_path, $ps_controller, 'Delete', array_merge($pa_parameters, array('confirm' => 1))).' '.
			caNavButton($po_request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), $ps_module_path, $ps_controller, $ps_cancel_action, $pa_parameters)
		)."</div>\n";
		
		return $vs_output;
	}
	
	# ------------------------------------------------------------------------------------------------
	/**
	 * Returns HTML <img> tag displaying spinning "I'm doing something" icon
	 */
	function caBusyIndicatorIcon($po_request, $pa_attributes=null) {
		if (!is_array($pa_attributes)) { $pa_attributes = array(); }
		
		if (!isset($pa_attributes['alt'])) {
			$pa_attributes['alt'] = $vs_img_name;
		}
		$vs_attr = _caHTMLMakeAttributeString($pa_attributes);
		$vs_button = "<img src='".$po_request->getThemeUrlPath()."/graphics/icons/indicator.gif' border='0' {$vs_attr}/> ";
	
		return $vs_button;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Formats extracted media metadata for display to user.
	 *
	 * @param $pa_metadata array - array key'ed by metadata system (eg. EXIF, DPX, IPTC) where values are arrays containing key/value metadata pairs
	 *
	 * @return string - formated metadata for display to user
	 */
	function caFormatMediaMetadata($pa_metadata) {
		$vs_buf = "<table>\n";
			
		$vn_metadata_rows = 0;
		if (is_array($pa_metadata) && sizeof($pa_metadata)) {
			foreach($pa_metadata as $vs_metadata_type => $va_metadata_data) {
				if (isset($va_metadata_data) && is_array($va_metadata_data)) {
					$vs_buf .= "<tr><th>".preg_replace('!^METADATA_!', '', $vs_metadata_type)."</th><th colspan='2'><!-- empty --></th></tr>\n";
					foreach($va_metadata_data as $vs_key => $vs_value) {
						$vs_buf .=  "<tr valign='top'><td><!-- empty --></td><td>{$vs_key}</td><td>{$vs_value}</td></tr>\n";
						$vn_metadata_rows++;
					}
				}
			}
		}
		
		if (!$vn_metadata_rows) {
			$vs_buf .=  "<tr valign='top'><td colspan='3'>"._t('No embedded metadata was extracted from the media')."</td></tr>\n";
		}
		$vs_buf .= "</table>\n";
		
		return $vs_buf;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Generates next/previous/back-to-results navigation HTML for bundleable editors
	 *
	 * @param $po_request RequestHTTP The current request
	 * @param $po_instance BaseModel An instance containing the currently edited record
	 * @param $po_result_context ResultContext The current result content
	 * @param $pa_options array An optional array of options. Supported options are:
	 *		backText = a string to use as the "back" button text; default is "Results"
	 *
	 * @return string HTML implementing the navigation element
	 */
	function caEditorFindResultNavigation($po_request, $po_instance, $po_result_context, $pa_options=null) {
		$vn_item_id 			= $po_instance->getPrimaryKey();
		$vs_pk 					= $po_instance->primaryKey();
		$vs_table_name			= $po_instance->tableName();
		if (($vs_priv_table_name = $vs_table_name) == 'ca_list_items') {
			$vs_priv_table_name = 'ca_lists';
		}
		
		$va_found_ids 			= $po_result_context->getResultList();
		$vn_current_pos			= $po_result_context->getIndexInResultList($vn_item_id);
		$vn_prev_id 			= $po_result_context->getPreviousID($vn_item_id);
		$vn_next_id 			= $po_result_context->getNextID($vn_item_id);
		
		if (isset($pa_options['backText']) && $pa_options['backText']) {
			$vs_back_text = $pa_options['backText'];
		} else {
			$vs_back_text = _t('Results');
		}
		
		$vs_buf = '';
		if (is_array($va_found_ids) && sizeof($va_found_ids)) {
			if ($vn_prev_id > 0) {
				if($po_request->user->canDoAction("can_edit_".$vs_priv_table_name)){
					$vs_buf .= caNavLink($po_request, '&larr;', '', $po_request->getModulePath(), $po_request->getController(), 'Edit'.'/'.$po_request->getActionExtra(), array($vs_pk => $vn_prev_id)).'&nbsp;';
				}else{
					$vs_buf .= caNavLink($po_request, '&larr;', '', $po_request->getModulePath(), $po_request->getController(), 'Summary', array($vs_pk => $vn_prev_id)).'&nbsp;';
				}
			} else {
				$vs_buf .=  '<span class="disabled">&larr;&nbsp;</span>';
			}
				
			$vs_buf .= ResultContext::getResultsLinkForLastFind($po_request, $vs_table_name,  $vs_back_text, ''). " (".($vn_current_pos)."/".sizeof($va_found_ids).")";
			
			if (!$vn_next_id && sizeof($va_found_ids)) { $vn_next_id = $va_found_ids[0]; }
			if ($vn_next_id > 0) {
				if($po_request->user->canDoAction("can_edit_".$vs_priv_table_name)){
					$vs_buf .= '&nbsp;'.caNavLink($po_request, '&rarr;', '', $po_request->getModulePath(), $po_request->getController(), 'Edit'.'/'.$po_request->getActionExtra(), array($vs_pk => $vn_next_id));
				}else{
					$vs_buf .= '&nbsp;'.caNavLink($po_request, '&rarr;', '', $po_request->getModulePath(), $po_request->getController(), 'Summary', array($vs_pk => $vn_next_id));
				}
			} else {
				$vs_buf .=  '<span class="disabled">&nbsp;&rarr;</span>';
			}
		} else {
			$vs_buf .= ResultContext::getResultsLinkForLastFind($po_request, $vs_table_name,  $vs_back_text, '');
		}
		
		return $vs_buf;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Generates standard-format inspector panels for editors
	 *
	 * @param View $po_view Inspector view object
	 * @param array $pa_options Optional array of options. Supported options are:
	 *		backText = a string to use as the "back" button text; default is "Results"
	 *
	 * @return string HTML implementing the inspector
	 */
	function caEditorInspector($po_view, $pa_options=null) {
		require_once(__CA_MODELS_DIR__.'/ca_sets.php');
		
		$t_item 				= $po_view->getVar('t_item');
		$vs_table_name = $t_item->tableName();
		if (($vs_priv_table_name = $vs_table_name) == 'ca_list_items') {
			$vs_priv_table_name = 'ca_lists';
		}
		
		$vn_item_id 			= $t_item->getPrimaryKey();
		$o_result_context		= $po_view->getVar('result_context');
		$t_ui 					= $po_view->getVar('t_ui');
		$t_type 				= method_exists($t_item, "getTypeInstance") ? $t_item->getTypeInstance() : null;
		$vs_type_name			= method_exists($t_item, "getTypeName") ? $t_item->getTypeName() : '';
		if (!$vs_type_name) { $vs_type_name = $t_item->getProperty('NAME_SINGULAR'); }
		
		$va_reps 				= $po_view->getVar('representations');
		
		
		$o_dm = Datamodel::load();
		
		if ($t_item->isHierarchical()) {
			$va_ancestors 		= $po_view->getVar('ancestors');
			$vn_parent_id		= $t_item->get($t_item->getProperty('HIERARCHY_PARENT_ID_FLD'));
		} else {
			$va_ancestors = array();
			$vn_parent_id = null;
		}

		// action extra to preserve currently open screen across next/previous links
		$vs_screen_extra 	= ($po_view->getVar('screen')) ? '/'.$po_view->getVar('screen') : '';
		
		$vs_buf = '<h3 class="nextPrevious">'.caEditorFindResultNavigation($po_view->request, $t_item, $o_result_context, $pa_options)."</h3>\n";

		$vs_color = null;
		if ($t_type) { $vs_color = trim($t_type->get('color')); } 
		if (!$vs_color && $t_ui) { $vs_color = trim($t_ui->get('color')); }
		if (!$vs_color) { $vs_color = "444444"; }
		
		$vs_buf .= "<h4><div id='colorbox' style='border: 6px solid #{$vs_color}; padding-bottom:15px;'>\n";
		
		$vs_icon = null;
		if ($t_type) { $vs_icon = $t_type->getMediaTag('icon', 'icon'); }
		if (!$vs_icon && $t_ui) { $vs_icon = $t_ui->getMediaTag('icon', 'icon'); }
		
		if ($vs_icon){
			$vs_buf .= "<div id='inspectoricon' style='border-right: 6px solid #{$vs_color}; border-bottom: 6px solid #{$vs_color}; -moz-border-radius-bottomright: 8px; -webkit-border-bottom-right-radius: 8px;'>\n{$vs_icon}</div>\n";
		}
		
		if (($po_view->request->getAction() === 'Delete') && ($po_view->request->getParameter('confirm', pInteger))) { 
			$vs_buf .= "<strong>"._t("Deleted %1", $vs_type_name)."</strong>\n";
			$vs_buf .= "</div></h4>\n";
		} else {	
			if ($vn_item_id) {
				if($po_view->request->user->canDoAction("can_edit_".$vs_priv_table_name)){
					$vs_buf .= "<strong>"._t("Editing %1", $vs_type_name).": </strong>\n";
				}else{
					$vs_buf .= "<strong>"._t("Viewing %1", $vs_type_name).": </strong>\n";
				}
				if (method_exists($t_item, 'getLabelForDisplay')) {
					$vn_parent_index = (sizeof($va_ancestors) - 1);
					if ($vn_parent_id && (($vs_table_name != 'ca_places') || ($vn_parent_index > 0))) {
						$va_parent = $va_ancestors[$vn_parent_index];
						$vs_disp_fld = $t_item->getLabelDisplayField();
						
						if ($va_parent['NODE'][$vs_disp_fld] && ($vs_editor_link = caEditorLink($po_view->request, $va_parent['NODE'][$vs_disp_fld], '', $vs_table_name, $va_parent['NODE'][$t_item->primaryKey()]))) {
							$vs_label = $vs_editor_link.' &gt; '.htmlentities($t_item->getLabelForDisplay(), ENT_COMPAT, 'utf-8', false);
						} else {
							$vs_label = ($va_parent['NODE'][$vs_disp_fld] ? htmlentities($va_parent['NODE'][$vs_disp_fld], ENT_COMPAT, 'utf-8', false).' &gt; ' : '').htmlentities($t_item->getLabelForDisplay(), ENT_COMPAT, 'utf-8', false);
						}
					} else {
						$vs_label = htmlentities($t_item->getLabelForDisplay(), ENT_COMPAT, 'utf-8', false);
						if (($vs_table_name === 'ca_editor_uis') && (in_array($po_view->request->getAction(), array('EditScreen', 'DeleteScreen', 'SaveScreen')))) {
							$t_screen = new ca_editor_ui_screens($po_view->request->getParameter('screen_id', pInteger));
							if (!($vs_screen_name = $t_screen->getLabelForDisplay())) {
								$vs_screen_name = _t('new screen');
							}
							$vs_label .= " &gt; ".$vs_screen_name;
						} 
						
					}
				} else {
					$vs_label = $t_item->get('name');
				}
				if (!$vs_label) { $vs_label = '['._t('BLANK').']'; }
			
				$vs_idno = $t_item->get($t_item->getProperty('ID_NUMBERING_ID_FIELD'));
				# --- watch this link
				$vs_watch = "";
				if (in_array($vs_table_name, array('ca_objects', 'ca_object_lots', 'ca_entities', 'ca_places', 'ca_occurrences', 'ca_collections', 'ca_storage_locations'))) {
					require_once(__CA_MODELS_DIR__.'/ca_watch_list.php');
					$t_watch_list = new ca_watch_list();
					$vs_watch = "<div style='float:right; width:25px; text-align:right; margin:0px; padding:0px;'><a href='#' title='"._t('Add/remove item to/from watch list.')."' onclick='caToggleItemWatch(); return false;' id='caWatchItemButton'>".caNavIcon($po_view->request, $t_watch_list->isItemWatched($vn_item_id, $t_item->tableNum(), $po_view->request->user->get("user_id")) ? __CA_NAV_BUTTON_UNWATCH__ : __CA_NAV_BUTTON_WATCH__)."</a></div>";
					
					$vs_buf .= "\n<script type='text/javascript'>
		function caToggleItemWatch() {
			var url = '".caNavUrl($po_view->request, $po_view->request->getModulePath(), $po_view->request->getController(), 'toggleWatch', array($t_item->primaryKey() => $vn_item_id))."';
			
			jQuery.getJSON(url, {}, function(data, status) {
				if (data['status'] == 'ok') {
					jQuery('#caWatchItemButton').html((data['state'] == 'watched') ? '".addslashes(caNavIcon($po_view->request, __CA_NAV_BUTTON_UNWATCH__))."' : '".addslashes(caNavIcon($po_view->request, __CA_NAV_BUTTON_WATCH__))."');
				} else {
					console.log('Error toggling watch status for item: ' + data['errors']);
				}
			});
		}
		</script>\n";
				}		
				
				$vs_buf .= "<div style='width:190px; overflow:hidden;'>{$vs_watch}{$vs_label}"."<a title='$vs_idno'>".($vs_idno ? " ({$vs_idno})" : '')."</a></div>\n";
			} else {
				$vs_parent_name = '';
				if ($vn_parent_id = $po_view->request->getParameter('parent_id', pInteger)) {
					$t_parent = clone $t_item;
					$t_parent->load($vn_parent_id);
					$vs_parent_name = $t_parent->getLabelForDisplay();
				}
				$vs_buf .= "<strong>"._t("Creating new %1", $vs_type_name).": <div>".($vs_parent_name ?  _t("%1 &gt; New %2", $vs_parent_name, $vs_type_name) : _t("New %1", $vs_type_name))."</div></strong>\n";
				$vs_buf .= "<br/>\n";
			}
			
		// -------------------------------------------------------------------------------------
		//
		// Item-specific information
		//
			//
			// Output lot info for ca_objects
			//
			$vb_is_currently_part_of_lot = true;
			if (!($vn_lot_id = $t_item->get('lot_id'))) {
				$vn_lot_id = $po_view->request->getParameter('lot_id', pInteger);
				$vb_is_currently_part_of_lot = false;
			}
			if (($vs_table_name === 'ca_objects') && ($vn_lot_id)) {
				require_once(__CA_MODELS_DIR__.'/ca_object_lots.php');
				
				$t_lot = new ca_object_lots($vn_lot_id);
				if(!($vs_lot_displayname = $t_lot->get('idno_stub'))) {
					$vs_lot_displayname = "Lot {$vn_lot_id}";	
				}
				if ($vs_lot_displayname) {
					$vs_buf .= "<strong>".($vb_is_currently_part_of_lot ? _t('Part of lot') : _t('Will be part of lot'))."</strong>: " . caNavLink($po_view->request, $vs_lot_displayname, '', 'editor/object_lots', 'ObjectLotEditor', 'Edit', array('lot_id' => $vn_lot_id));
				}
			}
			
			//
			// Output lot info for ca_object_lots
			//
			if (($vs_table_name === 'ca_object_lots') && $t_item->getPrimaryKey()) {
				$vs_buf .= "<br/><strong>".((($vn_num_objects = $t_item->numObjects()) == 1) ? _t('Lot contains %1 object', $vn_num_objects) : _t('Lot contains %1 objects', $vn_num_objects))."</strong>\n";
			
				if (((bool)$po_view->request->config->get('allow_automated_renumbering_of_objects_in_a_lot')) && ($va_nonconforming_objects = $t_item->getObjectsWithNonConformingIdnos())) {
				
					$vs_buf .= '<br/><br/><em>'. ((($vn_c = sizeof($va_nonconforming_objects)) == 1) ? _t('There is %1 object with non-conforming numbering', $vn_c) : _t('There are %1 objects with non-conforming numbering', $vn_c))."</em>\n";
					
					$vs_buf .= "<a href='#' onclick='jQuery(\"#inspectorNonConformingNumberList\").toggle(250); return false;'>".caNavIcon($po_view->request, __CA_NAV_BUTTON_ADD__);
					
					$vs_buf .= "<div id='inspectorNonConformingNumberList' class='inspectorNonConformingNumberList'><div class='inspectorNonConformingNumberListScroll'><ol>\n";
					foreach($va_nonconforming_objects as $vn_object_id => $va_object_info) {
						$vs_buf .= '<li>'.caEditorLink($po_view->request, $va_object_info['idno'], '', 'ca_objects', $vn_object_id)."</li>\n";
					}
					$vs_buf .= "</ol></div>";
					$vs_buf .= caNavLink($po_view->request, _t('Re-number objects').' &rsaquo;', 'button', $po_view->request->getModulePath(), $po_view->request->getController(), 'renumberObjects', array('lot_id' => $t_item->getPrimaryKey()));
					$vs_buf .= "</div>\n";
				}
			
				require_once(__CA_MODELS_DIR__.'/ca_objects.php');
				$t_object = new ca_objects();
				
				$vs_buf .= "<div class='inspectorLotObjectTypeControls'><form action='#' id='caAddObjectToLotForm'>";
				$vs_buf .= _t('Add new %1 to lot', $t_object->getTypeListAsHTMLFormElement('type_id', array('id' => 'caAddObjectToLotForm_type_id')));
				$vs_buf .= " <a href='#' onclick='caAddObjectToLotForm()'>".caNavIcon($po_view->request, __CA_NAV_BUTTON_ADD__).'</a>';
				$vs_buf .= "</form></div>\n";
				
				$vs_buf .= "<script type='text/javascript'>
	function caAddObjectToLotForm() { 
		window.location='".caEditorUrl($po_view->request, 'ca_objects', 0, false, array('lot_id' => $t_item->getPrimaryKey(), 'type_id' => ''))."' + jQuery('#caAddObjectToLotForm_type_id').val();
	}
	jQuery(document).ready(function() {
		jQuery('#objectLotsNonConformingNumberList').hide();
	});
</script>\n";
				
			}
			
			//
			// Output related objects for ca_object_representations
			//
			if ($vs_table_name === 'ca_object_representations') {
				if (sizeof($va_objects = $t_item->getRelatedItems('ca_objects'))) {
					$vs_buf .= "<div><strong>"._t("Related objects")."</strong>: <br/>\n";
					
					foreach($va_objects as $vn_rel_id => $va_rel_info) {
						if ($vs_label = array_shift($va_rel_info['labels'])) {
							$vs_buf .= caNavLink($po_view->request, '&larr; '.$vs_label.' ('.$va_rel_info['idno'].')', '', 'editor/objects', 'ObjectEditor', 'Edit/'.$po_view->getVar('object_editor_screen'), array('object_id' => $va_rel_info['object_id'])).'<br/>';
						}
					}
					$vs_buf .= "</div>\n";
				}
			}
			
			//
			// Output related object reprsentation for ca_representation_annotation
			//
			if ($vs_table_name === 'ca_representation_annotations') {
				if ($vn_representation_id = $t_item->get('representation_id')) {
					$vs_buf .= "<div><strong>"._t("Applied to representation")."</strong>: <br/>\n";
					$t_rep = new ca_object_representations($vn_representation_id);
					$vs_buf .= caNavLink($po_view->request, '&larr; '.$t_rep->getLabelForDisplay(), '', 'editor/object_representations', 'ObjectRepresentationEditor', 'Edit/'.$po_view->getVar('representation_editor_screen'), array('representation_id' => $vn_representation_id)).'<br/>';
					
					$vs_buf .= "</div>\n";
				}
			}
			
			//
			// Output extra useful info for sets
			//
			if ($vs_table_name === 'ca_sets') {
				if ($t_item->getPrimaryKey()) {
					$vs_buf .= "<div><strong>"._t("Number of items")."</strong>: ".$t_item->getItemCount(array('user_id' => $po_view->request->getUserID()))."<br/>\n";
					
					$vn_set_table_num = $t_item->get('table_num');
					$vs_buf .= "<strong>"._t("Type of content")."</strong>: ".caGetTableDisplayName($vn_set_table_num)."<br/>\n";
					
					$vs_buf .= "</div>\n";
				} else {
					if ($vn_set_table_num = $po_view->request->getParameter('table_num', pInteger)) {
						$vs_buf .= "<div><strong>"._t("Type of content")."</strong>: ".caGetTableDisplayName($vn_set_table_num)."<br/>\n";
					
						$vs_buf .= "</div>\n";
					}
				}
				$t_user = new ca_users($t_item->get('user_id'));
				if ($t_user->getPrimaryKey()) {
					$vs_buf .= "<div><strong>"._t('Owner')."</strong>: ".$t_user->get('fname').' '.$t_user->get('lname')."</div>\n";
				}
			}
			
			//
			// Output extra useful info for set items
			//
			if ($vs_table_name === 'ca_set_items') {
				JavascriptLoadManager::register("panel");
				$t_set = new ca_sets();
				if ($t_set->load($vn_set_id = $t_item->get('set_id'))) {
					$vs_buf .= "<div><strong>"._t("Part of set")."</strong>: ".caEditorLink($po_view->request, $t_set->getLabelForDisplay(), '', 'ca_sets', $vn_set_id)."<br/>\n";
					
					$t_content_instance = $t_item->getAppDatamodel()->getInstanceByTableNum($vn_item_table_num = $t_item->get('table_num'));
					if ($t_content_instance->load($vn_row_id = $t_item->get('row_id'))) {
						$vs_label = $t_content_instance->getLabelForDisplay();
						if ($vs_id_fld = $t_content_instance->getProperty('ID_NUMBERING_ID_FIELD')) {
							$vs_label .= " (".$t_content_instance->get($vs_id_fld).")";
						}	
						$vs_buf .= "<strong>"._t("Is %1", caGetTableDisplayName($vn_item_table_num, false)."</strong>: ".caEditorLink($po_view->request, $vs_label, '', $vn_item_table_num, $vn_row_id))."<br/>\n";
					}
					
					$vs_buf .= "</div>\n";
				}
			}
			
			//
			// Output extra useful info for lists
			// 
			if (($vs_table_name === 'ca_lists') && $t_item->getPrimaryKey()) {
				$vs_buf .= "<strong>"._t("Number of items")."</strong>: ".$t_item->numItemsInList()."<br/>\n";
			}
			
			//
			// Output containing list for list items
			// 
			if ($vs_table_name === 'ca_list_items') {
				if ($t_list = $po_view->getVar('t_list')) {
					$vn_list_id = $t_list->getPrimaryKey();
					$vs_buf .= "<strong>"._t("Part of")."</strong>: ".caEditorLink($po_view->request, $t_list->getLabelForDisplay(), '', 'ca_lists', $vn_list_id) ."<br/>\n";
					if ($t_item->get('is_default')) {
						$vs_buf .= "<strong>"._t("Is default for list")."</strong><br/>\n";
					}
				}
			}
	
			//
			// Output containing relationship type name for relationship types
			// 
			if ($vs_table_name === 'ca_relationship_types') {
				if (!($t_rel_instance = $t_item->getAppDatamodel()->getInstanceByTableNum($t_item->get('table_num'), true))) {
					if ($vn_parent_id = $po_view->request->getParameter('parent_id', pInteger)) {
						$t_rel_type = new ca_relationship_types($vn_parent_id);
						$t_rel_instance = $t_item->getAppDatamodel()->getInstanceByTableNum($t_rel_type->get('table_num'), true);
					}
				}
				
				if ($t_rel_instance) {
					$vs_buf .= "<div><strong>"._t("Is a")."</strong>: ".$t_rel_instance->getProperty('NAME_SINGULAR')."<br/></div>\n";
				}
			}
			
			//
			// Output extra useful info for metadata elements
			// 
			if (($vs_table_name === 'ca_metadata_elements') && $t_item->getPrimaryKey()) {
				$vs_buf .= "<div><strong>"._t("Element code")."</strong>: ".$t_item->get('element_code')."<br/></div>\n";
				
				if (sizeof($va_uis = $t_item->getUIs()) > 0) {
					$vs_buf .= "<div><strong>"._t("Referenced by user interfaces")."</strong>:<br/>\n";
					foreach($va_uis as $vn_ui_id => $va_ui_info) {
						$vs_buf .= caNavLink($po_view->request, $va_ui_info['name'], '', 'administrate/setup', 'interfaces', 'EditScreen', array('ui_id' => $vn_ui_id, 'screen_id' => $va_ui_info['screen_id']));
						$vs_buf .= " (".$o_dm->getTableProperty($va_ui_info['editor_type'], 'NAME_PLURAL').")<br/>\n";
					}
					$vs_buf .= "</div>\n";
				}
			}
			
			//
			// Output related objects for ca_editor_uis and ca_editor_ui_screens
			//
			if ($vs_table_name === 'ca_editor_uis') {
				$vs_buf .= "<div><strong>"._t("Number of screens")."</strong>: ".$t_item->getScreenCount()."\n";
					
				$vs_buf .= "</div>\n";
			}
			
			//
			// Output extra useful info for bundle displays
			//
			if ($vs_table_name === 'ca_bundle_displays') {
				if ($t_item->getPrimaryKey()) {
					$vs_buf .= "<div><strong>"._t("Number of placements")."</strong>: ".$t_item->getPlacementCount(array('user_id' => $po_view->request->getUserID()))."<br/>\n";
					
					$vn_content_table_num = $t_item->get('table_num');
					$vs_buf .= "<strong>"._t("Type of content")."</strong>: ".caGetTableDisplayName($vn_content_table_num)."<br/>\n";
					
					$vs_buf .= "</div>\n";
				} else {
					if ($vn_content_table_num = $po_view->request->getParameter('table_num', pInteger)) {
						$vs_buf .= "<div><strong>"._t("Type of content")."</strong>: ".caGetTableDisplayName($vn_content_table_num)."<br/>\n";
					
						$vs_buf .= "</div>\n";
					}
				}
				
				$t_user = new ca_users($t_item->get('user_id'));
				if ($t_user->getPrimaryKey()) {
					$vs_buf .= "<div><strong>"._t('Owner')."</strong>: ".$t_user->get('fname').' '.$t_user->get('lname')."</div>\n";
				}
			}
			
			//
			// Output extra useful info for search forms
			//
			if ($vs_table_name === 'ca_search_forms') {
				if ($t_item->getPrimaryKey()) {
					$vs_buf .= "<div><strong>"._t("Number of placements")."</strong>: ".$t_item->getPlacementCount(array('user_id' => $po_view->request->getUserID()))."<br/>\n";
					
					$vn_content_table_num = $t_item->get('table_num');
					$vs_buf .= "<strong>"._t("Searches for")."</strong>: ".caGetTableDisplayName($vn_content_table_num)."<br/>\n";
					
					$vs_buf .= "</div>\n";
				} else {
					if ($vn_content_table_num = $po_view->request->getParameter('table_num', pInteger)) {
						$vs_buf .= "<div><strong>"._t("Searches for")."</strong>: ".caGetTableDisplayName($vn_content_table_num)."<br/>\n";
					
						$vs_buf .= "</div>\n";
					}
				}
				$t_user = new ca_users($t_item->get('user_id'));
				if ($t_user->getPrimaryKey()) {
					$vs_buf .= "<div><strong>"._t('Owner')."</strong>: ".$t_user->get('fname').' '.$t_user->get('lname')."</div>\n";
				}
			}
		// -------------------------------------------------------------------------------------
		
	
			if (sizeof($va_reps) > 0) {
				
				$va_imgs = array();
				foreach($va_reps as $va_rep) {
					if (!($va_rep['info']['preview170']['WIDTH'] && $va_rep['info']['preview170']['HEIGHT'])) { continue; }
					$va_imgs[] = "{url:'".$va_rep['urls']['preview170']."', width: ".$va_rep['info']['preview170']['WIDTH'].", height: ".
					$va_rep['info']['preview170']['HEIGHT'].", link: '#', onclick:  'caMediaPanel.showPanel(\'".
					caNavUrl($po_view->request, 'editor/objects', 'ObjectEditor', 'GetRepresentationInfo', array('object_id' => $vn_item_id, 'representation_id' => $va_rep['representation_id']))."\')'}";
				}
				
				if (sizeof($va_imgs) > 0) {
					$vs_buf .= "<div class='button' style='text-align:right;'><a href='#' id='inspectorMoreInfo'>"._t("More info")."</a> &rsaquo;</div>
			<div id='inspectorInfo' style='background-color:#f9f9f9; border: 1px solid #eee; margin:3px 0px -3px 0px;'>
				<div id='inspectorInfoRepScrollingViewer'>
					<div id='inspectorInfoRepScrollingViewerContainer'>
						<div id='inspectorInfoRepScrollingViewerImageContainer'></div>
					</div>
				</div>
		";
					if (sizeof($va_reps) > 1) {
						$vs_buf .= "
					<div style='width: 170px; text-align: center;'>
						<a href='#' onclick='inspectorInfoRepScroller.scrollToPreviousImage(); return false;'>&larr;</a>
						<span id='inspectorInfoRepScrollingViewerCounter'></span>
						<a href='#' onclick='inspectorInfoRepScroller.scrollToNextImage(); return false;'>&rarr;</a>
					</div>
		";
					}
				
		
					
					$vs_buf .= "<script type='text/javascript'>";
					$vs_buf .= "
					var inspectorInfoRepScroller = caUI.initImageScroller([".join(",", $va_imgs)."], 'inspectorInfoRepScrollingViewerImageContainer', {
							containerWidth: 170, containerHeight: 170,
							imageCounterID: 'inspectorInfoRepScrollingViewerCounter',
							scrollingImageClass: 'inspectorInfoRepScrollerImage',
							scrollingImagePrefixID: 'inspectorInfoRep'
							
					});
				</script>";
				
					$vs_buf .= "</div>\n";
				}
			}
			
			// list of sets in which item is a member
			$t_set = new ca_sets();
			if (is_array($va_sets = caExtractValuesByUserLocale($t_set->getSetsForItem($t_item->tableNum(), $t_item->getPrimaryKey()))) && sizeof($va_sets)) {
				$va_links = array();
				foreach($va_sets as $vn_set_id => $va_set) {
					$va_links[] = "<a href='".caEditorUrl($po_view->request, 'ca_sets', $vn_set_id)."'>".$va_set['name']."</a>";
				}
				$vs_buf .= "<div><strong>".((sizeof($va_links) == 1) ? _t("In set") : _t("In sets"))."</strong> ".join(", ", $va_links)."</div>\n";
			}
			
			// export options		
			if ($vn_item_id && $vs_select = $po_view->getVar('available_mappings_as_html_select')) {
				$vs_buf .= "<div class='inspectorExportControls'>".caFormTag($po_view->request, 'exportItem', 'caExportForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true));
				$vs_buf .= $vs_select;
				$vs_buf .= caHTMLHiddenInput($t_item->primaryKey(), array('value' => $t_item->getPrimaryKey()));
				$vs_buf .= caHTMLHiddenInput('download', array('value' => 1));
				$vs_buf .= caFormSubmitLink($po_view->request, 'Export &rsaquo;', 'button', 'caExportForm');
				$vs_buf .= "</form></div>";
			}
			$vs_buf .= "</div></h4>\n";
			
			if (sizeof($va_reps) > 0) {
				$vs_buf .= "
	<script type='text/javascript'>
		var inspectorCookieJar = jQuery.cookieJar('caCookieJar');
		
		if (inspectorCookieJar.get('inspectorMoreInfoIsOpen') == undefined) {		// default is to have media open
			inspectorCookieJar.set('inspectorMoreInfoIsOpen', 1);
		}
		if (inspectorCookieJar.get('inspectorMoreInfoIsOpen') == 1) {
			jQuery('#inspectorInfo').toggle(0);
			jQuery('#inspectorMoreInfo').html('".addslashes(_t('Less info'))."');
		}
	
		jQuery('#inspectorMoreInfo').click(function() {
			jQuery('#inspectorInfo').slideToggle(350, function() { 
				inspectorCookieJar.set('inspectorMoreInfoIsOpen', (this.style.display == 'block') ? 1 : 0); 
				jQuery('#inspectorMoreInfo').html((this.style.display == 'block') ? '".addslashes(_t('Less info'))."' : '".addslashes(_t('More info'))."');
			}); 
			return false;
		});
	</script>
	";
			}
		}
		return $vs_buf;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	  *
	  */
	function caFilterTableList($pa_tables) {
		require_once(__CA_MODELS_DIR__.'/ca_occurrences.php');
		$o_config = Configuration::load();
		$o_dm = Datamodel::load();
		
		// assume table display names (*not actual database table names*) are keys and table_nums are values
		
		$va_filtered_tables = array();
		foreach($pa_tables as $vs_display_name => $vn_table_num) {
			$vs_table_name = $o_dm->getTableName($vn_table_num);
			
			if ((int)($o_config->get($vs_table_name.'_disable'))) { continue; }
			
			switch($vs_table_name) {
				case 'ca_occurrences':
					$t_occ = new ca_occurrences();	
					$va_types = $t_occ->getTypeList();
					$va_type_labels = array();
					foreach($va_types as $vn_item_id => $va_type_info) {
						$va_type_labels[] = $va_type_info['name_plural'];
					}
					
					if (sizeof($va_type_labels)) {
						$va_filtered_tables[join('/', $va_type_labels)] = $vn_table_num;
					}
					break;
				default:	
					$va_filtered_tables[$vs_display_name] = $vn_table_num;
					break;
			}
		}
		return $va_filtered_tables;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	function caGetTableDisplayName($pm_table_name_or_num, $pb_use_plural=true) {
		require_once(__CA_MODELS_DIR__.'/ca_occurrences.php');
		$o_dm = Datamodel::load();
		
		$vs_table = $o_dm->getTableName($pm_table_name_or_num);
		
		switch($vs_table) {
			case 'ca_occurrences':
				$t_occ = new ca_occurrences();	
					$va_types = $t_occ->getTypeList();
					$va_type_labels = array();
					foreach($va_types as $vn_item_id => $va_type_info) {
						$va_type_labels[] = $va_type_info[($pb_use_plural ? 'name_plural' : 'name_singular')];
					}
					
					return join('/', $va_type_labels);
				break;
			default:
				if($t_instance = $o_dm->getInstanceByTableName($vs_table, true)) {
					return $t_instance->getProperty(($pb_use_plural ? 'NAME_PLURAL' : 'NAME_SINGULAR'));
				}
				break;
		}
		
		return null;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * 
	 *
	 * @param
	 *
	 * @return 
	 */
	function caGetMediaDisplayInfo($ps_context, $ps_mimetype) {
		$o_config = Configuration::load();
		$o_media_display_config = Configuration::load($o_config->get('media_display'));
		
		if (!is_array($va_context = $o_media_display_config->getAssoc($ps_context))) { return null; }
	
		foreach($va_context as $vs_media_class => $va_media_class_info) {
			if (!is_array($va_mimetypes = $va_media_class_info['mimetypes'])) { continue; }
			
			if (in_array($ps_mimetype, $va_mimetypes)) {
				return $va_media_class_info;
			}
		}
		return null;
	}
	# ------------------------------------------------------------------------------------------------
	function caShowControlBox($request, $config, $position) {
		$control_box_position = $config->get('control_box_position');

		if ($control_box_position == 'both') {
			if ($request->isAjax()) {
				return $position == 'top';
			}
			return true;
		}
		if ($control_box_position == $position) {
			return true;
		}
		return false;
	}
	# ------------------------------------------------------------------------------------------------
?>