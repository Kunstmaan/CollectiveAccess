<?php
/* ----------------------------------------------------------------------
 * views/pageFormat/pageHeader.php : 
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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
	    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<title><?php print $this->appconfig->get("window_title"); ?></title>
		<link rel="stylesheet" href="<?php print $this->request->getThemeUrlPath(); ?>/css/base.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="<?php print $this->request->getThemeUrlPath(); ?>/css/sml.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="<?php print $this->request->getThemeUrlPath(); ?>/css/sets.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="<?php print $this->request->getThemeUrlPath(); ?>/css/jquery-ui-1.8.11.custom.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="<?php print $this->request->getBaseUrlPath(); ?>/js/videojs/video-js.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="<?php print $this->request->getBaseUrlPath(); ?>/js/jquery/jquery-jplayer/jplayer.blue.monday.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="<?php print $this->request->getBaseUrlPath(); ?>/js/jquery/jquery-autocomplete/jquery.autocomplete.css" type="text/css" media="screen" />
		
<?php
	print JavascriptLoadManager::getLoadHTML($this->request->getBaseUrlPath());
?>

		<script type="text/javascript">
			// initialise plugins
			jQuery(function(){
				jQuery('ul.sf-menu').superfish(
					{
						delay: 500,
						speed: 150,
                                                disableHI: true,
						animation: { opacity: 'show' }
					}
				);
			});
			
			// initialize CA Utils
			caUI.initUtils({unsavedChangesWarningMessage: '<?php _p('You have made changes in this form that you have not yet saved. If you navigate away from this form you will lose your unsaved changes.'); ?>'});

		</script>
		<!--[if lte IE 6]>
			<style type="text/css">
			#container {
			height: 100%;
			}
			</style>
			<![endif]-->
		<!-- super fish end menus -->
	</head>
	<body>
		<div align="center">