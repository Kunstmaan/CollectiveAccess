<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/CoreImage.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 * Plugin for processing images using CoreImage (Mac OS X only)
 */

include_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/core/Parsers/TilepicParser.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");

class WLPlugMediaCoreImage Extends WLPlug Implements IWLPlugMedia {
	var $errors = array();
	
	var $filepath;
	var $filepath_conv;		// filepath for converted input file; 
	var $handle;
	var $ohandle;
	var $properties;
	var $metadata = array();
	
	var $opo_config;
	var $opo_external_app_config;
	var $ops_CoreImage_path;
	
	var $info = array(
		"IMPORT" => array(
			"image/jpeg" 		=> "jpg",
			"image/gif" 		=> "gif",
			"image/tiff" 		=> "tiff",
			"image/png" 		=> "png",
			"image/x-bmp" 		=> "bmp",
			"image/x-psd" 		=> "psd",
			"image/jp2"			=> "jp2",
			"image/x-adobe-dng"	=> "dng"
		),
		"EXPORT" => array(
			"image/jpeg" 		=> "jpg",
			"image/gif" 		=> "gif",
			"image/tiff" 		=> "tiff",
			"image/png" 		=> "png",
			"image/x-bmp" 		=> "bmp",
			"image/x-psd" 		=> "psd",
			"image/tilepic" 	=> "tpc",
			"image/jp2"			=> "jp2",
			"image/x-adobe-dng"	=> "dng"
		),
		"TRANSFORMATIONS" => array(
			"SCALE" 			=> array("width", "height", "mode", "antialiasing", "trim_edges", "crop_from"),
			"ANNOTATE"			=> array("text", "font", "size", "color", "position", "inset"),
			"WATERMARK"			=> array("image", "width", "height", "position", "opacity"),
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
			'gamma'				=> 'W',
			'reference-black'	=> 'W',
			'reference-white'	=> 'W',
			'version'			=> 'W'	// required of all plug-ins
		),
		
		"NAME" => "CoreImage"
	);
	
	var $typenames = array(
		"image/jpeg" 		=> "JPEG",
		"image/gif" 		=> "GIF",
		"image/tiff" 		=> "TIFF",
		"image/png" 		=> "PNG",
		"image/x-bmp" 		=> "Windows Bitmap (BMP)",
		"image/x-psd" 		=> "Photoshop",
		"image/tilepic" 	=> "Tilepic",
		"image/jp2"			=> "JPEG-2000",
		"image/x-adobe-dng"	=> "Adobe DNG"
	);
	
	var $apple_type_names = array(
		"image/jpeg" 		=> "jpeg",
		"image/gif" 		=> "gif",
		"image/tiff" 		=> "tiff",
		"image/png" 		=> "png",
		"image/x-bmp" 		=> "bmp",
		"image/x-psd" 		=> "psd",
		"image/tilepic" 	=> "tpc",
		"image/jp2"			=> "jp2"
	);
	
