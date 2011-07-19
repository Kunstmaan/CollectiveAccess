<?php
/* ----------------------------------------------------------------------
 * views/editor/object_representations/ajax_object_representation_info_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
	$t_object_representation = $this->getVar('t_subject');
	$vs_show_version = $this->getVar('version');
	$va_versions = $this->getVar('versions');
	
?>
	<div class="objectRepresentationOverlayControls">
		<form>
			<table width="100%">
				<tr valign="middle">
					<td>
						Show 
<?php
						print caHTMLSelect('version', $va_versions, array('id' => 'objectRepresentationOverlayVersionControl', 'class' => 'objectRepresentationOverlayControls'), array('value' => $vs_show_version));
?>
						version
					</td><td>
<?php
						$va_rep_info = $this->getVar('version_info');

						print "(".$this->getVar('version_type')."; ". $va_rep_info['WIDTH']." x ". $va_rep_info['HEIGHT']."px)";
?>
					</td>
					<td>
			<?php print caNavButton($this->request, __CA_NAV_BUTTON_DOWNLOAD__, 'Download', 'editor/objects', 'ObjectEditor', 'DownloadRepresentation', array('version' => $vs_show_version, 'representation_id' => $t_object_representation->getPrimaryKey(), 'download' => 1), array(), array('no_background' => true, 'dont_show_content' => true)); ?>
					</td>
				</tr>
			</table>
		</form>
	</div>
	<div id="objectRepresentationOverlayContent">
<?php
	print $vs_show_version;
		switch($vs_show_version) {
			case 'tilepic':
				print $t_object_representation->getMediaTag('media', $vs_show_version, array('id' => 'objectRepresentationOverlayContentMedia', 'directly_embed_flash' => true, 'viewer_width' => "100%", 'viewer_height' => "95%"))."<br/>";
				break;
			case 'flv':
			case 'h264_hi':
			case 'h264_lo':
				print $t_object_representation->getMediaTag('media', $vs_show_version, array(
					'id' => 'objectRepresentationOverlayContentMedia', 
					'directly_embed_flash' => true, 
					'viewer_width' => "100%", 'viewer_height' => "95%"
				))."<br/>";
				break;
			default:
				print $t_object_representation->getMediaTag('media', $vs_show_version, array('id' => 'objectRepresentationOverlayContentMedia', 'directly_embed_flash' => true, 'viewer_width' => $va_rep_info['WIDTH'], 'viewer_height' => $va_rep_info['HEIGHT']))."<br/>";
				break;
		}
?>
	</div>

<script type="text/javascript">
	jQuery('#objectRepresentationOverlayVersionControl').click(
		function() {
			var containerID = jQuery(this).parents(':eq(6)').attr('id');
			jQuery("#" + containerID).load("<?php print caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetRepresentationInfo', array('representation_id' => $t_object_representation->getPrimaryKey(), 'version' => '')); ?>" + this.value);
		}
	);
</script>