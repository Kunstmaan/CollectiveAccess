<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/MagickWand.php :
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
 * Plugin for processing images using ImageMagick via the MagickWand PHP extension
 */

include_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/core/Parsers/TilepicParser.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");

class WLPlugMediaMagickWand Extends WLPlug Implements IWLPlugMedia {
	var $errors = array();
	
	var $filepath;
	var $filepath_conv;		// filepath for converted input file;  used for handling of camera RAW files where we have to convert to TIFF using dcraw app before using Magick to convert to whatever
	var $handle;
	var $ohandle;
	var $properties;
	var $metadata = array();
	
	var $opo_config;
	var $opo_external_app_config;
	var $ops_dcraw_path;
	
	var $info = array(
		"IMPORT" => array(
			"image/jpeg" 		=> "jpg",
			"image/gif" 		=> "gif",
			"image/tiff" 		=> "tiff",
			"image/png" 		=> "png",
			"image/x-bmp" 		=> "bmp",
			"image/x-psd" 		=> "psd",
			"image/tilepic" 	=> "tpc",
			"image/x-dcraw"		=> "crw",
			"image/x-dpx"		=> "dpx",
			"image/x-exr"		=> "exr",
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
			"image/x-dcraw"		=> "crw",
			"image/x-dpx"		=> "dpx",
			"image/x-exr"		=> "exr",
			"image/jp2"			=> "jp2",
			"image/x-adobe-dng"	=> "dng"
		),
		"TRANSFORMATIONS" => array(
			"SCALE" 			=> array("width", "height", "mode", "antialiasing"),
			"ANNOTATE"			=> array("text", "font", "size", "color", "position", "inset"),
			"WATERMARK"			=> array("image", "width", "height", "position", "opacity"),
			"ROTATE" 			=> array("angle"),
			"SET" 				=> array("property", "value"),
			
			# --- filters
			"MEDIAN"			=> array("radius"),
			"DESPECKLE"			=> array(""),
			"SHARPEN"			=> array("radius", "sigma"),
			"UNSHARPEN_MASK"	=> array("radius", "sigma", "amount", "threshold"),
            "MAX_SCALE" 		=> array("width", "height"),
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
		
		"NAME" => "MagickWand"
	);
	
	var $typenames = array(
		"image/jpeg" 		=> "JPEG",
		"image/gif" 		=> "GIF",
		"image/tiff" 		=> "TIFF",
		"image/png" 		=> "PNG",
		"image/x-bmp" 		=> "Windows Bitmap (BMP)",
		"image/x-psd" 		=> "Photoshop",
		"image/tilepic" 	=> "Tilepic",
		"image/x-dcraw"		=> "Camera RAW",
		"image/x-dpx"		=> "DPX",
		"image/x-exr"		=> "OpenEXR",
		"image/jp2"			=> "JPEG-2000",
		"image/x-adobe-dng"	=> "Adobe DNG"
	);
	
	var $magick_names = array(
		"image/jpeg" 		=> "JPEG",
		"image/gif" 		=> "GIF",
		"image/tiff" 		=> "TIFF",
		"image/png" 		=> "PNG",
		"image/x-bmp" 		=> "BMP",
		"image/x-psd" 		=> "PSD",
		"image/tilepic" 	=> "TPC",
		"image/x-dcraw"		=> "cRAW",
		"image/x-dpx"		=> "DPX",
		"image/x-exr"		=> "EXR",
		"image/jp2"			=> "JP2",
		"image/x-adobe-dng"	=> "DNG"
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
		"image/x-png" 		=> "image/png",
		"image/dpx" 		=> "image/x-dpx",
		"image/exr" 		=> "image/x-exr",
		"image/jpx"			=> "image/jp2",
		"image/jpm"			=> "image/jp2",
		"image/dng"			=> "image/x-adobe-dng"
	);
	
	
	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Provides image processing and conversion services using ImageMagick via the MagickWand PHP extension');
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		$this->opo_config = Configuration::load();
		$vs_external_app_config_path = $this->opo_config->get('external_applications');
		$this->opo_external_app_config = Configuration::load($vs_external_app_config_path);
		$this->ops_dcraw_path = $this->opo_external_app_config->get('dcraw_app');
		$this->ops_CoreImage_path = $this->opo_external_app_config->get('coreimagetool_app');
		
		if (caMediaPluginCoreImageInstalled($this->ops_CoreImage_path)) {
			return null;	// don't use if CoreImage executable are available
		}
		
		if (caMediaPluginImagickInstalled()) {	
			return null;	// don't use MagickWand if Imagick is available
		} 
		if (!caMediaPluginMagickWandInstalled()) {
			return null;	// don't use if MagickWand functions are unavailable
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
			} else { 
				if (!caMediaPluginMagickWandInstalled()) {
					$va_status['errors'][] = _t("Didn't load because MagickWand is not available");
				}
			}
		}
		
