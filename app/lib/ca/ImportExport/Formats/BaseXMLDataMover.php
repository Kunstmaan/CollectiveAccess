<?php
/** ---------------------------------------------------------------------
 * BaseXMLDataMover.php : base class for all XML-based import/export formats
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

	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	require_once(__CA_LIB_DIR__.'/ca/ImportExport/Formats/BaseDataMoverFormat.php');
	

	class BaseXMLDataMover extends BaseDataMoverFormat {
		# -------------------------------------------------------
		public function __construct() {
			parent::__construct();
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
		public function parse($pm_input, $pa_options=null) {
			parent::parse($pm_input, $pa_options=null);
		}
		# -------------------------------------------------------
		# Export
		# -------------------------------------------------------
		/**
		 * Outputs metadata to specified target using specified options
		 *
		 * @param $pm_target string|file resource - a file path or file resource to output the metadata to. If set to null metadata is used as return value (this can be memory intensive for very large metadata sets as the entire data set must be kept in memory)
		 * @param boolean|string - true on success, false on failure; if $pm_target is null or 'returnOutput' option is set to true then string representation of output metadata will be returned
		 * 		Support options are:
		 *				returnOutput - if true, then output() will return metadata, otherwise output is only sent to $pm_target
		 *				returnAsString - if true (and returnOutput is true as well) then returned output is a string (all records concatenated), otherwise output will be an array of strings, one for each record
		 * 				
		 * return array Returns array of output, one item for each record, except if the 'returnAsString' option is set in which case records are returned concatenated as a string
		 */
		public function output($pm_target, $pa_options=null) {
			if (!$this->opa_records || !sizeof($this->opa_records)) { return false; }
			
			$va_elements = $this->getElementList();
			$va_format_info = $this->getFormatInfo();
			
			$r_fp = null;
			if ($pm_target) {
				if(is_string($pm_target)) {
					$r_fp = fopen($pm_target, 'w');
				} else {
					if(is_file($pm_target)) {
						$r_fp = $pm_target;
					} else {
						return false;
					}
				}
			}
	
				if (!$pa_options['fragment']) {
			//	print_r($this->opa_records); 
			}
			
			$va_record_output = array();			// xml for each record
			$va_fragments = array();
			foreach($this->opa_records as $vn_i => $va_record) {
				$r_output = new SimpleXMLElement($va_format_info['start_tag'].$va_format_info['end_tag']);
				
				foreach($va_record as $vs_group => $va_mappings) {
					
				
					// get base path
					$va_destinations = array_keys($va_mappings);
					
					$va_base_path = null;
					foreach($va_destinations as $vs_dest) {
						$va_pieces = explode('/', $vs_dest);
						
						// remove attributes (if any) in case they are applied to the base path
						$va_tmp = array();
						foreach($va_pieces as $vs_tmp) {
							$va_tmp2 = explode('@', $vs_tmp);
							$va_tmp[] = $va_tmp2[0];
						}
						$va_pieces = $va_tmp;
						
						array_shift($va_pieces); 	// get rid of leading blank caused by leading '/'
						if (!$va_base_path) { $va_base_path = $va_pieces; continue; }
						
						// filter out non-base path elements
						$va_tmp = array();
						for($vn_i = 0; $vn_i < sizeof($va_base_path); $vn_i++) {
							if ($va_base_path[$vn_i] == $va_pieces[$vn_i]) {
								$va_tmp[] = $va_base_path[$vn_i];
							} else {
								break;
							}
						}
						$va_base_path = $va_tmp;
					}
					
					// ok, $va_base_path now contains the base path elements
					
					
					// Create tags for mapping base path
					$vp_ptr = $r_output;
					$vp_element_info = $va_elements;	// set to hierarchical list of all elements
					
					$vb_container_is_repeatable = false;
					$vp_parent = null;
					
					foreach($va_base_path as $vs_tag) {
						$vp_element_info = (isset($vp_element_info[$vs_tag]) ? $vp_element_info[$vs_tag] : $vp_element_info['subElements'][$vs_tag]);	// walk down the hierarchy
						$vp_parent = $vp_ptr;
						
						// create parent tags
						if (!$vp_ptr->{$vs_tag} || $vp_element_info['canRepeat']) { 
							$vp_ptr = $vp_ptr->addChild($vs_tag, ''); 
						} else {
							$vp_ptr = $vp_ptr->{$vs_tag};
						}
						
						$vn_i++;

					}
					$vb_container_is_repeatable = $vp_element_info['canRepeat'];	
					$vp_base_ptr = $vp_ptr;
					$vp_base_element_info = $vp_element_info;
					$vp_container = $vp_parent;
					
					// Process values						
					$va_acc = array();	// value accumulator
				
					foreach($va_mappings as $vs_destination => $va_values) {
						if (is_array($va_values)) {
							$va_values = caExtractValuesByUserLocale($va_values);
						} else {
							$va_values = array(0 => array($va_values));
						}

						foreach($va_values as $vn_x => $va_value_list) {
							$vn_index = -1;
							foreach($va_value_list as $vn_y => $vs_value) {
								$vn_index++;
								$va_tmp = explode('/', $vs_destination);
								array_shift($va_tmp);
								
								$vp_ptr = $vp_base_ptr;
								$vp_element_info = $vp_base_element_info;
								$vp_parent = null;
								
								for($vn_i = 0; $vn_i < sizeof($va_tmp); $vn_i++) {
									$vs_tag = $va_tmp[$vn_i];
									
									$vs_base_proc = array_shift(explode('@', $va_base_path[$vn_i]));
									$vs_tag_proc = array_shift(explode('@', $vs_tag));
									
									if (
										($vs_base_proc == $vs_tag_proc) && 
										($vn_i < (sizeof($va_tmp) - 1))
									) { continue; }		// skip base path (unless the path *is* the base path in which case we want to output the value for it)
									
									if (
										preg_match('!@!', $vs_tag)
									) { 
										if (($vn_i == (sizeof($va_tmp) - 1))) {
											// we have an attribute attached to a base path
											$va_tag_tmp = explode('@', $vs_tag);
											if (is_array($va_attributes = $this->_getAttributes($va_tag_tmp[0], array('/'.$vs_tag => $vs_value))) && sizeof($va_attributes)) {
												foreach($va_attributes as $vs_attribute_name => $vs_attribute_value) {
													foreach($vp_ptr->attributes() as $vs_existing_attr => $vs_existing_attr_value) {
														if ($vs_existing_attr == $vs_attribute_name) { continue(2); }
													}
													
													if ($vp_ptr->getName() != $va_tag_tmp[0]) {
														if (isset($vp_ptr->{$va_tag_tmp[0]})) {
															$vp_ptr = $vp_ptr->{$va_tag_tmp[0]};
														} else {
															// Badly formed mappings can set up a situation where
															// there's no tag for the attribute to be slotted in. For now we'll
															// just silently skip them but we should say something once we get
															// a proper debugging mode up and running.
															continue(2);
														}
													}
													
													if($vp_ptr[$vn_index]) {
														$vp_ptr[$vn_index]->addAttribute($vs_attribute_name, $vs_attribute_value);
													}
												}
											}
											$vs_tag = $va_tag_tmp[0];
										}
										continue (2); 
									} 					// skip attributes unless attached to base path
									
									$vp_element_info = (isset($vp_element_info[$vs_tag]) ? $vp_element_info[$vs_tag] : isset($vp_element_info['subElements'][$vs_tag]) ? $vp_element_info['subElements'][$vs_tag] : $vp_element_info);
									
									
									if (($vn_i == (sizeof($va_tmp) - 1) && ($vs_base_proc == $vs_tag_proc))) {
										// We're putting data into the last element of the base path
										// (This is happening because there's only one path in the mapping so the entire path is considered "base")
										$vp_parent = $vp_container;
									} else {
										$vp_parent = $vp_ptr;
										if (!$vp_ptr->{$vs_tag}) { 
											$vp_ptr = $vp_ptr->addChild($vs_tag, ''); 
										} else {
											$vp_ptr = $vp_ptr->{$vs_tag};
										}
									}
									if (is_array($va_attributes = $this->_getAttributes($vs_tag, array('/'.$vs_tag => $vs_value))) && sizeof($va_attributes)) {
										foreach($va_attributes as $vs_attribute_name => $vs_attribute_value) {
											foreach($vp_ptr->attributes() as $vs_existing_attr => $vs_existing_attr_value) {
												if ($vs_existing_attr == $vs_attribute_name) { continue(2); }
											}
											$vp_ptr->addAttribute($vs_attribute_name, $vs_attribute_value);
										}
										continue(2);
									}
								}
								if (substr($vs_value, 0, 14) == '{[_FRAGMENT_]}') {					// Insert XML fragment from sub-mapping into XML stream
									$vs_value = substr($vs_value, 14);
									$vs_frag_id = 'FRAGMENT_'.sizeof($va_fragments);
									$va_fragments[$vs_frag_id] = $vs_value;
									$vs_value =  "[_{$vs_frag_id}_]";
								}
								
								if ($vp_element_info['canRepeat']) {
									if (!$vp_parent) { 
										// Need proper debugging mode
										print "WARNING: NO PARENT FOR {$vs_tag}\n"; 
										continue; 
									}
									$vn_c = $vp_parent->{$vs_tag}->count();
									
									if(($vn_c == 1) && (!(string)$vp_parent->{$vs_tag}[0])) {	// if there's only one tag for this repeating field and it's empty, then it was just created during initialization above (code=$vp_ptr = $vp_ptr->addChild($vs_tag, '');) and we should use that element rather than creating a new one
										$vp_parent->{$vs_tag}[0] = $vs_value;
									} else {
										if ($vp_parent) { $vp_parent->addChild($vs_tag, $vs_value); }
									}
								} else {
									$va_acc[$vs_tag][] = $vs_value;				// accumulate values for output as delimited list later
								}
							}
						}
						
					//	if (!$vb_container_is_repeatable) {
							foreach($va_acc as $vs_tag => $va_values) {
								$vp_parent->{$vs_tag} = join('; ', $va_values);
							}
							$va_acc = array();
					//	} 
					}
					
					if (sizeof($va_acc)) {
						if ($vb_container_is_repeatable) {
							$va_tags = array_keys($va_acc);
							
							for($vn_i=0; $vn_i < sizeof($va_acc[$va_tags[0]]); $vn_i++) {
								if ($vn_i != 0) {
									$vo_node = $vp_container->addChild((string)$vp_parent->getName(), '');
								} else {
									$vo_node = $vp_parent;
								}
								foreach($va_tags as $vs_tag) {
									if(sizeof($va_acc[$vs_tag]) > $vn_i) {
										$vs_val = $va_acc[$vs_tag][$vn_i];
									} else {
										$vs_val = $va_acc[$vs_tag][0];		// static content will not repeat so we want to fish it out of the initial element
									}
									if ($vn_i != 0) { 
										$vo_node->addChild($vs_tag, $vs_val);
									} else {
										$vo_node->{$vs_tag} = $vs_val;
									}
								}
							}
						} else {
							foreach($va_acc as $vs_tag => $va_values) {
								if (!$vp_parent) { print "NO PARENT FOR $vs_tag/". join('; ', $va_values)."<br>\n"; continue; }
								$vp_parent->{$vs_tag} = join('; ', $va_values);
							}
						}
					}
				}
				
				if ((isset($pa_options['fragment']) && $pa_options['fragment'])) {
					$va_nodes = $r_output->children();						// strip top level tag container
					
					$vs_xml = $va_nodes[0]->asXML();
					if (isset($pa_options['stripOuterTag']) && ($pa_options['stripOuterTag'])) {
						$vs_output_tagname = $va_nodes->getName();
						$vs_xml = preg_replace("!^<{$vs_output_tagname}[^>]*>!i", "", $vs_xml);
						$vs_xml = preg_replace("!</{$vs_output_tagname}>$!i", "", $vs_xml);
					}
					$va_record_output[] = $vs_xml;	// and return what's inside
					
				} else {	
					// Replace fragments
					$vs_xml = $r_output->asXML();
					foreach($va_fragments as $vs_frag_id => $vs_fragment_xml) {
						$vs_xml = str_replace("[_{$vs_frag_id}_]", $vs_fragment_xml, $vs_xml);
					}
					
					//
					// Format XML to look nice
					//
					$o_dom = new DOMDocument('1.0');
					$o_dom->preserveWhiteSpace = false;
					$o_dom->formatOutput = true;
					$o_dom->loadXML($vs_xml);
					$va_record_output[] = $vs_output = $o_dom->saveXML();
				}
				
				
				if ($r_fp) {
					fputs($r_fp, $vs_output);
					
					if (!(isset($pa_options['returnOutput']) && $pa_options['returnOutput'])) {
						$vs_output = '';
					}
				}
			}
			
			if ($r_fp) {
				fclose($r_fp);
			}
			
			if ((isset($pa_options['fragment']) && $pa_options['fragment'])) {
				return $va_record_output;
			}
			
			if (is_null($pm_target) || (isset($pa_options['returnOutput']) && $pa_options['returnOutput'])) {
				if (isset($pa_options['returnAsString']) && $pa_options['returnAsString']) {
					return join('', $va_record_output);
				}
				return $va_record_output;
			}
			return true;
		}
		# -------------------------------------------------------
		/**
		 * Extract and format attributes
		 */
		private function _getAttributes($ps_tag, $pa_unit) {
			$va_attributes = array();
			foreach($pa_unit as $vs_tag => $vs_value) {
				if (!preg_match('![@]{1}!', $vs_tag)) { continue; }
				$va_tmp = preg_split('![@/]{1}!', $vs_tag);
				$vs_attr = array_pop($va_tmp);
				$vs_tag = array_pop($va_tmp);
				
				if (sizeof($va_tmp) > 0) {
					if ($vs_tag === $ps_tag) {
						$va_attributes[$vs_attr] = $vs_value;
					}
				}
			}
			
			return $va_attributes;
		}
		# -------------------------------------------------------
	}
?>