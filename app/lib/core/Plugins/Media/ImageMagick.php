<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/ImageMagick.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2011 Whirl-i-Gig
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
  * Plugin for processing images using ImageMagick command-line executables
  */

include_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/core/Parsers/TilepicParser.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");

class WLPlugMediaImageMagick Extends WLPlug Implements IWLPlugMedia {
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
			"image/tiff" 		=> "tiff",
			"image/png" 		=> "png",
			"image/x-bmp" 		=> "bmp",
			"image/x-psd" 		=> "psd",
			"image/tilepic" 	=> "tpc",
			"image/x-dpx"		=> "dpx",
			"image/x-exr"		=> "exr",
			"image/jp2"		=> "jp2",
			"image/x-adobe-dng"	=> "dng",
			"image/x-canon-cr2"	=> "cr2",
			"image/x-canon-crw"	=> "crw",
			"image/x-sony-arw"	=> "arw",
			"image/x-olympus-orf"	=> "orf",
			"image/x-pentax-pef"	=> "pef",
			"image/x-epson-erf"	=> "erf",
			"image/x-nikon-nef"	=> "nef",
			"image/x-sony-sr2"	=> "sr2",
			"image/x-sony-srf"	=> "srf",
			"image/x-sigma-x3f"	=> "x3f",
		),
		"EXPORT" => array(
			"image/jpeg" 		=> "jpg",
			"image/gif" 		=> "gif",
			"image/tiff" 		=> "tiff",
			"image/png" 		=> "png",
			"image/x-bmp" 		=> "bmp",
			"image/x-psd" 		=> "psd",
			"image/tilepic" 	=> "tpc",
			"image/x-dpx"		=> "dpx",
			"image/x-exr"		=> "exr",
			"image/jp2"		=> "jp2",
			"image/x-adobe-dng"	=> "dng",
			"image/x-canon-cr2"	=> "cr2",
			"image/x-canon-crw"	=> "crw",
			"image/x-sony-arw"	=> "arw",
			"image/x-olympus-orf"	=> "orf",
			"image/x-pentax-pef"	=> "pef",
			"image/x-epson-erf"	=> "erf",
			"image/x-nikon-nef"	=> "nef",
			"image/x-sony-sr2"	=> "sr2",
			"image/x-sony-srf"	=> "srf",
			"image/x-sigma-x3f"	=> "x3f",
		),
		"TRANSFORMATIONS" => array(
			"SCALE" 			=> array("width", "height", "mode", "antialiasing", "trim_edges", "crop_from"),
			"ANNOTATE"			=> array("text", "font", "size", "color", "position", "inset"),
			"WATERMARK"			=> array("image", "width", "height", "position", "opacity"),
			"ROTATE" 			=> array("angle"),
			"SET" 				=> array("property", "value"),
			"DENSITY"			=> array("ppi", "mode"),
			
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
			'gamma'				=> 'W',
			'reference-black'	=> 'W',
			'reference-white'	=> 'W',
			'version'			=> 'W'	// required of all plug-ins
		),
		
		"NAME" => "ImageMagick"
	);
	
	var $typenames = array(
		"image/jpeg" 		=> "JPEG",
		"image/gif" 		=> "GIF",
		"image/tiff" 		=> "TIFF",
		"image/png" 		=> "PNG",
		"image/x-bmp" 		=> "Windows Bitmap (BMP)",
		"image/x-psd" 		=> "Photoshop",
		"image/tilepic" 	=> "Tilepic",
		"image/x-dpx"		=> "DPX",
		"image/x-exr"		=> "OpenEXR",
		"image/jp2"		=> "JPEG-2000",
		"image/x-adobe-dng"	=> "Adobe DNG",
		"image/x-canon-cr2"	=> "Canon CR2 RAW Image",
		"image/x-canon-crw"	=> "Canon CRW RAW Image",
		"image/x-sony-arw"	=> "Sony ARW RAW Image",
		"image/x-olympus-orf"	=> "Olympus ORF Raw Image",
		"image/x-pentax-pef"	=> "Pentax Electronic File Image",
		"image/x-epson-erf"	=> "Epson ERF RAW Image",
		"image/x-nikon-nef"	=> "Nikon NEF RAW Image",
		"image/x-sony-sr2"	=> "Sony SR2 RAW Image",
		"image/x-sony-srf"	=> "Sony SRF RAW Image",
		"image/x-sigma-x3f"	=> "Sigma X3F RAW Image",
	);
	
	var $magick_names = array(
		"image/jpeg" 		=> "JPEG",
		"image/gif" 		=> "GIF",
		"image/tiff" 		=> "TIFF",
		"image/png" 		=> "PNG",
		"image/x-bmp" 		=> "BMP",
		"image/x-psd" 		=> "PSD",
		"image/tilepic" 	=> "TPC",
		"image/x-dpx"		=> "DPX",
		"image/x-exr"		=> "EXR",
		"image/jp2"		=> "JP2",
		"image/x-adobe-dng"	=> "DNG",
		"image/x-canon-cr2"	=> "CR2",
		"image/x-canon-crw"	=> "CRW",
		"image/x-sony-arw"	=> "ARW",
		"image/x-olympus-orf"	=> "ORF",
		"image/x-pentax-pef"	=> "PEF",
		"image/x-epson-erf"	=> "ERF",
		"image/x-nikon-nef"	=> "NEF",
		"image/x-sony-sr2"	=> "SR2",
		"image/x-sony-srf"	=> "SRF",
		"image/x-sigma-x3f"	=> "X3F",
	);
	
	#
	# Some versions of ImageMagick return variants on the "normal"
	# mimetypes for certain image formats, so we convert them here
	#
	var $magick_mime_map = array(
		"image/x-jpeg" 		=> "image/jpeg",
		"image/x-gif" 		=> "image/gif",
		"image/x-tiff" 		=> "image/tiff",
		"image/x-png" 		=> "image/png",
		"image/dpx" 		=> "image/x-dpx",
		"image/exr" 		=> "image/x-exr",
		"image/jpx"		=> "image/jp2",
		"image/jpm"		=> "image/jp2",
		"image/dng"		=> "image/x-adobe-dng"
	);
	
	
	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Provides image processing and conversion services using ImageMagick via exec() calls to ImageMagick binaries');
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		// get config for external apps
		$this->opo_config = Configuration::load();
		$vs_external_app_config_path = $this->opo_config->get('external_applications');
		$this->opo_external_app_config = Configuration::load($vs_external_app_config_path);
		$this->ops_imagemagick_path = $this->opo_external_app_config->get('imagemagick_path');
		
		$this->ops_CoreImage_path = $this->opo_external_app_config->get('coreimagetool_app');
		
		if (caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
			return null;	// don't use if CoreImage executable are available
		}
		
		if (caMediaPluginImagickInstalled()) {	
			return null;	// don't use if Imagick is available
		} 
		if (caMediaPluginMagickWandInstalled()) {
			return null;	// don't use if MagickWand functions are available
		}
		
		if (!caMediaPluginImageMagickInstalled($this->ops_imagemagick_path)) {
			return null;	// don't use if Imagemagick executables are unavailable
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
				$va_status['warnings'][] = _t("Didn't load because Imagick is available and preferred");
			} 
			if (caMediaPluginMagickWandInstalled()) {
				$va_status['unused'] = true;
				$va_status['warnings'][] = _t("Didn't load because MagickWand is available and preferred");
			}
			if (!caMediaPluginImageMagickInstalled($this->ops_imagemagick_path)) {
				$va_status['errors'][] = _t("Didn't load because ImageMagick executables cannot be found");
			}
			
		}	
		
		return $va_status;
	}
	# ------------------------------------------------
	public function divineFileFormat($filepath) {
		if(!strpos($filepath, ':') || (caGetOSFamily() == OS_WIN32)) {
			// ImageMagick bails when a colon is in the file name... catch it here
			$vs_mimetype = $this->_imageMagickIdentify($filepath);
			return ($vs_mimetype) ? $vs_mimetype : '';
		} else {
			$this->postError(1610, _t("Filenames with colons (:) are not allowed"), "WLPlugImageMagick->divineFileFormat()");
			return false;
		}
			
		# is it a tilepic?
		$tp = new TilepicParser();
		if ($tp->isTilepic($filepath)) {
			return 'image/tilepic';
		} else {
			# file format is not supported by this plug-in
			return '';
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
					$this->postError(1650, _t("Tile size property must be between 10 and 10000"), "WLPlugImageMagick->set()");
					return '';
				}
				$this->properties["tile_width"] = $value;
				$this->properties["tile_height"] = $value;
			} else {
				if ($this->info["PROPERTIES"][$property]) {
					switch($property) {
						case 'quality':
							if (($value < 1) || ($value > 100)) {
								$this->postError(1650, _t("Quality property must be between 1 and 100"), "WLPlugImageMagick->set()");
								return '';
							}
							$this->properties["quality"] = $value;
							break;
						case 'tile_width':
							if (($value < 10) || ($value > 10000)) {
								$this->postError(1650, _t("Tile width property must be between 10 and 10000"), "WLPlugImageMagick->set()");
								return '';
							}
							$this->properties["tile_width"] = $value;
							break;
						case 'tile_height':
							if (($value < 10) || ($value > 10000)) {
								$this->postError(1650, _t("Tile height property must be between 10 and 10000"), "WLPlugImageMagick->set()");
								return '';
							}
							$this->properties["tile_height"] = $value;
							break;
						case 'antialiasing':
							if (($value < 0) || ($value > 100)) {
								$this->postError(1650, _t("Antialiasing property must be between 0 and 100"), "WLPlugImageMagick->set()");
								return '';
							}
							$this->properties["antialiasing"] = $value;
							break;
						case 'layer_ratio':
							if (($value < 0.1) || ($value > 10)) {
								$this->postError(1650, _t("Layer ratio property must be between 0.1 and 10"), "WLPlugImageMagick->set()");
								return '';
							}
							$this->properties["layer_ratio"] = $value;
							break;
						case 'layers':
							if (($value < 1) || ($value > 25)) {
								$this->postError(1650, _t("Layer property must be between 1 and 25"), "WLPlugImageMagick->set()");
								return '';
							}
							$this->properties["layers"] = $value;
							break;	
						case 'tile_mimetype':
							if ((!($this->info["EXPORT"][$value])) && ($value != "image/tilepic")) {
								$this->postError(1650, _t("Tile output type '%1' is invalid", $value), "WLPlugImageMagick->set()");
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
					$this->postError(1650, _t("Can't set property %1", $property), "WLPlugImageMagick->set()");
					return '';
				}
			}
		} else {
			return '';
		}
		return 1;
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
		if (!(($this->handle) && ($filepath === $this->filepath))) {
			
			if(strpos($filepath, ':') && (caGetOSFamily() != OS_WIN32)) {
				$this->postError(1610, _t("Filenames with colons (:) are not allowed"), "WLPlugImageMagick->read()");
				return false;
			}
			if ($mimetype == 'image/tilepic') {
				#
				# Read in Tilepic format image
				#
				$this->handle = new TilepicParser($filepath);
				if (!$this->handle->error) {
					$this->filepath = $filepath;
					foreach($this->handle->properties as $k => $v) {
						if (isset($this->properties[$k])) {
							$this->properties[$k] = $v;
						}
					}
					$this->properties["mimetype"] = "image/tilepic";
					$this->properties["typename"] = "Tilepic";
					
					return 1;
				} else {
					$this->postError(1610, $this->handle->error, "WLPlugImageMagick->read()");
					return false;
				}
			} else {
				$this->handle = "";
				$this->filepath = "";
				
				
				$handle = $this->_imageMagickRead($filepath);
				if ($handle) {
					$this->handle = $handle;
					$this->filepath = $filepath;
					$this->metadata = array();
					
					$va_raw_metadata = $this->_imageMagickGetMetadata($filepath);
					foreach($va_raw_metadata as $vs_line) {
						list($vs_tag, $vs_value) = explode('=', $vs_line);
						if (!trim($vs_tag) || !trim($vs_value)) { continue; }
						if (sizeof($va_tmp = explode(':', $vs_tag)) > 1) {
							$vs_type = strtoupper($va_tmp[0]);
							$vs_tag = $va_tmp[1];
						} else {
							$vs_type = 'GENERIC';
						}
						
						$this->metadata['METADATA_'.$vs_type][$vs_tag] = $vs_value;
					}
					
					# load image properties
					$this->properties["width"] = $this->handle['width'];
					$this->properties["height"] = $this->handle['height'];
					$this->properties["quality"] = "";
					$this->properties["mimetype"] = $this->handle['mimetype'];
					$this->properties["typename"] = $this->handle['magick'];
					$this->properties["filesize"] = filesize($filepath);
					$this->properties["bitdepth"] = $this->handle['depth'];
					$this->properties["resolution"] = $this->handle['resolution'];
					$this->properties["colorspace"] = $this->handle['colorspace'];
					
					$this->ohandle = $this->handle;
					return 1;
				} else {
					# plug-in can't handle format
					return false;
				}
			}
		} else {
			# image already loaded by previous call (probably divineFileFormat())
			return 1;
		}
	}
	# ----------------------------------------------------------
	public function transform($operation, $parameters) {
		if ($this->properties["mimetype"] == "image/tilepic") { return false;} # no transformations for Tilepic
		if (!$this->handle) { return false; }
		
		if (!($this->info["TRANSFORMATIONS"][$operation])) {
			# invalid transformation
			$this->postError(1655, _t("Invalid transformation %1", $operation), "WLPlugImageMagick->transform()");
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
			case 'ANNOTATE':
				switch($parameters['position']) {
					case 'north_east':
						$position = 'NorthEast';
						break;
					case 'north_west':
						$position = 'NorthWest';
						break;
					case 'north':
						$position = 'North';
						break;
					case 'south_east':
						$position = 'SouthEast';
						break;
					case 'south':
						$position = 'South';
						break;
					case 'center':
						$position = 'Center';
						break;
					case 'south_west':
					default:
						$position = 'SouthWest';
						break;
				}
				
				$this->handle['ops'][] = array(
					'op' => 'annotation',
					'text' => $parameters['text'],
					'inset' => ($parameters['inset'] > 0) ? $parameters['inset']: 0,
					'font' => $parameters['font'],
					'size' => ($parameters['size'] > 0) ? $parameters['size']: 18,
					'color' => $parameters['color'] ? $parameters['color'] : "black",
					'position' => $position
				);
				break;
			# -----------------------
			case 'WATERMARK':
				if (!file_exists($parameters['image'])) { break; }
				$vn_opacity_setting = $parameters['opacity'];
				if (($vn_opacity_setting < 0) || ($vn_opacity_setting > 1)) {
					$vn_opacity_setting = 0.5;
				}
				
				if (($vn_watermark_width = $parameters['width']) < 10) { 
					$vn_watermark_width = $cw/2;
				}
				if (($vn_watermark_height = $parameters['height']) < 10) {
					$vn_watermark_height = $ch/2;
				}
				
				switch($parameters['position']) {
					case 'north_east':
						$vn_watermark_x = $cw - $vn_watermark_width;
						$vn_watermark_y = 0;
						break;
					case 'north_west':
						$vn_watermark_x = 0;
						$vn_watermark_y = 0;
						break;
					case 'north':
						$vn_watermark_x = ($cw - $vn_watermark_width)/2;
						$vn_watermark_y = 0;
						break;
					case 'south_east':
						$vn_watermark_x = $cw - $vn_watermark_width;
						$vn_watermark_y = $ch - $vn_watermark_height;
						break;
					case 'south':
						$vn_watermark_x = ($cw - $vn_watermark_width)/2;
						$vn_watermark_y = $cw - $vn_watermark_width;
						break;
					case 'center':
						$vn_watermark_x = ($cw - $vn_watermark_width)/2;
						$vn_watermark_y = ($ch - $vn_watermark_height)/2;
						break;
					case 'south_west':
					default:
						$vn_watermark_x = $cw - $vn_watermark_width;
						$vn_watermark_y = $ch - $vn_watermark_height;
						break;
				}
				
				$this->handle['ops'][] = array(
					'op' => 'watermark',
					'opacity' => $vn_opacity_setting,
					'watermark_width' => $vn_watermark_width,
					'watermark_height' => $vn_watermark_height,
					'position' => $parameters['position'],
					'position_x' => $vn_watermark_x,
					'position_y' => $vn_watermark_y,
					'watermark_image' => $parameters['image']
				);
				break;
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
						$crop_from = $parameters["crop_from"];
						if (!in_array($crop_from, array('center', 'north_east', 'north_west', 'south_east', 'south_west', 'random'))) {
							$crop_from = '';
						}
						
						$scale_factor_w = $w/$cw;
						$scale_factor_h = $h/$ch;
						$w = $cw * (($scale_factor_w > $scale_factor_h) ? $scale_factor_w : $scale_factor_h); 
						$h = $ch * (($scale_factor_w > $scale_factor_h) ? $scale_factor_w : $scale_factor_h);	
						
						$do_fill_box_crop = true;
						break;
					# ----------------
				}
				
				$w = round($w);
				$h = round($h);
				if ($w > 0 && $h > 0) {
					$crop_w_edge = $crop_h_edge = 0;
					if (preg_match("/^([\d]+)%$/", $parameters["trim_edges"], $va_matches)) {
						$crop_w_edge = ceil((intval($va_matches[1])/100) * $w);
						$crop_h_edge = ceil((intval($va_matches[1])/100) * $h);
					} else {
						if (isset($parameters["trim_edges"]) && (intval($parameters["trim_edges"]) > 0)) {
							$crop_w_edge = $crop_h_edge = intval($parameters["trim_edges"]);
						}
					}
					$this->handle['ops'][] = array(
						'op' => 'size',
						'width' => $w + ($crop_w_edge * 2),
						'height' => $h + ($crop_h_edge * 2),
						'antialiasing' => $aa
					);
					
					if ($do_fill_box_crop) {
						switch($crop_from) {
							case 'north_west':
								$crop_from_offset_y = 0;
								$crop_from_offset_x = $w - $parameters["width"];
								break;
							case 'south_east':
								$crop_from_offset_x = 0;
								$crop_from_offset_y = $h - $parameters["height"];
								break;
							case 'south_west':
								$crop_from_offset_x = $w - $parameters["width"];
								$crop_from_offset_y = $h - $parameters["height"];
								break;
							case 'random':
								$crop_from_offset_x = rand(0, $w - $parameters["width"]);
								$crop_from_offset_y = rand(0, $h - $parameters["height"]);
								break;
							case 'north_east':
								$crop_from_offset_x = $crop_from_offset_y = 0;
								break;
							case 'center':
							default:
								if ($w > $parameters["width"]) {
									$crop_from_offset_x = ceil(($w - $parameters["width"])/2);
								} else {
									if ($h > $parameters["height"]) {
										$crop_from_offset_y = ceil(($h - $parameters["height"])/2);
									}
								}
								break;
						}
						$this->handle['ops'][] = array(
							'op' => 'crop',
							'width' => $parameters["width"],
							'height' => $parameters["height"],
							'x' => $crop_w_edge + $crop_from_offset_x,
							'y' => $crop_h_edge + $crop_from_offset_y
						);
						
						$this->properties["width"] = $parameters["width"];
						$this->properties["height"] = $parameters["height"];
					} else {
						if ($crop_w_edge || $crop_h_edge) {
							$this->handle['ops'][] = array(
								'op' => 'crop',
								'width' => $w,
								'height' => $h,
								'x' => $crop_w_edge,
								'y' => $crop_h_edge
							);
						}
						$this->properties["width"] = $w;
						$this->properties["height"] = $h;
					}
				}
			break;
		# -----------------------
		case "DENSITY":
			$ppi = $parameters["ppi"];
			if ($ppi < .1) { $ppi = 1; }
			$mode = $parameters["mode"];
			if(isset($mode)) {
				$mode = strtolower(trim($mode));
			}
			if(!in_array($mode, array('simple', 'resample', 'resize'))) {
				$mode = 'simple';
			}
			$extra = array();
			if('resize' == $mode) {
				$cr = $this->properties['resolution'];
				$crx = $cr['x'];
				$cry = $cr['y'];

				// http://stackoverflow.com/questions/2121890/change-resolution-of-image-from-72-to-25-dpi-in-php
				$w = ($ppi * $cw) / $crx;
				$h = ($ppi * $ch) / $cry;
				$extra = array(
					'nw' => $w,
					'nh' => $h,
					'cw' => $cw,
					'ch' => $ch
				);
			}
			$this->handle['ops'][] = array(
				'op' => 'density',
				'ppi' => $ppi,
				'mode' => $mode,
				'extra' => $extra
			);
			break;
		# -----------------------
		case "ROTATE":
			$angle = $parameters["angle"];
			if (($angle > -360) && ($angle < 360)) {
				$this->handle['ops'][] = array(
					'op' => 'rotate',
					'angle' => $angle
				);
			}
			break;
		# -----------------------
		case "DESPECKLE":
			$this->handle['ops'][] = array(
				'op' => 'filter_despeckle'
			);
			break;
		# -----------------------
		case "MEDIAN":
			$radius = $parameters["radius"];
			if ($radius < .1) { $radius = 1; }
			$this->handle['ops'][] = array(
				'op' => 'filter_median',
				'radius' => $radius
			);
			break;
		# -----------------------
		case "SHARPEN":
			$radius = $parameters["radius"];
			if ($radius < .1) { $radius = 1; }
			$sigma = $parameters["sigma"];
			if ($sigma < .1) { $sigma = 1; }
			$this->handle['ops'][] = array(
				'op' => 'filter_sharpen',
				'radius' => $radius,
				'sigma' => $sigma
			);
			break;
		# -----------------------
		case "UNSHARPEN_MASK":
			$radius = $parameters["radius"];
			if ($radius < .1) { $radius = 1; }
			$sigma = $parameters["sigma"];
			if ($sigma < .1) { $sigma = 1; }
			$threshold = $parameters["threshold"];
			if ($threshold < .1) { $threshold = 1; }
			$amount = $parameters["amount"];
			if ($amount < .1) { $amount = 1; }
			$this->handle['ops'][] = array(
				'op' => 'filter_unsharp_mask',
				'radius' => $radius,
				'sigma' => $sigma,
				'amount' => $amount,
				'threshold' => $threshold
			);
			break;
		# -----------------------
		case "SET":
			while(list($k, $v) = each($parameters)) {
				$this->set($k, $v);
			}
			break;
		# -----------------------
		case "MAX_SCALE":
			$aa = $parameters["antialiasing"];
			if ($aa <= 0) { $aa = 0; }

			if($cw < $w && $ch < $h){

				$this->handle['ops'][] = array(
					'op' => 'size',
					'width' => $cw ,
					'height' => $ch,
					'antialiasing' => $aa
				);

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

				$this->handle['ops'][] = array(
					'op' => 'size',
					'width' => $w ,
					'height' => $h,
					'antialiasing' => $aa
				);
				$this->properties["width"] = $w;
				$this->properties["height"] = $h;


			}
			break;
		# -----------------------
		}
		return 1;
	}
	# ----------------------------------------------------------
	public function write($filepath, $mimetype) {
		if (!$this->handle) { return false; }
		if(strpos($filepath, ':') && (caGetOSFamily() != OS_WIN32)) {
			$this->postError(1610, _t("Filenames with colons (:) are not allowed"), "WLPlugImageMagick->write()");
			return false;
		}
		if ($mimetype == "image/tilepic") {
			if ($this->properties["mimetype"] == "image/tilepic") {
				copy($this->filepath, $filepath);
			} else {
				$tp = new TilepicParser();
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
					$this->postError(1610, $tp->error, "WLPlugTilepic->write()");	
					return false;
				}
			}
			# update mimetype
			foreach($properties as $k => $v) {
				$this->properties[$k] = $v;
			}
			$this->properties["mimetype"] = "image/tilepic";
			$this->properties["typename"] = "Tilepic";
			return 1;
		} else {
			# is mimetype valid?
			if (!($ext = $this->info["EXPORT"][$mimetype])) {
				# this plugin can't write this mimetype
				return false;
			} 
					
			if (!$this->_imageMagickWrite($this->handle, $filepath.".".$ext, $mimetype, $this->properties["quality"])) {
				$this->postError(1610, _t("%1: %2", $reason, $description), "WLPlugImageMagick->write()");
				return false;
			}
			
			# update mimetype
			$this->properties["mimetype"] = $mimetype;
			$this->properties["typename"] = $this->magick_names[$mimetype];
			
			return $filepath.".".$ext;
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
	public function magickToMimeType($ps_magick) {
		foreach($this->magick_names as $vs_mimetype => $vs_magick) {
			if ($ps_magick == $vs_magick) {
				return $vs_mimetype;
			}
		}
		return null;
	}
	# ------------------------------------------------
	public function reset() {
		if ($this->ohandle) {
			$this->handle = $this->ohandle;
			# load image properties
			$this->properties["width"] = $this->handle['width'];
			$this->properties["height"] = $this->handle['height'];
			$this->properties["quality"] = "";
			$this->properties["mimetype"] = $this->handle['mimetype'];
			$this->properties["typename"] = $this->handle['magick'];
			return true;
		}
		return false;
	}
	# ------------------------------------------------
	public function init() {
		unset($this->handle);
		unset($this->ohandle);
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
				$name = "name='".htmlspecialchars($options["name"], ENT_QUOTES, 'UTF-8')."' id='".htmlspecialchars($options["name"], ENT_QUOTES, 'UTF-8')."'";
			} else {
				$name = "";
			}
			if (isset($options["vspace"]) && ($options["vspace"] != "")) {
				$vspace = "vspace='".$options["name"]."'";
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
			
				return "<img src='$url' width='".$properties["width"]."' height='".$properties["height"]."' border='$border' $vspace $hspace $alt $title $name $usemap $align $style $class />";
			} else {
				return "<strong>No image</strong>";
			}
		}
	}
	# ------------------------------------------------
	# Command line wrappers
	# ------------------------------------------------
	private function _imageMagickIdentify($ps_filepath) {
		if (caMediaPluginImageMagickInstalled($this->ops_imagemagick_path)) {
			exec($this->ops_imagemagick_path.'/identify -format "%m" "'.$ps_filepath."\" 2> /dev/null", $va_output, $vn_return);
			return $this->magickToMimeType($va_output[0]);
		}
		return null;
	}
	# ------------------------------------------------
	private function _imageMagickGetMetadata($ps_filepath) {
		if (caMediaPluginImageMagickInstalled($this->ops_imagemagick_path)) {
			$va_metadata = array();
			exec($this->ops_imagemagick_path."/identify -format '%[EXIF:*]' \"".$ps_filepath.'"', $va_output, $vn_return);
			if ($va_output[0]) { $va_metadata = array_merge($va_metadata, $va_output); }
			exec($this->ops_imagemagick_path."/identify -format '%[DPX:*]' \"".$ps_filepath.'"', $va_output, $vn_return);
			if ($va_output[0]) { $va_metadata = array_merge($va_metadata, $va_output); }
			
			$va_iptc_tags = array(
				'credit' => '2:110',
				'byline' => '2:080',
				'date_created' => '2:055',
				'time_created' => '2:060',
				'caption' => '2:120',
				'copyright' => '2:116',
				'title' => '2:005',
				'genre' => '2:004',
				'urgency' => '2:010',
				'category' => '2:015',
				'supplemental category' => '2:020',
				'keywords' => '2:025',
				'special_instructions' => '2:040',
				'byline' => '2:080',
				'bylinetitle' => '2:085',
				'city' => '2:090',
				'location' => '2:092',
				'province' => '2:095',
				'countrycode' => '2:100',
				'countryname' => '2:101',
				'headline' => '2:105',
				'credit' => '2:110',
				'source' => '2:115',
				'description writer' => '2:122'
			);
			
			$va_iptc = array();
			foreach($va_iptc_tags as $vs_tag_name => $vs_tag_code) {
				$va_output = array();
				exec($this->ops_imagemagick_path."/identify -format '%[IPTC:".$vs_tag_code."]' \"".$ps_filepath.'"', $va_output, $vn_return);
				if ($va_output[0]) {
					$va_iptc[] = 'IPTC:'.str_replace(":", ",", $vs_tag_code).' ['.$vs_tag_name.']='.$va_output[0];
				}
			}
			
			if (sizeof($va_iptc)) {
				$va_metadata = array_merge($va_metadata, $va_iptc);
			}
			return $va_metadata;
		}
		return null;
	}
	# ------------------------------------------------
	private function _imageMagickRead($ps_filepath) {
		if (caMediaPluginImageMagickInstalled($this->ops_imagemagick_path)) {
			exec($this->ops_imagemagick_path.'/identify -format "%m;%w;%h;%[colorspace];%[depth];%[xresolution];%[yresolution]\n" "'.$ps_filepath."\" 2> /dev/null", $va_output, $vn_return);
			
			$va_tmp = explode(';', $va_output[0]);
			
			if (sizeof($va_tmp) != 7) {
				return null;
			}
			
			return array(
				'mimetype' => $this->magickToMimeType($va_tmp[0]),
				'magick' => $va_tmp[0],
				'width' => $va_tmp[1],
				'height' => $va_tmp[2],
				'colorspace' => $va_tmp[3],
				'depth' => $va_tmp[4],
				'resolution' => array(
					'x' => $va_tmp[5],
					'y' => $va_tmp[6]
				),
				'ops' => array(),
				'filepath' => $ps_filepath
			);
		}
		return null;
	}
	# ------------------------------------------------
	private function _imageMagickWrite($pa_handle, $ps_filepath, $ps_mimetype, $pn_quality=null) {
		if (caMediaPluginImageMagickInstalled($this->ops_imagemagick_path)) {
			
			$va_ops = array();	
			foreach($pa_handle['ops'] as $va_op) {
				switch($va_op['op']) {
					case 'annotation':
						$vs_op = '-gravity '.$va_op['position'].' -fill '.str_replace('#', '\\#', $va_op['color']).' -pointsize '.$va_op['size'].' -draw "text '.$va_op['inset'].','.$va_op['inset'].' \''.$va_op['text'].'\'"';
						
						if ($va_op['font']) {
							$vs_op .= ' -font '.$va_op['font'];
						}
						$va_ops['convert'][] = $vs_op;
						break;
					case 'watermark':
						$vs_op = "-dissolve ".($va_op['opacity'] * 100)." -gravity ".$va_op['position']." ".$va_op['watermark_image']; //"  -geometry ".$va_op['watermark_width']."x".$va_op['watermark_height']; [Seems to be interpreted as scaling the image being composited on as of at least v6.5.9; so we don't scale watermarks in ImageMagick... we just use the native size]
						$va_ops['composite'][] = $vs_op;
						break;
					case 'size':
						if ($va_op['width'] < 1) { break; }
						if ($va_op['height'] < 1) { break; }
						$va_ops['convert'][] = '-resize '.$va_op['width'].'x'.$va_op['height'].' -filter Cubic';
						break;
					case 'crop':
						if ($va_op['width'] < 1) { break; }
						if ($va_op['height'] < 1) { break; }
						if ($va_op['x'] < 0) { break; }
						if ($va_op['y'] < 0) { break; }
						$va_ops['convert'][] = '-crop '.$va_op['width'].'x'.$va_op['height'].'+'.$va_op['x'].'+'.$va_op['y'];
						break;
					case 'density':
						$ppi = $va_op['ppi'];
						$pos = strpos($ppi,'x'); // check if an x is in the strpos
						if(is_numeric($ppi) && $pos === false) {
							$ppi = $ppi.'x'.$ppi; // if no x is found and ppi is numeric we put it this way
						}
						$mode = $va_op['mode'];
						if('resize' == $mode) {
							$extra = $va_op['extra'];
							$cw = $extra['cw'];
							$ch = $extra['ch'];

							$nw = $extra['nw'];
							$nh = $extra['nh'];

							$va_ops['convert'][] = '-strip -units "PixelsPerInch" -density '.$ppi.' -resize '.$nw.'x'.$nh;
							break;
						}
						if('resample' == $mode) { // check if we need to resample the image
							$resample = ' -resample '.$ppi;
						}
						$va_ops['convert'][] = '-strip -units "PixelsPerInch" -density '.$ppi.$resample;
						break;
					case 'rotate':
						if (!is_numeric($va_op['angle'])) { break; }
						$va_ops['convert'][] = '-rotate '.$va_op['angle'];
						break;
					case 'filter_despeckle':
						$va_ops['convert'][] = '-despeckle';
						break;
					case 'filter_sharpen':
						if ($va_op['radius'] < 0) { break; }
						$vs_tmp = '-sharpen '.$va_op['radius'];
						if (isset($va_op['sigma'])) { $vs_tmp .= 'x'.$va_op['sigma'];}
						$va_ops['convert'][] = $vs_tmp;
						break;
					case 'filter_median':
						if ($va_op['radius'] < 0) { break; }
						$va_ops['convert'][] = '-median '.$va_op['radius'];
						break;
					case 'filter_unsharp_mask':
						if ($va_op['radius'] < 0) { break; }
						$vs_tmp = '-unsharp '.$va_op['radius'];
						if (isset($va_op['sigma'])) { $vs_tmp .= 'x'.$va_op['sigma'];}
						if (isset($va_op['amount'])) { $vs_tmp .= '+'.$va_op['amount'];}
						if (isset($va_op['threshold'])) { $vs_tmp .= '+'.$va_op['threshold'];}
						$va_ops['convert'][] = $vs_tmp;
						break;
				}
			}
			
			if ($this->properties['gamma']) {
				if (!$this->properties['reference-black']) { $this->properties['reference-black'] = 0; }
				if (!$this->properties['reference-white']) { $this->properties['reference-white'] = 65535; }
				$va_ops['convert'][] = "-set gamma ".$this->properties['gamma'];
				$va_ops['convert'][] = "-set reference-black ".$this->properties['reference-black'];
				$va_ops['convert'][] = "-set reference-white ".$this->properties['reference-white'];
			}
			
			$vs_input_file = $pa_handle['filepath'];
			if (is_array($va_ops['convert']) && sizeof($va_ops['convert'])) {
				if (!is_null($pn_quality)) {
					array_unshift($va_ops['convert'], '-quality '.intval($pn_quality));
				}
				array_unshift($va_ops['convert'], '-colorspace RGB');
				exec($this->ops_imagemagick_path.'/convert "'.$vs_input_file.'[0]" '.join(' ', $va_ops['convert']).' "'.$ps_filepath.'"');
				$vs_input_file = $ps_filepath;
			}
			if (is_array($va_ops['composite']) && sizeof($va_ops['composite'])) {
				exec($this->ops_imagemagick_path.'/composite '.join(' ', $va_ops['composite']).' "'.$vs_input_file.'[0]" "'.$ps_filepath.'"');
			}	
			
			return true;
		}
		return null;
	}
	# ------------------------------------------------
}
# ----------------------------------------------------------------------
?>
