<?php
/* ----------------------------------------------------------------------
 * views/editor/objects/ajax_object_representation_for_display_html.php : 
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
 
	$t_object 						= $this->getVar('t_subject');
	$t_rep 							= $this->getVar('t_object_representation');
	$vs_show_version 		= $this->getVar('version');
	$va_versions 				= $this->getVar('versions');	
	$vn_representation_id 	= $t_rep->getPrimaryKey();
	$vn_num_pages 			= $this->getVar('num_pages');
	
	// Get filename of originally uploaded file
	$va_media_info = $t_rep->getMediaInfo('media');
	$vs_original_filename = $va_media_info['ORIGINAL_FILENAME'];
	
		switch($vs_show_version) {
			case 'tilepic':
				print "<div class='filename'>{$vs_original_filename}<br/></div>";
				print $t_rep->getMediaTag('media', $vs_show_version, array(
					'id' => 'caMediaOverlayContentMedia', 
					'viewer_width' => "580", 'viewer_height' => "354",
					'viewer_base_url' => $this->request->getBaseUrlPath()
				))."<br/>";
				break;
			case 'h264_hi':
				print "<div class='filename'>{$vs_original_filename}<br/></div>";
				print $t_rep->getMediaTag('media', $vs_show_version, array(
					'id' => 'caMediaOverlayContentMedia', 
					'viewer_width' => "580", 'viewer_height' => "354", 'viewer_base_url' => $this->request->getBaseUrlPath(),
					'poster_frame_url' => $t_rep->getMediaUrl('media', 'medium')
				))."<br/>";
				break;
			case 'mp3':
				print '<div style="width: 420px; height: 90px; margin-left: auto; margin-right: auto; margin-top: 200px;">';
				print $t_rep->getMediaTag('media', 'mp3', array('id' => 'caMediaOverlayContentMedia', 'viewer_width' => '400', 'viewer_height' => '100'));
				print '</div>';
				break;
			default:
				print "<div class='filename'>{$vs_original_filename}<br/></div>";
				print $t_rep->getMediaTag('media', $vs_show_version, array('id' => 'caMediaOverlayContentMedia', 'viewer_width' => '580', 'viewer_height' => '354', 'embed' => true, 'poster_frame_url' => $t_rep->getMediaUrl('media', 'medium')))."<br/>";
				break;
		}
?>
<div><div style="float:left"><a href="<?php print caEditorUrl($this->request, 'ca_object_representations', $vn_representation_id)?>" ><?php print caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__)?></a></div><div style="float:left; margin-left:3px;";><?php print _t('Edit metadata'); ?></div></div>