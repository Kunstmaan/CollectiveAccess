<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Search/SearchIndexer.php : indexing of content for search
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

require_once(__CA_LIB_DIR__."/core/Search/SearchBase.php");
require_once(__CA_LIB_DIR__.'/core/Utils/Graph.php');
require_once(__CA_LIB_DIR__.'/core/Utils/Timer.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Cache.php');

class SearchIndexer extends SearchBase {
	# ------------------------------------------------

	private $opa_dependencies_to_update;
	
	static public $s_related_rows_joins_cache = array();
	
	/** 
	 * Global cache array for ca_metadata_element element_code => element_id conversions
	 */
	static public $s_SearchIndexer_element_id_cache = array();
	
	/** 
	 * Global cache array for ca_metadata_element element_code => data type conversions
	 */
	static public $s_SearchIndexer_element_data_type_cache = array();
		
	/** 
	 * Global cache array for ca_metadata_element element_code => list_id conversions
	 */
	static public $s_SearchIndexer_element_list_id_cache = array();
	
	private $opo_metadata_element = null;

	# ------------------------------------------------
	/**
	 * Constructor takes Db() instance which it uses for all database access. You should pass an instance in
	 * because all the database accesses need to be in the same transactional context as whatever else you're doing. In
	 * the case of Table::insert(), Table::update() and Table::delete() [the main users of , they're always in a transactional context
	 * so this is critical. If you don't pass an Db() instance then the constructor creates a new one, which is useful for
	 * cases where you're reindexing and not in a transaction.
	 */
	public function __construct($opo_db=null, $ps_engine=null) {
		require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
		parent::__construct($opo_db, $ps_engine);
		
		$this->opo_metadata_element = new ca_metadata_elements();
	}
	# -------------------------------------------------------
	/**
	 * Forces a full reindex of all rows in the database or, optionally, a single table
	 */
	public function reindex($ps_table_name=null, $pb_display_progress=true) {
		$t_timer = new Timer();
		if ($ps_table_name) {
			if ($this->opo_datamodel->tableExists($ps_table_name)) {
				$va_table_names = array($ps_table_name);
			} else {
				return false;
			}
		} else {
			$va_table_names = $this->opo_datamodel->getTableNames();
		}

		$this->opo_engine->truncateIndex($ps_table_name);
		$o_db = $this->opo_db;
		foreach($va_table_names as $vs_table) {
			$vn_table_num = $this->opo_datamodel->getTableNum($vs_table);
			$t_table = $this->opo_datamodel->getInstanceByTableName($vs_table, true);
			$vn_table_num = $t_table->tableNum();
			$vs_table_pk = $t_table->primaryKey();

			$va_fields_to_index = $this->getFieldsToIndex($vn_table_num);
			if (!is_array($va_fields_to_index) || (sizeof($va_fields_to_index) == 0)) {
				continue;
			}


			// TODO: enumerate fields in SELECT statement
			$qr_all = $o_db->query("SELECT * FROM $vs_table");

			$vn_num_rows = $qr_all->numRows();
			if ($pb_display_progress) {
				print "\nPROCESSING TABLE $vs_table... [$vn_num_rows rows]\n";
				$vn_last_message_length = 0;
			}

			$vn_c = 0;
			$t_instance = $this->opo_datamodel->getInstanceByTableName($vs_table, true);
			while($qr_all->nextRow()) {
				$t_instance->load($qr_all->get($t_instance->primaryKey()));
				$t_instance->doSearchIndexing(array(), true, $this->opo_engine->engineName());
				if (($vn_c % 10) == 0) {
					if ($pb_display_progress) {
						print str_repeat(chr(8), $vn_last_message_length);
						$vs_message = "$vs_table: indexed $vn_c rows (".sprintf("%2.2f", ($vn_c/$vn_num_rows) * 100)."%) [Memory used: ".sprintf("%4.2f mb", (memory_get_usage(true)/ 1048576)).']';
						print $vs_message;
						$vn_last_message_length = strlen($vs_message);
					}
				}
				$vn_c++;
			}
			$qr_all->free();
			unset($t_instance);
			if ($pb_display_progress) {
				print str_repeat(chr(8), $vn_last_message_length);
				if ($vn_num_rows == 0) { $vn_num_rows = 1; } // avoid div by zero
				$vs_message = "$vs_table: indexed $vn_c rows (".sprintf("%2.2f", ($vn_c/$vn_num_rows) * 100)."%)";
				print $vs_message;
				$vn_last_message_length = strlen($vs_message);

			}
			$this->opo_engine->optimizeIndex($vn_table_num);
		}
		print "\n\nDone! [Indexing took ".$t_timer->getTime(4)." seconds]\n";
	}
	# ------------------------------------------------
	/**
	 * Fetches list of dependencies for a given table
	 */
	public function getDependencies($ps_subject_table){
		/* set up cache */
		$va_frontend_options = array(
			'lifetime' => null, 				/* cache lives forever (until manual destruction) */
			'logging' => false,					/* do not use Zend_Log to log what happens */
			'write_control' => true,			/* immediate read after write is enabled (we don't write often) */
			'automatic_cleaning_factor' => 0, 	/* no automatic cache cleaning */
			'automatic_serialization' => true	/* we store arrays, so we have to enable that */
		);
		$vs_cache_dir = __CA_APP_DIR__.'/tmp';//$this->opo_app_config->get('site_home_dir').'/tmp';
		$va_backend_options = array(
			'cache_dir' => $vs_cache_dir,		/* where to store cache data? */
			'file_locking' => true,				/* cache corruption avoidance */
			'read_control' => false,			/* no read control */
			'file_name_prefix' => 'ca_cache',	/* prefix of cache files */
			'cache_file_umask' => 0700			/* permissions of cache files */
		);
		
		try {
			$vo_cache = Zend_Cache::factory('Core', 'File', $va_frontend_options, $va_backend_options);
		} catch (Exception $e) {
			// return dependencies without caching
			return $this->__getDependencies($ps_subject_table);
		}
		/* handle total cache miss (completely new cache has been generated) */
		if (!(is_array($va_cache_data = $vo_cache->load('ca_table_dependency_array')))) {
    		$va_cache_data = array();
		}

		/* cache outdated? (i.e. changes to search_indexing.conf) */
		$va_configfile_stat = stat($this->opo_search_config->get('search_indexing_config'));
		if($va_configfile_stat['mtime'] != $vo_cache->load('ca_table_dependency_array_mtime')) {
			$vo_cache->save($va_configfile_stat['mtime'],'ca_table_dependency_array_mtime');
			$va_cache_data = array();
		}

		if(isset($va_cache_data[$ps_subject_table]) && is_array($va_cache_data[$ps_subject_table])) { /* cache hit */
			/* return data from cache */
			/* TODO: probably we should implement some checks for data consistency */
			return $va_cache_data[$ps_subject_table];
		} else { /* cache miss */
			/* build dependency graph, store it in cache and return it */
			$va_deps = $this->__getDependencies($ps_subject_table);
			$va_cache_data[$ps_subject_table] = $va_deps;
			$vo_cache->save($va_cache_data,'ca_table_dependency_array');
			return $va_deps;
		}
	}
	# ------------------------------------------------
	/**
	 * Indexes single row in a table; this is the public call when one needs to index content.
	 * indexRow() will analyze the dependencies of the row being indexed and automatically
	 * apply the indexing of the row to all dependent rows in other tables.  (Note that while I call this
	 * a "public" call in fact you shouldn't need to call this directly. BaseModel.php does this for you
	 * during insert() and update().)
	 *
	 * For example, if you are indexing a row in table 'entities', then indexRow()
	 * will automatically apply the indexing not just to the entities record, but also
	 * to all objects, place_names, occurrences, lots, etc. that reference the entity.
	 * The dependencies are configured in the search_indices.conf configuration file.
	 *
	 * "subject" tablenum/row_id refer to the row **to which the indexing is being applied**. This may be the row being indexed
	 * or it may be a dependent row. The "content" tablenum/fieldnum/row_id parameters define the specific row and field being indexed.
	 * This is always the actual row being indexed. $pm_content is the content to be indexed and $pa_options is an optional associative
	 * array of indexing options passed through from the search_indices.conf (no options are defined yet - but will be soon)

	 */
	public function indexRow($pn_subject_tablenum, $pn_subject_row_id, $pa_field_data, $pb_reindex_mode=false, $pa_exclusion_list=null, $pa_changed_fields=null, $pa_old_values=null) {
		$vs_subject_tablename = $this->opo_datamodel->getTableName($pn_subject_tablenum);
		$t_subject = $this->getTableInstance($vs_subject_tablename, true);
		
		$vs_subject_pk = $t_subject->primaryKey();
		if (!is_array($pa_changed_fields)) { $pa_changed_fields = array(); }
		
		foreach($pa_changed_fields as $vs_k => $vb_bool) {
			if (!isset($pa_field_data[$vs_k])) { $pa_field_data[$vs_k] = null; }
		}
		
		$vb_can_do_incremental_indexing = $this->opo_engine->can('incremental_reindexing') ? true : false;		// can the engine do incremental indexing? Or do we need to reindex the entire row every time?
		
		foreach($this->opo_search_config->get('search_indexing_replacements') as $vs_to_replace => $vs_replacement){
			foreach($pa_field_data as $vs_k => &$vs_value) {
				if($vs_replacement=="nothing") {
					$vs_replacement="";
				}
				$vs_value = str_replace($vs_to_replace,$vs_replacement,$vs_value);
			}
		}
		
		if (!$pa_exclusion_list) { $pa_exclusion_list = array(); }
		$pa_exclusion_list[$pn_subject_tablenum][$pn_subject_row_id] = true;
			
		//
		// index fields in subject table itself
		//
		$va_fields_to_index = $this->getFieldsToIndex($pn_subject_tablenum);
		
		if (isset($va_fields_to_index['_metadata'])) {
			$va_data = $va_fields_to_index['_metadata'];
			unset($va_fields_to_index['_metadata']);
			
			foreach($pa_field_data as $vs_k => $vs_v) {
				if (substr($vs_k, 0, 14) === '_ca_attribute_') {
					$va_fields_to_index[$vs_k] = $va_data;
				}
			}
		}
		

		$vb_started_indexing = false;
		
		if (is_array($va_fields_to_index)) {
			$this->opo_engine->startRowIndexing($pn_subject_tablenum, $pn_subject_row_id);
			$vb_started_indexing = true;
			foreach($va_fields_to_index as $vs_field => $va_data) {
				switch($vs_field) {
					/* hierarchy indexing */
					case '_hier_ancestors':
						$va_field_content = array();
						if(is_array($va_ancestors = $t_subject->getHierarchyAncestors($pn_subject_row_id)) && sizeof($va_ancestors)>0) {
							foreach ($va_ancestors as $va_ancestor) {
								$va_ancestor_fields = array_keys($va_fields_to_index['_hier_ancestors']);
								foreach($va_ancestor_fields as $vs_ancestor_field){
									$va_field_content[] = $va_ancestor['NODE'][$vs_ancestor_field];	
								}
							}
							$this->opo_engine->indexField($pn_subject_tablenum, '_hier_ancestors', $pn_subject_row_id, join($va_field_content,"\n"), array());
						}
						break;
					default:
						if (substr($vs_field, 0, 14) === '_ca_attribute_') {
							$vs_v = $pa_field_data[$vs_field];
							if (!preg_match('!^_ca_attribute_(.*)$!', $vs_field, $va_matches)) { continue; }
							if ($vb_can_do_incremental_indexing && (!$pb_reindex_mode) && (!isset($pa_changed_fields[$vs_field]) || !$pa_changed_fields[$vs_field])) {
								continue;	// skip unchanged attribute value
							}

							if($va_data['exclude'] && is_array($va_data['exclude'])){
								$vb_cont = false;
								foreach($va_data["exclude"] as $vs_exclude_type){
									if($this->_getElementID($vs_exclude_type) == intval($va_matches[1])){
										$vb_cont = true;
										break;
									}
								}
								if($vb_cont) continue; // skip excluded attribute type
							}
							
							$va_data['datatype'] = (int)$this->_getElementDataType($va_matches[1]);
							
							switch($va_data['datatype']) {
								case 0: 		// container
									// index components of complex multi-value attributes
									$va_attributes = $t_subject->getAttributesByElement($va_matches[1], array('row_id' => $pn_subject_row_id));
									
									if (sizeof($va_attributes)) { 
										foreach($va_attributes as $vo_attribute) {
											foreach($vo_attribute->getValues() as $vo_value) {
												$vn_list_id = $this->_getElementListID($vo_value->getElementID());
												$this->opo_engine->indexField(4, $vo_value->getElementID(), $vo_attribute->getAttributeID(), $vo_value->getDisplayValue($vn_list_id), $va_data);	// 4 = ca_attributes
											}
										}
									} else {
										// we are deleting a container so cleanup existing sub-values
										$va_sub_elements = $this->opo_metadata_element->getElementsInSet($va_matches[1]);
										
										foreach($va_sub_elements as $vn_i => $va_element_info) {
											$this->opo_engine->indexField(4, $va_element_info['element_id'], $va_element_info['element_id'], '', $va_data);
										}
									}
									break;
								case 3:		// list
									// We pull the preferred labels of list items for indexing here. We do so for all languages. Note that
									// this only done for list attributes that are standalone and not a sub-element in a container. Perhaps
									// we should also index the text of sub-element lists, but it's not clear that it is a good idea yet. The list_id's of
									// sub-elements *are* indexed however, so advanced search forms passing ids instead of text will work.
									$va_tmp = array();
									$va_attributes = $t_subject->getAttributesByElement($va_matches[1], array('row_id' => $pn_subject_row_id));
									foreach($va_attributes as $vo_attribute) {
										foreach($vo_attribute->getValues() as $vo_value) {
											$va_tmp[$vo_attribute->getAttributeID()] = $vo_value->getDisplayValue();
										}
									}
									
									$va_new_values = array();
									$t_item = new ca_list_items();
									$va_labels = $t_item->getPreferredDisplayLabelsForIDs($va_tmp, array('returnAllLocales' => true));
									
									foreach($va_labels as $vn_row_id => $va_labels_per_row) {
										foreach($va_labels_per_row as $vn_locale_id => $vs_label) {
											$va_new_values[$vn_row_id][$vs_label] = true;
										}
									}
									
									foreach($va_tmp as $vn_attribute_id => $vn_item_id) {
										$vs_v = join(' ;  ', array_merge(array($vn_item_id), array_keys($va_new_values[$vn_item_id])));	
										$this->opo_engine->indexField(4, '_'.$vs_field, $vn_attribute_id, $vs_v, $va_data);
									}
									
									break;
								default:
									$va_attributes = $t_subject->getAttributesByElement($va_matches[1], array('row_id' => $pn_subject_row_id));
									foreach($va_attributes as $vo_attribute) {
										foreach($vo_attribute->getValues() as $vo_value) {
											//if the field is a daterange type get content from start and end fields
											$va_field_list = $t_subject->getFieldsArray();
											if(in_array($va_field_list[$vs_field]['FIELD_TYPE'],array(FT_DATERANGE,FT_HISTORIC_DATERANGE))) {
												$start_field = $va_field_list[$vs_field]['START'];
												$end_field = $va_field_list[$vs_field]['END'];
												$pn_content = $pa_field_data[$start_field] . " - " .$pa_field_data[$end_field];
											} else {
												$pn_content = $vo_value->getDisplayValue();
											}
											$this->opo_engine->indexField(4, '_'.$vs_field, $vo_attribute->getAttributeID(), $pn_content, $va_data);
										}
									}
									break;
							}
						} else {
							// plain old field
							if ($vb_can_do_incremental_indexing && (!$pb_reindex_mode) && (!isset($pa_changed_fields[$vs_field]) || !$pa_changed_fields[$vs_field])) {
								continue;
							}
							
							$va_field_list = $t_subject->getFieldsArray();
							if(in_array($va_field_list[$vs_field]['FIELD_TYPE'],array(FT_DATERANGE,FT_HISTORIC_DATERANGE))) {
								// if the field is a daterange type get content from start and end fields
								$start_field = $va_field_list[$vs_field]['START'];
								$end_field = $va_field_list[$vs_field]['END'];
								$pn_content = $pa_field_data[$start_field] . " - " .$pa_field_data[$end_field];
							} else {
								$pn_content = $pa_field_data[$vs_field];
							}
							$this->opo_engine->indexField($pn_subject_tablenum, $vs_field, $pn_subject_row_id, $pn_content, $va_data);
						}	
						break;
				}
			}
		}
		
		// -------------------------------------
		//
		// index related fields
		//
		// Here's where we generate indexing on the subject from content in related rows (data stored externally to the subject row)
		// If the underlying engine doesn't support incremental indexing (if it can't change existing indexing for a row in-place, in other words)
		// then we need to do this every time we update the indexing for a row; if the engine *does* support incremental indexing then
		// we can just update the existing indexing with content from the changed fields.
		//
		// We also do this indexing if we're in "reindexing" mode. In when reindexing is indicated it means that we need to act as if
		// we're indexing this row for the first time, and all indexing should be performed.
if (!$this->opo_engine->can('incremental_reindexing') || $pb_reindex_mode) {
		if (is_array($va_related_tables = $this->getRelatedIndexingTables($pn_subject_tablenum))) {
			if (!$vb_started_indexing) {
				$this->opo_engine->startRowIndexing($pn_subject_tablenum, $pn_subject_row_id);
				$vb_started_indexing = true;
			}
			
			foreach($va_related_tables as $vs_related_table) {
				$vn_related_tablenum = $this->opo_datamodel->getTableNum($vs_related_table);
				$vs_related_pk = $this->opo_datamodel->getTablePrimaryKeyName($vn_related_tablenum);
				
				$va_fields_to_index = $this->getFieldsToIndex($pn_subject_tablenum, $vs_related_table);
				$va_table_info = $this->getTableIndexingInfo($pn_subject_tablenum, $vs_related_table);
				
				//print "for table {$vs_related_table}: ".print_R($va_fields_to_index, true)."<br>";

				$va_field_list = array_keys($va_fields_to_index);
				
				$va_table_list_list = array(); //$va_table_info['tables'];
				$va_table_key_list = array(); //$va_table_info['keys'];
				
				if (isset($va_table_info['key']) && $va_table_info['key']) {
					$va_table_list_list = array('key' => array($vs_related_table));
					$va_table_key_list = array();
				} else {
					if ($pb_reindex_mode || (!$vb_can_do_incremental_indexing) || isset($va_fields_to_index['_hier_ancestors'])) {
						$va_table_list_list = isset($va_table_info['tables']) ? $va_table_info['tables'] : null;
						$va_table_key_list = isset($va_table_info['keys']) ? $va_table_info['keys'] : null;
					}
				}
				
				if (!is_array($va_table_list_list) || !sizeof($va_table_list_list)) { continue; } //$va_table_list_list = array($vs_related_table => array()); }
			
				foreach($va_table_list_list as $vs_list_name => $va_linking_tables) {
					array_push($va_linking_tables, $vs_related_table);
					$vs_left_table = $vs_subject_tablename;
	
					$va_joins = array();
					foreach($va_linking_tables as $vs_right_table) {
						if (is_array($va_table_key_list) && (isset($va_table_key_list[$vs_list_name][$vs_right_table][$vs_left_table]) || isset($va_table_key_list[$vs_list_name][$vs_left_table][$vs_right_table]))) {		// are the keys for this join specified in the indexing config?
							if (isset($va_table_key_list[$vs_list_name][$vs_left_table][$vs_right_table])) {
								$va_key_spec = $va_table_key_list[$vs_list_name][$vs_left_table][$vs_right_table];	
								$vs_join = 'INNER JOIN '.$vs_right_table.' ON ('.$vs_right_table.'.'.$va_key_spec['right_key'].' = '.$vs_left_table.'.'.$va_key_spec['left_key'].' AND ';
								if ($va_key_spec['right_table_num']) {
									$vs_join .= $vs_right_table.'.'.$va_key_spec['right_table_num'].' = '.$this->opo_datamodel->getTableNum($vs_left_table).')';
								} else {
									$vs_join .= $vs_left_table.'.'.$va_key_spec['left_table_num'].' = '.$this->opo_datamodel->getTableNum($vs_right_table).')';
								}
							} else {
								$va_key_spec = $va_table_key_list[$vs_list_name][$vs_right_table][$vs_left_table];
								$vs_join = 'INNER JOIN '.$vs_right_table.' ON ('.$vs_right_table.'.'.$va_key_spec['left_key'].' = '.$vs_left_table.'.'.$va_key_spec['right_key'].' AND ';
								if ($va_key_spec['right_table_num']) {
									$vs_join .= $vs_left_table.'.'.$va_key_spec['right_table_num'].' = '.$this->opo_datamodel->getTableNum($vs_right_table).')';
								} else {
									$vs_join .= $vs_right_table.'.'.$va_key_spec['left_table_num'].' = '.$this->opo_datamodel->getTableNum($vs_left_table).')';
								}
							}
							
							$va_joins[] = $vs_join;
						} else {
							if ($va_rel = $this->opo_datamodel->getOneToManyRelations($vs_left_table, $vs_right_table)) {
								$va_joins[] = 'INNER JOIN '.$va_rel['many_table'].' ON '.$va_rel['one_table'].'.'.$va_rel['one_table_field'].' = '.$va_rel['many_table'].'.'.$va_rel['many_table_field'];
							} else {
								if ($va_rel = $this->opo_datamodel->getOneToManyRelations($vs_right_table, $vs_left_table)) {
									$va_joins[] = 'INNER JOIN '.$va_rel['one_table'].' ON '.$va_rel['one_table'].'.'.$va_rel['one_table_field'].' = '.$va_rel['many_table'].'.'.$va_rel['many_table_field'];
								}
							}
						}
						$vs_left_table = $vs_right_table;
					}
	
					$va_proc_field_list = array();
					$vn_field_list_count = sizeof($va_field_list);
					for($vn_i=0; $vn_i < $vn_field_list_count; $vn_i++) {
						if ($va_field_list[$vn_i] == '_count' ||
							$va_field_list[$vn_i] == '_hier_ancestors' ||
							$va_field_list[$vn_i] == '_hier_siblings' ||
							$va_field_list[$vn_i] == '_hier_children') {
							continue; 
						}
						$va_proc_field_list[$vn_i] = $vs_related_table.'.'.$va_field_list[$vn_i];
					}
					$va_proc_field_list[] = $vs_related_table.'.'.$vs_related_pk;
					if (isset($va_rel['many_table']) && $va_rel['many_table']) { 
						$va_proc_field_list[] = $va_rel['many_table'].'.'.$va_rel['many_table_field'];
					}
					$vs_sql = "
						SELECT ".join(",", $va_proc_field_list)."
						FROM ".$vs_subject_tablename."
						".join("\n", $va_joins)."
						WHERE
							(".$vs_subject_tablename.'.'.$vs_subject_pk.' = ?)
					';
					$qr_res = $this->opo_db->query($vs_sql, $pn_subject_row_id);
					
					if ($this->opo_db->numErrors()) {
						// TODO: proper error reporting
						print_r($this->opo_db->getErrors());
						print "\n\n$vs_sql\n";
					}
					while($qr_res->nextRow()) {
						$va_field_data = $qr_res->getRow();
						foreach($va_fields_to_index as $vs_rel_field => $va_rel_field_info) {
							switch($vs_rel_field){
								case '_count':
								case '_hier_ancestors':
									break;
								default:
									$this->opo_engine->indexField($vn_related_tablenum, $vs_rel_field, $qr_res->get($vs_related_pk), trim($va_field_data[$vs_rel_field]), $va_rel_field_info);
									break;	
							}
						}
					}
					if (isset($va_fields_to_index['_count'])) {
						$this->opo_engine->indexField($pn_subject_tablenum, '_count', $pn_subject_row_id, $qr_res->numRows(), array());
					}
					/* hierarchical indexing for related tables */
					if (isset($va_fields_to_index['_hier_ancestors']) && ($qr_res->numRows()>0)) {
						$qr_res->seek(0);
						$t_instance = $this->opo_datamodel->getInstanceByTableNum($vn_related_tablenum, true);
						
						$vn_label_table_num = null;
						if ($t_label_instance = $t_instance->getLabelTableInstance()) {
							$vn_label_table_num = $t_label_instance->tableNum();
						}
						
						while($qr_res->nextRow()) {
							$vn_subj_id = intval($qr_res->get($t_instance->primaryKey()));
						
							if(is_array($va_ancestors = $t_instance->getHierarchyAncestors($vn_subj_id, array('includeSelf' => true))) && sizeof($va_ancestors)>0) {
								array_pop($va_ancestors); // pop off root
								
								$va_field_content = array();
								foreach ($va_ancestors as $va_ancestor) {
									$va_ancestor_fields = array_values($va_fields_to_index['_hier_ancestors']);
									
									$vn_id = $va_ancestor['NODE'][$t_instance->primaryKey()];
									if ($t_instance->load($vn_id)) {
										foreach($va_ancestor_fields as $vs_ancestor_field){
											switch($vs_ancestor_field) {
												case '_labels':
													$va_labels = $t_instance->getLabels();	
													$vs_label_fld = $t_instance->getLabelDisplayField();
													foreach($va_labels as $vn_id => $va_labels_by_locale) {
														foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
															foreach($va_label_list as $va_label) {
																$va_field_content[] = $va_label[$vs_label_fld];
															}
														}
													}
													break;
												default:
													if ($vs_content = trim($t_instance->get($vs_ancestor_field))) {
														$va_field_content[] = $vs_content;	
													}
													break;
											}
										}
									}
								}
								$this->opo_engine->indexField($t_instance->tableNum(), '_hier_ancestors', $vn_subj_id, join($va_field_content,"\n"), array());
							}
						}
					}
				}
			}
		}
}		
		// save indexing on subject
		if ($vb_started_indexing) {
			$this->opo_engine->commitRowIndexing();
		}
		
		if ((!$pb_reindex_mode) && (sizeof($pa_changed_fields) > 0)) {
			// When not reindexing then we consider the effect of the change on this row upon related rows that use it
			// in their indexing. This means figuring out which related tables have indexing that depend upon the subject row.
			//
			// We deal with this by pulling up a dependency map generated from the search_indexing.conf file and then reindexing
			// those rows
			$va_deps = $this->getDependencies($vs_subject_tablename);

			$va_changed_field_nums = array();
			foreach(array_keys($pa_changed_fields) as $vs_f) {
				$va_changed_field_nums[$vs_f] = $t_subject->fieldNum($vs_f);	
			}
			
			//
			// reindex rows in dependent tables that use the subject_row_id
			//
			$va_rows_to_reindex = $this->_getDependentRowsForSubject($pn_subject_tablenum, $pn_subject_row_id, $va_deps);
			
			if ($vb_can_do_incremental_indexing) { 
				$va_rows_to_reindex_by_row_id = array();
				
				foreach($va_rows_to_reindex as $vs_key => $va_row_to_reindex) {
					foreach($va_row_to_reindex['field_nums'] as $vs_fld_name => $vn_fld_num) {
						$vs_new_key = $va_row_to_reindex['table_num'].'/'.$va_row_to_reindex['field_table_num'].'/'.$vn_fld_num.'/'.$va_row_to_reindex['field_row_id'];
					
						if(!isset($va_rows_to_reindex_by_row_id[$vs_new_key])) {
							$va_rows_to_reindex_by_row_id[$vs_new_key] = array(
								'table_num' => $va_row_to_reindex['table_num'],
								'row_ids' => array(),
								'field_table_num' => $va_row_to_reindex['field_table_num'],
								'field_num' => $vn_fld_num,
								'field_name' => $vs_fld_name,
								'field_row_id' => $va_row_to_reindex['field_row_id'],
								'field_values' => $va_row_to_reindex['field_values'],
								'indexing_info' => $va_row_to_reindex['indexing_info'][$vs_fld_name]
							);
						}
						$va_rows_to_reindex_by_row_id[$vs_new_key]['row_ids'][] = $va_row_to_reindex['row_id'];
					}
				}
				foreach($va_rows_to_reindex_by_row_id as $va_row_to_reindex) {
					if ($va_row_to_reindex['field_table_num'] === 4) {		// is attribute
						$va_row_to_reindex['indexing_info']['datatype'] = $this->_getElementDataType($va_row_to_reindex['field_num']);
					}
					$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], $va_row_to_reindex['row_ids'], $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], $va_row_to_reindex['field_row_id'], $va_row_to_reindex['field_values'][$va_row_to_reindex['field_name']], $va_row_to_reindex['indexing_info']);
				}
			} else {
				//
				// If the underlying engine doesn't support incremental indexing then
				// we fall back to reindexing each dependenting row completely and independently.
				// This can be *really* slow for subjects with many dependent rows (for example, a ca_list_item value used as a type for many ca_objects rows)
				// and we need to think about how to optimize this for such engines; ultimately since no matter how you slice it in such
				// engines you're going to have a lot of reindexing going on, we may just have to construct a facility to handle large
				// indexing tasks in a separate process when the number of dependent rows exceeds a certain threshold
				//
				
				$o_indexer = new SearchIndexer($this->opo_db);
				foreach($va_rows_to_reindex as $va_row_to_reindex) {
					if ((!$t_dep) || ($t_dep->tableNum() != $va_row_to_reindex['table_num'])) {
						$t_dep = $this->opo_datamodel->getInstanceByTableNum($va_row_to_reindex['table_num']);
					}
					
					$vb_support_attributes = is_subclass_of($t_dep, 'BaseModelWithAttributes') ? true : false;
					if (is_array($pa_exclusion_list[$va_row_to_reindex['table_num']]) && (isset($pa_exclusion_list[$va_row_to_reindex['table_num']][$va_row_to_reindex['row_id']]))) { continue; }
					// trigger reindexing
					if ($vb_support_attributes) {
						if ($t_dep->load($va_row_to_reindex['row_id'])) {
							// 
							$o_indexer->indexRow($va_row_to_reindex['table_num'], $va_row_to_reindex['row_id'], $t_dep->getFieldValuesArray(), true, $pa_exclusion_list);
						}
					} else {
						$o_indexer->indexRow($va_row_to_reindex['table_num'], $va_row_to_reindex['row_id'], $va_row_to_reindex['field_values'], true, $pa_exclusion_list);
					}
				}
				$o_indexer = null;
			}
		} 
	}
	# ------------------------------------------------
	/**
	 * Removes indexing for specified row in table; this is the public call when one is deleting a record
	 * and needs to remove the associated indexing. unindexRow() will also remove indexing for the specified
	 * row from all dependent rows in other tables. It essentially undoes the results of indexRow().
	 * (Note that while this is called this a "public" call in fact you shouldn't need to call this directly. BaseModel.php does
	 * this for you during delete().)
	 */
	public function startRowUnIndexing($pn_subject_tablenum, $pn_subject_row_id) {
		$vb_can_do_incremental_indexing = $this->opo_engine->can('incremental_reindexing') ? true : false;		// can the engine do incremental indexing? Or do we need to reindex the entire row every time?
		
		$vs_subject_tablename 		= $this->opo_datamodel->getTableName($pn_subject_tablenum);
		$t_subject 					= $this->getTableInstance($vs_subject_tablename, true);
		$vs_subject_pk 				= $t_subject->primaryKey();

		$va_deps = $this->getDependencies($vs_subject_tablename);
		
		$this->opa_dependencies_to_update = $this->_getDependentRowsForSubject($pn_subject_tablenum, $pn_subject_row_id, $va_deps);
		return true;
	}
	# ------------------------------------------------
	public function commitRowUnIndexing($pn_subject_tablenum, $pn_subject_row_id) {
		$vb_can_do_incremental_indexing = $this->opo_engine->can('incremental_reindexing') ? true : false;		// can the engine do incremental indexing? Or do we need to reindex the entire row every time?
		
		// delete index from subject
		$this->opo_engine->removeRowIndexing($pn_subject_tablenum, $pn_subject_row_id); 
		
		if (is_array($this->opa_dependencies_to_update)) {
			if (!$vb_can_do_incremental_indexing) {
				$o_indexer = new SearchIndexer($this->opo_db);
				foreach($this->opa_dependencies_to_update as $va_item) {
					// trigger reindexing of related rows in dependent tables
					$o_indexer->indexRow($va_item['field_table_num'], $va_item['field_row_id'], $va_item['field_values'], true);
				}
				$o_indexer = null;
			} else {
				// incremental indexing engines delete dependent rows here
				// delete from index where other subjects reference it 
				$this->opo_engine->removeRowIndexing(null, null, $pn_subject_tablenum, null, $pn_subject_row_id);
				foreach($this->opa_dependencies_to_update as $va_item) {
					$this->opo_engine->removeRowIndexing(null, null, $va_item['field_table_num'], null, $va_item['field_row_id']); 
				}
			}	
		}
		$this->opa_dependencies_to_update = null;
	}
	# ------------------------------------------------
	/**
	 * Returns an array with info about rows that need to be reindexed due to change in content for the given subject
	 */
	private function _getDependentRowsForSubject($pn_subject_tablenum, $pn_subject_row_id, $va_deps) {
		$va_dependent_rows = array();
		$vs_subject_tablename = $this->opo_datamodel->getTableName($pn_subject_tablenum);
		
		$t_subject = $this->getTableInstance($vs_subject_tablename);
		$vs_subject_pk = $t_subject->primaryKey();
		
		foreach($va_deps as $vs_dep_table) {
			foreach($this->getRelatedIndexingTables($vs_dep_table) as $vs_x) {
				$va_table_info = $this->getTableIndexingInfo($vs_dep_table, $vs_x);
				
				if (isset($va_table_info['key']) && $va_table_info['key']) {
					$va_table_list_list = array('key' => array($vs_dep_table));
					$va_table_key_list = array();
				} else {
					$va_table_list_list = isset($va_table_info['tables']) ? $va_table_info['tables'] : null;
					$va_table_key_list = isset($va_table_info['keys']) ? $va_table_info['keys'] : null;
				}
				// loop through the tables for each relationship between the subject and the dep
				foreach($va_table_list_list as $vs_list_name => $va_linking_tables) {
					if (($vs_x != $vs_subject_tablename) && !in_array($vs_subject_tablename, $va_linking_tables)) { continue; }
					
					$va_linking_tables = is_array($va_linking_tables) ? array_reverse($va_linking_tables) : array();		// they come out of the conf file reversed from how we want them
					array_push($va_linking_tables, $vs_dep_table);															// the dep table name is not listed in the config file (it's redundant)
					
					$t_dep 				= $this->getTableInstance($vs_dep_table);
					$vs_dep_pk 			= $t_dep->primaryKey();
					$vn_dep_tablenum 	= $t_dep->tableNum();
					
					$va_rel_indexing_tables = $this->getRelatedIndexingTables($vn_dep_tablenum);	// get list of tables which are indexed against the dep
	
					// look for relationships where the current subject table is involved
					$va_rel_tables_to_index_list = array();
					
					foreach($va_rel_indexing_tables as $vs_rel_table) {
					
						if ($vs_rel_table == $vs_subject_tablename) { 			// direct relationship
							$va_rel_tables_to_index_list[] = $vs_rel_table;
							continue;
						}
						$va_rel_indexing_info = $this->getTableIndexingInfo($vn_dep_tablenum, $vs_rel_table);
						if (is_array($va_rel_indexing_info['tables']) && sizeof($va_rel_indexing_info['tables'])) {
							foreach($va_rel_indexing_info['tables'] as $vs_n => $va_table_list) {
								if (in_array($vs_subject_tablename, $va_table_list)) {		// implicit relationship
									$va_rel_tables_to_index_list[] = $vs_rel_table;
								}
							}
						} else {
							if (($va_rel_indexing_info['key']) && ($vs_rel_table == $vs_subject_tablename)) {	// many-to-one relationship
								$va_rel_tables_to_index_list[] = $vs_rel_table;
							}
						}
						
					}
					
					// update indexing for each relationship
					foreach($va_rel_tables_to_index_list as $vs_rel_table) {
						$va_indexing_info = $this->getTableIndexingInfo($vn_dep_tablenum, $vs_rel_table);
						$vn_rel_tablenum = $this->opo_datamodel->getTableNum($vs_rel_table);
						$vn_rel_pk = $this->opo_datamodel->getTablePrimaryKeyName($vn_rel_tablenum);
						if (is_array($va_indexing_info['tables']) && (sizeof($va_indexing_info['tables']))) {
							$va_table_path = $va_indexing_info['tables'];
						} else {
							if ($va_indexing_info['key']) {
								$va_table_path = array(0 => array($vs_rel_table, $vs_dep_table));
							} else {
								continue;
							}
						}
						
						foreach($va_table_path as $vs_n => $va_table_list) {
							if (!in_array($vs_rel_table, $va_table_list)) { $va_table_list[] = $vs_rel_table; }
							
							$va_full_path = $va_table_list;
							array_unshift($va_full_path, $vs_dep_table);
							$qr_rel_rows = $this->_getRelatedRows(array_reverse($va_full_path), isset($va_table_key_list[$vs_list_name]) ? $va_table_key_list[$vs_list_name] : null, $vs_subject_tablename, $pn_subject_row_id);
							$va_fields_to_index = $this->getFieldsToIndex($vn_dep_tablenum, $vs_rel_table);
							
							if ($qr_rel_rows) {
						
								while($qr_rel_rows->nextRow()) {
									foreach($va_fields_to_index as $vs_field => $va_indexing_info) {
										switch($vs_field) {
											case '_hier_ancestors':
												$vn_fld_num = 255;
												break;
											case '_count':
												$vn_fld_num = 254;
												break;
											default:
												if (!($vn_fld_num = $this->opo_datamodel->getFieldNum($vs_rel_table, $vs_field))) { continue; }
												break;
										}
										
										
										$vn_fld_row_id = $qr_rel_rows->get($vn_rel_pk);
										$vn_row_id = $qr_rel_rows->get($vs_dep_pk);
										$vs_key = $vn_dep_tablenum.'/'.$vn_row_id.'/'.$vn_rel_tablenum.'/'.$vn_fld_row_id;
										
										if (!isset($va_dependent_rows[$vs_key])) {
											$va_dependent_rows[$vs_key] = array(
												'table_num' => $vn_dep_tablenum,
												'row_id' => $vn_row_id,
												'field_table_num' => $vn_rel_tablenum,
												'field_row_id' => $vn_fld_row_id,
												'field_values' => $qr_rel_rows->getRow(),
												'field_nums' => array(),
												'field_names' => array()
											);
										}
										$va_dependent_rows[$vs_key]['field_nums'][$vs_field] = $vn_fld_num;
										$va_dependent_rows[$vs_key]['field_names'][$vn_fld_num] = $vs_field;
										$va_dependent_rows[$vs_key]['indexing_info'][$vs_field] = $va_indexing_info;
									}
								}
							}
						}
					}
				}
			}
		}
		return $va_dependent_rows;
	}
	# ------------------------------------------------
	/**
	 * Returns query result with rows related via tables specified in $pa_tables to the specified subject; used by
	 * _getDependentRowsForSubject() to generate dependent row set
	 */
	private function _getRelatedRows($pa_tables, $pa_table_keys, $ps_subject_tablename, $pn_row_id) {
		if (!in_array($ps_subject_tablename, $pa_tables)) { $pa_tables[] = $ps_subject_tablename; }
		$vs_key = md5(print_r($pa_tables, true)."/".print_r($pa_table_keys, true)."/".$ps_subject_tablename);

		if (!isset(SearchIndexer::$s_related_rows_joins_cache[$vs_key]) || !(SearchIndexer::$s_related_rows_joins_cache[$vs_key])) {
			$vs_left_table = $vs_select_tablename = array_shift($pa_tables);
	
			$t_subject = $this->opo_datamodel->getInstanceByTableName($ps_subject_tablename, true);
			$vs_subject_pk = $t_subject->primaryKey();
			
			$va_joins = array();
			foreach($pa_tables as $vs_right_table) {
				if ($vs_right_table == $vs_select_tablename) { continue; }
				if (is_array($pa_table_keys) && (isset($pa_table_keys[$vs_right_table][$vs_left_table]) || isset($pa_table_keys[$vs_left_table][$vs_right_table]))) {		// are the keys for this join specified in the indexing config?
					if (isset($pa_table_keys[$vs_left_table][$vs_right_table])) {
						$va_key_spec = $pa_table_keys[$vs_left_table][$vs_right_table];	
						$vs_join = 'INNER JOIN '.$vs_right_table.' ON ('.$vs_right_table.'.'.$va_key_spec['right_key'].' = '.$vs_left_table.'.'.$va_key_spec['left_key'].' AND ';
					
						if ($va_key_spec['right_table_num']) {
							$vs_join .= $vs_right_table.'.'.$va_key_spec['right_table_num'].' = '.$this->opo_datamodel->getTableNum($vs_left_table).')';
						} else {
							$vs_join .= $vs_left_table.'.'.$va_key_spec['left_table_num'].' = '.$this->opo_datamodel->getTableNum($vs_right_table).')';
						}
					} else {
						$va_key_spec = $pa_table_keys[$vs_right_table][$vs_left_table];
						$vs_join = 'INNER JOIN '.$vs_right_table.' ON ('.$vs_right_table.'.'.$va_key_spec['left_key'].' = '.$vs_left_table.'.'.$va_key_spec['right_key'].' AND ';
					
						if ($va_key_spec['right_table_num']) {
							$vs_join .= $vs_left_table.'.'.$va_key_spec['right_table_num'].' = '.$this->opo_datamodel->getTableNum($vs_right_table).')';
						} else {
							$vs_join .= $vs_right_table.'.'.$va_key_spec['left_table_num'].' = '.$this->opo_datamodel->getTableNum($vs_left_table).')';
						}
					}
					$va_joins[] = $vs_join;
	
				} else {
					if ($va_rel = $this->opo_datamodel->getOneToManyRelations($vs_left_table, $vs_right_table)) {
						$va_joins[] = 'INNER JOIN '.$va_rel['many_table'].' ON '.$va_rel['one_table'].'.'.$va_rel['one_table_field'].' = '.$va_rel['many_table'].'.'.$va_rel['many_table_field'];
					} else {
						if ($va_rel = $this->opo_datamodel->getOneToManyRelations($vs_right_table, $vs_left_table)) {
							$va_joins[] = 'INNER JOIN '.$va_rel['one_table'].' ON '.$va_rel['one_table'].'.'.$va_rel['one_table_field'].' = '.$va_rel['many_table'].'.'.$va_rel['many_table_field'];
						}
					}
				}
				$vs_left_table = $vs_right_table;
			}
		
			SearchIndexer::$s_related_rows_joins_cache[$vs_key] = $va_joins;
		} else {
			$va_joins = SearchIndexer::$s_related_rows_joins_cache[$vs_key];
			$vs_select_tablename = array_shift($pa_tables);
			$t_subject = $this->opo_datamodel->getInstanceByTableName($ps_subject_tablename, true);
			$vs_subject_pk = $t_subject->primaryKey();
		}

		$vs_sql = "
			SELECT *
			FROM ".$vs_select_tablename."
			".join("\n", $va_joins)."
			WHERE
			{$ps_subject_tablename}.{$vs_subject_pk} = ?
		";
		
		return $this->opo_db->query($vs_sql, $pn_row_id);
	}
	# ------------------------------------------------
	/**
	 * Generates directed graph that represents indexing dependencies between tables in the database
	 * and then derives a list of indexed tables that might contain rows needing to be reindexed because
	 * they use the subject table as part of their indexing.
	 */
	private function __getDependencies($ps_subject_table) {
		$o_graph = new Graph();
		$va_indexed_tables = $this->getIndexedTables();

		foreach($va_indexed_tables as $vs_indexed_table) {
			if ($vs_indexed_table == $ps_subject_table) { continue; }		// the subject can't be dependent upon itself

			// get list related tables used to index the subject table
			$va_related_tables = $this->getRelatedIndexingTables($vs_indexed_table);
			foreach($va_related_tables as $vs_related_table) {
				// get list of tables in indexing relationship
				// eg. if the subject is 'objects', and the related table is 'entities' then
				// the table list would be ['objects', 'objects_x_entities', 'entities']
				$va_info = $this->getTableIndexingInfo($vs_indexed_table, $vs_related_table);
				$va_table_list_list = $va_info['tables'];
				
				if (!is_array($va_table_list_list) || !sizeof($va_table_list_list)) { 
					if ($vs_table_key = $va_info['key']) {
						// Push direct relationship through one-to-many key onto table list
						$va_table_list_list = array($vs_related_table => array());
					} else {
						$va_table_list_list = array();
					}
				}

				foreach($va_table_list_list as $vs_list_name => $va_table_list) {
					array_unshift($va_table_list,$vs_indexed_table);
					array_push($va_table_list, $vs_related_table);
	
					if (in_array($ps_subject_table, $va_table_list)) {			// we only care about indexing relationships that include the subject table
						// for each each related table record the intervening tables in the graph
						$vs_last_table = '';
						foreach($va_table_list as $vs_tablename) {
							$o_graph->addNode($vs_tablename);
							if ($vs_last_table) {
								if ($va_rel = $this->opo_datamodel->getOneToManyRelations($vs_tablename, $vs_last_table)) {		// determining direction of relationship (directionality is from the "many" table to the "one" table
									$o_graph->addRelationship($vs_tablename, $vs_last_table, 10, true);
								} else {
									$o_graph->addRelationship($vs_last_table, $vs_tablename, 10, true);
								}
							}
							$vs_last_table = $vs_tablename;
						}
					}
				}
			}
		}

		$va_topo_list = $o_graph->getTopologicalSort();

		$va_deps = array();
		foreach($va_topo_list as $vs_tablename) {
			if ($vs_tablename == $ps_subject_table) { continue; }
			if (!in_array($vs_tablename, $va_indexed_tables)) { continue; }

			$va_deps[] = $vs_tablename;
		}

		return $va_deps;
	}
	# ------------------------------------------------
	/**
	 * Returns element_id of ca_metadata_element with specified element_code or NULL if the element doesn't exist
	 * Because of dependency and performance issues we do a straight query here rather than go through the ca_metadata_elements model
	 */
	private function _getElementID($ps_element_code) {
		if (isset(SearchIndexer::$s_SearchIndexer_element_id_cache[$ps_element_code])) { return SearchIndexer::$s_SearchIndexer_element_id_cache[$ps_element_code]; }
		
		if (is_numeric($ps_element_code)) {
			$qr_res = $this->opo_db->query("
				SELECT element_id, datatype, list_id FROM ca_metadata_elements WHERE element_id = ?
			", intval($ps_element_code));
		} else {
			$qr_res = $this->opo_db->query("
				SELECT element_id, datatype, list_id FROM ca_metadata_elements WHERE element_code = ?
			", $ps_element_code);
		}
		if (!$qr_res->nextRow()) { return null; }
		$vn_element_id =  $qr_res->get('element_id');
		SearchIndexer::$s_SearchIndexer_element_data_type_cache[$ps_element_code] = SearchIndexer::$s_SearchIndexer_element_data_type_cache[$vn_element_id] = $qr_res->get('datatype');
		SearchIndexer::$s_SearchIndexer_element_list_id_cache[$ps_element_code] = SearchIndexer::$s_SearchIndexer_element_list_id_cache[$vn_element_id] = $qr_res->get('list_id');
		return SearchIndexer::$s_SearchIndexer_element_id_cache[$ps_element_code] = $vn_element_id;
	}
	# ------------------------------------------------
	/**
	 * Returns datatype code of ca_metadata_element with specified element_code or NULL if the element doesn't exist
	 */
	private function _getElementDataType($ps_element_code) {
		$vn_element_id = $this->_getElementID($ps_element_code);	// ensures $s_SearchIndexer_element_data_type_cache[$ps_element_code] is populated
		return SearchIndexer::$s_SearchIndexer_element_data_type_cache[$vn_element_id];
	}
	# ------------------------------------------------
	/**
	 * Returns list_id of ca_metadata_element with specified element_code or NULL if the element doesn't exist
	 */
	private function _getElementListID($ps_element_code) {
		$vn_element_id = $this->_getElementID($ps_element_code);	// ensures $s_SearchIndexer_element_data_type_cache[$ps_element_code] is populated
		return SearchIndexer::$s_SearchIndexer_element_list_id_cache[$vn_element_id];
	}
	# ------------------------------------------------
}
?>