<?php
/* ----------------------------------------------------------------------
 * views/find/quick_search_results.php : 
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
 
 $ps_search = $this->getVar('search');
 $vs_sort_form = caFormTag($this->request, 'Index', 'QuickSearchSortForm');
 $vs_sort_form .= _t('Sort by ').caHTMLSelect('sort', array(_t('name') => 'name', _t('idno') => 'idno'), array('onchange' => 'jQuery("#QuickSearchSortForm").submit();'), array('value' => $this->getVar('sort')));
 $vs_sort_form .= "</form>";
 print $vs_control_box = caFormControlBox(
		'<div class="quickSearchHeader">'._t("Top %1 results for <em>%2</em>", $this->getVar('maxNumberResults'), $this->getVar('search')).'</div>', 
		'',
		$vs_sort_form
	);
	
	
	$vn_num_occurrence_types = sizeof($va_occurrence_types = $this->getVar('occurrence_types'));
	
	$vn_num_result_lists_to_display = 0;
	
	$va_searches = $this->getVar('searches');
?>

<div class="quickSearchContentArea">

<?php
	foreach($va_searches as $vs_table => $va_info) {
		if ($vs_table == 'ca_occurrences') {
			if($vn_num_occurrence_types > 0) {
				$va_occurrences_by_type = array();
				
				$o_res = $this->getVar('ca_occurrences_results');
				while($o_res->nextHit()) {
					$va_occurrences_by_type[$o_res->get('ca_occurrences.type_id')][] = array(
						'name' => join('; ', $o_res->getDisplayLabels()),
						'occurrence_id' => $o_res->get('ca_occurrences.occurrence_id'),
						'idno' => $o_res->get('ca_occurrences.idno')
					);
				}
			
				$vn_i = 0;
				
				
				foreach($va_occurrence_types as $vn_type_id => $va_type_info) {
					if ((!isset($va_occurrences_by_type[$vn_type_id])) || (!$va_occurrences_by_type[$vn_type_id])) {
						print "<div class='quickSearchNoResults rounded'>".unicode_ucfirst($va_type_info['name_plural'])." (0)"."</div>";
					}else{
						if (is_array($va_occurrences_by_type) && is_array($va_occurrences_by_type[$vn_type_id])) {
							$va_occurrences = $va_occurrences_by_type[$vn_type_id];
						} else {
							$va_occurrences = array();
						}
?>
				
						<div class="quickSearchResultHeader rounded">
							<div class="quickSearchFullResultsLink"><?php print caNavLink($this->request, _t("Full Results &rsaquo;"), null, $va_info['searchModule'], $va_info['searchController'], $va_info['searchAction'], array("search" => $ps_search, "type_id" => $vn_type_id)); ?></div>
							<a href='#' style="text-decoration:none; color:#333;" id='show<?php print $vs_table.$vn_type_id; ?>' onclick='$("#<?php print $vs_table.$vn_type_id; ?>_results").slideDown(250); $("#show<?php print $vs_table.$vn_type_id; ?>").hide(); $("#hide<?php print $vs_table.$vn_type_id; ?>").show(); return false; '><?php print unicode_ucfirst($va_type_info['name_plural'])." (".sizeof($va_occurrences_by_type[$vn_type_id]).")"; ?> <img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/expand.gif" width="11" height="11" border="0"></a>
							<a href='#' id='hide<?php print $vs_table.$vn_type_id; ?>' style='display:none; text-decoration:none; color:#333;' onclick='$("#<?php print $vs_table.$vn_type_id; ?>_results").slideUp(250); $("#show<?php print $vs_table.$vn_type_id; ?>").slideDown(1); $("#hide<?php print $vs_table.$vn_type_id; ?>").hide(); return false;'><?php print unicode_ucfirst($va_type_info['name_plural'])." (".sizeof($va_occurrences_by_type[$vn_type_id]).")"; ?> <img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/collapse.gif" width="11" height="11" border="0"></a>
						</div>
						<div class="quickSearchHalfWidthResults" id="<?php print $vs_table.$vn_type_id; ?>_results" style="display:none;">
							<ul class='quickSearchList'>
<?php
								foreach($va_occurrences as $vn_i => $va_occurrence) {
									$vs_idno_display = '';
									if ($va_occurrence['idno']) {
										$vs_idno_display = ' ['.$va_occurrence['idno'].']';
									}
									print '<li class="quickSearchList">'.caNavLink($this->request, $va_occurrence['name'], null, $va_info['module'], $va_info['controller'], $va_info['action'], array('occurrence_id' => $va_occurrence['occurrence_id']))." ".$vs_idno_display."</li>\n";
								}
?>
							</ul>
							<div class="quickSearchResultHide"><a href='#' id='hide<?php print $vs_table.$vn_type_id; ?>' onclick='$("#<?php print $vs_table.$vn_type_id; ?>_results").slideUp(250); $("#show<?php print $vs_table.$vn_type_id; ?>").slideDown(1); $("#hide<?php print $vs_table.$vn_type_id; ?>").hide(); return false;'> <img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/collapse.gif" width="11" height="11" border="0"></a></div>
						</div>
				
<?php
						$vn_i++;
					}
				}
			}	
		} else {
?>
	
<?php	
		$o_res = $this->getVar($vs_table.'_results');
?>
<?php	
		if ($o_res->numHits() >= 1) { 
?>
			<div class="quickSearchResultHeader rounded" >
				<div class="quickSearchFullResultsLink"><?php print caNavLink($this->request, _t("Full Results &rsaquo;"), null, $va_info['searchModule'], $va_info['searchController'], $va_info['searchAction'], array("search" => $ps_search)); ?></div>
				<a href='#' style="text-decoration:none; color:#333;" id='show<?php print $vs_table; ?>' onclick='$("#<?php print $vs_table; ?>_results").slideDown(250); $("#show<?php print $vs_table; ?>").hide(); $("#hide<?php print $vs_table; ?>").show(); return false; '><?php print $va_info['displayname']." (".$o_res->numHits().")"; ?> <img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/expand.gif" width="11" height="11" border="0"></a>
				<a href='#' id='hide<?php print $vs_table; ?>' style='display:none; text-decoration:none; color:#333;' onclick='$("#<?php print $vs_table; ?>_results").slideUp(250); $("#show<?php print $vs_table; ?>").slideDown(1); $("#hide<?php print $vs_table; ?>").hide(); return false;'><?php print $va_info['displayname']." (".$o_res->numHits().")"; ?> <img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/collapse.gif" width="11" height="11" border="0"></a>
			</div>
			<div class="quickSearchHalfWidthResults" id='<?php print $vs_table; ?>_results' style="display:none;">
				<ul class='quickSearchList'>
<?php
					while($o_res->nextHit()) {
						if ($vs_idno_display = trim($o_res->get($va_info['displayidno']))) {
							$vs_idno_display = ' ['.$vs_idno_display.']';
						}
						print '<li class="quickSearchList">'.caNavLink($this->request, join('; ', $o_res->getDisplayLabels()), null, $va_info['module'], $va_info['controller'], $va_info['action'], array($va_info['primary_key'] => $o_res->get($va_info['primary_key'])))." ".$vs_idno_display."</li>\n";
					}
?>
				</ul>
				<div class="quickSearchResultHide"><a href='#' id='hide<?php print $vs_table; ?>' onclick='$("#<?php print $vs_table; ?>_results").slideUp(250); $("#show<?php print $vs_table; ?>").slideDown(1); $("#hide<?php print $vs_table; ?>").hide(); return false;'> <img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/collapse.gif" width="11" height="11" border="0"></a></div>
			</div>
<?php	
		} else {
		print "<div class='quickSearchNoResults rounded'>".$va_info['displayname']." (".$o_res->numHits().")"."</div>";
		}
?>		
<?php
		}
	}
?>
</div>
