<?php
/* ----------------------------------------------------------------------
 * views/editor/objects/ajax_object_representation_multifile_filelist_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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

	$t_rep = $this->getVar('t_object_representation');
	$vn_representation_id = $t_rep->getPrimaryKey();
	$vn_num_multifiles = $this->getVar('num_multifiles');
	
	// Get filename of originally uploaded file
	$va_media_info = $t_rep->getMediaInfo('media');
	$vs_original_filename = $va_media_info['ORIGINAL_FILENAME'];
	
	print "<div id='primaryRep'>Primary Representation<br/><a href='#' onclick='jQuery(\"#caMediaOverlayMultifileMetadata\").load(\"".caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'GetRepresentationForDisplay', array('representation_id' => $vn_representation_id, ''))."\");'>".$t_rep->getMediaTag('media', 'preview')."</a><br/>{$vs_original_filename}</div><div id='multifileRep'>";
	print _t(($vn_num_multifiles == 1) ? '1 Additional File' : '%1 Additional Files', $vn_num_multifiles);
	foreach($this->getVar('multifiles') as $va_multifile) {
		print "<div style='padding: 5px;'><a href='#' onclick='jQuery(\"#caMediaOverlayMultifileMetadata\").load(\"".caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'GetRepresentationMultifileMetadataDisplay', array('representation_id' => $vn_representation_id, 'multifile_id' => $va_multifile['multifile_id']))."\");'>".$va_multifile['preview_tag']."</a><br/>".$va_multifile['resource_path']."</div>\n";
	}
?>
</div>