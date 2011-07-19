<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Media/MediaInfoCoder.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2010 Whirl-i-Gig
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
 * @subpackage Media
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
require_once(__CA_LIB_DIR__."/core/Media.php");
require_once(__CA_LIB_DIR__."/core/Media/MediaVolumes.php");
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");

$_MEDIA_INFO_CODER_INSTANCE_CACHE = null;

class MediaInfoCoder {
	# ---------------------------------------------------------------------------
	private $opo_volume_info;
	# ---------------------------------------------------------------------------
	static public function load() {
		global $_MEDIA_INFO_CODER_INSTANCE_CACHE;
		
		if (!$_MEDIA_INFO_CODER_INSTANCE_CACHE) {
			$_MEDIA_INFO_CODER_INSTANCE_CACHE = new MediaInfoCoder();
		}
		return $_MEDIA_INFO_CODER_INSTANCE_CACHE;
	}
	# ---------------------------------------------------------------------------
	public function __construct() {
		$this->opo_volume_info = new MediaVolumes();
	}
	# ---------------------------------------------------------------------------
	# Support for field types
	# ---------------------------------------------------------------------------
	public function getMediaArray($ps_data) {
		if (!is_array($ps_data)) {
			$va_data = caUnserializeForDatabase($ps_data);
			return is_array($va_data) ? $va_data : false;
		} else {
			return $ps_data;
		}
	}
	# ---------------------------------------------------------------------------
	public function getMediaInfo($ps_data, $ps_version=null, $ps_key=null) {
		if (!($va_media_info = $this->getMediaArray($ps_data))) {
			return false;
		}	
		
		if ($ps_version) {
			if (!$ps_key) {
				return $va_media_info[$ps_version];
			} else { 
				return $va_media_info[$ps_version][$ps_key];
			}
		} else {
			return $va_media_info;
		}
	}
	# ---------------------------------------------------------------------------
	public function getMediaPath($ps_data, $ps_version, $pa_options=null) {
		if (!($va_media_info = $this->getMediaArray($ps_data))) {
			return false;
		}
		
		$vn_page = 1;
		if (is_array($pa_options) && (isset($pa_options["page"])) && ($pa_options["page"] > 1)) {
			$vn_page = $pa_options["page"];
		}
		
		#
		# Is this version externally hosted?
		#
		if (isset($va_media_info[$ps_version]["EXTERNAL_URL"]) && ($va_media_info[$ps_version]["EXTERNAL_URL"])) {
			return '';		// no local path for externally hosted media
		}
		
		#
		# Is this version queued for processing?
		#
		if (isset($va_media_info[$ps_version]["QUEUED"]) && $va_media_info[$ps_version]["QUEUED"]) {
			if ($va_media_info[$ps_version]["QUEUED_ICON"]["filepath"]) {
				return $va_media_info[$ps_version]["QUEUED_ICON"]["filepath"];
			} else {
				return false;
			}
		}
		
		$va_volume_info = $this->opo_volume_info->getVolumeInformation($va_media_info[$ps_version]["VOLUME"]);
		if (!is_array($va_volume_info)) {
			return false;
		}
		if ($va_media_info[$ps_version]["FILENAME"]) {
			if (isset($va_media_info[$ps_version]["PAGES"]) && ($va_media_info[$ps_version]["PAGES"] > 1)) {
				if ($vn_page < 1) { $vn_page = 1; }
				if ($vn_page > $va_media_info[$ps_version]["PAGES"]) { $vn_page = 1; }
				return join("/",array($va_volume_info["absolutePath"], $va_media_info[$ps_version]["HASH"], $va_media_info[$ps_version]["MAGIC"]."_".$va_media_info[$ps_version]["FILESTEM"]."_".$vn_page.".".$va_media_info[$ps_version]["EXTENSION"]));
			} else {
				return join("/",array($va_volume_info["absolutePath"], $va_media_info[$ps_version]["HASH"], $va_media_info[$ps_version]["MAGIC"]."_".$va_media_info[$ps_version]["FILENAME"]));
			}
		} else {
			return false;
		}
	}
	# ---------------------------------------------------------------------------
	public function getMediaUrl($ps_data, $ps_version, $pa_options=null) {
		if (!($va_media_info = $this->getMediaArray($ps_data))) {
			return false;
		}
		
		$vn_page = 1;
		if (is_array($pa_options) && (isset($pa_options["page"])) && ($pa_options["page"] > 1)) {
			$vn_page = $pa_options["page"];
		}
		
		#
		# Is this version externally hosted?
		#
		if (isset($va_media_info[$ps_version]["EXTERNAL_URL"]) && ($va_media_info[$ps_version]["EXTERNAL_URL"])) {
			return $va_media_info[$ps_version]["EXTERNAL_URL"];
		}
		
		#
		# Is this version queued for processing?
		#
		if (isset($va_media_info[$ps_version]["QUEUED"]) && ($va_media_info[$ps_version]["QUEUED"])) {
			if ($va_media_info[$ps_version]["QUEUED_ICON"]["src"]) {
				return $va_media_info[$ps_version]["QUEUED_ICON"]["src"];
			} else {
				return false;
			}
		}
		
		$va_volume_info = $this->opo_volume_info->getVolumeInformation($va_media_info[$ps_version]["VOLUME"]);
		if (!is_array($va_volume_info)) {
			return false;
		}
		
		# is this mirrored?
		if (isset($va_volume_info["accessUsingMirror"]) && ($va_volume_info["accessUsingMirror"]) && ($va_media_info["MIRROR_STATUS"][$va_volume_info["accessUsingMirror"]] == "SUCCESS")) {
			$vs_protocol = 	$va_volume_info["mirrors"][$va_volume_info["accessUsingMirror"]]["accessProtocol"];
			$vs_host = 		$va_volume_info["mirrors"][$va_volume_info["accessUsingMirror"]]["accessHostname"];
			$vs_url_path = 	$va_volume_info["mirrors"][$va_volume_info["accessUsingMirror"]]["accessUrlPath"];  		
		} else {
			$vs_protocol = 	$va_volume_info["protocol"];
			$vs_host = 		$va_volume_info["hostname"];
			$vs_url_path = 	$va_volume_info["urlPath"];
		}
		
		if ($va_media_info[$ps_version]["FILENAME"]) {
			if (isset($va_media_info[$ps_version]["PAGES"]) && ($va_media_info[$ps_version]["PAGES"] > 1)) {
				if ($vn_page < 1) { $vn_page = 1; }
				if ($vn_page > $va_media_info[$ps_version]["PAGES"]) { $vn_page = 1; }
				$vs_fpath = join("/",array($vs_url_path, $va_media_info[$ps_version]["HASH"], $va_media_info[$ps_version]["MAGIC"]."_".$va_media_info[$ps_version]["FILESTEM"]."_".$vn_page.".".$va_media_info[$ps_version]["EXTENSION"]));
			} else {
				$vs_fpath = join("/",array($vs_url_path, $va_media_info[$ps_version]["HASH"], $va_media_info[$ps_version]["MAGIC"]."_".$va_media_info[$ps_version]["FILENAME"]));
			}
			return $vs_protocol."://$vs_host".$vs_fpath;
		} else {
			return false;
		}
	}
	# ---------------------------------------------------------------------------
	public function getMediaTag($ps_data, $ps_version, $pa_options=null) {
		if (!($va_media_info = $this->getMediaArray($ps_data))) {
			return false;
		}
		
		if (!is_array($pa_options)) {
			$pa_options = array();
		}
		if (!isset($pa_options["page"]) || ($pa_options["page"] < 1)) {
			$pa_options["page"] = 1;
		}
		
		#
		# Is this version queued for processing?
		#
		if (isset($va_media_info[$ps_version]["QUEUED"]) && ($va_media_info[$ps_version]["QUEUED"])) {
			if ($va_media_info[$ps_version]["QUEUED_ICON"]["src"]) {
				return "<img src='".$va_media_info[$ps_version]["QUEUED_ICON"]["src"]."' width='".$va_media_info[$ps_version]["QUEUED_ICON"]["width"]."' height='".$va_media_info[$ps_version]["QUEUED_ICON"]["height"]."' alt='".$va_media_info[$ps_version]["QUEUED_ICON"]["alt"]."'>";
			} else {
				return $va_media_info[$ps_version]["QUEUED_MESSAGE"];
			}
		}
		
		$vs_url = $this->getMediaUrl($va_media_info, $ps_version, $pa_options["page"]);
		$o_media = new Media();
		
		$o_vol = new MediaVolumes();
		$va_volume = $o_vol->getVolumeInformation($va_media_info[$ps_version]['VOLUME']);
		
		return $o_media->htmlTag($va_media_info[$ps_version]["MIMETYPE"], $vs_url, $va_media_info[$ps_version]["PROPERTIES"], $pa_options, $va_volume);
	}
	# ---------------------------------------------------------------------------
	public function getMediaVersions($ps_data) {
		if (!is_array($va_media_info)) {
			if (!($va_media_info = $this->getMediaArray($ps_data))) {
				return false;
			}
		}
		
		unset($va_media_info["ORIGINAL_FILENAME"]);
		unset($va_media_info["INPUT"]);
		unset($va_media_info["VOLUME"]);
		
		return array_keys($va_media_info);		
	}
	# ---------------------------------------------------------------------------
	public function hasMedia($ps_data, $ps_field) {  
		if (!($va_media_info = $this->getMediaArray($ps_data))) {
			return false;
		}
		if (is_array($va_media_info)) {
			return true;
		} else {
			return false;
		}
	}
	# ---------------------------------------------------------------------------
	public function mediaIsMirrored($ps_data, $ps_version) {
		if (!($va_media_info = $this->getMediaArray($ps_data))) {
			return false;
		}
		
		$va_volume_info = $this->opo_volume_info->getVolumeInformation($va_media_info[$ps_version]["VOLUME"]);
		if (!is_array($va_volume_info)) {
			return false;
		}
		if (is_array($va_volume_info["mirrors"])) {
			return sizeof($va_volume_info["mirrors"]);
		} else {
			return false;
		}
	}
	# --------------------------------------------------------------------------------
	public function getMediaMirrorStatus($ps_data, $ps_version, $ps_mirror="") {
		if (!($va_media_info = $this->getMediaArray($ps_data))) {
			return false;
		}
		
		$va_volume_info = $this->opo_volume_info->getVolumeInformation($va_media_info[$ps_version]["VOLUME"]);
		if (!is_array($va_volume_info)) {
			return false;
		}
		if ($ps_mirror) {
			return $va_media_info["MIRROR_STATUS"][$ps_mirror];
		} else {
			return $va_media_info["MIRROR_STATUS"][$va_volume_info["accessUsingMirror"]];
		}
	}
	# ---------------------------------------------------------------------------
}
?>