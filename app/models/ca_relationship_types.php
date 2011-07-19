<?php
/** ---------------------------------------------------------------------
 * app/models/ca_relationship_types.php : table access class for table ca_relationship_types
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


BaseModel::$s_ca_models_definitions['ca_relationship_types'] = array(
 	'NAME_SINGULAR' 	=> _t('relationship type'),
 	'NAME_PLURAL' 		=> _t('relationship types'),
 	'FIELDS' 			=> array(
 		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Type id', 'DESCRIPTION' => 'Unique identifier for relationship type'
		),
		'sub_type_left_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Type restriction for "left" side of relationship'), 'DESCRIPTION' => _t('Type restriction for "left" side of relationship (eg. if relationship is object ⇔ entity, then the restriction controls for which types of objects this relationship type is valid.')
		),
		'sub_type_right_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Type restriction for "right" side of relationship'), 'DESCRIPTION' => _t('Type restriction for "right" side of relationship (eg. if relationship is object ⇔ entity, then the restriction controls for which types of entities this relationship type is valid.')
		),
		'parent_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true,
				'DEFAULT' => '',
				'LABEL' => 'Parent', 'DESCRIPTION' => 'Identifier of parent relationship type; is null if relationship type is root of hierarchy.',
				'DONT_USE_AS_BUNDLE' => true
		),
		'hier_type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true,
				'DEFAULT' => '',
				'LABEL' => _t('Relationship type hierarchy identifier'), 'DESCRIPTION' => _t('Identifier of relationship_type that is root of the relationship_type hierarchy.'),
				'DONT_USE_AS_BUNDLE' => true
		),
		'type_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Relationship type code'), 'DESCRIPTION' => _t('Unique identifier for the relationship type; must be unique for relationship types for the current relationship.'),
				'BOUNDS_VALUE' => array(1,100),
				'UNIQUE_WITHIN' => array('table_num')
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Relationship'), 'DESCRIPTION' => _t('Indicates the type of relationship for which this type is applicable.'),
				'BOUNDS_VALUE' => array(0,255),
				'DONT_USE_AS_BUNDLE' => true
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('The relative priority of the relationship type when displayed in a list with other relationship types. Lower numbers indicate higher priority.'),
				'BOUNDS_VALUE' => array(0,65535)
		),
		'is_default' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Is default?'), 'DESCRIPTION' => _t('If checked this relationship type will be the default type for the relationship.')
		),
		'hier_left' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => 'Hierarchical index - left bound', 'DESCRIPTION' => 'Left-side boundary for nested set-style hierarchical indexing; used to accelerate search and retrieval of hierarchical record sets.',
				'DONT_USE_AS_BUNDLE' => true
		),
		'hier_right' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => 'Hierarchical index - right bound', 'DESCRIPTION' => 'Right-side boundary for nested set-style hierarchical indexing; used to accelerate search and retrieval of hierarchical record sets.',
				'DONT_USE_AS_BUNDLE' => true
		)
 	)
);

class ca_relationship_types extends BundlableLabelableBaseModelWithAttributes {
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
	protected $TABLE = 'ca_relationship_types';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'type_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('typename');

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
	protected $ORDER_BY = array('typename');

	# Maximum number of record to display per page in a listing
	protected $MAX_RECORDS_PER_PAGE = 20; 

	# How do you want to page through records in a listing: by number pages ordered
	# according to your setting above? Or alphabetically by the letters of the first
	# LIST_FIELD?
	protected $PAGE_SCHEME = 'alpha'; # alpha [alphabetical] or num [numbered pages; default]

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = '';
	
	
	# ------------------------------------------------------
	# Hierarchical table properties
	# ------------------------------------------------------
	protected $HIERARCHY_TYPE				=	__CA_HIER_TYPE_ADHOC_MONO__;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	'hier_left';
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	'hier_right';
	protected $HIERARCHY_PARENT_ID_FLD		=	'parent_id';
	protected $HIERARCHY_DEFINITION_TABLE	=	'ca_relationship_types';
	protected $HIERARCHY_ID_FLD				=	'hier_type_id';
	protected $HIERARCHY_POLY_TABLE			=	null;
	
	# ------------------------------------------------------
	# Change logging
	# ------------------------------------------------------
	protected $UNIT_ID_FIELD = null;
	protected $LOG_CHANGES_TO_SELF = true;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
		
		),
		"RELATED_TABLES" => array(
		
		)
	);
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = null;					// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = null;		// list code (ca_lists.list_code) of list defining types for this table
	
	
	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_relationship_type_labels';
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'RelationshipTypeSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'RelationshipTypeSearchResult';
	
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
		
		// 
		if ($pn_id) { $this->loadSubtypeLists();}
	}
	# ------------------------------------------------------
	public function load($pm_id=null) {
		if ($vn_rc = parent::load($pm_id)) {
			$this->loadSubtypeLists();
			return $vn_rc;
		}
		
		$this->FIELDS['sub_type_left_id']['BOUNDS_CHOICE_LIST'] = $this->FIELDS['sub_type_right_id']['BOUNDS_CHOICE_LIST'] = array();
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Load sub_type_left_id and sub_type_right_id fields with type lists appropriate to relationship table
	 * indicated by table_num field
	 */
	protected function loadSubtypeLists() {
		$va_left_type_list = array();
		$va_right_type_list = array();
		if ($vn_table_num = $this->get('table_num')) {
			$va_relationship_tables = $this->getRelationshipsUsingTypes();
			if (!isset($va_relationship_tables[$vn_table_num])) { return null; }
			$o_dm = $this->getAppDatamodel();
			
			$t_rel_instance = $o_dm->getInstanceByTableNum($vn_table_num, true);
			$t_instance = $o_dm->getInstanceByTableName($t_rel_instance->getLeftTableName(), true);
			
			
			if (method_exists($t_instance, 'getTypeList')) {
				$va_types = $t_instance->getTypeList();
				
				foreach($va_types as $vn_type_id => $va_type_info) {
					$va_left_type_list[$va_type_info['name_plural']] = $vn_type_id;
				}
			}
			
			$t_instance = $o_dm->getInstanceByTableName($t_rel_instance->getRightTableName(), true);
			
			if (method_exists($t_instance, 'getTypeList')) {
				$va_types = $t_instance->getTypeList();
			
				foreach($va_types as $vn_type_id => $va_type_info) {
					$va_right_type_list[$va_type_info['name_plural']] = $vn_type_id;
				}
			}
		}
				
		$this->FIELDS['sub_type_left_id']['DISPLAY_TYPE'] = DT_SELECT;
		$this->FIELDS['sub_type_left_id']['BOUNDS_CHOICE_LIST'] = $va_left_type_list;
		
		$this->FIELDS['sub_type_right_id']['DISPLAY_TYPE'] = DT_SELECT;
		$this->FIELDS['sub_type_right_id']['BOUNDS_CHOICE_LIST'] = $va_right_type_list;
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions() {
		parent::initLabelDefinitions();
		$this->BUNDLES['hierarchy_navigation'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Hierarchy navigation'));
		$this->BUNDLES['hierarchy_location'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Location in hierarchy'));
	}
	# ------------------------------------------------------
	/**
	 * Return information, including typenames filterd by user locale, for relationship types for the 
	 * specified relationship table (eg. ca_objects_x_entities, ca_entities_x_occurrences).
	 *
	 * Returns array keyed on relationship type_id; values are associative arrays keys on ca_relationship_types/ca_relationship_type_labels field names
	 */
	public function getRelationshipInfo($pm_table_name_or_num, $ps_type_code=null) {
		if (!is_numeric($pm_table_name_or_num)) {
			$vn_table_num = $this->getAppDatamodel()->getTableNum($pm_table_name_or_num);
		} else {
			$vn_table_num = $pm_table_name_or_num;
		}
		
		$vs_type_sql = '';
		if ($ps_type_code) {
			$vs_type_sql = " AND (crt.type_code = '".$this->getDb()->escape($ps_type_code)."')";
		}
		
		$qr_res = $this->getDb()->query("
			SELECT *
			FROM ca_relationship_types crt
			INNER JOIN ca_relationship_type_labels AS crtl ON crt.type_id = crtl.type_id
			WHERE
				(crt.table_num = ?) {$vs_type_sql}
		", (int)$vn_table_num);
		
		$va_relationships = array();
		while ($qr_res->nextRow()) {
			$va_relationships[$qr_res->get('type_id')][$qr_res->get('locale_id')] = $qr_res->getRow();
		}
		return caExtractValuesByUserLocale($va_relationships);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getRelationshipTypeID($pm_table_name_or_num, $ps_type_code) {
		if ($va_relationships = $this->getRelationshipInfo($pm_table_name_or_num, $ps_type_code)) {
			foreach($va_relationships as $vn_type_id => $va_type_info) {
				return $vn_type_id;
			}
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Returns array of tables that use relationship types
	 *
	 * @return array List of tables that use relationship types
	 */ 
	public function getRelationshipsUsingTypes() {
	 	$va_tables = $this->_DATAMODEL->getTableNames();
		$va_relationship_tables = array();
	 	foreach($va_tables as $vs_table) {
	 		if (preg_match('!_x_!', $vs_table)) {
	 			$t_instance = $this->_DATAMODEL->getInstanceByTableName($vs_table, true);
	 			if (!$t_instance->hasField('type_id')) { continue; }	// some relationships don't use types (eg. ca_users_x_roles)
	 			$vs_name = $t_instance->getProperty('NAME_PLURAL');
	 			$va_relationship_tables[$t_instance->tableNum()] = array('name' => $vs_name);
	 		}
	 	}
	 	return $va_relationship_tables;
	 }
	# ------------------------------------------------------
	/**
	 * Returns name of many-to-many table using relationship types between two tables
	 *
	 * @param string $ps_table1 A valid table name
	 * @param string $ps_table2 A valid table name
	 * @return string The name of a table relating the specified tables 
	 */
	 public function getRelationshipTypeTable($ps_table1, $ps_table2) {
	 	switch(sizeof($va_path = array_keys($this->getAppDatamodel()->getPath($ps_table1, $ps_table2)))) {
			case 3:
				return $va_path[1];
				break;
		}
		
		return null;
	 }
	 # ------------------------------------------------------
	/**
	 * Converts a list of relationship type_code string and/or numeric type_ids to a list of numeric type_ids
	 *
	 * @param string $ps_relationship_table The name of the relationship table that the types are valid for (Eg. ca_objects_x_entities)
	 * @param array $pa_list A list of relationship type_code string and/or numeric type_ids
	 * @param array $pa_options Optional array of options. Support options are:
	 *			includeChildren = If set to true, ids of children of relationship types are included in the returned values
	 * @return array A list of corresponding type_ids 
	 */
	 public function relationshipTypeListToIDs($ps_relationship_table, $pa_list, $pa_options=null) {
	 	$va_rel_ids = array();
		foreach($pa_list as $vm_type) {
			if ($vn_type_id = $this->getRelationshipTypeID($ps_relationship_table, $vm_type)) {
				$va_rel_ids[] = $vn_type_id;
			}
		}
		
		if (isset($pa_options['includeChildren']) && $pa_options['includeChildren']) {
			$va_children = $va_rel_ids;
			
			foreach($va_rel_ids as $vn_id) {
				$va_children = array_merge($va_children, $this->getHierarchyChildren($vn_id, array('idsOnly' => true)));
			}
			$va_rel_ids= array_keys(array_flip($va_children));
		}
		
		return $va_rel_ids;
	}
	# ------------------------------------------------------
	 public function getHierarchyList($pb_vocabularies=false) {
	 	$va_relationship_tables = $this->getRelationshipsUsingTypes();
		if (!sizeof($va_relationship_tables)) { return array(); }
		
		$o_db = $this->getDb();
		
		// get root for each hierarchy
		$qr_res = $o_db->query("
			SELECT rt.type_id, rt.table_num
			FROM ca_relationship_types rt
			WHERE 
				rt.parent_id IS NULL and rt.table_num IN (".join(',', array_keys($va_relationship_tables)).")
		");
		
		$va_hierarchies = array();
		while ($qr_res->nextRow()) {
			$vn_type_id = $qr_res->get('type_id');
			//$va_hierarchies[$vn_type_id]['table_num'] = $qr_res->get('table_num');	
			$va_hierarchies[$vn_type_id]['type_id'] = $vn_type_id;	
			$va_hierarchies[$vn_type_id]['name'] = $va_relationship_tables[$qr_res->get('table_num')]['name'];	
			
			$qr_children = $o_db->query("
				SELECT count(*) children
				FROM ca_relationship_types rt
				WHERE 
					rt.parent_id = ?
			", (int)$vn_type_id);
			$vn_children_count = 0;
			if ($qr_children->nextRow()) {
				$vn_children_count = $qr_children->get('children');
			}
			$va_hierarchies[$vn_type_id]['children'] = intval($vn_children_count);
			$va_hierarchies[$vn_type_id]['has_children'] = ($vn_children_count > 0) ? 1 : 0;
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
	 	return _t('Relationship types');
	 }
	# ------------------------------------------------------
	/**
	 * Override insert() to set table_num to whatever the parent is
	 */
	public function insert($pa_options=null) {
		$t_root_rel_type = new ca_relationship_types($this->get('parent_id'));
		$this->set('table_num', $t_root_rel_type->get('table_num'));
		return parent::insert($pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Override update() to set table_num to whatever the parent is
	 */
	public function update($pa_options=null) {
		$t_root_rel_type = new ca_relationship_types($this->get('parent_id'));
		$this->set('table_num', $t_root_rel_type->get('table_num'));
		return parent::update($pa_options);
	}
	# ------------------------------------------------------
}
?>