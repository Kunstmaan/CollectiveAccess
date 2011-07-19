<?php
/* ----------------------------------------------------------------------
 * app/lib/core/Search/SearchEngine.php : Base class for searches
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2011 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */

# ----------------------------------------------------------------------
# --- Import classes
# ----------------------------------------------------------------------
require_once(__CA_LIB_DIR__."/core/Search/SearchBase.php");
require_once(__CA_LIB_DIR__."/core/Plugins/SearchEngine/Lucene.php");
require_once(__CA_LIB_DIR__."/core/Plugins/SearchEngine/CachedResult.php");
require_once(__CA_LIB_DIR__."/core/Search/SearchIndexer.php");
require_once(__CA_LIB_DIR__."/core/Search/SearchResult.php");
require_once(__CA_LIB_DIR__."/core/Search/SearchCache.php");
require_once(__CA_LIB_DIR__."/core/Logging/Searchlog.php");
require_once(__CA_LIB_DIR__."/core/Utils/Timer.php");
require_once(__CA_MODELS_DIR__.'/ca_lists.php');

require_once(__CA_LIB_DIR__."/core/Search/Common/Parsers/LuceneSyntaxParser.php");
require_once(__CA_LIB_DIR__."/core/Zend/Search/Lucene/Search/Query.php");
require_once(__CA_LIB_DIR__."/core/Zend/Search/Lucene/Search/Query/Boolean.php");
require_once(__CA_LIB_DIR__."/core/Zend/Search/Lucene/Search/Query/Term.php");

# ----------------------------------------------------------------------
class SearchEngine extends SearchBase {

	private $opn_tablenum;
	private $opa_tables;
	// ----
	
	private $opa_options;
	private $opa_result_filters;
	
	/**
	 * @var subject type_id to limit browsing to (eg. only search ca_objects with type_id = 10)
	 */
	private $opa_search_type_ids = null;	
	
