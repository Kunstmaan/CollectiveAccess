<?php
/** ---------------------------------------------------------------------
 * app/models/ca_bundle_mappings.php
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
 * @subpackage models
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
 /**
   *
   */

require_once(__CA_LIB_DIR__.'/core/ModelSettings.php'); 
require_once(__CA_MODELS_DIR__.'/ca_bundle_mapping_relationships.php'); 

global $_ca_bundle_mappings_settings;
$_ca_bundle_mappings_settings = array(		// global
		
	);
	

BaseModel::$s_ca_models_definitions['ca_bundle_mappings'] = array(
 	'NAME_SINGULAR' 	=> _t('bundle mapping'),
 	'NAME_PLURAL' 		=> _t('bundle mappings'),
 	'FIELDS' 			=> array(
		'mapping_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Mapping id', 'DESCRIPTION' => 'Identifier for Mapping'
		),
		'direction' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_SELECT,
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 'E',
				'LABEL' => _t('Direction'), 'DESCRIPTION' => _t('Direction of mapping. "Import" indicates the mapping is <i>from</i> an external source into CollectiveAccess; "export" means the mapping is from CollectiveAccess to an external target.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('Import') => 'I',
					_t('Export') => 'E',
					_t('Both') => 'X',
					_t('Fragment') => 'F'
				)
		),
		'target' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Target'), 'DESCRIPTION' => _t('Code indicating import/export data format (Ex. EAD, METS)'),
				'BOUNDS_VALUE' => array(1,100)
		),
		'mapping_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Mapping code'), 'DESCRIPTION' => _t('Unique identifer for this mapping.'),
				'BOUNDS_VALUE' => array(0,100)
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Mapping table'), 'DESCRIPTION' => _t('CollectiveAccess table to which data is being imported of exported.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('Objects') => 57,
					_t('Object lots') => 51,
					_t('Entities') => 20,
					_t('Places') => 72,
					_t('Occurrences') => 67,
					_t('Collections') => 13,
					_t('Storage locations') => 89,
					_t('List items') => 33
				),
				'BOUNDS_LENGTH' => array(1,100)
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
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if mapping is accessible to the public or not. ')
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Mapping settings')
		)
	)
);

class ca_bundle_mappings extends LabelableBaseModelWithAttributes {
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
	protected $TABLE = 'ca_bundle_mappings';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'mapping_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('mapping_id');

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
	protected $ORDER_BY = array('mapping_id');

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = '';
	
	# ------------------------------------------------------
	# Hierarchical table properties
	# ------------------------------------------------------
	protected $HIERARCHY_TYPE				=	null;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	null;
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	null;
	protected $HIERARCHY_PARENT_ID_FLD		=	null;
	protected $HIERARCHY_DEFINITION_TABLE	=	null;
	protected $HIERARCHY_ID_FLD				=	null;
	protected $HIERARCHY_POLY_TABLE			=	null;
	
