<?php
/** ---------------------------------------------------------------------
 * app/models/ca_list_items.php : table access class for table ca_list_items
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
 * @subpackage models
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
 /**
   *
   */
 
require_once(__CA_LIB_DIR__.'/ca/BundlableLabelableBaseModelWithAttributes.php');
require_once(__CA_LIB_DIR__.'/ca/IHierarchy.php');
require_once(__CA_MODELS_DIR__.'/ca_lists.php');
require_once(__CA_MODELS_DIR__.'/ca_places.php');
require_once(__CA_MODELS_DIR__.'/ca_locales.php');


BaseModel::$s_ca_models_definitions['ca_list_items'] = array(
 	'NAME_SINGULAR' 	=> _t('list item'),
 	'NAME_PLURAL' 		=> _t('list items'),
 	'FIELDS' 			=> array(
 		'item_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Item id', 'DESCRIPTION' => 'Identifier for Item'
		),
		'parent_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Parent'), 'DESCRIPTION' => _t('Parent list item for this item')
		),
		'list_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('List'), 'DESCRIPTION' => _t('List item belongs to')
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'DISPLAY_FIELD' => array('ca_list_items.item_value'),
				'DISPLAY_ORDERBY' => array('ca_list_items.item_value'),
				'IS_NULL' => true, 
				'LIST_CODE' => 'list_item_types',
				'DEFAULT' => '',
				'LABEL' => _t('Type'), 'DESCRIPTION' => _t('Indicates the type of the list item.')
		),
		'idno' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Id number'), 'DESCRIPTION' => _t('Unique identifier for this list item'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'idno_sort' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Id number sort'), 'DESCRIPTION' => _t('Sortable value for id number'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'item_value' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Item value'), 'DESCRIPTION' => _t('Value of this list item; is stored in database when this item is selected'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'color' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_COLORPICKER, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Item color'), 'DESCRIPTION' => _t('Color to display item in')
		),
		'icon' => array(
				'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				"MEDIA_PROCESSING_SETTING" => 'ca_icons',
				'LABEL' => _t('Item icon'), 'DESCRIPTION' => _t('Optional icon to use with item')
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('Sort order'),
		),
		'hier_left' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Hierarchical index - left bound', 'DESCRIPTION' => 'Left-side boundary for nested set-style hierarchical indexing; used to accelerate search and retrieval of hierarchical record sets.'
		),
		'hier_right' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Hierarchical index - right bound', 'DESCRIPTION' => 'Right-side boundary for nested set-style hierarchical indexing; used to accelerate search and retrieval of hierarchical record sets.'
		),
		'is_enabled' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '1',
				'LABEL' => _t('Is enabled?'), 'DESCRIPTION' => _t('If checked this item is selectable and can be used in cataloguing.')
		),
		'is_default' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Is default?'), 'DESCRIPTION' => _t('If checked this item will be the default selection for the list.')
		),
		'validation_format' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 255, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Validation format'), 'DESCRIPTION' => _t('NOT CURRENTLY SUPPORTED; WILL BE A PERL-COMPATIBLE REGEX APPLIED TO VALIDATE INPUT'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'access' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'BOUNDS_CHOICE_LIST' => array(
					_t('Not accessible to public') => 0,
					_t('Accessible to public') => 1
				),
				'LIST' => 'access_statuses',
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if the list item is accessible to the public or not. ')
		),
		'status' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'BOUNDS_CHOICE_LIST' => array(
					_t('Newly created') => 0,
					_t('Editing in progress') => 1,
					_t('Editing complete - pending review') => 2,
					_t('Review in progress') => 3,
					_t('Completed') => 4
				),
				'LIST' => 'workflow_statuses',
				'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Indicates the current state of the list item.')
		),
		'deleted' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if list item is deleted or not.')
		)
 	)
);

class ca_list_items extends BundlableLabelableBaseModelWithAttributes implements IHierarchy {
	# ---------------------------------
	# --- Object attribute properties
	# ---------------------------------
	# Describe structure of content object's properties - eg. database fields and their
	# associated types, what modes are supported, et al.
	#

	# ------------------------------------------------------
	# --- Basic object parameters
	# ------------------------------------------------------
	# what table does this class represent?
	protected $TABLE = 'ca_list_items';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'item_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('idno');