	# ------------------------------------------------------------------
	public function __construct($opo_db=null, $ps_tablename=null) {
		parent::__construct($opo_db);
		if ($ps_tablename != null) { $this->ops_tablename = $ps_tablename; }
		
		$this->opa_options = array();
		$this->opa_result_filters = array();
		
		$this->opn_tablenum = $this->opo_datamodel->getTableNum($this->ops_tablename);
		
		$this->opa_tables = array();	
	}
	# ------------------------------------------------------------------
	public function setOption($ps_option, $pm_value) {
		return $this->opo_engine->setOption($ps_option, $pm_value);
	}
	# ------------------------------------------------------------------
	public function getOption($ps_option) {
		return $this->opo_engine->getOption($ps_option);
	}
	# ------------------------------------------------------------------
	public function getAvailableOptions() {
		return $this->opo_engine->getAvailableOptions();
	}
	# ------------------------------------------------------------------
	public function isValidOption($ps_option) {
		return $this->opo_engine->isValidOption($ps_option);
	}
	# ------------------------------------------------------------------
	# Search
	# ------------------------------------------------------------------
	/**
	 * Performs a search by calling the search() method on the underlying search engine plugin
	 * Information about all searches is logged to ca_search_log
	 *
	 * @param string $ps_search The search to perform; engine takes Lucene syntax query
	 * @param SearchResult $po_result  A newly instantiated sub-class of SearchResult to place search results into and return. If this is not set, then a generic SearchResults object will be returned.
	 * @param array $pa_options Optional array of options for the search. Options include
	 *:
	 *		sort - field or attribute to sort on in <table name>.<field or attribute name> format (eg. ca_objects.idno); default is to sort on relevance (aka. sort='_natural')
	 *		sort_direction - direction to sort results by, either 'asc' for ascending order or 'desc' for descending order; default is 'asc'
	 *		no_cache - if true, search is performed regardless of whether results for the search are already cached; default is false
	 *		limit - if set then search results will be limited to the quantity specified. If not set then all results are returned.
	 *		form_id - optional form identifier string to record in log for search
	 *		log_details - optional form description to record in log for search
	 *		search_source - optional source indicator text to record in log for search
	 *		checkAccess - optional array of access values to filter results on
	 *
	 * @return SearchResult Results packages in a SearchResult object, or sub-class of SearchResult if an instance was passed in $po_result
	 */
	public function &search($ps_search, $po_result=null, $pa_options=null) {
		$t = new Timer();
		if (!is_array($pa_options)) { $pa_options = array(); }
		$vn_limit = (isset($pa_options['limit']) && ($pa_options['limit'] > 0)) ? (int)$pa_options['limit'] : null;
		
		//print "QUERY=$ps_search<br>";
		//
		// Note that this is *not* misplaced code that should be in the Lucene plugin!
		//
		// We are using the Lucene syntax as our query syntax regardless the of back-end search engine.
		// The Lucene calls below just parse the query and then rewrite access points as-needed; the result
		// is a Lucene-compliant query ready-to-roll that is passed to the engine plugin. Of course, the Lucene
		// plugin just uses the string as-is... other plugins my choose to parse it however they wish to.
		//
		
		
		$vb_no_cache = isset($pa_options['no_cache']) ? $pa_options['no_cache'] : false;
		unset($pa_options['no_cache']);
		
		$t_table = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
		$vs_pk = $t_table->primaryKey();
		
		$o_cache = new SearchCache();
		if (
			(!$vb_no_cache && ($o_cache->load($ps_search, $this->opn_tablenum, $pa_options)))
		) {
			$va_hits = $o_cache->getResults();
			if (isset($pa_options['sort']) && $pa_options['sort'] && ($pa_options['sort'] != '_natural')) {
				$va_hits = $this->sortHits($va_hits, $pa_options['sort'], (isset($pa_options['sort_direction']) ? $pa_options['sort_direction'] : null));
			}
			$o_res = new WLPlugSearchEngineCachedResult(array_keys($va_hits), array(), $vs_pk);
		} else {
			$vs_char_set = $this->opo_app_config->get('character_set');
	
			$o_query_parser = new LuceneSyntaxParser();
			$o_query_parser->setEncoding($vs_char_set);
			$o_query_parser->setDefaultOperator(LuceneSyntaxParser::B_AND);
	
			$ps_search = preg_replace('![\']+!', '', $ps_search);
			try {
				$o_parsed_query = $o_query_parser->parse($ps_search, $vs_char_set);
			} catch (Exception $e) {
				$o_query_parser->parse('', $vs_char_set);
			}
			$va_rewrite_results = $this->_rewriteQuery($o_parsed_query);
			$o_rewritten_query = new Zend_Search_Lucene_Search_Query_Boolean($va_rewrite_results['terms'], $va_rewrite_results['signs']);
	
			$vs_search = $this->_queryToString($o_rewritten_query);
			//print "<div style='background:#FFFFFF; padding: 5px; border: 1px dotted #666666;'><strong>DEBUG: </strong>".$ps_search.'/'.$vs_search."</div>";
			
			$o_res =  $this->opo_engine->search($this->opn_tablenum, $vs_search, $this->opa_result_filters, $o_rewritten_query);

			$va_query_terms = array();
			
			// cache the results
			$va_hits = array();	
			$vn_c = 0;
			while($o_res->nextHit()) {
				if ($vn_limit && ($vn_limit <= $vn_c)) { break; }
				$va_hits[$o_res->get($vs_pk)] = true;
				$vn_c++;
			}
			$o_res->seek(0);
			
			if (isset($pa_options['checkAccess']) && (is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']))) {
				$va_access_values = $pa_options['checkAccess'];
				$va_hits = $this->filterHitsByAccess($va_hits, $va_access_values);
			}
			
			if (is_array($va_type_ids = $this->getTypeRestrictionList()) && sizeof($va_type_ids)) {
				$va_hits = $this->filterHitsByType($va_hits, $va_type_ids);
			}
			
			if (isset($pa_options['sort']) && $pa_options['sort'] && ($pa_options['sort'] != '_natural')) {
				$va_hits = $this->sortHits($va_hits, $pa_options['sort'], (isset($pa_options['sort_direction']) ? $pa_options['sort_direction'] : null));
			}
			
			$va_hit_values = array_keys($va_hits);
			$o_res = new WLPlugSearchEngineCachedResult($va_hit_values, $va_query_terms, $vs_pk);
			
			// cache for later use
			$o_cache->save($ps_search, $this->opn_tablenum, array_flip($va_hit_values), null, null, $pa_options);
		
			// log search
			$o_log = new Searchlog();
			
			global $AUTH_CURRENT_USER_ID;
			$vn_search_user_id = $AUTH_CURRENT_USER_ID ? $AUTH_CURRENT_USER_ID : null;
			$vn_search_form_id = isset($pa_options['form_id']) ? $pa_options['form_id'] : null;
			$vs_log_details = isset($pa_options['log_details']) ? $pa_options['log_details'] : '';
			$vs_search_source = isset($pa_options['search_source']) ? $pa_options['search_source'] : '';
				
			$vn_execution_time = $t->getTime(4);
			$o_log->log(array(
				'user_id' => $vn_search_user_id, 
				'table_num' => $this->opn_tablenum, 
				'search_expression' => $ps_search, 
				'num_hits' => sizeof($va_hit_values), 
				'form_id' => $vn_search_form_id, 
				'ip_addr' => $_SERVER['REMOTE_ADDR'] ?  $_SERVER['REMOTE_ADDR'] : null,
				'details' => $vs_log_details,
				'search_source' => $vs_search_source,
				'execution_time' => $vn_execution_time
			));
		}
		if ($po_result) {
			$po_result->init($this->opn_tablenum, $o_res, $this->opa_tables);
			return $po_result;
		} else {
			return new SearchResult($this->opn_tablenum, $o_res, $this->opa_tables);
		}
	}
	
	# ------------------------------------------------------------------
	/**
	 * @param $pa_hits Array of row_ids to sort. *MUST HAVE row_ids AS KEYS, NOT VALUES*
	 */
	public function filterHitsByType($pa_hits, $pa_type_ids) {
		$o_db = new Db();
		if (!sizeof($pa_hits)) { return $pa_hits; }
		if (!($t_table = $this->opo_datamodel->getInstanceByTableNum($this->opn_tablenum, true))) { return $pa_hits; }
		$vs_table_pk = $t_table->primaryKey();
		$vs_table_name = $this->ops_tablename;
		if (!($vs_type_field_name = $t_table->getTypeFieldName())) { return $pa_hits; }
		
		$qr_sort = $o_db->query("
			SELECT {$vs_table_name}.{$vs_table_pk}
			FROM {$vs_table_name}
			WHERE
				{$vs_table_name}.{$vs_table_pk} IN (".join(", ", array_keys($pa_hits)).") AND
				{$vs_table_name}.{$vs_type_field_name} IN (".join(", ", $pa_type_ids).")
		");
		
		$va_hits = array();
		while($qr_sort->nextRow()) {
			$va_hits[$qr_sort->get($vs_table_pk, array('binary' => true))] = true;
		}
		return $va_hits;
	}
	# ------------------------------------------------------------------
	/**
	 * @param $pa_hits Array of row_ids to filter. *MUST HAVE row_ids AS KEYS, NOT VALUES*
	 */
	public function filterHitsByAccess($pa_hits, $pa_access_values) {
		if (!sizeof($pa_hits)) { return $pa_hits; }
		if (!sizeof($pa_access_values)) { return $pa_hits; }
		if (!($t_table = $this->opo_datamodel->getInstanceByTableNum($this->opn_tablenum, true))) { return $pa_hits; }
		if (!$t_table->hasField('access')) { return $pa_hits; }
		
		$vs_table_pk = $t_table->primaryKey();
		$vs_table_name = $this->ops_tablename;
		if (!($vs_type_field_name = $t_table->getTypeFieldName())) { return $pa_hits; }
		
		$o_db = new Db();
		$qr_sort = $o_db->query("
			SELECT {$vs_table_name}.{$vs_table_pk}
			FROM {$vs_table_name}
			WHERE
				{$vs_table_name}.{$vs_table_pk} IN (".join(", ", array_keys($pa_hits)).") AND
				{$vs_table_name}.access IN (".join(", ", $pa_access_values).")
		");
		
		$va_hits = array();
		while($qr_sort->nextRow()) {
			$va_hits[$qr_sort->get($vs_table_pk, array('binary' => true))] = true;
		}
		return $va_hits;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getRandomResult($pn_num_hits=10, $po_result=null) {
		$o_db = new Db();
		if (!($t_table = $this->opo_datamodel->getInstanceByTableNum($this->opn_tablenum, true))) { return null; }
		$vs_table_pk = $t_table->primaryKey();
		$vs_table_name = $this->ops_tablename;
		
		$qr_res = $o_db->query("
			SELECT {$vs_table_name}.{$vs_table_pk}
			FROM {$vs_table_name}
			WHERE {$vs_table_name}.{$vs_table_pk} >= 
				(SELECT FLOOR( MAX({$vs_table_name}.{$vs_table_pk}) * RAND()) FROM {$vs_table_name}) 
			LIMIT {$pn_num_hits}
		");
		
		$va_hits = array();
		while($qr_res->nextRow()) {
			$va_hits[] = $qr_res->get($vs_table_pk, array('binary' => true));
		}
		
		$o_res = new WLPlugSearchEngineCachedResult($va_hits, array(), $vs_table_pk);
		
		if ($po_result) {
			$po_result->init($this->opn_tablenum, $o_res, array());
			return $po_result;
		} else {
			return new SearchResult($this->opn_tablenum, $o_res);
		}
	}
	# ------------------------------------------------------------------
	/**
	 * @param $pa_hits Array of row_ids to sort. *MUST HAVE row_ids AS KEYS, NOT VALUES*
	 */
	public function sortHits(&$pa_hits, $ps_field, $ps_direction='asc') {
			if (!in_array($ps_direction, array('asc', 'desc'))) { $ps_direction = 'asc'; }
			if (!is_array($pa_hits) || !sizeof($pa_hits)) { return $pa_hits; }
				
			$t_table = $this->opo_datamodel->getInstanceByTableNum($this->opn_tablenum, true);
			$vs_table_pk = $t_table->primaryKey();
			$vs_table_name = $this->ops_tablename;
			
			$va_fields = explode(';', $ps_field);
			$va_joins = array();
			$va_orderbys = array();
			$vs_is_preferred_sql = '';
			
			foreach($va_fields as $vs_field) {
				$va_tmp = explode('.', $vs_field);
				
				if ($va_tmp[0] == $vs_table_name) {
					// sort field is in search table
					
					if (!$t_table->hasField($va_tmp[1])) { 
						// is it an attribute?
						$t_element = new ca_metadata_elements();
						if ($t_element->load(array('element_code' => $va_tmp[1]))) {
							$vn_element_id = $t_element->getPrimaryKey();
							
							if (!($vs_sort_field = Attribute::getSortFieldForDatatype($t_element->get('datatype')))) {
								return $pa_hits;
							}
							
							$vs_sql = "
								SELECT t.*
								FROM {$vs_table_name} t
								INNER JOIN ca_attributes AS attr ON attr.row_id = t.{$vs_table_pk}
								INNER JOIN ca_attribute_values AS attr_vals ON attr_vals.attribute_id = attr.attribute_id
								WHERE
									(t.{$vs_table_pk} IN (".join(", ", array_keys($pa_hits))."))
									AND
									(attr_vals.element_id = ?) AND (attr.table_num = ?) AND (attr_vals.{$vs_sort_field} IS NOT NULL)
								ORDER BY
									attr_vals.{$vs_sort_field} {$ps_direction}
							";
							
							$qr_sort = $this->opo_db->query($vs_sql, (int)$vn_element_id, (int)$this->opn_tablenum);
			
							$va_sorted_hits = array();
							while($qr_sort->nextRow()) {
								$vn_id = $qr_sort->get($vs_table_pk, array('binary' => true));
								unset($pa_hits[$vn_id]);
								$va_sorted_hits[$vn_id] = $qr_sort->getRow();
							}
							
							foreach($pa_hits as $vn_id => $va_item) {
								$va_sorted_hits[$vn_id] = $va_item;
							}
							return $va_sorted_hits;
						}
					
						return $pa_hits; 	// return hits unsorted if field is not valid
					} else {	
						$va_field_info = $t_table->getFieldInfo($va_tmp[1]);
						if ($va_field_info['START'] && $va_field_info['END']) {
							$va_orderbys[] = $va_field_info['START'].' '.$ps_direction;
							$va_orderbys[] = $va_field_info['END'].' '.$ps_direction;
						} else {
							$va_orderbys[] = $vs_field.' '.$ps_direction;
						}
					}
				} else {
					// sort field is in related table (only many-one relations are supported)
					$va_rels = $this->opo_datamodel->getRelationships($vs_table_name, $va_tmp[0]);
					if (!$va_rels) { return $pa_hits; }							// return hits unsorted if field is not valid
					$t_rel = $this->opo_datamodel->getInstanceByTableName($va_tmp[0], true);
					if (!$t_rel->hasField($va_tmp[1])) { return $pa_hits; }
					$va_joins[$va_tmp[0]] = 'INNER JOIN '.$va_tmp[0].' ON '.$va_tmp[0].'.'.$va_rels[$vs_table_name][$va_tmp[0]][0][1].' = '.$vs_table_name.'.'.$va_rels[$vs_table_name][$va_tmp[0]][0][0]."\n";
					$va_orderbys[] = $vs_field.' '.$ps_direction;
					
					// if the related supports preferred values (eg. *_labels tables) then only consider those in the sort
					if ($t_rel->hasField('is_preferred')) {
						$vs_is_preferred_sql = " AND ".$va_tmp[0].".is_preferred = 1";
					}
				}
			}
			$vs_join_sql = join("\n", $va_joins);
			
			$vs_sql = "
				SELECT {$vs_table_name}.*
				FROM {$vs_table_name}
				{$vs_join_sql}
				WHERE
					{$vs_table_name}.{$vs_table_pk} IN (".join(", ", array_keys($pa_hits)).")
					{$vs_is_preferred_sql}
				ORDER BY
					".join(', ', $va_orderbys)."
			";
			//print $vs_sql;
			$qr_sort = $this->opo_db->query($vs_sql);
			
			$va_sorted_hits = array();
			while($qr_sort->nextRow()) {
				$va_sorted_hits[$qr_sort->get($vs_table_pk, array('binary' => true))] = $qr_sort->getRow();
			}
			return $va_sorted_hits;
		}
	# ------------------------------------------------------------------
	private function _rewriteQuery($po_query) {
		$va_terms = array();
		$va_signs = array();
		switch(get_class($po_query)) {
			case 'Zend_Search_Lucene_Search_Query_Boolean':
				$va_items = $po_query->getSubqueries();
				break;
			case 'Zend_Search_Lucene_Search_Query_MultiTerm':
				$va_items = $po_query->getTerms();
				break;
			default:
				$va_items = array();
				break;
		}
		
		if (method_exists($po_query, 'getSigns')) {
			$va_old_signs = $po_query->getSigns();
		} else {
			$va_old_signs = array();
		}

		$vn_i = 0;
		foreach($va_items as $o_term) {
			switch(get_class($o_term)) {
				case 'Zend_Search_Lucene_Search_Query_Preprocessing_Term':
					$va_terms[] = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term($o_term->__toString()));
					$va_signs[] = isset($va_old_signs[$vn_i]) ? $va_old_signs[$vn_i] : true;
					break;
				case 'Zend_Search_Lucene_Search_Query_Term':
					if (preg_match('!\-!', $vs_term_text = $o_term->getTerm()->text)) {	// hack to force hyphenated terms to quoted strings with a space instead of hyphen; addresses issue where PHP Lucene parser seems to do the wrong thing
						$vs_term_text = str_replace("-", " ", $vs_term_text);
						$va_terms[] = new Zend_Search_Lucene_Search_Query_Phrase(array($vs_term_text), null, $o_term->getTerm()->field);
						$va_signs[] = isset($va_old_signs[$vn_i]) ? $va_old_signs[$vn_i] : true;
					} else {
						$va_rewritten_terms = $this->_rewriteTerm($o_term, $va_old_signs[$vn_i]);
						if (sizeof($va_rewritten_terms['terms']) == 1) {
							$va_terms[] = new Zend_Search_Lucene_Search_Query_Term($va_rewritten_terms['terms'][0]);
							$va_signs[] = $va_rewritten_terms['signs'][0];
						} else { 
							for($vn_i = 0; $vn_i < sizeof($va_rewritten_terms['terms']); $vn_i++) {
								$va_terms[] = new Zend_Search_Lucene_Search_Query_MultiTerm(array($va_rewritten_terms['terms'][$vn_i]), array($va_rewritten_terms['signs'][$vn_i]));
								$va_signs[] = $va_rewritten_terms['signs'][$vn_i] ? true : null;
							}
							//$o_mt = new Zend_Search_Lucene_Search_Query_MultiTerm($va_rewritten_terms['terms'], $va_rewritten_terms['signs']);
						}
						//$va_terms[] = $o_mt;
						//$va_signs[] = sizeof($va_old_signs) ? array_shift($va_old_signs): true;
					}
					break;
				case 'Zend_Search_Lucene_Index_Term':
					$va_rewritten_terms = $this->_rewriteTerm(new Zend_Search_Lucene_Search_Query_Term($o_term), $va_old_signs[$vn_i]);
					if (sizeof($va_rewritten_terms['terms']) == 1) {
						$o_mt = new Zend_Search_Lucene_Search_Query_Term($va_rewritten_terms['terms'][0]);
					} else {
						$o_mt = new Zend_Search_Lucene_Search_Query_MultiTerm($va_rewritten_terms['terms'], $va_rewritten_terms['signs']);
					}
					$va_terms[] = $o_mt;
					$va_signs[] = sizeof($va_rewritten_terms['signs']) ? array_shift($va_rewritten_terms['signs']): true;
					break;
				case 'Zend_Search_Lucene_Search_Query_Wildcard':
					$va_rewritten_terms = $this->_rewriteTerm(new Zend_Search_Lucene_Search_Query_Term($o_term->getPattern()), $va_old_signs[$vn_i]);
					$o_mt = new Zend_Search_Lucene_Search_Query_MultiTerm($va_rewritten_terms['terms'], $va_rewritten_terms['signs']);
					$va_terms[] = $o_mt;
					$va_signs[] = sizeof($va_rewritten_terms['signs']) ? array_shift($va_rewritten_terms['signs']): true;
					break;
				case 'Zend_Search_Lucene_Search_Query_Phrase':
					$va_phrase_items = $o_term->getTerms();
					$va_rewritten_phrase = $this->_rewritePhrase($o_term, $va_old_signs[$vn_i]);
					
					foreach($va_rewritten_phrase['terms'] as $o_term) {
						$va_terms[] = $o_term;
					}
					foreach($va_rewritten_phrase['signs'] as $vb_sign) {
						$va_signs[] = $vb_sign;
					}
					break;
				case 'Zend_Search_Lucene_Search_Query_MultiTerm':
					$va_tmp = $this->_rewriteQuery($o_term);
					$va_terms[] = new Zend_Search_Lucene_Search_Query_MultiTerm($va_tmp['terms'], $va_tmp['signs']);
					$va_signs[] = $va_old_signs[$vn_i] ? $va_old_signs[$vn_i] : true;
					break;
				case 'Zend_Search_Lucene_Search_Query_Boolean':
					$va_tmp = $this->_rewriteQuery($o_term);
					$va_terms[] = new Zend_Search_Lucene_Search_Query_Boolean($va_tmp['terms'], $va_tmp['signs']);
					$va_signs[] = $va_old_signs[$vn_i] ? $va_old_signs[$vn_i] : true;
					break;
				case 'Zend_Search_Lucene_Search_Query_Range':
					$va_signs[] = $va_old_signs[$vn_i] ? $va_old_signs[$vn_i] : true;
					$va_terms = array_merge($va_terms, $this->_rewriteRange($o_term));
					break;
				default:
					// NOOP (TODO: do *something*)
					break;
			}	
			
			$vn_i++;
		}
		
		return array(
			'terms' => $va_terms,
			'signs' => $va_signs
		);
	}
	# ------------------------------------------------------------------
	/**
	 * @param $po_term - term to rewrite; must be Zend_Search_Lucene_Search_Query_Term object
	 * @param $pb_sign - Zend boolean flag (true=and, null=or, false=not)
	 * @return array - rewritten terms are *** Zend_Search_Lucene_Index_Term *** objects
	 */
	private function _rewriteTerm($po_term, $pb_sign) {
		$vs_fld = $po_term->getTerm()->field;
			
		if (sizeof($va_access_points = $this->getAccessPoints($this->opn_tablenum))) {
			// if field is access point then do rewrite
			if (
				isset($va_access_points[$vs_fld]) 
				&&
				($va_ap_info = $va_access_points[$vs_fld])
			) {
				$va_fields = isset($va_ap_info['fields']) ? $va_ap_info['fields'] : null;
				if (!in_array($vs_bool = strtoupper($va_ap_info['boolean']), array('AND', 'OR'))) {
					$vs_bool = 'OR';
				}
				$va_terms = array();
				foreach($va_fields as $vs_field) {
					$va_terms['terms'][] = new Zend_Search_Lucene_Index_Term($po_term->getTerm()->text, $vs_field);
					$va_terms['signs'][] = ($vs_bool == 'AND') ? true : false;
				}
				
				if (is_array($va_additional_criteria = $va_ap_info['additional_criteria'])) {
					foreach($va_additional_criteria as $vs_criterion) {
						$va_terms['terms'][] = new Zend_Search_Lucene_Index_Term($vs_criterion);
						$va_terms['signs'][] = $vs_bool;
					}
				}
				
				return $va_terms;
			}
		}
		
		// is it preferred labels? Rewrite the field for that.
		$va_tmp = explode('.', $vs_fld);
		if ($va_tmp[1] == 'preferred_labels') {
			if ($t_instance = $this->opo_datamodel->getInstanceByTableName($va_tmp[0], true)) {
				if (method_exists($t_instance, "getLabelTableName")) {
					return array(
						'terms' => array(new Zend_Search_Lucene_Index_Term($po_term->getTerm()->text, $t_instance->getLabelTableName().'.'.$t_instance->getLabelDisplayField())),
						'signs' => array($pb_sign)
					);
				}
			}
		}
		
		return array('terms' => array($po_term->getTerm()), 'signs' => array($pb_sign));
	}
	# ------------------------------------------------------------------
	/**
	 * @param $po_term - phrase expression to rewrite; must be Zend_Search_Lucene_Search_Query_Phrase object
	 * @param $pb_sign - Zend boolean flag (true=and, null=or, false=not)
	 * @return array - rewritten phrases are *** Zend_Search_Lucene_Search_Query_Phrase *** objects
	 */
	private function _rewritePhrase($po_term, $pb_sign) {		
		$va_index_term_strings = array();
		$va_phrase_terms = $po_term->getTerms();
		foreach($va_phrase_terms as $o_phrase_term) {
			$va_index_term_strings[] = $o_phrase_term->text; 
		}
		
		$vs_fld = $va_phrase_terms[0]->field;
		
		if (sizeof($va_access_points = $this->getAccessPoints($this->opn_tablenum))) {
			// if field is access point then do rewrite
			if (
				isset($va_access_points[$vs_fld]) 
				&&
				($va_ap_info = $va_access_points[$vs_fld])
			) {
				$va_fields = isset($va_ap_info['fields']) ? $va_ap_info['fields'] : null;
				if (!in_array($vs_bool = strtoupper($va_ap_info['boolean']), array('AND', 'OR'))) {
					$vs_bool = 'OR';
				}
				
				foreach($va_fields as $vs_field) {
					$va_terms['terms'][] = new Zend_Search_Lucene_Search_Query_Phrase($va_index_term_strings, null, $vs_field);
					$va_terms['signs'][] = ($vs_bool == 'AND') ? true : false;
				}
				
				if (is_array($va_additional_criteria = $va_ap_info['additional_criteria'])) {
					foreach($va_additional_criteria as $vs_criterion) {
						$va_terms['terms'][] = new Zend_Search_Lucene_Index_Term($vs_criterion);
						$va_terms['signs'][] = $vs_bool;
					}
				}
				
				return $va_terms;
			}
		}
		
		// is it preferred labels? Rewrite the field for that.
		$va_tmp = explode('.', $vs_fld);
		if ($va_tmp[1] == 'preferred_labels') {
			if ($t_instance = $this->opo_datamodel->getInstanceByTableName($va_tmp[0], true)) {
				if (method_exists($t_instance, "getLabelTableName")) {
					return array(
						'terms' => array(new Zend_Search_Lucene_Search_Query_Phrase($va_index_term_strings, null, $t_instance->getLabelTableName().'.'.$t_instance->getLabelDisplayField())),
						'signs' => array($pb_sign)
					);
				}
			}
		}
		
		return array('terms' => array($po_term), 'signs' => array($pb_sign));
	}
	# ------------------------------------------------------------------
	/**
	 * @param $po_range - range expression to rewrite; must be Zend_Search_Lucene_Search_Query_Range object
	 * @return array - rewritten search terms 
	 */
	private function _rewriteRange($po_range) {
		if (sizeof($va_access_points = $this->getAccessPoints($this->opn_tablenum))) {
			// if field is access point then do rewrite
			if ($va_ap_info = $va_access_points[$po_range->getField()]) {
				$va_fields = $va_ap_info['fields'];
				if (!in_array($vs_bool = strtoupper($va_ap_info['boolean']), array('AND', 'OR'))) {
					$vs_bool = 'OR';
				}
				$va_tmp = array();
				foreach($va_fields as $vs_field) {
					$po_range->getLowerTerm()->field = $vs_field;
					$po_range->getUpperTerm()->field = $vs_field;
					$o_range = new Zend_Search_Lucene_Search_Query_Range($po_range->getLowerTerm(), $po_range->getUpperTerm(), (($vs_bool == 'OR') ? null : true));
					$o_range->field = $vs_field;
					$va_terms[] = $o_range;
					
				}
				
				if (is_array($va_additional_criteria = $va_ap_info['additional_criteria'])) {
					foreach($va_additional_criteria as $vs_criterion) {
						$va_terms[] = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term($vs_criterion));
					}
				}
				return $va_terms;
			}
		}
		
		return array($po_range);
	}
	# ------------------------------------------------------------------
	private function _queryToString($po_parsed_query) {
		switch(get_class($po_parsed_query)) {
			case 'Zend_Search_Lucene_Search_Query_Boolean':
				$va_items = $po_parsed_query->getSubqueries();
				$va_signs = $po_parsed_query->getSigns();
				break;
			case 'Zend_Search_Lucene_Search_Query_MultiTerm':
				$va_items = $po_parsed_query->getTerms();
				$va_signs = $po_parsed_query->getSigns();
				break;
			case 'Zend_Search_Lucene_Search_Query_Phrase':
				//$va_items = $po_parsed_query->getTerms();
				$va_items = $po_parsed_query;
				$va_signs = null;
				break;
			case 'Zend_Search_Lucene_Search_Query_Range':
				$va_items = $po_parsed_query;
				$va_signs = null;
				break;
			default:
				$va_items = array();
				$va_signs = null;
				break;
		}
		
		$vs_query = '';
		foreach ($va_items as $id => $subquery) {
			if ($id != 0) {
				$vs_query .= ' ';
			}
		
			if (($va_signs === null || $va_signs[$id] === true) && ($id)) {
				$vs_query .= ' AND ';
			} else if (($va_signs[$id] === false) && $id) {
				$vs_query .= ' NOT ';
			} else {
				if ($id) { $vs_query .= ' OR '; }
			}
			switch(get_class($subquery)) {
				case 'Zend_Search_Lucene_Search_Query_Phrase':
					$vs_query .= '(' . $subquery->__toString(). ')';
					break;
				case 'Zend_Search_Lucene_Index_Term':
					$subquery = new Zend_Search_Lucene_Search_Query_Term($subquery);
					// intentional fallthrough to next case here
				case 'Zend_Search_Lucene_Search_Query_Term':
					$vs_query .= '(' . $subquery->__toString() . ')';
					break;	
				case 'Zend_Search_Lucene_Search_Query_Range':
					$vs_query = $subquery;
					break;
				default:
					$vs_query .= '(' . $this->_queryToString($subquery) . ')';
					break;
			}
			
		
			if ((method_exists($subquery, "getBoost")) && ($subquery->getBoost() != 1)) {
				$vs_query .= '^' . round($subquery->getBoost(), 4);
			}
		}
		
		return $vs_query;
    }
	# ------------------------------------------------------------------
	# Search parameter accessors
	# ------------------------------------------------------------------
	public function addTable($ps_tablename, $pa_fieldlist, $pa_join_tables=array(), $pa_criteria=array()) {
		$this->opa_tables[$ps_tablename] = array(
			'fieldList' => $pa_fieldlist,
			'joinTables' => $pa_join_tables,
			'criteria' => $pa_criteria
		);
	}
	# ------------------------------------------------------------------
	public function removeTable($ps_tablename) {
		unset($this->opa_tables[$ps_tablename]);
	}
	# ------------------------------------------------------------------
	public function getTables() {
		return $this->opa_tables;
	}
	# ------------------------------------------------------------------
	/**
	 * Result filters are criteria through which the results of a search are passed before being
	 * returned to the caller. They are often used to restrict the domain over which searches operate
	 * (for example, ensuring that a search only returns rows with a certain "status" field value)
	 *
	 * $ps_access_point is the name of an indexed field or access point
	 * $ps_operator is one of the following: =, <, >, <=, >=, - ("between"), in
	 * $pm_value is the value to apply; this is usually text or a number; for the "in" operator this is a comma-separated list of string or numeric values
	 *			
	 *
	 */
	public function addResultFilter($ps_access_point, $ps_operator, $pm_value) {
		if (!in_array($ps_operator, array('=', '<', '>', '<=', '>=', '-', 'in'))) { return false; }
		$o_indexer = new SearchIndexer();
		if(sizeof($va_access_point = explode('.',$ps_access_point))==2){
			$va_indexed_fields = $o_indexer->getFieldsToIndex($this->opn_tablenum,$va_access_point[0]);
			$vs_content_table_fieldname = $va_access_point[1];
		} else { /* don't know if we need this case */
			$va_indexed_fields = $o_indexer->getFieldsToIndex($this->opn_tablenum);
			$vs_content_table_fieldname = $ps_access_point;	
		}
		if (!is_array($va_indexed_fields[$vs_content_table_fieldname])) {
			if (!preg_match("/([\w_\-]+)\.(_count|md_[0-9]*)$/", $ps_access_point, $va_matches)) {
				return false;
			}

			// hmmm, is the access point a '_count' field or a metadata restriction like 'md_5'?
			$va_indexed_fields = $o_indexer->getFieldsToIndex($this->opn_tablenum, $va_matches[1]);
			if (!(is_array($va_indexed_fields) && (isset($va_indexed_fields['_count']) || isset($va_indexed_fields['_metadata'])))) {
				return false;
			}
		}
		
		$this->opa_result_filters[] = array(
			'access_point' => $ps_access_point,
			'operator' => $ps_operator,
			'value' => $pm_value
		);
		
		return true;
	}
	# ------------------------------------------------------------------
	public function clearResultFilters() {
		$this->opa_result_filters = array();
	}
	# ------------------------------------------------------------------
	public function getResultFilters() {
		return $this->opa_result_filters;
	}
	# ------------------------------------------------------
	# Type filtering
	# ------------------------------------------------------
	/**
	 * When type restrictions are specified, the search will only consider items of the given types. 
	 * If you specify a type that has hierarchical children then the children will automatically be included
	 * in the restriction. You may pass numeric type_id and alphanumeric type codes interchangeably.
	 *
	 * @param array $pa_type_codes_or_ids List of type_id or code values to filter search by. When set, the search will only consider items of the specified types. Using a hierarchical parent type will automatically include its children in the restriction. 
	 * @return boolean True on success, false on failure
	 */
	public function setTypeRestrictions($pa_type_codes_or_ids) {
		$t_instance = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
		
		if (!$pa_type_codes_or_ids) { return false; }
		if (is_array($pa_type_codes_or_ids) && !sizeof($pa_type_codes_or_ids)) { return false; }
		if (!is_array($pa_type_codes_or_ids)) { $pa_type_codes_or_ids = array($pa_type_codes_or_ids); }
		
		$t_list = new ca_lists();
		if (!method_exists($t_instance, 'getTypeListCode')) { return false; }
		if (!($vs_list_name = $t_instance->getTypeListCode())) { return false; }
		$va_type_list = $t_instance->getTypeList();
		
		$this->opa_search_type_ids = array();
		foreach($pa_type_codes_or_ids as $vs_code_or_id) {
			if (!(int)$vs_code_or_id) { continue; }
			if (!is_numeric($vs_code_or_id)) {
				$vn_type_id = $t_list->getItemIDFromList($vs_list_name, $vs_code_or_id);
			} else {
				$vn_type_id = (int)$vs_code_or_id;
			}
			
			if (!$vn_type_id) { return false; }
			
			if (isset($va_type_list[$vn_type_id]) && $va_type_list[$vn_type_id]) {	// is valid type for this subject
				// See if there are any child types
				$t_item = new ca_list_items($vn_type_id);
				$va_ids = $t_item->getHierarchyChildren(null, array('idsOnly' => true));
				$va_ids[] = $vn_type_id;
				$this->opa_search_type_ids = array_merge($this->opa_search_type_ids, $va_ids);
			}
		}
		
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of type_id values to restrict search to. Return values are always numeric types, 
	 * never codes, and will include all type_ids to filter on, including children of hierarchical types.
	 *
	 * @return array List of type_id values to restrict search to.
	 */
	public function getTypeRestrictionList() {
		return $this->opa_search_type_ids;
	}
	# ------------------------------------------------------
	/**
	 * Removes any specified type restrictions on the search
	 *
	 * @return boolean Always returns true
	 */
	public function clearTypeRestrictionList() {
		$this->opa_search_type_ids = null;
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * Ask the search engine plugin if everything is configured properly.
	 *
	 * @return ASearchConfigurationSettings
	 */
	public static function checkPluginConfiguration() {
		$o_config = Configuration::load();
		$ps_plugin_name = $o_config->get('search_engine_plugin');
		if (!file_exists(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/'.$ps_plugin_name.'ConfigurationSettings.php')) {
			return null;
		}
		require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/'.$ps_plugin_name.'ConfigurationSettings.php');
		$ps_classname = $ps_plugin_name."ConfigurationSettings";

		return new $ps_classname;
	}
	# ------------------------------------------------------------------
	/**
	 * Ask the search engine plugin for its display name
	 *
	 * @return String
	 */
	public static function getPluginEngineName() {
		$o_config = Configuration::load();
		$ps_plugin_name = $o_config->get('search_engine_plugin');
		if (!file_exists(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/'.$ps_plugin_name.'ConfigurationSettings.php')) {
			return null;
		}
		require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/'.$ps_plugin_name.'ConfigurationSettings.php');
		$ps_classname = $ps_plugin_name."ConfigurationSettings";
		$o_instance = new $ps_classname;
		return $o_instance->getEngineName();
	}
	# ------------------------------------------------------------------
	#
	# ------------------------------------------------------------------
	/**
	 * Performs the quickest possible search on the index for the table specified by the superclass
	 * using the text in $ps_search. Unlike the search() method, quickSearch doesn't support
	 * any sort of search syntax. You give it some text and you get a collection of (hopefully) relevant results back quickly. 
	 * quickSearch() is intended for autocompleting search suggestion UI's and the like, where performance is critical
	 * and the ability to control search parameters is not required.
	 *
	 * Quick searches are NOT logged to ca_search_log
	 *
	 * @param $ps_search - The text to search on
	 * @param $ps_tablename - name of table to search on
	 * @param $pn_table_num - number of table to search on (same table as $ps_tablename)
	 * @param $pa_options - an optional associative array specifying search options. Supported options are: 'limit' (the maximum number of results to return), 'checkAccess' (only return results that have an access value = to the specified value)
	 * 
	 * @return Array - an array of results is returned keys first by primary key id, then by locale_id. The array values are associative arrays with two keys: type_id (the type_id of the result; this points to a ca_list_items row defining the type of the result item) and label (the row item's label display field). You can push the returned results array from caExtractValuesByUserLocale() to get an array keyed by primary key id and returning for each id a displayable text label + the type of the found result item.
	 * 
	 */
	static function quickSearch($ps_search, $ps_tablename, $pn_tablenum, $pa_options=null) {
		$o_config = Configuration::load();
		$o_dm = Datamodel::load();
		
		if (!($ps_plugin_name = $o_config->get('search_engine_plugin'))) { return null; }
		if (!@require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/'.$ps_plugin_name.'.php')) { return null; }
		$ps_classname = 'WLPlugSearchEngine'.$ps_plugin_name;
		if (!($o_engine =  new $ps_classname)) { return null; }
		
		$va_ids = array_keys($o_engine->quickSearch($pn_tablenum, $ps_search, $pa_options));
		if (!is_array($va_ids) || !sizeof($va_ids)) { return array(); }
		$t_instance = $o_dm->getInstanceByTableNum($pn_tablenum, true);
		
		$t_label_instance = 		$t_instance->getLabelTableInstance();
		$vs_label_table_name = 		$t_instance->getLabelTableName();
		$vs_label_display_field = 	$t_instance->getLabelDisplayField();
		$vs_pk = 					$t_instance->primaryKey();
		$vs_is_preferred_sql = '';
		if ($t_label_instance->hasField('is_preferred')) {
			$vs_is_preferred_sql = ' AND (l.is_preferred = 1)';
		}
		
		$vs_check_access_sql = '';
		if (isset($pa_options['checkAccess']) && !is_null($pa_options['checkAccess']) && $t_instance->hasField('access')) {
			$vs_check_access_sql = ' AND (n.access = '.intval($pa_options['checkAccess']).')';
		}
		
		$vs_limit_sql = '';
		if (isset($pa_options['limit']) && !is_null($pa_options['limit']) && ($pa_options['limit'] > 0)) {
			$vs_limit_sql = ' LIMIT '.intval($pa_options['limit']);
		}
		
		
		$o_db = new Db();
		$qr_res = $o_db->query("
			SELECT n.{$vs_pk}, l.{$vs_label_display_field}, l.locale_id, n.type_id
			FROM {$vs_label_table_name} l
			INNER JOIN ".$ps_tablename." AS n ON n.{$vs_pk} = l.{$vs_pk}
			WHERE
				l.".$vs_pk." IN (".join(',', $va_ids).")
				{$vs_is_preferred_sql}
				{$vs_check_access_sql}
			{$vs_limit_sql}
		");
		
		$va_hits = array();
		while($qr_res->nextRow()) {
			$va_hits[$qr_res->get($vs_pk)][$qr_res->get('locale_id')] = array(
				'type_id' => $qr_res->get('type_id'),
				'label' => $qr_res->get($vs_label_display_field)
			);
		}
		return $va_hits;
	}
	# ------------------------------------------------------------------
}
?>
