<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/Audio.php :
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
 * Plugin for processing audio media using ffmpeg
 */

include_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/core/Parsers/getid3/getid3.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");

class WLPlugMediaAudio Extends WLPlug Implements IWLPlugMedia {

	var $errors = array();

	var $filepath;
	var $handle;
	var $ohandle;
	var $properties;
	var $oproperties;
	var $metadata = array();

	var $input_bitrate;
	var $input_channels;
	var $input_sample_frequency;

	var $opo_config;
	var $opo_external_app_config;
	var $ops_path_to_ffmpeg;

	var $ops_mediainfo_path;
	var $opb_mediainfo_available;

	var $info = array(
		"IMPORT" => array(
			"audio/mpeg"						=> "mp3",
			"audio/x-aiff"						=> "aiff",
			"audio/x-wav"						=> "wav",
			"audio/x-wave"						=> "wav",
			"audio/mp4"							=> "aac"
		),

		"EXPORT" => array(
			"audio/mpeg"						=> "mp3",
			"audio/x-aiff"						=> "aiff",
			"audio/x-wav"						=> "wav",
			"audio/mp4"							=> "aac",
			"video/x-flv"						=> "flv",
			"image/png"							=> "png",
			"image/jpeg"						=> "jpg"
		),

		"TRANSFORMATIONS" => array(
			"SET" 		=> array("property", "value"),
			"SCALE" 	=> array("width", "height", "mode", "antialiasing"),
			"ANNOTATE"	=> array("text", "font", "size", "color", "position", "inset"),	// dummy
			"WATERMARK"	=> array("image", "width", "height", "position", "opacity"),	// dummy
			"INTRO"		=> array("filepath"),
			"OUTRO"		=> array("filepath")
		),

		"PROPERTIES" => array(
			"width"				=> 'W',
			"height"			=> 'W',
			"version_width" 	=> 'R', // width version icon should be output at (set by transform())
			"version_height" 	=> 'R',	// height version icon should be output at (set by transform())
			"intro_filepath"	=> 'R',
			"outro_filepath"	=> 'R',
			"mimetype" 			=> 'R',
			"typename"			=> 'R',
			"bandwidth"			=> 'R',
			"title" 			=> 'R',
			"author" 			=> 'R',
			"copyright" 		=> 'R',
			"description" 		=> 'R',
			"duration" 			=> 'R',
			"filesize" 			=> 'R',
			"getID3_tags"		=> 'W',
			"quality"			=> "W",		// required for JPEG compatibility
			"bitrate"			=> 'W', 	// in kbps (ex. 64)
			"channels"			=> 'W',		// 1 or 2, typically
			"sample_frequency"	=> 'W',		// in khz (ex. 44100)
			"version"			=> 'W'		// required of all plug-ins
		),

		"NAME" => "Audio",
		"NO_CONVERSION" => 0
	);

	var $typenames = array(
		"audio/mpeg"						=> "MPEG-3",
		"audio/x-aiff"						=> "AIFF",
		"audio/x-wav"						=> "WAV",
		"audio/mp4"							=> "AAC",
		"image/png"							=> "PNG",
		"image/jpeg"						=> "JPEG"
	);


	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Provides audio processing and conversion using ffmpeg');
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		$this->opo_config = Configuration::load();
		$vs_external_app_config_path = $this->opo_config->get('external_applications');
		$this->opo_external_app_config = Configuration::load($vs_external_app_config_path);
		$this->ops_path_to_ffmpeg = $this->opo_external_app_config->get('ffmpeg_app');

		$this->ops_mediainfo_path = $this->opo_external_app_config->get('mediainfo_app');
		$this->opb_mediainfo_available = caMediaInfoInstalled($this->ops_mediainfo_path);

