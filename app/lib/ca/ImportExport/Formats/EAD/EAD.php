<?php
/** ---------------------------------------------------------------------
 * EAD.php : import/export module for EAD data format
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
 * @subpackage ImportExport
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

	require_once(__CA_LIB_DIR__.'/ca/ImportExport/Formats/BaseXMLDataMover.php');
	
	
global $g_ca_data_import_export_format_definitions;
$g_ca_data_import_export_format_definitions['EAD'] = array(
	'element_list' => array(
		'eadheader' => array(
			'canRepeat' 	=> false,
			'minValues' 	=> 1,
			'subElements' 	=> array(
				'eadid' 			=> array(),
				'filedesc'			=> array(
					'subElements' => array(
						'titlestmt' => array(
							'subElements' => array(
								'titleproper' => array()
							)
						),
						'publicationstmt' => array(
							'subElements' => array(
								'publisher' => array(),
								'date' => array(),
								'address' => array(
									'subElements' => array(
										'addressline' => array(
											'canRepeat' => true
										)
									)
								)
							)
						)
					)
				)
			)
		),
		'archdesc' => array(
			'canRepeat' 	=> false,
			'minValues' 	=> 0,
			'subElements' 	=> array(
				'did'	 			=> array(
					'subElements' => array(
						'head' => array(),
						'origination' => array(
							'subElements' => array(
								'persname' => array(
									'canRepeat' => true
								)
							)
						),
						'unitdate' => array(
							'canRepeat' => false
						),
						'repository' => array(
							'subElements' => array(
								'corpname' => array(
									'canRepeat' => false
								),
								'address' => array(
									'canRepeat' => false,
									'subElements' => array(
										'addressline' => array(
											'canRepeat' => true
										)
									)
								)
							)
						)
					)
				),
				'descgrp' => array(
					'subElements' => array(
						'accessrestrict' => array(
							'canRepeat' => true,
							'subElements' => array(
								'head' => array()
							)
						)
					)
				),
				'controlaccess' => array(
					'canRepeat' => false,
					'subElements' => array(
						'controlaccess' => array(
							'canRepeat' => true,
							'subElements' => array(
								'head' => array(),
								'subject' => array(
									'canRepeat' => true
								),
								'genreform' => array(
									'canRepeat' => true
								),
								'geogname' => array(
									'canRepeat' => true
								)
							)
						)
					)	
				),				
				'dsc' => array(
					'canRepeat' 	=> false,
					'minValues' 	=> 1,
					'subElements' 	=> array(
						'head' 			=> array(),
						'c'			=> array(
							'canRepeat' => true,
							'subElements' => array(
								'did' => array(
									'canRepeat' => true,
									'subElements' => array(
										'unittitle' => array(
											'canRepeat' => false
										)
									)
								)
							)
						)
					)
				)
			)
		)
	),
	'name' 				=> _t('EAD'),
	'version' 			=> '2002',
	'description' 		=> _t('Encoded Archival Description format'),
	'url' 				=> 'http://www.loc.gov/ead/',
	'output_mimetype'	=> 'text/xml',
	'file_extension'	=> 'xml',
	
	'start_tag'			=> '<?xml version="1.0" encoding="UTF-8"?>
<ead xmlns="urn:isbn:1-931666-22-9" xmlns:slink="http://www.w3.org/1999/xlink"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="urn:isbn:1-931666-22-9 http://www.loc.gov/ead/ead.xsd">
',
	'end_tag'			=> '</ead>'
);

	class DataMoverEAD extends BaseXMLDataMover {
		# -------------------------------------------------------
		protected $ops_name = 'EAD';
		# -------------------------------------------------------
		public function __construct() {
		
		}
		# -------------------------------------------------------
		# Import
		# -------------------------------------------------------
		/**
		 * Read and parse metadata
		 *
		 * @param $pm_input mixed - A file path or file resource containing the metadata to be parsed
		 * @param $pa_options array - An array of parse options
		 */
		public function import($pm_input, $pa_options=null) {
		
		}
		# -------------------------------------------------------
		# Export
		# -------------------------------------------------------
		/**
		 * Outputs metadata to specified target using specified options
		 *
		 * @param $pm_target string|file resource - a file path or file resource to output the metadata to. If set to null metadata is used as return value (this can be memory intensive for very large metadata sets as the entire data set must be kept in memory)
		 * @param $pa_options array -
		 * @return boolean|string - true on success, false on failure; if $pm_target is null or 'returnOutput' option is set to true then string representation of output metadata will be returned
		 */
		public function output($pm_target, $pa_options=null) {
			return parent::output($pm_target, $pa_options);
		}
		# -------------------------------------------------------
	}
?>