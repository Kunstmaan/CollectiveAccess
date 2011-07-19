<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/ca_objects_quick_look_html.php
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
 
 	$o_request 					= $this->getVar('request');
	$va_reps 					= $this->getVar('reps');
	$vn_object_id 				= $this->getVar('object_id');
	$vn_representation_id 		= $this->getVar('representation_id');
	$t_object					= $this->getVar('t_object');
	
	if (!$vn_representation_id) { $vn_representation_id = $va_reps[0]['representation_id']; }
	
	# --- need to set height of viewer/image - if there is more than one rep we will display thumbnails along bottom so need a shorter large image/viewer
	$vn_viewer_height = 550;
	if(sizeof($va_reps) > 1){
		$vn_viewer_height = 480;
	}
	if(sizeof($va_reps) > 10){
		$vn_viewer_height = 470;
	}
	
	$t_rep = new ca_object_representations($vn_representation_id);
	$va_versions = $t_rep->getMediaVersions('media');
	$va_info = $t_rep->getMediaInfo('media');
	if (!in_array($ps_version, $va_versions)) { 
		$o_settings = new MediaProcessingSettings($t_rep, 'media');
		if (!($ps_version = $o_settings->getMediaDefaultViewingVersion($va_info['INPUT']['MIMETYPE']))) {
			$ps_version = $va_versions[0]; 
		}
	}
	switch($ps_version) {
		case 'tilepic':
			print $t_rep->getMediaTag('media', $ps_version, array(
				'id' => 'quicklookOverlayContentMedia', 
				'viewer_width' => "800", 'viewer_height' => $vn_viewer_height,
				'viewer_base_url' => $o_request->getBaseUrlPath()
			))."<br/>";
			break;
		case 'flv':
		case 'h264_hi':
		case 'h264_lo':
			print $t_rep->getMediaTag('media', $ps_version, array(
				'id' => 'quicklookOverlayContentMedia', 
				'viewer_width' => "800", 'viewer_height' => $vn_viewer_height, 'viewer_base_url' => $o_request->getBaseUrlPath(),
				'poster_frame_url' => $t_rep->getMediaUrl('media', 'medium')
			))."<br/>";
			break;
		default:
			print $t_rep->getMediaTag('media', $ps_version, array('id' => 'quicklookOverlayContentMedia', 'viewer_width' => '800', 'viewer_height' => $vn_viewer_height, 'embed' => true, 'poster_frame_url' => $t_rep->getMediaUrl('media', 'medium')))."<br/>";
			break;
	}
?>
	<div id="quicklookPanelContentInfo">
<?php
	$vs_caption = "";
	if($this->getVar("idno")){
		$vs_caption = $this->getVar("idno")." - ";
	}
	if(sizeof($t_object->getLabelForDisplay($o_request)) > 0){
		$vs_caption .= $t_object->getLabelForDisplay($o_request);
	}
	print caNavLink($this->request, $vs_caption, '', 'editor/objects', 'ObjectEditor', 'Edit', array('object_id' => $t_object->get('object_id')));
	
?>
	</div>
<?php
	# --- get all reps and if there are more than one to display thumbnail links
	if(sizeof($va_reps) > 1){
		print "<div id='quicklookPanelContentThumbnails'>";
		# --- calculate with of div - we set the width so we can force side to side scrolling if there are a lot of reps
		print "<div style='width:".(74*(sizeof($va_reps)))."px;'>";
		foreach($va_reps as $va_rep_info){
			print "<a onclick='caQuickLookPanel.showPanel(\"".caNavUrl($o_request, 'find', 'SearchObjects', 'QuickLook', array('object_id' => $t_object->get('object_id'), 'representation_id' => $va_rep_info["representation_id"]))."\"); return false;' >".$va_rep_info["tags"]["icon"]."</a>";
		}
		print "</div></div><!-- quicklookPanelContentThumbnails -->";
	}
?>