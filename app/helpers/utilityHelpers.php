<?php
/** ---------------------------------------------------------------------
 * app/helpers/utilityHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2011 Whirl-i-Gig
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
   
require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
require_once(__CA_LIB_DIR__.'/core/Configuration.php');
require_once(__CA_LIB_DIR__.'/core/Parsers/ZipFile.php');


# ----------------------------------------------------------------------
# String localization functions (getText)
# ----------------------------------------------------------------------
/**
 * Translates the string in $ps_key into the current locale
 * You interpolate values into the returned string by embedding numbered placeholders in $ps_key 
 * in the format %n (where n is a number). Each parameter passed after $ps_key corresponds to a 
 * placeholder (ex. the first parameter replaces %1, the second %2)
 */
 
global $ca_translation_cache;
$ca_translation_cache = array();
function _t($ps_key) {
	global $ca_translation_cache, $_;
	global $_;
	
	if (!sizeof(func_get_args()) && isset($ca_translation_cache[$ps_key])) { return $ca_translation_cache[$ps_key]; }
	
	if (is_array($_)) {
		$vs_str = $ps_key;
		foreach($_ as $o_locale) {
			if ($o_locale->isTranslated($ps_key)) {
				$vs_str = $o_locale->_($ps_key);
				break;
			}
		}
	} else {
		if (!is_object($_)) { 
			$vs_str = $ps_key;
		} else {
			$vs_str = $_->_($ps_key);
		} 
	}
	if (sizeof($va_args = func_get_args()) > 1) {
		$vn_num_args = sizeof($va_args) - 1;
		for($vn_i=$vn_num_args; $vn_i >= 1; $vn_i--) {
			$vs_str = str_replace("%{$vn_i}", $va_args[$vn_i], $vs_str);
		}
	}
	return $ca_translation_cache[$ps_key] = $vs_str;
}

/**
 * The same as _t(), but rather than returning the translated string, it prints it
 **/
function _p($ps_key) {
	global $ca_translation_cache, $_;
	
	if (!sizeof(func_get_args()) && isset($ca_translation_cache[$ps_key])) { print $ca_translation_cache[$ps_key]; return; }
	
	if (is_array($_)) {
		$vs_str = $ps_key;
		foreach($_ as $o_locale) {
			if ($o_locale->isTranslated($ps_key)) {
				$vs_str = $o_locale->_($ps_key);
				break;
			}
		}
	} else {
		if (!is_object($_)) { 
			$vs_str = $ps_key;
		} else {
			$vs_str = $_->_($ps_key);
		} 
	}
	
	if (sizeof($va_args = func_get_args()) > 1) {
		$vn_num_args = sizeof($va_args) - 1;
		for($vn_i=$vn_num_args; $vn_i >= 1; $vn_i--) {
			$vs_str = str_replace("%{$vn_i}", $va_args[$vn_i], $vs_str);
		}
	}
	
	print $ca_translation_cache[$ps_key] = $vs_str;
	return;
}
# ----------------------------------------------------------------------
# Define parameter type constants for getParameter()
# ----------------------------------------------------------------------
if(!defined("pInteger")) { define("pInteger", 1); }
if(!defined("pFloat")) { define("pFloat", 2); }
if(!defined("pString")) { define("pString", 3); }
if(!defined("pArray")) { define("pArray", 4); }

# OS family constants
define('OS_POSIX', 0);
define('OS_WIN32', 1);


# ----------------------------------------
# --- XML
# ----------------------------------------
function caEscapeForXML($ps_text) {
	$ps_text = str_replace("&", "&amp;", $ps_text);
	$ps_text = str_replace("<", "&lt;", $ps_text);
	$ps_text = str_replace(">", "&gt;", $ps_text);
	$ps_text = str_replace("'", "&apos;", $ps_text);
	return str_replace("\"", "&quot;", $ps_text);
}
# ----------------------------------------
# --- Files
# ----------------------------------------
function caFileIsIncludable($ps_file) {
	$va_paths = explode(PATH_SEPARATOR, get_include_path());
	
	foreach ($va_paths as $vs_path) {
		$vs_fullpath = $vs_path.DIRECTORY_SEPARATOR.$ps_file;
 
		if (file_exists($vs_fullpath)) {
			return $vs_fullpath;
		}
    }
 
    return false;
}

