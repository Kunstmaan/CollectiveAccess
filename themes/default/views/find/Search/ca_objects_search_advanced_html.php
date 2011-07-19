<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/ca_objects_search_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2009 Whirl-i-Gig
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
 	 
	$vo_result = $this->getVar('result');
	
	
 	print $this->render('Search/search_advanced_controls_html.php');
 ?>
	<div id="quickLookOverlay"> 
		<div id="quickLookOverlayContent">
		
		</div>
	</div>
	
 	<div id="resultBox">
<?php
	if($vo_result) {
		print $this->render('Results/paging_controls_html.php');
		
		print $this->render('Results/search_options_html.php');
?>

	<div class="sectionBox">
<?php
		$vs_view = $this->getVar('current_view');
		if ($vo_result->numHits() == 0) { $vs_view = 'no_results'; }
		switch($vs_view) {
			case 'full':
				print $this->render('Results/ca_objects_results_full_html.php');
				break;
			case 'list':
				print $this->render('Results/ca_objects_results_list_html.php');
				break;
			case 'no_results':
				print $this->render('Results/no_results_html.php');
				break;
			default:
				print $this->render('Results/ca_objects_results_thumbnail_html.php');
				break;
		}
?>		
	</div><!-- end sectionbox -->
<?php
	}
?>
	</div><!-- end resultbox -->
	<div class="editorBottomPadding"><!-- empty --></div>

<?php
	if (!$this->request->isAjax()) {
?>
<script type="text/javascript">
	/*
		Set up the "quicklook" panel that will be triggered by links in each search result
		Note that the actual <div>'s implementing the panel are located in views/pageFormat/pageFooter.php
	*/
	var caQuickLookPanel = caUI.initPanel({ 
		panelID: 'quickLookPanel',										/* DOM ID of the <div> enclosing the panel */
		panelContentID: 'quickLookPanelContentArea',		/* DOM ID of the content area <div> in the panel */
		exposeBackgroundColor: '#000000',						/* color (in hex notation) of background masking out page content; include the leading '#' in the color spec */
		exposeBackgroundOpacity: 0.5,							/* opacity of background color masking out page content; 1.0 is opaque */
		panelTransitionSpeed: 200 									/* time it takes the panel to fade in/out in milliseconds */
	});
</script>
<?php
	}
?>