	# When the list of "list fields" above contains more than one field,
	# the LIST_DELIMITER text is displayed between fields as a delimiter.
	# This is typically a comma or space, but can be any string you like
	protected $LIST_DELIMITER = ' ';

	# What you'd call a single record from this table (eg. a "person")
	protected $NAME_SINGULAR;

	# What you'd call more than one record from this table (eg. "people")
	protected $NAME_PLURAL;

	# List of fields to sort listing of records by; you can use 
	# SQL 'ASC' and 'DESC' here if you like.
	protected $ORDER_BY = array('idno');

	# Maximum number of record to display per page in a listing
	protected $MAX_RECORDS_PER_PAGE = 20; 

	# How do you want to page through records in a listing: by number pages ordered
	# according to your setting above? Or alphabetically by the letters of the first
	# LIST_FIELD?
	protected $PAGE_SCHEME = 'alpha'; # alpha [alphabetical] or num [numbered pages; default]

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = 'rank';
	
	

	# ------------------------------------------------------
	# Hierarchical table properties
	# ------------------------------------------------------
	protected $HIERARCHY_TYPE				=	__CA_HIER_TYPE_MULTI_MONO__;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	'hier_left';
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	'hier_right';
	protected $HIERARCHY_PARENT_ID_FLD		=	'parent_id';
	protected $HIERARCHY_DEFINITION_TABLE	=	'ca_lists';
	protected $HIERARCHY_ID_FLD				=	'list_id';
	protected $HIERARCHY_POLY_TABLE			=	null;
	