		if (!caMediaPluginDcrawInstalled($this->ops_dcraw_path)) {
			$va_status['warnings'][] = _t("RAW image support is not enabled because DCRAW cannot be found");
		}
		
		return $va_status;
	}
	# ------------------------------------------------
	public function divineFileFormat($filepath) {
		# is it a camera raw image?
		if (caMediaPluginDcrawInstalled($this->ops_dcraw_path)) {
			exec(escapeshellcmd($this->ops_dcraw_path." -i ".$filepath)." 2> /dev/null", $va_output, $vn_return);
			if ($vn_return == 0) {
				if ((!preg_match("/^Cannot decode/", $va_output[0])) && (!preg_match("/Master/i", $va_output[0]))) {
					return 'image/x-dcraw';
				}
			}
		}
		
		if(!strpos($filepath, ':') || (caGetOSFamily() == OS_WIN32)) {
			// ImageMagick bails when a colon is in the file name... catch it here
			$r_handle = NewMagickWand();
			if ($filepath != '' && MagickPingImage($r_handle, $filepath)) {
				$mimetype = $this->_getMagickImageMimeType($r_handle);
				
				if (($mimetype) && $this->info["IMPORT"][$mimetype]) {
					return $mimetype;
				} else {
					return '';
				}
			} 
		} else {
			$this->postError(1610, _t("Filenames with colons (:) are not allowed"), "WLPlugMagickWand->divineFileFormat()");
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
	public function _getMagickImageMimeType($pr_handle) {
		$mimetype = MagickGetImageMimeType($pr_handle);
		if ($this->magick_mime_map[$mimetype]) {
			$mimetype = $this->magick_mime_map[$mimetype];
		}
		return $mimetype;
	}
	# ----------------------------------------------------------
	public function get($property) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property]) {
				return $this->properties[$property];
			} else {
				//print "Invalid property";
				return "";
			}
		} else {
			return "";
		}
	}
	# ----------------------------------------------------------
	public function set($property, $value) {
		if ($this->handle) {
			if ($property == "tile_size") {
				if (($value < 10) || ($value > 10000)) {
					$this->postError(1650, _t("Tile size property must be between 10 and 10000"), "WLPlugMagickWand->set()");
					return "";
				}
				$this->properties["tile_width"] = $value;
				$this->properties["tile_height"] = $value;
			} else {
				if ($this->info["PROPERTIES"][$property]) {
					switch($property) {
						case 'quality':
							if (($value < 1) || ($value > 100)) {
								$this->postError(1650, _t("Quality property must be between 1 and 100"), "WLPlugMagickWand->set()");
								return "";
							}
							$this->properties["quality"] = $value;
							break;
						case 'tile_width':
							if (($value < 10) || ($value > 10000)) {
								$this->postError(1650, _t("Tile width property must be between 10 and 10000"), "WLPlugMagickWand->set()");
								return "";
							}
							$this->properties["tile_width"] = $value;
							break;
						case 'tile_height':
							if (($value < 10) || ($value > 10000)) {
								$this->postError(1650, _t("Tile height property must be between 10 and 10000"), "WLPlugMagickWand->set()");
								return "";
							}
							$this->properties["tile_height"] = $value;
							break;
						case 'antialiasing':
							if (($value < 0) || ($value > 100)) {
								$this->postError(1650, _t("Antialiasing property must be between 0 and 100"), "WLPlugMagickWand->set()");
								return "";
							}
							$this->properties["antialiasing"] = $value;
							break;
						case 'layer_ratio':
							if (($value < 0.1) || ($value > 10)) {
								$this->postError(1650, _t("Layer ratio property must be between 0.1 and 10"), "WLPlugMagickWand->set()");
								return "";
							}
							$this->properties["layer_ratio"] = $value;
							break;
						case 'layers':
							if (($value < 1) || ($value > 25)) {
								$this->postError(1650, _t("Layer property must be between 1 and 25"), "WLPlugMagickWand->set()");
								return "";
							}
							$this->properties["layers"] = $value;
							break;	
						case 'tile_mimetype':
							if ((!($this->info["EXPORT"][$value])) && ($value != "image/tilepic")) {
								$this->postError(1650, _t("Tile output type '%1' is invalid", $value), "WLPlugMagickWand->set()");
								return "";
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
								return "";
							}
							break;
					}
				} else {
					# invalid property
					$this->postError(1650, _t("Can't set property %1", $property), "WLPlugMagickWand->set()");
					return "";
				}
			}
		} else {
			return "";
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
			
			if ($this->filepath_conv) { @unlink($this->filepath_conv); }
			
			if(strpos($filepath, ':') && (caGetOSFamily() != OS_WIN32)) {
				$this->postError(1610, _t("Filenames with colons (:) are not allowed"), "WLPlugMagickWand->read()");
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
					$this->postError(1610, $this->handle->error, "WLPlugMagickWand->read()");
					return false;
				}
			} else {
				$this->handle = "";
				$this->filepath = "";
				$handle = NewMagickWand();
				
				if ($mimetype == 'image/x-dcraw') {
					if (!caMediaPluginDcrawInstalled($this->ops_dcraw_path)) {
						$this->postError(1610, _t("Could not convert Camera RAW format file because conversion tool (dcraw) is not installed"), "WLPlugMagickWand->read()");
						return false;
					}
					
					$vs_tmp_name = tempnam("/tmp", "rawtmp");
					$vb_is_error = false;
					if (!copy($filepath, $vs_tmp_name)) {
						$this->postError(1610, _t("Could not copy Camera RAW file to temporary directory"), "WLPlugMagickWand->read()");
						return false;
					}
					
					//
					// Since dcraw sometimes identifies files as RAW that it cannot convert
					// we try to convert files identified as RAW to TIFF, and it that fails we then pass 
					// the original onto ImageMagick and hope it works ok - it usually does :-)
					//
					exec(escapeshellcmd($this->ops_dcraw_path." -T ".$vs_tmp_name), $va_output, $vn_return);
					if ($vn_return != 0) {
						//$this->postError(1610, _t("Camera RAW file conversion failed"), "WLPlugMagickWand->read()");
						//return false;
						$vb_is_error = true;
					}
					if (!(file_exists($vs_tmp_name.'.tiff') && (filesize($vs_tmp_name.'.tiff') > 0))) {
						//$this->postError(1610, _t("Translation from Camera RAW to TIFF failed"), "WLPlugMagickWand->read()");
						//return false;
						$vb_is_error = true;
					}
					@unlink($vs_tmp_name);
 					if ($this->filepath_conv) { @unlink($this->filepath_conv); }
					
					if (!$vb_is_error) {
						$this->filepath_conv = $vs_tmp_name.'.tiff';
					}
				}
				
				if (MagickReadImage($handle, ($this->filepath_conv) ? $this->filepath_conv : $filepath)) {
					if (WandHasException( $handle )) {
						$reason      = WandGetExceptionType( $handle ) ;
						$description = WandGetExceptionString( $handle ) ;
						$this->postError(1610, _t("%1: %2", $reason, $description), "WLPlugMagickWand->read()");
						return false;
					}
					$this->handle = $handle;
					$this->filepath = $filepath;
					$this->metadata = array();
					
					if (function_exists('MagickGetImageProperties')) {
						$va_raw_metadata = MagickGetImageProperties($this->handle);
						foreach($va_raw_metadata as $vs_tag) {
							if (sizeof($va_tmp = explode(':', $vs_tag)) > 1) {
								$vs_type = strtoupper($va_tmp[0]);
								$vs_prop_name = $va_tmp[1];
							} else {
								$vs_type = 'GENERIC';
								$vs_prop_name = $vs_tag;
							}
							
							$this->metadata['METADATA_'.$vs_type][$vs_prop_name] = MagickGetImageProperty($this->handle, $vs_tag);
						}
					}
					
					# load image properties
					$this->properties["width"] = MagickGetImageWidth($this->handle);
					$this->properties["height"] = MagickGetImageHeight($this->handle);
					$this->properties["quality"] = "";
					$this->properties["filesize"] = MagickGetImageSize($this->handle);
					$this->properties["bitdepth"] = MagickGetImageDepth($this->handle);
					$this->properties["resolution"] = MagickGetImageResolution($this->handle);
					$this->properties["colorspace"] = $this->_getColorspaceAsString(MagickGetImageColorspace($this->handle));
					
					// force all images to true color (takes care of GIF transparency for one thing...)
					MagickSetImageType($this->handle, MW_TrueColorType);

					if (!MagickSetImageColorspace( $this->handle, MW_RGBColorspace)) {
						$reason      = WandGetExceptionType( $this->handle ) ;
						$description = WandGetExceptionString( $this->handle ) ;
						$this->postError(1610, _t("%1: %2 during RGB colorspace transformation operation", $reason, $description), "WLPlugMagickWand->read()");
						return false;
					}
					
					if ($mimetype != 'image/x-dcraw') {
						$this->properties["mimetype"] = $this->_getMagickImageMimeType($this->handle);
						$this->properties["typename"] = MagickGetImageFormat($this->handle);
					} else {
						$this->properties["mimetype"] = 'image/x-dcraw';
						$this->properties["typename"] = 'Camera RAW';
					}
					$this->ohandle = CloneMagickWand($this->handle);
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
			$this->postError(1655, _t("Invalid transformation %1", $operation), "WLPlugMagickWand->transform()");
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
				$d = NewDrawingWand();
				if ($parameters['font']) { DrawSetFont($d,$parameters['font']); }
				
				$size = ($parameters['size'] > 0) ? $parameters['size']: 18;
				DrawSetFontSize($d, $size);
			
				$inset = ($parameters['inset'] > 0) ? $parameters['inset']: 0;
				$pw=NewPixelWand();
				PixelSetColor($pw, $parameters['color'] ? $parameters['color'] : "black");
				DrawSetFillColor($d,$pw);
				
				switch($parameters['position']) {
					case 'north_east':
						DrawSetGravity($d,MW_NorthEastGravity);
						break;
					case 'north_west':
						DrawSetGravity($d,MW_NorthWestGravity);
						break;
					case 'north':
						DrawSetGravity($d,MW_NorthGravity);
						break;
					case 'south_east':
						DrawSetGravity($d,MW_SouthEastGravity);
						break;
					case 'south':
						DrawSetGravity($d,MW_SouthGravity);
						break;
					case 'center':
						DrawSetGravity($d,MW_CenterGravity);
						break;
					case 'south_west':
					default:
						DrawSetGravity($d,MW_SouthWestGravity);
						break;
				}
				MagickAnnotateImage($this->handle,$d,$inset, $size + $inset, 0, $parameters['text']);
				break;
			# -----------------------
			case 'WATERMARK':
				if (!file_exists($parameters['image'])) { break; }
				$vn_opacity_setting = $parameters['opacity'];
				if (($vn_opacity_setting < 0) || ($vn_opacity_setting > 1)) {
					$vn_opacity_setting = 0.5;
				}
				$d = NewDrawingWand();
				
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
				
				$vn_opacity = @MagickGetQuantumRange() - (@MagickGetQuantumRange() * $vn_opacity_setting );
				if ($vn_opacity < 0) {
					$vn_opacity = 0.5;
				} else {
					if ($vn_opacity > @MagickGetQuantumRange()) {
						$vn_opacity = 0.5;
					}
				}
				
				$w = NewMagickWand();
				if (!MagickReadImage($w, $parameters['image'])) {
					$this->postError(1610, _t("Couldn't load watermark image at %1", $parameters['image']), "WLPlugMagickWand->transform:WATERMARK()");
					return false;
				}
				MagickEvaluateImage($w, MW_SubtractEvaluateOperator, $vn_opacity, MW_OpacityChannel) ;
				
				DrawComposite($d, MW_DissolveCompositeOp,$vn_watermark_x,$vn_watermark_y,$vn_watermark_width,$vn_watermark_height, $w);
				MagickDrawImage($this->handle, $d);
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
					if (!MagickResizeImage( $this->handle, $w + ($crop_w_edge * 2), $h + ($crop_h_edge * 2), MW_CubicFilter, $aa)) {
							$reason      = WandGetExceptionType( $this->handle ) ;
							$description = WandGetExceptionString( $this->handle ) ;
							$this->postError(1610, _t("%1: %2 during resize operation", $reason, $description), "WLPlugMagickWand->transform()");
							return false;
					}
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
						if (!MagickCropImage( $this->handle, $parameters["width"], $parameters["height"], $crop_w_edge + $crop_from_offset_x, $crop_h_edge + $crop_from_offset_y )) {
							$reason      = WandGetExceptionType( $this->handle ) ;
							$description = WandGetExceptionString( $this->handle ) ;
							$this->postError(1610, _t("%1: %2 during crop operation", $reason, $description), "WLPlugMagickWand->transform()");
							return false;
						}
						$this->properties["width"] = $parameters["width"];
						$this->properties["height"] = $parameters["height"];
					} else {
						if ($crop_w_edge || $crop_h_edge) {
							if (!MagickCropImage( $this->handle, $w, $h, $crop_w_edge, $crop_h_edge )) {
								$reason      = WandGetExceptionType( $this->handle ) ;
								$description = WandGetExceptionString( $this->handle ) ;
								$this->postError(1610, _t("%1: %2 during crop operation", $reason, $description), "WLPlugMagickWand->transform()");
								return false;
							}
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
				$pixel = NewPixelWand("#FFFFFF");
				if ( !MagickRotateImage( $this->handle, $pixel, $angle ) ) {
					$reason      = WandGetExceptionType( $this->handle ) ;
					$description = WandGetExceptionString( $this->handle ) ;
					$this->postError(1610, _t("%1: %2", $reason, $description), "WLPlugMagickWand->transform()");
					
					DestroyPixelWand($pixel);
					return false;
				} else {
					DestroyPixelWand($pixel);
				}
			}
			break;
		# -----------------------
		case "DESPECKLE":
			$radius = $parameters["radius"];
			if ( !MagickDespeckleImage( $this->handle) ) {
				$reason      = WandGetExceptionType( $this->handle ) ;
				$description = WandGetExceptionString( $this->handle ) ;
				$this->postError(1610, _t("%1: %2", $reason, $description), "WLPlugMagickWand->transform:DESPECKLE()");
				return false;
			}
			break;
		# -----------------------
		case "MEDIAN":
			$radius = $parameters["radius"];
			if ($radius < .1) { $radius = 1; }
			if ( !MagickMedianFilterImage( $this->handle, $radius) ) {
				$reason      = WandGetExceptionType( $this->handle ) ;
				$description = WandGetExceptionString( $this->handle ) ;
				$this->postError(1610,  _t("%1: %2", $reason, $description), "WLPlugMagickWand->transform:MEDIAN()");
				return false;
			}
			break;
		# -----------------------
		case "SHARPEN":
			$radius = $parameters["radius"];
			if ($radius < .1) { $radius = 1; }
			$sigma = $parameters["sigma"];
			if ($sigma < .1) { $sigma = 1; }
			if ( !MagickSharpenImage( $this->handle, $radius, $sigma) ) {
				$reason      = WandGetExceptionType( $this->handle ) ;
				$description = WandGetExceptionString( $this->handle ) ;
				$this->postError(1610,  _t("%1: %2", $reason, $description), "WLPlugMagickWand->transform:SHARPEN()");
				return false;
			}
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
			if ( !MagickUnsharpMaskImage( $this->handle, $radius, $sigma, $amount, $threshold) ) {
				$reason      = WandGetExceptionType( $this->handle ) ;
				$description = WandGetExceptionString( $this->handle ) ;
				$this->postError(1610,  _t("%1: %2", $reason, $description), "WLPlugMagickWand->transform:UNSHARPEN_MASK()");
				return false;
			}
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
			if ($cw < $w && $ch < $h) {
				if (!MagickResizeImage($this->handle, $cw , $ch , MW_CubicFilter, $aa)) {
                    $reason = WandGetExceptionType($this->handle);
                    $description = WandGetExceptionString($this->handle);
                    $this->postError(1610, _t("%1: %2 during resize operation", $reason, $description), "WLPlugMagickWand->transform()");
                    return false;
				}
				$this->properties["width"] = $cw;
				$this->properties["height"] = $ch;
			} else {
				$aspect_ratio = $cw / $ch;
				$screen_ratio = $w / $h;

				if ($aspect_ratio < $screen_ratio) {
					$w = $aspect_ratio * $h;
				} else {
					$h = $w / $aspect_ratio;
				}

				$w = round($w);
				$h = round($h);

				if (!MagickResizeImage($this->handle, $w , $h , MW_CubicFilter, $aa)) {
                    $reason = WandGetExceptionType($this->handle);
                    $description = WandGetExceptionString($this->handle);
                    $this->postError(1610, _t("%1: %2 during resize operation", $reason, $description), "WLPlugMagickWand->transform()");
                    return false;
				}
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
			$this->postError(1610, _t("Filenames with colons (:) are not allowed"), "WLPlugMagickWand->write()");
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
			
			MagickSetImageFormat($this->handle, $this->magick_names[$mimetype]);
			# set quality
			if (($this->properties["quality"]) && ($this->properties["mimetype"] != "image/tiff")){ 
				MagickSetImageCompressionQuality($this->handle, $this->properties["quality"]);
			}
			
			MagickSetImageBackgroundColor($this->handle, NewPixelWand("#CC0000"));
			MagickSetImageMatteColor($this->handle, NewPixelWand("#CC0000"));
			
			if ($this->properties['gamma']) {
				if (!$this->properties['reference-black']) { $this->properties['reference-black'] = 0; }
				if (!$this->properties['reference-white']) { $this->properties['reference-white'] = 65535; }
				MagickLevelImage($this->handle, $this->properties['reference-black'], $this->properties['gamma'], $this->properties['reference-white']);
			}
					
			# write the file
			if ( !MagickWriteImage( $this->handle, $filepath.".".$ext ) ) {
				# error
				$reason      = WandGetExceptionType( $this->handle ) ;
				$description = WandGetExceptionString( $this->handle ) ;
			
				$this->postError(1610, _t("%1: %2", $reason, $description), "WLPlugMagickWand->write()");
				return false;
			}
			
			# update mimetype
			$this->properties["mimetype"] = $mimetype;
			$this->properties["typename"] = MagickGetImageFormat($this->handle);
			
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
		return "";
	}
	# ------------------------------------------------
	public function mimetype2typename($mimetype) {
		return $this->typenames[$mimetype];
	}
	# ------------------------------------------------
	private function _getColorspaceAsString($pn_colorspace) {
		switch($pn_colorspace) {
			case MW_UndefinedColorspace:
				$vs_colorspace = 'UNDEFINED';
				break;
			case MW_RGBColorspace:
				$vs_colorspace = 'RGB';
				break;
			case MW_GRAYColorspace:
				$vs_colorspace = 'GRAY';
				break;
			case MW_TransparentColorspace:
				$vs_colorspace = 'TRANSPARENT';
				break;
			case MW_OHTAColorspace:
				$vs_colorspace = 'OHTA';
				break;
			case MW_LABColorspace:
				$vs_colorspace = 'LAB';
				break;
			case MW_XYZColorspace:
				$vs_colorspace = 'XYZ';
				break;
			case MW_YCbCrColorspace:
				$vs_colorspace = 'YCBCR';
				break;
			case MW_YCCColorspace:
				$vs_colorspace = 'YCC';
				break;
			case MW_YIQColorspace:
				$vs_colorspace = 'YIQ';
				break;
			case MW_YPbPrColorspace:
				$vs_colorspace = 'YPBPR';
				break;
			case MW_YUVColorspace:
				$vs_colorspace = 'YUV';
				break;
			case MW_CMYKColorspace:
				$vs_colorspace = 'CMYK';
				break;
			case MW_sRGBColorspace:
				$vs_colorspace = 'SRGB';
				break;
			case MW_HSBColorspace:
				$vs_colorspace = 'HSB';
				break;
			case MW_HSLColorspace:
				$vs_colorspace = 'HSL';
				break;
			case MW_HWBColorspace:
				$vs_colorspace = 'HWB';
				break;
			default:
				$vs_colorspace = 'UNKNOWN';
				break;
		}
		return $vs_colorspace;
	}
	# ------------------------------------------------
	public function reset() {
		if ($this->ohandle) {
			$this->handle = CloneMagickWand($this->ohandle);
			# load image properties
			$this->properties["width"] = MagickGetImageWidth($this->handle);
			$this->properties["height"] = MagickGetImageHeight($this->handle);
			$this->properties["quality"] = "";
			$this->properties["mimetype"] = $this->_getMagickImageMimeType($this->handle);
			$this->properties["typename"] = MagickGetImageFormat($this->handle);
			return 1;
		}
		return false;
	}
	# ------------------------------------------------
	public function init() {
		unset($this->handle);
		unset($this->ohandle);
		unset($this->properties);
		unset($this->filepath);
		$this->errors = array();
	}
	# ------------------------------------------------
	public function cleanup() {
		$this->destruct();
	}
	# ------------------------------------------------
	public function destruct() {
		if (is_object($this->handle)) { DestroyMagickWand($this->handle); }
		if (is_object($this->ohandle)) { DestroyMagickWand($this->ohandle); }
		if ($this->filepath_conv) { @unlink($this->filepath_conv); }
	}
	# ------------------------------------------------
	public function htmlTag($url, $properties, $options=null, $pa_volume_info=null) {
		if (!is_array($options)) { $options = array(); }
		
		foreach(array(
			'name', 'url', 'viewer_width', 'viewer_height', 'idname',
			'viewer_base_url', 'viewer_theme_url', 'width', 'height',
			'vspace', 'hspace', 'alt', 'title', 'usemap', 'align', 'border', 'class', 'style',
            'annotate', 'object_id', 'addButtonClassName',
			
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
            $viewer_theme_url =			$options["viewer_theme_url"];
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
			
			if ($options["annotate"]) {
				$annotate = $options['annotate'];
			} else {
				$annotate = false;
			}

			$viewer_base_url = $options["viewer_base_url"];
			$viewer_theme_url = $options["viewer_theme_url"];
			$request = $options["request"];
			
			if (!isset($properties["width"])) $properties["width"] = 100;
			if (!isset($properties["height"])) $properties["height"] = 100;
					
			if (($url) && ($properties["width"] > 0) && ($properties["height"] > 0)) {
				$tag = "";
				if ($annotate) {
					JavascriptLoadManager::register('annotate');
					$object_id = $options["object_id"];
					$rep  = new ca_object_representations($object_id);
					$media_info = $rep->get('media');

					$original_width = $media_info["original"]["PROPERTIES"]["width"];
					$original_height = $media_info["original"]["PROPERTIES"]["height"];
					$resized_width = $properties["width"];
					$resized_height = $properties["height"];

					$wasresized = 0;
					if (($original_width > $resized_width) || ($original_height > $resized_height)) {
						$wasresized = 1;
					}
					$addButtonClassName = $options["addButtonClassName"];

					$getUrl = caNavUrl($request, 'lookup', 'ImageAnnotation', 'Get', array('object' => $object_id));

					$tag = <<<EOT
					<style type="text/css" media="all">@import "$viewer_theme_url/css/annotation.css";</style>
					<script language="javascript">
					$(window).load(function() {
						$("img[name]='media_$object_id'").annotateImage({
							useAjax:true,
							getUrl: "$getUrl",
							editable:true,
							addButtonClassName:'$addButtonClassName',
							original_width:'$original_width',
							original_height:'$original_height',
							resized_width:'$resized_width',
							resized_height:'$resized_height',
							resized:'$wasresized',
						});
					});
					</script>
EOT;
				}

				$tag."<img src='$url' width='".$properties["width"]."' height='".$properties["height"]."' border='$border' $vspace $hspace $alt $title $name $usemap $align $style $class />";

				return $tag;
			} else {
				return "<strong>No image</strong>";
			}
		}
	}
	# ------------------------------------------------
}
# ----------------------------------------------------------------------
?>
