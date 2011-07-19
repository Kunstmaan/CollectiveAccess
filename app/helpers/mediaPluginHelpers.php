<?php
/** ---------------------------------------------------------------------
 * app/helpers/mediaPluginHelpers.php : miscellaneous functions
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
  /**
   *
   */
   
 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');

	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if CoreImageTool executable is available at specified path
	 * 
	 * @param $ps_path_to_coreimage - full path to CoreImageTool including executable name
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginCoreImageInstalled($ps_path_to_coreimage) {
		global $_MEDIAHELPER_PLUGIN_CACHE_COREIMAGE;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_COREIMAGE[$ps_path_to_coreimage])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_COREIMAGE[$ps_path_to_coreimage];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_COREIMAGE = array();
		}
		if (!$ps_path_to_coreimage || (preg_match("/[^\/A-Za-z0-9]+/", $ps_path_to_coreimage)) || !file_exists($ps_path_to_coreimage)) { return false; }
		
		exec($ps_path_to_coreimage.' 2> /dev/null', $va_output, $vn_return);
		if (($vn_return >= 0) && ($vn_return < 127)) {
			return $_MEDIAHELPER_PLUGIN_CACHE_COREIMAGE[$ps_path_to_coreimage] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_COREIMAGE[$ps_path_to_coreimage] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if ImageMagick executables is available within specified directory path
	 * 
	 * @param $ps_imagemagick_path - path to directory containing ImageMagick executables
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginImageMagickInstalled($ps_imagemagick_path) {
		global $_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK[$ps_imagemagick_path])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK[$ps_imagemagick_path];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK = array();
		}
		if (!$ps_imagemagick_path || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_imagemagick_path)) || !file_exists($ps_imagemagick_path) || !is_dir($ps_imagemagick_path)) { return false; }
		
		exec($ps_imagemagick_path.'/identify 2> /dev/null', $va_output, $vn_return);
		if (($vn_return >= 0) && ($vn_return < 127)) {
			return $_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK[$ps_imagemagick_path] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK[$ps_imagemagick_path] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if dcraw executable is available at specified path
	 * 
	 * @param $ps_path_to_dcraw - full path to dcraw including executable name
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginDcrawInstalled($ps_path_to_dcraw) {
		global $_MEDIAHELPER_PLUGIN_CACHE_DCRAW;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_DCRAW[$ps_path_to_dcraw])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_DCRAW[$ps_path_to_dcraw];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_DCRAW = array();
		}
		if (!$ps_path_to_dcraw || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_path_to_dcraw)) || !file_exists($ps_path_to_dcraw)) { return false; }
		
		exec($ps_path_to_dcraw.' -i 2> /dev/null', $va_output, $vn_return);
		if (($vn_return >= 0) && ($vn_return < 127)) {
			return $_MEDIAHELPER_PLUGIN_CACHE_DCRAW[$ps_path_to_dcraw] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_DCRAW[$ps_path_to_dcraw] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if ffmpeg executable is available at specified path
	 * 
	 * @param $ps_path_to_ffmpeg - full path to ffmpeg including executable name
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginFFfmpegInstalled($ps_path_to_ffmpeg) {
		global $_MEDIAHELPER_PLUGIN_CACHE_FFMPEG;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_FFMPEG[$ps_path_to_ffmpeg])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_FFMPEG[$ps_path_to_ffmpeg];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_FFMPEG = array();
		}
		if (!$ps_path_to_ffmpeg || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_path_to_ffmpeg)) || !file_exists($ps_path_to_ffmpeg)) { return false; }

		exec($ps_path_to_ffmpeg.'> /dev/null 2>&1', $va_output, $vn_return);
		if (($vn_return >= 0) && ($vn_return < 127)) {
			return $_MEDIAHELPER_PLUGIN_CACHE_FFMPEG[$ps_path_to_ffmpeg] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_FFMPEG[$ps_path_to_ffmpeg] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if Ghostscript (gs) executable is available at specified path
	 * 
	 * @param $ps_path_to_ghostscript - full path to Ghostscript including executable name
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginGhostscriptInstalled($ps_path_to_ghostscript) {
		global $_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT[$ps_path_to_ghostscript])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT[$ps_path_to_ghostscript];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT = array();
		}
		if (!trim($ps_path_to_ghostscript) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_path_to_ghostscript)) || !file_exists($ps_path_to_ghostscript)) { return false; }
		exec($ps_path_to_ghostscript." -v 2> /dev/null", $va_output, $vn_return);
		if (($vn_return >= 0) && ($vn_return < 127)) {
			return $_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT[$ps_path_to_ghostscript] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT[$ps_path_to_ghostscript] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if PdfToText executable is available at specified path
	 * 
	 * @param $ps_path_to_pdf_to_text - full path to PdfToText including executable name
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginPdftotextInstalled($ps_path_to_pdf_to_text) {
		if (!trim($ps_path_to_pdf_to_text) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_path_to_pdf_to_text))  || !file_exists($ps_path_to_pdf_to_text)) { return false; }
		exec($ps_path_to_pdf_to_text." -v 2> /dev/null", $va_output, $vn_return);
		if (($vn_return >= 0) && ($vn_return < 127)) {
			return true;
		}
		return false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if AbiWord executable is available at specified path
	 * 
	 * @param $ps_path_to_abiword - full path to AbiWord including executable name
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginAbiwordInstalled($ps_path_to_abiword) {
		if (!trim($ps_path_to_abiword) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_path_to_abiword)) || !file_exists($ps_path_to_abiword)) { return false; }
		exec($ps_path_to_abiword." --version 2> /dev/null", $va_output, $vn_return);
		if (($vn_return >= 0) && ($vn_return < 127)) {
			return true;
		}
		return false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if Imagick PHP extension is available
	 * 
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginImagickInstalled() {
		$o_config = Configuration::load();
		if ($o_config->get('dont_use_imagick')) { return false; }
		return class_exists('Imagick') ? true : false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if MagickWand PHP extension is available
	 * 
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginMagickWandInstalled() {
		return function_exists('MagickReadImage') ? true : false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if GD PHP extension is available. Return false if GD is installed but lacks JPEG support unless "don't worry about JPEGs" parameter is set to true.
	 *
	 * @param boolean $pb_dont_worry_about_jpegs If set will return true if GD is installed without JPEG support; default is to consider JPEG-less GD worthless.
	 * 
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginGDInstalled($pb_dont_worry_about_jpegs=false) {
		if ($pb_dont_worry_about_jpegs) {
			return function_exists('imagecreatefromgif') ? true : false;
		} else {
			return function_exists('imagecreatefromjpeg') ? true : false;
		}
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if mediainfo is installed in the given path.
	 * @param string $ps_mediainfo_path path to mediainfo
	 */
	function caMediaInfoInstalled($ps_mediainfo_path) {
		global $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_mediainfo_path])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_mediainfo_path];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO = array();
		}
		if (!trim($ps_mediainfo_path) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_mediainfo_path)) || !file_exists($ps_mediainfo_path)) { return false; }
		exec($ps_mediainfo_path." --Help > /dev/null",$va_output,$vn_return);
		if($vn_return == 255) {
			return $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_mediainfo_path] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_mediainfo_path] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Extracts media metadata using "mediainfo"
	 * @param string $ps_mediainfo_path path to mediainfo binary
	 * @param string $ps_filepath file path
	 */
	function caExtractMetadataWithMediaInfo($ps_mediainfo_path,$ps_filepath){
		if (!trim($ps_mediainfo_path) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_mediainfo_path)) || !file_exists($ps_mediainfo_path)) { return false; }
		exec($ps_mediainfo_path." ".$ps_filepath,$va_output,$vn_return);
		$vs_cat = "GENERIC";
		$va_return = array();
		foreach($va_output as $vs_line){
			$va_split = explode(":",$vs_line);
			$vs_left = trim($va_split[0]);
			$vs_right = trim($va_split[1]);
			if(strlen($vs_right)==0){ // category line
				$vs_cat = strtoupper($vs_left);
				continue;
			}
			if(strlen($vs_left) && strlen($vs_right)) {
				if($vs_left!="Complete name"){ // we probably don't want to display temporary filenames
					$va_return["METADATA_".$vs_cat][$vs_left] = $vs_right;
				}
			}
		}

		return $va_return;
	}
	# ------------------------------------------------------------------------------------------------
?>