		if (!caMediaPluginFFfmpegInstalled($this->ops_path_to_ffmpeg)) { return null; }

		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	public function checkStatus() {
		$va_status = parent::checkStatus();
		
		if ($this->register()) {
			$va_status['available'] = true;
		} else {
			if (!caMediaPluginFFfmpegInstalled($this->ops_path_to_ffmpeg)) { 
				$va_status['errors'][] = _t("Didn't load because ffmpeg is not installed");
			}
		}
		
		return $va_status;
	}
	# ------------------------------------------------
	public function divineFileFormat($filepath) {
		$ID3 = new getid3();
		$info = $ID3->analyze($filepath);
		if ($info['fileformat'] == 'riff') {
			if (isset($info['audio']['dataformat']) && ($info['audio']['dataformat'] == 'wav')) {
				$info['mime_type'] = 'audio/x-wav';
			}
		}
		if (($info["mime_type"]) && isset($this->info["IMPORT"][$info["mime_type"]]) && $this->info["IMPORT"][$info["mime_type"]]) {
			if ($info["mime_type"] === 'audio/x-wave') {
				$info["mime_type"] = 'audio/x-wav';
			}
			$this->handle = $this->ohandle = $info;
			if($this->opb_mediainfo_available){
				$this->metadata = caExtractMetadataWithMediaInfo($this->ops_mediainfo_path, $filepath);
			} else {
				$this->metadata = $info;
			}
			return $info["mime_type"];
		} else {
			# file format is not supported by this plug-in
			return "";
		}
	}
	# ----------------------------------------------------------
	public function get($property) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property]) {
				return $this->properties[$property];
			} else {
				print "Invalid property '$property'";
				return "";
			}
		} else {
			return "";
		}
	}
	# ----------------------------------------------------------
	public function set($property, $value) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property]) {
				switch($property) {
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
				$this->postError(1650, _t("Can't set property %1", $property), "WLPlugAudio->set()");
				return "";
			}
		} else {
			return "";
		}
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
	# ------------------------------------------------
	public function read ($filepath) {
		if (!file_exists($filepath)) {
			$this->postError(1650, _t("File %1 does not exist", $filepath), "WLPlugAudio->read()");
			$this->handle = "";
			$this->filepath = "";
			return false;
		}
		if (!(($this->handle) && ($this->handle["filepath"] == $filepath))) {
			$ID3 = new getid3();
			$info = $ID3->analyze($filepath);
			
			if ($info["mime_type"] === 'audio/x-wave') {
				$info["mime_type"] = 'audio/x-wav';
			}
			$this->handle = $this->ohandle = $info;
		}
		if (!((isset($this->handle["error"])) && (is_array($this->handle["error"])) && (sizeof($this->handle["error"]) > 0))) {
			$this->filepath = $filepath;
			//$this->properties  = $this->handle;

			$this->properties["mimetype"] = $this->handle["mime_type"];
			$this->properties["typename"] = $this->typenames[$this->properties["mimetype"]] ? $this->typenames[$this->properties["mimetype"]] : "Unknown";

			$this->properties["duration"] = $this->handle["playtime_seconds"];
			$this->properties["filesize"] = filesize($filepath);


			switch($this->properties["mimetype"]) {
				case 'audio/mpeg':

					if (is_array($this->handle["tags"]["id3v1"]["title"])) {
						$this->properties["title"] = 		join("; ",$this->handle["tags"]["id3v1"]["title"]);
					}
					if (is_array($this->handle["tags"]["id3v1"]["artist"])) {
						$this->properties["author"] = 		join("; ",$this->handle["tags"]["id3v1"]["artist"]);
					}
					if (is_array($this->handle["tags"]["id3v1"]["comment"])) {
						$this->properties["copyright"] = 	join("; ",$this->handle["tags"]["id3v1"]["comment"]);
					}
					if (
						(is_array($this->handle["tags"]["id3v1"]["album"])) &&
						(is_array($this->handle["tags"]["id3v1"]["year"])) &&
						(is_array($this->handle["tags"]["id3v1"]["genre"]))) {
						$this->properties["description"] = 	join("; ",$this->handle["tags"]["id3v1"]["album"])." ".join("; ",$this->handle["tags"]["id3v1"]["year"])." ".join("; ",$this->handle["tags"]["id3v1"]["genre"]);
					}
					$this->properties["type_specific"] = array("audio" => $this->handle["audio"], "tags" => $this->handle["tags"]);

					$this->properties["bandwidth"] = array("min" => $this->handle["bitrate"], "max" => $this->handle["bitrate"]);

					$this->properties["getID3_tags"] = $this->handle["tags"];

					$this->properties["bitrate"] = $input_bitrate = $this->handle["bitrate"];
					$this->properties["channels"] = $input_channels = $this->handle["audio"]["channels"];
					$this->properties["sample_frequency"] = $input_sample_frequency = $this->handle["audio"]["sample_rate"];
					$this->properties["duration"] = $this->handle["playtime_seconds"];
					break;
				case 'audio/x-aiff':

					$this->properties["type_specific"] = array("audio" => $this->handle["audio"], "riff" => $this->handle["riff"]);

					$this->properties["bandwidth"] = array("min" => $this->handle["bitrate"], "max" => $this->handle["bitrate"]);

					$this->properties["getID3_tags"] = array();

					$this->properties["bitrate"] = $input_bitrate = $this->handle["bitrate"];
					$this->properties["channels"] = $input_channels = $this->handle["audio"]["channels"];
					$this->properties["sample_frequency"] = $input_sample_frequency = $this->handle["audio"]["sample_rate"];
					$this->properties["duration"] = $this->handle["playtime_seconds"];
					break;
				case 'audio/x-wav':
					$this->properties["type_specific"] = array();

					$this->properties["audio"] = $this->handle["audio"];
					$this->properties["bandwidth"] = array("min" => $this->handle["bitrate"], "max" => $this->handle["bitrate"]);

					$this->properties["getID3_tags"] = array();

					$this->properties["bitrate"] = $input_bitrate = $this->handle["bitrate"];
					$this->properties["channels"] = $input_channels = $this->handle["audio"]["channels"];
					$this->properties["sample_frequency"] = $this->handle["audio"]["sample_rate"];
					$this->properties["duration"] = $this->handle["playtime_seconds"];
					break;
				case 'audio/mp4':
					$this->properties["type_specific"] = array();

					$this->properties["audio"] = $this->handle["audio"];
					$this->properties["bandwidth"] = array("min" => $this->handle["bitrate"], "max" => $this->handle["bitrate"]);

					$this->properties["getID3_tags"] = array();

					$this->properties["bitrate"] = $input_bitrate = $this->handle["bitrate"];
					$this->properties["channels"] = $input_channels = $this->handle["audio"]["channels"];
					$this->properties["sample_frequency"] = $input_sample_frequency = $this->handle["audio"]["sample_rate"];
					$this->properties["duration"] = $this->handle["playtime_seconds"];
					break;
			}

			$this->oproperties = $this->properties;

			return 1;
		} else {
			$this->postError(1650, join("; ", $this->handle["error"]), "WLPlugAudio->read()");
			$this->handle = "";
			$this->filepath = "";
			return false;
		}
	}
	# ----------------------------------------------------------
	public function transform($operation, $parameters) {
		if (!$this->handle) { return false; }
		if (!($this->info["TRANSFORMATIONS"][$operation])) {
			# invalid transformation
			$this->postError(1655, _t("Invalid transformation %1", $operation), "WLPlugAudio->transform()");
			return false;
		}

		# get parameters for this operation
		$sparams = $this->info["TRANSFORMATIONS"][$operation];

		$this->properties["version_width"] = $w = $parameters["width"];
		$this->properties["version_height"] = $h = $parameters["height"];
		
		if (!$parameters["width"]) {
			$this->properties["version_width"] = $w = $parameters["height"];
		}
		if (!$parameters["height"]) {
			$this->properties["version_height"] = $h = $parameters["width"];
		}
		
		$cw = $this->get("width");
		$ch = $this->get("height");
		if (!$cw) { $cw = $w; }
		if (!$ch) { $ch = $h; }
		switch($operation) {
			# -----------------------
			case "SET":
				while(list($k, $v) = each($parameters)) {
					$this->set($k, $v);
				}
				break;
			# -----------------------
			case 'SCALE':
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
				if (!($w > 0 && $h > 0)) {
					$this->postError(1610, _t("%1: %2 during resize operation", $reason, $description), "WLPlugAudio->transform()");
					return false;
				}
				if ($do_crop) {
					$this->properties["width"] = $parameters["width"];
					$this->properties["height"] = $parameters["height"];
				} else {
					$this->properties["width"] = $w;
					$this->properties["height"] = $h;
				}
				break;
			# -----------------------
			case 'INTRO':
				$this->properties["intro_filepath"] = $parameters["filepath"];
				break;
			# -----------------------
			case 'OUTRO':
				$this->properties["outro_filepath"] = $parameters["filepath"];
				break;
			# -----------------------
		}
		return 1;
	}
	# ----------------------------------------------------------
	public function write($filepath, $mimetype) {
		if (!$this->handle) { return false; }
		if (!($ext = $this->info["EXPORT"][$mimetype])) {
			# this plugin can't write this mimetype
			$this->postError(1610, _t("Can't convert '%1' to '%2': unsupported format", $this->handle["mime_type"], $mimetype), "WLPlugAudio->write()");
			return false;
		}

		$o_config = Configuration::load();

		$va_tags = $this->get("getID3_tags");

		$vs_intro_filepath = $this->get("intro_filepath");
		$vs_outro_filepath = $this->get("outro_filepath");

		if (($vn_output_bitrate = $this->get("bitrate"))< 32) {
			$vn_output_bitrate = 64;
		}
		if (($vn_sample_frequency = $this->get("sample_frequency")) < 4096) {
			$vn_sample_frequency = 44100;
		}
		if (($vn_channels = $this->get("channels")) < 1) {
			$vn_channels = 1;
		}
		if (
			($this->properties["mimetype"] == $mimetype)
			&&
			(!(($this->properties["mimetype"] == "audio/mpeg") && ($vs_intro_filepath || $vs_outro_filepath)))
			&&
			(($vn_output_bitrate == $this->input_bitrate) && ($vn_sample_frequency == $this->input_sample_frequency) && ($vn_channels == $this->input_channels))
		) {
			# write the file
			if ( !copy($this->filepath, $filepath.".".$ext) ) {
				$this->postError(1610, _t("Couldn't write file to '%1'", $filepath), "WLPlugAudio->write()");
				return false;
			}
		} else {

			if (($mimetype != "image/png") && ($mimetype != "image/jpeg")) {
				#
				# Do conversion
				#
				exec(escapeshellcmd($this->ops_path_to_ffmpeg." -f ".$this->info["IMPORT"][$this->properties["mimetype"]]." -i \"".$this->filepath."\" -f ".$this->info["EXPORT"][$mimetype]." -ab ".$vn_output_bitrate." -ar ".$vn_sample_frequency." -ac ".$vn_channels."  -y \"".$filepath.".".$ext."\"")." 2>&1", $va_output, $vn_return);

				if ($vn_return != 0) {
					@unlink($filepath.".".$ext);
					$this->postError(1610, _t("Error converting file to %1 [%2]: %3", $this->typenames[$mimetype], $mimetype, join("; ", $va_output)), "WLPlugAudio->write()");
					return false;
				}

				if ($mimetype == "audio/mpeg") {
					if ($vs_intro_filepath || $vs_outro_filepath) {
						// add intro
						$vs_tmp_filename = tempnam("/tmp", "audio");
						if ($vs_intro_filepath) {
							exec(escapeshellcmd($this->ops_path_to_ffmpeg." -i '".$vs_intro_filepath."' -f mp3 -ab ".$vn_output_bitrate." -ar ".$vn_sample_frequency." -ac ".$vn_channels." -y '".$vs_tmp_filename."'"), $va_output, $vn_return);
							if ($vn_return != 0) {
								@unlink($filepath.".".$ext);
								$this->postError(1610, _t("Error converting intro to %1 [%2]: %3", $this->typenames[$mimetype], $mimetype, join("; ", $va_output)), "WLPlugAudio->write()");
								return false;
							}
						}

						$r_fp = fopen($vs_tmp_filename, "a");
						$r_mp3fp = fopen($filepath.".".$ext, "r");
						while (!feof($r_mp3fp)) {
							fwrite($r_fp, fread($r_mp3fp, 8192));
						}
						fclose($r_mp3fp);
						if ($vs_outro_filepath) {
							$vs_tmp_outro_filename = tempnam("/tmp", "audio");
							exec(escapeshellcmd($this->ops_path_to_ffmpeg." -i '".$vs_outro_filepath."' -f mp3 -ab ".$vn_output_bitrate." -ar ".$vn_sample_frequency." -ac ".$vn_channels." -y '".$vs_tmp_outro_filename."'"), $va_output, $vn_return);
							if ($vn_return != 0) {
								@unlink($filepath.".".$ext);
								$this->postError(1610, _t("Error converting outro to %1 [%2]: %3", $this->typenames[$mimetype], $mimetype, join("; ", $va_output)), "WLPlugAudio->write()");
								return false;
							}
							$r_mp3fp = fopen($vs_tmp_outro_filename, "r");
							while (!feof($r_mp3fp)) {
								fwrite($r_fp, fread($r_mp3fp, 8192));
							}
							unlink($vs_tmp_outro_filename);
						}
						fclose($r_fp);
						copy($vs_tmp_filename, $filepath.".".$ext);
						unlink($vs_tmp_filename);
					}
				}
				$o_getid3 = new getid3();
				$va_mp3_output_info = $o_getid3->analyze($filepath.".".$ext);
				$this->properties = array();
				if (is_array($va_mp3_output_info["tags"]["id3v1"]["title"])) {
					$this->properties["title"] = 		join("; ",$va_mp3_output_info["tags"]["id3v1"]["title"]);
				}
				if (is_array($va_mp3_output_info["tags"]["id3v1"]["artist"])) {
					$this->properties["author"] = 		join("; ",$va_mp3_output_info["tags"]["id3v1"]["artist"]);
				}
				if (is_array($va_mp3_output_info["tags"]["id3v1"]["comment"])) {
					$this->properties["copyright"] = 	join("; ",$va_mp3_output_info["tags"]["id3v1"]["comment"]);
				}
				if (
					(is_array($va_mp3_output_info["tags"]["id3v1"]["album"])) &&
					(is_array($va_mp3_output_info["tags"]["id3v1"]["year"])) &&
					(is_array($va_mp3_output_info["tags"]["id3v1"]["genre"]))) {
					$this->properties["description"] = 	join("; ",$va_mp3_output_info["tags"]["id3v1"]["album"])." ".join("; ",$va_mp3_output_info["tags"]["id3v1"]["year"])." ".join("; ",$va_mp3_output_info["tags"]["id3v1"]["genre"]);
				}
				$this->properties["type_specific"] = array("audio" => $va_mp3_output_info["audio"], "tags" => $va_mp3_output_info["tags"]);

				$this->properties["bandwidth"] = array("min" => $va_mp3_output_info["bitrate"], "max" => $va_mp3_output_info["bitrate"]);

				$this->properties["bitrate"] = $va_mp3_output_info["bitrate"];
				$this->properties["channels"] = $va_mp3_output_info["audio"]["channels"];
				$this->properties["sample_frequency"] = $va_mp3_output_info["audio"]["sample_rate"];
				$this->properties["duration"] = $va_mp3_output_info["playtime_seconds"];
			} else {
				# use default media icons
				if (file_exists($o_config->get("default_media_icons"))) {
					$o_icon_info = Configuration::load($o_config->get("default_media_icons"));
					if ($va_icon_info = $o_icon_info->getAssoc($this->handle["mime_type"])) {
						$vs_icon_path = $o_icon_info->get("icon_folder_path");
						
						$vs_version = $this->get("version");
						if (!$va_icon_info[$vs_version]) { $vs_version = 'small'; }
						if (!copy($vs_icon_path."/".trim($va_icon_info[$vs_version]),$filepath.".".$ext)) {
							$this->postError(1610, _t("Can't copy icon file for [%1] from %2 to %3", $vs_version, $vs_icon_path."/".trim($va_icon_info[$this->get("version")]), $filepath.".".$ext), "WLPlugAudio->write()");
							return false;
						}

						// set
						$va_old_properties = $this->properties;
						$this->properties = array();
						$this->properties["width"] = $va_old_properties["version_width"];
						$this->properties["height"] = $va_old_properties["version_height"];
						$this->properties["quality"] = $va_old_properties["quality"];
					} else {
						$this->postError(1610, _t("No icon available for media type '%1' (system misconfiguration)", $this->handle["mime_type"]), "WLPlugAudio->write()");
						return false;
					}
				} else {
					$this->postError(1610, _t("No icons available (system misconfiguration)"), "WLPlugAudio->write()");
					return false;
				}
			}
		}

		if ($mimetype == "audio/mpeg") {
			// try to write getID3 tags (if set)
			if (is_array($pa_options) && is_array($pa_options) && sizeof($pa_options) > 0) {
				require_once('parsers/getid3/getid3.php');
				require_once('parsers/getid3/write.php');
				$o_getID3 = new getID3();
				$o_tagwriter = new getid3_writetags();
				$o_tagwriter->filename   = $filepath.".".$ext;
				$o_tagwriter->tagformats = array('id3v2.3');
				$o_tagwriter->tag_data = $pa_options;

				// write them tags
				if (!$o_tagwriter->WriteTags()) {
					// failed to write tags
				}
			}
		}

		$this->properties["mimetype"] = $mimetype;
		$this->properties["typename"] = $this->typenames[$mimetype];

		return $filepath.".".$ext;
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
	public function reset() {
		$this->errors = array();
		$this->properties = $this->oproperties;
		return $this->handle = $this->ohandle;
	}
	# ------------------------------------------------
	public function init() {
		$this->errors = array();
		$this->filepath = "";
		$this->handle = "";
		$this->properties = "";
		
		$this->metadata = array();
	}
	# ------------------------------------------------
	public function htmlTag($ps_url, $pa_properties, $pa_options=null, $pa_volume_info=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		foreach(array(
			'name', 'show_controls', 'url', 'text_only', 'viewer_width', 'viewer_height', 'id',
			'poster_frame_url', 'viewer_parameters', 'viewer_base_url', 'width', 'height',
			'vspace', 'hspace', 'alt', 'title', 'usemap', 'align', 'border', 'class', 'style', 'duration', 'pages'
		) as $vs_k) {
			if (!isset($pa_options[$vs_k])) { $pa_options[$vs_k] = null; }
		}
		
		switch($pa_properties["mimetype"]) {
			# ------------------------------------------------
			case 'audio/mpeg':
				$viewer_base_url 	= $pa_options["viewer_base_url"];
				$vs_id 				= $pa_options["id"] ? $pa_options["id"] : "mp3player";

				switch($pa_options["player"]) {
					case 'small':
						ob_start();
						$vn_viewer_width = ($pa_properties["viewer_width"] > 0) ? $pa_properties["viewer_width"] : 165;
						$vn_viewer_height = ($pa_properties["viewer_height"] > 0) ? $pa_properties["viewer_height"] : 38;
?>
						<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="<?php print $vn_viewer_width; ?>" height="<?php print $vn_viewer_height; ?>" id="<?php print $vs_id; ?>" align="">
							<param name="movie" value="<?php print $viewer_base_url; ?>/viewers/apps/niftyplayer.swf?file=<?php print $ps_url; ?>&as=0">
							<param name="quality" value="high">
							<param name="bgcolor" value="#FFFFFF">
							<embed src="<?php print $viewer_base_url; ?>/viewers/apps/niftyplayer.swf?file=<?php print $ps_url; ?>&as=0" quality="high" bgcolor="#FFFFFF" width="<?php print $vn_viewer_width; ?>" height="<?php print $vn_viewer_height; ?>" align="" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">
							</embed>
						</object>
<?php
						return ob_get_clean();
						break;
					case 'text':
						return "<a href='$ps_url'>".(($pa_options["text_only"]) ? $pa_options["text_only"] : "Listen to MP3")."</a>";
						break;
					default:
						$vn_viewer_width = ($pa_properties["viewer_width"] > 0) ? $pa_properties["viewer_width"] : 400;
						$vn_viewer_height = ($pa_properties["viewer_height"] > 0) ? $pa_properties["viewer_height"] : 95;
						ob_start();
?>
			<div style="width: <?php print $vn_viewer_width; ?>px; height: <?php print $vn_viewer_height; ?>px;">
				<div id="<?php print $vs_id; ?>"></div>
				<div class="jp-single-player">
					<div class="jp-interface">
						<ul class="jp-controls">
							<li><a href="#" id="jplayer_play" class="jp-play" tabindex="1">play</a></li>
							<li><a href="#" id="jplayer_pause" class="jp-pause" tabindex="1">pause</a></li>
							<li><a href="#" id="jplayer_stop" class="jp-stop" tabindex="1">stop</a></li>
							<li><a href="#" id="jplayer_volume_min" class="jp-volume-min" tabindex="1">min volume</a></li>
							<li><a href="#" id="jplayer_volume_max" class="jp-volume-max" tabindex="1">max volume</a></li>
						</ul>
						<div class="jp-progress">
							<div id="jplayer_load_bar" class="jp-load-bar">
								<div id="jplayer_play_bar" class="jp-play-bar"></div>
							</div>
						</div>
						<div id="jplayer_volume_bar" class="jp-volume-bar">
							<div id="jplayer_volume_bar_value" class="jp-volume-bar-value"></div>
						</div>
						<div id="jplayer_play_time" class="jp-play-time"></div>
						<div id="jplayer_total_time" class="jp-total-time"></div>
					</div>
					<div id="jplayer_playlist" class="jp-playlist">
						<ul>
							<li></li>
						</ul>
					</div>
				</div>
				<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery("#<?php print $vs_id; ?>").jPlayer( {
							ready: function () {
								this.element.jPlayer("setFile", "<?php print $ps_url; ?>");
							},
							nativeSupport: true, oggSupport: false, customCssIds: false,
							swfPath: "<?php print $viewer_base_url; ?>/js/jquery/jquery-jplayer"
						});
						jQuery("#<?php print $vs_id; ?>").jPlayer("onProgressChange", function(lp,ppr,ppa,pt,tt) {
							jQuery("#jplayer_play_time").text(jQuery.jPlayer.convertTime(pt)); // Default format of 'mm:ss'
							jQuery("#jplayer_total_time").text(jQuery.jPlayer.convertTime(tt)); // Default format of 'mm:ss'
						});	
					});
				</script>
			</div>
<?php
						return ob_get_clean();
						break;
				}
				break;
				# ------------------------------------------------
			case 'audio/mp4':
				$name = $pa_options["name"] ? $pa_options["name"] : "mp3player";

				if ($pa_options["text_only"]) {
					return "<a href='$ps_url'>".(($pa_options["text_only"]) ? $pa_options["text_only"] : "Listen to AAC")."</a>";
				} else {
					ob_start();
?>
					<table border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td>
								<embed width="<?php print $pa_properties["width"]; ?>" height="<?php print $pa_properties["height"] + 16; ?>"
									src="<?php print $ps_url; ?>" type="audio/mp4">
							</td>
						</tr>
					</table>
<?php
					return ob_get_clean();
				}
				break;
				# ------------------------------------------------
			case 'audio/x-wav':
				$name = $pa_options["name"] ? $pa_options["name"] : "mp3player";

				if ($pa_options["text_only"]) {
					return "<a href='$ps_url'>".(($pa_options["text_only"]) ? $pa_options["text_only"] : "Listen to WAV")."</a>";
				} else {
					ob_start();
?>
					<table border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td>
								<embed width="<?php print $pa_properties["width"]; ?>" height="<?php print $pa_properties["height"] + 16; ?>"
									src="<?php print $ps_url; ?>" type="audio/x-wav">
							</td>
						</tr>
					</table>
<?php
					return ob_get_clean();
				}
				break;
				# ------------------------------------------------
			case 'audio/x-aiff':
				$name = $pa_options["name"] ? $pa_options["name"] : "mp3player";

				if ($pa_options["text_only"]) {
					return "<a href='$ps_url'>".(($pa_options["text_only"]) ? $pa_options["text_only"] : "Listen to AIFF")."</a>";
				} else {
					ob_start();
?>
					<table border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td>
								<embed width="<?php print $pa_properties["width"]; ?>" height="<?php print $pa_properties["height"] + 16; ?>"
									src="<?php print $ps_url; ?>" type="audio/x-aiff">
							</td>
						</tr>
					</table>
<?php
					return ob_get_clean();
				}
				break;
			# ------------------------------------------------
			case "video/x-flv":
				$vs_name = 				$pa_options["name"] ? $pa_options["name"] : "flv_player";
				$vs_id = 				$pa_options["id"] ? $pa_options["id"] : "flv_player";

				$vs_flash_vars = 		$pa_options["viewer_parameters"];
				$viewer_base_url =		$pa_options["viewer_base_url"];

				$vn_width =				$pa_options["viewer_width"] ? $pa_options["viewer_width"] : $pa_properties["width"];
				$vn_height =			$pa_options["viewer_height"] ? $pa_options["viewer_height"] : $pa_properties["height"];
				
				$vs_poster_frame_url =	$pa_options["poster_frame_url"];
				
				ob_start();
				
				$vs_config = 'config={"playlist":[{"url":"<?php print $vs_poster_frame_url; ?>", "scaling": "fit"}, {"url": "<?php print $ps_url; ?>","autoPlay":false,"autoBuffering":true, "scaling": "fit"}]};';
?>

			<div id="<?php print $vs_id; ?>">
				<h1><?php print _t('You must have the Flash Plug-in version 9.0.124 or better installed to play video and audio in CollectiveAccess'); ?></h1>
				<p><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>
			</div>
			<object width="<?php print $vn_width; ?>" height="<?php print $vn_height; ?>" type="application/x-shockwave-flash"
				data="<?php print $viewer_base_url; ?>/viewers/apps/flowplayer-3.2.5.swf">
				<param name="movie" value="<?php print $viewer_base_url; ?>/viewers/apps/flowplayer-3.2.5.swf" />
				<param name="allowfullscreen" value="true" />
				<param name="bgcolor" value="#000000" />
				<param name="flashvars" value='<?php print $vs_config; ?>' />
				<img src="<?php print $vs_poster_frame_url; ?>" width="<?php print $vn_width; ?>" height="<?php print $vn_height; ?>" alt="<?php print _t('Preview image for audio'); ?>" title="<?php print _t('Your system cannot play FLV audio. Sorry. :-('); ?>" />
			 </object>
<?php
				return ob_get_clean();
				break;
				# ------------------------------------------------
			case 'image/jpeg':
			case 'image/png':
				#
				# Standard imaage
				#
				if (isset($pa_options["name"]) && ($pa_options["name"] != "")) {
					$name = "name='".htmlspecialchars($pa_options["name"], ENT_QUOTES, 'UTF-8')."' id='".htmlspecialchars($pa_options["name"], ENT_QUOTES, 'UTF-8')."'";
				} else {
					$name = "";
				}
				if (isset($pa_options["vspace"]) && ($pa_options["vspace"] != "")) {
					$vspace = "vspace='".$pa_options["name"]."'";
				} else {
					$vspace = "";
				}
				if (isset($pa_options["hspace"]) && ($pa_options["hspace"] != "")) {
					$hspace = "hspace='".$pa_options["hspace"]."'";
				} else {
					$hspace = "";
				}
				if (isset($pa_options["alt"]) && ($pa_options["alt"] != "")) {
					$alt = "alt='".htmlspecialchars($pa_options["alt"], ENT_QUOTES, 'UTF-8')."'";
				} else {
					$alt = "alt='image'";
				}
				if (isset($pa_options["title"]) && ($pa_options["title"] != "")) {
					$title = "title='".htmlspecialchars($pa_options["title"], ENT_QUOTES, 'UTF-8')."'";
				} else {
					$title = "";
				}
				if (isset($pa_options["usemap"]) && ($pa_options["usemap"] != "")) {
					$usemap = "usemap='#".$pa_options["usemap"]."'";
				} else {
					$usemap = "";
				}
				if (isset($pa_options["align"]) && ($pa_options["align"] != "")) {
					$align = " align='".$pa_options["align"]."'";
				} else {
					$align= "";
				}

				if ($pa_options["border"]) {
					$border = intval($pa_options["border"]);
				} else {
					$border = 0;
				}
				if ($pa_options["class"]) {
					$class = "class='".$pa_options["class"]."'";
				} else {
					$class = "";
				}


				if (isset($pa_options["style"]) && ($pa_options["style"] != "")) {
					$style = " style='".$pa_options["style"]."'";
				} else {
					$style= "";
				}

				if (!isset($pa_properties["width"])) $pa_properties["width"] = 100;
				if (!isset($pa_properties["height"])) $pa_properties["height"] = 100;

				if (($ps_url) && ($pa_properties["width"] > 0) && ($pa_properties["height"] > 0)) {

					return "<img src='$ps_url' width='".$pa_properties["width"]."' height='".$pa_properties["height"]."' border='$border' $vspace $hspace $alt $title $name $usemap $align $class $style />";
				} else {
					return "<b><i>No image</i></b>";
				}
				break;
				# ------------------------------------------------

		}
	}
	# ------------------------------------------------
	public function cleanup() {
		return;
	}
	# ------------------------------------------------
}
?>