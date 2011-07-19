<?php
/* ----------------------------------------------------------------------
 * views/editor/objects/ajax_object_representation_metadata_html.php : 
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
	$t_multifile = $this->getVar('t_multifile');
?>
	<div class='filename'><?php print _t('file').': '.$t_multifile->get('resource_path'); ?></div>
<?php 	

	print "<div style='margin-bottom:0px;'>".$t_multifile->getMediaTag('media', 'large_preview')."</div>\n";
	$va_media_info = $t_multifile->getMediaInfo('media', 'original');
	foreach($va_media_info['PROPERTIES'] as $vs_prop => $vs_val) {
		if (!$vs_val) { continue; }
		
		switch($vs_prop) {
			case 'version':
				// noop
				break;
			case 'filesize':
				print "<b>"._t(unicode_ucfirst($vs_prop)).'</b>: '.sprintf("%4.1f", ((int)$vs_val)/(1024*1024))."mbytes<br/>\n"; 
				break;
			default:
				print "<b>"._t(unicode_ucfirst($vs_prop)).'</b>: '.$vs_val."<br/>\n"; 
				break;
		}
		
	}
	print "<h2>"._t('Extracted metadata').":</h2>\n";	
	print caFormatMediaMetadata($t_multifile->get('media_metadata'));
?>