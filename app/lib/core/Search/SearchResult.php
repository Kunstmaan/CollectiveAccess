<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Search/SearchResult.php : implements interface to results from a search
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

# ----------------------------------------------------------------------
# --- Import classes
# ----------------------------------------------------------------------
include_once(__CA_LIB_DIR__."/core/BaseObject.php");
include_once(__CA_LIB_DIR__."/core/Datamodel.php");
include_once(__CA_LIB_DIR__."/core/Media/MediaInfoCoder.php");
include_once(__CA_LIB_DIR__."/core/File/FileInfoCoder.php");
include_once(__CA_LIB_DIR__."/core/Parsers/TimeExpressionParser.php");
include_once(__CA_LIB_DIR__."/core/Parsers/TimecodeParser.php");


# ----------------------------------------------------------------------
class SearchResult extends BaseObject {
	
	private $opo_datamodel;
	private $opo_search_config;
	private $opo_db;
	private $opn_table_num;
	private $ops_table_name;
	private $ops_table_pk;
	// ----
	
	private $opa_options;
	
	private $opo_engine_result;
	protected $opa_tables;
	
	private $opo_row_instance;
	
	private $opa_prefetch_cache;
	private $opa_rel_prefetch_cache;
	
	private $opo_tep; // timeexpression parser
	
