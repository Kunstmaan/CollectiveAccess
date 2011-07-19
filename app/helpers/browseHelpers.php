<?php
/** ---------------------------------------------------------------------
 * app/helpers/browseHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
   
require_once(__CA_MODELS_DIR__.'/ca_lists.php');


	# ---------------------------------------
	/**
	 * 
	 *
	 * @param array $pa_facet
	 * @param array $pa_item
	 * @param array $pa_facet_info
	 * @param array $pa_options List of display options. Supported options are:
	 *		termClass = CSS class to <span> browse term with. If not set, defaults to 'hierarchyBrowserItemTerm' 
	 *		pathClass = CSS class to <span> browse path elements with. If not set, defaults to 'hierarchyBrowserItemPath' 
	 *		
	 * @return string 
	 */
	function caGetLabelForDisplay(&$pa_facet, $pa_item, $pa_facet_info, $pa_options=null) {
		$vs_term_class = (isset($pa_options['termClass']) && $pa_options['termClass']) ? $pa_options['termClass'] : 'hierarchyBrowserItemTerm';
		$vs_label = "<span class='{$vs_term_class}'>".htmlentities($pa_item['label'], ENT_COMPAT, 'UTF-8')."</span>";
		if ($pa_facet_info['show_hierarchy'] && $pa_item['parent_id']) {
			$va_hierarchy = caGetHierarchicalLabelsForDisplay($pa_facet, $pa_item['parent_id'], $pa_options);
			array_unshift($va_hierarchy, $vs_label);
			if (isset($pa_facet_info['remove_first_items']) && ($pa_facet_info['remove_first_items'] > 0)) {
				if (($vn_l = sizeof($va_hierarchy) - $pa_facet_info['remove_first_items']) > 0) {
					$va_hierarchy = array_slice($va_hierarchy, 0, $vn_l);
				}
			}
			
			if (isset($pa_facet_info['hierarchy_limit']) && ($pa_facet_info['hierarchy_limit'] > 0) && (sizeof($va_hierarchy) > $pa_facet_info['hierarchy_limit'])) {
				$va_hierarchy = array_slice($va_hierarchy, 0, $pa_facet_info['hierarchy_limit']);
			}
			
			if (strtolower($pa_facet_info['hierarchy_order']) == 'asc') {
				$va_hierarchy = array_reverse($va_hierarchy);
			}
			
			return join($pa_facet_info['hierarchical_delimiter'], $va_hierarchy);
		}
		
		return $vs_label;
	}
	# ---------------------------------------
	/**
	 * 
	 *
	 * @param array $pa_facet
	 * @param int $pn_id
	 * @param array $pa_facet_info
	 * @param array $pa_options List of display options. Supported options are:
	 *		pathClass = CSS class to <span> browse path elements with. If not set, defaults to 'hierarchyBrowserItemPath' 
	 *
	 * @return array
	 */
	function caGetHierarchicalLabelsForDisplay(&$pa_facet, $pn_id, $pa_options=null) {
		if (!$pa_facet[$pn_id]['label']) { return array(); }
		
		$vs_path_class = (isset($pa_options['pathClass']) && $pa_options['pathClass']) ? $pa_options['pathClass'] : 'hierarchyBrowserItemPath';
		$va_values = array("<span class='{$vs_path_class}'>".htmlentities($pa_facet[$pn_id]['label'], ENT_COMPAT, 'UTF-8')."</span>");
		if ($vn_parent_id = $pa_facet[$pn_id]['parent_id']) {
			$va_values = array_merge($va_values, caGetHierarchicalLabelsForDisplay($pa_facet, $vn_parent_id));
		}
		return $va_values;
	}
	# ---------------------------------------
?>