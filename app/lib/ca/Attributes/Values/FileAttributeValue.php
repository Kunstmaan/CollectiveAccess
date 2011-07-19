<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/FileAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2010 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/IAttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/AttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	require_once(__CA_LIB_DIR__."/core/File/FileInfoCoder.php");
	require_once(__CA_LIB_DIR__."/core/File/FileMimeTypes.php");
 	require_once(__CA_LIB_DIR__.'/core/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants
 	
 
 	global $_ca_attribute_settings;
 	
 	$_ca_attribute_settings['FileAttributeValue'] = array(		// global
		'doesNotTakeLocale' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Does not use locale setting'),
			'description' => _t('Check this option if you don\'t want your georeferences to be locale-specific. (The default is to not be.)')
		),
		'canBeUsedInDisplay' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used in display'),
			'description' => _t('Check this option if this attribute value can be used for display in search results. (The default is to be.)')
		),
		'displayTemplate' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 4,
			'label' => _t('Display template'),
			'validForRootOnly' => 1,
			'description' => _t('Layout for value when used in a display (can include HTML). Element code tags prefixed with the ^ character can be used to represent the value in the template. For example: <i>^my_element_code</i>.')
		),
		'displayDelimiter' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => ',',
			'width' => 10, 'height' => 1,
			'label' => _t('Value delimiter'),
			'validForRootOnly' => 1,
			'description' => _t('Delimiter to use between multiple values when used in a display.')
		)
	);
 
	class FileAttributeValue extends AttributeValue implements IAttributeValue {
 		# ------------------------------------------------------------------
 		private $opa_file_data;
 		private $ops_file_data;
 		private $ops_file_original_name;
 		private $opo_file_info_coder;
 		# ------------------------------------------------------------------
 		public function __construct($pa_value_array=null) {
 			$this->opo_file_info_coder = new FileInfoCoder();
 			parent::__construct($pa_value_array);
 		}
 		# ------------------------------------------------------------------
 		public function loadTypeSpecificValueFromRow($pa_value_array) {
 			$this->opn_value_id = $pa_value_array['value_id'];
 			$this->ops_file_data = $pa_value_array['value_blob'];
 			$this->opa_file_data = $this->opo_file_info_coder->getFileArray($pa_value_array['value_blob']);
 			$this->ops_file_original_name = $pa_value_array['value_longtext2'];
 		}
 		# ------------------------------------------------------------------
		public function getDisplayValue($pa_options=null) {
			$vs_val = '';
			if ($vs_url = $this->opo_file_info_coder->getFileUrl($this->opa_file_data)) {
				$va_info =  $this->opo_file_info_coder->getFileInfo($this->opa_file_data);
				
				$va_dimensions = array();
				if ($va_info['ORIGINAL_FILENAME']) {
					$vs_filename = $va_info['ORIGINAL_FILENAME'];
				} else {
					$vs_filename = _t('Uploaded file');
				}
				if ($va_info['MIMETYPE']) {
					$va_dimensions[] = FileMimeTypes::nameForMimeType($va_info['MIMETYPE']);
				}
				if (!isset($va_info['PROPERTIES']['filesize']) || !($vn_filesize = $va_info['PROPERTIES']['filesize'])) {
 					$vn_filesize = @filesize($this->opo_file_info_coder->getFilePath($this->opa_file_data));
 				}
 				if ($vn_filesize) {
 					$va_dimensions[] = sprintf("%4.2f", $vn_filesize/(1024*1024)).'mb';
 				}
				$vs_dimensions = join('; ', $va_dimensions);
				$vs_val = "<div class='attributeFileInfoContainer'><div class='attributeFileFileName'>{$vs_filename}</div><div class='attributeFileFileInfo'>{$vs_dimensions}</div></div>";
			}
			return $vs_val;
		}
 		# ------------------------------------------------------------------
 		public function parseValue($ps_value, $pa_element_info) {
 			$vb_is_file_path = false;
 			if (
 				(is_array($ps_value) && $ps_value['_uploaded_file'] && file_exists($ps_value['tmp_name']) && (filesize($ps_value['tmp_name']) > 0))
 				||
 				($vb_is_file_path = file_exists($ps_value))
 			) {
 				// got file
 				if ($vb_is_file_path) {
 					return array(
						'value_blob' => $ps_value,
						'value_longtext2' => $ps_value,
						'value_decimal1' => null,
						'value_decimal2' => null,
						'_file' => true			// this tells the ca_attribute_values (which is the caller) to treat value_blob as a path to a file to be ingested
					);
 				} else {
					return array(
						'value_blob' => $ps_value['tmp_name'],
						'value_longtext2' => $ps_value['name'],
						'value_decimal1' => null,
						'value_decimal2' => null,
						'_file' => true			// this tells the ca_attribute_values (which is the caller) to treat value_blob as a path to a file to be ingested
					);
				}
 			} else {
 				//$this->postError(1970, _t('No file uploaded'), 'FileAttributeValue->parseValue()');
				//return false;
 			}
 			return array(
				'value_blob' => $this->ops_file_data,
				'value_longtext2' => $this->ops_file_original_name,
				'value_decimal1' => null,
				'value_decimal2' => null,
				'_dont_save' => true
			);
 		}
 		# ------------------------------------------------------------------
 		public function htmlFormElement($pa_element_info, $pa_options=null) {
 			$vs_element = '<table><tr>';
 			$vs_element .= '<td>'._t("Select a file").': <input type="file" name="{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}"></td>' ;
 			
 			$vs_element .= '<td>{{'.$pa_element_info['element_id'].'}}</td>';
 			if (isset($pa_options['po_request']) && ($vo_request = $pa_options['po_request'])) {
 				$vs_element .= '<td>'.urlDecode(caNavLink($vo_request, caNavIcon($vo_request, __CA_NAV_BUTTON_DOWNLOAD__, null, array('align' => 'middle')), '', $vo_request->getModulePath(), $vo_request->getController(), 'DownloadFile', array('download' => 1, 'element_id' => $pa_element_info['element_id'], 'attribute_id' => '{n}'), array('class' => 'attributeDownloadButton', 'id' => 'download_control_{n}'))).'</td>';
 			}
 			$vs_element .= '</tr></table>';
 			return $vs_element;
 		}
 		# ------------------------------------------------------------------
 		public function getAvailableSettings() {
 			global $_ca_attribute_settings;
 			
 			return $_ca_attribute_settings['FileAttributeValue'];
 		}
 		# ------------------------------------------------------------------
		/**
		 * Returns name of field in ca_attribute_values to use for sort operations
		 * 
		 * @return string Name of sort field
		 */
		public function sortField() {
			return null;
		}
 		# ------------------------------------------------------------------
	}
 ?>