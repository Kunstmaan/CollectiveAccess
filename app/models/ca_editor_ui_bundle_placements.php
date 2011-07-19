<?php
/** ---------------------------------------------------------------------
 * app/models/ca_editor_ui_bundle_placements.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2010 Whirl-i-Gig
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


BaseModel::$s_ca_models_definitions['ca_editor_ui_bundle_placements'] = array(
 	'NAME_SINGULAR' 	=> _t('UI bundle placement'),
 	'NAME_PLURAL' 		=> _t('UI bundle placements'),
 	'FIELDS' 			=> array(
 		'placement_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Placement id', 'DESCRIPTION' => 'Identifier for Placement'
		),
		'screen_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Screen id', 'DESCRIPTION' => 'Identifier for Screen'
		),
		'bundle_name' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Bundle name'), 'DESCRIPTION' => _t('Bundle name'),
				'BOUNDS_VALUE' => array(1,255)
		),
		'placement_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Placement code'), 'DESCRIPTION' => _t('Unique code for placement of this bundle on this screen.'),
				'BOUNDS_VALUE' => array(0,255)
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Settings')
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('Sort order')
		)
 	)
);

global $_ca_editor_ui_bundle_placement_settings;
$_ca_editor_ui_bundle_placement_settings = array(		// global
		'restrict_to_type' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 30, 'height' => 1,
			'default' => 0,
			'label' => _t('Restrict this bundle to lookups on the specified type (for bundles doing lookups on typed items - eg. objects, places, entities, et al)'),
			'description' => _t('Set to the list item code of the type of the typed lookup item to limit lookups to.')
		),
		'restrict_to_list' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 30, 'height' => 1,
			'default' => 0,
			'label' => _t('Restrict this bundle to handling the specified list (for bundles doing lookups on lists)'),
			'description' => _t('Set to the list code of the list to limit lookups to, for bundles that do lookups on lists and vocabularies.')
		),
		'restrict_to_relationship_types' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 30, 'height' => 1,
			'default' => 0,
			'label' => _t('Restrict this bundle to handling the specified list (for bundles doing lookups on lists)'),
			'description' => _t('Only list relationships with the specified types. Set this to a list of type codes.')
		),
		'label' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 70, 'height' => 2,
			'default' => 'New label',
			'label' => _t('Alternate label to place on bundle'),
			'description' => _t('Custom label text to use for this placement of this bundle.')
		),
		'add_label' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 70, 'height' => 2,
			'default' => 'Add new',
			'label' => _t('Alternate label to place on bundle add button'),
			'description' => _t('Custom text to use for the add button for this placement of this bundle.')
		),
		'description' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 70, 'height' => 2,
			'default' => '',
			'label' => _t('Descriptive text for bundle.'),
			'description' => _t('Descriptive text to use for help for bundle. Will override descriptive text set for underlying metadata element, if set.')
		)
	);


class ca_editor_ui_bundle_placements extends BaseModel {
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
	protected $TABLE = 'ca_editor_ui_bundle_placements';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'placement_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('bundle_name');

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
	protected $ORDER_BY = array('placement_id');

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = 'rank';
	
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
	protected $LABEL_TABLE_NAME = null;
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	/**
	 * Settings delegate - implements methods for setting, getting and using 'settings' var field
	 */
	public $SETTINGS;
	
	# ----------------------------------------
	function __construct($pn_id=null) {
		global $_ca_editor_ui_bundle_placement_settings;
		parent::__construct($pn_id);
		
		$this->SETTINGS = new ModelSettings($this, 'settings', $_ca_editor_ui_bundle_placement_settings);
	}
	# ------------------------------------------------------
	public function __destruct() {
		unset($this->SETTINGS);
	}
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
	# ----------------------------------------
}
?>