	# ------------------------------------------------------
	# Change logging
	# ------------------------------------------------------
	protected $UNIT_ID_FIELD = null;
	protected $LOG_CHANGES_TO_SELF = true;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
			'list_id'
		),
		"RELATED_TABLES" => array(
			
		)
	);
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = 'type_id';					// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = 'list_item_types';		// list code (ca_lists.list_code) of list defining types for this table
	
	# ------------------------------------------------------
	# Labels
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_list_item_labels';
	
	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = 'ca_list_items_x_list_items';
	
	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	// protected $ID_NUMBERING_ID_FIELD = 'idno';				// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = 'idno_sort';		// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	protected $ID_NUMBERING_CONTEXT_FIELD = 'list_id';		// name of field to use value of for "context" when checking for duplicate identifier values; if not set identifer is assumed to be global in scope; if set identifer is checked for uniqueness (if required) within the value of this field
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'ListItemSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'ListItemSearchResult';
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	# ------------------------------------------------------
	# --- Constructor
	#
	# This is a function called when a new instance of this object is created. This
	# standard constructor supports three calling modes:
	#
	# 1. If called without parameters, simply creates a new, empty objects object
	# 2. If called with a single, valid primary key value, creates a new objects object and loads
	#    the record identified by the primary key value
	#
	# ------------------------------------------------------
	public function __construct($pn_id=null) {
		parent::__construct($pn_id);	# call superclass constructor
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions() {
		parent::initLabelDefinitions();
		$this->BUNDLES['ca_objects'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects'));
		$this->BUNDLES['ca_entities'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities'));
		$this->BUNDLES['ca_places'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places'));
		$this->BUNDLES['ca_occurrences'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences'));
		$this->BUNDLES['ca_collections'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections'));
		
		$this->BUNDLES['ca_list_items'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related vocabulary terms'));
		
		$this->BUNDLES['hierarchy_navigation'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Hierarchy navigation'));
		$this->BUNDLES['hierarchy_location'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Location in hierarchy'));
	}
 	# ------------------------------------------------------
	public function insert($pa_options=null) {
		if (!$this->inTransaction()) {
			$this->setTransaction(new Transaction());
		}
		if ($this->get('is_default')) {
			$this->getDb()->query("
				UPDATE ca_list_items SET is_default = 0 WHERE list_id = ?
			", (int)$this->get('list_id'));
		}
		$vn_rc = parent::insert($pa_options);
		
		if ($this->getPrimaryKey()) {
			$t_list = new ca_lists();
			$t_list->setTransaction($this->getTransaction());
			
			
			if (($t_list->load($this->get('list_id'))) && ($t_list->get('list_code') == 'place_hierarchies') && ($this->get('parent_id'))) {
				// insert root or place hierarchy when creating non-root items in 'place_hierarchies' list
				$t_locale = new ca_locales();
				$va_locales = $this->getAppConfig()->getList('locale_defaults');
				$vn_locale_id = $t_locale->localeCodeToID($va_locales[0]);
				
				// create root in ca_places
				$t_place = new ca_places();
				$t_place->setTransaction($this->getTransaction());
				$t_place->setMode(ACCESS_WRITE);
				$t_place->set('hierarchy_id', $this->getPrimaryKey());
				$t_place->set('locale_id', $vn_locale_id);
				$t_place->set('type_id', null);
				$t_place->set('parent_id', null);
				$t_place->set('idno', 'Root node for '.$this->get('idno'));
				$t_place->insert();
				
				if ($t_place->numErrors()) {
					$this->delete();
					$this->errors = array_merge($this->errors, $t_place->errors);
					return false;
				}
				
				$t_place->addLabel(
					array(
						'name' => 'Root node for '.$this->get('idno')
					),
					$vn_locale_id, null, true
				);
			}
		}
		
		if ($this->numErrors()) {
			$this->getTransaction()->rollback();
		} else {
			$this->getTransaction()->commit();
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	public function update($pa_options=null) {
		if (!$this->inTransaction()) {
			$this->setTransaction(new Transaction());
		}
		if ($this->get('is_default')) {
			$this->getDb()->query("
				UPDATE ca_list_items SET is_default = 0 WHERE list_id = ?
			", (int)$this->get('list_id'));
		}
		$vn_rc = parent::update($pa_options);
		
		if ($this->numErrors()) {
			$this->getTransaction()->rollback();
		} else {
			$this->getTransaction()->commit();
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Return array containing information about all lists, including their root_id's
	 */
	 public function getHierarchyList($pb_vocabularies=false) {
	 	$t_list = new ca_lists();
	 	
	 	$va_hierarchies = caExtractValuesByUserLocale($t_list->getListOfLists());
		
		$o_db = $this->getDb();
		
		$va_hierarchy_ids = array();
		foreach($va_hierarchies as $vn_list_id => $va_list_info) {
			$va_hierarchy_ids[] = intval($vn_list_id);
		}
		
		if (!sizeof($va_hierarchy_ids)) { return array(); }
		
		// get root for each hierarchy
		$qr_res = $o_db->query("
			SELECT cli.item_id, cli.list_id, count(*) children
			FROM ca_list_items cli
			LEFT JOIN ca_list_items AS cli2 ON cli.item_id = cli2.parent_id
			WHERE 
				cli.parent_id IS NULL and cli.list_id IN (".join(',', $va_hierarchy_ids).")
			GROUP BY
				cli.item_id
		");
		
		while ($qr_res->nextRow()) {
			$vn_hierarchy_id = $qr_res->get('list_id');
			$va_hierarchies[$vn_hierarchy_id]['list_id'] = $qr_res->get('list_id');		// when we need to edit the list
			$va_hierarchies[$vn_hierarchy_id]['item_id'] = $qr_res->get('item_id');	
			
			$qr_children = $o_db->query("
				SELECT count(*) children
				FROM ca_list_items cli
				WHERE 
					cli.parent_id = ?
			", (int)$qr_res->get('item_id'));
			$vn_children_count = 0;
			if ($qr_children->nextRow()) {
				$vn_children_count = $qr_children->get('children');
			}
			$va_hierarchies[$vn_hierarchy_id]['children'] = intval($vn_children_count);
			$va_hierarchies[$vn_hierarchy_id]['has_children'] = ($vn_children_count > 0) ? 1 : 0;
		}
		
		// sort by label
		$va_hierarchies_indexed_by_label = array();
		foreach($va_hierarchies as $vs_k => $va_v) {
			$va_hierarchies_indexed_by_label[$va_v['name']][$vs_k] = $va_v;
		}
		ksort($va_hierarchies_indexed_by_label);
		$va_sorted_hierarchies = array();
		foreach($va_hierarchies_indexed_by_label as $vs_l => $va_v) {
			foreach($va_v as $vs_k => $va_hier) {
				$va_sorted_hierarchies[$vs_k] = $va_hier;
			}
		}
		return $va_sorted_hierarchies;
	 }
	 # ------------------------------------------------------
	 /**
	 * Returns name of hierarchy for currently loaded item or, if specified, item with item_id = to optional $pn_id parameter
	 */
	 public function getHierarchyName($pn_id=null) {
	 	if ($pn_id) {
	 		$t_item = new ca_list_items($pn_id);
	 		$vn_hierarchy_id = $t_item->get('list_id');
	 	} else {
	 		$vn_hierarchy_id = $this->get('list_id');
	 	}
	 	$t_list = new ca_lists();
	 	$t_list->load($vn_hierarchy_id);
	 	
	 	return $t_list->getLabelForDisplay(false);
	 }
	 # ------------------------------------------------------
	/**
	 * Returns a flat list of all items in the specified list referenced by items in the specified table
	 * (and optionally a search on that table)
	 */
	public function getReferenced($pm_table_num_or_name, $pn_type_id=null, $pa_reference_limit_ids=null, $pn_access=null, $pn_restrict_to_relationship_hierarchy_id=null) {
		if (is_numeric($pm_table_num_or_name)) {
			$vs_table_name = $this->getAppDataModel()->getTableName($pm_table_num_or_name);
		} else {
			$vs_table_name = $pm_table_num_or_name;
		}
		
		if (!($t_ref_table = $this->getAppDatamodel()->getInstanceByTableName($vs_table_name, true))) {
			return null;
		}
		
		
		if (!$vs_table_name) { return null; }
		
		$o_db = $this->getDb();
		$va_path = $this->getAppDatamodel()->getPath($this->tableName(), $vs_table_name);
		array_shift($va_path); // remove table name from path
		
		$vs_last_table = $this->tableName();
		$va_joins = array();
		foreach($va_path as $vs_rel_table_name => $vn_rel_table_num) {
			$va_rels = $this->getAppDatamodel()->getRelationships($vs_last_table, $vs_rel_table_name);
			$va_rel = $va_rels[$vs_last_table][$vs_rel_table_name][0];
			
			
			$va_joins[] = "INNER JOIN {$vs_rel_table_name} ON {$vs_last_table}.".$va_rel[0]." = {$vs_rel_table_name}.".$va_rel[1];
			
			$vs_last_table = $vs_rel_table_name;
		}
		
		$va_sql_wheres = array();
		if (is_array($pa_reference_limit_ids) && sizeof($pa_reference_limit_ids)) {
			$va_sql_wheres[] = "({$vs_table_name}.".$t_ref_table->primaryKey()." IN (".join(',', $pa_reference_limit_ids)."))";
		}
		
		if (!is_null($pn_access)) {
			$va_sql_wheres[] = "({$vs_table_name}.access = ".intval($pn_access).")";
		}
		
		if ($pn_restrict_to_relationship_hierarchy_id > 0) {
			$va_sql_wheres[] = "(ca_list_items.list_id = {$pn_restrict_to_relationship_hierarchy_id})";
		}
		
		// get item counts
		$vs_sql = "
			SELECT ca_list_items.item_id, count(*) cnt
			FROM ca_list_items
			".join("\n", $va_joins)."
			".(sizeof($va_sql_wheres) ? " WHERE ".join(' AND ', $va_sql_wheres) : "")."
			GROUP BY
				ca_list_items.item_id, {$vs_table_name}.".$t_ref_table->primaryKey()."
		";
		$qr_items = $o_db->query($vs_sql);
		
		$va_item_counts = array();
		while($qr_items->nextRow()) {
			$va_item_counts[$qr_items->get('item_id')]++;
		}
		
		$vs_sql = "
			SELECT ca_list_items.item_id, ca_list_items.idno, ca_list_item_labels.*, count(*) c
			FROM ca_list_items
			INNER JOIN ca_list_item_labels ON ca_list_item_labels.item_id = ca_list_items.item_id
			".join("\n", $va_joins)."
			WHERE
				(ca_list_item_labels.is_preferred = 1)
				".(sizeof($va_sql_wheres) ? " AND ".join(' AND ', $va_sql_wheres) : "")."
			GROUP BY
				ca_list_item_labels.label_id
			ORDER BY 
				ca_list_item_labels.name_plural
		";
		
		$qr_items = $o_db->query($vs_sql);
		
		$va_items = array();
		while($qr_items->nextRow()) {
			$vn_item_id = $qr_items->get('item_id');
			$va_items[$vn_item_id][$qr_items->get('locale_id')] = array_merge($qr_items->getRow(), array('cnt' => $va_item_counts[$vn_item_id]));
		}
		
		return caExtractValuesByUserLocale($va_items);
	}
	# ------------------------------------------------------
}
?>
