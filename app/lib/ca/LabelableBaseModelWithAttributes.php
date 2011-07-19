<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/LabelableBaseModelWithAttributes.php : base class for models that take application of bundles
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2011 Whirl-i-Gig
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
 
 define('__CA_LABEL_TYPE_PREFERRED__', 0);
 define('__CA_LABEL_TYPE_NONPREFERRED__', 1);
 define('__CA_LABEL_TYPE_ANY__', 2);
  
 require_once(__CA_LIB_DIR__.'/core/BaseModelWithAttributes.php');
 require_once(__CA_LIB_DIR__.'/core/BaseModel.php');
 require_once(__CA_LIB_DIR__.'/ca/ILabelable.php');
 require_once(__CA_APP_DIR__.'/models/ca_locales.php');
 require_once(__CA_APP_DIR__.'/models/ca_users.php');
 require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');
 
	class LabelableBaseModelWithAttributes extends BaseModelWithAttributes implements ILabelable {
		# ------------------------------------------------------------------
		static $s_label_cache = array();
		static $s_labels_by_id_cache = array();
		# ------------------------------------------------------------------
		public function __construct($pn_id=null) {
			parent::__construct($pn_id);
		}
		# ------------------------------------------------------------------
		/**
			Adds a label to the currently loaded row; the $pa_label_values array an associative array where keys are field names 
			and values are the field values; some label are defined by more than a single field (people's names for instance) which is why
			the label value is an array rather than a simple scalar value
			
			TODO: do checking when inserting preferred label values that a preferred value is not already defined for the locale.
		 */ 
		public function addLabel($pa_label_values, $pn_locale_id, $pn_type_id=null, $pb_is_preferred=false, $pn_status=0) {
			if (!($vn_id = $this->getPrimaryKey())) { return null; }
		
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName()))) { return null; }
			if ($this->inTransaction()) {
				$t_label->setTransaction($this->getTransaction());
			}
			foreach($pa_label_values as $vs_field => $vs_value) {
				if ($t_label->hasField($vs_field)) { 
					$t_label->set($vs_field, $vs_value); 
					if ($t_label->numErrors()) { 
						$this->errors = $t_label->errors; //array_merge($this->errors, $t_label->errors);
						return false;
					}
				}
			}
			$t_label->set('locale_id', $pn_locale_id);
			if ($t_label->hasField('type_id')) { $t_label->set('type_id', $pn_type_id); }
			if ($t_label->hasField('is_preferred')) { $t_label->set('is_preferred', $pb_is_preferred ? 1 : 0); }
			if ($t_label->hasField('status')) { $t_label->set('status', $pn_status); }
			
			$t_label->set($this->primaryKey(), $vn_id);
			
			$t_label->setMode(ACCESS_WRITE);
			
			$this->opo_app_plugin_manager->hookBeforeLabelInsert(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this, 'label_instance' => $t_label));
		
			$vn_label_id = $t_label->insert();
			
			$this->opo_app_plugin_manager->hookAfterLabelInsert(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this, 'label_instance' => $t_label));
		
			if ($t_label->numErrors()) { 
				$this->errors = $t_label->errors; //array_merge($this->errors, $t_label->errors);
				return false;
			}
			return $vn_label_id;
		}
		# ------------------------------------------------------------------
		/**
		 * Edit existing label
		 */
		public function editLabel($pn_label_id, $pa_label_values, $pn_locale_id, $pn_type_id=null, $pb_is_preferred=false, $pn_status=0) {
			if (!($vn_id = $this->getPrimaryKey())) { return null; }
			
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName()))) { return null; }
			
			if ($this->inTransaction()) {
				$t_label->setTransaction($this->getTransaction());
			}
			
			if (!($t_label->load($pn_label_id))) { return null; }
		
			$vb_has_changed = false;
			foreach($pa_label_values as $vs_field => $vs_value) {
				if ($t_label->hasField($vs_field)) { 
					$t_label->set($vs_field, $vs_value); 
					if ($t_label->numErrors()) { 
						$this->errors = $t_label->errors;
						return false;
					}
					
					if ($t_label->changed($vs_field)) { $vb_has_changed = true; }
				}
			}
			
			if (!$vb_has_changed) { return $t_label->getPrimaryKey(); }
			
			$t_label->set('locale_id', $pn_locale_id);
			if ($t_label->hasField('type_id')) { $t_label->set('type_id', $pn_type_id); }
			if ($t_label->hasField('is_preferred')) { $t_label->set('is_preferred', $pb_is_preferred ? 1 : 0); }
			if ($t_label->hasField('status')) { $t_label->set('status', $pn_status); }
			
			$t_label->set($this->primaryKey(), $vn_id);
			
			$t_label->setMode(ACCESS_WRITE);
			
			$this->opo_app_plugin_manager->hookBeforeLabelUpdate(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this, 'label_instance' => $t_label));
		
			$t_label->update();
			
			$this->opo_app_plugin_manager->hookAfterLabelUpdate(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this, 'label_instance' => $t_label));
		
			
			if ($t_label->numErrors()) { 
				$this->errors = $t_label->errors;
				return false;
			}
			return $t_label->getPrimaryKey();
		}
		# ------------------------------------------------------------------
		/**
		 * Remove specified label
		 */
 		public function removeLabel($pn_label_id) {
 			if (!$this->getPrimaryKey()) { return null; }
 			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName()))) { return null; }
 			
 			if (!$t_label->load($pn_label_id)) { return null; }
 			if (!($t_label->get($this->primaryKey()) == $this->getPrimaryKey())) { return null; }
 			
 			if ($this->inTransaction()) {
				$t_label->setTransaction($this->getTransaction());
			}
 			$t_label->setMode(ACCESS_WRITE);
 			
 			$this->opo_app_plugin_manager->hookBeforeLabelDelete(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this, 'label_instance' => $t_label));
		
 			$t_label->delete();
 			
 			$this->opo_app_plugin_manager->hookAfterLabelDelete(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this, 'label_instance' => $t_label));
		
 			if ($t_label->numErrors()) { 
				$this->errors = array_merge($this->errors, $t_label->errors);
				return false;
			}
 			return true;
 		}
		# ------------------------------------------------------------------
		/**
		 * Remove all labels, preferred and non-preferred, attached to this row
		 */
 		public function removeAllLabels() {
 			if (!$this->getPrimaryKey()) { return null; }
 			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName()))) { return null; }
 			
 			$va_labels = $this->getLabels();
 			foreach($va_labels as $vn_id => $va_labels_by_locale) {
 				foreach($va_labels_by_locale as $vn_locale_id => $va_labels) {
 					foreach($va_labels as $vn_i => $va_label) {
 						$this->removeLabel($va_label['label_id']);
 					}
 				}
 			}
 		}
 		# ------------------------------------------------------------------
 		/**
 		 *
 		 *
 		 */
 		public function loadByLabel($pa_label_values, $pa_table_values=null) {
 			$t_instance = $this->getLabelTableInstance();
 			
 			$o_db = $this->getDb();
 			
 			$va_wheres = array();
 			foreach($pa_label_values as $vs_fld => $vs_val) {
 				if ($t_instance->hasField($vs_fld)) {
 					$va_wheres[$this->getLabelTableName().".".$vs_fld." = ?"] = $vs_val;
 				}
 			}
 			
 			if (is_array($pa_table_values)) {
				foreach($pa_table_values as $vs_fld => $vs_val) {
					if ($t_instance->hasField($vs_fld)) {
						$va_wheres[$this->tableName().".".$vs_fld." = ?"] = $vs_val;
					}
				}
			}
 			
 			$vs_sql = "
 				SELECT ".$this->getLabelTableName().".".$this->primaryKey()."
 				FROM ".$this->getLabelTableName()."
 				INNER JOIN  ".$this->tableName()." ON ".$this->tableName().".".$this->primaryKey()." = ".$this->getLabelTableName().".".$this->primaryKey()." 
 				WHERE	
 			". join(" AND ", array_keys($va_wheres));
 			
 			$qr_hits = $o_db->query($vs_sql, array_values($va_wheres));
 			if($qr_hits->nextRow()) {
 				if($this->load($qr_hits->get($this->primaryKey()))) {
 					return true;
 				}
 			}
 			return false;
 		}
 		# ------------------------------------------------------------------
 		/**
 		 *
 		 *
 		 * @param $ps_field -
 		 * @param $pa_options -
 		 *		returnAsArray - 
 		 * 		delimiter -
 		 *		template -
 		 *		locale -
 		 *		returnAllLocales - Returns requested value in all locales for which it is defined. Default is false. Note that this is not supported for hierarchy specifications (eg. ca_objects.hierarchy).
 		 *		direction - For hierarchy specifications (eg. ca_objects.hierarchy) this determines the order in which the hierarchy is returned. ASC will return the hierarchy root first while DESC will return it with the lowest node first. Default is ASC.
 		 *		top - For hierarchy specifications (eg. ca_objects.hierarchy) this option, if set, will limit the returned hierarchy to the first X nodes from the root down. Default is to not limit.
 		 *		bottom - For hierarchy specifications (eg. ca_objects.hierarchy) this option, if set, will limit the returned hierarchy to the first X nodes from the lowest node up. Default is to not limit.
 		 * 		hierarchicalDelimiter - Text to place between items in a hierarchy for a hierarchical specification (eg. ca_objects.hierarchy) when returning as a string
 		 *		removeFirstItems - If set to a non-zero value, the specified number of items at the top of the hierarchy will be omitted. For example, if set to 2, the root and first child of the hierarchy will be omitted. Default is zero (don't delete anything).
 		 */
		public function get($ps_field, $pa_options=null) {
			
			$vs_template = 				(isset($pa_options['template'])) ? $pa_options['template'] : null;
			$vb_return_as_array = 		(isset($pa_options['returnAsArray'])) ? (bool)$pa_options['returnAsArray'] : false;
			$vb_return_all_locales =	(isset($pa_options['returnAllLocales'])) ? (bool)$pa_options['returnAllLocales'] : false;
			$vs_delimiter =				(isset($pa_options['delimiter'])) ? $pa_options['delimiter'] : ' ';
			if ($vb_return_all_locales && !$vb_return_as_array) { $vb_return_as_array = true; }
		
			// if desired try to return values in a preferred language/locale
			$va_preferred_locales = null;
			if (isset($pa_options['locale']) && $pa_options['locale']) {
				$va_preferred_locales = array($pa_options['locale']);
			}
		
			// does get refer to an attribute?
			$va_tmp = explode('.', $ps_field);
			
			if (($va_tmp[1] == 'hierarchy') && (sizeof($va_tmp) == 2)) {
				$va_tmp[2] = 'preferred_labels';
				$ps_field = join('.', $va_tmp);
			}
			
			$t_label = $this->getLabelTableInstance();
			
			$t_instance = $this;
			if ((sizeof($va_tmp) >= 3 && ($va_tmp[2] == 'preferred_labels' && (!$va_tmp[3] || $t_label->hasField($va_tmp[3])))) || ($va_tmp[1] == 'hierarchy')) {
				switch($va_tmp[1]) {
					case 'parent':
						if (($this->isHierarchical()) && ($vn_parent_id = $this->get($this->getProperty('HIERARCHY_PARENT_ID_FLD')))) {
							$t_instance = $this->getAppDatamodel()->getInstanceByTableNum($this->tableNum());
							if (!$t_instance->load($vn_parent_id)) {
								$t_instance = $this;
							} else {
								unset($va_tmp[1]);
								$va_tmp = array_values($va_tmp);
							}
						}
						break;
					case 'children':
						if ($this->isHierarchical()) {
							unset($va_tmp[1]);					// remove 'children' from field path
							$va_tmp = array_values($va_tmp);
							$vs_childless_path = join('.', $va_tmp);
							
							$va_data = array();
							$va_children_ids = $this->getHierarchyChildren(null, array('idsOnly' => true));
							
							$t_instance = $this->getAppDatamodel()->getInstanceByTableNum($this->tableNum());
							
							foreach($va_children_ids as $vn_child_id) {
								if ($t_instance->load($vn_child_id)) {
									$va_data = array_merge($va_data, $t_instance->get($vs_childless_path, array_merge($pa_options, array('returnAsArray' => true))));
								}
							}
							
							if ($vb_return_as_array) {
								return $va_data;
							} else {
								return join($vs_delimiter, $va_data);
							}
						}
						break;
					case 'hierarchy':
						$vs_direction =(isset($pa_options['direction'])) ? strtoupper($pa_options['direction']) : null;
						if (!in_array($vs_direction, array('ASC', 'DESC'))) { $vs_direction = 'ASC'; }
						
						$vn_top = (int)(isset($pa_options['top'])) ? strtoupper($pa_options['top']) : 0;
						if ($vn_top < 0) { $vn_top = 0; }
						$vn_bottom = (int)(isset($pa_options['bottom'])) ? strtoupper($pa_options['bottom']) : 0;
						if ($vn_bottom < 0) { $vn_bottom = 0; }
						
						$vs_pk = $this->primaryKey();
						$vs_label_table_name = $this->getLabelTableName();
						$t_label_instance = $this->getLabelTableInstance();
						$vs_display_field = ($t_label_instance->hasField($va_tmp[2])) ? $va_tmp[2] : $this->getLabelDisplayField();
						if (!($va_ancestor_list = $this->getHierarchyAncestors(null, array(
							'additionalTableToJoin' => $vs_label_table_name, 
							'additionalTableJoinType' => 'LEFT',
							'additionalTableSelectFields' => array($vs_display_field, 'locale_id'),
							'additionalTableWheres' => array('('.$vs_label_table_name.'.is_preferred = 1 OR '.$vs_label_table_name.'.is_preferred IS NULL)'),
							'includeSelf' => true
						)))) {
							$va_ancestor_list = array();
						}
						
						// remove root and children if so desired
						if (isset($pa_options['removeFirstItems']) && ((int)$pa_options['removeFirstItems'] > 0)) {
							for($vn_i=0; $vn_i < (int)$pa_options['removeFirstItems']; $vn_i++) {
								array_pop($va_ancestor_list);
							}
						}
						
						if ($vs_display_field != $va_tmp[2]) {
							if ($this->hasField($va_tmp[2])) {
								$vs_display_field = $va_tmp[2];
							}
						}
						
						$va_tmp = array();
						foreach($va_ancestor_list as $vn_i => $va_item) {
							if ($vs_label = $va_item['NODE'][$vs_display_field]) {
								$va_tmp[$va_item['NODE'][$vs_pk]] = $vs_label;
							}
						}
							
						
						if ($vn_top > 0) {
							$va_tmp = array_slice($va_tmp, sizeof($va_tmp) - $vn_top, $vn_top, true);
						} else {
							if ($vn_bottom > 0) {
								$va_tmp = array_slice($va_tmp, 0, $vn_bottom, true);
							}
						}
						
						if ($vs_direction == 'ASC') {
							$va_tmp = array_reverse($va_tmp);
						}
						
						if ($vb_return_as_array) {
							return $va_tmp;
						} else {
							$vs_hier_delimiter =	(isset($pa_options['hierarchicalDelimiter'])) ? $pa_options['hierarchicalDelimiter'] : $pa_options['delimiter'];
							return join($vs_hier_delimiter, $va_tmp);
						}
						break;
				}
			}
			
			switch(sizeof($va_tmp)) {
				case 1:
					switch($va_tmp[0]) {
						# ---------------------------------------------
						case 'preferred_labels':
							if (!$vb_return_as_array) {
								return $t_instance->getLabelForDisplay(false, $pa_options['locale']);
							} else {
								$va_labels = $t_instance->getPreferredLabels(null, false);
								if ($vb_return_all_locales) {
									return $va_labels;
								} else {
									// Simplify array by getting rid of third level array which is unnecessary since
									// there is only ever one preferred label for a locale
									$va_labels = caExtractValuesByUserLocale($va_labels, null, $va_preferred_locales);
									$va_processed_labels = array();
									foreach($va_labels as $vn_label_id => $va_label_list) {
										$va_processed_labels[$vn_label_id] = $va_label_list[0];
									}
									
									return $va_processed_labels;
								}
							}
							break;
						# ---------------------------------------------
						case 'nonpreferred_labels':
							if (!$vb_return_as_array) {
								$va_labels = caExtractValuesByUserLocale($t_instance->getNonPreferredLabels(null, false));
								$vs_disp_field = $this->getLabelDisplayField();
								$va_processed_labels = array();
								foreach($va_labels as $vn_label_id => $va_label_list) {
									foreach($va_label_list as $vn_i => $va_label) {
										$va_processed_labels[] = $va_label[$vs_disp_field];
									}
								}
								
								return join($vs_delimiter, $va_processed_labels);
							} else {
								$va_labels = $t_instance->getNonPreferredLabels(null, false);
								if ($vb_return_all_locales) {
									return $va_labels;
								} else {
									return caExtractValuesByUserLocale($va_labels, null, $va_preferred_locales);
								}
							}
							break;
						# ---------------------------------------------
					}
					break;
				case 2:
				case 3:
					if ($va_tmp[0] === $t_instance->tableName()) {
						switch($va_tmp[1]) {
							# ---------------------------------------------
							case 'preferred_labels':
								if (!$vb_return_as_array) {
									if (isset($va_tmp[2]) && ($va_tmp[2])) {
										$va_labels = caExtractValuesByUserLocale($t_instance->getPreferredLabels(), null, $va_preferred_locales);
										
										foreach($va_labels as $vn_row_id => $va_label_list) {
											return $va_label_list[0][$va_tmp[2]];
										}
										return null;
									} else {
										return $t_instance->getLabelForDisplay(false, $pa_options['locale']);
									}
								} else {
									$va_labels = $t_instance->getPreferredLabels(null, false);
									
									if (!$vb_return_all_locales) {
										// Simplify array by getting rid of third level array which is unnecessary since
										// there is only ever one preferred label for a locale
										$va_labels = caExtractValuesByUserLocale($va_labels, null, $va_preferred_locales);
										$va_processed_labels = array();
										foreach($va_labels as $vn_label_id => $va_label_list) {
											$va_processed_labels[$vn_label_id] = $va_label_list[0];
										}
										
										$va_labels = $va_processed_labels;
									}
									
									if (isset($va_tmp[2]) && ($va_tmp[2])) {		// specific field
										if ($vb_return_all_locales) {
											foreach($va_labels as $vn_label_id => $va_labels_by_locale) {
												foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
													foreach($va_label_list as $vn_i => $va_label) {
														$va_labels[$vn_label_id][$vn_locale_id][$vn_i] = $va_label[$va_tmp[2]];
													}
												}
											}
										} else {
											// get specified field value
											foreach($va_labels as $vn_label_id => $va_label_info) {
												$va_labels[$vn_label_id] = $va_label_info[$va_tmp[2]];
											}
										}
									}
									
									return $va_labels;
								}
								break;
							# ---------------------------------------------
							case 'nonpreferred_labels':
								if (!$vb_return_as_array) {
									if (isset($va_tmp[2]) && ($va_tmp[2])) {
										$vs_disp_field = $va_tmp[2];
									} else {
										$vs_disp_field = $this->getLabelDisplayField();
									}
									$va_labels = caExtractValuesByUserLocale($t_instance->getNonPreferredLabels(), null, $va_preferred_locales);
									
									$va_values = array();
									foreach($va_labels as $vn_row_id => $va_label_list) {
										foreach($va_label_list as $vn_i => $va_label) {
											$va_values[] = $va_label[$vs_disp_field];
										}
									}
									return join($vs_delimiter, $va_values);
								} else {
									$va_labels = $t_instance->getNonPreferredLabels(null, false);
									
									if (!$vb_return_all_locales) {
										$va_labels = caExtractValuesByUserLocale($va_labels, null, $va_preferred_locales);
									}
									
									if (isset($va_tmp[2]) && ($va_tmp[2])) {		// specific field
										if ($vb_return_all_locales) {
											foreach($va_labels as $vn_label_id => $va_labels_by_locale) {
												foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
													foreach($va_label_list as $vn_i => $va_label) {
														$va_labels[$vn_label_id][$vn_locale_id][$vn_i] = $va_label[$va_tmp[2]];
													}
												}
											}
										} else {
											// get specified field value
											foreach($va_labels as $vn_label_id => $va_label_info) {
												foreach($va_label_info as $vn_id => $va_label) {
													$va_labels[$vn_label_id] = $va_label[$va_tmp[2]];
												}
											}
										}
									}
									
									return $va_labels;
								}
								break;
							# ---------------------------------------------
						}
					}
					break;
			}
			return parent::get($ps_field, $pa_options);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns text of preferred label in the user's currently selected locale, or else falls back to
	     * whatever locale is available
	     *
	     * @param boolean $pb_dont_cache If true then fetched label is not cached and reused for future invokations. Default is true (don't cache) because in some cases [like when editing labels] caching can cause undesirable side-effects. However in read-only situations it measurably increase performance.
		 * @param mixed $pm_locale If set to a valid locale_id or locale code value will be returned in specified language instead of user's default language, assuming the label is available in the specified language. If it is not the value will be returned in a language that is available using the standard fall-back procedure.
		 * @param array $pa_options Array of options. Supported options are those of getLabels()
		 * @return string The label value
		 */
 		public function getLabelForDisplay($pb_dont_cache=true, $pm_locale=null, $pa_options=null) {
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
			
			$va_preferred_locales = null;
			if ($pm_locale) {
				$va_preferred_locales = array($pm_locale);
			}
			
			$va_tmp = caExtractValuesByUserLocale($this->getLabels(null, __CA_LABEL_TYPE_PREFERRED__, $pb_dont_cache, $pa_options), null, $va_preferred_locales, array());
			$va_label = array_shift($va_tmp);
			return $va_label[0][$t_label->getDisplayField()];
 			
 		}
		# ------------------------------------------------------------------
		/**
		 * Returns a list of fields that should be displayed in user interfaces for labels
		 *
		 * @return array List of field names
		 */
		public function getLabelUIFields() {
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
			return $t_label->getUIFields();
		}
		# ------------------------------------------------------------------
		/**
		 * Returns the name of the field that is used to display the label
		 *
		 * @return string Name of display field
		 */
		public function getLabelDisplayField() {
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
			return $t_label->getDisplayField();
		}
		# ------------------------------------------------------------------
		/**
		 * Returns the name of the field that is used to sort label content
		 *
		 * @return string Name of sort field
		 */
		public function getLabelSortField() {
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
			return $t_label->getSortField();
		}
		# ------------------------------------------------------------------
		/** 
		 * Extracts values for UI fields from request and return an associative array
		 * If none of the UI fields are set to *anything* then we return NULL; this is a signal
		 * to ignore the label input (ie. it was a blank form bundle)
		 *
		 * @param HTTPRequest $po_request Request object
		 * @param string $ps_form_prefix
		 * @param string $ps_label_id
		 * @param boolean $ps_is_preferred
		 *
		 * @return array Array of values or null is no values were set in the request
		 */
		public function getLabelUIValuesFromRequest($po_request, $ps_form_prefix, $ps_label_id, $pb_is_preferred=false) {
			$va_fields = $this->getLabelUIFields();
			
			$vb_value_set = false;
			$va_values = array();
			
			if (is_null($pb_is_preferred)) {
				$vs_pref_key = '';
			} else {
				$vs_pref_key = ($pb_is_preferred ? '_Pref' : '_NPref');
			}
			foreach($va_fields as $vs_field) {
				if ($vs_val = $po_request->getParameter($ps_form_prefix.$vs_pref_key.$vs_field.'_'.$ps_label_id, pString)) {
					$va_values[$vs_field] = $vs_val;
					$vb_value_set = true;
				} else {
					$va_values[$vs_field] = '';
				}
			}
			
			return ($vb_value_set) ? $va_values: null;
		}
		# ------------------------------------------------------------------
		/** 
		 * Returns labels associated with this row. By default all labels - preferred and non-preferred, and from all locales -
		 * are returned. You can limit the returned labels to specified locales by passing a list of locale_ids (numeric ids, *not* locale codes)
		 * in $pn_locale_ids. Similarly you can limit return labels to preferred on non-preferred by setting $pn_mode to __CA_LABEL_TYPE_PREFERRED__
		 * or __CA_LABEL_TYPE_NONPREFERRED__
		 *
		 * getLabels() returns an associated array keyed by the primary key of the item the label is attached to; each value is an array keyed by locale_id, the values of which
		 * is a list of associative arrays with the label table data. This return format is designed to be digested by the displayHelper function caExtractValuesByUserLocale()
		 *
		 * @param array $pa_locale_ids
		 * @param int $pn_mode
		 * @param boolean $pb_dont_cache
		 * @param array $pa_options Array of options. Supported options are:
		 *			row_id = The row_id to return labels for. If omitted the id of the currently loaded row is used. If row_id is not set and now row is loaded then getLabels() will return null.
		 *
		 * @return array List of labels
		 */
 		public function getLabels($pa_locale_ids=null, $pn_mode=__CA_LABEL_TYPE_ANY__, $pb_dont_cache=true, $pa_options=null) {
 			if (!($vn_id = $this->getPrimaryKey()) && !(isset($pa_options['row_id']) && ($vn_id = $pa_options['row_id']))) { return null; }
 			if (!$pb_dont_cache && is_array($va_tmp = LabelableBaseModelWithAttributes::$s_label_cache[$this->tableName().'/'.$vn_id.'/'.intval($pn_mode)])) {
 				return $va_tmp;
 			}
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
 			
 			$vs_label_where_sql = 'WHERE (l.'.$this->primaryKey().' = ?)';
 			$vs_locale_join_sql = '';
 			
 			if ($pa_locale_ids) {
 				$vs_label_where_sql .= ' AND (l.locale_id IN ('.join(',', $pa_locale_ids).'))';
 			}
 			$vs_locale_join_sql = 'INNER JOIN ca_locales AS loc ON loc.locale_id = l.locale_id';
 			
 			if ($t_label->hasField('is_preferred')) {
 				switch($pn_mode) {
 					case __CA_LABEL_TYPE_PREFERRED__:
 						$vs_label_where_sql .= ' AND (l.is_preferred = 1)';
 						break;
 					case __CA_LABEL_TYPE_NONPREFERRED__:
 						$vs_label_where_sql .= ' AND (l.is_preferred = 0)';
 						break;
 					default:
 						// noop
 						break;
 				}
 			}
 			
 			$o_db = $this->getDb();
 			
 			$qr_res = $o_db->query("
 				SELECT l.*, loc.country locale_country, loc.language locale_language, loc.dialect locale_dialect, loc.name locale_name
 				FROM ".$this->getLabelTableName()." l
 				$vs_locale_join_sql
 				$vs_label_where_sql
 				ORDER BY
 					loc.name
 			", (int)$vn_id);
 			
 			$va_labels = array();
 			while($qr_res->nextRow()) {
 				$va_labels[$vn_id][$qr_res->get('locale_id')][] = array_merge($qr_res->getRow(), array('form_element' => $t_label->htmlFormElement($this->getLabelDisplayField(), null)));
 			}
 			
 			LabelableBaseModelWithAttributes::$s_label_cache[$this->tableName().'/'.$vn_id.'/'.intval($pn_mode)] = $va_labels;
 			
 			return $va_labels;
 		}
 		# ------------------------------------------------------------------
		/** 
		 * Returns number of preferred labels for the current row
		 *
		 * @return int Number of labels
		 */
 		public function getPreferredLabelCount() {
 			if (!$this->getPrimaryKey()) { return null; }
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
			
			$o_db = $this->getDb();
 			
 			if (!$t_label->hasField('is_preferred')) { 
 				$qr_res = $o_db->query("
					SELECT l.label_id 
					FROM ".$this->getLabelTableName()." l
					WHERE 
						(l.".$this->primaryKey()." = ?)
				", $this->getPrimaryKey());
 			} else {
				$qr_res = $o_db->query("
					SELECT l.label_id 
					FROM ".$this->getLabelTableName()." l
					WHERE 
						(l.is_preferred = 1) AND (l.".$this->primaryKey()." = ?)
				", $this->getPrimaryKey());
			}
 			
 			return $qr_res->numRows();
		}
		# ------------------------------------------------------------------
		/** 
		 * Creates a default label when none exists
		 *
		 * @return boolean True on success, false on error
		 */
 		public function addDefaultLabel() {
 			if (!$this->getPreferredLabelCount()) {
				$t_locale = new ca_locales();
				$va_locale_list = $t_locale->getLocaleList();
				$vn_locale_id = array_shift(array_keys($va_locale_list));
				return $this->addLabel(
					array($this->getLabelDisplayField() => '['._t('BLANK').']'),
					$vn_locale_id,
					null,
					true
				);
			}
			return false;
 		}
		# ------------------------------------------------------------------
		/** 
		 * Returns a list of preferred labels, optionally limited to the locales specified in the array $pa_locale_ids.
		 * The returned list is an array with the same structure as returned by getLabels()
		 */
 		public function getPreferredLabels($pa_locale_ids=null, $pb_dont_cache=true, $pa_options=null) {
			return $this->getLabels($pa_locale_ids, __CA_LABEL_TYPE_PREFERRED__, $pb_dont_cache, $pa_options);
		}
		# ------------------------------------------------------------------
		/** 
		 * Returns a list of non-preferred labels, optionally limited to the locales specified in the array $pa_locale_ids.
		 * The returned list is an array with the same structure as returned by getLabels()
		 */
 		public function getNonPreferredLabels($pa_locale_ids=null, $pb_dont_cache=true, $pa_options=null) {
			return $this->getLabels($pa_locale_ids, __CA_LABEL_TYPE_NONPREFERRED__, $pb_dont_cache, $pa_options);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns name of table in database containing labels for the current table
		 * The value is set in a property in the calling sub-class
		 *
		 * @return string Name of label table
		 */
		public function getLabelTableName() {
			return isset($this->LABEL_TABLE_NAME) ? $this->LABEL_TABLE_NAME : null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns instance of model class for label table
		 *
		 * @return BaseLabel Instance of label model
		 */
		public function getLabelTableInstance() {
			if ($vs_label_table_name = $this->getLabelTableName()) {
				return $this->_DATAMODEL->getInstanceByTableName($vs_label_table_name, true);
			}
			return null;
		}
		# ------------------------------------------------------------------
		/** 
		 * Returns HTML form bundle (for use in a form) for preferred labels attached to this row
		 *
		 * @param HTTPRequest $po_request The current request
		 * @param string $ps_form_name
		 * @param string $ps_placement_code
		 * @param array $pa_bundle_settings
		 * @param array $pa_options Array of options. Supported options are 
		 *			noCache = If set to true then label cache is bypassed; default is true
		 *
		 * @return string Rendered HTML bundle
		 */
		public function getPreferredLabelHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
			global $g_ui_locale;
			
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
			
			$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
			
			if(!is_array($pa_options)) { $pa_options = array(); }
			if (!isset($pa_options['dontCache'])) { $pa_options['dontCache'] = true; }
			
			$o_view->setVar('id_prefix', $ps_form_name.'_Pref');
			$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
			
			$o_view->setVar('labels', $va_labels = $this->getPreferredLabels(null, $pa_options['dontCache']));
			$o_view->setVar('t_label', $t_label);
			$o_view->setVar('settings', $pa_bundle_settings);
			$o_view->setVar('add_label', isset($pa_bundle_settings['add_label'][$g_ui_locale]) ? $pa_bundle_settings['add_label'][$g_ui_locale] : null);
			
			// generate list of inital form values; the label bundle Javascript call will
			// use the template to generate the initial form
			$va_inital_values = array();
			$va_new_labels_to_force_due_to_error = array();
			
			if ($this->getPrimaryKey()) {
				if (sizeof($va_labels)) {
					foreach ($va_labels as $va_labels_by_locale) {
						foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
							foreach($va_label_list as $va_label) {
								$va_inital_values[$va_label['label_id']] = $va_label;
							}
						}
					}
				}
			} else {
				if ($this->numErrors()) {
					foreach($_REQUEST as $vs_key => $vs_value ) {
						if (!preg_match('/'.$ps_placement_code.$ps_form_name.'_Pref'.'locale_id_new_([\d]+)/', $vs_key, $va_matches)) { continue; }
						$vn_c = intval($va_matches[1]);
						if ($vn_new_label_locale_id = $po_request->getParameter($ps_placement_code.$ps_form_name.'_Pref'.'locale_id_new_'.$vn_c, pString)) {
							if(is_array($va_label_values = $this->getLabelUIValuesFromRequest($po_request, $ps_placement_code.$ps_form_name, 'new_'.$vn_c, true))) {
								$va_label_values['locale_id'] = $vn_new_label_locale_id;
								$va_new_labels_to_force_due_to_error[] = $va_label_values;
							}
						}
					}
				}
			}
			$o_view->setVar('new_labels', $va_new_labels_to_force_due_to_error);
			$o_view->setVar('label_initial_values', $va_inital_values);
			
			return $o_view->render($this->getLabelTableName().'_preferred.php');
		}
		# ------------------------------------------------------------------
		/** 
		 * Returns HTML form bundle (for use in a form) for non-preferred labels attached to this row
		 *
		 * @param HTTPRequest $po_request The current request
		 * @param string $ps_form_name
		 * @param string $ps_placement_code
		 * @param array $pa_bundle_settings
		 * @param array $pa_options Array of options. Supported options are 
		 *			noCache = If set to true then label cache is bypassed; default is true
		 *
		 * @return string Rendered HTML bundle
		 */
		public function getNonPreferredLabelHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
			global $g_ui_locale;
			
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
			
			$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
			
			if(!is_array($pa_options)) { $pa_options = array(); }
			if (!isset($pa_options['dontCache'])) { $pa_options['dontCache'] = true; }
			
			$o_view->setVar('id_prefix', $ps_form_name.'_NPref');
			$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
			$o_view->setVar('labels', $va_labels = $this->getNonPreferredLabels(null, $pa_options['dontCache']));
			$o_view->setVar('t_label', $t_label);
			$o_view->setVar('settings', $pa_bundle_settings);
			$o_view->setVar('add_label', isset($pa_bundle_settings['add_label'][$g_ui_locale]) ? $pa_bundle_settings['add_label'][$g_ui_locale] : null);
			
			$va_new_labels_to_force_due_to_error = array();
			$va_inital_values = array();
			
			if ($this->getPrimaryKey()) {
				// generate list of inital form values; the label bundle Javascript call will
				// use the template to generate the initial form
				if (sizeof($va_labels)) {
					foreach ($va_labels as $vn_item_id => $va_labels_by_locale) {
						foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
							foreach($va_label_list as $va_label) {
								$va_inital_values[$va_label['label_id']] = $va_label;
							}
						}
					}
				}
			} else {
				if ($this->numErrors()) {
					foreach($_REQUEST as $vs_key => $vs_value ) {
						if (!preg_match('/'.$ps_placement_code.$ps_form_name.'_NPref'.'locale_id_new_([\d]+)/', $vs_key, $va_matches)) { continue; }
						$vn_c = intval($va_matches[1]);
						if ($vn_new_label_locale_id = $po_request->getParameter($ps_placement_code.$ps_form_name.'_NPref'.'locale_id_new_'.$vn_c, pString)) {
							if(is_array($va_label_values = $this->getLabelUIValuesFromRequest($po_request, $ps_placement_code.$ps_form_name, 'new_'.$vn_c, true))) {
								$va_label_values['locale_id'] = $vn_new_label_locale_id;
								$va_new_labels_to_force_due_to_error[] = $va_label_values;
							}
						}
					}
				}
			}
			
			$o_view->setVar('new_labels', $va_new_labels_to_force_due_to_error);
			$o_view->setVar('label_initial_values', $va_inital_values);
			
			return $o_view->render($this->getLabelTableName().'_nonpreferred.php');
		}
		# ------------------------------------------------------------------
		/** 
		 * Returns array of preferred labels appropriate for locale setting of current user and row
		 * key'ed by label_id; values are arrays of label field values.
		 *
		 * NOTE: the returned list is *not* a complete list of preferred labels but rather
		 * a list of labels selected for display to the current user based upon the user's locale setting
		 * and the locale setting of the row
		 *
		 * @param boolean $pb_dont_cache If true label cache is bypassed; default is false
		 * @param array $pa_options Array of options. Supported options are those of getLabels()
		 *
		 * @return array List of labels
		 */
		public function getDisplayLabels($pb_dont_cache=false, $pa_options=null) {
			return caExtractValuesByUserLocale($this->getPreferredLabels(null, $pb_dont_cache, $pa_options), null, null, array());
		}
		# ------------------------------------------------------------------
		/**
		 * Returns list of valid display modes as set in user_pref_defs.conf (via ca_users class)
		 *
		 * @return array List of modes
		 */
		private function getValidLabelDisplayModes() {
			$t_user = new ca_users();
			$va_pref_info = $t_user->getPreferenceInfo('cataloguing_display_label_mode');
			return array_values($va_pref_info['choiceList']);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns associative array, keyed by primary key value with values being
		 * the preferred label of the row from a suitable locale, ready for display 
		 * 
		 * @param array $pa_ids indexed array of primary key values to fetch labels for
		 * @param array $pa_options Optional array of options. Supported options include:
		 *								returnAllLocales = if set to true, an array indexed by row_id and then locale_id will be returned
		 * @return array An array of preferred labels in the current locale indexed by row_id, unless returnAllLocales is set, in which case the array includes preferred labels in all available locales and is indexed by row_id and locale_id
		 */
		public function getPreferredDisplayLabelsForIDs($pa_ids, $pa_options=null) {
			$va_ids = array();
			foreach($pa_ids as $vn_id) {
				if (intval($vn_id) > 0) { $va_ids[] = intval($vn_id); }
			}
			if (!is_array($va_ids) || !sizeof($va_ids)) { return array(); }
			
			$vs_cache_key = md5(print_r($pa_ids, true).'/'.print_R($pa_options, true));
			if (!isset($pa_options['noCache']) && !$pa_options['noCache'] && LabelableBaseModelWithAttributes::$s_labels_by_id_cache[$vs_cache_key]) {
				return LabelableBaseModelWithAttributes::$s_labels_by_id_cache[$vs_cache_key];
			}
			
			$o_db = $this->getDb();
			
			$vs_display_field = $this->getLabelDisplayField();
			$vs_pk = $this->primaryKey();
			
			$vs_preferred_sql = '';
			
			if (($t_label_instance = $this->getLabelTableInstance()) && ($t_label_instance->hasField('is_preferred'))) {
				$vs_preferred_sql = "AND (is_preferred = 1)";
			}
			$va_labels = array();
			$qr_res = $o_db->query("
				SELECT {$vs_pk}, {$vs_display_field}, locale_id
				FROM ".$this->getLabelTableName()."
				WHERE
					({$vs_pk} IN (".join(',', $va_ids).")) {$vs_preferred_sql}
				ORDER BY
					{$vs_display_field}
			");
			
			
			while($qr_res->nextRow()) {
				$va_labels[$qr_res->get($vs_pk)][$qr_res->get('locale_id')] = $qr_res->get($vs_display_field);
			}
			
			if (isset($pa_options['returnAllLocales']) && $pa_options['returnAllLocales']) {
				return LabelableBaseModelWithAttributes::$s_labels_by_id_cache[$vs_cache_key] = $va_labels;
			}
			
			return LabelableBaseModelWithAttributes::$s_labels_by_id_cache[$vs_cache_key] = caExtractValuesByUserLocale($va_labels);
		}
		# ------------------------------------------------------------------
		# Hierarchies
		# ------------------------------------------------------------------
		/**
		 * Returns hierarchy (if labelable table is hierarchical) with labels included as array
		 * indexed first by table primary key and then by locale_id of label (the standard format
		 * suitable for processing by caExtractValuesByUserLocale())
		 *
		 * @param int $pn_id Optional row_id to use as root of returned hierarchy. If omitted hierarchy root is used.
		 *
		 * @return array Array of row data, key'ed on row primary key and locale_id. Values are arrays of field values from rows in the hierarchy.
		 */ 
		public function getHierarchyWithLabels($pn_id=null) {
			if(!($qr_res = $this->getHierarchy($pn_id, array('additionalTableToJoin' => $this->getLabelTableName())))) { return null; }
			
			$vs_pk = $this->primaryKey();
			$va_hier = array();
			while($qr_res->nextRow()) {
				$va_hier[$qr_res->get($vs_pk)][$qr_res->get('locale_id')] = $qr_res->getRow();
			}
			return $va_hier;
		}
		# ------------------------------------------------------------------
		# User group-based access control
		# ------------------------------------------------------------------
		/**
		 * Returns array of user groups associated with the currently loaded row. The array
		 * is key'ed on user group group_id; each value is an  array containing information about the group. Array keys are:
		 *			group_id		[group_id for group]
		 *			name			[name of group]
		 *			code				[short alphanumeric code identifying the group]
		 *			description	[text description of group]
		 *
		 * @return array List of groups associated with the currently loaded row
		 */ 
		public function getUserGroups($pa_options=null) {
			if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
			if (!($vs_group_rel_table = $this->getProperty('USER_GROUPS_RELATIONSHIP_TABLE'))) { return null; }
			$vs_pk = $this->primaryKey();
			
			if (!is_array($pa_options)) { $pa_options = array(); }
			$vb_return_for_bundle =  (isset($pa_options['returnAsInitialValuesForBundle']) && $pa_options['returnAsInitialValuesForBundle']) ? true : false;
			
			$o_dm = Datamodel::load();
			$t_rel = $o_dm->getInstanceByTableName($vs_group_rel_table);
			
			$vb_supports_date_restrictions = (bool)$t_rel->hasField('effective_date');
			$o_tep = new TimeExpressionParser();
			
			$o_db = $this->getDb();
			
			$qr_res = $o_db->query("
				SELECT g.*, r.*
				FROM {$vs_group_rel_table} r
				INNER JOIN ca_user_groups AS g ON g.group_id = r.group_id
				WHERE
					r.{$vs_pk} = ?
			", $vn_id);
			
			$va_groups = array();
			
			while($qr_res->nextRow()) {
				$va_row = $qr_res->getRow();
				if ($vb_supports_date_restrictions) {
					$o_tep->setUnixTimestamps($qr_res->get('sdatetime'), $qr_res->get('edatetime'));
					$va_row['effective_date'] = $o_tep->getText();
				}
				
				if ($vb_return_for_bundle) {
					$vs_display_format = $this->getAppConfig()->get('ca_user_groups_relationship_display_format');
					if (!preg_match_all('!\^{1}([A-Za-z0-9\._]+)!', $vs_display_format, $va_matches)) {
						$vs_display_format = '^ca_user_groups.name';
						$va_bundles = array('ca_user_groups.name');
					} else {
						$va_bundles = $va_matches[1];
					}
					
					$va_row['_display'] = $vs_display_format;
					foreach($va_bundles as $vs_bundle) {
						$va_row['_display'] = str_replace("^{$vs_bundle}", $qr_res->get($vs_bundle), $va_row['_display']);
					}
					$va_row['id'] = (int)$qr_res->get('group_id');
					$va_groups[(int)$qr_res->get('relation_id')] = $va_row;
				} else {
					$va_groups[(int)$qr_res->get('group_id')] = $va_row;
				}
			}
			
			return $va_groups;
		}
		# ------------------------------------------------------------------
		/**
		 * Checks if currently loaded row is accessible (read or edit access) to the specified group or groups
		 *
		 * @param mixed $pm_group_id A group_id or array of group_ids to check
		 * @return bool True if at least one group can access the currently loaded row, false if no groups have access; returns null if no row is currently loaded.
		 */ 
		public function isAccessibleToUserGroup($pm_group_id) {
			if (!is_array($pm_group_id)) { $pm_group_id = array($pm_group_id); }
			if (is_array($va_groups = $this->getUserGroups())) {
				foreach($pm_group_id as $pn_group_id) {
					if (isset($va_groups[$pn_group_id]) && (is_array($va_groups[$pn_group_id]))) {
						// is effective date set?
						if (($va_groups[$pn_group_id]['sdatetime'] > 0) && ($va_groups[$pn_group_id]['edatetime'] > 0)) {
							if (($va_groups[$pn_group_id]['sdatetime'] > time()) || ($va_groups[$pn_group_id]['edatetime'] <= time())) {
								return false;
							}
						}
						return true;
					}
				}
				return false;
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */ 
		public function addUserGroups($pa_group_ids, $pa_effective_dates=null) {
			if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
			if (!($vs_group_rel_table = $this->getProperty('USER_GROUPS_RELATIONSHIP_TABLE'))) { return null; }
			$vs_pk = $this->primaryKey();
			
			$o_dm = Datamodel::load();
			$t_rel = $o_dm->getInstanceByTableName($vs_group_rel_table);
			
			$va_current_groups = $this->getUserGroups();
			
			foreach($pa_group_ids as $vn_group_id => $vn_access) {
				$t_rel->load(array('group_id' => $vn_group_id, $vs_pk => $vn_id));		// try to load existing record
				
				$t_rel->setMode(ACCESS_WRITE);
				$t_rel->set($vs_pk, $vn_id);
				$t_rel->set('group_id', $vn_group_id);
				$t_rel->set('access', $vn_access);
				if ($t_rel->hasField('effective_date')) {
					$t_rel->set('effective_date', $pa_effective_dates[$vn_group_id]);
				}
				
				if ($t_rel->getPrimaryKey()) {
					$t_rel->update();
				} else {
					$t_rel->insert();
				}
				
				if ($t_rel->numErrors()) {
					$this->errors = $t_rel->errors;
					return false;
				}
			}
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */ 
		public function setUserGroups($pa_group_ids, $pa_effective_dates=null) {
			if (is_array($va_groups = $this->getUserGroups())) {
				$this->removeAllUserGroups();
				if (!$this->addUserGroups($pa_group_ids, $pa_effective_dates)) { return false; }
				
				return true;
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */ 
		public function removeUserGroups($pa_group_ids) {
			if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
			if (!($vs_group_rel_table = $this->getProperty('USER_GROUPS_RELATIONSHIP_TABLE'))) { return null; }
			$vs_pk = $this->primaryKey();
			
			$o_dm = Datamodel::load();
			$t_rel = $o_dm->getInstanceByTableName($vs_group_rel_table);
			
			$va_current_groups = $this->getUserGroups();
			
			foreach($pa_group_ids as $vn_group_id) {
				if (!isset($va_current_groups[$vn_group_id]) && $va_current_groups[$vn_group_id]) { continue; }
				
				$t_rel->setMode(ACCESS_WRITE);
				if ($t_rel->load(array($vs_pk => $vn_id, 'group_id' => $vn_group_id))) {
					$t_rel->delete(true);
					
					if ($t_rel->numErrors()) {
						$this->errors = $t_rel->errors;
						return false;
					}
				}
			}
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Removes all user groups from currently loaded row
		 *
		 * @return bool True on success, false on failure
		 */ 
		public function removeAllUserGroups() {
			if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
			if (!($vs_group_rel_table = $this->getProperty('USER_GROUPS_RELATIONSHIP_TABLE'))) { return null; }
			$vs_pk = $this->primaryKey();
			
			$o_db = $this->getDb();
			
			$qr_res = $o_db->query($x="
				DELETE FROM {$vs_group_rel_table}
				WHERE
					{$vs_pk} = ?
			", (int)$vn_id);
			
			if ($o_db->numErrors()) {
				$this->errors = $o_db->errors;
				return false;
			}
			return true;
		}
		# ------------------------------------------------------------------		
		/**
		 * 
		 */
		public function getUserGroupHTMLFormBundle($po_request, $ps_form_name, $pn_table_num, $pn_item_id, $pn_user_id=null, $pa_options=null) {
			$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
			
			
			require_once(__CA_MODELS_DIR__.'/ca_user_groups.php');
			$t_group = new ca_user_groups();
			
			$o_dm = Datamodel::load();
			$t_rel = $o_dm->getInstanceByTableName($this->getProperty('USERS_RELATIONSHIP_TABLE'));
			$o_view->setVar('t_rel', $t_rel);
			
			$o_view->setVar('table_num', $pn_table_num);
			$o_view->setVar('id_prefix', $ps_form_name);		
			$o_view->setVar('request', $po_request);	
			$o_view->setVar('t_group', $t_group);
			$o_view->setVar('initialValues', $this->getUserGroups(array('returnAsInitialValuesForBundle' => true)));
			
			return $o_view->render('ca_user_groups.php');
		}
		# ------------------------------------------------------------------
		# User-based access control
		# ------------------------------------------------------------------
		/**
		 * Returns array of users associated with the currently loaded row. The array
		 * is key'ed on user user user_id; each value is an  array containing information about the user. Array keys are:
		 *			user_id			[user_id for user]
		 *			user_name	[name of user]
		 *			code				[short alphanumeric code identifying the group]
		 *			description	[text description of group]
		 *
		 * @return array List of groups associated with the currently loaded row
		 */ 
		public function getUsers($pa_options=null) {
			if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
			if (!($vs_user_rel_table = $this->getProperty('USERS_RELATIONSHIP_TABLE'))) { return null; }
			$vs_pk = $this->primaryKey();
			
			if (!is_array($pa_options)) { $pa_options = array(); }
			$vb_return_for_bundle =  (isset($pa_options['returnAsInitialValuesForBundle']) && $pa_options['returnAsInitialValuesForBundle']) ? true : false;
			
			$o_dm = Datamodel::load();
			$t_rel = $o_dm->getInstanceByTableName($vs_user_rel_table);
			
			$vb_supports_date_restrictions = (bool)$t_rel->hasField('effective_date');
			$o_tep = new TimeExpressionParser();
			
			$o_db = $this->getDb();
			
			$qr_res = $o_db->query("
				SELECT u.*, r.*
				FROM {$vs_user_rel_table} r
				INNER JOIN ca_users AS u ON u.user_id = r.user_id
				WHERE
					r.{$vs_pk} = ?
			", $vn_id);
			
			$va_users = array();
			
			while($qr_res->nextRow()) {
				$va_row = $qr_res->getRow();
				if ($vb_supports_date_restrictions) {
					$o_tep->setUnixTimestamps($qr_res->get('sdatetime'), $qr_res->get('edatetime'));
					$va_row['effective_date'] = $o_tep->getText();
				}
				
				if ($vb_return_for_bundle) {
					$vs_display_format = $this->getAppConfig()->get('ca_users_relationship_display_format');
					if (!preg_match_all('!\^{1}([A-Za-z0-9\._]+)!', $vs_display_format, $va_matches)) {
						$vs_display_format = '^ca_users.user_name';
						$va_bundles = array('ca_users.user_name');
					} else {
						$va_bundles = $va_matches[1];
					}
					
					$va_row['_display'] = $vs_display_format;
					foreach($va_bundles as $vs_bundle) {
						$va_row['_display'] = str_replace("^{$vs_bundle}", $qr_res->get($vs_bundle), $va_row['_display']);
					}
					//$va_row['_display'] = $qr_res->get('user_name');
					$va_row['id'] = (int)$qr_res->get('user_id');
					$va_users[(int)$qr_res->get('relation_id')] = $va_row;
				} else {
					$va_users[(int)$qr_res->get('user_id')] = $va_row;
				}
			}
			
			return $va_users;
		}
		# ------------------------------------------------------------------
		/**
		 * Checks if currently loaded row is accessible (read or edit access) to the specified group or groups
		 *
		 * @param mixed $pm_group_id A group_id or array of group_ids to check
		 * @return bool True if at least one group can access the currently loaded row, false if no groups have access; returns null if no row is currently loaded.
		 */ 
		public function isAccessibleToUser($pm_user_id) {
			if (!is_array($pm_user_id)) { $pm_user_id = array($pm_user_id); }
			if (is_array($va_users = $this->getUsers())) {
				foreach($pm_user_id as $pn_user_id) {
					if (isset($va_users[$pn_user_id]) && (is_array($va_users[$pn_user_id]))) {
						// is effective date set?
						if (($va_users[$pn_user_id]['sdatetime'] > 0) && ($va_users[$pn_user_id]['edatetime'] > 0)) {
							if (($va_users[$pn_user_id]['sdatetime'] > time()) || ($va_users[$pn_user_id]['edatetime'] <= time())) {
								return false;
							}
						}
						return true;
					}
				}
				return false;
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */ 
		public function addUsers($pa_user_ids, $pa_effective_dates=null) {
			if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
			if (!($vs_user_rel_table = $this->getProperty('USERS_RELATIONSHIP_TABLE'))) { return null; }
			$vs_pk = $this->primaryKey();
			
			$o_dm = Datamodel::load();
			$t_rel = $o_dm->getInstanceByTableName($vs_user_rel_table);
			
			foreach($pa_user_ids as $vn_user_id => $vn_access) {
				$t_rel->load(array('user_id' => $vn_user_id, $vs_pk => $vn_id));		// try to load existing record
				
				$t_rel->setMode(ACCESS_WRITE);
				$t_rel->set($vs_pk, $vn_id);
				$t_rel->set('user_id', $vn_user_id);
				$t_rel->set('access', $vn_access);
				if ($t_rel->hasField('effective_date')) {
					$t_rel->set('effective_date', $pa_effective_dates[$vn_user_id]);
				}
				
				if ($t_rel->getPrimaryKey()) {
					$t_rel->update();
				} else {
					$t_rel->insert();
				}
				
				if ($t_rel->numErrors()) {
					$this->errors = $t_rel->errors;
					return false;
				}
			}
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */ 
		public function setUsers($pa_user_ids, $pa_effective_dates=null) {
			$this->removeAllUsers();
			if (!$this->addUsers($pa_user_ids, $pa_effective_dates)) { return false; }
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */ 
		public function removeUsers($pa_user_ids) {
			if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
			if (!($vs_user_rel_table = $this->getProperty('USERS_RELATIONSHIP_TABLE'))) { return null; }
			$vs_pk = $this->primaryKey();
			
			$o_dm = Datamodel::load();
			$t_rel = $o_dm->getInstanceByTableName($vs_user_rel_table);
			
			$va_current_users = $this->getUsers();
			
			foreach($pa_user_ids as $vn_user_id) {
				if (!isset($va_current_users[$vn_user_id]) && $va_current_users[$vn_user_id]) { continue; }
				
				$t_rel->setMode(ACCESS_WRITE);
				if ($t_rel->load(array($vs_pk => $vn_id, 'user_id' => $vn_user_id))) {
					$t_rel->delete(true);
					
					if ($t_rel->numErrors()) {
						$this->errors = $t_rel->errors;
						return false;
					}
				}
			}
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Removes all user users from currently loaded row
		 *
		 * @return bool True on success, false on failure
		 */ 
		public function removeAllUsers() {
			if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
			if (!($vs_user_rel_table = $this->getProperty('USERS_RELATIONSHIP_TABLE'))) { return null; }
			$vs_pk = $this->primaryKey();
			
			$o_db = $this->getDb();
			
			$qr_res = $o_db->query("
				DELETE FROM {$vs_user_rel_table}
				WHERE
					{$vs_pk} = ?
			", $vn_id);
			if ($o_db->numErrors()) {
				$this->errors = $o_db->errors;
				return false;
			}
			return true;
		}
		# ------------------------------------------------------------------		
		/**
		 * 
		 */
		public function getUserHTMLFormBundle($po_request, $ps_form_name, $pn_table_num, $pn_item_id, $pn_user_id=null, $pa_options=null) {
			$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
			
			
			require_once(__CA_MODELS_DIR__.'/ca_users.php');
			$t_user = new ca_users();
			
			$o_dm = Datamodel::load();
			$t_rel = $o_dm->getInstanceByTableName($this->getProperty('USERS_RELATIONSHIP_TABLE'));
			$o_view->setVar('t_rel', $t_rel);
			
			$o_view->setVar('table_num', $pn_table_num);
			$o_view->setVar('id_prefix', $ps_form_name);		
			$o_view->setVar('request', $po_request);	
			$o_view->setVar('t_user', $t_user);
			$o_view->setVar('initialValues', $this->getUsers(array('returnAsInitialValuesForBundle' => true)));
			
			return $o_view->render('ca_users.php');
		}
		# ------------------------------------------------------
	}
?>