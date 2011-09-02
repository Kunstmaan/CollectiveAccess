<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/GD.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2011 Whirl-i-Gig
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
 
/** 
 * Plugin for processing images using GD
 */

include_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/core/Parsers/TilepicParser.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");

class WLPlugMediaGD Extends WLPlug Implements IWLPlugMedia {
	var $errors = array();
	
	var $filepath;
	var $handle;
	var $ohandle;
	var $properties;
	var $metadata = array();
	
	var $opo_config;
	var $opo_external_app_config;
	var $ops_imagemagick_path;
	
	var $info = array(
		"IMPORT" => array(
			"image/jpeg" 		=> "jpg",
			"image/gif" 		=> "gif",
			"image/png" 		=> "png",
			"image/tilepic" 	=> "tpc"
		),
		"EXPORT" => array(
			"image/jpeg" 		=> "jpg",
			"image/gif" 		=> "gif",
			"image/png" 		=> "png",
			"image/tilepic" 	=> "tpc"
		),
		"TRANSFORMATIONS" => array(
			"SCALE" 			=> array("width", "height", "mode", "antialiasing", "trim_edges", "crop_from"),					// trim_edges and crop_from are dummy and not supported by GD; they are supported by the ImageMagick-based plugins
			"ANNOTATE"	=> array("text", "font", "size", "color", "position", "inset"),	// dummy
			"WATERMARK"	=> array("image", "width", "height", "position", "opacity"),	// dummy
			"ROTATE" 			=> array("angle"),
			"SET" 				=> array("property", "value"),
			"DENSITY"			=> array("ppi", "mode"), // dummy
			
			# --- filters
			"MEDIAN"			=> array("radius"),
			"DESPECKLE"			=> array(""),
			"SHARPEN"			=> array("radius", "sigma"),
			"UNSHARPEN_MASK"	=> array("radius", "sigma", "amount", "threshold"),
			"MAX_SCALE" 			=> array("width", "height"),
		),
		"PROPERTIES" => array(
			"width" 			=> 'R',
			"height" 			=> 'R',
			"mimetype" 			=> 'R',
			"typename" 			=> 'R',
			'tiles'				=> 'R',
			'layers'			=> 'W',
			"quality" 			=> 'W',
			'tile_width'		=> 'W',
			'tile_height'		=> 'W',
			'antialiasing'		=> 'W',
			'layer_ratio'		=> 'W',
			'tile_mimetype'		=> 'W',
			'output_layer'		=> 'W',
			'version'			=> 'W'	// required of all plug-ins
		),
		"NAME" => "GD"
	);
	
	var $typenames = array(
		"image/jpeg" 		=> "JPEG",
		"image/gif" 		=> "GIF",
		"image/png" 		=> "PNG",
		"image/tilepic" 	=> "Tilepic"
	);
	
	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Provides limited image processing and conversion services using libGD');
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		$this->opo_config = Configuration::load();
		$vs_external_app_config_path = $this->opo_config->get('external_applications');
		$this->opo_external_app_config = Configuration::load($vs_external_app_config_path);
		$this->ops_imagemagick_path = $this->opo_external_app_config->get('imagemagick_path');
		$this->ops_CoreImage_path = $this->opo_external_app_config->get('coreimagetool_app');
		
