<?php
/** ---------------------------------------------------------------------
 * app/models/ca_users.php : table access class for table ca_users
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

require_once(__CA_LIB_DIR__."/core/AccessRestrictions.php");
require_once(__CA_APP_DIR__.'/models/ca_user_roles.php');
require_once(__CA_APP_DIR__.'/models/ca_user_groups.php');
require_once(__CA_APP_DIR__.'/models/ca_locales.php');


BaseModel::$s_ca_models_definitions['ca_users'] = array(
 	'NAME_SINGULAR' 	=> _t('user'),
 	'NAME_PLURAL' 		=> _t('users'),
 	'FIELDS' 			=> array(
 		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'User id', 'DESCRIPTION' => 'Identifier for User'
		),
		'user_name' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('User name'), 'DESCRIPTION' => _t('The login name for this user. This name is used in combination with the password set below to access the system.'),
				'BOUNDS_LENGTH' => array(1,255)
		),
		'userclass' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('User class'), 'DESCRIPTION' => _t('"Full" user accounts may log into all CollectiveAccess interfaces. "Public" user accounts may only log into the publicly accessible front-end system (if one exists). "Deleted" users may not log into any interface – the account is considered removed.'),
				"BOUNDS_CHOICE_LIST"=> array(
					_t('full-access') 	=> 0,
					_t('public-access only')	=> 1,
					_t('deleted') => 255
				)
		),
		'password' => array(
				'FIELD_TYPE' => FT_PASSWORD, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 60, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Password'), 'DESCRIPTION' => _t('The login password for this user. Passwords must be at least 4 characters and should ideally contain a combination of letters and numbers. Passwords are case-sensitive.'),
				'BOUNDS_LENGTH' => array(4,100)
		),
		'fname' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 60, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('First name'), 'DESCRIPTION' => _t('The forename of this user.'),
				'BOUNDS_LENGTH' => array(1,255)
		),
		'lname' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 60, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Last name'), 'DESCRIPTION' => _t('The surname of this user.'),
				'BOUNDS_LENGTH' => array(1,255)
		),
		'email' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 60, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('E-mail'), 'DESCRIPTION' => _t('The e-mail address of this user. The address will be used for all mail-based system notifications and alerts to this user.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'vars' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'User variable storage', 'DESCRIPTION' => 'Storage area for user variables'
		),
		'volatile_vars' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Volatile user variable storage', 'DESCRIPTION' => 'Storage area for user variables of limited size that change often'
		),
		'active' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_CHECKBOXES, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Account is activated?'), "DESCRIPTION" => "If checked, indicates user account is active. Only active users are allowed to log into the system.",
				'BOUNDS_VALUE' => array(0,1)
		),
		'confirmed_on' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Confirmed on'), 'DESCRIPTION' => _t('Confirmed on')
		),
		'confirmation_key' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 32, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Confirmation key'), 'DESCRIPTION' => _t('Confirmation key used for email verification.'),
				'BOUNDS_LENGTH' => array(0,32)
		)
 	)
);

class ca_users extends BaseModel {
	# ---------------------------------
	# --- Object attribute properties
	# ---------------------------------
	# Describe structure of content object's properties - eg. database fields and their
	# associated types, what modes are supported, et al.
	#
	private $_user_pref_defs;
	# ------------------------------------------------------
	# --- Basic object parameters
	# ------------------------------------------------------
	# what table does this class represent?
	protected $TABLE = 'ca_users';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'user_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('user_name');

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
	protected $ORDER_BY = array('user_name');

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
	
	/** 
	 * Container for persistent user-specific variables
	 */
	private $opa_user_vars;
	private $opa_user_vars_have_changed = false;
	
	/** 
	 * Container for volatile (often changing) persistent user-specific variables
	 * of limited size. This is meant for storage of values that change on every request. By
	 * segregating these values from less volatile (and often much larger) user var data we can
	 * avoid the cost of writing large blocks of data to the database on every request
	 */
	private $opa_volatile_user_vars;
	private $opa_volatile_user_vars_have_changed = false;
	
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
	# ----------------------------------------
	/**
	 * Loads user record.
	 *
	 * @access public
	 * @param integer $pm_user_id User id to load. If you pass a string instead of an integer, the record with a user name matching the string will be loaded.
	 * @return bool Returns true if no error, false if error occurred
	 */	
	public function load($pm_user_id=null) {
		if (is_numeric($pm_user_id)) {
			$vn_rc = parent::load($pm_user_id);
		} else {
			if (is_array($pm_user_id)) {
				$vn_rc = parent::load($pm_user_id);
			} else {
				$vn_rc = parent::load(array("user_name" => $pm_user_id));
			}
		}
		
		# load user vars (the get() method automatically unserializes the data)
		$this->opa_user_vars = $this->get("vars");
		$this->opa_volatile_user_vars = $this->get("volatile_vars");
		
		if (!isset($this->opa_user_vars) || !is_array($this->opa_user_vars)) {
			$this->opa_user_vars = array();
		}
		if (!isset($this->opa_volatile_user_vars) || !is_array($this->opa_volatile_user_vars)) {
			$this->opa_volatile_user_vars = array();
		}
		return $vn_rc;
	}
	# ----------------------------------------
	/**
	 * Creates new user record. You must set all required user fields before calling this method. If errors occur you can use the standard Table class error handling methods to figure out what went wrong.
	 *
	 * Required fields are user_name, password, fname and lname.
	 *
	 * @access public 
	 * @return bool Returns true if no error, false if error occurred
	 */	
	public function insert($pa_options=null) {
		# Confirmation key is an md5 hash than can be used as a confirmation token. The idea
		# is that you create a new user record with the 'active' field set to false. You then
		# send the confirmation key to the new user (usually via e-mail) and ask them to respond
		# with the key. If they do, you know that the e-mail address is valid.
		$vs_confirmation_key = md5(tempnam("/tmp","meow").time().rand(1000, 999999999));
		$this->set("confirmation_key", $vs_confirmation_key);
		
		# set user vars (the set() method automatically serializes the vars array)
		$this->set("vars",$this->opa_user_vars);
		$this->set("volatile_vars",$this->opa_volatile_user_vars);
		
		return parent::insert($pa_options);
	}
	# ----------------------------------------
	/**
	 * Saves changes to user record. You must make sure all required user fields are set before calling this method. If errors occur you can use the standard Table class error handling methods to figure out what went wrong.
	 *
	 * Required fields are user_name, password, fname and lname.
	 *
	 * If you do not call this method at the end of your request changed user vars will not be saved! If you are also using the Auth class, the Auth->close() method will call this for you.
	 *
	 * @access public
	 * @return bool Returns true if no error, false if error occurred
	 */	
	public function update($pa_options=null) {
		$this->clearErrors();
		
		# set user vars (the set() method automatically serializes the vars array)
		if ($this->opa_user_vars_have_changed) {
			$this->set("vars",$this->opa_user_vars);
		}
		if ($this->opa_volatile_user_vars_have_changed) {
			$this->set("volatile_vars",$this->opa_volatile_user_vars);
		}
		return parent::update();
	}
	# ----------------------------------------
	/**
	 * Deletes user. Unlike standard model rows, ca_users rows should never actually be deleted because they need to exist for logging purposes.
	 * So this version of delete() marks the row as deleted by setting ca_users.userclass = 255 and *not* invoking to BaseModel::delete()
	 * @access public
	 * @return bool Returns true if no error, false if error occurred
	 */	
	public function delete() {
		$this->clearErrors();
		$this->set('userclass', 255);
		return $this->update();
	}
	# ----------------------------------------
	# --- Authentication
	# ----------------------------------------
	/**
	 * Returns true if the provided clear-text password ($ps_password) is valid for the currently loaded record.
	 *
	 * Note: If "user_old_style_passwords" configuration directive is set to a non-blank, non-zero 
	 * value in the application configuration file, passwords are encrypted using the PHP crypt() function. Otherwise
	 * the md5 hash of the clear-text password is used.
	 *
	 * @access public
	 * @param string $ps_password Clear-text password
	 * @return bool Returns true if password is valid, false if not
	 */	
	# Returns true if password (clear text) is correct for the current user
	public function verify($ps_password) {
		return (md5($ps_password) == $this->get("password")) ? true : false;
	}
	# ----------------------------------------
	# --- User variables
	# ----------------------------------------
	/**
	 * Sets user variable. User variables are names ("keys") with associated values (strings, numbers or arrays).
	 * Once a user variable is set its value persists across instantiations until deleted or changed.
	 *
	 * Changes to user variables are saved when the insert() (for new user records) or update() (for existing user records)
	 * method is called. If you do not call either of these any changes will be lost when the request completes.
	 *
	 * @access public
	 * @param string $ps_key Name of user variable
	 * @param mixed $pm_val Value of user variable. Can be string, number or array.
	 * @param array $pa_options Associative array of options. Support options are:
	 *		- ENTITY_ENCODE_INPUT = Convert all "special" HTML characters in variable value to entities; default is true
	 *		- URL_ENCODE_INPUT = Url encodes variable value; default is  false
	 * @return bool Returns true on successful save, false if the variable name or value was invalid
	 */	
	public function setVar ($ps_key, $pm_val, $pa_options=null) {
		if (is_object($pm_val)) { return false; }
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$this->clearErrors();
		if ($ps_key) {			
			if (isset($pa_options['volatile']) && $pa_options['volatile']) {
				$va_vars =& $this->opa_volatile_user_vars;
				$vb_has_changed =& $this->opa_volatile_user_vars_have_changed;
				
				unset($this->opa_user_vars[$ps_key]);
			} else {
				$va_vars =& $this->opa_user_vars;
				$vb_has_changed =& $this->opa_user_vars_have_changed;
				
				unset($this->opa_volatile_user_vars_have_changed[$ps_key]);
			}
			
			if (isset($pa_options["ENTITY_ENCODE_INPUT"]) && $pa_options["ENTITY_ENCODE_INPUT"]) {
				if (is_string($pm_val)) {
					$vs_proc_val = htmlentities(html_entity_decode($pm_val));
				} else {
					$vs_proc_val = $pm_val;
				}
			} else {
				if (isset($pa_options["URL_ENCODE_INPUT"]) && $pa_options["URL_ENCODE_INPUT"]) {
					$vs_proc_val = urlencode($pm_val);
				} else {
					$vs_proc_val = $pm_val;
				}
			}
			
			if (
				(
					(is_array($vs_proc_val) && !is_array($va_vars[$ps_key]))
					||
					(!is_array($vs_proc_val) && is_array($va_vars[$ps_key]))
					||
					(is_array($vs_proc_val) && (is_array($va_vars[$ps_key])) && (sizeof($vs_proc_val) != sizeof($va_vars[$ps_key])))
					||
					(md5(print_r($vs_proc_val, true)) != md5(print_r($va_vars[$ps_key], true)))
				)
			) {
				$vb_has_changed = true;
				$va_vars[$ps_key] = $vs_proc_val;
			} else {
				if ((string)$vs_proc_val != (string)$va_vars[$ps_key]) {
					$vb_has_changed = true;
					$va_vars[$ps_key] = $vs_proc_val;
				}
			}
			return true;
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Deletes user variable. Once deleted, you must call insert() (for new user records) or update() (for existing user records)
	 * to make the deletion permanent.
	 *
	 * @access public
	 * @param string $ps_key Name of user variable
	 * @return bool Returns true if variable was defined, false if it didn't exist
	 */	
	public function deleteVar ($ps_key) {
		$this->clearErrors();
		
		if (isset($this->opa_user_vars[$ps_key])) {
			unset($this->opa_user_vars[$ps_key]);
			$this->opa_user_vars_have_changed = true;
			return true;
		} else {
			if (isset($this->opa_volatile_user_vars[$ps_key])) {
				unset($this->opa_volatile_user_vars[$ps_key]);
				$this->opa_volatile_user_vars_have_changed = true;
				return true;
			} else {
				return false;
			}
		}
	}
	# ----------------------------------------
	/**
	 * Returns value of user variable. Returns null if variable does not exist.
	 *
	 * @access public
	 * @param string $ps_key Name of user variable
	 * @return mixed Value of variable (string, number or array); null is variable is not defined.
	 */	
	public function getVar ($ps_key) {
		$this->clearErrors();
		if (isset($this->opa_user_vars[$ps_key])) {
			return (is_array($this->opa_user_vars[$ps_key])) ? $this->opa_user_vars[$ps_key] : stripSlashes($this->opa_user_vars[$ps_key]);
		} else {
			if (isset($this->opa_volatile_user_vars[$ps_key])) {
				return (is_array($this->opa_volatile_user_vars[$ps_key])) ? $this->opa_volatile_user_vars[$ps_key] : stripSlashes($this->opa_volatile_user_vars[$ps_key]);
			}
		}
		return null;
	}
	# ----------------------------------------
	/**
	 * Returns list of user variable names
	 *
	 * @access public
	 * @return array Array of uservar names, or empty array if none are defined
	 */	
	public function getVarKeys() {
		$va_keys = array();
		if (isset($this->opa_user_vars) && is_array($this->opa_user_vars)) {
			$va_keys = array_keys($this->opa_user_vars);
		}
		if (isset($this->opa_volatile_user_vars) && is_array($this->opa_volatile_user_vars)) {
			$va_keys = array_merge($va_keys, array_keys($this->opa_volatile_user_vars));
		}
		
		return $va_keys;
	}
	# ----------------------------------------
	/** 
	 * Returns list of users
	 *
	 * @param array $pa_options Optional array of options. Options include:
	 *		sort
	 *		sort_direction
	 *		userclass
	 *	@return array List of users. Array is keyed on user_id and value is array with all ca_users fields + the last_login time as a unix timestamp
	 *
	 */
	public function getUserList($pa_options=null) {
		$ps_sort_field= isset($pa_options['sort']) ? $pa_options['sort'] : '';
		$ps_sort_direction= isset($pa_options['sort_direction']) ? $pa_options['sort_direction'] : 'asc';
		$pa_userclass= isset($pa_options['userclass']) ? $pa_options['userclass'] : array();

		if(!is_array($pa_userclass)) { $pa_userclass = array($pa_userclass); }

		$o_db = $this->getDb();
		
		$va_valid_sorts = array('lname,fname', 'user_name', 'email', 'last_login', 'active');
		if (!in_array($ps_sort_field, $va_valid_sorts)) {
			$ps_sort_field = 'lname,fname';
		}
		
		if($ps_sort_direction != 'desc') {
			$ps_sort_direction = 'asc';
		}
		
		$va_query_params = array();
		$vs_user_class_sql = '';
		if (is_array($pa_userclass) && sizeof($pa_userclass)) {
			$vs_user_class_sql = " WHERE userclass IN (?)";
			$va_query_params[] = $pa_userclass;
		}
		
		if ($ps_sort_field == 'last_login') {
			$vs_sort = '';
		} else {
			$vs_sort = "ORDER BY {$ps_sort_field} {$ps_sort_direction}";
		}
		$qr_users = $o_db->query("
			SELECT *
			FROM ca_users
				{$vs_user_class_sql}
			{$vs_sort}
		", $va_query_params);
		
		$va_users = array();
		while($qr_users->nextRow()) {
			$va_vars = $qr_users->getVars('vars');
 			$va_users[$qr_users->get('user_id')] = array_merge($qr_users->getRow(), array('last_login' => $va_vars['last_login']));
 		}
		
		return $va_users;
	}
	# ----------------------------------------
	/**
	 * Returns HTML multiple <select> with list of "full" users
	 *
	 * @param array $pa_options (optional) array of options. Keys are:
	 *		size = height of multiple select, in rows; default is 8
	 *		name = HTML form element name to apply to role <select>; default is 'groups'
	 *		id = DOM id to apply to role <select>; default is no id
	 *		label = String to label form element with
	 *		selected = User_id values to select
	 * @return string Returns HTML containing form element and form label
	 */
	public function userListAsHTMLFormElement($pa_options=null) {
		$vn_size = (isset($pa_options['size']) && ($pa_options['size'] > 0)) ? $pa_options['size'] : 8;
		$vs_name = (isset($pa_options['name'])) ? $pa_options['name'] : 'users';
		$vs_id = (isset($pa_options['id'])) ? $pa_options['id'] : '';
		$vs_label = (isset($pa_options['label'])) ? $pa_options['label'] : _t('Users');
		$va_selected = (isset($pa_options['selected']) && is_array($pa_options['selected'])) ? $pa_options['selected'] : array();
		
		$va_users = $this->getUserList($pa_options);
		$vs_buf = '';
		
		if (sizeof($va_users)) {
			$vs_buf .= "<select multiple='1' name='{$vs_name}[]' size='{$vn_size}' id='{$vs_id}'>\n";
			foreach($va_users as $vn_user_id => $va_user_info) {
				$SELECTED = (in_array($vn_user_id, $va_selected)) ? "SELECTED='1'" : "";
				$vs_buf .= "<option value='{$vn_user_id}' {$SELECTED}>".$va_user_info['fname'].' '.$va_user_info['lname'].($va_user_info['email'] ? " (".$va_user_info['email'].")" : "")."</option>\n";
			}
			$vs_buf .= "</select>\n";
		}
		if ($vs_buf && ($vs_format = $this->_CONFIG->get('form_element_display_format'))) {
			$vs_format = str_replace("^ELEMENT", $vs_buf, $vs_format);
			$vs_format = str_replace("^LABEL", $vs_label, $vs_format);
			$vs_format = str_replace("^ERRORS", '', $vs_format);
			$vs_buf = str_replace("^EXTRA", '', $vs_format);
		}
		
		return $vs_buf;
	}
	# ----------------------------------------
	# --- Roles
	# ----------------------------------------
	/**
	 * Add roles to current user.
	 *
	 * @access public
	 * @param mixed $pm_roles Single role or list (array) of roles to add. Roles may be specified by name, code or id.
	 * @return integer Returns number of roles added or false if there was an error. The number of roles added will not necessarily match the number of roles you tried to add. If you try to add the same role twice, or to add a role that already exists for this user, addRoles() will silently ignore it.
	 */	
	public function addRoles($pm_roles) {
		if (!is_array($pm_roles)) {
			$pm_roles = array($pm_roles);
		}
		
		if ($pn_user_id = $this->getPrimaryKey()) {
			$t_role = new ca_user_roles();
			
			$vn_roles_added = 0;
			foreach ($pm_roles as $vs_role) {
				$vb_got_role = 0;
				if (is_numeric($vs_role)) {
					$vb_got_role = $t_role->load($vs_role);
				}
				if (!$vb_got_role) {
					if (!$t_role->load(array("name" => $vs_role))) {
						if (!$t_role->load(array("code" => $vs_role))) {
							continue;
						}
						
					}
					$vb_got_role = 1;
				}
					
				$o_db = $this->getDb();
				$o_db->query("
					INSERT INTO ca_users_x_roles 
					(user_id, role_id)
					VALUES
					(?, ?)
				", (int)$pn_user_id, (int)$t_role->getPrimaryKey());
				
				if ($o_db->numErrors() == 0) {
					$vn_roles_added++;
				} else {
					$this->postError(930, _t("Database error adding role '%1': %2", $vs_role, join(';', $o_db->getErrors())),"User->addRoles()");
				}
			}
			return $vn_roles_added;
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Remove roles from current user.
	 *
	 * @access public
	 * @param mixed $pm_roles Single role or list (array) of roles to remove. Roles may be specified by name, code or id.
	 * @return bool Returns true on success, false on error.
	 */	
	public function removeRoles($pm_roles) {
		if (!is_array($pm_roles)) {
			$pm_roles = array($pm_roles);
		}
		
		if ($pn_user_id = $this->getPrimaryKey()) {
			$t_role = new ca_user_roles();
			
			$vn_roles_added = 0;
			$va_role_ids = array();
			foreach ($pm_roles as $vs_role) {
				$vb_got_role = 0;
				if (is_numeric($vs_role)) {
					$vb_got_role = $t_role->load($vs_role);
				}
				if (!$vb_got_role) {
					if (!$t_role->load(array("name" => $vs_role))) {
						if (!$t_role->load(array("code" => $vs_role))) {
							continue;
						}
					}
					$vb_got_role = 1;
				}
				
				if ($vb_got_role) {
					$va_role_ids[] = intval($t_role->getPrimaryKey());
				}
			}
			
			if (sizeof($va_role_ids) > 0) { 
				$o_db = $this->getDb();
				$o_db->query("
					DELETE FROM ca_users_x_roles 
					WHERE 
						(user_id = ?) AND (role_id IN (".join(", ", $va_role_ids)."))
				", (int)$pn_user_id);
					
				if ($o_db->numErrors()) {
					$this->postError(931, _t("Database error: %1", join(';', $o_db->getErrors())),"User->removeRoles()");
					return false;
				} else {
					return true;
				}
			} else {
				$this->postError(931, _t("No roles specified"),"User->removeRoles()");
				return false;
			}
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Removes all roles from current user.
	 *
	 * @access public
	 * @return bool Returns true on success, false on error.
	 */
	public function removeAllRoles() {
		if ($vn_user_id = $this->getPrimaryKey()) {
			$o_db = $this->getDb();
			$o_db->query("DELETE FROM ca_users_x_roles WHERE user_id = ?", (int)$vn_user_id);
			
			if ($o_db->numErrors()) {
				$this->postError(931, _t("Database error: %1", join(';', $o_db->getErrors())),"User->removeAllRoles()");
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Get list of all roles supported by the application. If you want to get the current user's roles, use getUserRoles()
	 *
	 * @access public
	 * @return integer Returns associative array of roles. Key is role id, value is array containing information about the role.
	 *
	 * The role information array contains the following keys: 
	 *		role_id 	(numeric id you can use in addRoles(), deleteRoles(), hasRole(), etc.)
	 *		name 		(the full name of the role)
	 *		code		(a short code used for the role)
	 *		description	(narrative description of role)
	 */
	public function getRoleList() {
		$t_role = new ca_user_roles();
		return $t_role->getRoleList();
	}
	# ----------------------------------------
	/**
	 * Get list of roles the current user has
	 *
	 * @access public
	 * @return array Returns associative array of roles. Key is role id, value is array containing information about the role.
	 *
	 * The role information array contains the following keys: 
	 *		role_id 	(numeric id you can use in addRoles(), deleteRoles(), hasRole(), etc.)
	 *		name 		(the full name of the role)
	 *		code		(a short code used for the role)
	 *		description	(narrative description of role)
	 */
	public function getUserRoles() {
		if ($pn_user_id = $this->getPrimaryKey()) {
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT wur.role_id, wur.name, wur.code, wur.description, wur.rank
				FROM ca_user_roles wur
				INNER JOIN ca_users_x_roles AS wuxr ON wuxr.role_id = wur.role_id
				WHERE wuxr.user_id = ?
				ORDER BY wur.rank
			", (int)$pn_user_id);
			
			$va_roles = array();
			while($qr_res->nextRow()) {
				$va_roles[$qr_res->get("role_id")] = $qr_res->getRow();
			}
			
			return $va_roles;
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Determines whether current user has a specified role.
	 *
	 * @access public
	 * @param mixed $pm_role The role to test for the current user. Role may be specified by name, code or id.
	 * @return bool Returns true if user has the role, false if not.
	 */	
	public function hasUserRole($ps_role) {
		if (!($pn_user_id = $this->getPrimaryKey())) {
			return false;
		}
		
		$vb_got_role = 0;
		$t_role = new ca_user_roles();
		if (is_numeric($ps_role)) {
			$vb_got_role = $t_role->load($ps_role);
		}
		if (!$vb_got_role) {
			if (!$t_role->load(array("name" => $ps_role))) {
				if (!$t_role->load(array("code" => $ps_role))) {
					return false;
				}
			}
			$vb_got_role = 1;
		}
		
		if ($vb_got_role) {
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT * 
				FROM ca_users_x_roles
				WHERE
					(user_id = ?) AND
					(role_id = ?)
			", (int)$pn_user_id, (int)$t_role->getPrimaryKey());
			
			if (!$qr_res) { return false; }
			
			if ($qr_res->nextRow()) {
				return true;
			} else {
				return false;
			}
		} else {
			$this->postError(940, _t("Invalid role '%1'", $ps_role),"User->hasRole()");
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Determines whether current user has a specified role attached to their user record or
	 * to an associated group.
	 *
	 * @access public
	 * @param mixed $pm_role The role to test for the current user. Role may be specified by name, code or id.
	 * @return bool Returns true if user has the role, false if not.
	 */	
	public function hasRole($ps_role) {
		if ($this->hasUserRole($ps_role)) {
			return true;
		} else {
			if ($this->hasGroupRole($ps_role)) {
				return true;
			}
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Returns HTML multiple <select> with full list of roles for currently loaded user
	 *
	 * @param array $pa_options (optional) array of options. Keys are:
	 *		size = height of multiple select, in rows; default is 8
	 *		name = HTML form element name to apply to role <select>; default is 'roles'
	 *		id = DOM id to apply to role <select>; default is no id
	 *		label = String to label form element with
	 * @return string Returns HTML containing form element and form label
	 */
	public function roleListAsHTMLFormElement($pa_options=null) {
		$vn_size = (isset($pa_options['size']) && ($pa_options['size'] > 0)) ? $pa_options['size'] : 8;
		$vs_name = (isset($pa_options['name'])) ? $pa_options['name'] : 'roles';
		$vs_id = (isset($pa_options['id'])) ? $pa_options['id'] : '';
		$vs_label = (isset($pa_options['label'])) ? $pa_options['label'] : _t('Roles');
		
		
		$va_roles = $this->getRoleList();
		$vs_buf = '';
		if (sizeof($va_roles)) {
			if(!$va_user_roles = $this->getUserRoles()) { $va_user_roles = array(); }
		
			$vs_buf .= "<select multiple='1' name='{$vs_name}[]' size='{$vn_size}' id='{$vs_id}'>\n";
			foreach($va_roles as $vn_role_id => $va_role_info) {
				$SELECTED = (isset($va_user_roles[$vn_role_id]) && $va_user_roles[$vn_role_id]) ? "SELECTED='1'" : "";
				$vs_buf .= "<option value='{$vn_role_id}' {$SELECTED}>".$va_role_info['name']." [".$va_role_info["code"]."]</option>\n";
			}
			$vs_buf .= "</select>\n";
		}
		if ($vs_buf && ($vs_format = $this->_CONFIG->get('form_element_display_format'))) {
			$vs_format = str_replace("^ELEMENT", $vs_buf, $vs_format);
			$vs_format = str_replace("^LABEL", $vs_label, $vs_format);
			$vs_format = str_replace("^ERRORS", '', $vs_format);
			$vs_buf = str_replace("^EXTRA", '', $vs_format);
		}
		
		return $vs_buf;
	}
	# ----------------------------------------
	# --- Groups
	# ----------------------------------------
	/**
	 * Add current user to one or more groups.
	 *
	 * @access public
	 * @param mixed $pm_groups Single group or list (array) of group to add user to. Groups may be specified by name, short name or numeric id.
	 * @return integer Returns number of groups user was added to or false if there was an error. The number of groups user was added to will not necessarily match the number of groups you passed in $pm_groups. If you try to add the user to the same group twice, or to a group that the user is already a member of, addToGroups() will silently ignore it.
	 */	
	public function addToGroups($pm_groups) {
		if (!is_array($pm_groups)) {
			$pm_groups = array($pm_groups);
		}
		
		if ($pn_user_id = $this->getPrimaryKey()) {
			$t_group = new ca_user_groups();
			
			$vn_groups_added = 0;
			foreach ($pm_groups as $vs_group) {
				$vb_got_group = 0;
				if (is_numeric($vs_group)) {
					$vb_got_group = $t_group->load($vs_group);
				}
				if (!$vb_got_group) {
					if (!$t_group->load(array("name" => $vs_group))) {
						if (!$t_group->load(array("name_short" => $vs_group))) {
							continue;
						}
						
					}
					$vb_got_group = 1;
				}
					
				$o_db = $this->getDb();
				$o_db->query("
					INSERT INTO ca_users_x_groups 
					(user_id, group_id)
					VALUES
					(?, ?)
				", (int)$pn_user_id, (int)$t_group->getPrimaryKey());
				
				if ($o_db->numErrors() == 0) {
					$vn_groups_added++;
				} else {
					$this->postError(935, _t("Database error: %1", join(';', $o_db->getErrors())),"User->addToGroups()");
				}
			}
			return $vn_groups_added;
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Remove current user from one or more groups.
	 *
	 * @access public
	 * @param mixed $pm_groups Single group or list (array) of groups to remove current user from. Groups may be specified by name, short name or id.
	 * @return bool Returns true on success, false on error.
	 */	
	public function removeFromGroups($pm_groups) {
		if (!is_array($pm_groups)) {
			$pm_groups = array($pm_groups);
		}
		
		if ($pn_user_id = $this->getPrimaryKey()) {
			$t_group = new ca_user_groups();
			
			$vn_groups_added = 0;
			$va_group_ids = array();
			foreach ($pm_groups as $ps_group) {
				$vb_got_group = 0;
				if (is_numeric($ps_group)) {
					$vb_got_group = $t_group->load($ps_group);
				}
				if (!$vb_got_group) {
					if (!$t_group->load(array("name" => $ps_group))) {
						if (!$t_group->load(array("name_short" => $ps_group))) {
							continue;
						}
					}
					$vb_got_group = 1;
				}
				
				if ($vb_got_group) {
					$va_group_ids[] = intval($t_group->getPrimaryKey());
				}
			}
			
			if (sizeof($va_group_ids) > 0) { 
				$o_db = $this->getDb();
				$o_db->query("
					DELETE FROM ca_users_x_groups 
					WHERE (user_id = ?) AND (group_id IN (".join(", ", $va_group_ids)."))
				", (int)$pn_user_id);
					
				if ($o_db->numErrors()) {
					$this->postError(936, _t("Database error: %1", join(';', $o_db->getErrors())),"User->removeFromGroups()");
					return false;
				} else {
					return true;
				}
			} else {
				$this->postError(945, _t("No groups specified"),"User->removeFromGroups()");
				return false;
			}
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Remove current user from all associated groups.
	 *
	 * @access public
	 * @return bool Returns true on success, false on error.
	 */
	public function removeFromAllGroups() {
		if ($vn_user_id = $this->getPrimaryKey()) {
			$o_db = $this->getDb();
			$o_db->query("DELETE FROM ca_users_x_groups WHERE user_id = ?", (int)$vn_user_id);
			
			if ($o_db->numErrors()) {
				$this->postError(936, _t("Database error: %1", join(';', $o_db->getErrors())),"User->removeFromAllGroups()");
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Get list of all available user groups. If you want to get a list of the current user's groups, use getUserGroups()
	 *
	 * @access public
	 * @return integer Returns associative array of groups. Key is group id, value is array containing information about the group.
	 *
	 * The group information array contains the following keys: 
	 *		group_id 	(numeric id you can use in addRoles(), deleteRoles(), hasRole(), etc.)
	 *		name 		(the full name of the group)
	 *		name_short	(an abbreviated name used for the group)
	 *		description	(narrative description of group)
	 *		admin_id	(user_id of group administrator)
	 *		admin_fname	(first name of group administrator)
	 *		admin_lname	(last name of group administrator)
	 *		admin_email	(email address of group administrator)
	 */
	public function getGroupList($pn_user_id=null) {
		$t_group = new ca_user_groups();
		return $t_group->getGroupList('name', 'asc', $pn_user_id);
	}
	# ----------------------------------------
	/**
	 * Get list of roles the current user has via associated groups
	 *
	 * @access public
	 * @return array Returns associative array of roles. Key is role id, value is array containing information about the role.
	 *
	 * The role information array contains the following keys: 
	 *		role_id 	(numeric id you can use in addRoles(), deleteRoles(), hasRole(), etc.)
	 *		name 		(the full name of the role)
	 *		code		(a short code used for the role)
	 *		description	(narrative description of role)
	 */
	public function getGroupRoles() {
		if ($pn_user_id = $this->getPrimaryKey()) {
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT wur.role_id, wur.name, wur.code, wur.description, wur.rank
				FROM ca_user_roles wur
				INNER JOIN ca_groups_x_roles AS wgxr ON wgxr.role_id = wur.role_id
				INNER JOIN ca_users_x_groups AS wuxg ON wuxg.group_id = wgxr.group_id
				WHERE wuxg.user_id = ?
				ORDER BY wur.rank
			", (int)$pn_user_id);
			
			$va_roles = array();
			while($qr_res->nextRow()) {
				$va_roles[$qr_res->get("role_id")] = $qr_res->getRow();
			}
			
			return $va_roles;
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Determines whether current user is in a group with the specified role.
	 *
	 * @access public
	 * @param mixed $pm_role The role to test for the current user. Role may be specified by name, code or id.
	 * @return bool Returns true if user has the role, false if not.
	 */	
	public function hasGroupRole($ps_role) {
		if (!($pn_user_id = $this->getPrimaryKey())) {
			return false;
		}
		
		$vb_got_role = 0;
		$t_role = new ca_user_roles();
		if (is_numeric($ps_role)) {
			$vb_got_role = $t_role->load($ps_role);
		}
		if (!$vb_got_role) {
			if (!$t_role->load(array("name" => $ps_role))) {
				if (!$t_role->load(array("code" => $ps_role))) {
					return false;
				}
			}
			$vb_got_role = 1;
		}
		
		if ($vb_got_role) {
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT wgr.role_id 
				FROM ca_groups_x_roles wgr
				INNER JOIN ca_users_x_groups AS wuxg ON wuxg.group_id = wgr.group_id 
				WHERE
					(wuxg.user_id = ?) AND
					(wgr.role_id = ?)
			", (int)$pn_user_id, (int)$t_role->getPrimaryKey());
			if ($qr_res->nextRow()) {
				return true;
			} else {
				return false;
			}
		} else {
			$this->postError(940, _t("Invalid role '%1'", $ps_role),"User->hasGroupRole()");
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Get list of current user's groups.
	 *
	 * @access public
	 * @return array Returns associative array of groups. Key is group id, value is array containing information about the group.
	 *
	 * The group information array contains the following keys: 
	 *		group_id 	(numeric id you can use in addRoles(), deleteRoles(), hasRole(), etc.)
	 *		name 		(the full name of the group)
	 *		name_short	(an abbreviated name used for the group)
	 *		description	(narrative description of group)
	 *		admin_id	(user_id of group administrator)
	 *		admin_fname	(first name of group administrator)
	 *		admin_lname	(last name of group administrator)
	 *		admin_email	(email address of group administrator)
	 */
	public function getUserGroups() {
		if ($pn_user_id = $this->getPrimaryKey()) {
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT 
					wug.group_id, wug.name, wug.code, wug.description,
					wug.user_id admin_id, wu.fname admin_fname, wu.lname admin_lname, wu.email admin_email
				FROM ca_user_groups wug
				LEFT JOIN ca_users AS wu ON wug.user_id = wu.user_id
				INNER JOIN ca_users_x_groups AS wuxg ON wuxg.group_id = wug.group_id
				WHERE wuxg.user_id = ?
				ORDER BY wug.rank
			", (int)$pn_user_id);
			$va_groups = array();
			while($qr_res->nextRow()) {
				$va_groups[$qr_res->get("group_id")] = $qr_res->getRow();
			}
			
			return $va_groups;
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Determines whether current user is a member of the specified group.
	 *
	 * @access public
	 * @param mixed $ps_group The group to test for the current user for membership in. Group may be specified by name, short name or id.
	 * @return bool Returns true if user is a member of the group, false if not.
	 */	
	public function inGroup($ps_group) {
		if (!($pn_user_id = $this->getPrimaryKey())) {
			return false;
		}
		
		$vb_got_group = 0;
		$t_group = new ca_user_groups();
		if (is_numeric($ps_group)) {
			$vb_got_group = $t_group->load($ps_group);
		}
		if (!$vb_got_group) {
			if (!$t_group->load(array("name" => $ps_group))) {
				if (!$t_group->load(array("name_short" => $ps_group))) {
					return false;
				}
			}
			$vb_got_group = 1;
		}
		
		if ($vb_got_group) {
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT link_id 
				FROM ca_users_x_groups
				WHERE
					(user_id = ?) AND
					(group_id = ?)
			", (int)$pn_user_id, (int)$t_group->getPrimaryKey());
			if ($qr_res->nextRow()) {
				return true;
			} else {
				return false;
			}
		} else {
			$this->postError(945, _t("Group '%1' does not exist", $ps_group),"User->inGroup()");
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Returns HTML multiple <select> with full list of groups for currently loaded user
	 *
	 * @param array $pa_options (optional) array of options. Keys are:
	 *		size = height of multiple select, in rows; default is 8
	 *		name = HTML form element name to apply to role <select>; default is 'groups'
	 *		id = DOM id to apply to role <select>; default is no id
	 *		label = String to label form element with
	 * @return string Returns HTML containing form element and form label
	 */
	public function groupListAsHTMLFormElement($pa_options=null) {
		$vn_size = (isset($pa_options['size']) && ($pa_options['size'] > 0)) ? $pa_options['size'] : 8;
		$vs_name = (isset($pa_options['name'])) ? $pa_options['name'] : 'groups';
		$vs_id = (isset($pa_options['id'])) ? $pa_options['id'] : '';
		$vs_label = (isset($pa_options['label'])) ? $pa_options['label'] : _t('Groups');
		
		
		$va_groups = $this->getGroupList();
		$vs_buf = '';
		
		if (sizeof($va_groups)) {
			if(!$va_user_groups = $this->getUserGroups()) { $va_user_groups = array(); }
		
			$vs_buf .= "<select multiple='1' name='{$vs_name}[]' size='{$vn_size}' id='{$vs_id}'>\n";
			foreach($va_groups as $vn_group_id => $va_group_info) {
				$SELECTED = (isset($va_user_groups[$vn_group_id]) && $va_user_groups[$vn_group_id]) ? "SELECTED='1'" : "";
				$vs_buf .= "<option value='{$vn_group_id}' {$SELECTED}>".$va_group_info['name']." [".$va_group_info["code"]."]</option>\n";
			}
			$vs_buf .= "</select>\n";
		}
		if ($vs_buf && ($vs_format = $this->_CONFIG->get('form_element_display_format'))) {
			$vs_format = str_replace("^ELEMENT", $vs_buf, $vs_format);
			$vs_format = str_replace("^LABEL", $vs_label, $vs_format);
			$vs_format = str_replace("^ERRORS", '', $vs_format);
			$vs_buf = str_replace("^EXTRA", '', $vs_format);
		}
		
		return $vs_buf;
	}
	# ----------------------------------------
	# --- User preferences
	# ----------------------------------------
	/**
	 * Returns value of user preference. Returns null if preference does not exist.
	 *
	 * @access public
	 * @param string $ps_pref Name of user preference
	 * @return mixed Value of variable (string, number or array); null is variable is not defined.
	 */	
	public function getPreference($ps_pref) {
		if ($this->isValidPreference($ps_pref)) {
			$va_prefs = $this->getVar("_user_preferences");
			
			$va_pref_info = $this->getPreferenceInfo($ps_pref);
			
			if (!isset($va_prefs)) {
				return isset($va_pref_info["default"]) ? $va_pref_info["default"] : null;
			}
			return isset($va_prefs[$ps_pref]) ? $va_prefs[$ps_pref] : ($va_pref_info["default"] ? $va_pref_info["default"] : null);
		} else {
			$this->postError(920, _t("%1 is not a valid user preference", $ps_pref),"User->getPreference()");
			return null;
		}
	}
	# ----------------------------------------
	/**
	 * Sets value of user preference. Returns false if preference or value is invalid.
	 *
	 * @access public
	 * @param string $ps_pref Name of user preference
	 * @param mixed $ps_val Value of preference
	 * @return bool True if preference was set; false if it could not be set.
	 */	
	public function setPreference($ps_pref, $ps_val) {
		if ($this->isValidPreference($ps_pref)) {
			if ($this->isValidPreferenceValue($ps_pref, $ps_val, 1)) {
				$va_prefs = $this->getVar("_user_preferences");
				$va_prefs[$ps_pref] = $ps_val;
				$this->setVar("_user_preferences", $va_prefs);
				return true;
			} else {
				return false;
			}
		} else {
			$this->postError(920, _t("%1 is not a valid user preference", $ps_pref),"User->getPreference()");
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Returns list of supported preference names. If the $ps_group_name is provided, then only
	 * preference names for the specified group are returned. Otherwise all supported preference 
	 * names are returned.
	 *
	 * @access public
	 * @param string $ps_group_name Name of user preference group
	 * @return array List of valid preferences
	 */	
	public function getValidPreferences($ps_group_name="") {
		if ($ps_group_name) {
			if ($va_group = $this->getPreferenceGroupInfo($ps_group_name)) {
				return array_keys($va_group["preferences"]);
			} else {
				return array();
			}
		} else {
			$this->loadUserPrefDefs();
			return array_keys($this->_user_pref_defs->getAssoc("preferenceDefinitions"));
		}
	}
	# ----------------------------------------
	/**
	 * Returns list of supported preference group names. Preference groups are simply 
	 * groupings of related preference values. Typically preference groups are
	 * used by preference configuration user interfaces to group related preferences
	 * together in convenient units. When using preferences to in application code it 
	 * is not usually important what group a preference belongs to.
	 *
	 * @access public
	 * @return array List of supported preference group names
	 */	
	public function getValidPreferenceGroups() {
		$this->loadUserPrefDefs();
		return array_keys($this->_user_pref_defs->getAssoc("preferenceGroups"));
	}
	# ----------------------------------------
	/**
	 * Tests whether a preference name is supported or not.
	 *
	 * @access public
	 * @param string $ps_pref Name of user preference
	 * @return bool Returns true if preference is supports; false if it is not supported.
	 */	
	public function isValidPreference($ps_pref) {
		return (in_array($ps_pref, $this->getValidPreferences())) ? true : false;
	}
	# ----------------------------------------
	/**
	 * Tests whether a value is valid for a given preference
	 *
	 * @access public
	 * @param string $ps_pref Name of user preference
	 * @param mixed $ps_value Preference value to test
	 * @param bool $pb_post_errors If true, invalid parameter causes errors to be thrown; if false, error messages are supressed. Default is false.
	 * @return bool Returns true if value is valid; false if value is invalid.
	 */	
	public function isValidPreferenceValue($ps_pref, $ps_value, $pb_post_errors=false) {
		if ($this->isValidPreference($ps_pref)) {
			$va_pref_info = $this->getPreferenceInfo($ps_pref);
			
			# check number of picks for checkboxes
			if (is_array($ps_value) && isset($va_pref_info["picks"])) {
				if (!((sizeof($ps_value) >= $va_pref_info["picks"]["minimum"]) && (sizeof($ps_value) <= $va_pref_info["picks"]["maximum"]))) {
					if ($pb_post_errors) {
						if ($va_pref_info["picks"]["minimum"] < $va_pref_info["picks"]["maximum"]) {
							$this->postError(921, _t("You must select between %1 and %2 choices for %3", $va_pref_info["picks"]["minimum"], $va_pref_info["picks"]["maximum"], $va_pref_info["label"]),"User->isValidPreferenceValue()");
						} else {
							$this->postError(921, _t("You must select %1 choices for %2", $va_pref_info["picks"]["minimum"], $va_pref_info["label"]),"User->isValidPreferenceValue()");
						}
					}
					return false;
				}
			}
			
			# make sure value is in choice list
			if (isset($va_pref_info["choiceList"]) && is_array($va_pref_info["choiceList"])) {
				if (is_array($ps_value)) {
					foreach($ps_value as $vs_value) {
						if (!in_array($vs_value, array_values($va_pref_info["choiceList"]))) {
							if ($pb_post_errors) {
								$this->postError(921, _t("%1 is not a valid value for %2", $vs_value, $va_pref_info["label"]),"User->isValidPreferenceValue()");
							}
							return false;
						}
					}
				} else {
					if (!in_array($ps_value, array_values($va_pref_info["choiceList"]))) {
						if ($pb_post_errors) {
							$this->postError(921, _t("%1 is not a valid value for %2", $ps_value, $va_pref_info["label"]),"User->isValidPreferenceValue()");
						}
						return false;
					}
				}
			}
			
			switch($va_pref_info["formatType"]) {
				# ---------------------------------
				case 'FT_NUMBER':
					if (isset($va_pref_info["value"]) && is_array($va_pref_info["value"])) {
						# make sure value within length bounds
						if (!(($ps_value >= $va_pref_info["value"]["minimum"]) && ($ps_value <= $va_pref_info["value"]["maximum"]))) {
							if ($pb_post_errors) {
								$this->postError(921, _t("Value for %1 must be between %2 and %3", $va_pref_info["label"], $va_pref_info["value"]["minimum"], $va_pref_info["value"]["maximum"]),"User->isValidPreferenceValue()");
							}
							return false;
						}
					}
					break;
				# ---------------------------------
				case 'FT_TEXT':
					if (isset($va_pref_info["length"]) && is_array($va_pref_info["length"])) { 
						# make sure value within length bounds
						if (!((strlen($ps_value) >= $va_pref_info["length"]["minimum"]) && (strlen($ps_value) <= $va_pref_info["length"]["maximum"]))){
							if ($pb_post_errors) {
								$this->postError(921, _t("Value for %1 must be between %2 and %3 characters", $va_pref_info["label"], $va_pref_info["length"]["minimum"], $va_pref_info["length"]["maximum"]),"User->isValidPreferenceValue()");
							}
							return false;
						}
					}
					break;
				# ---------------------------------
				default:
					return "Configuration error: invalid format type for $ps_pref";
					break;
				# ---------------------------------
			}
			return true;
		} else {
			return false;
		}
	}
	# ----------------------------------------
	/**
	 * Generates HTML form element widget for preference based upon settings in preference definition file.
	 * By calling this method for a series of preference names, one can quickly create an HTML-based configuration form.
	 *
	 * @access public
	 * @param string $ps_pref Name of user preference
	 * @param string $ps_format Format string containing simple tags to be replaced with preference information. Tags supported are:
	 *		^LABEL = name of preference
	 *		^ELEMENT = HTML code to generate form widget
	 * If you omit $ps_format, the element code alone (content of ^ELEMENT) is returned.
	 * @return string HTML code to generate form widget
	 */	
	public function preferenceHtmlFormElement($ps_pref, $ps_format=null, $pa_options=null) {
		if ($this->isValidPreference($ps_pref)) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			$o_db = $this->getDb();
			
			$va_pref_info = $this->getPreferenceInfo($ps_pref);
			
			$vs_current_value = $this->getPreference($ps_pref);
			$vs_output = "";
			
			foreach(array(
				'displayType', 'displayWidth', 'displayHeight', 'length', 'formatType', 'choiceList',
				'label', 'description'
			) as $vs_k) {
				if (!isset($va_pref_info[$vs_k])) { $va_pref_info[$vs_k] = null; }
			}
			
			switch($va_pref_info["displayType"]) {
				# ---------------------------------
				case 'DT_FIELD':
					if (($vn_display_width = $va_pref_info["displayWidth"]) < 1) {
						$vn_display_width = 20;
					}
					if (($vn_display_height = $va_pref_info["displayHeight"]) < 1) {
						$vn_display_height = 1;
					}
					
					if (isset($va_pref_info["length"]["maximum"])) {
						$vn_max_input_length = $va_pref_info["length"]["maximum"];
					} else {
						$vn_max_input_length = $vn_display_width;
					}
					
					if ($vn_display_height > 1) {
						$vs_output = "<textarea name='pref_$ps_pref' rows='".$vn_display_height."' cols='".$vn_display_width."'>".htmlspecialchars($vs_current_value, ENT_QUOTES, 'UTF-8')."</textarea>\n";
					} else {
						$vs_output = "<input type='text' name='pref_$ps_pref' size='$vn_display_width' maxlength='$vn_max_input_length' value='".htmlspecialchars($vs_current_value, ENT_QUOTES, 'UTF-8')."'/>\n";
					}
					break;
				# ---------------------------------
				case 'DT_SELECT':
					$vs_output = "<select name='pref_$ps_pref'>\n";
					
					switch($va_pref_info['formatType']) {
						case 'FT_UI_LOCALE':
							$va_locales = array();
							if ($r_dir = opendir(__CA_APP_DIR__.'/locale/')) {
								while (($vs_locale_dir = readdir($r_dir)) !== false) {
									if ($vs_locale_dir{0} == '.') { continue; }
									if (sizeof($va_tmp = explode('_', $vs_locale_dir)) == 2) {
										$va_locales[$vs_locale_dir] = $va_tmp;
									}
								}
							}
							
							$va_opts = array();
							$t_locale = new ca_locales();
							foreach($va_locales as $vs_code => $va_parts) {
								try {
									$vs_lang_name = Zend_Locale::getTranslation(strtolower($va_parts[0]), 'language', strtolower($va_parts[0]));
									$vs_country_name = Zend_Locale::getTranslation($va_parts[1], 'Country', $vs_code);
								} catch (Exception $e) {
									$vs_lang_name = strtolower($va_parts[0]);
									$vs_country_name = $vs_code;
								}
								$va_opts[($vs_lang_name ? $vs_lang_name : $vs_code).($vs_country_name ? ' ('.$vs_country_name.')':'')] = $vs_code;
							}
							break;
						case 'FT_LOCALE':
							$qr_locales = $o_db->query("
								SELECT *
								FROM ca_locales
								ORDER BY 
									name
							");
							$va_opts = array();
							while($qr_locales->nextRow()) {
								$va_opts[$qr_locales->get('name')] = $qr_locales->get('language').'_'.$qr_locales->get('country');
							}
							break;
						case 'FT_THEME':
							if ($r_dir = opendir($this->_CONFIG->get('themes_directory'))) {
								$va_opts = array();
								while (($vs_theme_dir = readdir($r_dir)) !== false) {
									if ($vs_theme_dir{0} == '.') { continue; }
										$o_theme_info = Configuration::load($this->_CONFIG->get('themes_directory').'/'.$vs_theme_dir.'/themeInfo.conf');
										$va_opts[$o_theme_info->get('name')] = $vs_theme_dir;
								}
							}
							break;
						case 'FT_OBJECT_EDITOR_UI':
						case 'FT_OBJECT_LOT_EDITOR_UI':
						case 'FT_ENTITY_EDITOR_UI':
						case 'FT_PLACE_EDITOR_UI':
						case 'FT_OCCURRENCE_EDITOR_UI':
						case 'FT_COLLECTION_EDITOR_UI':
						case 'FT_STORAGE_LOCATION_EDITOR_UI':
						case 'FT_OBJECT_REPRESENTATION_EDITOR_UI':
						case 'FT_REPRESENTATION_ANNOTATION_EDITOR_UI':
						case 'FT_SET_EDITOR_UI':
						case 'FT_SET_ITEM_EDITOR_UI':
						case 'FT_LIST_EDITOR_UI':
						case 'FT_LIST_ITEM_EDITOR_UI':
						
							switch($va_pref_info['formatType']) {
								case 'FT_OBJECT_EDITOR_UI':
									$vn_table_num = 57;
									break;
								case 'FT_OBJECT_LOT_EDITOR_UI':
									$vn_table_num = 51;
									break;
								case 'FT_ENTITY_EDITOR_UI':
									$vn_table_num = 20;
									break;
								case 'FT_PLACE_EDITOR_UI':
									$vn_table_num = 72;
									break;
								case 'FT_OCCURRENCE_EDITOR_UI':
									$vn_table_num = 67;
									break;
								case 'FT_COLLECTION_EDITOR_UI':
									$vn_table_num = 13;
									break;
								case 'FT_STORAGE_LOCATION_EDITOR_UI':
									$vn_table_num = 89;
									break;
								case 'FT_OBJECT_REPRESENTATION_EDITOR_UI':
									$vn_table_num = 56;
									break;
								case 'FT_REPRESENTATION_ANNOTATION_EDITOR_UI':
									$vn_table_num = 82;
									break;
								case 'FT_SET_EDITOR_UI':
									$vn_table_num = 103;
									break;
								case 'FT_SET_ITEM_EDITOR_UI':
									$vn_table_num = 105;
									break;
								case 'FT_LIST_EDITOR_UI':
									$vn_table_num = 36;
									break;
								case 'FT_LIST_ITEM_EDITOR_UI':
									$vn_table_num = 33;
									break;
							}
							$qr_uis = $o_db->query("
								SELECT *
								FROM ca_editor_uis ceui
								INNER JOIN ca_editor_ui_labels AS ceuil ON ceui.ui_id = ceuil.ui_id
								WHERE
									(ceui.user_id = ? OR ceui.is_system_ui = 1) AND (ceui.editor_type = ?)
							", (int)$this->getPrimaryKey(), (int)$vn_table_num);
							$va_opts = array();
							while($qr_uis->nextRow()) {
								$va_opts[$qr_uis->get('ui_id')][$qr_uis->get('locale_id')] = $qr_uis->get('name');
							}
							
							$va_opts = caExtractValuesByUserLocale($va_opts);
							
							if (!is_array($va_opts) || (sizeof($va_opts) == 0)) { $vs_output = ''; break(2); }
							foreach($va_opts as $vs_val => $vs_opt) {
								$vs_selected = ($vs_val == $vs_current_value) ? "SELECTED" : "";
								$vs_output .= "<option value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."' $vs_selected>".$vs_opt."</option>\n";	
							}
							$vs_output .= "</select>\n";
							break(2);
						default:
							$va_opts = $va_pref_info["choiceList"];
							break;
					}
					if (!is_array($va_opts) || (sizeof($va_opts) == 0)) { $vs_output = ''; break; }
					foreach($va_opts as $vs_opt => $vs_val) {
						$vs_selected = ($vs_val == $vs_current_value) ? "selected='1'" : "";
						$vs_output .= "<option value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."' $vs_selected>".$vs_opt."</option>\n";	
					}
					$vs_output .= "</select>\n";
					break;
				# ---------------------------------
				case 'DT_CHECKBOXES':
					if ($va_pref_info["formatType"] == 'FT_BIT') {
						$vs_selected = ($vs_current_value) ? "CHECKED" : "";
						$vs_output .= "<input type='checkbox' name='pref_$ps_pref' value='1' $vs_selected>\n";	
					} else {
						foreach($va_pref_info["choiceList"] as $vs_opt => $vs_val) {
							if (is_array($vs_current_value)) {
								$vs_selected = (in_array($vs_val, $vs_current_value)) ? "CHECKED" : "";
							} else {
								$vs_selected = "";
							}
							$vs_output .= "<input type='checkbox' name='pref_".$ps_pref."[]' value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."' $vs_selected> ".$vs_opt." \n";	
						}
					}
					break;
				# ---------------------------------
				case 'DT_RADIO_BUTTONS':
					foreach($va_pref_info["choiceList"] as $vs_opt => $vs_val) {
						$vs_selected = ($vs_val == $vs_current_value) ? "CHECKED" : "";
						$vs_output .= "<input type='radio' name='pref_$ps_pref' value='".htmlspecialchars($vs_val, ENT_QUOTES, 'UTF-8')."' $vs_selected> ".$vs_opt." \n";	
					}
					break;
				# ---------------------------------
				case 'DT_PASSWORD':
					if (($vn_display_width = $va_pref_info["displayWidth"]) < 1) {
						$vn_display_width = 20;
					}
					
					if (isset($va_pref_info["length"]["maximum"])) {
						$vn_max_input_length = $va_pref_info["length"]["maximum"];
					} else {
						$vn_max_input_length = $vn_display_width;
					}
					
					$vs_output = "<input type='password' name='pref_$ps_pref' size='$vn_display_width' maxlength='$vn_max_input_length' value='".htmlspecialchars($vs_current_value, ENT_QUOTES, 'UTF-8')."'/>\n";
					
					break;
				# ---------------------------------
				default:
					return "Configuration error: Invalid display type for $ps_pref";
				# ---------------------------------
			}
			
			if (is_null($ps_format)) {
				if (isset($pa_options['field_errors']) && is_array($pa_options['field_errors']) && sizeof($pa_options['field_errors'])) {
					$ps_format = $this->_CONFIG->get('form_element_error_display_format');
					$va_field_errors = array();
					foreach($pa_options['field_errors'] as $o_e) {
						$va_field_errors[] = $o_e->getErrorDescription();
					}
					$vs_errors = join('; ', $va_field_errors);
				} else {
					$ps_format = $this->_CONFIG->get('form_element_display_format');
					$vs_errors = '';
				}
			}
			if ($ps_format && $vs_output) {
				$vs_format = $ps_format;
				$vs_format = str_replace("^ELEMENT", $vs_output, $vs_format);
			} else {
				$vs_format = $vs_output;
			}
			
			$vs_format = str_replace("^EXTRA", '',  $vs_format);
			if (preg_match("/\^DESCRIPTION/", $vs_format)) {
				$vs_format = str_replace("^LABEL", _t($va_pref_info["label"]), $vs_format);
				$vs_format = str_replace("^DESCRIPTION", _t($va_pref_info["description"]), $vs_format);
			} else {
				// no explicit placement of description text, so...
				$vs_field_id = 'pref_'.$ps_pref;
				$vs_format = str_replace("^LABEL",'<span id="'.$vs_field_id.'">'._t($va_pref_info["label"]).'</span>', $vs_format);
				
				TooltipManager::add('#'.$vs_field_id, "<h3>".$va_pref_info["label"]."</h3>".$va_pref_info["description"]);
			}
			return $vs_format;

		} else {
			return "";
		}
	}
	# ----------------------------------------
/**
 * Returns preference information array for specified preference directly from definition file.
 *
 * @access public
 * @param string $ps_pref Name of user preference
 * @return array Information array, directly from definition file
 */	
	public function getPreferenceInfo($ps_pref) {
		$this->loadUserPrefDefs();
		$va_prefs = $this->_user_pref_defs->getAssoc("preferenceDefinitions");
		return $va_prefs[$ps_pref];
	}
	# ----------------------------------------
/**
 * Loads user_pref_defs config file
 *
 * @access public
 * @param boolean $pb_force_reload If true, load defs file even if it has already been loaded
 * @return void
 */	
	
	public function loadUserPrefDefs($pb_force_reload=false) {
		if (!$this->_user_pref_defs || $pb_force_reload) {
			if ($vs_user_pref_def_path = $this->getAppConfig()->get("user_pref_defs")) {
				$this->_user_pref_defs = Configuration::load($vs_user_pref_def_path, $pb_force_reload);
				return true;
			}
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Returns preference group information array for specified preference directly from definition file.
	 *
	 * @access public
	 * @param string $ps_pref_group Name of user preference group
	 * @return array Information array, directly from definition file
	 */	
	public function getPreferenceGroupInfo($ps_pref_group) {
		$this->loadUserPrefDefs();
		$va_groups = $this->_user_pref_defs->getAssoc("preferenceGroups");
		return $va_groups[$ps_pref_group];
	}
	# ----------------------------------------
	# User's saved searches
	# ----------------------------------------
	/**
	 * Add a saved search to the user's profile
	 *
	 * @param mixed $pm_table_name_or_num Table name or number of search target (eg. ca_objects or 57 for an object search)
	 * @param string $ps_type A search type descriptor. This is just a string used to distinguish different types of searches (eg. basic vs. advanced) and is typically set to the "find_type" property value set in subclasses of BaseSearchController
	 * @param array $pa_search An array containing actual search parameters. For a basic search, this will have one key: "search"; for advanced searches it will have keys for all form values
	 * @return mixed Returns md5 key for saved search or boolean false if search could not be saved
	 */
	public function addSavedSearch($pm_table_name_or_num, $ps_type, $pa_search) {
		if (!is_array($va_saved_searches = $this->getVar('saved_searches'))) {
			$va_saved_searches = array();
		}
		
		$o_dm = Datamodel::load();
		if (!($vn_table_num = $o_dm->getTableNum($pm_table_name_or_num))) { return false; }
		
		if(!is_array($va_searches = $this->getVar('saved_searches'))) { $va_searches = array(); }
		
		$vs_md5 = md5(print_r($pa_search, true));
		
		if (isset($va_searches[$vn_table_num][strtolower($ps_type)][$vs_md5])) {
			// is duplicate
			return false;
		}
		$va_searches[$vn_table_num][strtolower($ps_type)][$vs_md5] = $pa_search;
		
		$this->setVar('saved_searches', $va_searches);
		
		return $vs_md5;
	}
	# ----------------------------------------
	/**
	 * Removes the specified search from the user's saved search list
	 *
	 * @param mixed $pm_table_name_or_num Table name or number of search target (eg. ca_objects or 57 for an object search)
	 * @param string $ps_type A search type descriptor. This is just a string used to distinguish different types of searches (eg. basic vs. advanced) and is typically set to the "find_type" property value set in subclasses of BaseSearchController
	 * @param string $ps_key The 32 character md5 hash key for the saved search
	 * @return boolean Returns true if specified search was cleared, false if not
	 */
	public function removeSavedSearch($pm_table_name_or_num, $ps_type, $ps_key) {
		$o_dm = Datamodel::load();
		if (!($vn_table_num = $o_dm->getTableNum($pm_table_name_or_num))) { return false; }
		
		if(!is_array($va_searches = $this->getVar('saved_searches'))) { return false; }
		unset($va_searches[$vn_table_num][strtolower($ps_type)][$ps_key]);
		$this->setVar('saved_searches', $va_searches);
		
		return true;
	}
	# ----------------------------------------
	/**
	 * Removes all searches for the specified table and, if specified, search type. If both parameters are omitted then all saved searches for all search targets are removed.
	 *
	 * @param mixed $pm_table_name_or_num Optional table name or number of search target (eg. ca_objects or 57 for an object search)
	 * @param string $ps_type Optional search type descriptor. This is just a string used to distinguish different types of searches (eg. basic vs. advanced) and is typically set to the "find_type" property value set in subclasses of BaseSearchController
	 * @return boolean True if searches were cleared, false if the operation failed
	 */
	public function clearSavedSearches($pm_table_name_or_num=null, $ps_type=null) {
		$o_dm = Datamodel::load();
		if ($pm_table_name_or_num) {
			$vn_table_num = $o_dm->getTableNum($pm_table_name_or_num);
		}
		
		if(!is_array($va_searches = $this->getVar('saved_searches'))) { $va_searches = array(); }
		if ($vn_table_num && $ps_type) {
			unset($va_searches[$vn_table_num][strtolower($ps_type)]);
			$this->setVar('saved_searches', $va_searches);
			
			return true;
		} else {
			if ($vn_table_num) {
				unset($va_searches[$vn_table_num]);
				$this->setVar('saved_searches', $va_searches);
				
				return true;
			} else {
				// clear everything
				$this->setVar('saved_searches', array());
				return true;
			}
		}
		
		return false;
	}
	# ----------------------------------------
	/**
	 * Returns information about a single saved search based upon search key. The key is a 32 character md5 hash 
	 *
	 * @param mixed $pm_table_name_or_num Table name or number of search target (eg. ca_objects or 57 for an object search)
	 * @param string $ps_type A search type descriptor. This is just a string used to distinguish different types of searches (eg. basic vs. advanced) and is typically set to the "find_type" property value set in subclasses of BaseSearchController
	 * @param string $ps_key The 32 character md5 hash key for the saved search
	 * @return array An array containing the search parameters + 2 special entries: (1) _label is a display label for the search (2) _form_id is the ca_search_forms.form_id for the search, if the search was form-based. _form_id will be undefined if the search was basic (eg. simple one-entry text search)
	 */
	public function getSavedSearchByKey($pm_table_name_or_num, $ps_type, $ps_key) {
		$o_dm = Datamodel::load();
		if (!($vn_table_num = $o_dm->getTableNum($pm_table_name_or_num))) { return false; }
		if(!is_array($va_searches = $this->getVar('saved_searches'))) { $va_searches = array(); }
		
		return is_array($va_searches[$vn_table_num][strtolower($ps_type)][$ps_key]) ? $va_searches[$vn_table_num][strtolower($ps_type)][$ps_key] : array();
	}
	# ----------------------------------------
	/**
	 * Returns list of saved searches for the specified search target and search type
	 *
	 * @param mixed $pm_table_name_or_num Table name or number of search target (eg. ca_objects or 57 for an object search)
	 * @param string $ps_type A search type descriptor. This is just a string used to distinguish different types of searches (eg. basic vs. advanced) and is typically set to the "find_type" property value set in subclasses of BaseSearchController
	 * @return array An array of saved searches, or an empty array if no searches have been saved. The array's keys are 32 character md5 saved search keys. The values are arrays with the search parameters + 2 special entries: (1) _label is a display label for the search (2) _form_id is the ca_search_forms.form_id for the search, if the search was form-based. _form_id will be undefined if the search was basic (eg. simple one-entry text search)
	 */
	public function getSavedSearches($pm_table_name_or_num, $ps_type) {
		$o_dm = Datamodel::load();
		if (!($vn_table_num = $o_dm->getTableNum($pm_table_name_or_num))) { return false; }
		if(!is_array($va_searches = $this->getVar('saved_searches'))) { $va_searches = array(); }
	
		return is_array($va_searches[$vn_table_num][strtolower($ps_type)]) ? $va_searches[$vn_table_num][strtolower($ps_type)] : array();
	}
	# ----------------------------------------
	# Utils
	# ----------------------------------------
	/**
	 * Check if a user name exists
	 *
	 * @param mixed $ps_user_name_or_id The user name or numeric user_id of the user
	 * @return boolean True if user exists, false if not
	 */
	public function exists($ps_user_name_or_id) {
		$t_user = new User();
		if ($t_user->load($ps_user_name_or_id)) {
			return true;
		} else {
			if ($t_user->load(array("user_name" => $ps_user_name_or_id))) {
				return true;
			}
		}
		return false;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function addIp($pn_ip1, $pn_ip2, $pn_ip3, $pn_ip4s, $pn_ip4e, $ps_notes) {
		if (!$this->getPrimaryKey()) { return array(); }
		
		if (($pn_ip1 < 1) || ($pn_ip1 > 255)) { return false;}
		if (($pn_ip2 < 0) || ($pn_ip2 > 255)) { return false;}
		if (!$pn_ip3 || ($pn_ip3 < 0) || ($pn_ip3 > 255)) { $pn_ip3 = "NULL";}
		if (!$pn_ip4s || ($pn_ip4s < 1) || ($pn_ip4s > 255)) { 
			$pn_ip4s = "NULL";
			$pn_ip4e = "NULL";
		} else {
			if (!$pn_ip4e || ($pn_ip4e < 1) || ($pn_ip4e > 255) || ($pn_ip4e < $pn_ip4s)) { 
				$pn_ip4e = $pn_ip4s;
			}
		}
		
		$o_db = $this->getDb();
		$o_db->query("
			INSERT INTO ca_ips
			(user_id, ip1, ip2, ip3, ip4s, ip4e,notes)
			VALUES
			(?,$pn_ip1, $pn_ip2, $pn_ip3, $pn_ip4s, $pn_ip4e, ?)
		", (int)$this->getPrimaryKey(), (string)$ps_notes);
		if ($o_db->numErrors()) {
			$this->errors = array_merge($this->errors, $o_db->errors());
			return false;
		} else {
			return true;
		}
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function removeIp($pn_ip_id) {
		if (!$this->getPrimaryKey()) { return array(); }
		$o_db = $this->getDb();
		$o_db->query("
			DELETE
			FROM ca_ips
			WHERE
				(user_id = ?) AND
				(ip_id = ?)
		", (int)$this->getPrimaryKey(), (int)$pn_ip_id);
		
		if ($o_db->numErrors()) {
			return false;
		} else {
			return true;
		}
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function &getIpList() {
		if (!$this->getPrimaryKey()) { return array(); }
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_ips
			WHERE
				(user_id = ?)
		", (int)$this->getPrimaryKey());
		
		$va_ips = array();
		while($qr_res->nextRow()) {
			$va_ips[] = $qr_res->getRow();
		}
		return $va_ips;
	}
	# ----------------------------------------
	# Auth API methods
	# ----------------------------------------
	/**
	 *
	 */
	public function close() {
		$this->setMode(ACCESS_WRITE);
		$this->update();
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getUserID() {
		return $this->getPrimaryKey();
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getName() {
		return $this->get("fname")." ". $this->get("lname");
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function isActive() {
		return ($this->get("active") && ($this->get("userclass") != 255)) ? true : false;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getLastPing() {
		return $this->getVar($this->getAppConfig()->get("app_name")."_last_ping");
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function setLastPing($pn_time) {
		$this->setVar($this->getAppConfig()->get("app_name")."_last_ping", $pn_time, array('volatile' => true));
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function setLastLogout($pn_time) {
		$this->setVar($this->getAppConfig()->get("app_name")."_previous_to_last_logout", $this->getLastLogout(), array('volatile' => true));
		$this->setVar($this->getAppConfig()->get("app_name")."_last_logout", $pn_time, array('volatile' => true));
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getLastLogout() {
		return $this->getVar($this->getAppConfig()->get("app_name")."_last_logout");
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getNextToLastLogout() {
		return $this->getVar($this->getAppConfig()->get("app_name")."_previous_to_last_logout");
	}
	# ----------------------------------------
	/**
	 * This is a option-less authentication. Either your login works or it doesn't.
	 * Other apps implementing this interface may need to know what you're trying to do 
	 * in order to make a decision; $pa_options is an associative array of User handler-specific
	 * keys and values that can contain such information
	 */
	public function authenticate(&$ps_username, $ps_password="", $pa_options="") {
		if ($this->load(array("user_name" => $ps_username))) {
			if ($this->verify($ps_password) && $this->isActive()) {
				return true;
			}
		}
		
		// check ips
		if (!isset($pa_options["dont_check_ips"]) || !$pa_options["dont_check_ips"]) {
			if ($vn_user_id = $this->ipAuthenticate()) {
				if ($this->load($vn_user_id)) {
					$ps_username = $this->get("user_name");
					return 2;
				} 
			}
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * 
	 * Looks IP address up in ca_ips database. Returns true and loads information for the 
	 * IP if the address is in the database, or false if the address is not in the database.
	 *
	 * @access public 
	 * @param string IP address to authenticate. If it is omitted, the current client ip (taken from the REMOTE_ADDR environment variable) is used.
	 * @return bool True if ip is in the database (also loads the ip record into the instance); false if ip does not exist in the database.
	 */	
	public function ipAuthenticate($ip = "") {
		if (!$ip) { $ip = getEnv("REMOTE_ADDR");}
		$ipp = explode(".",$ip);
		if (sizeof($ipp) == 4) {
			$chk = array();
			for($i=0; $i<4;$i++) {
				if ($i == 3) {
					$chk[] = "((ip4s <= ".$ipp[$i]." AND ip4e >= ".$ipp[$i].") OR (ip4s IS NULL AND ip4e IS NULL))";
				} else {
					$chk[] = "(ip".($i+1)." = ".$ipp[$i].")";
				}
			}
	
			$i = 4;
			
			$o_db = $this->getDb();
			while ($i > 0) {
				$sql = "
					SELECT wip.ip_id, wip.user_id 
					FROM ca_ips wip
					INNER JOIN ca_users AS wu ON wu.user_id = wip.user_id
					WHERE 
				";
				$sql .= join (" AND ", $chk);
				$qr_res = $o_db->query($sql);
				
				if($qr_res->nextRow()) {
					# got rule
					return $qr_res->get("user_id");
				} else {
					array_pop($chk);
					if ($i < 4) {
						array_unshift($chk, "(ip$i IS NULL)");
					} else {
						array_unshift($chk, "(ip4s IS NULL AND ip4e IS NULL)");
					}
					$i--;
				}
			}
		}
		return false;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getClassName() {
		return "ca_users";
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getPreferredUILocale() {
		$t_locale = new ca_locales();
		if ($vs_locale = $this->getPreference('ui_locale')) {
			return $vs_locale;
		} 
		
		$va_default_locales = $this->getAppConfig()->getList('locale_defaults');
		if (sizeof($va_default_locales)) {
			return $va_default_locales[0];
		}
		
		die(_t("No default UI locale is available"));
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getPreferredUILocaleID() {
		$t_locale = new ca_locales();
		if ($vs_locale = $this->getPreference('ui_locale')) {
			if ($t_locale->loadLocaleByCode($vs_locale)) {
				return $t_locale->getPrimaryKey();
			}
		} 
		
		$va_default_locales = $this->getAppConfig()->getList('locale_defaults');
		if (sizeof($va_default_locales) && $vn_locale_id = $t_locale->loadLocaleByCode($va_default_locales[0])) {
			return $vn_locale_id;
		}
		die(_t("No default UI locale is available"));
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function getPreferredDisplayLocaleIDs($pn_item_locale_id=null) {
		$vs_mode = $this->getPreference('cataloguing_display_label_mode');
		
		$va_locale_ids = array();
		switch($vs_mode) {
			case 'cataloguing_locale':
				if ($vs_locale = $this->getPreference('cataloguing_locale')) {
					$t_locale = new ca_locales();
					if ($t_locale->loadLocaleByCode($vs_locale)) {
						$va_locale_ids[$t_locale->getPrimaryKey()] = true;
					}
				}
				break;
			case 'item_locale':
				if ($pn_item_locale_id) { 
					$va_locale_ids[$pn_item_locale_id] = true;
				}
				break;
			case 'cataloguing_and_item_locale':
			default:
				if ($vs_locale = $this->getPreference('cataloguing_locale')) {
					$t_locale = new ca_locales();
					if ($t_locale->loadLocaleByCode($vs_locale)) {
						$va_locale_ids[$t_locale->getPrimaryKey()] = true;
					}
				}
				if ($pn_item_locale_id) { 
					$va_locale_ids[$pn_item_locale_id] = true;
				}
				break;
		}
		return array_keys($va_locale_ids);
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function isStandardUser() {
		return (((int)$this->get('userclass') === 0) ?  true : false);
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function isPublicUser() {
		return (((int)$this->get('userclass') === 1) ?  true : false);
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function isDeletedUser() {
		return (((int)$this->get('userclass') === 255) ?  true : false);
	}
	# ----------------------------------------
	# Authorization methods
	# ----------------------------------------
	/**
	 * Checks if user is allowed to perform the specified action (possible actions are defined in app/conf/user_actions.conf)
	 * Returns true if user can do action, false otherwise.
	 */
	public function canDoAction($ps_action) {
		if(!$this->getPrimaryKey()) return false; // "empty" ca_users object -> no groups or roles associated -> can't do action

		// is user administrator?
		if ($this->getPrimaryKey() == $this->_CONFIG->get('administrator_user_id')) { return true; }	// access restrictions don't apply to user with user_id = admin id
		
		// get user roles
		$va_roles = $this->getUserRoles();
		foreach($this->getGroupRoles() as $vn_role_id => $va_role_info) {
			$va_roles[$vn_role_id] = $va_role_info;
		}
		
		$va_actions = ca_user_roles::getActionsForRoleIDs(array_keys($va_roles));
		if (in_array('is_administrator', $va_actions)) { return true; }		// access restrictions don't apply to users with is_administrator role
		return in_array($ps_action, $va_actions);
	}
	# ----------------------------------------
	/**
	 * Returns the type of access the user has to the specified bundle.
	 * Types of access are:
	 *		- EDIT (implies ability to view and change bundle content)
	 *		- VIEW (implies ability to view bundle content only)
	 *		- NONE (indicates that the user has no access to bundle)
	 */
	public function getBundleAccessLevel($ps_action) {
		// TODO
	}
	# ----------------------------------------
	/**
	 * Determine if a user is allowed to access a certain module/controller/action combination
	 *
	 * @param array $pa_module_path
	 * @param string $ps_controller
	 * @param string $ps_action
	 * @return bool
	 */
	public function canAccess($pa_module_path,$ps_controller,$ps_action){
		$vo_acr = AccessRestrictions::load();
		return $vo_acr->userCanAccess($this->getUserID(), $pa_module_path, $ps_controller, $ps_action);
	}
	# ----------------------------------------
}
?>