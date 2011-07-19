<?php
/** ---------------------------------------------------------------------
 * OAIDC.php : import/export module for OAI DublicCore data format
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
 * @subpackage ImportExport
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

	require_once(__CA_LIB_DIR__.'/ca/ImportExport/OAIPMH/Harvester/OAIPMHHarvesterOaiDC.php');
	
	
global $g_ca_data_import_export_format_definitions;
$g_ca_data_import_export_format_definitions['OAIDC'] = array(
	'element_list' => array(
		'contributor' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
			
			)
		),
		'coverage' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
			
			)
		),
		'creator' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
			
			)
		),
		'date' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
			
			)
		),
		'description' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
			
			)
		),
		'format' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
			
			)
		),
		'identifier' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
			
			)
		),
		'language' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
			
			)
		),
		'publisher' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
			
			)
		),
		'relation' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
			
			)
		),
		'rights' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
			
			)
		),
		'source' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
			
			)
		),
		'subject' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
			
			)
		),
		'title' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
			
			)
		),
		'type' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
			
			)
		)
	),
	'name' 				=> _t('OAIDC'),
	'version' 			=> '1.0',
	'description' 		=> _t('OAI DublinCore'),
	'url' 				=> 'http://www.oai.org',
	'output_mimetype'	=> 'text/xml',
	'file_extension'	=> 'xml',
	
	'start_tag'			=> '',
    
    'end_tag'			=> ''
);

	class DataMoverOAIDC {
		# -------------------------------------------------------
		protected $ops_name = 'OAIDC';
		# -------------------------------------------------------
		public function __construct() {
		
		}
		# -------------------------------------------------------
		# Import
		# -------------------------------------------------------
		/**
		 * Read and parse metadata
		 *
		 * @param $ps_url string - URL of an OAI-PMH data provider
		 * @param $po_caller DataImporter - Instance of DataImporter to call importRecord() on for each processed oai_dc record 
		 * @param $pa_options array - An array of options to use when interacting with the OAI-PMH provider
		 */
		public function import($ps_url, $po_caller, $pa_options=null) {
			$o_harvester = new OAIPMHHarvesterOaiDC($ps_url, $po_caller, $pa_options);
		}
		# -------------------------------------------------------
		# Export
		# -------------------------------------------------------
		/**
		 * Outputs metadata to specified target using specified options
		 *
		 * @param $pm_target string|file resource - a file path or file resource to output the metadata to. If set to null metadata is used as return value (this can be memory intensive for very large metadata sets as the entire data set must be kept in memory)
		 * @return boolean|string - true on success, false on failure; if $pm_target is null or 'returnOutput' option is set to true then string representation of output metadata will be returned
		 */
		public function output($pm_target, $pa_options=null) {
			die("Not implemented");
		}
		# -------------------------------------------------------
	}
?>