	var $apple_UTIs = array(
		"image/jpeg" 		=> "public.jpeg",
		"image/gif" 		=> "com.compuserve.gif",
		"image/tiff" 		=> "public.tiff",
		"image/png" 		=> "public.png",
		"image/x-bmp" 		=> "com.microsoft.bmp",
		"image/x-psd" 		=> "com.adobe.photoshop.image",
		"image/tilepic" 	=> "public.tpc",
		"image/jp2"			=> "public.jpeg-2000"
	);

	
	# ------------------------------------------------
	public function __construct() {
		$this->opo_config = Configuration::load();
		$this->description = _t('Provides CoreImage-based image processing and conversion services via the command-line CoreImageTool (Mac OS X 10.4+ only). This can provide a significant performance boost when using Macintosh servers.');
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		// get config for external apps
		$vs_external_app_config_path = $this->opo_config->get('external_applications');
		$this->opo_external_app_config = Configuration::load($vs_external_app_config_path);
		$this->ops_CoreImage_path = $this->opo_external_app_config->get('coreimagetool_app');
		
		
		if (!caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
			return null;	// don't use if CoreImage executables are unavailable
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
			if (!caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
				$va_status['errors'][] = _t("Didn't load because CoreImageTool executable cannot be found");
			}
			
		}
		
		return $va_status;
	}
	# ------------------------------------------------
	public function divineFileFormat($filepath) {
		$vs_mimetype = $this->_CoreImageIdentify($filepath);
		return ($vs_mimetype) ? $vs_mimetype : '';
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
					$this->postError(1650, _t("Tile size property must be between 10 and 10000"), "WLPlugCoreImage->set()");
					return '';
				}
				$this->properties["tile_width"] = $value;
				$this->properties["tile_height"] = $value;
			} else {
				if ($this->info["PROPERTIES"][$property]) {
					switch($property) {
						case 'quality':
							if (($value < 1) || ($value > 100)) {
								$this->postError(1650, _t("Quality property must be between 1 and 100"), "WLPlugCoreImage->set()");
								return '';
							}
							$this->properties["quality"] = $value;
							break;
						case 'tile_width':
							if (($value < 10) || ($value > 10000)) {
								$this->postError(1650, _t("Tile width property must be between 10 and 10000"), "WLPlugCoreImage->set()");
								return '';
							}
							$this->properties["tile_width"] = $value;
							break;
						case 'tile_height':
							if (($value < 10) || ($value > 10000)) {
								$this->postError(1650, _t("Tile height property must be between 10 and 10000"), "WLPlugCoreImage->set()");
								return '';
							}
							$this->properties["tile_height"] = $value;
							break;
						case 'antialiasing':
							if (($value < 0) || ($value > 100)) {
								$this->postError(1650, _t("Antialiasing property must be between 0 and 100"), "WLPlugCoreImage->set()");
								return '';
							}
							$this->properties["antialiasing"] = $value;
							break;
						case 'layer_ratio':
							if (($value < 0.1) || ($value > 10)) {
								$this->postError(1650, _t("Layer ratio property must be between 0.1 and 10"), "WLPlugCoreImage->set()");
								return '';
							}
							$this->properties["layer_ratio"] = $value;
							break;
						case 'layers':
							if (($value < 1) || ($value > 25)) {
								$this->postError(1650, _t("Layer property must be between 1 and 25"), "WLPlugCoreImage->set()");
								return '';
							}
							$this->properties["layers"] = $value;
							break;	
						case 'tile_mimetype':
							if ((!($this->info["EXPORT"][$value])) && ($value != "image/tilepic")) {
								$this->postError(1650, _t("Tile output type '%1' is invalid", $value), "WLPlugCoreImage->set()");
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
					$this->postError(1650, _t("Can't set property %1", $property), "WLPlugCoreImage->set()");
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
				$this->postError(1610, _t("Filenames with colons (:) are not allowed"), "WLPlugCoreImage->read()");
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
					
					if ($this->filepath_conv) { @unlink($this->filepath_conv); }
					return 1;
				} else {
					$this->postError(1610, $this->handle->error, "WLPlugCoreImage->read()");
					return false;
				}
			} else {
				$this->handle = "";
				$this->filepath = "";
				
				
				
				$handle = $this->_CoreImageRead(($this->filepath_conv) ? $this->filepath_conv : $filepath);
				if ($handle) {
					$this->handle = $handle;
					$this->filepath = $filepath;
					$this->metadata = array();
					
					$va_raw_metadata = $this->_CoreImageGetMetadata(($this->filepath_conv) ? $this->filepath_conv : $filepath);
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
			$this->postError(1655, _t("Invalid transformation %1", $operation), "WLPlugCoreImage->transform()");
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
			$this->postError(1610, _t("Filenames with colons (:) are not allowed"), "WLPlugCoreImage->write()");
			return false;
		}
		if ($mimetype == "image/tilepic") {
			if ($this->properties["mimetype"] == "image/tilepic") {
				copy($this->filepath, $filepath);
			} else {
				$tp = new TilepicParser();
				if (!($properties = $tp->encode(($this->filepath_conv) ? $this->filepath_conv : $this->filepath, $filepath, 
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
					
			if (!$this->_CoreImageWrite($this->handle, $filepath.".".$ext, $mimetype, $this->properties["quality"])) {
				$this->postError(1610, _t("%1: %2", $reason, $description), "WLPlugCoreImage->write()");
				return false;
			}
			
			# update mimetype
			$this->properties["mimetype"] = $mimetype;
			$this->properties["typename"] = $this->typenames[$mimetype];
			
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
	public function appleTypeToMimeType($ps_apple_type) {
		foreach($this->apple_type_names as $vs_mimetype => $vs_apple_type) {
			if ($ps_apple_type == $vs_apple_type) {
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
		if ($this->filepath_conv) { @unlink($this->filepath_conv); }
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
	private function _CoreImageIdentify($ps_filepath) {
		if (caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
			$va_info = explode(':', shell_exec("sips --getProperty format \"{$ps_filepath}\""));
			return $this->appleTypeToMimeType(trim(array_pop($va_info)));
		}
		return null;
	}
	# ------------------------------------------------
	private function _CoreImageGetMetadata($ps_filepath) {
		if (caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
			$va_metadata = array();
			return $va_metadata;
		}
		return null;
	}
	# ------------------------------------------------
	private function _CoreImageRead($ps_filepath) {
		if (caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
			$vs_output = shell_exec('sips --getProperty format --getProperty space --getProperty bitsPerSample --getProperty pixelWidth --getProperty pixelHeight --getProperty dpiWidth --getProperty dpiHeight "'.$ps_filepath."\" 2> /dev/null");
			
			$va_tmp = explode("\n", $vs_output);
			
			array_shift($va_tmp);
			
			$va_properties = array();
			foreach($va_tmp as $vs_line) {
				$va_line_tmp = explode(':', $vs_line);
				$va_properties[trim($va_line_tmp[0])] = trim($va_line_tmp[1]);
			}
			
			return array(
				'mimetype' => $this->appleTypeToMimeType($va_properties['format']),
				'magick' => $va_properties['format'],
				'width' => $va_properties['pixelWidth'],
				'height' => $va_properties['pixelHeight'],
				'colorspace' => $va_properties['space'],
				'depth' => $va_properties['bitsPerSample'],
				'resolution' => array(
					'x' => $va_properties['dpiWidth'],
					'y' => $va_properties['dpiHeight']
				),
				'ops' => array(),
				'filepath' => $ps_filepath
			);
		}
		return null;
	}
	# ------------------------------------------------
	private function _CoreImageWrite($pa_handle, $ps_filepath, $ps_mimetype, $pn_quality=null) {
		if (caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
			$va_ops = array();	
			foreach($pa_handle['ops'] as $va_op) {
				switch($va_op['op']) {
					case 'annotation':
						// TODO: watermarking and annotation is not currrently supported in this plugin
						
						//$vs_op = '-gravity '.$va_op['position'].' -fill '.str_replace('#', '\\#', $va_op['color']).' -pointsize '.$va_op['size'].' -draw "text '.$va_op['inset'].','.$va_op['inset'].' \''.$va_op['text'].'\'"';
						
						//if ($va_op['font']) {
						//	$vs_op .= ' -font '.$va_op['font'];
						//}
						//$va_ops['convert'][] = $vs_op;
						break;
					case 'watermark':
						// TODO: watermarking and annotation is not currrently supported in this plugin
						
						//$vs_op = "-dissolve ".($va_op['opacity'] * 100)." -gravity ".$va_op['position']." ".$va_op['watermark_image']; //"  -geometry ".$va_op['watermark_width']."x".$va_op['watermark_height']; [Seems to be interpreted as scaling the image being composited on as of at least v6.5.9; so we don't scale watermarks in CoreImage... we just use the native size]
						//$va_ops['composite'][] = $vs_op;
						break;
					case 'size':
						if ($va_op['width'] < 1) { break; }
						if ($va_op['height'] < 1) { break; }
						
						$vn_scale = $va_op['width']/$this->handle['width'];
						$va_ops[] = "filter image CILanczosScaleTransform scale={$vn_scale}:aspectRatio=1";
						break;
					case 'crop':
						if ($va_op['width'] < 1) { break; }
						if ($va_op['height'] < 1) { break; }
						if ($va_op['x'] < 0) { break; }
						if ($va_op['y'] < 0) { break; }
						
						$va_ops[] = "filter image CICrop rectangle=".join(",", array($va_op['x'], $va_op['y'], $va_op['width'], $va_op['height']));
						break;
					case 'rotate':
						if (!is_numeric($va_op['angle'])) { break; }
						
						// TODO: stop being lazy and implement the math to convert a rotational angle into the transform matrix
						// we need to pass to CIAffineTransform; for now this plugin doesn't support image rotation
						//$va_ops[] = "filter image CIAffineTransform transform=1,0,0,1,0,0";
						break;
					case 'filter_despeckle':
						// TODO: see if this works nicely... just using default values
						$va_ops[] = "filter image CINoiseReduction inputNoiseLevel=0.2:inputSharpness=0.4";
						break;
					case 'filter_median':
						if ($va_op['radius'] < 0) { break; }
						// NOTE: CoreImage Median doesn't take a radius, unlike ImageMagick's
						$va_ops[] = "filter image CIMedianFilter ";
						break;
					case 'filter_unsharp_mask':
					case 'filter_sharpen':
						if ($va_op['radius'] < 0) { break; }
						
						$vn_radius = $va_op['radius'];
						if(!($vn_intensity = $va_op['amount'])) {
							$vn_intensity = 1;
						}
						
						$va_ops[] = "filter image CIUnsharpMask radius={$vn_radius}:intensity={$vn_intensity}";
						break;
				}
			}
			
			$vs_input_file = $pa_handle['filepath'];
			if (is_array($va_ops) && sizeof($va_ops)) {
				array_unshift($va_ops, "load image \"{$vs_input_file}\"");
				array_push($va_ops, "store image \"{$ps_filepath}\" ".$this->apple_UTIs[$ps_mimetype]);
				//print "<hr>".join(" ", $va_ops)."<hr>";
				exec($this->ops_CoreImage_path." ".join(" ", $va_ops));
				
				$vs_input_file = $ps_filepath;
			}
			
			return true;
		}
		return null;
	}
	# ------------------------------------------------
}
# ----------------------------------------------------------------------
?>
