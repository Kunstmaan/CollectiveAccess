<?php
/** ---------------------------------------------------------------------
 * DataExporter.php : manages export of data
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

	require_once(__CA_LIB_DIR__.'/ca/ImportExport/DataMover.php');
	require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
	
	class DataExporter extends DataMover {
		# -------------------------------------------------------
		private $opo_format;
		# -------------------------------------------------------
		/**
		 *
		 */
		public function __construct() {
			parent::__construct();	
			$this->opo_datamodel = Datamodel::load();
		}
		# -------------------------------------------------------
		/**
		 * Exports data in the specified format from the specified location using the specified mapping
		 *
		 * @param $ps_format string - the name of the format the data is to be output in (eg. PBCore, EAD, NewsML); this is used to load the appropriate data format processing class
		 * @param $pm_mapping_name_or_id - the mapping_code (string) or mapping_id (integer) of the mapping to apply when exporting
		 * @param $pm_data mixed - a query result, database query or model instance containing the data to export
		 * @param $pm_destination mixed - a file path or file resource to write the data to
		 * @param $pa_options array - an array of options to use during export
		 */
		public function export($pm_mapping_name_or_id, $pm_data, $pm_destination, $pa_options=null) {
			// get mapping
			if (!($o_mapping = $this->opo_bundle_mapping->mappingIsAvailable($pm_mapping_name_or_id))) {
				// mapping doesn't exist
				return false;
			}
			$ps_format = $o_mapping->get('target');
			
			// get format processor
			require_once(__CA_LIB_DIR__.'/ca/ImportExport/Formats/'.$ps_format.'/'.$ps_format.'.php');
			$vs_classname = 'DataMover'.$ps_format;
			if (!($this->opo_format = new $vs_classname)) { 
				// error: invalid format
				return null; 
			}
			
			$va_mapping_rels = $o_mapping->getRelationships();
			
			$va_mapping_rels_by_group = array();
			foreach($va_mapping_rels as $vn_i => $va_rel_info) {
				$va_mapping_rels_by_group[$va_rel_info['group_code']][] = $va_rel_info;
			}
			
			// process data
			$va_records = array();
			
			//
			// For model instances
			//
			if (is_subclass_of($pm_data, "BaseModel")) {
				$pm_data = $pm_data->makeSearchResult($pm_data->tableName(), array($pm_data->getPrimaryKey()));
			}
				
			$t_table = $pm_data->getResultTableInstance();
			$vs_table_name = $t_table->tableName();
			$vs_type_id_fld = method_exists($t_table, "getTypeFieldName") ? $t_table->getTypeFieldName() : null;

					
			while($pm_data->nextHit()) {
				$vn_type_id = ($vs_type_id_fld) ? $pm_data->get("{$vs_table_name}.{$vs_type_id_fld}") : null;

				$va_record = array();
				
				foreach($va_mapping_rels_by_group as $vs_group_code => $va_mappings) {		// iterate through each group
					$vb_value_set = false;
					$va_values = array();
					
					foreach($va_mappings as $vn_i => $va_rel_info) {
						if ($va_rel_info['type_id'] && ($va_rel_info['type_id'] != $vn_type_id)) { continue; }		// only use type-appropriate mappings
					
						$va_tmp = explode(':', $va_rel_info['element_name']);
						
						$va_rel_settings = is_array($va_rel_info['settings']) ? $va_rel_info['settings'] : array();
						
						if(sizeof($va_tmp) > 1) {				// element_name has modifier
							switch($vs_mod = array_shift($va_tmp)) {
								case 'fragment':
									$va_bundle_name = explode('.', $va_rel_info['bundle_name']);
									$t_instance = $this->opo_datamodel->getInstanceByTableName($va_bundle_name[0], true);
							
									//
									// Fetch records to output through the fragement
									//
									$va_ids = $pm_data->get($va_bundle_name[0].'.'.$t_instance->primaryKey(), array_merge($va_rel_settings, array('returnAsArray' => true)));
					
									if (!sizeof($va_ids)) { continue; }		// nothing to output so skip
									$qr_data = $t_instance->makeSearchResult($va_bundle_name[0], $va_ids);		// make it into a search result 'cos that's what DataExporter::export() wants
									if (!$qr_data) { continue; }					// if making of search results fails we need skip since there's nothing more to be done
									
									//
									// Generate the fragment by exporting the fetched records through the specified sub-mapping
									// $va_tmp[0] holds the mapping name specified after fetch: (eg. fetch:my_mapping_name)
									//
									$o_exporter = new DataExporter();
									$va_fragments = $o_exporter->export($va_tmp[0], $qr_data, null, array_merge($pa_options, array('fragment' => true, 'stripOuterTag' => !trim($va_rel_info['destination']))));
									if (!sizeof($va_fragments)) { continue; }		// nothing to output so skip
									//
									// The header option lets a fragment mapping specify tags with static values that appear once in the block and only appear if there are fragments to be output
									// Not terribly exciting but useful for some metadata formats that like to have explanatory headings in the markup (like EAD)
									//
									if (is_array($va_fragments) && sizeof($va_fragments) && isset($va_rel_settings['header']) && is_array($va_rel_settings['header'])) {
										foreach($va_rel_settings['header'] as $vs_tag => $vs_value) {
											array_unshift($va_fragments, "<{$vs_tag}>".caEscapeForXML($vs_value)."</{$vs_tag}>");
										}
									}
									
									if (!trim($va_rel_info['destination'])) {
										//
										// Destination is top-level but SimpleXML doesn't give us any way to inject test into the top level without killing
										// everything in it :-( ... so we have to resort to the tom-foolery below
										//
										// Next time we'll use a better XML API...
										//
										if (!($o_submapping = $this->opo_bundle_mapping->mappingIsAvailable($va_tmp[0]))) { continue; }
										$va_sub_rels = $o_submapping->getRelationships();							// get the mapping relationships in sub-mapping
						
										$va_sub_dest_pieces = (explode('/', $va_sub_rels[0]['destination']));		// pull the root tag out of the first relationship - the root tag should be the same for all relationships so it doesn't matter which we grab from
										$vs_sub_dest = $va_sub_dest_pieces[1];												// not 0 because that's blank (splitting "/whatever" on "/" leaves [0] blank)
										$va_sub_dest_pieces = explode('@', $vs_sub_dest);
										$vs_sub_dest = $va_sub_dest_pieces[0];												// want to make sure we split off any attribute specified - we want the tag name only
										
										if (!$vs_sub_dest) { continue; }																// if we couldn't get a sub-destination tag name to wrap this stuff in then we have to bail because otherwise we'll throw an error below
										
										$va_rel_info['destination'] .= '/'.$vs_sub_dest;										// wrap the output in the root tag; by wrapping it we get around SimpleXML's limitation on injecting content into the top level of the document... because we're no longer doing so
										foreach($va_fragments as $vs_fragment) {
											$vm_val[0][1][] = '{[_FRAGMENT_]}'.$vs_fragment;								// output individual fragments into the root tag wrapper; these have had their root tags stripped via the 'stripOuterTag' option in the DataExporter::export() call above
										}
									} else {
										//
										// Destination is below the top-level so all we need to do is output all fragments as a single value - nice and easy
										//
										$vm_val[0][1][] = '{[_FRAGMENT_]}'.join(' ', $va_fragments);
									}
									
									$vb_value_set = true;
									break;
								case 'static':
									// Convoluted code to support repeating static content if required to...
									
									// is it an attribute? If so we need to make sure we expand the attribute to all repeating values
									if (sizeof($va_desc_pieces = explode('@', $va_rel_info['destination'])) > 1) {
										$vs_attr_name = array_pop($va_desc_pieces);
										$vs_destination_proc = join('', $va_desc_pieces);
										
										if (is_array($va_values[$vs_destination_proc])) {
											$vs_value = join(':', $va_tmp);
											foreach($va_values[$vs_destination_proc] as $vs_index =>$va_by_locale) {
												foreach($va_by_locale as $vn_locale_id => $va_val_list) {
													foreach($va_val_list as $vn_id => $vs_v) {
														$va_values[$va_rel_info['destination']][$vs_index][$vn_locale_id][$vn_id] = $vs_value;
													}
												}
											}
											$vm_val = $va_values[$va_rel_info['destination']];
											break;
										}
									}
									
									if ($va_values[$va_rel_info['destination']]) {
										if (!is_array($va_values[$va_rel_info['destination']])) {
											$va_values[$va_rel_info['destination']] = array(0 => array(1 => array($va_values[$va_rel_info['destination']])));
										}
										$va_values[$va_rel_info['destination']][0][1][] =  join(':', $va_tmp);
										$vm_val = $va_values[$va_rel_info['destination']];
									} else {
										$vm_val = join(':', $va_tmp);
									}
									if ($va_rel_info['bundle_name'] == 'static') { $vb_value_set = true; }
									break;
								case 'date':
									$vs_date_type = array_shift($va_tmp);
									if (!($vs_pattern = join(':', $va_tmp))) {
										$vs_pattern = 'c';	// default to ISO 8601 - everyone loves that one right?
									}
									
									$vm_val = '';
									switch($vs_date_type) {
										case 'update':
											$va_update_info = $pm_data->getLastChangeTimestamp();
											$vm_val = date($vs_pattern, $va_update_info['timestamp']);
											break;
										case 'creation':
											$va_creation_info = $pm_data->getCreationTimestamp();
											$vm_val = date($vs_pattern, $va_creation_info['timestamp']);
											break;
										case 'timestamp':
										default:
											$vm_val = date($vs_pattern, time());
											break;
									}
									if ($va_rel_info['bundle_name'] == 'date') { $vb_value_set = true; }
									break;
							}
							
							if ($vm_val && (isset($va_rel_settings['forceOutput']) && $va_rel_settings['forceOutput'])) {
								$vb_value_set = true;
							}
						} else {								// is mapped element
							// If bundle_name is a bare string, then it's a table name and we'll need to add the element_name 
							// (field or element_name) to complete it
							$va_tmp = explode('.', $va_rel_info['bundle_name']);
							if (sizeof($va_tmp) < 2) {
								$va_rel_info['bundle_name'] .= '.'.$va_rel_info['element_name'];
							}
							
							$vm_val = $pm_data->get($va_rel_info['bundle_name'], array_merge(array('returnAsArray' => true, 'returnAllLocales' => true, 'convertCodesToDisplayText' => true), $va_rel_settings));
							
							if (is_string($vm_val)) { $vm_val = trim($vm_val); }
								 
							$va_format_elements = null;
							if ($vs_format = isset($va_rel_settings['format']) ? $va_rel_settings['format'] : null) {
								if (preg_match_all('!\^([A-Za-z0-9_\-]+)!', $vs_format, $va_matches)) {
									$va_format_elements = $va_matches[1];
								}
							}
							if (is_array($vm_val) && sizeof($vm_val)) { 
								foreach($vm_val as $vn_row_id => $va_values_by_locale) {
									foreach($va_values_by_locale as $vn_locale_id => $va_value_list) {
										foreach($va_value_list as $vn_value_id => $va_value) {
											if (is_array($va_value)) {
												$vm_val[$vn_row_id][$vn_locale_id][$vn_value_id] = $va_value[$va_rel_info['element_name']];
											} else {
												$vm_val[$vn_row_id][$vn_locale_id][$vn_value_id] = $va_value;
											}
											// replace values in format string
											if($vs_format && $va_value){
												$vs_formatted_value = $vs_format;
												if (is_array($va_format_elements)) {
													foreach($va_format_elements as $vs_element) {
														$vs_formatted_value = str_replace('^'.$vs_element, $va_value[$vs_element], $vs_formatted_value);
													}
												}
												$vm_val[$vn_row_id][$vn_locale_id][$vn_value_id]  = $vs_formatted_value;
											}
										}
									}
								}
							} else {
								if (is_array($vm_val)) {		// no value to set
									continue;
								}
							}
							
							
							
							if ($vm_val) {
								$vb_value_set = true;
							}
						}
						
						$va_values[trim($va_rel_info['destination'])] = $vm_val;
						$vm_val = null;
						
					}
					if ($vb_value_set) {
						$va_record[$vs_group_code] = $va_values;
					}
				}
				$this->opo_format->add($va_record);
			}
			
			return $this->opo_format->output($pm_destination, $pa_options);
		}
		# -------------------------------------------------------
		/** 
		 * Returns mimetype of data exported using specified mappings
		 *
		 * @return string - Returns mimetype or null if mapping is invalid
		 */
		public function exportMimetype($pm_mapping_name_or_id) {
			// get mapping
			if (!$o_mapping = $this->opo_bundle_mapping->mappingIsAvailable($pm_mapping_name_or_id)) {
				// mapping doesn't exist
				return null;
			}
			$ps_format = $o_mapping->get('target');
			
			// get format processor
			require_once(__CA_LIB_DIR__.'/ca/ImportExport/Formats/'.$ps_format.'/'.$ps_format.'.php');
			$vs_classname = 'DataMover'.$ps_format;
			if (!($this->opo_format = new $vs_classname)) { 
				// error: invalid format
				return null; 
			}
			
			return $this->opo_format->getOutputMimetype($o_mapping->getPrimaryKey());
		}
		# -------------------------------------------------------
		/** 
		 * Returns file extension to use for data exported using specified mappings
		 *
		 * @return string - Returns file extension or null if mapping is invalid
		 */
		public function exportFileExtension($pm_mapping_name_or_id) {
			// get mapping
			if (!$o_mapping = $this->opo_bundle_mapping->mappingIsAvailable($pm_mapping_name_or_id)) {
				// mapping doesn't exist
				return null;
			}
			$ps_format = $o_mapping->get('target');
			
			// get format processor
			require_once(__CA_LIB_DIR__.'/ca/ImportExport/Formats/'.$ps_format.'/'.$ps_format.'.php');
			$vs_classname = 'DataMover'.$ps_format;
			if (!($this->opo_format = new $vs_classname)) { 
				// error: invalid format
				return null; 
			}
			
			return $this->opo_format->getFileExtension($o_mapping->getPrimaryKey());
		}
		# -------------------------------------------------------
		/** 
		 * Returns target format of data exported using specified mappings
		 *
		 * @return string - Returns target code
		 */
		public function exportTarget($pm_mapping_name_or_id) {
			// get mapping
			if (!$o_mapping = $this->opo_bundle_mapping->mappingIsAvailable($pm_mapping_name_or_id)) {
				// mapping doesn't exist
				return null;
			}
			return $o_mapping->get('target');
		}
		# -------------------------------------------------------
	}
?>