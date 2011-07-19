<?php
/* ----------------------------------------------------------------------
 * views/editor/objects/ajax_object_representation_info_html.php : 
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
	$t_object = $this->getVar('t_subject');
	$t_rep = $this->getVar('t_object_representation');
	$vn_representation_id = $t_rep->getPrimaryKey();
	$vn_num_pages = (int)$this->getVar('num_pages');
?>
	
	<div id="caMediaOverlayContent">
		<div style="float: left;">
			<a href="#" onclick="caLoadMultifileFileList(caMultifileFileListCurrentPage -1);" class="button">&lsaquo; <?php print _t("Previous"); ?></a> <?php print _t("Page"); ?> <span id="caMediaOverlayMultifileFileListCurrentPage">1</span>/<?php print $vn_num_pages; ?> <a href="#" onclick="caLoadMultifileFileList(caMultifileFileListCurrentPage+1);" class="button"><?php print _t("Next"); ?> &rsaquo;</a>
			<br/>
			<form action="#"><?php print _t("Jump to page"); ?>: <input type="text" size="3" name="page" id="jumpToPageNum" value=""/> <a href="#" onclick='caLoadMultifileFileList(jQuery("#jumpToPageNum").val());' class="button"><?php print _t('GO'); ?></a></form>
			<div class="caMediaOverlayMultifileFileList" id="caMediaOverlayMultifileFileList">
							
			</div>
		</div>
		
		<div class="caMediaOverlayMultifileMetadata" id="caMediaOverlayMultifileMetadata" style="float: right;">
			<?php print _t('Click on a file icon to view more information about it'); ?>
		</div>
	</div>
	
	<!-- Controls -->
	<div class="caMediaOverlayControls">
			<table width="100%">
				<tr valign="middle">
<?php
				if (($vn_num_multifiles = $this->getVar('num_multifiles')) > 0) {
?>
					<td>
						<?php print _t(($vn_num_multifiles == 1) ? 'Showing 1 additional file attached to this representation' : 'Showing %1 additional files attached to this representation', $vn_num_multifiles); ?>
					</td>
<?php
				}
				

?>
				</tr>
			</table>
	</div>

<script type="text/javascript">
	jQuery('#caMediaOverlayVersionControl').change(
		function() {
			var containerID = jQuery(this).parents(':eq(6)').attr('id');
			jQuery("#" + containerID).load("<?php print caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetRepresentationInfo', array('representation_id' => $t_rep->getPrimaryKey(), 'object_id' => $t_object->getPrimaryKey(), 'version' => '')); ?>" + this.value);
		}
	);
	
	jQuery('#caMediaOverlayRepresentationViewLink').click(
		function() {
			var containerID = jQuery(this).parents(':eq(5)').attr('id');
			jQuery("#" + containerID).load("<?php print caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetRepresentationInfo', array('representation_id' => $t_rep->getPrimaryKey(), 'object_id' => $t_object->getPrimaryKey(), 'version' => '')); ?>");
		}
	);
	
	jQuery(document).ready(
		function() {
			jQuery('#caMediaOverlayMultifileFileList').load("<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'GetRepresentationMultifileFileList', array('representation_id' => $t_rep->getPrimaryKey(), 'object_id' => $t_object->getPrimaryKey())); ?>");
		}
	);
	
	var caMultifileFileListCurrentPage = 1;
	function caLoadMultifileFileList(page) {
		if (page < 1) { page = 1; }
		if (page > <?php print $vn_num_pages; ?>) { page = <?php print $vn_num_pages; ?>; }
		jQuery('#caMediaOverlayMultifileFileList').load("<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'GetRepresentationMultifileFileList', array('representation_id' => $t_rep->getPrimaryKey(), 'object_id' => $t_object->getPrimaryKey())); ?>/mp/" + page);
		
		caMultifileFileListCurrentPage = page;
		jQuery('#caMediaOverlayMultifileFileListCurrentPage').html("" + caMultifileFileListCurrentPage);
		jQuery('#jumpToPageNum').val('');
	}
	
</script>