	# ------------------------------------------------------
	# Change logging
	# ------------------------------------------------------
	protected $UNIT_ID_FIELD = null;
	protected $LOG_CHANGES_TO_SELF = false;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
		
		),
		"RELATED_TABLES" => array(
		
		)
	);	
	
	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_bundle_mapping_labels';
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	/**
	 * Settings delegate - implements methods for setting, getting and using 'settings' var field
	 */
	public $SETTINGS;
	
	# ----------------------------------------
	public function __construct($pn_id=null) {
		global $_ca_bundle_mappings_settings;
		parent::__construct($pn_id);
		
		//
		$this->SETTINGS = new ModelSettings($this, 'settings', $_ca_bundle_mappings_settings);
	}
	# ------------------------------------------------------
	public function __destruct() {
		unset($this->SETTINGS);
	}
	# ------------------------------------------------------
	# Mapping settings
	# ------------------------------------------------------
	/**
	 *
	 */
	public function addRelationship($ps_bundle_name, $ps_destination, $ps_group_code, $pn_type_id, $pa_settings) {
		if (!($vn_mapping_id = $this->getPrimaryKey())) { return null; }
		
		$t_relationship = new ca_bundle_mapping_relationships();
		$t_relationship->setMode(ACCESS_WRITE);
		$t_relationship->set('mapping_id', $vn_mapping_id);
		$t_relationship->set('bundle_name', $ps_bundle_name);
		$t_relationship->set('destination', $ps_destination);
		$t_relationship->set('group_code', $ps_group_code);
		$t_relationship->set('type_id', $pn_type_id);
		
		foreach($pa_settings as $vs_key => $vs_value) {
			$t_relationship->setSetting($vs_key, $vs_value);
		}
		
		$t_relationship->insert();
		
		if ($t_relationship->numErrors()) {
			$this->errors = array_merge($this->errors, $t_relationship->errors);
			return false;
		}
		return $t_relationship->getPrimaryKey();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function removeRelationship($pn_relation_id) {
		if (!($vn_mapping_id = $this->getPrimaryKey())) { return null; }
		
		$t_relationship = new ca_bundle_mapping_relationships($pn_relation_id);
		if ($t_relationship->getPrimaryKey() && ($t_relationship->get('mapping_id') == $vn_mapping_id)) {
			$t_relationship->setMode(ACCESS_WRITE);
			$t_relationship->delete(true);
			
			if ($t_relationship->numErrors()) {
				$this->errors = array_merge($this->errors, $t_relationship->errors);
				return false;
			}
			return true;
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getRelationships($pn_type_id=null) {
		if (!($vn_mapping_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDb();
		
		$vs_type_sql = "";
		if ($pn_type_id = (int)$pn_type_id) {
			$vs_type_sql = " AND (type_id IS NULL OR type_id = {$pn_type_id})";
		}
		
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_bundle_mapping_relationships
			WHERE
				mapping_id = ? {$vs_type_sql}
		", (int)$vn_mapping_id);
		
		$va_rels = array();
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			$va_row['settings'] = caUnserializeForDatabase($qr_res->get('settings'));
			$va_rels[] = $va_row;
		}
		
		return $va_rels;
	}
	# ------------------------------------------------------
	# Settings
	# ------------------------------------------------------
	/**
	 * Reroutes calls to method implemented by settings delegate to the delegate class
	 */
	public function __call($ps_name, $pa_arguments) {
		if (method_exists($this->SETTINGS, $ps_name)) {
			return call_user_func_array(array($this->SETTINGS, $ps_name), $pa_arguments);
		}
		die($this->tableName()." does not implement method {$ps_name}");
	}
	# ------------------------------------------------------	
	/**
	 *
	 */
	public function getAvailableMappings($pm_table_name_or_num, $pa_directions) {
		$o_dm = Datamodel::load();
		if (!($vn_table_num = $o_dm->getTableNum($pm_table_name_or_num))) {
			return null;
		}
		
		if (!is_array($pa_directions)) {
			$pa_directions = array($pa_directions);
		}
		
		$va_directions = array();
		
		foreach($pa_directions as $ps_direction) {
			$ps_direction = strtoupper($ps_direction);
			if (!in_array($ps_direction, array('I', 'E', 'X'))) {
				$ps_direction = 'X';
			}
			$va_directions["'".$ps_direction."'"] = true;
		}
		
		$vs_direction_sql = '';
		if (sizeof($va_directions)) {
			$vs_direction_sql = ' AND (direction IN ('.join(', ', array_keys($va_directions)).'))';
		}
		
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT cbm.*, cbml.name, cbml.locale_id
			FROM ca_bundle_mappings cbm
			LEFT JOIN ca_bundle_mapping_labels AS cbml ON cbm.mapping_id = cbml.mapping_id 
			WHERE
				table_num = ? {$vs_direction_sql}
		", (int)$vn_table_num);
		
		$va_mappings = array();
		while($qr_res->nextRow()) {
			$va_mappings[$qr_res->get('mapping_code')][$qr_res->get('locale_id')] = $va_mappings[$qr_res->get('mapping_id')][$qr_res->get('locale_id')] = $qr_res->getRow();
		}
		
		return caExtractValuesByUserLocale($va_mappings);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function mappingIsAvailable($pm_mapping_code_or_id) {
		$t_chk = new ca_bundle_mappings();
		
		if (is_numeric($pm_mapping_code_or_id)) {
			$vb_exists = $t_chk->load($pm_mapping_code_or_id);
		} else {
			$vb_exists = $t_chk->load(array('mapping_code' => $pm_mapping_code_or_id));
		}
		
		return ($vb_exists ? $t_chk : false);
	}
	# -------------------------------------------------------
}
?>