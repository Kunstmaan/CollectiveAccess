<?php
/* ----------------------------------------------------------------------
 * views/editor/objects/ajax_object_representation_info_html.php : 
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
	$t_object 				= $this->getVar('t_subject');
	$t_rep 					= $this->getVar('t_object_representation');
	$vs_show_version 		= $this->getVar('version');
	$va_versions 			= $this->getVar('versions');	
	$vn_representation_id 	= $t_rep->getPrimaryKey();
	$vn_num_pages 			= $this->getVar('num_pages');
	$va_reps 				= $this->getVar('reps');
	
	// Get filename of originally uploaded file
	$va_media_info 			= $t_rep->getMediaInfo('media');
	$vs_original_filename 	= $va_media_info['ORIGINAL_FILENAME'];

if (($vn_num_multifiles = $this->getVar('num_multifiles')) == 0) {	
?>
	<!-- Controls -->
	<div class="caMediaOverlayControls">
			<table width="95%">
				<tr valign="middle">
					<td align="left">
						<form>
<?php
							print _t('Display %1 version', caHTMLSelect('version', $va_versions, array('id' => 'caMediaOverlayVersionControl', 'class' => 'caMediaOverlayControls'), array('value' => $vs_show_version)));
							$va_rep_info = $this->getVar('version_info');

							if (($this->getVar('version_type')) && ($va_rep_info['WIDTH'] > 0) && ($va_rep_info['HEIGHT'] > 0)) {
								print " (".$this->getVar('version_type')."; ". $va_rep_info['WIDTH']." x ". $va_rep_info['HEIGHT']."px)";
							}							
?>
						</form>
						
					</td>
<?php
					if($this->request->user->canDoAction("can_edit_ca_objects")){
?>
						<td align="middle" valign="middle">
							<div><div style="float:left"><a href="<?php print caEditorUrl($this->request, 'ca_object_representations', $vn_representation_id)?>" ><?php print caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__)?></a></div><div style="float:left; margin:2px 0px 0px 3px;"><?php print _t("Edit Representation Metadata"); ?></div></div>
						</td>
<?php
					}
					if((1) || $this->request->user->canDoAction("can_download_ca_object_representations")){
?>
					<td align="right" text-align="right">
<?php 
						print caFormTag($this->request, 'DownloadRepresentation', 'downloadRepresentationForm', 'editor/objects/ObjectEditor', 'get', 'multipart/form-data', null, array('disableUnsavedChangesWarning' => true));
						print caHTMLSelect('version', $va_versions, array('id' => 'caMediaOverlayVersionControl', 'class' => 'caMediaOverlayControls'), array('value' => 'original'));
						print ' '.caFormSubmitLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_DOWNLOAD__, null, array('align' => 'middle')), '', 'downloadRepresentationForm');
						print caHTMLHiddenInput('representation_id', array('value' => $t_rep->getPrimaryKey()));
						print caHTMLHiddenInput('object_id', array('value' => $t_object->getPrimaryKey()));
						print caHTMLHiddenInput('download', array('value' => 1));
?>
						</form>
					</td>
<?php
					}
?>
				</tr>
			</table>
	</div><!-- end caMediaOverlayControls -->
<?php
	$vn_viewer_height = 550;
	if(sizeof($va_reps) > 1){
		$vn_viewer_height = 480;
	}
	if(sizeof($va_reps) > 10){
		$vn_viewer_height = 469;
	}
?>
	<div id="caMediaOverlayContent" style="height:<?php print $vn_viewer_height; ?>px;">
<?php
		switch($vs_show_version) {
			case 'tilepic':
				print $t_rep->getMediaTag('media', $vs_show_version, array(
					'id' => 'caMediaOverlayContentMedia', 
					'viewer_width' => "100%", 'viewer_height' => "100%",
					'viewer_base_url' => $this->request->getBaseUrlPath(),
					'viewer_theme_url' => $this->request->getThemeUrlPath()
				))."<br/>";
				break;
			case 'h264_hi':
				print $t_rep->getMediaTag('media', $vs_show_version, array(
					'id' => 'caMediaOverlayContentMedia', 
					'viewer_width' => "800", 'viewer_height' => "525", 'viewer_base_url' => $this->request->getBaseUrlPath(),
					'poster_frame_url' => $t_rep->getMediaUrl('media', 'medium')
				))."<br/>";
				break;
			case 'mp3':
				print '<div style="width: 420px; height: 90px; margin-left: auto; margin-right: auto; margin-top: 200px;">';
				print $t_rep->getMediaTag('media', 'mp3', array('id' => 'caMediaOverlayContentMedia', 'viewer_width' => '400', 'viewer_height' => '100'));
				print '</div>';
				break;
			default:
				print $t_rep->getMediaTag('media', $vs_show_version, array('id' => 'caMediaOverlayContentMedia', 'viewer_width' => '800', 'viewer_height' => '525', 'poster_frame_url' => $t_rep->getMediaUrl('media', 'medium')))."<br/>";
				break;
		}
?>
	</div><!-- end caMediaOverlayContent -->
<?php
	# --- get all reps and if there are more than one to display thumbnail links
	if(sizeof($va_reps) > 1){
		print "<div id='caMediaOverlayThumbnails'>";
		# --- calculate with of div - we set the width so we can force side to side scrolling if there are a lot of reps
		print "<div style='width:".(74*(sizeof($va_reps)))."px;'>";
		foreach($va_reps as $va_rep_info){
			print "<a onclick='caMediaPanel.showPanel(\"".caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetRepresentationInfo', array('object_id' => $t_object->get("object_id"), 'representation_id' => $va_rep_info['representation_id']))."\"); return false;' >".$va_rep_info["tags"]["icon"]."</a>";
		}
		print "</div></div><!-- caMediaOverlayThumbnails -->";
	}
?>

<script type="text/javascript">
	jQuery('#caMediaOverlayVersionControl').change(
		function() {
			var containerID = jQuery(this).parents(':eq(6)').attr('id');
			jQuery("#" + containerID).load("<?php print caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetRepresentationInfo', array('representation_id' => $t_rep->getPrimaryKey(), 'object_id' => $t_object->getPrimaryKey(), 'version' => '')); ?>" + this.value);
		}
	);
	
	jQuery('#caMediaOverlayMultifileViewLink').click(
		function() {
			var containerID = jQuery(this).parents(':eq(5)').attr('id');
			jQuery("#" + containerID).load("<?php print caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetRepresentationMultifileInfo', array('representation_id' => $t_rep->getPrimaryKey(), 'object_id' => $t_object->getPrimaryKey(), 'version' => '')); ?>");
		}
	);
</script>

<?php
} else {
?>
	<!-- Controls -->
	<div class="caMediaOverlayControls">
	
			<table width="100%">
				<tr valign="middle">

				</tr>
			</table>
	</div>

	<div id="caMediaOverlayContent">
		<div style="float: left;">
<!--			<a href="#" onclick="caLoadMultifileFileList(caMultifileFileListCurrentPage -1);" class="button">&lsaquo; <?php print _t("Previous"); ?></a> <?php print _t("Page"); ?> <span id="caMediaOverlayMultifileFileListCurrentPage">1</span>/<?php print $vn_num_pages; ?> <a href="#" onclick="caLoadMultifileFileList(caMultifileFileListCurrentPage+1);" class="button"><?php print _t("Next"); ?> &rsaquo;</a>
			<form action="#"><?php print _t("Jump to page"); ?>: <input type="text" size="2" name="page" id="jumpToPageNum" value=""/> <a href="#" onclick='caLoadMultifileFileList(jQuery("#jumpToPageNum").val());' class="button"><?php print _t('GO'); ?></a></form>
-->			
			<div class="caMediaOverlayMultifileFileList" id="caMediaOverlayMultifileFileList">
						
			</div>
		</div>
		
		<div class="caMediaOverlayMultifileMetadata" id="caMediaOverlayMultifileMetadata" style="float: right;">
			<?php print "<div style='margin-bottom:5px'>"._t('Click on a file icon to view more information about it')."</div>"; 
			print "<div class='filename'>{$vs_original_filename}<br/></div>";
			print $t_rep->getMediaTag('media', $vs_show_version, array('id' => 'caMediaOverlayContentMedia', 'viewer_width' => '580', 'viewer_height' => '345', 'embed' => true, 'poster_frame_url' => $t_rep->getMediaUrl('media', 'medium')))."<br/>"; ?>
		<div><div style="float:left"><a href="<?php print caEditorUrl($this->request, 'ca_object_representations', $vn_representation_id)?>" ><?php print caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__)?></a></div><div style="float:left; margin-left:3px;";>Edit Metadata</div></div>
		</div>
		
	</div>
	
	<!-- Controls -->
	<div class="caMediaOverlayControls">
	
			<table width="100%">
				<tr valign="middle">

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
<?php
}
?>