	# ------------------------------------------------------------------
	public function __construct($pn_subject_table_num=null, $po_engine_result=null, $pa_tables=null) {
		
		$this->opo_db = new Db();
		$this->opo_datamodel = Datamodel::load();
		
		$this->opa_prefetch_cache = array();
		$this->opa_rel_prefetch_cache = array();
		
		if ($pn_subject_table_num) {
			$this->init($pn_subject_table_num, $po_engine_result, $pa_tables);
		}
		
		if (!$GLOBALS["_DbResult_time_expression_parser"]) { $GLOBALS["_DbResult_time_expression_parser"] = new TimeExpressionParser(); }
		if (!$GLOBALS["_DbResult_timecodeparser"]) { $GLOBALS["_DbResult_timecodeparser"] = new TimecodeParser(); }
		
		if (!$GLOBALS["_DbResult_mediainfocoder"]) { $GLOBALS["_DbResult_mediainfocoder"] = MediaInfoCoder::load(); }
		if (!$GLOBALS["_DbResult_fileinfocoder"]) { $GLOBALS["_DbResult_fileinfocoder"] = FileInfoCoder::load(); }
		
		
		// valid options and defaults
		$this->opa_options = array(
				// SearchResult::get() can load field data from database when it is not available directly from the search index (most fields are *not* available from the index)
				// It is almost always more efficient to grab multiple field values from a table in one query, and to do so for multiple rows, than to generate and execute queries 
				// each time get() is called. Thus get() automatically "prefetches" field values for a given table when it is called; the "prefetch" option defined how many rows
				// beyond the current row are pre-loaded. You ideally want this value to match the number of rows you actually plan to use. If you're generating lists of search
				// results and page the results with 50 results per page then you'd want to the prefetch to be 50. If the number of rows you need is very large (> 200?) then it might
				// make sense to use a value less than the total number of rows since queries with many enumerated row_ids (which is what the prefetch mechanism uses) may run slowly
				// when a large number of ids are specified. The default for this is 50.
				// 
				'prefetch' => 50,
				'dontPrefetchAttributes' => false
		);
		
		
		$this->opo_tep = new TimeExpressionParser();

	}
	# ------------------------------------------------------------------
	public function init($pn_subject_table_num, $po_engine_result, $pa_tables) {
		$this->opn_table_num = $pn_subject_table_num;
		$this->opo_engine_result = $po_engine_result;
		$this->opa_tables = $pa_tables;
		
		$this->errors = array();
	
		$this->opo_row_instance = $this->opo_datamodel->getInstanceByTableNum($this->opn_table_num, true);
		$this->ops_table_name =  $this->opo_row_instance->tableName();
		$this->ops_table_pk = $this->opo_row_instance->primaryKey();
	}
	# ------------------------------------------------------------------
	public function tableNum() {
		return $this->opn_table_num;
	}
	# ------------------------------------------------------------------
	public function tableName() {
		return $this->ops_table_name;
	}
	# ------------------------------------------------------------------
	public function numHits() {
		return $this->opo_engine_result->numHits();
	}
	# ------------------------------------------------------------------
	public function nextHit() {
		return $this->opo_engine_result->nextHit();
	}
	# ------------------------------------------------------------------
	public function previousHit() {
		$vn_index = $this->opo_engine_result->currentRow();
		if ($vn_index >= 0) {
			$this->opo_engine_result->seek($vn_index);
		}
	}
	# ------------------------------------------------------------------
	/**
  	 * Returns true if this current hit is the last in the set
  	 *
  	 * @return boolean True if current hit is the last in the results set, false otherwise
	 */
	public function isLastHit() {
		$vn_index = $this->opo_engine_result->currentRow();
		$vn_num_hits = $this->opo_engine_result->numHits();
		
		if ($vn_index == ($vn_num_hits - 1)) { return true; }
		
		return false;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function getRowIDsToPrefetch($ps_tablename, $pn_start, $pn_num_rows) {
		$va_row_ids = array();
		
		$vn_cur_row_index = $this->opo_engine_result->currentRow();
		$this->seek($pn_start);
		
		$vn_i=0;
		while(self::nextHit() && ($vn_i < $pn_num_rows)) {
			$va_row_ids[] = $this->opo_engine_result->get($this->ops_table_pk);
			$vn_i++;
		}
		$this->seek($vn_cur_row_index + 1);
		
		return $va_row_ids;
	}
	# ------------------------------------------------------------------
	/**
	 * TODO: implement prefetch of related and non-indexed-stored fields. Basically, instead of doing a query for every row via get() [which will still be an option if you're lazy]
	 * prefetch() will allow you to tell SearchResult to preload values for a set of hits starting at $pn_start 
	 * Because this can be done in a single query it'll presumably be faster than lazy loading lots of rows
	 */
	public function prefetch($ps_tablename, $pn_start, $pn_num_rows) {
		//print "PREFETCH: ".$ps_tablename.' - '. $pn_start.' - '. $pn_num_rows."<br>";
		
		// get row_ids to fetch
		if (sizeof($va_row_ids = $this->getRowIDsToPrefetch($ps_tablename, $pn_start, $pn_num_rows)) == 0) { return; }
		
		// do join
		$va_joins = array();
		
		$t_rel_instance = $this->opo_datamodel->getInstanceByTableName($ps_tablename, true);
		
		if ($ps_tablename != $this->ops_table_name) {
			$va_fields = $this->opa_tables[$ps_tablename]['fieldList'];
			$va_fields[] = $this->ops_table_name.'.'.$this->ops_table_pk;
			
			// Include type_id field for item table (eg. ca_entities.type_id)
			if (method_exists($t_rel_instance, "getTypeFieldName") && ($t_rel_instance->getTypeFieldName()) && ($vs_type_fld_name = $t_rel_instance->getTypeFieldName())) {
				$va_fields[] = $t_rel_instance->tableName().'.'.$vs_type_fld_name.' item_type_id';
			} else {
				// Include type_id field for item table (eg. ca_entities.type_id) when fetching labels
				if (method_exists($t_rel_instance, "getSubjectTableInstance")) {
					$t_label_subj_instance = $t_rel_instance->getSubjectTableInstance();
					if (method_exists($t_label_subj_instance, "getTypeFieldName") && ($vs_type_fld_name = $t_label_subj_instance->getTypeFieldName())) {
						$va_fields[] = $t_label_subj_instance->tableName().'.'.$vs_type_fld_name.' item_type_id';
					}
				}
			}
			
			$va_joined_table_info = $this->opa_tables[$ps_tablename];
			$va_linking_tables = $va_joined_table_info['joinTables'];
			if (!is_array($va_linking_tables)) { $va_linking_tables = array(); }
			array_push($va_linking_tables, $ps_tablename);
			
			$vs_left_table = $this->ops_table_name;
	
			foreach($va_linking_tables as $vs_right_table) {
				if ($va_rel = $this->opo_datamodel->getOneToManyRelations($vs_left_table, $vs_right_table)) {
					$va_joins[] = 'INNER JOIN '.$va_rel['many_table'].' ON '.$va_rel['one_table'].'.'.$va_rel['one_table_field'].' = '.$va_rel['many_table'].'.'.$va_rel['many_table_field'];
					$t_link = $this->opo_datamodel->getInstanceByTableName($va_rel['many_table'], true);
					if (is_a($t_link, 'BaseRelationshipModel') && $t_link->hasField('type_id')) {
						$va_fields[] = $va_rel['many_table'].'.type_id rel_type_id';
					}
				} else {
					if ($va_rel = $this->opo_datamodel->getOneToManyRelations($vs_right_table, $vs_left_table)) {
						$va_joins[] = 'INNER JOIN '.$va_rel['one_table'].' ON '.$va_rel['one_table'].'.'.$va_rel['one_table_field'].' = '.$va_rel['many_table'].'.'.$va_rel['many_table_field'];
					}
				}
				$vs_left_table = $vs_right_table;
			}
		} else {
			$va_fields = array('*');
		}
		
		$vs_criteria_sql = '';
		if (is_array($this->opa_tables[$ps_tablename]['criteria']) && (sizeof($this->opa_tables[$ps_tablename]['criteria']) > 0)) {
			$vs_criteria_sql = ' AND ('.join(' AND ', $this->opa_tables[$ps_tablename]['criteria']).')';
		}
	
		$vb_has_locale_id = true;
		if ($this->opo_row_instance->hasField('locale_id') && (!$t_rel_instance->hasField('locale_id'))) {
			$va_fields[] = $this->ops_table_name.'.locale_id';
			$vb_has_locale_id = true;
		}
		
		$vs_order_by = '';
		if ($t_rel_instance->hasField('idno_sort')) {
			$vs_order_by = " ORDER BY ".$t_rel_instance->tableName().".idno_sort";
		}
	
		$vs_rel_pk = $t_rel_instance->primaryKey();
		
		$vs_sql = "
			SELECT ".join(',', $va_fields)."
			FROM ".$this->ops_table_name."
			".join("\n", $va_joins)."
			WHERE
				".$this->ops_table_name.'.'.$this->ops_table_pk." IN (".join(',', $va_row_ids).") $vs_criteria_sql
			{$vs_order_by}
		";
		//print "<pre>$vs_sql</pre>";
		$qr_rel = $this->opo_db->query($vs_sql);
		
		$va_rel_row_ids = array();
		while($qr_rel->nextRow()) {
			$vn_row_id = $qr_rel->get($this->ops_table_pk);
			if ($vb_has_locale_id) {
				$this->opa_prefetch_cache[$ps_tablename][$vn_row_id][$qr_rel->get('locale_id')][] = $qr_rel->getRow();
			} else {
				$this->opa_prefetch_cache[$ps_tablename][$vn_row_id][1][] = $qr_rel->getRow();
			}
		}
		
		// Fill row_id values for which there is nothing to prefetch with an empty lists
		// otherwise we'll try and prefetch these again later wasting time.
		foreach($va_row_ids as $vn_row_id) {
			if (!isset($this->opa_prefetch_cache[$ps_tablename][$vn_row_id])) {
				$this->opa_prefetch_cache[$ps_tablename][$vn_row_id] = array();
			}
		}
		
		// try to prefetch attributes
		if (!$this->getOption('dontPrefetchAttributes')) {
			ca_attributes::prefetchAttributes($this->opo_db, $this->opn_table_num, $va_row_ids, array('dontFetchAlreadyCachedValues' => true));
			
			if (($t_rel_instance->tableNum() != $this->opn_table_num) && (is_subclass_of($t_rel_instance, 'BundlableLabelableBaseModelWithAttributes'))) {
				ca_attributes::prefetchAttributes($this->opo_db, $t_rel_instance->tableNum(), $va_rel_row_ids, array('dontFetchAlreadyCachedValues' => true));
			}
		}
		
		//print "<pre>".print_r($this->opa_prefetch_cache[$ps_tablename], true)."</pre>";
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */
	public function prefetchRelated($ps_tablename, $pn_start, $pn_num_rows, $pa_options) {
		//print "PREFETCH RELATED: ".$ps_tablename.' - '. $pn_start.' - '. $pn_num_rows."<br>";
		if (sizeof($va_row_ids = $this->getRowIDsToPrefetch($ps_tablename, $pn_start, $pn_num_rows)) == 0) { return; }
		
		$vs_md5 = md5(print_r($pa_options, true));
		$va_rel_items = $this->opo_row_instance->getRelatedItems($ps_tablename, array_merge($pa_options, array('row_ids' => $va_row_ids, 'limit' => 100000)));		// if there are more than 100,000 then we have a problem
		foreach($va_rel_items as $vn_relation_id => $va_rel_item) {
			$this->opa_rel_prefetch_cache[$ps_tablename][$va_rel_item['row_id']][$vs_md5][] = $va_rel_item;
		}
		
		// Fill row_id values for which there is nothing to prefetch with an empty lists
		// otherwise we'll try and prefetch these again later wasting time.
		foreach($va_row_ids as $vn_row_id) {
			if (!isset($this->opa_rel_prefetch_cache[$ps_tablename][$vn_row_id][$vs_md5])) {
				$this->opa_rel_prefetch_cache[$ps_tablename][$vn_row_id][$vs_md5] = array();
			}
		}
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * Returns a value from the query result. This can be a single value if it is a field in the subject table (eg. objects table in an objects search), or
	 * perhaps multiple related values (eg. related entities in an objects search). By default get() always returns a single value; for fields with multiple values
	 * the value will be the first value encountered when loading the field data. 
	 *
	 * You can fetch the values of attributes attached to the subject row (ie. if you're searching for ca_objects rows, the subject row is the ca_objects row)
	 * by use the "virtual" field name <subject_table_name>.<element_code> (ex. ca_objects.date_created)
	 * If the attribute is a multi-value container then you can fetch a specific value using the format <subject_table_name>.<attribute_element_code>/<value_element_code>
	 * For example, if you want to get the "date_value" value out of a "date" attribute attached to a ca_objects row, then you'd call get()
	 * with this fieldname: ca_objects.date/date_value
	 *
	 * If you want to get the other values for a multiple-value fields use the following options:
	 *
	 *				returnAsArray = if true, return an array, otherwise return a string (default is false)
	 *				template = formats attribute values; precede element codes with a caret ("^"). Eg. "^address1<br/>^city, ^state ^postalcode ^country"; only used when returnAsArray is false and a scalar is therefore to be returned.
	 *				delimiter = 
	 *				returnAllLocales = 
	 *				convertCodesToDisplayText = if true then list_ids are automatically converted to display text in the current locale; default is false (return list_ids raw)
	 *				restrict_to_relationship_types - restricts returned items to those related to the current row by the specified relationship type(s). You can pass either an array of types or a single type. The types can be relationship type_code's or type_id's.
 	 *				exclude_relationship_types - omits any items related to the current row with any of the specified types from the returned set of its. You can pass either an array of types or a single type. The types can be relationship type_code's or type_id's.
 	 *
	 */
	function get($ps_field, $pa_options=null) {
		$t = new Timer();
		// first see if the search engine can provide the field value directly (fastest)
		if(!(($vs_value = $this->opo_engine_result->get($ps_field, $pa_options)) === false)) {
			// TODO: implement returnAsArray and returnAllLocales
			return $vs_value;
		}
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$vs_template = 				(isset($pa_options['template'])) ? $pa_options['template'] : null;
		$vb_return_as_array = 		(isset($pa_options['returnAsArray'])) ? (bool)$pa_options['returnAsArray'] : false;
		$vb_return_all_locales = 	(isset($pa_options['returnAllLocales'])) ? (bool)$pa_options['returnAllLocales'] : false;
		$vs_delimiter = 				(isset($pa_options['delimiter'])) ? $pa_options['delimiter'] : ' ';
		if ($vb_return_all_locales && !$vb_return_as_array) { $vb_return_as_array = true; }
		
		$vn_row_id = $this->opo_engine_result->get($this->ops_table_pk);	
		
		
		// try to lazy load (slower)
		$va_path_components = $this->getFieldPathComponents($ps_field);
	
		if (!($t_instance = $this->opo_datamodel->getInstanceByTableName($va_path_components['table_name'], true))) { return null; }
		
		// Simple related table get
		if (
			(($va_path_components['num_components'] == 1) && ($va_path_components['table_name'] !== $this->ops_table_name))
			||
			(($va_path_components['num_components'] == 2) && ($va_path_components['field_name'] == 'related'))
		) {
			if (!($t_table = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name, true))) { return null; }
			
			if ($va_path_components['num_components'] == 2) {
				$va_path_components['num_components'] = 1;
				$va_path_components['field_name'] = null;
			}
			
			$vs_opt_md5 = md5(print_R($pa_options, true));
			if (!isset($this->opa_rel_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5])) {
				$this->prefetchRelated($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
			}
			
			$va_related_items = $this->opa_rel_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5];
			if (!is_array($va_related_items)) { return null; }
			
			if($vb_return_as_array) {
				 if ($vb_return_all_locales) {
					return $va_related_items;
				 } else {
					foreach($va_related_items as $vn_relation_id => $va_relation_info) {
						$va_relation_info['labels'] = caExtractValuesByUserLocale(array(0 => $va_relation_info['labels']));	
						$va_related_items[$vn_relation_id]['labels'] = $va_relation_info['labels'];
					}
					return $va_related_items;
				 }
			} else {
				$va_proc_labels = array();
				
				$va_row_ids = array();
				$vs_rel_pk = $t_instance->primaryKey();
				
				foreach($va_related_items as $vn_relation_id => $va_relation_info) {
					$va_row_ids[] = $va_relation_info[$vs_rel_pk];
				}
				if (!sizeof($va_row_ids)) { return ''; }
				$va_tags = array();
				$vb_uses_attributes = false;
				if (preg_match_all("!\^([A-Za-z0-9_\.]+)!", $vs_template, $va_matches)) {
					$va_tags = $va_matches[1];
					foreach($va_tags as $vs_tag) {
						$va_tmp = explode('.', $vs_tag);
						if ($va_tmp[0] === $ops_table_name) {
							if (!$t_instance->hasField($va_tmp[1])) {
								$vb_uses_attributes = true;
								break;
							}
						}
					}
				}
				
				$qr_rel_items = $t_instance->makeSearchResult($va_path_components['table_name'], $va_row_ids);
				$qr_rel_items->setOption('prefetch', 1000);
				
				if (!$vb_uses_attributes) { $qr_rel_items->setOption('dontPrefetchAttributes', true); }
				
				$va_values = array();
				while($qr_rel_items->nextHit()) {
					$va_relation_info = array_shift($va_related_items);
					if (sizeof($va_tags)) {
						$vs_value = $vs_template;
						foreach($va_tags as $vs_tag) {
							switch($vs_tag) {
								case 'label':
								case 'preferred_labels':
								case $va_path_components['table_name'].'.preferred_labels':
									$vs_value = str_replace("^{$vs_tag}", $va_relation_info['label'], $vs_value);
									break;
								case 'relationship_typename':
									$vs_value = str_replace("^{$vs_tag}", $va_relation_info['relationship_typename'], $vs_value);
									break;
								default:
									$vs_value = str_replace("^{$vs_tag}", $qr_rel_items->get($vs_tag), $vs_value);
									break;
							}
						}
						
						$va_values[] = $vs_value;
					} else {
						$va_values[] = $qr_rel_items->get($va_path_components['table_name'].'.preferred_labels');
					}
				}
				
				
				return join($vs_delimiter, $va_values);
			}
		}
		
		$vb_need_parent = false;
		$vb_need_children = false;
		
		
		//
		// Transform "preferred_labels" into tables for pre-fetching
		//
		$vb_is_get_for_labels = $vb_return_all_label_values = $vb_get_preferred_labels_only = $vb_get_nonpreferred_labels_only = false;
		if(in_array($va_path_components['field_name'], array('preferred_labels', 'nonpreferred_labels'))) {
			if (is_subclass_of($t_instance, 'LabelableBaseModelWithAttributes')) {
				
				$vb_get_preferred_labels_only = ($va_path_components['field_name'] == 'preferred_labels') ? true : false;
				$vb_get_nonpreferred_labels_only = ($va_path_components['field_name'] == 'nonpreferred_labels') ? true : false;
				
				if ($va_path_components['num_components'] == 2) {
					$vb_return_all_label_values = true;
				}
				
				$va_path_components['table_name'] = $t_instance->getLabelTableName();
				$t_label_instance = $t_instance->getLabelTableInstance();
				if (!$va_path_components['subfield_name'] || !$t_label_instance->hasField($va_path_components['subfield_name'])) {
					$va_path_components['field_name'] = $t_instance->getLabelDisplayField();
				} else {
					$va_path_components['field_name'] = $va_path_components['subfield_name'];
				}
				$va_path_components['subfield_name'] = null;
				
				$va_path_components = $this->getFieldPathComponents($va_path_components['table_name'].'.'.$va_path_components['field_name']);
				// Ok, convert the table instance to the label table since that's the table we'll be grabbing data from
				$t_instance = $t_label_instance;
				
				$vb_is_get_for_labels = true;
			}
		}
		// Hack to only get item_id for preferred labels
        if (isset($pa_options['preferredLabels'])) {
            $vb_get_preferred_labels_only = (bool)$pa_options['preferredLabels'];
        }
		if ($va_path_components['num_components'] >= 2) {
			switch($va_path_components['field_name']) {
				case 'parent':
					if (($t_instance->isHierarchical()) && ($vn_parent_id = $this->get($va_path_components['table_name'].'.'.$t_instance->getProperty('HIERARCHY_PARENT_ID_FLD')))) {
						//
						// TODO: support some kind of prefetching of parents?
						//
						unset($va_path_components['components'][1]);
						if ($t_instance->load($vn_parent_id)) {
							return $t_instance->get(join('.', array_values($va_path_components['components'])), $pa_options);
						}
						return null;
					}
					break;
				case 'children':
					if ($t_instance->isHierarchical()) {
						//unset($va_path_components['components'][1]);	// remove 'children' from field path
						
						$vs_field_spec = join('.', array_values($va_path_components['components']));
						if ($vn_id = $this->get($va_path_components['table_name'].'.'.$t_instance->primaryKey(), array('returnAsArray' => false))) {
							if($t_instance->load($vn_id)) {
								return $t_instance->get($vs_field_spec, $pa_options);
							}
						}
						return null;
					} 
					break;
				case 'related':
					// Regular related table call
					if ($va_path_components['table_name'] != $this->ops_table_name) {
						// just remove "related" from name and be on our way
						$va_tmp = $va_path_components['components'];
						array_splice($va_tmp, 1, 1);
						return $this->get(join('.', $va_tmp), $pa_options);
					}
					
					// Self-relations need special handling
					$vs_opt_md5 = md5(print_R($pa_options, true));
					if (!isset($this->opa_rel_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5])) {
						$this->prefetchRelated($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
					}
					
					$va_related_items = $this->opa_rel_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5];
					if (!($t_table = $this->opo_datamodel->getInstanceByTableName($va_path_components['table_name'], true))) { return null; }
					
					$va_ids = array();
					foreach($va_related_items as $vn_relation_id => $va_item) {
						$va_ids[] = $va_item[$t_table->primaryKey()];
					}
					
					$va_vals = array();
					
					if ($qr_res = $t_table->makeSearchResult($va_path_components['table_name'], $va_ids)) {
						$va_tmp = $va_path_components['components'];
						unset($va_tmp[1]);
						$vs_rel_field = join('.', $va_tmp);
						
						while($qr_res->nextHit()) {
							if ($vb_return_as_array) {
								$va_vals = array_merge($va_vals, $qr_res->get($vs_rel_field, $pa_options));
							} else {
								$va_vals[] = $qr_res->get($vs_rel_field, $pa_options);
							}
						}
					}
					
					if ($vb_return_as_array) {
						return $va_vals;
					} else {
						return join($vs_delimiter, $va_vals);
					}
					break;
				case 'hierarchy':
					if ($t_instance->isHierarchical()) {
						$vs_field_spec = join('.', array_values($va_path_components['components']));
						if ($va_ids = $this->get($va_path_components['table_name'].'.'.$t_instance->primaryKey(), array('returnAsArray' => true))) {
							$va_vals = array();
							foreach($va_ids as $vn_id) {
								if($t_instance->load($vn_id)) {
									$va_vals[] = $t_instance->get($vs_field_spec.".preferred_labels", $pa_options);
								}
							}
							if ($vb_return_as_array) {
								return $va_vals;
							} else {
								return join($vs_delimiter, $va_vals);
							}
						}
						return null;
					} 
					break;
			}
		}
		// If the requested table was not added to the query via SearchEngine::addTable()
		// then auto-add it here. It's better to explicitly add it with addTables() as that call
		// gives you precise control over which fields are autoloaded and also lets you specify limiting criteria 
		// for selection of related field data; and it also lets you explicitly define the tables used to join the
		// related table. Autoloading guesses and usually does what you want, but not always.
		if (!isset($this->opa_tables[$va_path_components['table_name']]) || !$this->opa_tables[$va_path_components['table_name']]) {
			$va_join_tables = $this->opo_datamodel->getPath($this->ops_table_name, $va_path_components['table_name']);
			array_shift($va_join_tables); 	// remove subject table
			array_pop($va_join_tables);		// remove content table (we only need linking tables here)
			$this->opa_tables[$va_path_components['table_name']] = array(
				'fieldList' => array($va_path_components['table_name'].'.*'),
				'joinTables' => array_keys($va_join_tables),
				'criteria' => array()
			);
		}
		
		
		if (($va_path_components['table_name'] === $this->ops_table_name) && !$t_instance->hasField($va_path_components['field_name']) && method_exists($t_instance, 'getAttributes')) {
			//
			// Return attribute values for primary table 
			//
			if (!$vb_return_as_array) {
				if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($va_path_components['field_name'])) {
					$vs_template = '';
					if ($va_path_components['subfield_name']) { 
						$vs_template = '^'.$va_path_components['subfield_name']; 
					} else {
						if (isset($pa_options['template'])) { $vs_template = $pa_options['template']; }
					}
					return $t_instance->getAttributesForDisplay($va_path_components['field_name'], $vs_template, array_merge(array('row_id' => $vn_row_id), $pa_options));
				}
				return $t_instance->getRawValue($vn_row_id, $va_path_components['field_name'], $va_path_components['subfield_name'], ',');
			} else {
				$va_values = $t_instance->getAttributeDisplayValues($va_path_components['field_name'], $vn_row_id, $pa_options);
				
				if ($va_path_components['subfield_name']) {
					if ($vb_return_all_locales) {
						foreach($va_values as $vn_row_id => $va_values_by_locale) {
							foreach($va_values_by_locale as $vn_locale_id => $va_value_list) {
								foreach($va_value_list as $vn_attr_id => $va_attr_data) {
									$va_values[$vn_row_id][$vn_locale_id][$vn_attr_id] = $va_attr_data[$va_path_components['subfield_name']];
								}
							}
						}
					} else {
						$va_processed_value_list = array();
						foreach($va_values as $vn_row_id => $va_value_list) {
							foreach($va_value_list as $vn_attr_id => $va_attr_data) {
								$va_processed_value_list[$vn_attr_id] = $va_attr_data[$va_path_components['subfield_name']];
							}
						}
						$va_values = $va_processed_value_list;
					}
				} else {
					if (!$vb_return_all_locales) {
						$va_values = array_shift($va_values);
					}
				}
				
				return $va_values;
			}
		} else {
			// Prefetch intrinsic fields in primary and related tables
			if (!isset($this->opa_prefetch_cache[$va_path_components['table_name']][$vn_row_id])) {
				$this->prefetch($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'));	// try to prefetch ahead (usually doesn't hurt and very often helps performance)
			}
		}
		
		//
		// Prepare return value
		//
		$va_return_values = array();
		if (($va_path_components['table_name'] !== $this->ops_table_name) && ($va_path_components['field_name'] !== 'relationship_typename') && !$t_instance->hasField($va_path_components['field_name']) && method_exists($t_instance, 'getAttributes')) {
			//
			// Return attributes in a related table
			//
			$vs_pk = $t_instance->primaryKey();
			
			$vs_opt_md5 = md5(print_R($pa_options, true));
			if (!isset($this->opa_rel_prefetch_cache[$va_path_components['table_name']][$vn_row_id][$vs_opt_md5])) {
				$this->prefetchRelated($va_path_components['table_name'], $this->opo_engine_result->currentRow(), $this->getOption('prefetch'), $pa_options);
			}
			
			foreach($this->opa_rel_prefetch_cache[$va_path_components['table_name']][$vn_row_id] as $vn_locale_id => $va_values_by_locale) {
				foreach($va_values_by_locale as $vn_i => $va_values) {
					if (!$vb_return_as_array) {
						$vs_val = $t_instance->getAttributesForDisplay($va_path_components['field_name'], $vs_template, array_merge(array('row_id' => $va_values[$vs_pk]), $pa_options));
					} else {
						$vs_val = $t_instance->getAttributeDisplayValues($va_path_components['field_name'], $va_values[$vs_pk], $pa_options);
					}
					if ($vs_val) {
						if ($vb_return_as_array) {
							if (!$vb_return_all_locales) {
								foreach($vs_val as $vn_i => $va_values_list) {
									foreach($va_values_list as $vn_j => $va_values) {
										$va_return_values[] = $va_values;
									}
								}
							} else {
								foreach($vs_val as $vn_i => $va_values_list) {
									$va_return_values[] = $va_values_list;
								}
							}
						} else {
							$va_return_values[] = $vs_val;
						}
					}
				}
			}
			if ($vb_return_as_array) {
				return $va_return_values;
			} else {
				if (isset($pa_options['convertLineBreaks']) && $pa_options['convertLineBreaks']) {
					return caConvertLineBreaks(join($vs_delimiter, $va_return_values));
				} else {
					return join($vs_delimiter, $va_return_values);
				}
			}
		} else {
			//
			// Return fields in primary or related table
			//
			$t_list = $this->opo_datamodel->getInstanceByTableName('ca_lists', true);
			$va_value_list = array($vn_row_id => $this->opa_prefetch_cache[$va_path_components['table_name']][$vn_row_id]);

				if (isset($pa_options['restrict_to_relationship_types']) && $pa_options['restrict_to_relationship_types']) {
				if (!is_array($pa_options['restrict_to_relationship_types'])) {
					$pa_options['restrict_to_relationship_types'] = array($pa_options['restrict_to_relationship_types']);
				}
				
				if (sizeof($pa_options['restrict_to_relationship_types'])) {
					$t_rel_type = $this->opo_datamodel->getInstanceByTableName('ca_relationship_types', true);
					$va_rel_types = array();
					$va_rel_path = array_keys($this->opo_datamodel->getPath($this->ops_table_name,  $va_path_components['table_name']));
					foreach($pa_options['restrict_to_relationship_types'] as $vm_type) {
						if ($vn_type_id = $t_rel_type->getRelationshipTypeID($va_rel_path[1], $vm_type)) {
							$va_rel_types[] = $vn_type_id;
							if (is_array($va_children = $t_rel_type->getHierarchyChildren($vn_type_id, array('idsOnly' => true)))) {
								$va_rel_types = array_merge($va_rel_types, $va_children);
							}
						}
					}
					if (sizeof($va_rel_types)) {
						$va_tmp = array();
						foreach($va_value_list as $vn_id => $va_by_locale) {
							foreach($va_by_locale as $vn_locale_id => $va_values) {
								foreach($va_values as $vn_i => $va_value) {
									if (in_array($va_value['rel_type_id'], $va_rel_types)) {
										$va_tmp[$vn_id][$vn_locale_id][$vn_i] = $va_value;
									}
								}
							}
						}
						$va_value_list = $va_tmp;
					}
				}
			}
			if (isset($pa_options['exclude_relationship_types']) && $pa_options['exclude_relationship_types']) {
				if (!is_array($pa_options['exclude_relationship_types'])) {
					$pa_options['exclude_relationship_types'] = array($pa_options['exclude_relationship_types']);
				}
				
				if (sizeof($pa_options['exclude_relationship_types'])) {
					$t_rel_type = $this->opo_datamodel->getInstanceByTableName('ca_relationship_types', true);
					$va_rel_types = array();
					$va_rel_path = array_keys($this->opo_datamodel->getPath($this->ops_table_name,  $va_path_components['table_name']));
					foreach($pa_options['exclude_relationship_types'] as $vm_type) {
						if ($vn_type_id = $t_rel_type->getRelationshipTypeID($va_rel_path[1], $vm_type)) {
							$va_rel_types[] = $vn_type_id;
							if (is_array($va_children = $t_rel_type->getHierarchyChildren($vn_type_id, array('idsOnly' => true)))) {
								$va_rel_types = array_merge($va_rel_types, $va_children);
							}
						}
					}
					if (sizeof($va_rel_types)) {
						$va_tmp = array();
						foreach($va_value_list as $vn_id => $va_by_locale) {
							foreach($va_by_locale as $vn_locale_id => $va_values) {
								foreach($va_values as $vn_i => $va_value) {
									if (!in_array($va_value['rel_type_id'], $va_rel_types)) {
										$va_tmp[$vn_id][$vn_locale_id][$vn_i] = $va_value;
									}
								}
							}
						}
						$va_value_list = $va_tmp;
					}
				}
			}
			
			if (isset($pa_options['restrict_to_types']) && $pa_options['restrict_to_types']) {
				if (!is_array($pa_options['restrict_to_types'])) {
					$pa_options['restrict_to_types'] = array($pa_options['restrict_to_types']);
				}
				
				if (sizeof($pa_options['restrict_to_types'])) {
					$vs_type_list_code = null;
					if (method_exists($t_instance, "getTypeFieldName")) {
						$vs_type_list_code = $t_instance->getTypeListCode();
					} else {
						if (method_exists($t_instance, "getSubjectTableInstance")) {
							$t_label_subj_instance = $t_instance->getSubjectTableInstance();
							if (method_exists($t_label_subj_instance, "getTypeFieldName")) {
								$vs_type_list_code = $t_label_subj_instance->getTypeListCode();
							}
						}
					}
					
					if ($vs_type_list_code) {
						$va_types = array();
						$t_item = $this->opo_datamodel->getInstanceByTableName('ca_list_items', true);
						foreach($pa_options['restrict_to_types'] as $vm_type) {
							if ($vn_type_id = $t_list->getItemIDFromList($vs_type_list_code, $vm_type)) {
								$va_types[] = $vn_type_id;
								if (is_array($va_children = $t_item->getHierarchyChildren($vn_type_id, array('idsOnly' => true)))) {
									$va_types = array_merge($va_types, $va_children);
								}
							}
						}
						
						if (sizeof($va_types)) {
							$va_tmp = array(); 
							foreach($va_value_list as $vn_id => $va_by_locale) {
								foreach($va_by_locale as $vn_locale_id => $va_values) {
									foreach($va_values as $vn_i => $va_value) {
										if (in_array($va_value['item_type_id'], $va_types)) {
											$va_tmp[$vn_id][$vn_locale_id][$vn_i] = $va_value;
										}
									}
								}
							}
							$va_value_list = $va_tmp;
						}
					}
				}
			}
			
			// handle 'relationship_typename' call
			$vb_get_relationship_typename = false;
			if ($va_path_components['field_name'] == 'relationship_typename') {
				$va_path_components['field_name'] = 'rel_type_id';
				$vb_get_relationship_typename = true;
			}
	
			if ($vb_return_as_array) {
				if ($t_instance->hasField($va_path_components['field_name']) && ($va_path_components['table_name'] === $this->ops_table_name)) {
					$va_field_info = $t_instance->getFieldInfo($va_path_components['field_name']);
					
					switch($va_field_info['FIELD_TYPE']) {
						case FT_DATERANGE:
						case FT_HISTORIC_DATERANGE:
							$this->opo_tep->init();
							if ($va_field_info['FIELD_TYPE'] == FT_DATERANGE) {
								$this->opo_tep->setUnixTimestamps($va_value[$va_field_info['START']], $va_value[$va_field_info['END']]);
							} else {
								$this->opo_tep->setHistoricTimestamps($va_value[$va_field_info['START']], $va_value[$va_field_info['END']]);
							}
							$vs_prop = $this->opo_tep->getText();
							if ($vb_return_all_locales) {
								$va_return_values[$vn_row_id][$vn_locale_id][] = $vs_prop;
							} else {
								$va_return_values[] = $vs_prop;
							}
							break;
						default:
							// is intrinsic field in primary table
							foreach($va_value_list as $vn_id => $va_values_by_locale) {
								foreach($va_values_by_locale as $vn_locale_id => $va_values) {
									foreach($va_values as $vn_i => $va_value) {
										if (($vb_get_preferred_labels_only) && (!$va_value['is_preferred'])) { continue; }
										if (($vb_get_nonpreferred_labels_only) && ($va_value['is_preferred'])) { continue; }
										
										if ($vb_return_all_locales) {
											$va_return_values[$vn_row_id][$vn_locale_id][] = $va_value[$va_path_components['field_name']];
										} else {
											$va_return_values[] = $va_value[$va_path_components['field_name']];
										}
									}
								}
							}
							break;
					}
				} else {
					foreach($va_value_list as $vn_i => $va_values_by_locale) {
						foreach($va_values_by_locale as $vn_locale_id => $va_values) {
							foreach($va_values as $vn_i => $va_value) {
								if (($vb_get_preferred_labels_only) && (!$va_value['is_preferred'])) { continue; }
								if (($vb_get_nonpreferred_labels_only) && ($va_value['is_preferred'])) { continue; }
								
								// do we need to translate foreign key and choice list codes to display text?
								$t_instance = $this->opo_datamodel->getInstanceByTableName($va_path_components['table_name'], true);
								$vs_prop = ($vb_return_all_label_values) ? $va_value : $va_value[$va_path_components['field_name']];
								
								if ($vb_get_relationship_typename) {
									if (!$t_rel_type) { $t_rel_type = $this->opo_datamodel->getInstanceByTableName('ca_relationship_types', true); }
									if (is_array($va_labels = $t_rel_type->getDisplayLabels(false, array('row_id' => (int)$vs_prop)))) {
										$va_label = array_shift($va_labels);
										$vs_prop = $va_label[0]['typename'];
									} else {
										$vs_prop = "?";
									}
								} else {
									if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($vs_list_code = $t_instance->getFieldInfo($va_path_components['field_name'],"LIST_CODE"))) {
										$vs_prop = $t_list->getItemFromListForDisplayByItemID($vs_list_code, $vs_prop);
									} else {
										if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($vs_list_code = $t_instance->getFieldInfo($va_path_components['field_name'],"LIST"))) {
											$vs_prop = $t_list->getItemFromListForDisplayByItemValue($vs_list_code, $vs_prop);
										} else {
											if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($va_path_components['field_name'] === 'locale_id') && ((int)$vs_prop > 0)) {
												$t_locale = new ca_locales($vs_prop);
												$vs_prop = $t_locale->getName();
											} else {
												if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && (is_array($va_list = $t_instance->getFieldInfo($va_path_components['field_name'],"BOUNDS_CHOICE_LIST")))) {
													foreach($va_list as $vs_option => $vs_value) {
														if ($vs_value == $vs_prop) {
															$vs_prop = $vs_option;
															break;
														}
													}
												}
											}
										}
									}
								}
								if ($vb_return_all_locales) {
									$va_return_values[$vn_row_id][$vn_locale_id][] = $vs_prop;
								} else {
									$va_return_values[] = $vs_prop;
								}
							}
						}
					}
				}
				return $va_return_values;
			} else {
				//
				// Return scalar
				//
				if ($vb_get_preferred_labels_only || $vb_get_nonpreferred_labels_only) {
					// We have to distinguish between preferred and non-preferred labels here
					// so that only appropriate labels are passed for output.
					$va_filtered_values = array();
					foreach($va_value_list as $vn_label_id => $va_labels_by_locale) {
						foreach($va_labels_by_locale as $vn_locale_id => $va_labels) {
							foreach($va_labels as $vn_i => $va_label) {
								if (	
									($vb_get_preferred_labels_only && ((!isset($va_label['is_preferred']) || $va_label['is_preferred'])))
									||
									($vb_get_nonpreferred_labels_only && !$va_label['is_preferred'])
								) {
									$va_filtered_values[$vn_label_id][$vn_locale_id][] = $va_label;
								}
							}
						}
					}
					$va_value_list = $va_filtered_values;
				}
				$va_value_list = caExtractValuesByUserLocale($va_value_list);
				
				// do we need to translate foreign key and choice list codes to display text?
				$t_instance = $this->opo_datamodel->getInstanceByTableName($va_path_components['table_name'], true);
				$va_field_info = $t_instance->getFieldInfo($va_path_components['field_name']);
						
				foreach($va_value_list as $vn_i => $va_values) {
					if (!is_array($va_values)) { continue; }
					
					$vs_template_value = $vs_template;
					foreach($va_values as $vn_j => $va_value) {
						switch($va_field_info['FIELD_TYPE']) {
							case FT_BIT:
								if ($pa_options['convertCodesToDisplayText']) {
									$va_value[$va_path_components['field_name']] = (bool)$vs_prop ? _t('yes') : _t('no'); 
								}
								break;
							case FT_DATERANGE:
								$this->opo_tep->init();
								$this->opo_tep->setUnixTimestamps($va_value[$va_field_info['START']], $va_value[$va_field_info['END']]);
								$va_value[$va_path_components['field_name']] = $this->opo_tep->getText();
								break;
							case FT_HISTORIC_DATERANGE:
								$this->opo_tep->init();
								$this->opo_tep->setHistoricTimestamps($va_value[$va_field_info['START']], $va_value[$va_field_info['END']]);
								$va_value[$va_path_components['field_name']] = $this->opo_tep->getText();
								break;
							case FT_MEDIA:
								$vs_version = $va_path_components['subfield_name'];
								
								if (isset($pa_options['unserialize']) && $pa_options['unserialize']) {
									return $va_value[$va_path_components['field_name']];
								} else {
									$o_media_settings = new MediaProcessingSettings($va_path_components['table_name'], $va_path_components['field_name']);
									$va_versions = $o_media_settings->getMediaTypeVersions('*');
								
									if (!isset($va_versions[$vs_version])) {
										$vs_version = array_shift(array_keys($va_versions));
									}
									
									if (isset($pa_options['returnURL']) && ($pa_options['returnURL'])) {
										$va_value[$va_path_components['field_name']] = $this->getMediaUrl('ca_object_representations.media', $vs_version, $pa_options);
									} else {
										$va_value[$va_path_components['field_name']] = $this->getMediaTag('ca_object_representations.media', $vs_version, $pa_options);
									}
								}
								break;
							default:
								// noop
								break;
						}
						
						$vs_prop = $va_value[$va_path_components['field_name']];
		
						if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($vs_list_code = $t_instance->getFieldInfo($va_path_components['field_name'],"LIST_CODE"))) {
							$va_value[$va_path_components['field_name']] = $t_list->getItemFromListForDisplayByItemID($vs_list_code, $vs_prop);
						} else {
							if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($vs_list_code = $t_instance->getFieldInfo($va_path_components['field_name'],"LIST"))) {
								$va_value[$va_path_components['field_name']] = $t_list->getItemFromListForDisplayByItemValue($vs_list_code, $vs_prop);
							} else {
								if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($va_path_components['field_name'] === 'locale_id') && ((int)$vs_prop > 0)) {
									$t_locale = new ca_locales($vs_prop);
									$va_value[$va_path_components['field_name']] = $t_locale->getName();
								} else {
									if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && (is_array($va_list = $t_instance->getFieldInfo($va_path_components['field_name'],"BOUNDS_CHOICE_LIST")))) {
										foreach($va_list as $vs_option => $vs_value) {
											if ($vs_value == $vs_prop) {
												$va_value[$va_path_components['field_name']] = $vs_option;
												break;
											}
										}
									}
								}
							}
						}
						
						if ($vs_template) {
							$vs_prop = $vs_template;
							foreach($va_value as $vs_k => $vs_v) {
								$vs_prop = str_replace("^{$vs_k}", $vs_v, $vs_prop); 
							}
						} else {
							$vs_prop = $va_value[$va_path_components['field_name']];
						}
					
						
						$va_return_values[] = $vs_prop;
					}
				}
				
				if (isset($pa_options['convertLineBreaks']) && $pa_options['convertLineBreaks']) {
					return caConvertLineBreaks(join($vs_delimiter, $va_return_values));
				} else {
					return join($vs_delimiter, $va_return_values);
				}
			}
		}

		return null;
	}
	# ------------------------------------------------------------------
	public function seek($pn_index) {
		return $this->opo_engine_result->seek($pn_index);
	}
	# ------------------------------------------------------------------
	#  Field value accessors (allow you to get specialized values out of encoded fields such as uploaded media and files, dates/date ranges, timecode, etc.) 
	# ------------------------------------------------------------------
	function getFieldInfo($ps_field) {
		$va_tmp = explode(".", $ps_field);
		switch(sizeof($va_tmp)) {
			case 1:		// query field name (no table specified, in other words)
				return array("table" => null, "field" => $ps_field, "instance" => null);
				break;
			case 2:		// table.field format fieldname
				$o_dm = Datamodel::load();
				$o_instance = $o_dm->getInstanceByTableName($va_tmp[0], true);
				if ($o_instance) {
					return array("table" => $va_tmp[0], "field" => $va_tmp[1], "instance" => $o_instance);
				}
				return array("table" => null, "field" => $ps_field, "instance" => null);
				break;
			default:	// invalid field name
				return false;
				break;
		}
	}
	# ------------------------------------------------------------------
	function getMediaInfo($ps_field, $ps_version=null, $ps_key=null, $pn_index=0) {
		//$va_field = $this->getFieldInfo($ps_field);
		$va_media_info = $this->get($ps_field, array("unserialize" => true, 'returnAsArray' => true));
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaInfo($va_media_info[$pn_index], $ps_version, $ps_key);
	}
	# ------------------------------------------------------------------
	function getMediaPath($ps_field, $ps_version) {
		//$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaPath($this->get($ps_field, array("unserialize" => true)), $ps_version);
	}
	# ------------------------------------------------------------------
	/**
	 * Return array of media paths attached to this search result. An object can have more than one representation.
	 *
	 */
	function getMediaPaths($ps_field, $ps_version, $pa_options=null) {
		//$va_field = $this->getFieldInfo($ps_field);
		
		$va_media = $this->get($ps_field, array("unserialize" => true, 'returnAsArray' => true));
		
		$va_media_paths = array();
		if (is_array($va_media) && sizeof($va_media)) {
			foreach($va_media as $vm_media) {
				$va_media_paths[] = $GLOBALS["_DbResult_mediainfocoder"]->getMediaPath($vm_media, $ps_version, $pa_options);
			}
		}
		return $va_media_paths;
	}
	# ------------------------------------------------------------------
	function getMediaUrl($ps_field, $ps_version) {
		//$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaUrl($this->get($ps_field, array("unserialize" => true)), $ps_version);
	}
	# ------------------------------------------------------------------
	/**
	 * Return array of media urls attached to this search result. An object can have more than one representation.
	 *
	 */
	function getMediaUrls($ps_field, $ps_version, $pa_options=null) {
		//$va_field = $this->getFieldInfo($ps_field);
		
		$va_media = $this->get($ps_field, array("unserialize" => true, 'returnAsArray' => true));
		
		$va_media_urls = array();
		if (is_array($va_media) && sizeof($va_media)) {
			foreach($va_media as $vm_media) {
				$va_media_urls[] = $GLOBALS["_DbResult_mediainfocoder"]->getMediaUrl($vm_media, $ps_version, $pa_options);
			}
		}
		return $va_media_urls;
	}
	# ------------------------------------------------------------------
	function getMediaTag($ps_field, $ps_version, $pa_options=null) {
		//$va_field = $this->getFieldInfo($ps_field);
	
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaTag($this->get($ps_field, array("unserialize" => true)), $ps_version, $pa_options);
	}
	# ------------------------------------------------------------------
	/**
	 * Return array of media tags attached to this search result. An object can have more than one representation.
	 *
	 */
	function getMediaTags($ps_field, $ps_version, $pa_options=null) {
		//$va_field = $this->getFieldInfo($ps_field);
		
		$va_media = self::get($ps_field, array("unserialize" => true, 'returnAsArray' => true));
		$va_media_tags = array();
		if (is_array($va_media) && sizeof($va_media)) {
			foreach($va_media as $vm_media) {
				$va_media_tags[] = $GLOBALS["_DbResult_mediainfocoder"]->getMediaTag($vm_media, $ps_version, $pa_options);
			}
		}
		return $va_media_tags;
	}
	# ------------------------------------------------------------------
	/**
	 * Return array of media info arrays attached to this search result. An object can have more than more representation.
	 *
	 */
	function getMediaInfos($ps_field) {
		//$va_field = $this->getFieldInfo($ps_field);
		
		$va_media = $this->get($ps_field, array("unserialize" => true, 'returnAsArray' => true));
		
		$va_media_infos = array();
		if (is_array($va_media) && sizeof($va_media)) {
			foreach($va_media as $vm_media) {
				$va_media_infos[] = $GLOBALS["_DbResult_mediainfocoder"]->getMediaInfo($vm_media);
			}
		}
		return $va_media_infos;
	}
	# ------------------------------------------------------------------
	function getMediaVersions($ps_field) {
		//$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaVersions($this->get($ps_field, array("unserialize" => true)));
	}
	# ------------------------------------------------------------------
	function hasMediaVersion($ps_field, $ps_version) {
		if (!is_array($va_tmp = $this->getMediaVersions($ps_field))) {
			return false;
		}
		return in_array($ps_version, $va_tmp);
	}
	# ------------------------------------------------------------------
	function hasMedia($ps_field) {  
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->hasMedia($this->get($va_field["field"], array("unserialize" => true)));
	}
	# ------------------------------------------------------------------
	function mediaIsMirrored($ps_field, $ps_version) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->mediaIsMirrored($this->get($va_field["field"], array("unserialize" => true)), $ps_version);
	}
	# ------------------------------------------------------------------
	function getMediaMirrorStatus($ps_field, $ps_version, $ps_mirror=null) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaMirrorStatus($this->get($va_field["field"], array("unserialize" => true)), $ps_version, $ps_mirror);
	}
	# ------------------------------------------------------------------
	function getFileInfo($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileInfo($this->get($va_field["field"], array("unserialize" => true)));
	}
	# ------------------------------------------------------------------
	function getFilePath($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFilePath($this->get($va_field["field"], array("unserialize" => true)));
	}
	# ------------------------------------------------------------------
	function getFileUrl($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileUrl($this->get($va_field["field"], array("unserialize" => true)));
	}
	# ------------------------------------------------------------------
	function hasFile($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->hasFile($this->get($va_field["field"], array("unserialize" => true)));
	}
	# ------------------------------------------------------------------
	function getFileConversions($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileConversions($this->get($va_field["field"], array("unserialize" => true)));
	}
	# ------------------------------------------------------------------
	function getFileConversionPath($ps_field, $ps_mimetype) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileConversionPath($this->get($va_field["field"], array("unserialize" => true)), $ps_mimetype);
	}
	# ------------------------------------------------------------------
	function getFileConversionUrl($ps_field, $ps_mimetype) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileConversionUrl($this->get($va_field["field"], array("unserialize" => true)), $ps_mimetype);
	}
	# ------------------------------------------------------------------
	function getDate($ps_field, $pa_options=null) {
		
		$va_field = $this->getFieldInfo($ps_field);
		if (is_object($va_field["instance"])) {
			if (!in_array($vn_field_type = $va_field["instance"]->getFieldInfo($va_field["field"], "FIELD_TYPE"), array(FT_DATE, FT_TIME, FT_DATETIME, FT_TIMESTAMP, FT_HISTORIC_DATETIME, FT_HISTORIC_DATERANGE, FT_DATERANGE))) {
				return false;
			}
			
			$vn_val = $this->get($va_field["field"], array("binary" => true));
			$GLOBALS["_DbResult_time_expression_parser"]->init();	// get rid of any linger date-i-ness
			switch($vn_field_type) {
				case (FT_DATE):
				case (FT_TIME):
				case (FT_DATETIME):
				case (FT_TIMESTAMP):
				case (FT_HISTORIC_DATETIME):	
					if ($pa_options["getRawDate"]) {
						return $vn_val;
					} else {
						if ($vn_field_type == FT_HISTORIC_DATETIME) {
							$GLOBALS["_DbResult_time_expression_parser"]->setHistoricTimestamps($vn_val, $vn_val);
						} else {
							$GLOBALS["_DbResult_time_expression_parser"]->setUnixTimestamps($vn_val, $vn_val);
						}
						return $GLOBALS["_DbResult_time_expression_parser"]->getText();
					}
					break;
				case (FT_DATERANGE):
				case (FT_HISTORIC_DATERANGE):	
					$vs_start_field_name = 	$va_field["instance"]->getFieldInfo($va_field["field"],"START");
					$vs_end_field_name = 	$va_field["instance"]->getFieldInfo($va_field["field"],"END");
					
					if (!$pa_options["getRawDate"]) {
						if ($vn_field_type == FT_HISTORIC_DATERANGE) {
							$GLOBALS["_DbResult_time_expression_parser"]->setHistoricTimestamps($this->get($vs_start_field_name, array("binary" => true)), $this->get($vs_end_field_name, array("binary" => true)));
						} else {
							$GLOBALS["_DbResult_time_expression_parser"]->setUnixTimestamps($this->get($vs_start_field_name, array("binary" => true)), $this->get($vs_end_field_name, array("binary" => true)));
						}
						return $GLOBALS["_DbResult_time_expression_parser"]->getText();
					} else {
						return array($this->get($vs_start_field_name, array("binary" => true)), $this->get($vs_end_field_name, array("binary" => true)));
					}
					break;
			}
		}
	}
	# ------------------------------------------------------------------
	function getTimecode($ps_field, $ps_format=null) {
		$va_field = $this->getFieldInfo($ps_field);
		if (is_object($va_field["instance"])) {
			if ($va_field["instance"]->getFieldInfo($va_field["field"], "FIELD_TYPE") != FT_TIMECODE) {
				return false;
			}
		}
		
		if (is_numeric($vn_tc = $this->get($va_field["field"]))) {
			$GLOBALS["_DbResult_timecodeparser"]->setParsedValueInSeconds($vn_tc);
			return $GLOBALS["_DbResult_timecodeparser"]->getText($ps_format);
		} else {
			return false;
		}
	}
	# ------------------------------------------------------------------
	function getChoiceListValue($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		if(is_object($va_field["instance"])) {
			if (is_array($va_field["instance"]->getFieldInfo($va_field["field"], "BOUNDS_CHOICE_LIST"))) {
				return $va_field["instance"]->getChoiceListValue($va_field["field"], $this->get($va_field["field"]));
			} else {
				// no choice list; return actual field value
				return $this->get($va_field["field"]);
			}
		} else {
			return false;
		}
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	function getVars($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		if (is_object($va_field["instance"])) {
			if ($va_field["instance"]->getFieldInfo($va_field["field"], "FIELD_TYPE") != FT_VARS) {
				return false;
			}
		}
		return $this->get($va_field["field"], array("unserialize" => true));
	}
	# ------------------------------------------------------------------
	# Options
	# ------------------------------------------------------------------
	public function setOption($ps_option, $pm_value) {
		if ($this->isValidOption($ps_option)) {
			$this->opa_options[$ps_option] = $pm_value;

			return true;
		}
		return false;
	}
	# ------------------------------------------------------------------
	public function getOption($ps_option) {
		return $this->opa_options[$ps_option];
	}
	# ------------------------------------------------------------------
	public function getAvailableOptions() {
		return array_keys($this->opa_options);
	}
	# ------------------------------------------------------------------
	public function isValidOption($ps_option) {
		return in_array($ps_option, $this->getAvailableOptions());
	}
	# ------------------------------------------------------------------
	# Utilities
	# ------------------------------------------------------------------
	public function getQueryTerms() {
		return $this->opo_engine_result->getQueryTerms();
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getResultTableName() {
		return $this->ops_table_name;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getResultTableInstance() {
		return $this->opo_datamodel->getInstanceByTableName($this->ops_table_name, true);
	}
	# ------------------------------------------------------------------
	/**
	  *
	  */
	private function getFieldPathComponents($ps_path) {
		$va_tmp = explode('.', $ps_path);
		switch(sizeof($va_tmp)) {
			# -------------------------------------
			case 1:		
				if ($t_instance = $this->opo_datamodel->getInstanceByTableName($va_tmp[0], true)) {	// table name
					$vs_table_name = $va_tmp[0];
					$vs_field_name = null;
					$vs_subfield_name = null;
				} else {																			// field name in searched table
					$vs_table_name = $this->ops_table_name;
					$vs_field_name = $va_tmp[0];
					$vs_subfield_name = null;
				}
				break;
			# -------------------------------------
			case 2:		// table_name.field_name
				$vs_table_name = $va_tmp[0];
				$vs_field_name = $va_tmp[1];
				$vs_subfield_name = null;
				break;
			# -------------------------------------
			default:
			case 3:		// table_name.field_name.sub_element
				$vs_table_name = $va_tmp[0];
				$vs_field_name = $va_tmp[1];
				$vs_subfield_name = $va_tmp[2];
				break;
			# -------------------------------------
		}
		
		return array(
			'table_name' 		=> $vs_table_name,
			'field_name' 		=> $vs_field_name,
			'subfield_name' 	=> $vs_subfield_name,
			'num_components'	=> sizeof($va_tmp),
			'components'		=> $va_tmp
		);
	}
	# ------------------------------------------------------------------
	/**
	 * Scans the result set and gets all field values of the field list given, including their count.
	 * This can be useful for presentation of results partitioned by type
	 * 
	 * The returned array looks like this:
	 * array(
	 * 	field1 => array(
	 * 				"field_value1" => count_of_field_value1,
	 * 				"field_value2" => count_of_field_value2,
	 * 				...)
	 *  field2 => ...
	 *  ...
	 * )
	 * 
	 * If it is not possible to fetch values for one of the given fields, it is simply ignored.
	 *
	 * @param array $pa_field_list List of fields to fetch counts for. Fields should be fully qualified <table>.<field> specifications (eg. ca_objects.type_id)
	 * @param bool $vb_sort If true, counts for each field value will be sorted by value; default is false
	 */
	public function getResultCountForFieldValues($pa_field_list, $vb_sort=false){
		if (($vn_cur_row_index = $this->opo_engine_result->currentRow()) < 0) {
			$vn_cur_row_index = 0;
		}
		$this->seek(0);
		$va_result = array();
		$this->setOption("prefetch", self::numHits());
		
		// loop through result and try to fetch values of the given field list
		while(self::nextHit()) {
			foreach($pa_field_list as $vs_field){
				// try to fetch fields as defined, don't care about non-existing fields
				if($vm_field_values=$this->get($vs_field,array('returnAsArray' => true))) {
					if(is_array($vm_field_values) && sizeof($vm_field_values)>0) {
						// rewrite $vs_field to represent the SearchEngine::addFilter() format;
						// this makes life a lot easier
						$va_matches = array();
						if(preg_match("/([\w_\-]+)\.(md_[0-9]+)\.([\w_\-]+)$/",$vs_field,$va_matches)) {
							$vs_field = $va_matches[1].'.'.$va_matches[2];
						}
						foreach($vm_field_values as $vs_field_value) {
							if($va_result[$vs_field][$vs_field_value]){
								$va_result[$vs_field][$vs_field_value]++;
							} else {
								$va_result[$vs_field][$vs_field_value] = 1;
							}
						}						
					} // do nothing on other cases (e.g. error or empty fields)
				}
			}
		}
		
		// restore current position
		$this->seek($vn_cur_row_index);
		
		// user wants the arrays to be sorted
		if($vb_sort) {
			foreach ($va_result as &$va_field_contents){
				ksort($va_field_contents);
			}
		}
		return $va_result;
	}
	# ------------------------------------------------------------------
}
?>