# ----------------------------------------
# File and directory copying
# ----------------------------------------
	function caCopyDirectory($fromDir,$toDir,$chmod=0755,$verbose=false,$replace_existing=true) {
		$errors=array();
		$messages=array();
		
		if (!file_exists($toDir)) {
			mkdir($toDir, $chmod);
		}
		if (!is_writable($toDir)) {
			$errors[]='target '.$toDir.' is not writable';
		}
		if (!is_dir($toDir)) {
			$errors[]='target '.$toDir.' is not a directory';
		}
		if (!is_dir($fromDir)) {
			$errors[]='source '.$fromDir.' is not a directory';
		}
		if (!empty($errors)) {
			if ($verbose) {
				foreach($errors as $err) {
					echo '<strong>Error</strong>: '.$err.'<br />';
				}
			}
			return false;
		}
		
		$exceptions=array('.','..');
		
		$handle=opendir($fromDir);
		while (false!==($item=readdir($handle))) {
			if (!in_array($item,$exceptions)) {
				// cleanup for trailing slashes in directories destinations
				$from=str_replace('//','/',$fromDir.'/'.$item);
				$to=str_replace('//','/',$toDir.'/'.$item);
		
				if (is_file($from))  {
					if (!((!$replace_existing) && file_exists($to))) { 
						if (@copy($from,$to))  {
							chmod($to,$chmod);
							touch($to,filemtime($from)); // to track last modified time
							$messages[]='File copied from '.$from.' to '.$to;
						} else {
							$errors[]='cannot copy file from '.$from.' to '.$to;
						}
					}
				}
				if (is_dir($from))  {
					if (@mkdir($to))  {
						chmod($to,$chmod);
						$messages[]='Directory created: '.$to;
					} else {
						$errors[]='cannot create directory '.$to;
					}
					caCopyDirectory($from,$to,$chmod,$verbose,$replace_existing);
				}
			}
		}
		closedir($handle);
		
		if ($verbose) {
			foreach($errors as $err) {
				echo '<strong>Error</strong>: '.$err."<br/>\n";
			}
			foreach($messages as $msg) {
				echo $msg."<br/>\n";
			}
		}
		return true;
	}
	# ----------------------------------------
	/**
	 * Removes directory $dir and recursively all content within. This means all files and subdirectories within the specified directory will be removed without any question!
	 *
	 * @param string $dir The path to the directory you wish to remove
	 * @param bool $pb_delete_dir By default caRemoveDirectory() will remove the specified directory after delete everything within it. Setting this to false will retain the directory after removing everything inside of it, effectively "cleaning" the directory.
	 * @return bool Always returns true
	 */
	function caRemoveDirectory($dir, $pb_delete_dir=true) {
		if(substr($dir, -1, 1) == "/"){
			$dir = substr($dir, 0, strlen($dir) - 1);
		}
		if ($handle = opendir($dir)) {
			while (false !== ($item = readdir($handle))) {
				if ($item != "." && $item != "..") {
					if (is_dir("{$dir}/{$item}")) { caRemoveDirectory("{$dir}/{$item}", true);  }
					else { @unlink("{$dir}/{$item}"); }
				}
			}
			closedir($handle);
			if ($pb_delete_dir) {
				@rmdir($dir);
			}
		} else {
			return false;
		}
		
		return true;
	}
	# ----------------------------------------
	/**
	 * Returns a list of files for the directory $dir and all sub-directories. Optionally can be restricted to return only files that are in $dir (no sub-directories).
	 *
	 * @param string $dir The path to the directory you wish to get the contents list for
	 * @param bool $pb_recursive Optional. By default caGetDirectoryContentsAsList() will recurse through all sub-directories of $dir; set this to false to only consider files that are in $dir itself.
	 * @param bool $pb_include_hidden_files Optional. By default caGetDirectoryContentsAsList() does not consider hidden files (files starting with a '.') when calculating file counts. Set this to true to include hidden files in counts. Note that the special UNIX '.' and '..' directory entries are *never* counted as files.
	 * @return array An array of file paths.
	 */
	function &caGetDirectoryContentsAsList($dir, $pb_recursive=true, $pb_include_hidden_files=false) {
		$va_file_list = array();
		if(substr($dir, -1, 1) == "/"){
			$dir = substr($dir, 0, strlen($dir) - 1);
		}
		if ($handle = opendir($dir)) {
			while (false !== ($item = readdir($handle))) {
				if ($item != "." && $item != ".." && ($pb_include_hidden_files || (!$pb_include_hidden_files && $item{0} !== '.'))) {
					$vb_is_dir = is_dir("{$dir}/{$item}");
					if ($pb_recursive && $vb_is_dir) { 
						$va_file_list = array_merge($va_file_list, caGetDirectoryContentsAsList("{$dir}/{$item}"));
					} else { 
						if (!$vb_is_dir) { 
							$va_file_list[] = "{$dir}/{$item}";
						}
					}
				}
			}
			closedir($handle);
		}
		
		return $va_file_list;
	}
	# ----------------------------------------
	/**
	 * Returns a list of directories from all directories under $dir as an array of directory paths with associated file counts. 
	 *
	 * @param string $dir The path to the directory you wish to get the contents list for
	 * @param bool $pb_include_root Optional. By default caGetSubDirectoryList() omits the root directory ($dir) and any files in it. Set this to true to include the root directory if it contains files.
	 * @param bool $pb_include_hidden_files Optional. By default caGetSubDirectoryList() does not consider hidden files (files starting with a '.') when calculating file counts. Set this to true to include hidden files in counts. Note that the special UNIX '.' and '..' directory entries are *never* counted as files.
	 * @return array An array with directory paths as keys and file counts as values. The array is sorted alphabetically.
	 */
	function &caGetSubDirectoryList($dir, $pb_include_root=false, $pb_include_hidden_files=false) {
		$va_dir_list = array();
		if(substr($dir, -1, 1) == "/"){
			$dir = substr($dir, 0, strlen($dir) - 1);
		}
		if ($pb_include_root) {
			$va_dir_list[$dir] = 0;
		}
		$vn_file_count = 0;
		if ($handle = @opendir($dir)) {
			while (false !== ($item = readdir($handle))) {
				if ($item != "." && $item != ".." && ($pb_include_hidden_files || (!$pb_include_hidden_files && $item{0} !== '.'))) {
					if (is_dir("{$dir}/{$item}")) { 
						$va_dir_list = array_merge($va_dir_list, caGetSubDirectoryList("{$dir}/{$item}", true, $pb_include_hidden_files));
					}  else {
						$vn_file_count++;
					}
				}
			}
			closedir($handle);
		}
		
		if ($pb_include_root) {
			$va_dir_list[$dir] = $vn_file_count;
		}
		
		ksort($va_dir_list);
		return $va_dir_list;
	}
	# ----------------------------------------
	function caZipDirectory($ps_directory, $ps_name, $ps_output_file) {
		$va_files_to_zip = caGetDirectoryContentsAsList($ps_directory);
		
		$o_zip = new ZipFile();
		foreach($va_files_to_zip as $vs_file) {
			$vs_name = str_replace($ps_directory, $ps_name, $vs_file);
			$o_zip->addFile($vs_file, $vs_name);
		}
		
		$vs_new_file = $o_zip->output(ZIPFILE_FILEPATH);
		copy($vs_new_file, $ps_output_file);
		unlink ($vs_new_file);
		
		return true;
	}
	# ----------------------------------------
	function caGetOSFamily() {
		switch(strtoupper(substr(PHP_OS, 0, 3))	) {
			case 'WIN':
				return OS_WIN32;
				break;
			default:
				return OS_POSIX;
				break;
		}
	}
	# ----------------------------------------
	function caGetPHPVersion() {
		$vs_version = phpversion();
		$va_tmp = explode('.', $vs_version);

		$vn_i = 0;
		$vn_major = $vn_minor = $vn_revision = 0;
		foreach($va_tmp as $vs_element) {
			if (is_numeric($vs_element)) {
				switch($vn_i) {
					case 0:
						$vn_major = intval($vs_element);
						break;
					case 1:
						$vn_minor = intval($vs_element);
						break;
					case 2:
						$vn_revision = intval($vs_element);
						break;
				}
				
				$vn_i++;
			}
		}
		
		return(array(
			'version' => join('.', array($vn_major, $vn_minor, $vn_revision)), 
			'major' => $vn_major, 
			'minor' => $vn_minor, 
			'revision' => $vn_revision,
			'versionInt' => ($vn_major * 10000) + ($vn_minor * 100) + ($vn_revision)
		));
	}
	# ----------------------------------------
	function caEscapeHTML($ps_text, $vs_character_set='utf-8') {
		if (!$opa_php_version) { $opa_php_version = caGetPHPVersion(); }
		
		if ($opa_php_version['versionInt'] >= 50203) {
			$ps_text = htmlspecialchars(stripslashes($ps_text), ENT_QUOTES, $vs_character_set, false);
		} else {
			$ps_text = htmlspecialchars(stripslashes($ps_text), ENT_QUOTES, $vs_character_set);
		}
		return str_replace("&amp;#", "&#", $ps_text);
	}
	# ----------------------------------------
	function caGetTempDirPath() {
		if (function_exists('sys_get_temp_dir')) {
			return sys_get_temp_dir();
		}

		if (!empty($_ENV['TMP'])) {
			return realpath($_ENV['TMP']);
		} else {
			if (!empty($_ENV['TMPDIR'])) {
    		 	return realpath($_ENV['TMPDIR']);
   			} else {
				if (!empty($_ENV['TEMP'])) {
					return realpath( $_ENV['TEMP'] );
				} else {
					$vs_tmp = tempnam( md5(uniqid(rand(), TRUE)), '' );
					if ($vs_tmp)  {
						$vs_tmp_dir = realpath(dirname($vs_tmp));
						unlink($vs_tmp);
						return $vs_tmp_dir;
					} else {
						return false;
					}
				}
			}
		}
	}
	# ----------------------------------------
	function caQuoteList($pa_list) {
		if (!is_array($pa_list)) { return array(); }
		$va_quoted_list = array();
		foreach($pa_list as $ps_list) {
			$va_quoted_list[] = "'".addslashes($ps_list)."'";
		}
		return $va_quoted_list;
	}
	# ----------------------------------------
	function caSerializeForDatabase($ps_data, $pb_compress=false) {
		if ($pb_compress && function_exists('gzcompress')) {
			return gzcompress(serialize($ps_data));
		} else {
			return base64_encode(serialize($ps_data));
		}
	}
	# ----------------------------------------
	function caUnserializeForDatabase($ps_data) {
		if (is_array($ps_data)) { return $ps_data; }
		if (function_exists('gzuncompress') && ($ps_uncompressed_data = @gzuncompress($ps_data))) {
			return unserialize($ps_uncompressed_data);
		}
		return unserialize(base64_decode($ps_data));
	}
	# ----------------------------------------
	/**
	 * 
	 */
	function caWinExec($ps_cmd, &$pa_output, &$pn_return_val) {
		$va_descr = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "w")
		);
		
		$va_env=array('placeholder' => '   ');		// do we need this?
		$r_proc = proc_open($ps_cmd,$va_descr,$va_pipes,null,$va_env,array('bypass_shell'=>TRUE));
		
		if (!is_resource($r_proc)) {
			$pa_output = array();
			$pn_return_val = -1;
			return false;
		} else {
			// Write to app w/ $pipes[0] here...
			fclose($va_pipes[0]);
			
			// Retrieve & close stdout(1) & stderr(2)
			$output=preg_replace("![\n\r]+!", "", stream_get_contents($va_pipes[1]));
			$error=stream_get_contents($va_pipes[2]);
			
			$pa_output = array($output);
			if ($error) {
				$pa_output[] = $error;
			}
			fclose($va_pipes[1]);
			fclose($va_pipes[2]);
			
			// It is important that you close any pipes before calling
			// proc_close in order to avoid a deadlock
			$pn_return_val = proc_close($r_proc);
			return true;
		}
	}
	# ----------------------------------------
	function caConvertHTMLBreaks($ps_text) {
		# check for tags before converting breaks
		preg_match_all("/<[A-Za-z0-9]+/", $ps_text, $va_tags);
		$va_ok_tags = array("<b", "<i", "<u", "<strong", "<em", "<strike", "<sub", "<sup", "<a", "<img", "<span");

		$vb_convert_breaks = true;
		foreach($va_tags[0] as $vs_tag) {
			if (!in_array($vs_tag, $va_ok_tags)) {
				$vb_convert_breaks = false;
				break;
			}
		}

		if ($vb_convert_breaks) {
			$ps_text = preg_replace("/(\n|\r\n){2}/","<p/>",$ps_text);
			$ps_text = ereg_replace("\n","<br/>",$ps_text);
		}
		
		return $ps_text;
	}
	# ----------------------------------------
	/**
	 * Returns list of ngrams for $ps_word; $pn_n is the length of the ngram
	 */
	function caNgrams($str, $size = 5, $clean = true) {
  		$arrNgrams = array();
  		if ($clean) {
	  		$str = strtolower(preg_replace("/[^A-Za-z0-9]/",'',$str));
		}
		for ($i = 0; $i < (strlen($str)-$size+1); $i++) {
			$potential_ngram = substr($str, $i, $size);
			if (strlen($potential_ngram) > 1) {
				$arrNgrams[] = $potential_ngram;
			}
		}
		
		if ($clean) {
			$arrNgrams = array_unique($arrNgrams);
		}
		return($arrNgrams);
	}
	# ---------------------------------------
	/**
	 * Sanity check, is it really an URL?
	 */
	function caIsUrl($vs_url){
		if (!preg_match('#^http\\:\\/\\/[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+#i', $vs_url)) {
		    return false;
		} else {
		    return true;
		}
	}
	# ---------------------------------------
	/**
	 * Returns memory used by current request, either in bytes (integer) or in megabytes for display (string)
	 * 
	 * If $pb_dont_include_base_usage is set to true (default) then usage is counted from a base level 
	 * as defined in the __CA_BASE_MEMORY_USAGE__ constant. This constant should be set early in the request immediately 
	 * after all core includes() are performed.
	 *
	 * If $pb_dont_include_base_usage is set to false then this function returns the same value as the PHP memory_get_usage() built-in
	 * with the "real memory" parameter set.
	 *
	 * If the $pb_format_for_display is set (default = true), then the memory usage is returned as megabytes in a string (ex. 9.75M)
	 * If it is not set then an integer representing the number of bytes used is returned (ex. 10223616)
	 */
	function caGetMemoryUsage($pb_dont_include_base_usage=true, $pb_format_for_display=true) {
		$vn_base_use = defined("__CA_BASE_MEMORY_USAGE__") ? intval(__CA_BASE_MEMORY_USAGE__) : 0;
		
		$vn_usage = ($pb_dont_include_base_usage) ? memory_get_usage(true) - $vn_base_use : memory_get_usage(true);
		
		if ($pb_format_for_display) {
			return sprintf("%3.2f", ($vn_usage/(1024 * 1024)))."M";
		} else {
			return $vn_usage;
		}
	}
	# ---------------------------------------
	/**
	 * Checks URL for apparent well-formedness. Return true if it looks like a valid URL, false if not. This function does
	 * not actually connect to the URL to confirm its validity. It only validates at text content for well-formedness.
	 *
	 * @param string $ps_url The URL to check
	 * @return boolean true if it appears to be valid URL, false if not
	 */
	function isURL($ps_url) {
		if (preg_match("!(http|ftp|https|rtmp|rtsp):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&;:/~\+#]*[\w\-\@?^=%&/~\+#])?!", $ps_url, $va_matches)) {
			return array(
				'protocol' => $va_matches[1],
				'url' => $ps_url
			);
		}
		return false;
	}
	# ---------------------------------------
	/**
	 * Helper function for use with usort() that returns an array of strings sorted by length
	 */
	function caLengthSortHelper($a,$b){ return strlen($b)-strlen($a); }
	# ---------------------------------------
	/**
	 *
	 */
	function caConvertLineBreaks($ps_text) {
		$vs_text = $ps_text;
		
		# check for tags before converting breaks
		preg_match_all("/<[A-Za-z0-9]+/", $vs_text, $va_tags);
		$va_ok_tags = array("<b", "<i", "<u", "<strong", "<em", "<strike", "<sub", "<sup", "<a", "<img", "<span");

		$vb_convert_breaks = true;
		foreach($va_tags[0] as $vs_tag) {
			if (!in_array($vs_tag, $va_ok_tags)) {
				$vb_convert_breaks = false;
				break;
			}
		}

		if ($vb_convert_breaks) {
			$vs_text = preg_replace("/(\n|\r\n){2}/","<p/>",$vs_text);
			$vs_text = ereg_replace("\n","<br/>",$vs_text);
		}
		
		return $vs_text;
	}
	# ---------------------------------------
	/**
	 * Prints stack trace from point of invokation
	 *
	 * @param array $pa_options Optional array of options. Support options are:
	 *		html - if true, then HTML formatted output will be returned; otherwise plain-text output is returned; default is false
	 *		print - if true output is printed to standard output; default is false
	 * @return string Stack trace output
	 */
	function caPrintStacktrace($pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$va_trace = debug_backtrace();
		
		$va_buf = array();
		foreach($va_trace as $va_line) {
			if(isset($pa_options['html']) && $pa_options['html']) {
				$va_buf[] = array($va_line['file'], $va_line['class'], $va_line['function'], $va_line['line']);
			} else {
				$va_buf[] = $va_line['file'].':'.($va_line['class'] ? $va_line['class'].':' : '').$va_line['function'].'@'.$va_line['line'];
			}
		}
		
		if(isset($pa_options['html']) && $pa_options['html']) {
			// TODO: make nicer looking HTML output
			$vs_output = "<table>\n<tr><th>File</th><th>Class</th><th>Function</th><th>Line</th></tr>\n";
			foreach($va_buf as $va_line) {
				$vs_output .= "<tr><td>".join('</td><td>', $va_line)."</td></tr>\n";
			}
			$vs_output .= "</table>\n";
		} else {
			$vs_output = join("\n", $va_buf);
		}
		
		if(isset($pa_options['print']) && $pa_options['print']) {
			print $vs_output;
		}
		
		return $vs_output;
	}
	# ---------------------------------------
	/**
	 * Converts expression with fractional expression to decimal equivalent. 
	 * Only fractional numbers are converted to decimal. The surrounding text will be
	 * left unchanged.
	 *
	 * Examples of valid expressions are:
	 *		12 1/2" (= 12.5")
	 *		12⅔ ft (= 12.667 ft)
	 *		"Total is 12 3/4 lbs" (= "Total is 12.75 lbs")
	 *
	 * Both text fractions (ex. 3/4) and Unicode fraction glyphs (ex. ¾) may be used.
	 *
	 * @param string $ps_fractional_expression String including fractional expression to convert
	 * @return string $ps_fractional_expression with fractions replaced with decimal equivalents
	 */
	function caConvertFractionalNumberToDecimal($ps_fractional_expression) {
		// convert ascii fractions (eg. 1/2) to decimal
		if (preg_match('!^([\d]*)[ ]*([\d]+)/([\d]+)!', $ps_fractional_expression, $va_matches)) {
			if ((float)$va_matches[2] > 0) {
				$vn_val = ((float)$va_matches[2])/((float)$va_matches[3]);
			} else {
				$vn_val = '';
			}
			$vn_val = sprintf("%4.3f", ((float)$va_matches[1] + $vn_val));
			
			$ps_fractional_expression = str_replace($va_matches[0], $vn_val, $ps_fractional_expression);
		} else {
			// replace unicode fractions with decimal equivalents
			foreach(array(
				'½' => '.5','⅓' => '.333',
				'⅔' => '.667','¼' => '.25',
				'¾' => '.75') as $vs_glyph => $vs_val
			) {
				$ps_fractional_expression = preg_replace('![ ]*'.$vs_glyph.'!u', $vs_val, $ps_fractional_expression);	
			}
		}
		
		return $ps_fractional_expression;
	}	
	# ---------------------------------------
	/**
	 * Returns list of values 
	 */
	function caExtractArrayValuesFromArrayOfArrays($pa_array, $ps_key, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$va_extracted_values = array();
		
		foreach($pa_array as $vm_i => $va_values) {
			if (!isset($va_values[$ps_key])) { continue; }
			$va_extracted_values[] = $va_values[$ps_key];
		}
		
		if (isset($pa_options['removeDuplicates'])) { 
			$va_extracted_values = array_flip(array_flip($va_extracted_values));
		}
		
		return $va_extracted_values;
	}
	# ---------------------------------------
	/**
	 * Takes locale-formatted float (eg. 54,33) and converts it to the "standard"
	 * format needed for calculations (eg 54.33)
	 *
	 * @param string $ps_value The value to convert
	 * @return float The converted value
	 */
	function caConvertLocaleSpecificFloat($ps_value) {
		$va_locale = localeconv();
		$va_search = array(
			$va_locale['decimal_point'], 
			$va_locale['mon_decimal_point'], 
			$va_locale['thousands_sep'], 
			$va_locale['mon_thousands_sep'], 
			$va_locale['currency_symbol'], 
			$va_locale['int_curr_symbol']
		);
		$va_replace = array('.', '.', '', '', '', '');
	
		$vs_converted_value = str_replace($va_search, $va_replace, $ps_value);
		return (float)$vs_converted_value;
	}
	# ---------------------------------------
	/**
	 * Formats any number of seconds into a readable string
	 *
	 * @param int Seconds to format
	 * @param int Number of divisors to return, ie (3) gives '1 Year, 3 Days, 9 Hours' whereas (2) gives '1 Year, 3 Days'
	 * @param string Seperator to use between divisors
	 * @return string Formatted interval
	*/
	function caFormatInterval($pn_seconds, $pn_precision = -1, $ps_separator = ', ') {
		$va_divisors = Array(
			31536000 => array('singular' => _t('year'), 'plural' => _t('years'), 'divisor' => 31536000),
			2628000 => array('singular' => _t('month'), 'plural' => _t('months'), 'divisor' => 2628000),
			86400 => array('singular' => _t('day'), 'plural' => _t('days'), 'divisor' => 86400),
			3600 => array('singular' => _t('hour'), 'plural' => _t('hours'), 'divisor' => 3600),
			60 => array('singular' => _t('minute'), 'plural' => _t('minutes'), 'divisor' => 60),
			1 => array('singular' => _t('second'), 'plural' => _t('seconds'), 'divisor' => 1)
		);
	
		krsort($va_divisors);
	
		$va_out = array();
		
		foreach($va_divisors as $vn_divisor => $va_info) {
			// If there is at least 1 of thie divisor's time period
			if($vn_value = floor($pn_seconds / $vn_divisor)) {
				// Add the formatted value - divisor pair to the output array.
				// Omits the plural for a singular value.
				if($vn_value == 1)
					$va_out[] = "{$vn_value} ".$va_info['singular'];
				else
					$va_out[] = "{$vn_value} ".$va_info['plural'];
	
				// Stop looping if we've hit the precision limit
				if(--$pn_precision == 0)
					break;
			}
	
			// Strip this divisor from the total seconds
			$pn_seconds %= $vn_divisor;
		}
	
		if (!isset($va_out)) {
			$va_out[] = "0".$va_info['plural'];
		}
		
		return implode($ps_separator, $va_out);
	}
	# ---------------------------------------
	/**
	 * Parses string for form element dimension. If a simple integer is passed then it is considered
	 * to be expressed as the number of characters to display. If an integer suffixed with 'px' is passed
	 * then the dimension is considered to be expressed in pixesl. If non-integers are passed they will
	 * be cast to integers.
	 *
	 * An array is always returned, with two keys: 
	 *		dimension = the integer value of the dimension
	 *		type = either 'pixels' or 'characters'
	 *
	 * @param string $ps_dimension
	 * @return array An array describing the parsed value or null if no value was passed
	*/
	function caParseFormElementDimension($ps_dimension) {
		$ps_dimension = trim($ps_dimension);
		if (!$ps_dimension) { return null; }
		
		if (preg_match('!^([\d]+)[ ]*(px)$!', $ps_dimension, $va_matches)) {
			return array(
				'dimension' => (int)$va_matches[1],
				'type' => 'pixels'
			);
		}
		
		return array(
			'dimension' => (int)$ps_dimension,
			'type' => 'characters'
		);
	}
	# ---------------------------------------
?>