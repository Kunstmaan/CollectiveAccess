<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Utils/DataMigrationUtils.php :
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
 * @subpackage Utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
 	require_once(__CA_MODELS_DIR__.'/ca_entities.php');
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 
	class DataMigrationUtils {
		# -------------------------------------------------------
		/**
		 * @var encoding of source data
		 */
		static $s_source_encoding = 'ISO-8859-1';
		
		/** 
		 * @var encoding of target data (should almost always be UTF-8)
		 */
		static $s_target_encoding = 'UTF-8';
		
		# -------------------------------------------------------
		/**
		 * Sets the source text encoding to be used by DataMigrationUtils::transformTextEncoding()
		 */
		static function setSourceTextEncoding($ps_encoding) {
			DataMigrationUtils::$s_source_encoding = $ps_encoding;
		}
		# -------------------------------------------------------
		/** 
		 * Returns entity_id for the entity with the specified name, regardless of specified type. If the entity does not already 
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid entity field or attribute.
		 *
		 * @param array $pa_entity_name Array with values for entity label
		 * @param int $pn_type_id The type_id of the entity type to use if the entity needs to be created
		 * @param int $pn_locale_id The locale_id to use if the entity needs to be created (will be used for both the entity locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created entity records with. These values are *only* used for newly created entities; they will not be applied if the entity named already exists. The array keys should be names of ca_entities fields or valid entity attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options, which include:
		 *				outputErrors - if true, errors will be printed to console [default=true]
		 *				dontCreate - if true then new entities will not be created [default=false]
		 */
		static function getEntityID($pa_entity_name, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if(!isset($pa_options['outputErrors'])) { $pa_options['outputErrors'] = true; }
			
			$t_entity = new ca_entities();
			if (sizeof($va_entity_ids = $t_entity->getEntityIDsByName($pa_entity_name['forename'], $pa_entity_name['surname'])) == 0) {
				if (isset($pa_options['dontCreate']) && $pa_options['dontCreate']) { return false; }
				
				$t_entity->setMode(ACCESS_WRITE);
				$t_entity->set('locale_id', $pn_locale_id);
				$t_entity->set('type_id', $pn_type_id);
				$t_entity->set('source_id', isset($pa_values['source_id']) ? $pa_values['source_id'] : null);
				$t_entity->set('access', isset($pa_values['access']) ? $pa_values['access'] : 0);
				$t_entity->set('status', isset($pa_values['status']) ? $pa_values['status'] : 0);
				$t_entity->set('idno', isset($pa_values['idno']) ? $pa_values['idno'] : null);
				$t_entity->set('lifespan', isset($pa_values['lifespan']) ? $pa_values['lifespan'] : null);
				unset($pa_values['access']);	
				unset($pa_values['status']);
				unset($pa_values['idno']);
				unset($pa_values['source_id']);
				unset($pa_values['lifespan']);
				
				if (is_array($pa_values)) {
					foreach($pa_values as $vs_element => $va_value) { 					
						if (is_array($va_value)) {
							// array of values (complex multi-valued attribute)
							$t_entity->addAttribute(
								array_merge($va_value, array(
									'locale_id' => $pn_locale_id
								)), $vs_element);
						} else {
							// scalar value (simple single value attribute)
							if ($va_value) {
								$t_entity->addAttribute(array(
									'locale_id' => $pn_locale_id,
									$vs_element => $va_value
								), $vs_element);
							}
						}
					}
				}
				$t_entity->insert();
				
				if ($t_entity->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "ERROR INSERTING entity (".$pa_entity_name['forename']."/".$pa_entity_name['surname']."): ".join('; ', $t_entity->getErrors())."\n";
						print_R($pa_values);
					}
					return null;
				}
				$t_entity->addLabel($pa_entity_name, $pn_locale_id, null, true);
				
				
				$vn_entity_id = $t_entity->getPrimaryKey();
			} else {
				$vn_entity_id = array_shift($va_entity_ids);
			}
				
			return $vn_entity_id;
		}
		# -------------------------------------------------------
		/** 
		 *
		 */
		static function getListItemID($pm_list_code_or_id, $ps_item_idno, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if(!isset($pa_options['outputErrors'])) { $pa_options['outputErrors'] = true; }
			
			if (!($vn_list_id = ca_lists::getListID($pm_list_code_or_id))) { return null; }
			
			$t_list = new ca_lists();
			$t_item = new ca_list_items();
			
			if ($t_item->load(array('list_id' => $vn_list_id, 'idno' => $ps_item_idno))) {
				return $t_item->getPrimaryKey();
			}
			
			//
			// Need to create list item
			//
			if (!$t_list->load($vn_list_id)) {
				return null;
			}
			if ($t_item = $t_list->addItem($ps_item_idno, $pa_values['is_enabled'], $pa_values['is_default'], $pa_values['parent_id'], $pn_type_id, $ps_item_idno, '', (int)$pa_values['status'], (int)$pa_values['access'], $pa_values['rank'])) {
				$t_item->addLabel(
					array(
						'name_singular' => $pa_values['name_singular'] ? $pa_values['name_singular'] : $ps_item_idno,
						'name_plural' => $pa_values['name_plural'] ? $pa_values['name_plural'] : $ps_item_idno
					), $pn_locale_id, null, true
				);
				
				return $t_item->getPrimaryKey();
			}
			return null;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		static function transformTextEncoding($ps_text) {
			$ps_text = str_replace("‘", "'", $ps_text);
			$ps_text = str_replace("’", "'", $ps_text);
			$ps_text = str_replace("”", '"', $ps_text);
			$ps_text = str_replace("“", '"', $ps_text);
			$ps_text = str_replace("–", "-", $ps_text);
			$ps_text = str_replace("…", "...", $ps_text);
			return iconv(DataMigrationUtils::$s_source_encoding, DataMigrationUtils::$s_target_encoding, $ps_text);
		}
		# -------------------------------------------------------
		/**
		 * Takes a string and returns an array with the name parsed into pieces according to common heuristics
		 *
		 * @param string $ps_text The name text
		 * @param array $pa_options Optional array of options. Supported options are:
		 *		locale = locale code to use when applying rules; if omitted current user locale is employed
		 *
		 * @return array Array containing parsed name, keyed on ca_entity_labels fields (eg. forename, surname, middlename, etc.)
		 */
		static function splitEntityName($ps_text, $pa_options=null) {
			global $g_ui_locale;
			
			if (isset($pa_options['locale']) && $pa_options['locale']) {
				$vs_locale = $pa_options['locale'];
			} else {
				$vs_locale = $g_ui_locale;
			}
		
			if (file_exists($vs_lang_filepath = __CA_LIB_DIR__.'/ca/Utils/DataMigrationUtils/'.$vs_locale.'.lang')) {
				$o_config = Configuration::load($vs_lang_filepath);
				$va_titles = $o_config->getList('titles');
			} else {
				$o_config = null;
				$va_titles = array();
			}
			
			$va_name = array();
			if (strpos($ps_text, ',') !== false) {
				// is comma delimited
				$va_tmp = explode(',', $ps_text);
				$va_name['surname'] = $va_tmp[0];
				
				if(sizeof($va_tmp) > 1) {
					$va_name['forename'] = $va_tmp[1];
				}
			} else {
				// check for titles
				$ps_text = preg_replace('![^A-Za-z0-9 \-]+!', '', $ps_text);
				foreach($va_titles as $vs_title) {
					if (preg_match("!^({$vs_title})!", $ps_text, $va_matches)) {
						$va_name['prefix'] = $va_matches[1];
						$ps_text = str_replace($va_matches[1], '', $ps_text);
					}
				}
				
				$va_tmp = preg_split('![ ]+!', trim($ps_text));
				
				switch(sizeof($va_tmp)) {
					case 1:
						$va_name['surname'] = $ps_text;
						break;
					case 2:
						$va_name['forename'] = $va_tmp[0];
						$va_name['surname'] = $va_tmp[1];
						break;
					case 3:
						$va_name['forename'] = $va_tmp[0];
						$va_name['middlename'] = $va_tmp[1];
						$va_name['surname'] = $va_tmp[2];
						break;
					case 4:
					default:
						if (strpos($ps_text, ' '._t('and').' ') !== false) {
							$va_name['surname'] = array_pop($va_tmp);
							$va_name['forename'] = join(' ', $va_tmp);
						} else {
							$va_name['forename'] = array_shift($va_tmp);
							$va_name['middlename'] = array_shift($va_tmp);
							$va_name['surname'] = join(' ', $va_tmp);
						}
						break;
				}
			}
			
			foreach($va_name as $vs_k => $vs_v) {
				$va_name[$vs_k] = trim($vs_v);
			}
			
			return $va_name;
		}
		# -------------------------------------------------------
	}
?>