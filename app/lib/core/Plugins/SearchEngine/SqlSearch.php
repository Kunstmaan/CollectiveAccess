<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/SqlSearch.php :
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

 require_once(__CA_LIB_DIR__.'/core/Db.php');
 require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
 require_once(__CA_LIB_DIR__.'/core/Plugins/WLPlug.php');
 require_once(__CA_LIB_DIR__.'/core/Plugins/IWLPlugSearchEngine.php');
 require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/SqlSearchResult.php'); 
 require_once(__CA_LIB_DIR__.'/core/Search/Common/Stemmer/SnoballStemmer.php');
 require_once(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser.php');
 
 require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');

class WLPlugSearchEngineSqlSearch extends WLPlug implements IWLPlugSearchEngine {
	# -------------------------------------------------------
	private $opo_config;
	private $opo_search_config;
	
	private $opo_datamodel;
	private $ops_encoding;

	private $opn_indexing_subject_tablenum=null;
	private $opn_indexing_subject_row_id=null;

	private $opa_options;
	private $opa_doc_content_buffer;
	
	private $opa_filters;
	
	private $opa_capabilities;
	
	private $opo_db;			// db connection
	private $ops_insert_sql; 	// sql INSERT statement (for indexing)
	
	private $ops_delete_sql;	// sql DELETE statement (for unindexing)
	private $opqr_delete;		// prepared statement for delete (subject_tablenum and subject_row_id only specified)
	private $ops_delete_with_field_specification_sql;		// sql DELETE statement (for unindexing)
	private $opqr_delete_with_field_specification;			// prepared statement for delete with field_tablenum and field_num specified
	
	private $ops_update_index_in_place_sql;
	private $opqr_update_index_in_place;
	
	private $ops_search_mysql_fulltext_tokenize_preg;			// Perl-compatible regular expression used to tokenize strings for indexing (include preg delimiters - eg. it could look like this: !\.\_\-! which would tokenize on .,- and _... the '!' chars block off the regex)
	
	private $opo_stemmer;		// snoball stemmer
	private $opb_do_stemming = true;
	
	private $opo_tep;			// date/time expression parse
	
	static $s_word_cache = array();
	
	# -------------------------------------------------------
	public function __construct() {
		$this->opo_config = Configuration::load();
		$this->opo_search_config = Configuration::load($this->opo_config->get('search_config'));
		$this->opo_datamodel = Datamodel::load();
		$this->ops_encoding = $this->opo_config->get('character_set');
		$this->ops_search_mysql_fulltext_tokenize_preg = $this->opo_search_config->get('search_mysql_fulltext_tokenize_preg');
		
		$this->init();
		$this->opo_db = new Db();
		$this->opo_tep = new TimeExpressionParser();
		
		$this->ops_lookup_word_sql = "
			SELECT word_id 
			FROM ca_sql_search_words
			WHERE
				word = ?
		";
		
		$this->opqr_lookup_word = $this->opo_db->prepare($this->ops_lookup_word_sql);
		
		$this->ops_insert_word_index_sql = "
			INSERT INTO ca_sql_search_word_index
			(table_num, row_id, field_table_num, field_num, field_row_id, word_id, seq, boost)
			VALUES
		";
		
		$this->ops_insert_word_sql = "
			INSERT INTO ca_sql_search_words
			(word, soundex, metaphone, stem)
			VALUES
			(?, ?, ?, ?)
		";
		$this->opqr_insert_word = $this->opo_db->prepare($this->ops_insert_word_sql);
		
		$this->ops_delete_word_index_sql = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?)";
		$this->opqr_delete_word_index = $this->opo_db->prepare($this->ops_delete_word_index_sql);
		
		$this->ops_delete_word_index_with_field_num_sql = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (field_table_num = ?) AND (field_num = ?)";
		$this->opqr_delete_word_index_with_field_num = $this->opo_db->prepare($this->ops_delete_word_index_with_field_num_sql);
		
		$this->ops_delete_word_index_with_field_row_id_sql = "DELETE FROM ca_sql_search_word_index WHERE (table_num = ?) AND (row_id = ?) AND (field_table_num = ?) AND (field_row_id = ?)";
		$this->opqr_delete_word_index_with_field_row_id = $this->opo_db->prepare($this->ops_delete_word_index_with_field_row_id_sql);
				
		$this->opo_stemmer = new SnoballStemmer();
		$this->opb_do_stemming = (int)trim($this->opo_search_config->get('search_sql_search_do_stemming')) ? true : false;
		
		
		$this->debug = false;
	}
	# -------------------------------------------------------
	# Initialization and capabilities
	# -------------------------------------------------------
	public function init() {
		$this->opa_options = array(
				'limit' => 2000			// maximum number of hits to return [default=2000]
		);
		
		// Defines specific capabilities of this engine and plug-in
		// The indexer and engine can use this information to optimize how they call the plug-in
		$this->opa_capabilities = array(
			'incremental_reindexing' => true		// can update indexing using only changed fields, rather than having to reindex the entire row (and related stuff) every time
		);
	}
	# -------------------------------------------------------
	/**
	 * Completely clear index (usually in preparation for a full reindex)
	 */
	public function truncateIndex() {
		$this->opo_db->query("TRUNCATE TABLE ca_sql_search_word_index");
		$this->opo_db->query("TRUNCATE TABLE ca_sql_search_text");
		$this->opo_db->query("TRUNCATE TABLE ca_sql_search_words");
		$this->opo_db->query("TRUNCATE TABLE ca_sql_search_ngrams");
		return true;
	}
	# -------------------------------------------------------
	private function _setMode($ps_mode) {
		switch ($ps_mode) {
			case 'search':
			
				break;
			case 'indexing':
			
				break;
			default:
				break;
		}
		
	}
	# -------------------------------------------------------
	/**
	 * Returns true/false indication of whether the plug-in has a capability
	 **/
	public function can($ps_capability) {
		return $this->opa_capabilities[$ps_capability];
	}
	# -------------------------------------------------------
	public function __destruct() {
		unset($this->opo_config);
		unset($this->opo_search_config);
		unset($this->opo_datamodel);
		unset($this->opo_db);
		unset($this->opo_tep);
	}
	# -------------------------------------------------------
	# Options
	# -------------------------------------------------------
	public function setOption($ps_option, $pm_value) {
		if ($this->isValidOption($ps_option)) {
			$this->opa_options[$ps_option] = $pm_value;

			switch($ps_option) {
				case 'limit':
					
					break;
			}
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	public function getOption($ps_option) {
		return $this->opa_options[$ps_option];
	}
	# -------------------------------------------------------
	public function getAvailableOptions() {
		return array_keys($this->opa_options);
	}
	# -------------------------------------------------------
	public function isValidOption($ps_option) {
		return in_array($ps_option, $this->getAvailableOptions());
	}
	# -------------------------------------------------------
	# Search
	# -------------------------------------------------------
	public function search($pn_subject_tablenum, $ps_search_expression, $pa_filters=array(), $po_rewritten_query=null) {
		$this->_setMode('search');
		$this->opa_filters = $pa_filters;
		
		if (!($t_instance = $this->opo_datamodel->getInstanceByTableNum($pn_subject_tablenum, true))) {
			// TODO: Better error message
			die("Invalid subject table");
		}
		
		if (trim($ps_search_expression) === '((*))') {	
			$vs_table_name = $t_instance->tableName();
			$vs_pk = $t_instance->primaryKey();
			$vs_sql = "
				SELECT {$vs_pk} row_id 
				FROM {$vs_table_name}
			";
			$qr_res = $this->opo_db->query($vs_sql);
		} else {
			
			
			$this->_createTempTable('ca_sql_search_search_final');
			$this->_doQueriesForSqlSearch($po_rewritten_query, $pn_subject_tablenum, 'ca_sql_search_search_final', 0);
				
			// do we need to filter?
			if (is_array($va_filters = $this->getFilters()) && sizeof($va_filters)) {
				foreach($va_filters as $va_filter) {
					if (!$t_instance->hasField($va_filter['field'])) { continue; }
					$vm_val = $this->_filterValueToQueryValue($va_filter);
					
					$vs_sql = "
						DELETE FROM ca_sql_search_search_final
						WHERE
							row_id IN (
								SELECT ".$t_instance->primaryKey()."
								FROM ".$t_instance->tableName()."
								WHERE
									(".$va_filter['field']." ".$va_filter['operator']." ".$vm_val.")
							)
					";
					$qr_res = $this->opo_db->query($vs_sql);
				}
			}
			
			$vs_sql = "
				SELECT DISTINCT row_id
				FROM ca_sql_search_search_final
				ORDER BY
					boost DESC
			";
			$qr_res = $this->opo_db->query($vs_sql);
		
			$this->_dropTempTable('ca_sql_search_search_final');
		}
		
		$va_hits = array();
		while($qr_res->nextRow()) {
			$va_hits[] = array(
				'subject_id' => $pn_subject_tablenum,
				'subject_row_id' => $qr_res->get('row_id'),
			);
		}

		return new WLPlugSearchEngineSqlSearchResult($va_hits, array());
	}
	# -------------------------------------------------------
	private function _filterValueToQueryValue($pa_filter) {
		switch(strtolower($pa_filter['operator'])) {
			case '>':
			case '<':
			case '=':
			case '>=':
			case '<=':
				return (int)$pa_filter['value'];
				break;
			case 'in':
			case 'not in':
				$va_tmp = explode(',', $pa_filter['value']);
				$va_values = array();
				foreach($va_tmp as $vs_tmp) {
					$va_values[] = (int)$vs_tmp;
				}
				return "(".join(",", $va_values).")";
				break;
			default:
				return $pa_filter['value'];
				break;
		}
	}
	# -------------------------------------------------------
	private function _createTempTable($ps_name) {
		$this->opo_db->query("
			CREATE TEMPORARY TABLE {$ps_name} (
				row_id int unsigned not null,
				boost int not null default 1,
				
				unique key i_row_id (row_id),
				key i_boost (boost)
			) engine=memory;
		");
		if ($this->opo_db->numErrors()) {
			return false;
		}
		return true;
	}
	# -------------------------------------------------------
	private function _dropTempTable($ps_name) {
		$this->opo_db->query("
			DROP TABLE {$ps_name};
		");
		if ($this->opo_db->numErrors()) {
			return false;
		}
		return true;
	}
	# -------------------------------------------------------
	private function _getElementIDForAccessPoint($ps_access_point) {
		list($vs_table, $vs_field) = explode('.', $ps_access_point);
		if (!($t_table = $this->opo_datamodel->getInstanceByTableName($vs_table, true))) { return null; }
		$vs_table_num = $t_table->tableNum();
		
		if (is_numeric($vs_field)) {
			$vs_fld_num = $vs_field;
		} else {
			$vs_fld_num = $this->opo_datamodel->getFieldNum($vs_table, $vs_field);	
		}
		if (!$vs_fld_num) {
			$t_element = new ca_metadata_elements();
			if ($t_element->load(array('element_code' => $vs_field))) {
				switch ($t_element->get('datatype')) {
					case 4:	// geocode
					case 8:	// length
					case 9:	// weight
						return array('table_num' => $vs_table_num, 'element_id' => $t_element->getPrimaryKey(), 'datatype' => $t_element->get('datatype'), 'element_info' => $t_element->getFieldValuesArray());
						break;
				
				}
			}
		} else {
			return array('table_num' => $vs_table_num, 'field_num' => $vs_fld_num, 'datatype' => null);
		}

		return null;
	}
	# -------------------------------------------------------
	private function _doQueriesForSqlSearch($po_rewritten_query, $pn_subject_tablenum, $ps_dest_table, $pn_level=0) {		// query is always of type Zend_Search_Lucene_Search_Query_Boolean
		$vn_i = 0;
		$va_old_signs = $po_rewritten_query->getSigns();
		foreach($po_rewritten_query->getSubqueries() as $o_lucene_query_element) {
			if (is_null($va_old_signs)) {	// if array is null then according to Zend Lucene all subqueries should be "are required"... so we AND them
				$vs_op = "AND";
			} else {
				if (is_null($va_old_signs[$vn_i])) {	// is the sign for a particular query is null then OR is (it is "neither required nor prohibited")
					$vs_op = 'OR';
				} else {
					$vs_op = ($va_old_signs[$vn_i]) ? 'AND' : 'NOT';	// true sign indicated "required" (AND) operation, false indicated "prohibited" (NOT) operation
				}
			}
			if ($vn_i == 0) { $vs_op = 'OR'; }
			//$vs_op = 'AND';
			
	
			switch(get_class($o_lucene_query_element)) {
				case 'Zend_Search_Lucene_Search_Query_Boolean':
					$this->_createTempTable('ca_sql_search_temp_'.$pn_level);
					
					$this->_doQueriesForSqlSearch($o_lucene_query_element, $pn_subject_tablenum, 'ca_sql_search_temp_'.$pn_level, ($pn_level+1));
					
					
					// merge with current destination
					switch($vs_op) {
						case 'AND':
							// and
							$this->_createTempTable($ps_dest_table.'_acc');
							
							if ($vn_i == 0) {
								$vs_sql = "
									INSERT IGNORE INTO {$ps_dest_table}
									SELECT DISTINCT row_id, boost
									FROM ca_sql_search_temp_{$pn_level}
								";
								//print "$vs_sql<hr>";
								$qr_res = $this->opo_db->query($vs_sql);
							} else {
								$vs_sql = "
									INSERT IGNORE INTO {$ps_dest_table}_acc
									SELECT mfs.row_id, SUM(mfs.boost)
									FROM {$ps_dest_table} mfs
									INNER JOIN ca_sql_search_temp_{$pn_level} AS ftmp1 ON ftmp1.row_id = mfs.row_id
									GROUP BY mfs.row_id
								";
								//print "$vs_sql<hr>";
								$qr_res = $this->opo_db->query($vs_sql);
								
								$qr_res = $this->opo_db->query("TRUNCATE TABLE {$ps_dest_table}");
								
								$qr_res = $this->opo_db->query("INSERT INTO {$ps_dest_table} SELECT row_id, boost FROM {$ps_dest_table}_acc");
							} 
							$this->_dropTempTable($ps_dest_table.'_acc');
							break;
						case 'NOT':
							
							$vs_sql = "
								DELETE FROM {$ps_dest_table} WHERE row_id IN
								(SELECT row_id FROM ca_sql_search_temp_{$pn_level})
							";
						
							//print "$vs_sql<hr>";
							$qr_res = $this->opo_db->query($vs_sql);
							break;
						default:
						case 'OR':
							// or
							$vs_sql = "
								INSERT IGNORE INTO {$ps_dest_table}
								SELECT row_id, SUM(boost)
								FROM ca_sql_search_temp_{$pn_level}
								GROUP BY row_id
							";
							//print "$vs_sql<hr>";
							$qr_res = $this->opo_db->query($vs_sql);
							break;
					}
					
					$this->_dropTempTable('ca_sql_search_temp_'.$pn_level);
					break;
				case 'Zend_Search_Lucene_Search_Query_Term':
				case 'Zend_Search_Lucene_Search_Query_MultiTerm':
				case 'Zend_Search_Lucene_Search_Query_Phrase':
				case 'Zend_Search_Lucene_Search_Query_Range':
					$va_ft_terms = array();
					$va_ft_like_terms = array();
					$vs_direct_sql_query = null;
					$va_tmp = array();
					$vs_access_point = '';
					$va_raw_terms = array();
					switch(get_class($o_lucene_query_element)) {
						case 'Zend_Search_Lucene_Search_Query_Range':
							$va_lower_term = $o_lucene_query_element->getLowerTerm();
							$va_upper_term = $o_lucene_query_element->getUpperTerm();
							$va_element = $this->_getElementIDForAccessPoint($va_lower_term->field);
							
							switch($va_element['datatype']) {
								case 4:		// geocode
									$t_geocode = new GeocodeAttributeValue();
									$va_parsed_value = $t_geocode->parseValue($va_lower_term->text, $va_element['element_info']);
									$vs_lower_lat = $va_parsed_value['value_decimal1'];
									$vs_lower_long = $va_parsed_value['value_decimal2'];
									
									$va_parsed_value = $t_geocode->parseValue($va_upper_term->text, $va_element['element_info']);
									$vs_upper_lat = $va_parsed_value['value_decimal1'];
									$vs_upper_long = $va_parsed_value['value_decimal2'];
									
									//print "find $vs_lower_lat/$vs_lower_long to $vs_upper_lat/$vs_upper_long<br>";
									$vs_direct_sql_query = "
										SELECT ca.row_id, 1
										FROM ca_attribute_values cav
										INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
										WHERE
											(cav.element_id = ".intval($va_element['element_id']).") AND (ca.table_num = ?)
											AND
											(cav.value_decimal1 BETWEEN ".floatval($vs_lower_lat)." AND ".floatval($vs_upper_lat).")
											AND
											(cav.value_decimal2 BETWEEN ".floatval($vs_lower_long)." AND ".floatval($vs_upper_long).")	
									";
									break;
								case 6:		// currency
									$t_cur = new CurrencyAttributeValue();
									$va_parsed_value = $t_cur->parseValue($va_lower_term->text, $va_element['element_info']);
									$vs_currency = preg_replace('![^A-Z0-9]+!', '', $va_parsed_value['value_longtext1']);
									$vn_lower_val = $va_parsed_value['value_decimal1'];
									
									$va_parsed_value = $t_cur->parseValue($va_upper_term->text, $va_element['element_info']);
									$vn_upper_val = $va_parsed_value['value_decimal1'];
									
									$vs_direct_sql_query = "
										SELECT ca.row_id, 1
										FROM ca_attribute_values cav
										INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
										WHERE
											(cav.element_id = ".intval($va_element['element_id']).") AND (ca.table_num = ?)
											AND
											(cav.value_decimal1 BETWEEN ".floatval($vn_lower_val)." AND ".floatval($vn_upper_val).")
											AND
											(cav.value_longtext1 = '".$vs_currency."')
											
									";
									break;
								case 10:	// timecode
									$t_timecode = new TimecodeAttributeValue();
									$va_parsed_value = $t_timecode->parseValue($va_lower_term->text, $va_element['element_info']);
									$vn_lower_val = $va_parsed_value['value_decimal1'];
									
									$va_parsed_value = $t_timecode->parseValue($va_upper_term->text, $va_element['element_info']);
									$vn_upper_val = $va_parsed_value['value_decimal1'];
									break;
								case 8: 	// length
									$t_len = new LengthAttributeValue();
									$va_parsed_value = $t_len->parseValue($va_lower_term->text, $va_element['element_info']);
									$vn_lower_val = $va_parsed_value['value_decimal1'];
									
									$va_parsed_value = $t_len->parseValue($va_upper_term->text, $va_element['element_info']);
									$vn_upper_val = $va_parsed_value['value_decimal1'];
									break;
								case 9: 	// weight
									$t_weight = new WeightAttributeValue();
									$va_parsed_value = $t_weight->parseValue($va_lower_term->text, $va_element['element_info']);
									$vn_lower_val = $va_parsed_value['value_decimal1'];
									
									$va_parsed_value = $t_weight->parseValue($va_upper_term->text, $va_element['element_info']);
									$vn_upper_val = $va_parsed_value['value_decimal1'];
									break;
								case 11: 	// integer
									$vn_lower_val = intval($va_lower_term->text);
									$vn_upper_val = intval($va_upper_term->text);
									
									$vs_direct_sql_query = "
										SELECT ca.row_id, 1
										FROM ca_attribute_values cav
										INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
										WHERE
											(cav.element_id = ".intval($va_element['element_id']).") AND (ca.table_num = ?)
											AND
											(cav.value_integer1 BETWEEN ".floatval($vn_lower_val)." AND ".floatval($vn_upper_val).")
											
									";
									break;
								case 12:	// decimal
									$vn_lower_val = floatval($va_lower_term->text);
									$vn_upper_val = floatval($va_upper_term->text);
									break;
							}
							
							if (!$vs_direct_sql_query) {
								$vs_direct_sql_query = "
									SELECT ca.row_id, 1
									FROM ca_attribute_values cav
									INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
									WHERE
										(cav.element_id = ".intval($va_element['element_id']).") AND (ca.table_num = ?)
										AND
										(cav.value_decimal1 BETWEEN ".floatval($vn_lower_val)." AND ".floatval($vn_upper_val).")
										
								";
							}
							break;
						case 'Zend_Search_Lucene_Search_Query_Phrase':
						 	$va_words = array();
						 	foreach($o_lucene_query_element->getQueryTerms() as $o_term) {
								if (!$vs_access_point && ($vs_field = $o_term->field)) { $vs_access_point = $vs_field; }
								$vs_term = $o_term->text;
								
								$va_raw_terms[] = $va_words[] = ''.$vs_term;
							}
							if (!sizeof($va_words)) { continue(3); }
							
							$va_ft_terms[] = '+"'.addslashes(join(' ', $va_words)).'"';
							break;
						case 'Zend_Search_Lucene_Search_Query_MultiTerm':
							$va_ft_like_term_list = array();
							
							foreach($o_lucene_query_element->getTerms() as $o_term) {
								$vs_term = method_exists($o_term, "getTerm") ? $o_term->getTerm()->text : $o_term->text;
								$va_raw_terms[] = $vs_term;
								if (!$vs_access_point && ($vs_field = method_exists($o_term, "getTerm") ? $o_term->getTerm()->field : $o_term->field)) { $vs_access_point = $vs_field; }
								
								$vs_stripped_term = preg_replace('!\*+$!u', '', $vs_term);
								if ((unicode_strlen($vs_stripped_term) < $vn_ft_min_word_length) || in_array($vs_stripped_term, WLPlugSearchEngineSqlSearch::$_search_SqlSearch_stopwords)){
									$vb_had_wildcard = ($vs_stripped_term !== $vs_term) ? true : false;
									
									$va_ft_like_term_list[] = $vs_stripped_term.($vb_had_wildcard ? '%' : '');;

								} else {
									$va_ft_terms[] = '+'.$vs_term;
								}
							}
							if (!sizeof($va_ft_like_term_list) && !sizeof($va_ft_terms)) { continue(3); }
							if (sizeof($va_ft_like_term_list)) {
								$va_ft_like_terms[] = ' '.join(' ', $va_ft_like_term_list).' ';	// space is added to ensure that we don't pick up items with text that merely *starts* with the last term in the multi-term
							}
							break;
						default:
							$vs_access_point = $o_lucene_query_element->getTerm()->field;
							$va_raw_terms[] = $vs_term = $o_lucene_query_element->getTerm()->text;
							if (get_class($o_lucene_query_element) != 'Zend_Search_Lucene_Search_Query_MultiTerm') {
								$vs_stripped_term = preg_replace('!\*+$!u', '', $vs_term);
								if ((unicode_strlen($vs_stripped_term) < $vn_ft_min_word_length)) {									

									// do stemming
									$vb_had_wildcard = ($vs_stripped_term !== $vs_term) ? true : false;
									
									if ($this->opb_do_stemming && !preg_match('!y$!u', $vs_stripped_term) && preg_match('![A-Za-z]$!u', $vs_stripped_term)) {	// don't stem things ending in 'y' as that can cause problems (eg "Bowery" becomes "Boweri")
										$vs_stripped_term = $this->opo_stemmer->stem($vs_stripped_term).'%';

									}
									$va_ft_like_terms[] = $vs_stripped_term.($vb_had_wildcard ? '%' : '');
								} else {		
									$vb_add_quotes = false;
									if (preg_match('![^A-Za-z0-9\-]+!u', $vs_term)) {
										$vb_add_quotes = true;
									}
									// do stemming
									if ($this->opb_do_stemming) {
										$vs_to_stem = preg_replace('!\*$!u', '', $vs_term);
										if (!preg_match('!y$!u', $vs_to_stem) && !preg_match('![^A-Za-z0-9\-]+!u', $vs_to_stem)) {	// don't stem things ending in 'y' as that can cause problems (eg "Bowery" becomes "Boweri")
											$vs_term = '"'.$vs_to_stem.'" ';
											if ($vb_add_quotes) {
												$vs_term .= '"'.$this->opo_stemmer->stem($vs_to_stem).'*"';
											} else {
												$vs_term .= $this->opo_stemmer->stem($vs_to_stem).'*';
											}
										} else {
											if ($vb_add_quotes) {
												$vs_term = '"'.$vs_term.'"';
											}
										}
									} else {
										if ($vb_add_quotes) {
											$vs_term = '"'.$vs_term.'"';
										}
									}
									
									// TODO: properly escape
									$va_ft_terms[] = "'".addslashes($vs_term)."'";
								}
							}
							break;
					}
					
					
					
					$vs_fld_num = $vs_table_num = $t_table = null;
					$vb_ft_bit_optimization = false;
					if ($vs_access_point) {
						list($vs_table, $vs_field) = explode('.', $vs_access_point);
						if ($vs_table && $vs_field) {
							$t_table = $this->opo_datamodel->getInstanceByTableName($vs_table, true);
							$vs_table_num = $t_table->tableNum();
							if ($t_table) {
								if (is_numeric($vs_field)) {
									$vs_fld_num = $vs_field;
								} else {
									$vs_fld_num = $this->opo_datamodel->getFieldNum($vs_table, $vs_field);
									
									if (!$vs_fld_num) {
										$t_element = new ca_metadata_elements();
										if ($t_element->load(array('element_code' => $vs_field))) {
											$vs_fld_num = $t_element->getPrimaryKey();
											
											//
											// For certain types of attributes we can directly query the
											// attributes in the database rather than using the full text index
											// This allows us to do "intelligent" querying... for example on date ranges
											// parsed from natural language input and for length dimensions using unit conversion
											//
											switch($t_element->get('datatype')) {
												case 2:		// dates		
													$vb_all_numbers = true;
													foreach($va_raw_terms as $vs_term) {
														if (!is_numeric($vs_term)) {
															$vb_all_numbers = false;
															break;
														}
													}
													$vs_raw_term = join(' ', $va_raw_terms);
													if ($this->opo_tep->parse($vs_raw_term)) {
														$va_dates = $this->opo_tep->getHistoricTimestamps();
														$vs_direct_sql_query = "
															SELECT ca.row_id, 1
															FROM ca_attribute_values cav
															INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
															WHERE
																(cav.element_id = ".intval($vs_fld_num).") AND (ca.table_num = ?)
																AND
																(
																	(cav.value_decimal1 BETWEEN ".floatval($va_dates['start'])." AND ".floatval($va_dates['end']).")
																	OR
																	(cav.value_decimal2 BETWEEN ".floatval($va_dates['start'])." AND ".floatval($va_dates['end']).")
																	OR
																	(cav.value_decimal1 <= ".floatval($va_dates['start'])." AND cav.value_decimal2 >= ".floatval($va_dates['end']).")	
																)
																
														";
													}
													break;
												case 4:		// geocode
													$t_geocode = new GeocodeAttributeValue();
													
													// If it looks like a lat/long pair that has been tokenized by Lucene
													// into oblivion rehydrate it here.
													//
													// TODO: add checks here to make sure that not only does it *look* like
													// it could be a lat/long but it *is* a lat/long
													if((sizeof($va_raw_terms) >= 2) && (sizeof($va_raw_terms) <= 4)) {
														$va_raw_terms = array($va_raw_terms[0].'.'.$va_raw_terms[1], $va_raw_terms[2].'.'.$va_raw_terms[3]);
													}
													
													$va_parsed_value = $t_geocode->parseValue(join(' ', $va_raw_terms), $t_element->getFieldValuesArray());
													$vn_lat = $va_parsed_value['value_decimal1'];
													$vn_long = $va_parsed_value['value_decimal2'];
													
													// TODO: allow specification of distance from point to include in search
													// Right now it is just hard-coded to 0.01 for testing... this doesn't
													// actually make sense as a real value
													$vn_lat_lower = $vn_lat - 0.01;
													$vn_lat_higher = $vn_lat + 0.01;
													$vn_long_lower = $vn_long - 0.01;
													$vn_long_higher = $vn_long + 0.01;
													
													$vs_direct_sql_query = "
														SELECT ca.row_id, 1
														FROM ca_attribute_values cav
														INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
														WHERE
															(cav.element_id = ".intval($vs_fld_num).") AND (ca.table_num = ?)
															AND
															(cav.value_decimal1 BETWEEN {$vn_lat_lower} AND {$vn_lat_higher})
															AND
															(cav.value_decimal2 BETWEEN {$vn_long_lower} AND {$vn_long_higher})
															
													";
													break;
												case 6:		// currency
													$t_cur = new CurrencyAttributeValue();
													$va_parsed_value = $t_cur->parseValue(join(' ', $va_raw_terms), $t_element->getFieldValuesArray());
													$vn_amount = $va_parsed_value['value_decimal1'];
													$vs_currency = preg_replace('![^A-Z0-9]+!', '', $va_parsed_value['value_longtext1']);
													
													$vs_direct_sql_query = "
														SELECT ca.row_id, 1
														FROM ca_attribute_values cav
														INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
														WHERE
															(cav.element_id = ".intval($vs_fld_num).") AND (ca.table_num = ?)
															AND
															(cav.value_decimal1 = ".floatval($vn_amount).")
															AND
															(cav.value_longtext1 = '".$this->opo_db->escape($vs_currency)."')
															
													";
													break;
												case 8:		// length
													$t_len = new LengthAttributeValue();
													$va_parsed_value = $t_len->parseValue(array_shift($va_raw_terms), $t_element->getFieldValuesArray());
													$vn_len = $va_parsed_value['value_decimal1'];	// this is always in meters so we can compare this value to the one in the database
													
													$vs_direct_sql_query = "
														SELECT ca.row_id, 1
														FROM ca_attribute_values cav
														INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
														WHERE
															(cav.element_id = ".intval($vs_fld_num).") AND (ca.table_num = ?)
															AND
															(cav.value_decimal1 = ".floatval($vn_len).")
															
													";
													break;
												case 9:		// weight
													$t_weight = new WeightAttributeValue();
													$va_parsed_value = $t_weight->parseValue(array_shift($va_raw_terms), $t_element->getFieldValuesArray());
													$vn_weight = $va_parsed_value['value_decimal1'];	// this is always in kilograms so we can compare this value to the one in the database
													
													$vs_direct_sql_query = "
														SELECT ca.row_id, 1
														FROM ca_attribute_values cav
														INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
														WHERE
															(cav.element_id = ".intval($vs_fld_num).") AND (ca.table_num = ?)
															AND
															(cav.value_decimal1 = ".floatval($vn_weight).")
															
													";
													break;
												case 10:	// timecode
													$t_timecode = new TimecodeAttributeValue();
													$va_parsed_value = $t_timecode->parseValue(join(' ', $va_raw_terms), $t_element->getFieldValuesArray());
													$vn_timecode = $va_parsed_value['value_decimal1'];
													
													$vs_direct_sql_query = "
														SELECT ca.row_id, 1
														FROM ca_attribute_values cav
														INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
														WHERE
															(cav.element_id = ".intval($vs_fld_num).") AND (ca.table_num = ?)
															AND
															(cav.value_decimal1 = ".floatval($vn_timecode).")
															
													";
													break;
												case 11: 	// integer
													$vs_direct_sql_query = "
														SELECT ca.row_id, 1
														FROM ca_attribute_values cav
														INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
														WHERE
															(cav.element_id = ".intval($vs_fld_num).") AND (ca.table_num = ?)
															AND
															(cav.value_integer1 = ".intval(array_shift($va_raw_terms)).")
															
													";
													break;
												case 12:	// decimal
													$vs_direct_sql_query = "
														SELECT ca.row_id, 1
														FROM ca_attribute_values cav
														INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
														WHERE
															(cav.element_id = ".intval($vs_fld_num).") AND (ca.table_num = ?)
															AND
															(cav.value_decimal1 = ".floatval(array_shift($va_raw_terms)).")
															
													";
													break;
												default:
													$vs_table_num = 4; // attributes
													break;
											}
										}
									}
								}
								if ($t_table->getFieldInfo($t_table->fieldName($vs_fld_num), 'FIELD_TYPE') == FT_BIT) {
									$vb_ft_bit_optimization = true;
								}
							}
						}
					}
					
					//
					// If we're querying on the fulltext index then we need to construct
					// the query here... if we already have a direct SQL query to run then we can skip this
					//
					if (!$vs_direct_sql_query) {
						$va_sql_where = array();
						if (sizeof($va_ft_terms)) {
							if (($t_table) && (strlen($vs_fld_num) > 0)) {
								$va_sql_where[] = "((swi.field_table_num = ".intval($vs_table_num).") AND (swi.field_num = ".intval($vs_fld_num).") AND (sw.word IN (".join(',', $va_ft_terms).")))";
							} else {
								$va_sql_where[] =  "(sw.word IN (".join(',', $va_ft_terms)."))";
							}
						}
						
						if (sizeof($va_ft_like_terms)) {
							$va_tmp = array();
							foreach($va_ft_like_terms as $vs_term) {
								if ($vb_ft_bit_optimization) {
									$va_tmp[] = '(sw.word = \' '.addslashes(trim($vs_term)).' \')';
								} else {
									$va_tmp[] = '(sw.word LIKE \' '.addslashes(trim($vs_term)).' %\')';
								}
							}
							if (($t_table) && (strlen($vs_fld_num) > 0)) {
								$va_sql_where[] = "((swi.field_table_num = ".intval($vs_table_num).") AND (swi.field_num = ".intval($vs_fld_num).") AND (".join(' AND ', $va_tmp)."))";
							} else {
								$va_sql_where[] =  "(".join(' AND ', $va_tmp).")";
							}
						}
						
						$vs_sql_where = join(' AND ', $va_sql_where);
					}
					
					//print "OP=$vs_op<br>";
					if ($vn_i == 0) {
						$vs_sql = ($vs_direct_sql_query) ? "INSERT IGNORE INTO {$ps_dest_table} {$vs_direct_sql_query}" : "
							INSERT IGNORE INTO {$ps_dest_table}
							SELECT swi.row_id, SUM(swi.boost)
							FROM ca_sql_search_word_index swi
							INNER JOIN ca_sql_search_words AS sw ON sw.word_id = swi.word_id
							WHERE
								{$vs_sql_where}
								AND
								swi.table_num = ?
							GROUP BY
								swi.row_id
						";

						if ($this->debug) { print 'FIRST: '.$vs_sql." [$pn_subject_tablenum]<hr>\n"; }
						$qr_res = $this->opo_db->query($vs_sql, $pn_subject_tablenum);
					} else {
						switch($vs_op) {
							case 'AND':
								$this->_createTempTable($ps_dest_table.'_acc');
								$vs_sql = ($vs_direct_sql_query) ? "INSERT IGNORE INTO {$ps_dest_table}_acc {$vs_direct_sql_query}" : "
									INSERT IGNORE INTO {$ps_dest_table}_acc
									SELECT swi.row_id, SUM(swi.boost)
									FROM ca_sql_search_word_index swi
									INNER JOIN ca_sql_search_words AS sw ON sw.word_id = swi.word_id
									INNER JOIN {$ps_dest_table} AS ftmp1 ON ftmp1.row_id = swi.row_id
									WHERE
										{$vs_sql_where}
										AND
										swi.table_num = ?
									GROUP BY
										swi.row_id
								";
	
								if ($this->debug) { print 'AND:'.$vs_sql."<hr>\n"; }
								$qr_res = $this->opo_db->query($vs_sql, $pn_subject_tablenum);
								$qr_res = $this->opo_db->query("TRUNCATE TABLE {$ps_dest_table}");
								$qr_res = $this->opo_db->query("INSERT INTO {$ps_dest_table} SELECT row_id, boost FROM {$ps_dest_table}_acc");
								
								//$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_sql_search_temp_2");
								
								$this->_dropTempTable($ps_dest_table.'_acc');
								break;
							case 'NOT':
								$vs_sql = "
									DELETE FROM {$ps_dest_table} WHERE row_id IN
									(SELECT swi.row_id
									FROM ca_sql_search_word_index swi
									INNER JOIN ca_sql_search_words AS sw ON sw.word_id = swi.word_id
									WHERE
										{$vs_sql_where}
										AND
										swi.table_num = ?
									GROUP BY
										swi.row_id)
								";
							
								//print "$vs_sql<hr>";
								$qr_res = $this->opo_db->query($vs_sql, $pn_subject_tablenum);
								break;
							default:
							case 'OR':
								$vs_sql = ($vs_direct_sql_query) ? "INSERT IGNORE INTO {$ps_dest_table} {$vs_direct_sql_query}" : "
									INSERT IGNORE INTO {$ps_dest_table}
									SELECT swi.row_id, SUM(swi.boost)
									FROM ca_sql_search_word_index swi
									INNER JOIN ca_sql_search_words AS sw ON sw.word_id = swi.word_id
									WHERE
										{$vs_sql_where}
										AND
										swi.table_num = ?
									GROUP BY
										swi.row_id
								";
	
								if ($this->debug) { print 'OR'.$vs_sql."<hr>\n"; }
								$qr_res = $this->opo_db->query($vs_sql, $pn_subject_tablenum);
								break;
						}
					}
							
					break;
				default:
					//print get_class($o_lucene_query_element);
					break;
			}
			
			$vn_i++;
		}				
	}
	# -------------------------------------------------------
	# Indexing
	# -------------------------------------------------------
	public function startRowIndexing($pn_subject_tablenum, $pn_subject_row_id) {
		$this->_setMode('indexing');
		
		if ($this->debug) { print "[SqlSearchDebug] startRowIndexing: $pn_subject_tablenum/$pn_subject_row_id<br>\n"; }

		$this->opn_indexing_subject_tablenum = $pn_subject_tablenum;
		$this->opn_indexing_subject_row_id = $pn_subject_row_id;

		$this->opa_doc_content_buffer = array();
	}
	# -------------------------------------------------------
	public function indexField($pn_content_tablenum, $ps_content_fieldname, $pn_content_row_id, $pm_content, $pa_options) {
		if (is_array($pm_content)) {
			$pm_content = serialize($pm_content);
		}
		
		$vn_boost = 1;
		if (isset($pa_options['BOOST'])) {
			$vn_boost = intval($pa_options['BOOST']);
		}
		
		if ($this->debug) { print "[SqlSearchDebug] indexField: $pn_content_tablenum/$ps_content_fieldname [$pn_content_row_id] =&gt; $pm_content<br>\n"; }
		if (!isset($pa_options['DONT_TOKENIZE'])) { $pa_options['DONT_TOKENIZE'] = false; }
		if (in_array('DONT_TOKENIZE', $pa_options)) { $pa_options['DONT_TOKENIZE'] = true; }
		
		if (!isset($pa_options['STORE'])) { $pa_options['STORE'] = false; }
		if (in_array('STORE', $pa_options)) { $pa_options['STORE'] = true; }
		
		if (!isset($pa_options['datatype'])) { $pa_options['datatype'] = null; }
		
		$vb_tokenize = $pa_options['DONT_TOKENIZE'] ? false : true;
		$vb_store = $pa_options['STORE'] ? true : false;
		
		if ($pn_content_tablenum == 4) { 	// is attribute (4=ca_attributes)
			preg_match('!([\d]+)$!u', $ps_content_fieldname, $va_matches);
			$vn_field_num = intval($va_matches[1]);
		} else {
			switch($ps_content_fieldname) {
				case '_hier_ancestors':
					$vn_field_num = 255;
					break;
				case '_count':
					$vn_field_num = 254;
					break;
				default:
					// is regular field in some table
					$vn_field_num = $this->opo_datamodel->getFieldNum($this->opo_datamodel->getTableName($pn_content_tablenum), $ps_content_fieldname);
					break;
			}
		}
		
		// Tokenize string
		if ($vb_tokenize) {
			$va_words = preg_split($this->ops_search_mysql_fulltext_tokenize_preg."u", preg_replace('!["\']+!u', '', $pm_content));
		} else {
			$va_words = array($pm_content);
		}
		foreach($va_words as $vs_word) {
			$this->opa_doc_content_buffer[$pn_content_tablenum.'/'.$vn_field_num.'/'.$pn_content_row_id.'/'.$vn_boost][] = $vs_word;
		}
	}
	# ------------------------------------------------
	public function commitRowIndexing() {
		// add fields to doc
		$va_row_sql = array();
		$vn_segment = 0;
		foreach($this->opa_doc_content_buffer as $vs_key => $va_content) {
			$vn_seq = 0;
			foreach($va_content as $vs_word) {
				$va_tmp = explode('/', $vs_key);
			
				$vn_word_id = $this->getWordID($vs_word);
			
				if ($this->can('incremental_reindexing')) {
					$this->removeRowIndexing($this->opn_indexing_subject_tablenum, $this->opn_indexing_subject_row_id, intval($va_tmp[0]), intval($va_tmp[1]));
				}
				
				$va_row_sql[$vn_segment][] = '('.intval($this->opn_indexing_subject_tablenum).','.intval($this->opn_indexing_subject_row_id).','.intval($va_tmp[0]).','.intval($va_tmp[1]).','.intval($va_tmp[2]).','.(int)$vn_word_id.','.$vn_seq.','.(int)$va_tmp[3].')';	
				$vn_seq++;
				
				if (sizeof($va_row_sql[$vn_segment]) > 1024) { $vn_segment++; }
			}
		}
		//table_num, row_id, field_table_num, field_num, field_row_id, word_id, seq, boost
		// remove any existing indexing for this row
		if (!$this->can('incremental_reindexing')) {
			$this->removeRowIndexing($this->opn_indexing_subject_tablenum, $this->opn_indexing_subject_row_id);
		}
		
		// add new indexing
		if ($this->debug) { print "[SqlSearch] ADD DOC [".$this->opn_indexing_subject_tablenum."/".$this->opn_indexing_subject_row_id."]<br>\n"; }
		
		if (sizeof($va_row_sql)) {
			foreach($va_row_sql as $vn_segment => $va_row_sql_list) {
				$vs_sql = $this->ops_insert_word_index_sql."\n".join(",", $va_row_sql_list);
				$this->opo_db->query($vs_sql);
			}
			if ($this->debug) { print "[SqlSearchDebug] Commit row indexing<br>\n"; }
		}
	
		// clean up
		$this->opn_indexing_subject_tablenum = null;
		$this->opn_indexing_subject_row_id = null;
		$this->opa_doc_content_buffer = null;
	}
	# ------------------------------------------------
	public function getWordID($ps_word) {
		$ps_word = trim($ps_word);
		if (WLPlugSearchEngineSqlSearch::$s_word_cache[$ps_word]) { return WLPlugSearchEngineSqlSearch::$s_word_cache[$ps_word]; } 
		if ($qr_res = $this->opqr_lookup_word->execute($ps_word)) {
			if ($qr_res->nextRow()) {
				return WLPlugSearchEngineSqlSearch::$s_word_cache[$ps_word] = $qr_res->get('word_id');
			}
		}
		
		// insert word
		$this->opqr_insert_word->execute($ps_word, soundex($ps_word), metaphone($ps_word), $this->opo_stemmer->stem($ps_word));
		return WLPlugSearchEngineSqlSearch::$s_word_cache[$ps_word] = $this->opo_db->getLastInsertID();
	}
	# ------------------------------------------------
	public function removeRowIndexing($pn_subject_tablenum, $pn_subject_row_id, $pn_field_table_num=null, $pn_field_num=null, $pn_field_row_id=null) {
	
		//print "[SqlSearchDebug] removeRowIndexing: $pn_subject_tablenum/$pn_subject_row_id<br>\n"; 
		
		// remove dependent row indexing
		if ($pn_field_table_num && $pn_field_num) {
			return $this->opqr_delete_word_index_with_field_num->execute($pn_subject_tablenum, $pn_subject_row_id, $pn_field_table_num, $pn_field_num);
		} else {
			if ($pn_field_table_num && $pn_field_row_id) {
				return $this->opqr_delete_word_index_with_field_row_id->execute($pn_subject_tablenum, $pn_subject_row_id, $pn_field_table_num, $pn_field_row_id);
			} else {
				return $this->opqr_delete_word_index->execute($pn_subject_tablenum, $pn_subject_row_id);
			}
		}
	}
	# ------------------------------------------------
	public function updateIndexingInPlace($pn_subject_tablenum, $pa_subject_row_ids, $pn_content_tablenum, $ps_content_fieldnum, $pn_content_row_id, $pm_content, $pa_options=null) {
		// Find existing indexing for this subject and content 		
		$qr_index = $this->opo_db->query("
			SELECT row_id
			FROM ca_mysql_fulltext_search
			WHERE
				(table_num = ?) AND (row_id IN (?)) AND (field_table_num = ?) AND (field_num = ?) AND (field_row_id = ?)
		", intval($pn_subject_tablenum), join(',', $pa_subject_row_ids), intval($pn_content_tablenum), intval($ps_content_fieldnum), intval($pn_content_row_id));
		
		// if indexing already exists then mark in-place update
		$va_subject_rows_ids_to_update = array();
		while($qr_index->nextRow()) {
			$va_subject_rows_ids_to_update[$qr_index->get('row_id')] = true;
		}
		
		$va_words = preg_split("#[\.\,\!\?\_]#u", $pm_content);
		
		$vn_boost = 1;
		if (isset($pa_options['BOOST'])) {
			$vn_boost = intval($pa_options['BOOST']);
		}
		
		// mark all content for which indexing does not already exist for insert
		$va_row_insert_sql = array();
		foreach($pa_subject_row_ids as $vn_row_id) {
			if (!isset($va_subject_rows_ids_to_update[$vn_row_id]) || !$va_subject_rows_ids_to_update[$vn_row_id]) {
				foreach($va_words as $vs_word) {
					$va_row_insert_sql[] = "(".intval($pn_subject_tablenum).",".intval($vn_row_id).",".intval($pn_content_tablenum).",".intval($ps_content_fieldnum).",".intval($pn_content_row_id).",' ".$this->opo_db->escape($vs_word)." ', ".intval($vn_boost).")";
				}
			}
		}
		
		// do insert
		if (sizeof($va_row_insert_sql)) {
			$vs_sql = $this->ops_insert_sql."\n".join(",", $va_row_insert_sql);
			$this->opo_db->query($vs_sql);
			if ($this->debug) { print "[SqlSearchDebug] Commit row indexing<br>\n"; }
		}		
		
		// do update (in a single query... should be pretty efficient)
		$this->opqr_update_index_in_place->execute(' '.$pm_content.' ', intval($pn_subject_tablenum), join(',', array_keys($va_subject_rows_ids_to_update)), intval($pn_content_tablenum), intval($ps_content_fieldnum), intval($pn_content_row_id));
	}
	# -------------------------------------------------
	/**
	 * Not supported in this engine - does nothing
	 */
	public function optimizeIndex($pn_tablenum) {
		// noop	
	}
	# -------------------------------------------------
	public function addFilter($ps_access_point, $ps_operator, $pm_value) {
		$this->opa_filters[] = array(
			'access_point' => $ps_access_point, 
			'operator' => $ps_operator, 
			'value ' => $pm_value
		);
	}
	# -------------------------------------------------
	public function getFilters() {
		return $this->opa_filters;
	}
	# --------------------------------------------------
	public function clearFilters() {
		$this->opa_filters = array();
	}
	# --------------------------------------------------
	public function engineName() {
		return 'SqlSearch';
	}
	# --------------------------------------------------
	/**
	 * Performs the quickest possible search on the index for the specfied table_num in $pn_table_num
	 * using the text in $ps_search. Unlike the search() method, quickSearch doesn't support
	 * any sort of search syntax. You give it some text and you get a collection of (hopefully) relevant results back quickly. 
	 * quickSearch() is intended for autocompleting search suggestion UI's and the like, where performance is critical
	 * and the ability to control search parameters is not required.
	 *
	 * @param $pn_table_num - The table index to search on
	 * @param $ps_search - The text to search on
	 * @param $pa_options - an optional associative array specifying search options. Supported options are: 'limit' (the maximum number of results to return)
	 *
	 * @return Array - an array of results is returned keyed by primary key id. The array values boolean true. This is done to ensure no duplicate row_ids
	 * 
	 */
	public function quickSearch($pn_table_num, $ps_search, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$vs_limit_sql = '';
		if (isset($pa_options['limit']) && ($pa_options['limit'] > 0)) { 
			$vs_limit_sql = 'LIMIT '.$pa_options['limit'];
		}
		
		$va_tmp = preg_split('![ ]+!u', $ps_search);
		for($vn_i=0; $vn_i < sizeof($va_tmp); $vn_i++) {
			$va_tmp[$vn_i] = '+'.$va_tmp[$vn_i]."*";
		}
		
		$qr_res = $this->opo_db->query("
			SELECT *
			FROM ca_mysql_fulltext_search
			WHERE
				(MATCH (fieldtext) AGAINST ('".addslashes(join(' ', $va_tmp))."' IN BOOLEAN MODE))
				AND
				table_num = ?
			ORDER BY boost DESC
			{$vs_limit_sql}
		", $pn_table_num);
		
		$va_hits = array();
		while($qr_res->nextRow()) {
			$va_hits[$qr_res->get('row_id')] = true;
		}
		
		return $va_hits;
	}
	# --------------------------------------------------
}
?>
