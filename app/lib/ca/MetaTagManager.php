<?php
/** ---------------------------------------------------------------------
 * MetaTagManager.php : class to control loading of metatags in page headers
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
 * @package CollectiveAccess
 * @subpackage Misc
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

	require_once(__CA_LIB_DIR__.'/core/Configuration.php');

	class MetaTagManager {
		# --------------------------------------------------------------------------------
		private static $opa_tags;
		# --------------------------------------------------------------------------------
		/**
		 * Initialize 
		 *
		 * @return void
		 */
		static function init() {
			MetaTagManager::$opa_tags = array('meta' => array(), 'link' => array());
		}
		# --------------------------------------------------------------------------------
		/**
		 * Add <meta> tag to response
		 *
		 * @param $ps_tag_name (string) - name attribute of <meta> tag
		 * @param $ps_content (string) - content of <meta> tag
		 * @return (bool) - Returns true if tooltip was successfully added, false if not
		 */
		static function addMeta($ps_tag_name, $ps_content) {			
			if (!is_array(MetaTagManager::$opa_tags)) { MetaTagManager::init(); }
			if (!$ps_tag_name) { return false; }
			
			MetaTagManager::$opa_tags['meta'][$ps_tag_name] = $ps_content;
			
			return true;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Add <link> tag to response.
		 *
		 * @param $ps_rel (string) - rel attribute of <link> tag
		 * @param $ps_href (string) - href attribute of <link> tag
		 * @return (bool) - Always return true
		 */
		static function addLink($ps_rel, $ps_href) {			
			if (!is_array(MetaTagManager::$opa_tags)) { MetaTagManager::init(); }
			if (!$ps_rel) { return false; }
			
			MetaTagManager::$opa_tags['link'][$ps_rel] = $ps_href;
			
			return true;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Clears all currently set tags
		 *
		 * @return void
		 */
		static function clearAll() {
			MetaTagManager::init();
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns <meta> and <link> tags
		 *
		 * @return (string) - HTML <meta> and <link> tags
		 */
		static function getHTML() {
			$vs_buf = '';
			if (!is_array(MetaTagManager::$opa_tags)) { MetaTagManager::init(); }
			if (sizeof(MetaTagManager::$opa_tags['meta'])) {	
				foreach(MetaTagManager::$opa_tags['meta'] as $vs_tag_name => $vs_content) {
					$vs_buf .= "<meta name='".htmlspecialchars($vs_tag_name, ENT_QUOTES)."' content='".htmlspecialchars($vs_content, ENT_QUOTES)."'/>\n";
				}
			}
			if (sizeof(MetaTagManager::$opa_tags['link'])) {	
				foreach(MetaTagManager::$opa_tags['link'] as $vs_rel => $vs_href) {
					$vs_buf .= "<link rel='".htmlspecialchars($vs_rel, ENT_QUOTES)."' href='".htmlspecialchars($vs_href, ENT_QUOTES)."'/>\n";
				}
			}
			return $vs_buf;
		}
		# --------------------------------------------------------------------------------
	}
?>