		if (caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
			return null;	// don't use if CoreImage executable are available
		}
		if (caMediaPluginImagickInstalled()) {	
			return null;	// don't use GD if Imagick is available
		} 
		if (caMediaPluginMagickWandInstalled()) {	
			return null;	// don't use GD if MagickWand is available
		} 
		if (caMediaPluginImageMagickInstalled($this->ops_imagemagick_path)) {
			return null;	// don't use if ImageMagick executables are available
		}
		if (!caMediaPluginGDInstalled()) {
			return null;	// don't use if GD functions are not available
		}

		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	public function checkStatus() {
		$va_status = parent::checkStatus();
		
		if ($this->register()) {
			$va_status['available'] = true;
		} else {
			if (caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
				$va_status['unused'] = true;
				$va_status['warnings'][] = _t("Didn't load because CoreImageTool is available and preferred");
			} 
			if (caMediaPluginImagickInstalled()) {	
				$va_status['unused'] = true;
				$va_status['warnings'][] = _t("Didn't load because Imagick/ImageMagick is available and preferred");
			} 
			if (caMediaPluginMagickWandInstalled()) {	
				$va_status['unused'] = true;
				$va_status['warnings'][] = _t("Didn't load because Imagick/MagickWand is available and preferred");
			} 
			if (caMediaPluginImageMagickInstalled($this->ops_imagemagick_path)) {
				$va_status['unused'] = true;
				$va_status['warnings'][] = _t("Didn't load because ImageMagick (command-line) is available and preferred");
			}
			if (!caMediaPluginGDInstalled()) {
				$va_status['errors'][] = _t("Didn't load because your PHP install lacks GD support");
			}
		}
		
		return $va_status;
	}
	# ------------------------------------------------
	public function divineFileFormat($filepath) {
		if($va_info = getimagesize($filepath)) {
			switch($va_info[2]) {
				case IMAGETYPE_GIF:
					return "image/gif";
					break;
				case IMAGETYPE_JPEG:
					return "image/jpeg";
					break;
				case IMAGETYPE_PNG:
					return "image/png";
					break;
			}
			return '';
		} else {
			$tp = new TilepicParser();
			$tp->useLibrary(LIBRARY_GD);
			if ($tp->isTilepic($filepath)) {
				return "image/tilepic";
			} else {
				# file format is not supported by this plug-in
				return '';
			}
		}
	}
	# ----------------------------------------------------------
	public function get($property) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property]) {
				return $this->properties[$property];
			} else {
				//print "Invalid property";
				return '';
			}
		} else {
			return '';
		}
	}
	# ----------------------------------------------------------
	public function set($property, $value) {
		if ($this->handle) {
			if ($property == "tile_size") {
				if (($value < 10) || ($value > 10000)) {
					$this->postError(1650, _t("Tile size property must be between 10 and 10000"), "WLPlugGD->set()");
					return '';
				}
				$this->properties["tile_width"] = $value;
				$this->properties["tile_height"] = $value;
			} else {
				if ($this->info["PROPERTIES"][$property]) {
					switch($property) {
						case 'quality':
							if (($value < 1) || ($value > 100)) {
								$this->postError(1650, _t("Quality property must be between 1 and 100"), "WLPlugGD->set()");
								return '';
							}
							$this->properties["quality"] = $value;
							break;
						case 'tile_width':
							if (($value < 10) || ($value > 10000)) {
								$this->postError(1650, _t("Tile width property must be between 10 and 10000"), "WLPlugGD->set()");
								return '';
							}
							$this->properties["tile_width"] = $value;
							break;
						case 'tile_height':
							if (($value < 10) || ($value > 10000)) {
								$this->postError(1650, _t("Tile height property must be between 10 and 10000"), "WLPlugGD->set()");
								return '';
							}
							$this->properties["tile_height"] = $value;
							break;
						case 'antialiasing':
							if (($value < 0) || ($value > 100)) {
								$this->postError(1650, _t("Antialiasing property must be between 0 and 100"), "WLPlugGD->set()");
								return '';
							}
							$this->properties["antialiasing"] = $value;
							break;
						case 'layer_ratio':
							if (($value < 0.1) || ($value > 10)) {
								$this->postError(1650, _t("Layer ratio property must be between 0.1 and 10"), "WLPlugGD->set()");
								return '';
							}
							$this->properties["layer_ratio"] = $value;
							break;
						case 'layers':
							if (($value < 1) || ($value > 25)) {
								$this->postError(1650, _t("Layer property must be between 1 and 25"), "WLPlugGD->set()");
								return '';
							}
							$this->properties["layers"] = $value;
							break;	
						case 'tile_mimetype':
							if ((!($this->info["EXPORT"][$value])) && ($value != "image/tilepic")) {
								$this->postError(1650, _t("Tile output type '%1' is invalid", $value), "WLPlugGD->set()");
								return '';
							}
							$this->properties["tile_mimetype"] = $value;
							break;
						case 'output_layer':
							$this->properties["output_layer"] = $value;
							break;
						default:
							if ($this->info["PROPERTIES"][$property] == 'W') {
								$this->properties[$property] = $value;
							} else {
								# read only
								return '';
							}
							break;
					}
				} else {
					# invalid property
					$this->postError(1650, _t("Can't set property %1", $property), "WLPlugGD->set()");
					return '';
				}
			}
		} else {
			return '';
		}
		return true;
	}
	# ------------------------------------------------
	/**
	 * Returns text content for indexing, or empty string if plugin doesn't support text extraction
	 *
	 * @return String Extracted text
	 */
	public function getExtractedText() {
		return '';
	}
	# ------------------------------------------------
	/**
	 * Returns array of extracted metadata, key'ed by metadata type or empty array if plugin doesn't support metadata extraction
	 *
	 * @return Array Extracted metadata
	 */
	public function getExtractedMetadata() {
		return $this->metadata;
	}
	# ----------------------------------------------------------
	public function read($filepath, $mimetype="") {
		if ($mimetype == 'image/tilepic') {
			#
			# Read in Tilepic format image
			#
			$this->handle = new TilepicParser($filepath);
			$tp->useLibrary(LIBRARY_GD);
			if (!$this->handle->error) {
				$this->filepath = $filepath;
				foreach($this->handle->properties as $k => $v) {
					if (isset($this->properties[$k])) {
						$this->properties[$k] = $v;
					}
				}
				$this->properties["mimetype"] = "image/tilepic";
				$this->properties["typename"] = "Tilepic";
				return true;
			} else {
				postError(1610, $this->handle->error, "WLPlugGD->read()");
				return false;
			}
		} else {
			$this->handle = "";
			$this->filepath = "";
			$this->metadata = array();
			
			$va_info = getimagesize($filepath);
			switch($va_info[2]) {
				case IMAGETYPE_GIF:
					$this->handle = imagecreatefromgif($filepath);
					$vs_mimetype = "image/gif";
					$vs_typename = "GIF";
					break;
				case IMAGETYPE_JPEG:
					$this->handle = imagecreatefromjpeg($filepath);
					$vs_mimetype = "image/jpeg";
					$vs_typename = "JPEG";
					
					if(function_exists('exif_read_data')) {
						$this->metadata["METADATA_EXIF"] = exif_read_data($filepath);
					}
					break;
				case IMAGETYPE_PNG:
					$this->handle = imagecreatefrompng($filepath);
					$vs_mimetype = "image/png";
					$vs_typename = "PNG";
					break;
				default:
					return false;
					break;
			}
			
			if ($this->handle) {
				$this->filepath = $filepath;
				
				# load image properties
				$this->properties["width"] = $va_info[0];
				$this->properties["height"] = $va_info[1];
				$this->properties["quality"] = "";
				$this->properties["mimetype"] = $vs_mimetype;
				$this->properties["typename"] = $vs_typename;
				
				return true;
			} else {
				# plug-in can't handle format
				return false;
			}
		}
	}
	# ----------------------------------------------------------
	public function transform($operation, $parameters) {
		if ($this->properties["mimetype"] == "image/tilepic") { return false;} # no transformations for Tilepic
		if (!$this->handle) { return false; }
		
		if (!($this->info["TRANSFORMATIONS"][$operation])) {
			# invalid transformation
			postError(1655, _t("Invalid transformation %1", $operation), "WLPlugGD->transform()");
			return false;
		}
		
		# get parameters for this operation
		$sparams = $this->info["TRANSFORMATIONS"][$operation];
		
		$w = $parameters["width"];
		$h = $parameters["height"];
		$cw = $this->get("width");
		$ch = $this->get("height");
		$do_crop = 0;
		switch($operation) {
			# -----------------------
			case 'SCALE':
				$aa = $parameters["antialiasing"];
				if ($aa <= 0) { $aa = 0; }
				switch($parameters["mode"]) {
					# ----------------
					case "width":
						$scale_factor = $w/$cw;
						$h = $ch * $scale_factor;
						break;
					# ----------------
					case "height":
						$scale_factor = $h/$ch;
						$w = $cw * $scale_factor;
						break;
					# ----------------
					case "bounding_box":
						$scale_factor_w = $w/$cw;
						$scale_factor_h = $h/$ch;
						$w = $cw * (($scale_factor_w < $scale_factor_h) ? $scale_factor_w : $scale_factor_h); 
						$h = $ch * (($scale_factor_w < $scale_factor_h) ? $scale_factor_w : $scale_factor_h);	
						break;
					# ----------------
					case "fill_box":
						$scale_factor_w = $w/$cw;
						$scale_factor_h = $h/$ch;
						$w = $cw * (($scale_factor_w > $scale_factor_h) ? $scale_factor_w : $scale_factor_h); 
						$h = $ch * (($scale_factor_w > $scale_factor_h) ? $scale_factor_w : $scale_factor_h);	
						
						$do_crop = 1;
						break;
					# ----------------
				}
		
				$w = round($w);
				$h = round($h);
				if ($w > 0 && $h > 0) {
					$r_new_img = imagecreatetruecolor($w, $h);
					if (!imagecopyresampled($r_new_img, $this->handle, 0, 0, 0, 0, $w, $h, $cw, $ch)) {
						$this->postError(1610, _t("Couldn't resize image"), "WLPlugGD->transform()");
						return false;
					}
					imagedestroy($this->handle);
					$this->handle = $r_new_img;
					if ($do_crop) {
						$r_new_img = imagecreatetruecolor($parameters["width"], $parameters["height"]);
						imagecopy($r_new_img, $this->handle,0,0,0,0,$parameters["width"], $parameters["height"]);
						imagedestroy($this->handle);
						$this->handle = $r_new_img;
						$this->properties["width"] = $parameters["width"];
						$this->properties["height"] = $parameters["height"];
					} else {
						$this->properties["width"] = $w;
						$this->properties["height"] = $h;
					}
			}
			break;
		# -----------------------
		case "ROTATE":
			$angle = $parameters["angle"];
			if (($angle > -360) && ($angle < 360)) {
				if ( !($r_new_img = imagerotate($this->handle, $angle, 0 )) ){
					postError(1610, _t("Couldn't rotate image"), "WLPlugGD->transform()");
					return false;
				}
				imagedestroy($this->handle);
				$this->handle = $r_new_img;
			}
			break;
		# -----------------------
		case "DESPECKLE":
			# noop
			break;
		# -----------------------
		case "MEDIAN":
			# noop
			break;
		# -----------------------
		case "SHARPEN":
			# noop
			break;
		# -----------------------
		case "UNSHARP_MASK":
			# noop
			break;
		# -----------------------
		case "SET":
			while(list($k, $v) = each($parameters)) {
				$this->set($k, $v);
			}
			break;
		# -----------------------
		case "MAX_SCALE":
			if($cw < $w && $ch < $h){
				$r_new_img = imagecreatetruecolor($cw, $ch);
				if (!imagecopyresampled($r_new_img, $this->handle, 0, 0, 0, 0, $cw, $ch, $cw, $ch)) {
						$this->postError(1610, _t("Couldn't resize image"), "WLPlugGD->transform()");
						return false;
				}
				imagedestroy($this->handle);
				$this->handle = $r_new_img;
				$this->properties["width"] = $cw;
				$this->properties["height"] = $ch;
			}else{
				$aspect_ratio= $cw/$ch;
				$screen_ratio= $w/$h;

				if($aspect_ratio < $screen_ratio){
					$w=$aspect_ratio*$h;
				}else{
					$h=$w/$aspect_ratio;
				}

				$w = round($w);
				$h = round($h);

				$r_new_img = imagecreatetruecolor($w, $h);
				if (!imagecopyresampled($r_new_img, $this->handle, 0, 0, 0, 0, $w, $h, $cw, $ch)) {
						$this->postError(1610, _t("Couldn't resize image"), "WLPlugGD->transform()");
						return false;
				}
				imagedestroy($this->handle);
				$this->handle = $r_new_img;
				$this->properties["width"] = $w;
				$this->properties["height"] = $h;

			}

			break;
		# -----------------------
		}
		return true;
	}
	# ----------------------------------------------------------
	public function write($filepath, $mimetype) {
		if (!$this->handle) { return false; }
		
		if ($mimetype == "image/tilepic") {
			if ($this->properties["mimetype"] == "image/tilepic") {
				copy($this->filepath, $filepath);
			} else {
				$tp = new TilepicParser();
				$tp->useLibrary(LIBRARY_GD);
				if (!($properties = $tp->encode($this->filepath, $filepath, 
					array(
						"tile_width" => $this->properties["tile_width"],
						"tile_height" => $this->properties["tile_height"],
						"layer_ratio" => $this->properties["layer_ratio"],
						"quality" => $this->properties["quality"],
						"antialiasing" => $this->properties["antialiasing"],
						"output_mimetype" => $this->properties["tile_mimetype"],
						"layers" => $this->properties["layers"],
					)					
				))) {
					$this->postError(1610, $this->handle->error, "WLPlugTilepic->write()");	
					return false;
				}
			}
			# update mimetype
			foreach($properties as $k => $v) {
				$this->properties[$k] = $v;
			}
			$this->properties["mimetype"] = "image/tilepic";
			$this->properties["typename"] = "Tilepic";
			return true;
		} else {
			# is mimetype valid?
			if (!($ext = $this->info["EXPORT"][$mimetype])) {
				# this plugin can't write this mimetype
				return false;
			} 
			
			# get layer out of Tilepic
			if ($this->properties["mimetype"] == "image/tilepic") {
				if (!($h = $this->handle->getLayer($this->properties["output_layer"] ? $this->properties["output_layer"] : intval($this->properties["layers"]/2.0), $mimetype))) {
					$this->postError(1610, $this->handle->error, "WLPlugTilepic->write()");	
					return false;
				}
				$this->handle = $h;
			}
			
			$vn_res = 0;
			switch($mimetype) {
				case 'image/gif':
					$vn_res = imagegif($this->handle, $filepath.".".$ext);
					$vs_typename = "GIF";
					break;
				case 'image/jpeg':
					$vn_res = imagejpeg($this->handle, $filepath.".".$ext, $this->properties["quality"] ? $this->properties["quality"] : null);
					$vs_typename = "JPEG";
					break;
				case 'image/png':
					$vn_res = imagepng($this->handle, $filepath.".".$ext);
					$vs_typename = "PNG";
					break;
			}
			
			# write the file
			if (!$vn_res) {
				# error
				$this->postError(1610, _t("Couldn't write image"), "WLPlugGD->write()");
				return false;
			}
			
			# update mimetype
			$this->properties["mimetype"] = $mimetype;
			$this->properties["typename"] = $vs_typename;
			
			return true;
		}
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	# This method must be implemented for plug-ins that can output preview frames for videos or pages for documents
	public function &writePreviews($ps_filepath, $pa_options) {
		return null;
	}
	# ------------------------------------------------
	public function getOutputFormats() {
		return $this->info["EXPORT"];
	}
	# ------------------------------------------------
	public function getTransformations() {
		return $this->info["TRANSFORMATIONS"];
	}
	# ------------------------------------------------
	public function getProperties() {
		return $this->info["PROPERTIES"];
	}
	# ------------------------------------------------
	public function mimetype2extension($mimetype) {
		return $this->info["EXPORT"][$mimetype];
	}
	# ------------------------------------------------
	public function extension2mimetype($extension) {
		reset($this->info["EXPORT"]);
		while(list($k, $v) = each($this->info["EXPORT"])) {
			if ($v === $extension) {
				return $k;
			}
		}
		return '';
	}
	# ------------------------------------------------
	public function mimetype2typename($mimetype) {
		return $this->typenames[$mimetype];
	}
	# ------------------------------------------------
	public function reset() {
		$this->read($this->filepath);
		return true;
	}
	# ------------------------------------------------
	public function init() {
		unset($this->handle);
		unset($this->properties);
		unset($this->filepath);
		
		$this->metadata = array();
		$this->errors = array();
	}
	# ------------------------------------------------
	public function cleanup() {
		$this->destruct();
	}
	# ------------------------------------------------
	public function destruct() {
		if (is_resource($this->handle)) { imagedestroy($this->handle); };
	}
	# ------------------------------------------------
	public function htmlTag($url, $properties, $options=null, $pa_volume_info=null) {
		if (!is_array($options)) { $options = array(); }
		
		foreach(array(
			'name', 'url', 'viewer_width', 'viewer_height', 'idname',
			'viewer_base_url', 'width', 'height',
			'vspace', 'hspace', 'alt', 'title', 'usemap', 'align', 'border', 'class', 'style',
			
			'tilepic_init_magnification', 'tilepic_use_labels', 'tilepic_edit_labels', 'tilepic_parameter_list',
			'tilepic_app_parameters', 'directly_embed_flash', 'tilepic_label_processor_url', 'tilepic_label_typecode',
			'tilepic_label_default_title', 'tilepic_label_title_readonly'
		) as $vs_k) {
			if (!isset($options[$vs_k])) { $options[$vs_k] = null; }
		}
		
		if(preg_match("/\.tpc\$/", $url)) {
			#
			# Tilepic
			#
			if (!isset($properties["width"])) $properties["width"] = 100;
			if (!isset($properties["height"])) $properties["height"] = 100;
			
			$width = $properties["width"];
			$height = $properties["height"];
			
			$tile_width = $properties["tile_width"];
			$tile_height = $properties["tile_height"];
			
			$layers = $properties["layers"];
			$ratio = $properties["layer_ratio"];
			
			$idname = $options["idname"];
			if (!$idname) { $idname = "bischen"; }
			
			$sx = intval($width/2.0); 
			$sy = intval(0 - ($height/2.0)); 
			
			$init_magnification = 		$options["tilepic_init_magnification"];
			
			$use_labels = 				$options["tilepic_use_labels"];
			$edit_labels = 				$options["tilepic_edit_labels"];
			$parameter_list = 			$options["tilepic_parameter_list"];
			$vs_app_parameters = 		$options["tilepic_app_parameters"];
			
			$viewer_width = 			$options["viewer_width"];
			$viewer_height = 			$options["viewer_height"];
			
			$viewer_base_url =			$options["viewer_base_url"];
			$directly_embed_flash = 	$options['directly_embed_flash'];
			
			if(!$viewer_label_processor_url = $options["tilepic_label_processor_url"]) {
				$viewer_label_processor_url = $viewer_base_url."/viewers/apps/labels.php";
			}
			
			$vn_label_typecode = intval($options["tilepic_label_typecode"]);
			
			$vs_label_title = $options["tilepic_label_default_title"];
			$vn_label_title_readonly = $options["tilepic_label_title_readonly"] ? 1 : 0;
			
			if (!$viewer_width || !$viewer_height) {
				$viewer_width = $this->opo_config->get("tilepic_viewer_width");
				if (!$viewer_width) { $viewer_width = 500; }
				$viewer_height = $this->opo_config->get("tilepic_viewer_height");
				if (!$viewer_height) { $viewer_height = 500; }
			}
			
			$vs_flash_vars = "tpViewerUrl=$viewer_base_url/viewers/apps/tilepic.php&tpLabelProcessorURL=$viewer_label_processor_url&tpImageUrl=$url&tpWidth=$width&tpHeight=$height&tpInitMagnification=$init_magnification&tpScales=$layers&tpRatio=$ratio&tpTileWidth=$tile_width&tpTileHeight=$tile_height&tpUseLabels=$use_labels&tpEditLabels=$edit_labels&tpParameterList=$parameter_list$vs_app_parameters&labelTypecode=$vn_label_typecode&labelDefaultTitle=".urlencode($vs_label_title)."&labelTitleReadOnly=".$vn_label_title_readonly;
		if (!$directly_embed_flash) {
				$tag = <<<EOT
				<div id="$idname">
					Flash version 8 or better required!
				</div>
				<script type='text/javascript'>
					swfobject.embedSWF("$viewer_base_url/viewers/apps/bischen.swf", "$idname", "$viewer_width", "$viewer_height", "8.0.0","$viewer_base_url/viewers/apps/expressInstall.swf", false, {AllowScriptAccess: "always", allowFullScreen: "true", flashvars:"$vs_flash_vars", bgcolor: "#ffffff"});
				</script>
EOT;
			} else {		
				$tag = <<<EOT
		<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" 
			width="$viewer_width" height="$viewer_height" id="$idname" align="middle">
			<param name="allowScriptAccess" value="sameDomain" />
			<param name="FlashVars" value="$vs_flash_vars" />
			<param name="movie" value="$viewer_base_url/viewers/apps/bischen.swf" />
			<param name="quality" value="high" />
			<param name="bgcolor" value="#ffffff" />
			<embed src="$viewer_base_url/viewers/apps/bischen.swf" quality="high" bgcolor="#ffffff" width="$viewer_width" height="$viewer_height" name="$idname" align="middle" 
				FlashVars="$vs_flash_vars" 
				allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
		</object>
EOT;
			}
			return $tag;
		} else {
			#
			# Standard imaage
			#
			if (isset($options["name"]) && ($options["name"] != "")) {
				$name = "name='".htmlspecialchars($options["name"], ENT_QUOTES, 'UTF-8')."'";
			} else {
				$name = "";
			}
			if (isset($options["vspace"]) && ($options["vspace"] != "")) {
				$vspace = "vspace='".$options["vspace"]."'";
			} else {
				$vspace = "";
			}
			if (isset($options["hspace"]) && ($options["hspace"] != "")) {
				$hspace = "hspace='".$options["hspace"]."'";
			} else {
				$hspace = "";
			}
			if (isset($options["alt"]) && ($options["alt"] != "")) {
				$alt = "alt='".htmlspecialchars($options["alt"], ENT_QUOTES, 'UTF-8')."'";
			} else {
				$alt = "alt='image'";
			}
			if (isset($options["title"]) && ($options["title"] != "")) {
				$title = "title='".htmlspecialchars($options["title"], ENT_QUOTES, 'UTF-8')."'";
			} else {
				$title = "";
			}
			if (isset($options["usemap"]) && ($options["usemap"] != "")) {
				$usemap = "usemap='#".$options["usemap"]."'";
			} else {
				$usemap = "";
			}
			if (isset($options["align"]) && ($options["align"] != "")) {
				$align = " align='".$options["align"]."'";
			} else {
				$align= "";
			}
			
			if (isset($options["style"]) && ($options["style"] != "")) {
				$style = " style='".$options["style"]."'";
			} else {
				$style= "";
			}
			
			if (isset($options["class"]) && ($options["class"] != "")) {
				$class = " class='".$options["class"]."'";
			} else {
				$class= "";
			}
			
			if ($options["border"]) {
				$border = intval($options["border"]);
			} else {
				$border = 0;
			}
			
			if (!isset($properties["width"])) $properties["width"] = 100;
			if (!isset($properties["height"])) $properties["height"] = 100;
					
			if (($url) && ($properties["width"] > 0) && ($properties["height"] > 0)) {
			
				return "<img src='$url' width='".$properties["width"]."' height='".$properties["height"]."' border='$border' $vspace $hspace $alt $title $name $usemap $align $class $style />";
			} else {
				return "<b><i>No image</i></b>";
			}
		}
	}
	# ------------------------------------------------
}
?>
