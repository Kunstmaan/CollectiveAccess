<?php
/** ---------------------------------------------------------------------
 * app/models/ca_editor_uis.php
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
require_once(__CA_MODELS_DIR__.'/ca_editor_ui_screens.php');


BaseModel::$s_ca_models_definitions['ca_editor_uis'] = array(
 	'NAME_SINGULAR' 	=> _t('editor UI'),
 	'NAME_PLURAL' 		=> _t('editor UIs'),
 	'FIELDS' 			=> array(
 		'ui_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Ui id', 'DESCRIPTION' => 'Identifier for Ui'
		),
		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'User id', 'DESCRIPTION' => 'Identifier for User'
		),
		'is_system_ui' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Is system UI?'), 'DESCRIPTION' => _t('If set, user interface will be available to all users.'),
				'REQUIRES' => array('is_administrator')
		),
		'editor_type' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT,
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Editor type'), 'DESCRIPTION' => _t('Type of item this editor UI operates on.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('objects') => 57,
					_t('object lots') => 51,
					_t('entities') => 20,
					_t('places') => 72,
					_t('occurrences') => 67,
					_t('collections') => 13,
					_t('storage locations') => 89,
					_t('loans') => 133,
					_t('movements') => 137,
					_t('tours') => 153,
					_t('tour stops') => 155,
					_t('object events') => 45,
					_t('object representations') => 56,
					_t('representation annotations') => 82,
					_t('object lot events') => 38,
					_t('sets') => 103,
					_t('set items') => 105,
					_t('lists') => 36,
					_t('list items') => 33,
					_t('search forms') => 121,
					_t('displays') => 124,
					_t('relationship types') => 79
				)
		),
		'color' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_COLORPICKER, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Color'), 'DESCRIPTION' => _t('Color to identify the editor UI with')
		),
		'icon' => array(
				'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				"MEDIA_PROCESSING_SETTING" => 'ca_icons',
				'LABEL' => _t('Icon'), 'DESCRIPTION' => _t('Optional icon to identify the editor UI with')
		)
 	)
);

class ca_editor_uis extends LabelableBaseModelWithAttributes {
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
	protected $TABLE = 'ca_editor_uis';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'ui_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('ui_id');

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
	protected $ORDER_BY = array('ui_id');

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
	# Group-based access control
	# ------------------------------------------------------
	protected $USERS_RELATIONSHIP_TABLE = 'ca_editor_uis_x_users';
	protected $USER_GROUPS_RELATIONSHIP_TABLE = 'ca_editor_uis_x_user_groups';
	
	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_editor_ui_labels';
	
	
	static $s_available_ui_cache = array();
	static $s_screen_info_cache = array();
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	# ----------------------------------------
	public function __construct($pn_id=null) {
		parent::__construct($pn_id);
	}
	# ------------------------------------------------------
	public function insert($pa_options=null) {
		$vn_rc = parent::insert($pa_options);
		
		if ($this->getPrimaryKey()) {
			// create root in ca_list_items
			$t_item_root = new ca_editor_ui_screens();
			$t_item_root->setMode(ACCESS_WRITE);
			$t_item_root->set('ui_id', $this->getPrimaryKey());
			$t_item_root->set('is_default', 0);
			$t_item_root->insert();
			
			if ($t_item_root->numErrors()) {
				$this->delete();
				$this->errors = array_merge($this->errors, $t_item_root->errors);
			}
		}
		
		return $vn_rc;
	}
	# ----------------------------------------
	/**
	 * Values for $ps_type:
	 *		'ca_objects' = objects editor
	 *		'ca_entities' = entities editor
	 *		.. etc ..
	 */
	public function loadDefaultUI($ps_type, $po_request) {
		if (!$this->load($po_request->user->getPreference('cataloguing_'.$ps_type.'_editor_ui'))) {
			if (!($vn_type = $this->getAppDatamodel()->getTableNum($ps_type))) { return false; }
			$va_ui_ids = ca_editor_uis::getAvailableUIs($vn_type, $po_request->getUserID());
			
			if (sizeof($va_ui_ids) == 0) { return false; }
			$va_tmp = array_keys($va_ui_ids);
			return $this->load($va_tmp[0]); 
		}
		return true;
	}
	# ----------------------------------------
	#
	# ----------------------------------------
	/**
		Returns list of screen for a given UI. 
		
		TODO: 	Needs to handle screens hierarchcally - right now it just returns 
				a flat list in rank order without regard to hierarchical nesting
	 */
	public function getScreens($po_request=null, $pn_type_id=null) {
		if (!$this->getPrimaryKey()) { return false; }
		if (ca_editor_uis::$s_screen_info_cache[$this->getPrimaryKey().'/'.$pn_type_id]) { return ca_editor_uis::$s_screen_info_cache[$this->getPrimaryKey().'/'.$pn_type_id]; }
		
		$o_db = $this->getDb();
		
		$vs_type_sql = ((int)$pn_type_id) ? "AND (ceustr.type_id IS NULL OR ceustr.type_id = ".intval($pn_type_id).")" : '';
	
		$qr_res = $o_db->query("
			SELECT ceus.*, ceusl.*
			FROM ca_editor_ui_screens ceus
			INNER JOIN ca_editor_ui_screen_labels AS ceusl ON ceus.screen_id = ceusl.screen_id
			LEFT JOIN ca_editor_ui_screen_type_restrictions AS ceustr ON ceus.screen_id = ceustr.screen_id
			WHERE
				(ceus.ui_id = ?) {$vs_type_sql}
			ORDER BY 
				ceus.rank, ceus.screen_id
		", (int)$this->getPrimaryKey());
		
		$va_screens = array();
		
		while($qr_res->nextRow()) {
			$va_screens[$qr_res->get('screen_id')][$qr_res->get('locale_id')] = $qr_res->getRow();
		}
		return ca_editor_uis::$s_screen_info_cache[$this->getPrimaryKey().'/'.$pn_type_id] = caExtractValuesByUserLocale($va_screens);
	}
	# ----------------------------------------
	/**
	  * Return number of screens configured for currently loaded UI 
	  *
	  * @param int $pn_type_id Optional type_id used when and per-screen type restrictions are enforced; if not set (the default) then all screens are returned - no type restrictions are enforced.
	  * @return int Number of screens configured for the current UI
	  */
	public function getScreenCount($pn_type_id=null) {
		if (!$this->getPrimaryKey()) { return false; }
		if (ca_editor_uis::$s_screen_info_cache[$this->getPrimaryKey().'/'.$pn_type_id]) { return sizeof(ca_editor_uis::$s_screen_info_cache[$this->getPrimaryKey().'/'.$pn_type_id]); }
		
		return sizeof($this->getScreens(null, $pn_type_id));
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getScreenBundlePlacements($pm_screen) {
		if (!$this->getPrimaryKey()) { return false; }
		
		$o_db = $this->getDb();
		
		
		$vn_screen_id = intval(str_replace('Screen', '', $pm_screen));
		
		$va_bundles = array();
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_editor_ui_bundle_placements ceuibp
			INNER JOIN ca_editor_ui_screens AS ceus ON ceus.screen_id = ceuibp.screen_id
			WHERE
				(ceus.ui_id = ?) AND (ceuibp.screen_id = ?)
			ORDER BY 
				ceuibp.rank
		", (int)$this->getPrimaryKey(), (int)$vn_screen_id);
		
		$va_placements = array();
		while ($qr_res->nextRow()) {
			$va_tmp = $qr_res->getRow();
			$va_tmp['settings'] = $qr_res->getVars('settings');
			$va_placements[] = $va_tmp;
		}
		
		return $va_placements;
	}
	# ----------------------------------------
	/**
	 * Returns screen name for the first screen in the currently loaded UI 
	 * that contains the bundle named by $ps_bundle_name
	 */
	public function getScreenWithBundle($ps_bundle_name, $po_request=null) {
		if (!$this->getPrimaryKey()) { return null; }
		
		foreach($this->getScreens($po_request) as $va_screen) {
			$vn_screen_id = $va_screen['screen_id'];
			$va_placements = $this->getScreenBundlePlacements('Screen'.$vn_screen_id);
			
			foreach($va_placements as $va_placement) {
				if ($va_placement['bundle_name'] === $ps_bundle_name) {
					return 'Screen'.$vn_screen_id;
				}
			}
		}
		return false;
	}
	# ----------------------------------------
	/**
		Return navigation configuration fragment suitable for insertion into the navigation.conf structure.
		Can be used by lib/core/AppNavigation to dynamically insert navigation for screens into navigation tree
	 */
	public function getScreensAsNavConfigFragment($po_request, $pn_type_id, $ps_module_path, $ps_controller, $ps_action, $pa_parameters, $pa_requirements, $pb_disable_options=false, $pa_options=null) {
		if (!($va_screens = $this->getScreens($po_request, $pn_type_id))) { return false; }
		
		$va_nav = array();
		
		$vn_default_screen_id = null;
		foreach($va_screens as $va_screen) {
			if (!$vn_default_screen_id) { $vn_default_screen_id = $va_screen['screen_id']; }
			$va_nav['screen_'.$va_screen['screen_id']] = array(
				'displayName' => $va_screen['name'],
				"default" => array( 
					'module' => $ps_module_path, 
					'controller' => $ps_controller,
					'action' => $ps_action.'/Screen'.$va_screen['screen_id']
				),
				"useActionInPath" => 0,
				"useActionExtraInPath" => 1,
				"disabled" => $pb_disable_options,
				"requires" => $pa_requirements,
				"parameters" => $pa_parameters
			);
			
			if (is_array($pa_options)) {
				$va_nav['screen_'.$va_screen['screen_id']] = array_merge($va_nav['screen_'.$va_screen['screen_id']], $pa_options);
			}
			if ($va_screen['is_default']) { $vn_default_screen_id = $va_screen['screen_id']; }
		}
		return array('fragment' => $va_nav, 'defaultScreen' => 'Screen'.$vn_default_screen_id);
	}
	# ----------------------------------------
	# Static
	# ----------------------------------------
	/**
	 * Get simple UI list (restricted by user)
	 */
	public static function getUIList($pn_type=null, $pn_user_id=null){
		if ($pn_user_id) { $vs_key = $pn_user_id; } else { $vs_key = "_all_"; }
		if (ca_editor_uis::$s_available_ui_cache[$pn_type.'/'.$pn_user_id]) { return ca_editor_uis::$s_available_ui_cache[$pn_type.'/'.$pn_user_id]; }
		$o_db = new Db();
		
		$vs_type_sql = '';
		if ($pn_type) {
			$vs_type_sql = '(ceui.editor_type = '.((int)$pn_type).')';
		}
		if ($pn_user_id) {
			$qr_res = $o_db->query("
				SELECT ceui.ui_id, ceuil.name, ceuil.description, ceuil.locale_id, ceui.editor_type, ceui.is_system_ui
				FROM ca_editor_uis ceui
				INNER JOIN ca_editor_ui_labels AS ceuil ON ceui.ui_id = ceuil.ui_id
				WHERE
					{$vs_type_sql} ".($vs_type_sql ? " AND " : "")."
					(
						(ceui.user_id = ?) OR
						(ceui.is_system_ui = 1)
					)
				ORDER BY ceuil.name
			",(int)$pn_user_id);
		} else {
			$qr_res = $o_db->query("
				SELECT ceui.ui_id, ceuil.name, ceuil.description, ceuil.locale_id, ceui.editor_type, ceui.is_system_ui
				FROM ca_editor_uis ceui
				INNER JOIN ca_editor_ui_labels AS ceuil ON ceui.ui_id = ceuil.ui_id
				".($vs_type_sql ? "WHERE {$vs_type_sql}" : "")."
				ORDER BY ceuil.name
			");
		}
		
		$va_uis = array();
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			$va_uis[$va_row['ui_id']][$va_row['locale_id']] = $va_row;
		}
		
		$va_uis = caExtractValuesByUserLocale($va_uis);
		return ca_editor_uis::$s_available_ui_cache[$pn_type.'/'.$pn_user_id] = $va_uis;
	}
	# ----------------------------------------
	/**
	 * Get UI count
	 */
	public static function getUICount($pn_type=null, $pn_user_id=null){
		return sizeof(ca_editor_uis::getUIList($pn_type, $pn_user_id));
	}
	# ----------------------------------------
	/**
	 * Returns a list of ca_editor_uis ui_ids for all
	 * user interfaces that the user can access for the specified type
	 */
	public static function getAvailableUIs($pn_type, $pn_user_id) {
		if (ca_editor_uis::$s_available_ui_cache[$pn_type.'/'.$pn_user_id]) { return ca_editor_uis::$s_available_ui_cache[$pn_type.'/'.$pn_user_id]; }
		
		$o_db = new Db();
		$qr_res = $o_db->query("
			SELECT ceui.ui_id, ceuil.name, ceuil.description, ceuil.locale_id, ceui.editor_type, ceui.is_system_ui
			FROM ca_editor_uis ceui
			INNER JOIN ca_editor_ui_labels AS ceuil ON ceui.ui_id = ceuil.ui_id
			WHERE
				(ceui.editor_type = ?) AND
				(
					(ceui.user_id = ?) OR
					(ceui.is_system_ui = 1)
				)
			ORDER BY ceuil.name
		", (int)$pn_type, (int)$pn_user_id);
		
		$va_uis = array();
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			$va_uis[$va_row['ui_id']][$va_row['locale_id']] = $va_row;
		}
		
		$va_uis = caExtractValuesByUserLocale($va_uis);
		
		return ca_editor_uis::$s_available_ui_cache[$pn_type.'/'.$pn_user_id] = $va_uis;
	}
	# ----------------------------------------
}
?>