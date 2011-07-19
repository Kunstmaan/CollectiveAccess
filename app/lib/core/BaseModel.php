<?php
/** ---------------------------------------------------------------------
 * app/lib/core/BaseModel.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2000-2011 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

# ------------------------------------------------------------------------------------
# --- Field type constants
# ------------------------------------------------------------------------------------
define("FT_NUMBER",0);
define("FT_TEXT", 1);
define("FT_TIMESTAMP", 2);
define("FT_DATETIME", 3);
define("FT_HISTORIC_DATETIME", 4);
define("FT_DATERANGE", 5);
define("FT_HISTORIC_DATERANGE", 6);
define("FT_BIT", 7);
define("FT_FILE", 8);
define("FT_MEDIA", 9);
define("FT_PASSWORD", 10);
define("FT_VARS", 11);
define("FT_TIMECODE", 12);
define("FT_DATE", 13);
define("FT_HISTORIC_DATE", 14);
define("FT_TIME", 15);
define("FT_TIMERANGE", 16);
# ------------------------------------------------------------------------------------
# --- Display type constants
# ------------------------------------------------------------------------------------
define("DT_SELECT", 0);
define("DT_LIST", 1);
define("DT_LIST_MULTIPLE", 2);
define("DT_CHECKBOXES", 3);
define("DT_RADIO_BUTTONS", 4);
define("DT_FIELD", 5);
define("DT_HIDDEN", 6);
define("DT_OMIT", 7);
define("DT_TEXT", 8);
define("DT_PASSWORD", 9);
define("DT_COLORPICKER", 10);
define("DT_TIMECODE", 12);
# ------------------------------------------------------------------------------------
# --- Access mode constants
# ------------------------------------------------------------------------------------
define("ACCESS_READ", 0);
define("ACCESS_WRITE", 1);

# ------------------------------------------------------------------------------------
# --- Text-markup constants
# ------------------------------------------------------------------------------------
define("__CA_MT_HTML__", 0);
define("__CA_MT_TEXT_ONLY__", 1);

# ------------------------------------------------------------------------------------
# --- Hierarchy type constants
# ------------------------------------------------------------------------------------
define("__CA_HIER_TYPE_SIMPLE_MONO__", 1);
define("__CA_HIER_TYPE_MULTI_MONO__", 2);
define("__CA_HIER_TYPE_ADHOC_MONO__", 3);
define("__CA_HIER_TYPE_MULTI_POLY__", 4);

# ----------------------------------------------------------------------
# --- Import classes
# ----------------------------------------------------------------------
require_once(__CA_LIB_DIR__."/core/BaseObject.php");
require_once(__CA_LIB_DIR__."/core/Error.php");
require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__."/core/ApplicationChangeLog.php");
require_once(__CA_LIB_DIR__."/core/Parsers/TimeExpressionParser.php");
require_once(__CA_LIB_DIR__."/core/Parsers/TimecodeParser.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_LIB_DIR__."/core/Media.php");
require_once(__CA_LIB_DIR__."/core/Media/MediaVolumes.php");
require_once(__CA_LIB_DIR__."/core/File.php");
require_once(__CA_LIB_DIR__."/core/File/FileVolumes.php");
require_once(__CA_LIB_DIR__."/core/Utils/Timer.php");
require_once(__CA_LIB_DIR__."/core/Utils/Unicode.php");
require_once(__CA_LIB_DIR__."/core/Search/SearchIndexer.php");
require_once(__CA_LIB_DIR__."/core/Db/Transaction.php");
require_once(__CA_LIB_DIR__."/core/Media/MediaProcessingSettings.php");
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");
require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");

/**
 * Base class for all database table classes. Implements database insert/update/delete
 * functionality and a whole lot more, including automatic generation of HTML form
 * widgets, layering of additional field types and data processing functionality
 * (media upload fields using the Media class; date/time and date range fields
 * with natural language input using the TimeExpressionParser class; serialized
 * variable stores using php serialize(), automatic management of hierarchies, etc.)
 * Each table in your database should have a class that extends BaseModel.
 */
 
 
class BaseModel extends BaseObject {
	# --------------------------------------------------------------------------------
	# --- Properties
	# --------------------------------------------------------------------------------
	/**
	 * representation of the access mode of the current instance.
	 * 0 (ACCESS_READ) means read-only,
	 * 1 (ACCESS_WRITE) characterizes a BaseModel instance that is able to write to the database.
	 *
	 * @access private
	 */
	private $ACCESS_MODE;

	/**
	 * local Db object for database access
	 *
	 * @access private
	 */
	private $o_db;

	/**
	 * debug mode state
	 *
	 * @access private
	 */
	var $debug = 0;


	/**
	 * @access private
	 */
	var $_FIELD_VALUES;

	/**
	 * @access protected
	 */
	protected $_FIELD_VALUE_CHANGED;

	/**
	 * @access private
	 */
	var $_FIELD_VALUES_OLD;

	/**
	 * @access private
	 */
	private $_FILES;
	
	/**
	 * @access private
	 */
	private $_SET_FILES;

	/**
	 * @access private
	 */
	private $_FILES_CLEAR;

	/**
	 * object-oriented access to media volume information
	 *
	 * @access private
	 */
	private $_MEDIA_VOLUMES;

	/**
	 * object-oriented access to file volume information
	 *
	 * @access private
	 */
	private $_FILE_VOLUMES;

	/**
	 * if true, DATETIME fields take Unix date/times,
	 * not parseable date/time expressions
	 *
	 * @access private
	 */
	private $DIRECT_DATETIMES = 0;

	/**
	 * local Configuration object representation
	 *
	 * @access protected
	 */
	protected $_CONFIG;

	/**
	 * local Datamodel object representation
	 *
	 * @access protected
	 */
	protected $_DATAMODEL = null;

	/**
	 * contains current Transaction object
	 *
	 * @access protected
	 */
	protected $_TRANSACTION = null;

	/**
	 * The current locale. Used to determine which set of localized error messages to use. Default is US English ("en_us")
	 *
	 * @access private
	 */
	var $ops_locale = "en_us";				#

	/**
	 * prepared change log statement (primary log entry)
	 *
	 * @access private
	 */
	private $opqs_change_log;

	/**
	 * prepared change log statement (log subject entries)
	 *
	 * @access private
	 */
	private $opqs_change_log_subjects;

	/**
	 * prepared statement to get change log
	 *
	 * @access private
	 */
	private $opqs_get_change_log;

	/**
	 * prepared statement to get change log
	 *
	 * @access private
	 */
	private $opqs_get_change_log_subjects;		#

	/**
	 * array containing parsed version string from
	 *
	 * @access private
	 */
	private $opa_php_version;				#


	# --------------------------------------------------------------------------------
	# --- Error handling properties
	# --------------------------------------------------------------------------------

	/**
	 * Array of error objects
	 *
	 * @access public
	 */
	public $errors;

	/**
	 * If true, on error error message is printed and execution halted
	 *
	 * @access private
	 */
	private $error_output;
	
	/**
	 * List of fields that had conflicts with existing data during last update()
	 * (ie. someone else had already saved to this field while the user of this instance was working)
	 *
	 * @access private
	 */
	private $field_conflicts;



	static public $search_indexer;
	
	
	static $s_ca_models_definitions;
	
	/**
	 * Constructor
	 * In general you should not call this constructor directly. Any table in your database
	 * should be represented by an extension of this class.
	 *
	 * @param int $pn_id primary key identifier of the table row represented by this object
	 * if omitted, an empty object is created which can be used to create a new row in the database.
	 * @return BaseModel
	 */
	public function __construct($pn_id=null) {
		$vs_table_name = $this->tableName();
		if (!$this->FIELDS =& BaseModel::$s_ca_models_definitions[$vs_table_name]['FIELDS']) {
			die("Field definitions not found for {$vs_table_name}");
		}
		$this->NAME_SINGULAR =& BaseModel::$s_ca_models_definitions[$vs_table_name]['NAME_SINGULAR'];
		$this->NAME_PLURAL =& BaseModel::$s_ca_models_definitions[$vs_table_name]['NAME_PLURAL'];
		
		$this->errors = array();
		$this->error_output = 0;  # don't halt on error
		$this->field_conflicts = array();

		$this->_CONFIG = Configuration::load();
		$this->_DATAMODEL = Datamodel::load();
		$this->_FILES_CLEAR = array();
		$this->_SET_FILES = array();
		$this->_MEDIA_VOLUMES = MediaVolumes::load();
		$this->_FILE_VOLUMES = FileVolumes::load();
		$this->_FIELD_VALUE_CHANGED = array();

		if ($vs_locale = $this->_CONFIG->get("locale")) {
			$this->ops_locale = $vs_locale;
		}
		
 		$this->opo_app_plugin_manager = new ApplicationPluginManager();

		$this->setMode(ACCESS_READ);

		if ($pn_id) { $this->load($pn_id);}
	}

	/**
	 * Get Db object
	 * If a transaction is pending, the Db object representation is taken from the Transaction object,
	 * if not, a new connection is established, stored in a local property and returned.
	 *
	 * @return Db
	 */
	public function getDb() {
		if ($this->inTransaction()) {
			$this->o_db = $this->getTransaction()->getDb();
		} else {
			if (!$this->o_db) {
				$this->o_db = new Db();
				$this->o_db->dieOnError(false);
			}
		}
		return $this->o_db;
	}
	
	/**
	 * Convenience method to return application configuration object. This is the same object
	 * you'd get if you instantiated a Configuration() object without any parameters 
	 *
	 * @return Configuration
	 */
	public function getAppConfig() {
		return $this->_CONFIG;
	}
	
	/**
	 * Convenience method to return application datamodel object. This is the same object
	 * you'd get if you instantiated a Datamodel() object
	 *
	 * @return Configuration
	 */
	public function getAppDatamodel() {
		return $this->_DATAMODEL;
	}
	/**
	 * Get character set from configuration file. Defaults to
	 * UTF8 if configuration parameter "character_set" is
	 * not found.
	 *
	 * @return string the character set
	 */
	public function getCharacterSet() {
		if (!($vs_charset = $this->_CONFIG->get("character_set"))) {
			$vs_charset = "UTF-8";
		}
		return $vs_charset;
	}
	# --------------------------------------------------------------------------------
	# --- Transactions
	# --------------------------------------------------------------------------------
	/**
	 * Sets up local transaction property and refreshes local db property to
	 * reflect the db connection of that transaction. After setting it, you
	 * can perform common actions.
	 *
	 * @see BaseModel::getTransaction()
	 * @see BaseModel::inTransaction()
	 * @see BaseModel::removeTransaction()
	 *
	 * @param Transaction $transaction
	 * @return bool success state
	 */
	public function setTransaction(&$transaction) {
		if (is_object($transaction)) {
			$this->_TRANSACTION = $transaction;
			$this->getDb(); // refresh $o_db property to reflect db connection of transaction
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get transaction property.
	 *
	 * @see BaseModel::setTransaction()
	 * @see BaseModel::inTransaction()
	 * @see BaseModel::removeTransaction()
	 *
	 * @return Transaction
	 */
	public function getTransaction() {
		return isset($this->_TRANSACTION) ? $this->_TRANSACTION : null;
	}

	/**
	 * Is there a pending transaction?
	 *
	 * @return bool
	 */
	public function inTransaction() {
		if (isset($this->_TRANSACTION)) { return ($this->_TRANSACTION) ? true : false; }
		return false;
	}

	/**
	 * Remove transaction property.
	 *
	 * @param bool $ps_commit If true, the transaction is committed, if false, the transaction is rollback.
	 * Defaults to true.
	 * @return bool success state
	 */
	public function removeTransaction($ps_commit=true) {
		if ($this->inTransaction()) {
			if ($ps_commit) {
				$this->_TRANSACTION->commit();
			} else {
				$this->_TRANSACTION->rollback();
			}
			unset($this->_TRANSACTION);
			return true;
		} else {
			return false;
		}
	}
	# --------------------------------------------------------------------------------
	# Set/get values
	# --------------------------------------------------------------------------------
	/**
	 * Get all field values of the current row that is represented by this BaseModel object.
	 *
	 * @see BaseModel::getChangedFieldValuesArray()
	 * @return array associative array: field name => field value
	 */
	public function getFieldValuesArray() {
		return $this->_FIELD_VALUES;
	}

	/**
	 * Set alls field values of the current row that is represented by this BaseModel object
	 * by passing an associative array as follows: field name => field value
	 *
	 * @param array $pa_values associative array: field name => field value
	 * @return void
	 */
	public function setFieldValuesArray($pa_values) {
		$this->_FIELD_VALUES = $pa_values;
	}

	/**
	 * Get an associative array of the field values that changed since instantiation of
	 * this BaseModel object.
	 *
	 * @return array associative array: field name => field value
	 */
	public function getChangedFieldValuesArray() {
		$va_fieldnames = array_keys($this->_FIELD_VALUES);

		$va_changed_field_values_array = array();
		foreach($va_fieldnames as $vs_fieldname) {
			if($this->changed($vs_fieldname)) {
				$va_changed_field_values_array[$vs_fieldname] = $this->_FIELD_VALUES[$vs_fieldname];
			}
		}
		return $va_changed_field_values_array;
	}

	/**
	 * What was the original value of a field?
	 *
	 * @param string $ps_field field name
	 * @return mixed original field value
	 */
	public function getOriginalValue($ps_field) {
		return $this->_FIELD_VALUES_OLD[$ps_field];
	}

	/**
	 * Check if the content of a field has changed.
	 *
	 * @param string $ps_field field name
	 * @return bool
	 */
	public function changed($ps_field) {
		return isset($this->_FIELD_VALUE_CHANGED[$ps_field]) ? $this->_FIELD_VALUE_CHANGED[$ps_field] : null;
	}

	/**
	 * Returns value of primary key
	 *
	 * @param bool $vb_quoted Set to true if you want the method to return the value in quotes. Default is false.
	 * @return mixed value of primary key
	 */
	public function getPrimaryKey ($vb_quoted=false) {
		if (!isset($this->_FIELD_VALUES[$this->PRIMARY_KEY])) return null;
		$vm_pk = $this->_FIELD_VALUES[$this->PRIMARY_KEY];
		if ($vb_quoted) {
			$vm_pk = $this->quote($this->PRIMARY_KEY, $vm_pk);
		}

		return $vm_pk;
	}

	/**
	 * Get a field value of the table row that is represented by this object.
	 *
	 * @param string $ps_field field name
	 * @param array $pa_options options array; can be omitted.
	 * It should be an associative array of boolean (except one of the options) flags. In case that some of the options are not set, they are treated as 'false'.
	 * Possible options (keys) are:
	 * -BINARY: return field value as is
	 * -FILTER_HTML_SPECIAL_CHARS: convert all applicable chars to their html entities
	 * -DONT_PROCESS_GLOSSARY_TAGS: ?
	 * -CONVERT_HTML_BREAKS: similar to nl2br()
	 * -convertLineBreaks: same as CONVERT_HTML_BREAKS
	 * -GET_DIRECT_DATE: return raw date value from database if $ps_field adresses a date field, otherwise the value will be parsed using the TimeExpressionParser::getText() method
	 * -GET_DIRECT_TIME: return raw time value from database if $ps_field adresses a time field, otherwise the value will be parsed using the TimeExpressionParser::getText() method
	 * -TIMECODE_FORMAT: set return format for fields representing time ranges possible (string) values: COLON_DELIMITED, HOURS_MINUTES_SECONDS, RAW; data will be passed through floatval() by default
	 * -QUOTE: set return value into quotes
	 * -URL_ENCODE: value will be passed through urlencode()
	 * -ESCAPE_FOR_XML: convert <, >, &, ' and " characters for XML use
	 * -DONT_STRIP_SLASHES: if set to true, return value will not be passed through stripslashes()
	 * -template: formatting string to use for returned value; ^<fieldname> placeholder is used to represent field value in template
	 * -returnAsArray: if true, fields that can return multiple values [currently only <table_name>.children.<field>] will return values in an indexed array; default is false
	 * -returnAllLocales:
	 * -delimiter: if set, value is used as delimiter when fields that can return multiple fields are returned as strings; default is a single space
	 * -convertCodesToDisplayText: if set, id values refering to foreign keys are returned as preferred label text in the current locale
	 * -returnURL: if set then url is returned for media, otherwise an HTML tag for display is returned
	 */
	public function get($ps_field, $pa_options=null) {
		if (!$ps_field) return null;
		if (!is_array($pa_options)) { $pa_options = array();}
		
		$vb_return_as_array = 		(isset($pa_options['returnAsArray'])) ? (bool)$pa_options['returnAsArray'] : false;
		$vb_return_all_locales = 	(isset($pa_options['returnAllLocales'])) ? (bool)$pa_options['returnAllLocales'] : false;
		$vs_delimiter =				(isset($pa_options['delimiter'])) ? $pa_options['delimiter'] : ' ';
		if ($vb_return_all_locales && !$vb_return_as_array) { $vb_return_as_array = true; }
		
		$vn_row_id = $this->getPrimaryKey();
		
		if ($vb_return_as_array && $vb_return_as_array) {
			$vn_locale_id = ($this->hasField('locale_id')) ? $this->get('locale_id') : null;
		}
		
		$va_tmp = explode('.', $ps_field);
		if (sizeof($va_tmp) > 1) {
			if ($va_tmp[0] === $this->tableName()) {
				switch(sizeof($va_tmp)) {
					case 2:
						// support <table_name>.<field_name> syntax
						$ps_field = $va_tmp[1];
						break;
					default: // > 2 elements
						// media field?
						$va_field_info = $this->getFieldInfo($va_tmp[1]);
						if (($va_field_info['FIELD_TYPE'] === FT_MEDIA) && (!isset($pa_options['returnAsArray'])) && !$pa_options['returnAsArray']) {
							$o_media_settings = new MediaProcessingSettings($va_tmp[0], $va_tmp[1]);
							$va_versions = $o_media_settings->getMediaTypeVersions('*');
							
							$vs_version = $va_tmp[2];
							if (!isset($va_versions[$vs_version])) {
								$vs_version = array_shift(array_keys($va_versions));
							}
							
							if (isset($pa_options['returnURL']) && $pa_options['returnURL']) {
								return $this->getMediaUrl($va_tmp[1], $vs_version);
							} else {
								return $this->getMediaTag($va_tmp[1], $vs_version);
							}
						}
						
						if (($va_tmp[1] == 'parent') && ($this->isHierarchical()) && ($vn_parent_id = $this->get($this->getProperty('HIERARCHY_PARENT_ID_FLD')))) {
							$t_instance = $this->getAppDatamodel()->getInstanceByTableNum($this->tableNum(), true);
							if (!$t_instance->load($vn_parent_id)) {
								return ($vb_return_as_array) ? array() : null;
							} else {
								unset($va_tmp[1]);
								$va_tmp = array_values($va_tmp);
								
								if ($vb_return_as_array) {
									if ($vb_return_all_locales) {
										return array(
											$vn_row_id => array(	// this row_id
												$vn_locale_id => array(				// base model fields aren't translate-able
													$t_instance->get(join('.', $va_tmp))
												)
											)
										);
									} else {
										return array(
											$vn_row_id => $t_instance->get(join('.', $va_tmp))
										);
									}
								} else {
									return $t_instance->get(join('.', $va_tmp));
								}
							}
						} else {
							if (($va_tmp[1] == 'children') && ($this->isHierarchical())) {
		
								unset($va_tmp[1]);					// remove 'children' from field path
								$va_tmp = array_values($va_tmp);
								$vs_childless_path = join('.', $va_tmp);
								
								$va_data = array();
								$va_children_ids = $this->getHierarchyChildren(null, array('idsOnly' => true));
								
								$t_instance = $this->getAppDatamodel()->getInstanceByTableNum($this->tableNum());
								
								foreach($va_children_ids as $vn_child_id) {
									if ($t_instance->load($vn_child_id)) {
										if ($vb_return_as_array) {
											$va_data[$vn_child_id]  = array_shift($t_instance->get($vs_childless_path, array_merge($pa_options, array('returnAsArray' => $vb_return_as_array, 'returnAllLocales' => $vb_return_all_locales))));
										} else {
											$va_data[$vn_child_id]  = $t_instance->get($vs_childless_path, array_merge($pa_options, array('returnAsArray' => false, 'returnAllLocales' => false)));
										}
									}
								}
								
								if ($vb_return_as_array) {
									if ($vb_return_all_locales) {
										return array(
											$vn_row_id => array(	// this row_id
												$vn_locale_id => $va_data
											)
										);
									} else {
										return $va_data;
									}
								} else {
									return join($vs_delimiter, $va_data);
								}
							} 
						}
						break;
				}	
			} else {
				// can't pull fields from other tables!
				return $vb_return_as_array ? array() : null;
			}
		}

		if (isset($pa_options["BINARY"]) && $pa_options["BINARY"]) {
			return $this->_FIELD_VALUES[$ps_field];
		}

		if (array_key_exists($ps_field,$this->FIELDS)) {
			$ps_field_type = $this->getFieldInfo($ps_field,"FIELD_TYPE");

			if ($this->getFieldInfo($ps_field, 'IS_LIFESPAN')) {
				$pa_options['isLifespan'] = true;
			}

		switch ($ps_field_type) {
			case (FT_BIT):
				$vs_prop = isset($this->_FIELD_VALUES[$ps_field]) ? $this->_FIELD_VALUES[$ps_field] : "";
				if (isset($pa_options['convertCodesToDisplayText'])) {
					$vs_prop = (bool)$vs_prop ? _t('yes') : _t('no');
				}
				
				return $vs_prop;
				break;
			case (FT_TEXT):
			case (FT_NUMBER):
			case (FT_PASSWORD):
				$vs_prop = isset($this->_FIELD_VALUES[$ps_field]) ? $this->_FIELD_VALUES[$ps_field] : "";
				if (isset($pa_options["FILTER_HTML_SPECIAL_CHARS"]) && ($pa_options["FILTER_HTML_SPECIAL_CHARS"])) {
					$vs_prop = htmlentities(html_entity_decode($vs_prop));
				}
				//
				// Convert foreign keys and choice list values to display text is needed
				//
				if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($vs_list_code = $this->getFieldInfo($ps_field,"LIST_CODE"))) {
					$t_list = new ca_lists();
					$vs_prop = $t_list->getItemFromListForDisplayByItemID($vs_list_code, $vs_prop);
				} else {
					if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($vs_list_code = $this->getFieldInfo($ps_field,"LIST"))) {
						$t_list = new ca_lists();
						if (!($vs_tmp = $t_list->getItemFromListForDisplayByItemValue($vs_list_code, $vs_prop))) {
							if ($vs_tmp = $this->getChoiceListValue($ps_field, $vs_prop)) {
								$vs_prop = $vs_tmp;
							}
						} else {
							$vs_prop = $vs_tmp;
						}
					} else {
						if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && ($ps_field === 'locale_id') && ((int)$vs_prop > 0)) {
							$t_locale = new ca_locales($vs_prop);
							$vs_prop = $t_locale->getName();
						} else {
							if (isset($pa_options['convertCodesToDisplayText']) && $pa_options['convertCodesToDisplayText'] && (is_array($va_list = $this->getFieldInfo($ps_field,"BOUNDS_CHOICE_LIST")))) {
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
				if (
					(isset($pa_options["CONVERT_HTML_BREAKS"]) && ($pa_options["CONVERT_HTML_BREAKS"]))
					||
					(isset($pa_options["convertLineBreaks"]) && ($pa_options["convertLineBreaks"]))
				) {
					$vs_prop = caConvertLineBreaks($vs_prop);
				}
				break;
			case (FT_DATETIME):
			case (FT_TIMESTAMP):
			case (FT_HISTORIC_DATETIME):
			case (FT_HISTORIC_DATE):
			case (FT_DATE):

				if (isset($pa_options["GET_DIRECT_DATE"]) && $pa_options["GET_DIRECT_DATE"]) {
					$vs_prop = $this->_FIELD_VALUES[$ps_field];
				} else {
					$o_tep = new TimeExpressionParser();
					$vn_timestamp = isset($this->_FIELD_VALUES[$ps_field]) ? $this->_FIELD_VALUES[$ps_field] : 0;

					if (($ps_field_type == FT_HISTORIC_DATETIME) || ($ps_field_type == FT_HISTORIC_DATE)) {
						$o_tep->setHistoricTimestamps($vn_timestamp, $vn_timestamp);
					} else {
						$o_tep->setUnixTimestamps($vn_timestamp, $vn_timestamp);
					}
					if (($ps_field_type == FT_DATE) || ($ps_field_type == FT_HISTORIC_DATE)) {
						$vs_prop = $o_tep->getText(array_merge(array('timeOmit' => true), $pa_options));
					} else {
						$vs_prop =  $o_tep->getText($pa_options);
					}
				}
				break;
			case (FT_TIME):
				if (isset($pa_options["GET_DIRECT_TIME"]) && $pa_options["GET_DIRECT_TIME"]) {
					$vs_prop = $this->_FIELD_VALUES[$ps_field];
				} else {
					$o_tep = new TimeExpressionParser();
					$vn_timestamp = isset($this->_FIELD_VALUES[$ps_field]) ? $this->_FIELD_VALUES[$ps_field] : 0;

					$o_tep->setTimes($vn_timestamp, $vn_timestamp);
					$vs_prop = $o_tep->getText($pa_options);
				}
				break;
			case (FT_DATERANGE):
			case (FT_HISTORIC_DATERANGE):
				$vs_start_field_name = $this->getFieldInfo($ps_field,"START");
				$vs_end_field_name = $this->getFieldInfo($ps_field,"END");
				
				$vn_start_date = isset($this->_FIELD_VALUES[$vs_start_field_name]) ? $this->_FIELD_VALUES[$vs_start_field_name] : null;
				$vn_end_date = isset($this->_FIELD_VALUES[$vs_end_field_name]) ? $this->_FIELD_VALUES[$vs_end_field_name] : null;
				if (!isset($pa_options["GET_DIRECT_DATE"]) || !$pa_options["GET_DIRECT_DATE"]) {
					$o_tep = new TimeExpressionParser();
					if ($ps_field_type == FT_HISTORIC_DATERANGE) {
						$o_tep->setHistoricTimestamps($vn_start_date, $vn_end_date);
					} else {
						$o_tep->setUnixTimestamps($vn_start_date, $vn_end_date);
					}
					$vs_prop = $o_tep->getText($pa_options);
				} else {
					$vs_prop = array($vn_start_date, $vn_end_date);
				}
				break;
			case (FT_TIMERANGE):
				$vs_start_field_name = $this->getFieldInfo($ps_field,"START");
				$vs_end_field_name = $this->getFieldInfo($ps_field,"END");
				
				
				$vn_start_date = isset($this->_FIELD_VALUES[$vs_start_field_name]) ? $this->_FIELD_VALUES[$vs_start_field_name] : null;
				$vn_end_date = isset($this->_FIELD_VALUES[$vs_end_field_name]) ? $this->_FIELD_VALUES[$vs_end_field_name] : null;
				if (!isset($pa_options["GET_DIRECT_TIME"]) || !$pa_options["GET_DIRECT_TIME"]) {
					$o_tep = new TimeExpressionParser();
					$o_tep->setTimes($vn_start_date, $vn_end_date);
					$vs_prop = $o_tep->getText($pa_options);
				} else {
					$vs_prop = array($vn_start_date, $vn_end_date);
				}
				break;
			case (FT_TIMECODE):
				$o_tp = new TimecodeParser();
				$o_tp->setParsedValueInSeconds($this->_FIELD_VALUES[$ps_field]);
				$vs_prop = $o_tp->getText(isset($pa_options["TIMECODE_FORMAT"]) ? $pa_options["TIMECODE_FORMAT"] : null);
				break;
			case (FT_MEDIA):
			case (FT_FILE):
				if (isset($pa_options["USE_MEDIA_FIELD_VALUES"]) && $pa_options["USE_MEDIA_FIELD_VALUES"]) {
					$vs_prop = $this->_FIELD_VALUES[$ps_field];
				} else {
					$vs_prop = (isset($this->_SET_FILES[$ps_field]['tmp_name']) && $this->_SET_FILES[$ps_field]['tmp_name']) ? $this->_SET_FILES[$ps_field]['tmp_name'] : $this->_FIELD_VALUES[$ps_field];
				}
				break;
			case (FT_VARS):
				$vs_prop = (isset($this->_FIELD_VALUES[$ps_field]) && $this->_FIELD_VALUES[$ps_field]) ? $this->_FIELD_VALUES[$ps_field] : null;
				break;
		}

		if (isset($pa_options["QUOTE"]) && $pa_options["QUOTE"])
			$vs_prop = $this->quote($ps_field, $vs_prop);
		} else {
			$this->postError(710,_t("'%1' does not exist in this object", $ps_field),"BaseModel->get()");
			return $vb_return_as_array ? array() : null;
		}

		if (isset($pa_options["URL_ENCODE"]) && $pa_options["URL_ENCODE"]) {
			$vs_prop = urlEncode($vs_prop);
		}

		if (isset($pa_options["ESCAPE_FOR_XML"]) && $pa_options["ESCAPE_FOR_XML"]) {
			$vs_prop = escapeForXML($vs_prop);
		}

		if (!(isset($pa_options["DONT_STRIP_SLASHES"]) && $pa_options["DONT_STRIP_SLASHES"])) {
			if (is_string($vs_prop)) { $vs_prop = stripSlashes($vs_prop); }
		}
		
		if ((isset($pa_options["template"]) && $pa_options["template"])) {
			$vs_template_with_substitution = str_replace("^".$ps_field, $vs_prop, $pa_options["template"]);
			$vs_prop = str_replace("^".$this->tableName().".".$ps_field, $vs_prop, $vs_template_with_substitution);
		}
		
		if ($vb_return_as_array) {
			if ($vb_return_all_locales) {
				return array(
					$vn_row_id => array(	// this row_id
						$vn_locale_id => array($vs_prop)
					)
				);
			} else {
				return array(
					$vn_row_id => $vs_prop
				);
			}
		} else {
			return $vs_prop;
		}
	}

	/**
	 * Set field value(s) for the table row represented by this object
	 *
	 * @param string|array string $pa_fields representation of a field name
	 * or array of string representations of field names
	 * @param mixed $pm_value value to set the given field(s) to
	 * @param array $pa_options associative array of options
	 * possible options (keys):
	 * when dealing with date/time fields:
	 * - SET_DIRECT_DATE
	 * - SET_DIRECT_TIME
	 * - SET_DIRECT_TIMES
	 *
	 * for media/files fields:
	 * - original_filename : (note that it is lower case) optional parameter which enables you to pass the original filename of a file, in addition to the representation in the temporary, global _FILES array;
	 */
	public function set($pa_fields, $pm_value="", $pa_options=null) {
		$this->errors = array();
		if (!is_array($pa_fields)) {
			$pa_fields = array($pa_fields => $pm_value);
		}

		foreach($pa_fields as $vs_field => $vm_value) {
			if (array_key_exists($vs_field, $this->FIELDS)) {
				$pa_fields_type = $this->getFieldInfo($vs_field,"FIELD_TYPE");
				if (!$this->verifyFieldValue($vs_field, $vm_value)) {
					return false;
				}

				if ($vs_field == $this->primaryKey()) {
					$vm_value = preg_replace("/[\"']/", "", $vm_value);
				}


				// what markup is supported for text fields?
				$vs_markup_type = $this->getFieldInfo($vs_field, "MARKUP_TYPE");

				// if markup is non-HTML then strip out HTML special chars for safety
				if (!($vs_markup_type == __CA_MT_HTML__)) {
					$vm_value = htmlspecialchars($vm_value, ENT_QUOTES, 'UTF-8');
				}

				$vs_cur_value = isset($this->_FIELD_VALUES[$vs_field]) ? $this->_FIELD_VALUES[$vs_field] : null;
				switch ($pa_fields_type) {
					case (FT_NUMBER):
						if ($vs_cur_value != $vm_value) {
							$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
						}

						if (($vm_value !== "") || ($this->getFieldInfo($vs_field, "IS_NULL") && ($vm_value == ""))) {
							if ($vm_value) {
								$vm_orig_value = $vm_value;
								$vm_value = preg_replace("/[^\d-.]+/", "", $vm_value); # strip non-numeric characters
								if (!preg_match("/^[\-]{0,1}[\d.]+$/", $vm_value)) {
									$this->postError(1100,_t("'%1' for %2 is not numeric", $vm_orig_value, $vs_field),"BaseModel->set()");
									return "";
								}
							}
							$this->_FIELD_VALUES[$vs_field] = $vm_value;
						}
						break;
					case (FT_BIT):
						if ($vs_cur_value != $vm_value) {
							$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
						}
						$this->_FIELD_VALUES[$vs_field] = ($vm_value ? 1 : 0);
						break;
					case (FT_DATETIME):
					case (FT_HISTORIC_DATETIME):
					case (FT_DATE):
					case (FT_HISTORIC_DATE):
						if (($this->DIRECT_DATETIMES) || ($pa_options["SET_DIRECT_DATE"])) {
							$this->_FIELD_VALUES[$vs_field] = $vm_value;
						} else {
							if (!$vm_value && $this->FIELDS[$vs_field]["IS_NULL"]) {
								if ($vs_cur_value) {
									$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
								}
								$this->_FIELD_VALUES[$vs_field] = null;
							} else {
								$o_tep= new TimeExpressionParser();

								if (($pa_fields_type == FT_DATE) || ($pa_fields_type == FT_HISTORIC_DATE)) {
									$va_timestamps = $o_tep->parseDate($vm_value);
								} else {
									$va_timestamps = $o_tep->parseDatetime($vm_value);
								}
								if (!$va_timestamps) {
									$this->postError(1805, $o_tep->getParseErrorMessage(), 'BaseModel->set()');
									return false;
								}

								if (($pa_fields_type == FT_HISTORIC_DATETIME) || ($pa_fields_type == FT_HISTORIC_DATE)) {
									if($vs_cur_value != $va_timestamps["start"]) {
										$this->_FIELD_VALUES[$vs_field] = $va_timestamps["start"];
										$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
									}
								} else {
									$va_timestamps = $o_tep->getUnixTimestamps();
									if ($va_timestamps[0] == -1) {
										$this->postError(1830, $o_tep->getParseErrorMessage(), 'BaseModel->set()');
										return false;
									}
									if ($vs_cur_value != $va_timestamps["start"]) {
										$this->_FIELD_VALUES[$vs_field] = $va_timestamps["start"];
										$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
									}
								}
							}

						}

						break;
					case (FT_TIME):
						if (($this->DIRECT_TIMES) || ($pa_options["SET_DIRECT_TIME"])) {
							$this->_FIELD_VALUES[$vs_field] = $vm_value;
						} else {
							if (!$vm_value && $this->FIELDS[$vs_field]["IS_NULL"]) {
								if ($vs_cur_value) {
									$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
								}
								$this->_FIELD_VALUES[$vs_field] = null;
							} else {
								$o_tep= new TimeExpressionParser();
								if (!$o_tep->parseTime($vm_value)) {
									$this->postError(1805, $o_tep->getParseErrorMessage(), 'BaseModel->set()');
									return false;
								}

								$va_times = $o_tep->getTimes();
								if ($vs_cur_value != $va_times['start']) {
									$this->_FIELD_VALUES[$vs_field] = $va_times['start'];
									$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
								}
							}

						}

						break;
					case (FT_TIMESTAMP):
						# can't set timestamp
						break;
					case (FT_DATERANGE):
					case (FT_HISTORIC_DATERANGE):
						$vs_start_field_name = $this->getFieldInfo($vs_field,"START");
						$vs_end_field_name = $this->getFieldInfo($vs_field,"END");
												
						$vn_start_date = isset($this->_FIELD_VALUES[$vs_start_field_name]) ? $this->_FIELD_VALUES[$vs_start_field_name] : null;
						$vn_end_date = isset($this->_FIELD_VALUES[$vs_end_field_name]) ? $this->_FIELD_VALUES[$vs_end_field_name] : null;
						if (($this->DIRECT_DATETIMES) || ($pa_options["SET_DIRECT_DATE"])) {
							if (is_array($vm_value) && (sizeof($vm_value) == 2) && ($vm_value[0] <= $vm_value[1])) {
								if ($vn_start_date != $vm_value[0]) {
									$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
									$this->_FIELD_VALUES[$vs_start_field_name] = $vm_value[0];
								}
								if ($vn_end_date != $vm_value[1]) {
									$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
									$this->_FIELD_VALUES[$vs_end_field_name] = $vm_value[1];
								}
							} else {
								$this->postError(1100,_t("Invalid direct date values"),"BaseModel->set()");
							}
						} else {
							if (!$vm_value && $this->FIELDS[$vs_field]["IS_NULL"]) {
								if ($vn_start_date || $vn_end_date) {
									$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
								}
								$this->_FIELD_VALUES[$vs_start_field_name] = null;
								$this->_FIELD_VALUES[$vs_end_field_name] = null;
							} else {
								$o_tep = new TimeExpressionParser();
								if (!$o_tep->parseDatetime($vm_value)) {
									$this->postError(1805, $o_tep->getParseErrorMessage(), 'BaseModel->set()');
									return false;
								}

								if ($pa_fields_type == FT_HISTORIC_DATERANGE) {
									$va_timestamps = $o_tep->getHistoricTimestamps();
								} else {
									$va_timestamps = $o_tep->getUnixTimestamps();
									if ($va_timestamps[0] == -1) {
										$this->postError(1830, $o_tep->getParseErrorMessage(), 'BaseModel->set()');
										return false;
									}
								}

								if ($vn_start_date != $va_timestamps["start"]) {
									$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
									$this->_FIELD_VALUES[$vs_start_field_name] = $va_timestamps["start"];
								}
								if ($vn_end_date != $va_timestamps["end"]) {
									$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
									$this->_FIELD_VALUES[$vs_end_field_name] = $va_timestamps["end"];
								}
							}
						}
						break;
					case (FT_TIMERANGE):
						$vs_start_field_name = $this->getFieldInfo($vs_field,"START");
						$vs_end_field_name = $this->getFieldInfo($vs_field,"END");

						if (($this->DIRECT_TIMES) || ($pa_options["SET_DIRECT_TIMES"])) {
							if (is_array($vm_value) && (sizeof($vm_value) == 2) && ($vm_value[0] <= $vm_value[1])) {
								if ($this->_FIELD_VALUES[$vs_start_field_name] != $vm_value[0]) {
									$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
									$this->_FIELD_VALUES[$vs_start_field_name] = $vm_value[0];
								}
								if ($this->_FIELD_VALUES[$vs_end_field_name] != $vm_value[1]) {
									$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
									$this->_FIELD_VALUES[$vs_end_field_name] = $vm_value[1];
								}
							} else {
								$this->postError(1100,_t("Invalid direct time values"),"BaseModel->set()");
							}
						} else {
							if (!$vm_value && $this->FIELDS[$vs_field]["IS_NULL"]) {
								if ($this->_FIELD_VALUES[$vs_start_field_name] || $this->_FIELD_VALUES[$vs_end_field_name]) {
									$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
								}
								$this->_FIELD_VALUES[$vs_start_field_name] = null;
								$this->_FIELD_VALUES[$vs_end_field_name] = null;
							} else {
								$o_tep = new TimeExpressionParser();
								if (!$o_tep->parseTime($vm_value)) {
									$this->postError(1805, $o_tep->getParseErrorMessage(), 'BaseModel->set()');
									return false;
								}

								$va_timestamps = $o_tep->getTimes();

								if ($this->_FIELD_VALUES[$vs_start_field_name] != $va_timestamps["start"]) {
									$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
									$this->_FIELD_VALUES[$vs_start_field_name] = $va_timestamps["start"];
								}
								if ($this->_FIELD_VALUES[$vs_end_field_name] != $va_timestamps["end"]) {
									$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
									$this->_FIELD_VALUES[$vs_end_field_name] = $va_timestamps["end"];
								}
							}

						}
						break;
					case (FT_TIMECODE):
						$o_tp = new TimecodeParser();
						if ($o_tp->parse($vm_value)) {
							if ($o_tp->getParsedValueInSeconds() != $vs_cur_value) {
								$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
								$this->_FIELD_VALUES[$vs_field] = $o_tp->getParsedValueInSeconds();
							}
						}
						break;
					case (FT_TEXT):
						if (is_string($vm_value)) {
							$vm_value = stripSlashes($vm_value);
						}
						if ($this->getFieldInfo($vs_field, "DISPLAY_TYPE") == DT_LIST_MULTIPLE) {
							if (is_array($vm_value)) {
								if (!($vs_list_multiple_delimiter = $this->getFieldInfo($vs_field, 'LIST_MULTIPLE_DELIMITER'))) { $vs_list_multiple_delimiter = ';'; }
								$vs_string_value = join($vs_list_multiple_delimiter, $vm_value);
								$vs_string_value = str_replace("\0", '', $vs_string_value);
								if ($vs_cur_value != $vs_string_value) {
									$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
								}
								$this->_FIELD_VALUES[$vs_field] = $vs_string_value;
							}
						} else {
							$vm_value = str_replace("\0", '', $vm_value);
							if ($this->getFieldInfo($vs_field, "ENTITY_ENCODE_INPUT")) {
								$vs_value_entity_encoded = htmlentities(html_entity_decode($vm_value));
								if ($vs_cur_value != $vs_value_entity_encoded) {
									$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
								}
								$this->_FIELD_VALUES[$vs_field] = $vs_value_entity_encoded;
							} else {
								if ($this->getFieldInfo($vs_field, "URL_ENCODE_INPUT")) {
									$vs_value_url_encoded = urlencode($vm_value);
									if ($vs_cur_value != $vs_value_url_encoded) {
										$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
									}
									$this->_FIELD_VALUES[$vs_field] = $vs_value_url_encoded;
								} else {
									if ($vs_cur_value != $vm_value) {
										$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
									}
									$this->_FIELD_VALUES[$vs_field] = $vm_value;
								}
							}
						}
						break;
					case (FT_PASSWORD):
						if (!$vm_value) {		// store blank passwords as blank, not MD5 of blank
							$this->_FIELD_VALUES[$vs_field] = $vs_crypt_pw = "";
						} else {
							if ($this->_CONFIG->get("use_old_style_passwords")) {
								$vs_crypt_pw = crypt($vm_value,substr($vm_value,0,2));
							} else {
								$vs_crypt_pw = md5($vm_value);
							}
							if (($vs_cur_value != $vm_value) && ($vs_cur_value != $vs_crypt_pw)) {
								$this->_FIELD_VALUES[$vs_field] = $vs_crypt_pw;
							}
							if ($vs_cur_value != $vs_crypt_pw) {
								$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
							}
						}
						break;
					case (FT_VARS):
						if (md5(print_r($vs_cur_value, true)) != md5(print_r($vm_value, true))) {
							$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
						}
						$this->_FIELD_VALUES[$vs_field] = $vm_value;
						break;
					case (FT_MEDIA):
					case (FT_FILE):
						$vb_allow_fetching_of_urls = (bool)$this->_CONFIG->get('allow_fetching_of_media_from_remote_urls');
						
						# if there's a tmp_name is the global _FILES array
						# then we'll process it in insert()/update()...
						$this->_SET_FILES[$vs_field]['options'] = $pa_options;
						
						if (caGetOSFamily() == OS_WIN32) {	// fix for paths using backslashes on Windows failing in processing
							$vm_value = str_replace('\\', '/', $vm_value);
						}
						
						$va_matches = null;
						
						if (file_exists($vm_value) || ($vb_allow_fetching_of_urls && isURL($vm_value))) {
							$this->_SET_FILES[$vs_field]['original_filename'] = $pa_options["original_filename"];
							$this->_SET_FILES[$vs_field]['tmp_name'] = $vm_value;
							$this->_FIELD_VALUE_CHANGED[$vs_field] = true;
						} else {
							# only return error when file name is not 'none'
							# 'none' is PHP's stupid way of telling you there
							# isn't a file...
							if (($vm_value != "none") && ($vm_value)) {
								//$this->postError(1500,_t("%1 does not exist", $vm_value),"BaseModel->set()");
							}
							return false;
						}
						break;
					default:
						die("Invalid field type in BaseModel->set()");
						break;
				}
			} else {
				$this->postError(710,_t("%1' does not exist in this object", $vs_field),"BaseModel->set()");
				return false;
			}
		}
		return true;
	}
	# --------------------------------------------------------------------------------
	# BaseModel info
	# --------------------------------------------------------------------------------
	/**
	 * Returns an array containing the field names of the table.
	 *
	 * @return array field names
	 */
	public function getFields() {
		return array_keys($this->FIELDS);
	}
	/**
	 * Returns an array containing the field names of the table as keys and their info as values.
	 *
	 * @return array field names
	 */
	public function getFieldsArray() {
		return $this->FIELDS;
	}
	/**
	 * Returns name of primary key
	 *
	 * @return string the primary key of the table
	 */
	public function primaryKey() {
		return $this->PRIMARY_KEY;
	}

	/**
	 * Returns name of the table
	 *
	 * @return string the name of the table
	 */
	public function tableName() {
		return $this->TABLE;
	}

	/**
	 * Returns number of the table as defined in datamodel.conf configuration file
	 *
	 * @return int table number
	 */
	public function tableNum() {
		return $this->_DATAMODEL->getTableNum($this->TABLE);
	}

	/**
	 * Returns number of the given field. Field numbering is defined implicitly by the order.
	 *
	 * @param string $ps_field field name
	 * @return int field number
	 */
	public function fieldNum($ps_field) {
		return $this->_DATAMODEL->getFieldNum($this->TABLE, $ps_field);
	}
	
	/**
	 * Returns name of the given field number. 
	 *
	 * @param number $pn_num field number
	 * @return string field name
	 */
	public function fieldName($pn_num) {
		$va_fields = $this->getFields();
		return isset($va_fields[$pn_num]) ? $va_fields[$pn_num] : null;
	}

	/**
	 * Returns name field used for arbitrary ordering of records (returns "" if none defined)
	 *
	 * @return string field name, "" if none defined
	 */
	public function rankField() {
		return $this->RANK;
	}

	/**
	 * Returns table property
	 *
	 * @return mixed the property
	 */
	public function getProperty($property) {
		if (isset($this->{$property})) { return $this->{$property}; }
		return null;
	}

	/**
	 * Returns a string with the values taken from fields which are defined in global property LIST_FIELDS
	 * taking into account the global property LIST_DELIMITER. Each extension of BaseModel can define those properties.
	 *
	 * @return string
	 */
	public function getListFieldText() {
		if (is_array($va_list_fields = $this->getProperty('LIST_FIELDS'))) {
			$vs_delimiter = $this->getProperty('LIST_DELIMITER');
			if ($vs_delimiter == null) { $vs_delimiter = ' '; }

			$va_tmp = array();
			foreach($va_list_fields as $vs_field) {
				$va_tmp[] = $this->get($vs_field);
			}

			return join($vs_delimiter, $va_tmp);
		}
		return '';
	}
	# --------------------------------------------------------------------------------
	# Access mode
	# --------------------------------------------------------------------------------
	/**
	 * Returns the current access mode.
	 *
	 * @param bool $pb_return_name If set to true, return string representations of the modes
	 * (i.e. 'read' or 'write'). If set to false (it is by default), returns ACCESS_READ or ACCESS_WRITE.
	 *
	 * @return int|string access mode representation
	 */
	public function getMode($pb_return_name=false) {
		if ($pb_return_name) {
			switch($this->ACCESS_MODE) {
				case ACCESS_READ:
					return 'read';
					break;
				case ACCESS_WRITE:
					return 'write';
					break;
				default:
					return '???';
					break;
			}
		}
		return $this->ACCESS_MODE;
	}

	/**
	 * Set current access mode.
	 *
	 * @param int $pn_mode access mode representation, either ACCESS_READ or ACCESS_WRITE
	 * @return bool either returns the access mode or false on error
	 */
	public function setMode($pn_mode) {
		if (in_array($pn_mode, array(ACCESS_READ, ACCESS_WRITE))) {
			return $this->ACCESS_MODE = $pn_mode;
		}
		$this->postError(700,_t("Mode:%1 is not supported by this object", $pn_mode),"BaseModel->setMode()");
		return false;
	}
	# --------------------------------------------------------------------------------
	# --- Content methods (just your standard Create, Return, Update, Delete methods)
	# --------------------------------------------------------------------------------
	/**
	 * Generic method to load content properties of object with database fields.
	 * After dealing with one row in the database using an extension of BaseModel
	 * you can use this method to make the same object represent another row, instead
	 * of destructing the old and constructing a new one.
	 *
	 * @param mixed $pm_id primary key value of the record to load (assuming we have no composed primary key)
	 * @return bool success state
	 */
	public function load ($pm_id=null) {
		$this->clear();
		
		if ($pm_id == null) {
			//$this->postError(750,_t("Can't load record; key is blank"), "BaseModel->load()");
			return false;
		}

		$o_db = $this->getDb();

		if (!is_array($pm_id)) {
			if ($this->_getFieldTypeType($this->primaryKey()) == 1) {
				$pm_id = $this->quote($pm_id);
			} else {
				$pm_id = intval($pm_id);
			}

			$vs_sql = "SELECT * FROM ".$this->tableName()." WHERE ".$this->primaryKey()." = ".$pm_id;
		} else {
			$va_sql_wheres = array();
			foreach ($pm_id as $vs_field => $vm_value) {
				# support case where fieldname is in format table.fieldname
				if (preg_match("/([\w_]+)\.([\w_]+)/", $vs_field, $va_matches)) {
					if ($va_matches[1] != $this->tableName()) {
						if ($this->_DATAMODEL->tableExists($va_matches[1])) {
							$this->postError(715,_t("BaseModel '%1' cannot be accessed with this class", $matches[1]), "BaseModel->load()");
							return false;
						} else {
							$this->postError(715, _t("BaseModel '%1' does not exist", $matches[1]), "BaseModel->load()");
							return false;
						}
					}
					$vs_field = $matches[2]; # get field name alone
				}

				if (!$this->hasField($vs_field)) {
					$this->postError(716,_t("Field '%1' does not exist", $vs_field), "BaseModel->load()");
					return false;
				}

				if ($this->_getFieldTypeType($vs_field) == 0) {
					if (!is_numeric($vm_value) && !is_null($vm_value)) {
						$vm_value = intval($vm_value);
					}
				} else {
					$vm_value = $this->quote($vs_field, is_null($vm_value) ? '' : $vm_value);
				}

				if (is_null($vm_value)) {
					$va_sql_wheres[] = "($vs_field IS NULL)";
				} else {
					if ($vm_value == '') { continue; }
					$va_sql_wheres[] = "($vs_field = $vm_value)";
				}
			}
			$vs_sql = "SELECT * FROM ".$this->tableName()." WHERE ".join(" AND ", $va_sql_wheres);
		}

		$qr_res = $o_db->query($vs_sql);

		if ($qr_res->nextRow()) {
			foreach($this->FIELDS as $vs_field => $va_attr) {
				$vs_cur_value = isset($this->_FIELD_VALUES[$vs_field]) ? $this->_FIELD_VALUES[$vs_field] : null;
				$va_row =& $qr_res->getRow();
				switch($va_attr["FIELD_TYPE"]) {
					case FT_DATERANGE:
					case FT_HISTORIC_DATERANGE:
					case FT_TIMERANGE:
						$vs_start_field_name = $va_attr["START"];
						$vs_end_field_name = $va_attr["END"];
						$this->_FIELD_VALUES[$vs_start_field_name] 	= 	$va_row[$vs_start_field_name];
						$this->_FIELD_VALUES[$vs_end_field_name] 	= 	$va_row[$vs_end_field_name];
						break;
					case FT_TIMESTAMP:
						$this->_FIELD_VALUES[$vs_field] = $va_row[$vs_field];
						break;
					case FT_VARS:
					case FT_FILE:
					case FT_MEDIA:
						if (!$vs_cur_value) {
							$this->_FIELD_VALUES[$vs_field] = caUnserializeForDatabase($va_row[$vs_field]);
						}
						break;
					default:
						$this->_FIELD_VALUES[$vs_field] = $va_row[$vs_field];
						break;
				}
			}
			
			$this->_FIELD_VALUES_OLD = $this->_FIELD_VALUES;
			$this->_FILES_CLEAR = array();
			return true;
		} else {
			if (!is_array($pm_id)) {
				$this->postError(750,_t("Invalid %1 '%2'", $this->primaryKey(), $pm_id), "BaseModel->load()");
			} else {
				$va_field_list = array();
				foreach ($pm_id as $vs_field => $vm_value) {
					$va_field_list[] = "$vs_field => $vm_value";
				}
				$this->postError(750,_t("No record with %1", join(", ", $va_field_list)), "BaseModel->load()");
			}
			return false;
		}
	}

	/**
	 *
	 */
	 private function _calcHierarchicalIndexing($pa_parent_info) {
	 	$vs_hier_left_fld = $this->getProperty('HIERARCHY_LEFT_INDEX_FLD');
	 	$vs_hier_right_fld = $this->getProperty('HIERARCHY_RIGHT_INDEX_FLD');
	 	$vs_hier_id_fld = $this->getProperty('HIERARCHY_ID_FLD');
	 	$vs_parent_fld = $this->getProperty('HIERARCHY_PARENT_ID_FLD');
	 	
	 	$o_db = $this->getDb();
	 	
	 	$qr_up = $o_db->query("
			SELECT MAX(".$vs_hier_right_fld.") maxChildRight
			FROM ".$this->tableName()."
			WHERE
				(".$vs_hier_left_fld." > ?) AND
				(".$vs_hier_right_fld." < ?) AND (".$this->primaryKey()." <> ?)".
				($vs_hier_id_fld ? " AND ($vs_hier_id_fld = ".intval($pa_parent_info[$vs_hier_id_fld]).")" : '')."
		", $pa_parent_info[$vs_hier_left_fld], $pa_parent_info[$vs_hier_right_fld], $pa_parent_info[$this->primaryKey()]);
	 
	 	if ($qr_up->nextRow()) {
	 		if (!($vn_gap_start = $qr_up->get('maxChildRight'))) {
	 			$vn_gap_start = $pa_parent_info[$vs_hier_left_fld];
	 		}
	 	
			$vn_gap_end = $pa_parent_info[$vs_hier_right_fld];
			$vn_gap_size = ($vn_gap_end - $vn_gap_start);
			
			if ($vn_gap_size < 0.00001) {
				// rebuild hierarchical index if the current gap is not large enough to fit current record
				$this->rebuildHierarchicalIndex($this->get($vs_hier_id_fld));
				$pa_parent_info = $this->_getHierarchyParent($pa_parent_info[$this->primaryKey()]);
				return $this->_calcHierarchicalIndexing($pa_parent_info);
			}

			if (($vn_scale = strlen(floor($vn_gap_size/10000))) < 1) { $vn_scale = 1; } 

			$vn_interval_start = $vn_gap_start + ($vn_gap_size/(pow(10, $vn_scale)));
			$vn_interval_end = $vn_interval_start + ($vn_gap_size/(pow(10, $vn_scale)));
			
			//print "--------------------------\n";
			//print "GAP START={$vn_gap_start} END={$vn_gap_end} SIZE={$vn_gap_size} SCALE={$vn_scale} INC=".($vn_gap_size/(pow(10, $vn_scale)))."\n";
			//print "INT START={$vn_interval_start} INT END={$vn_interval_end}\n";
			//print "--------------------------\n";
			return array('left' => $vn_interval_start, 'right' => $vn_interval_end);
	 	}
	 	return null;
	 }
	 
	 /**
	  *
	  */
	 private function _getHierarchyParent($pn_parent_id) {
	 	$o_db = $this->getDb();
	 	$qr_get_parent = $o_db->query("
			SELECT *
			FROM ".$this->tableName()."
			WHERE 
				".$this->primaryKey()." = ?
		", intval($pn_parent_id));
		
		if($qr_get_parent->nextRow()) {
			return $qr_get_parent->getRow();
		}
		return null;
	 }
	 
	/**
	 * Use this method to insert new record using supplied values
	 * (assuming that you've constructed your BaseModel object as empty record)
	 * @param $pa_options array optional associative array of options. Supported options include: 
	 *				dont_do_search_indexing = if set to true then no search indexing on the inserted record is performed
	 * @return int primary key of the new record, false on error
	 */
	public function insert ($pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$vb_we_set_transaction = false;
		$this->clearErrors();
		if ($this->getMode() == ACCESS_WRITE) {
			if (!$this->inTransaction()) {
				$this->setTransaction(new Transaction($this->getDb()));
				$vb_we_set_transaction = true;
			}
			$o_db = $this->getDb();

			$vs_fields = "";
			$vs_values = "";

			$va_media_fields = array();
			$va_file_fields = array();

			//
			// Set any auto-set hierarchical fields (eg. HIERARCHY_LEFT_INDEX_FLD and HIERARCHY_RIGHT_INDEX_FLD indexing for all and HIERARCHY_ID_FLD for ad-hoc hierarchies) here
			//
			$vn_hier_left_index_value = $vn_hier_right_index_value = 0;
			if ($vb_is_hierarchical = $this->isHierarchical()) {
				$vn_parent_id = $this->get($this->getProperty('HIERARCHY_PARENT_ID_FLD'));
				$va_parent_info = $this->_getHierarchyParent($vn_parent_id);
				
				switch($this->getHierarchyType()) {
					case __CA_HIER_TYPE_SIMPLE_MONO__:	// you only need to set parent_id for this type of hierarchy
						if (!$vn_parent_id) {
							if ($vn_parent_id = $this->getHierarchyRootID(null)) {
								$this->set($this->getProperty('HIERARCHY_PARENT_ID_FLD'), $vn_parent_id);
								$va_parent_info = $this->_getHierarchyParent($vn_parent_id);
							}
						}
						break;
					case __CA_HIER_TYPE_MULTI_MONO__:	// you need to set parent_id (otherwise it defaults to the hierarchy root); you only need to set hierarchy_id if you're creating a root
						if (!($vn_hierarchy_id = $this->get($this->getProperty('HIERARCHY_ID_FLD')))) {
							$this->set($this->getProperty('HIERARCHY_ID_FLD'), $va_parent_info[$this->getProperty('HIERARCHY_ID_FLD')]);
						}
						if (!$vn_parent_id) {
							if ($vn_parent_id = $this->getHierarchyRootID($vn_hierarchy_id)) {
								$this->set($this->getProperty('HIERARCHY_PARENT_ID_FLD'), $vn_parent_id);
								$va_parent_info = $this->_getHierarchyParent($vn_parent_id);
							}
						}
						break;
					case __CA_HIER_TYPE_ADHOC_MONO__:	// you need to set parent_id for this hierarchy; you never need to set hierarchy_id
						if (!($vn_hierarchy_id = $this->get($this->getProperty('HIERARCHY_ID_FLD')))) {
							if ($va_parent_info) {
								// set hierarchy to that of parent
								$this->set($this->getProperty('HIERARCHY_ID_FLD'), $va_parent_info[$this->getProperty('HIERARCHY_ID_FLD')]);
							} 
							
							// if there's no parent then this is a root in which case HIERARCHY_ID_FLD should be set to the primary
							// key of the row, which we'll know once we insert it (so we must set it after insert)
						}
						break;
					case __CA_HIER_TYPE_MULTI_POLY__:	// TODO: implement
					
						break;
					default:
						die("Invalid hierarchy type: ".$this->getHierarchyType());
						break;
				}
				
				if ($va_parent_info) {
					$va_hier_indexing = $this->_calcHierarchicalIndexing($va_parent_info);
				} else {
					$va_hier_indexing = array('left' => 1, 'right' => pow(2,32));
				}
				$this->set($this->getProperty('HIERARCHY_LEFT_INDEX_FLD'), $va_hier_indexing['left']);
				$this->set($this->getProperty('HIERARCHY_RIGHT_INDEX_FLD'), $va_hier_indexing['right']);
			}
			
			$va_many_to_one_relations = $this->_DATAMODEL->getManyToOneRelations($this->tableName());

			foreach($this->FIELDS as $vs_field => $va_attr) {
				$vs_field_type = $va_attr["FIELD_TYPE"];				# field type
				$vs_field_value = $this->get($vs_field, array("TIMECODE_FORMAT" => "RAW"));
				
				# --- check bounds (value, length and choice lists)
				if (!$this->verifyFieldValue($vs_field, $vs_field_value)) {
					# verifyFieldValue() posts errors so we don't have to do anything here
					# No query will be run if there are errors so we don't have to worry about invalid
					# values being written into the database. By not immediately bailing on an error we
					# can return a list of *all* input errors to the caller; this is perfect listing all form input errors in
					# a form-based user interface
				}

				# --- TODO: This is MySQL dependent
				# --- primary key has no place in an INSERT statement if it is identity
				if ($vs_field == $this->primaryKey()) {
					if (isset($va_attr["IDENTITY"]) && $va_attr["IDENTITY"]) {
						if (!defined('__CA_ALLOW_SETTING_OF_PRIMARY_KEYS__') || !$vs_field_value) {
							continue;
						}
					}
				}

				# --- check ->one relations
				if (isset($va_many_to_one_relations[$vs_field]) && $va_many_to_one_relations[$vs_field]) {
					# Nothing to verify if key is null
					if (!(
						(isset($va_attr["IS_NULL"]) && $va_attr["IS_NULL"]) &&
						(
							($vs_field_value == "") || ($vs_field_value === null)
						)
					)) {
						if ($t_many_table = $this->_DATAMODEL->getTableInstance($va_many_to_one_relations[$vs_field]["one_table"])) {
							if ($this->inTransaction()) {
								$t_many_table->setTransaction($this->getTransaction());
							}
							$t_many_table->load($this->get($va_many_to_one_relations[$vs_field]["many_table_field"]));
						}


						if ($t_many_table->numErrors()) {
							$this->postError(750,_t("%1 record with $vs_field = %2 does not exist", $t_many_table->tableName(), $this->get($vs_field)),"BaseModel->insert()");
						}
					}
				}

				if (isset($va_attr["IS_NULL"]) && $va_attr["IS_NULL"] && ($vs_field_value == null)) {
					$vs_field_value_is_null = true;
				} else {
					$vs_field_value_is_null = false;
				}

				if ($vs_field_value_is_null) {
					if (($vs_field_type == FT_DATERANGE) || ($vs_field_type == FT_HISTORIC_DATERANGE)  || ($vs_field_type == FT_TIMERANGE)) {
						$start_field_name = $va_attr["START"];
						$end_field_name = $va_attr["END"];
						$vs_fields .= "$start_field_name, $end_field_name,";
						$vs_values .= "NULL, NULL,";
					} else {
						$vs_fields .= "$vs_field,";
						$vs_values .= "NULL,";
					}
				} else {
					switch($vs_field_type) {
						# -----------------------------
						case (FT_DATETIME): 	# date/time
						case (FT_HISTORIC_DATETIME):
						case (FT_DATE):
						case (FT_HISTORIC_DATE):
							$vs_fields .= "$vs_field,";
							$v = isset($this->_FIELD_VALUES[$vs_field]) ? $this->_FIELD_VALUES[$vs_field] : null;
							if ($v == '') {
								$this->postError(1805, _t("Date is undefined but field %1 does not support NULL values", $vs_field),"BaseModel->insert()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							if (!is_numeric($v)) {
								$this->postError(1100, _t("Date is invalid for %1", $vs_field),"BaseModel->insert()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							$vs_values .= $v.",";		# output as is
							break;
						# -----------------------------
						case (FT_TIME):
							$vs_fields .= $vs_field.",";
							$v = isset($this->_FIELD_VALUES[$vs_field]) ? $this->_FIELD_VALUES[$vs_field] : null;
							if ($v == "") {
								$this->postError(1805, _t("Time is undefined but field %1 does not support NULL values", $vs_field),"BaseModel->insert()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							if (!is_numeric($v)) {
								$this->postError(1100, _t("Time is invalid for ", $vs_field),"BaseModel->insert()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							$vs_values .= $v.",";		# output as is
							break;
						# -----------------------------
						case (FT_TIMESTAMP):	# insert on stamp
							$vs_fields .= $vs_field.",";
							$vs_values .= time().",";
							break;
						# -----------------------------
						case (FT_DATERANGE):
						case (FT_HISTORIC_DATERANGE):
							$start_field_name = $va_attr["START"];
							$end_field_name = $va_attr["END"];

							if (($this->_FIELD_VALUES[$start_field_name] == "") || ($this->_FIELD_VALUES[$end_field_name] == "")) {
								$this->postError(1805, _t("Daterange is undefined but field does not support NULL values"),"BaseModel->insert()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							if (!is_numeric($this->_FIELD_VALUES[$start_field_name])) {
								$this->postError(1100, _t("Starting date is invalid"),"BaseModel->insert()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							if (!is_numeric($this->_FIELD_VALUES[$end_field_name])) {
								$this->postError(1100,_t("Ending date is invalid"),"BaseModel->insert()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							$vs_fields .= "$start_field_name, $end_field_name,";
							$vs_values .= $this->_FIELD_VALUES[$start_field_name].", ".$this->_FIELD_VALUES[$end_field_name].",";

							break;
						# -----------------------------
						case (FT_TIMERANGE):
							$start_field_name = $va_attr["START"];
							$end_field_name = $va_attr["END"];

							if (($this->_FIELD_VALUES[$start_field_name] == "") || ($this->_FIELD_VALUES[$end_field_name] == "")) {
								$this->postError(1805,_t("Time range is undefined but field does not support NULL values"),"BaseModel->insert()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							if (!is_numeric($this->_FIELD_VALUES[$start_field_name])) {
								$this->postError(1100,_t("Starting time is invalid"),"BaseModel->insert()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							if (!is_numeric($this->_FIELD_VALUES[$end_field_name])) {
								$this->postError(1100,_t("Ending time is invalid"),"BaseModel->insert()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							$vs_fields .= "$start_field_name, $end_field_name,";
							$vs_values .= $this->_FIELD_VALUES[$start_field_name].", ".$this->_FIELD_VALUES[$end_field_name].",";

							break;
						# -----------------------------
						case (FT_NUMBER):
						case (FT_BIT):
							if (!$vb_is_hierarchical) {
								if ((isset($this->RANK)) && ($vs_field == $this->RANK) && (!$this->get($this->RANK))) {
									$qr_fmax = $o_db->query("SELECT MAX(".$this->RANK.") m FROM ".$this->TABLE);
									$qr_fmax->nextRow();
									$vs_field_value = $qr_fmax->get("m")+1;
									$this->set($vs_field, $vs_field_value);
								}
							}
							$vs_fields .= "$vs_field,";
							$v = $vs_field_value;
							if (!trim($v)) { $v = 0; }
							if (!is_numeric($v)) {
								$this->postError(1100,_t("Number is invalid for %1 [%2]", $vs_field, $v),"BaseModel->insert()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							$vs_values .= $v.",";
							break;
						# -----------------------------
						case (FT_TIMECODE):
							$vs_fields .= $vs_field.",";
							$v = $vs_field_value;
							if (!trim($v)) { $v = 0; }
							if (!is_numeric($v)) {
								$this->postError(1100, _t("Timecode is invalid"),"BaseModel->insert()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							$vs_values .= $v.",";
							break;
						# -----------------------------
						case (FT_MEDIA):
							$vs_fields .= $vs_field.",";
							$vs_values .= "'',";
							$va_media_fields[] = $vs_field;
							break;
						# -----------------------------
						case (FT_FILE):
							$vs_fields .= $vs_field.",";
							$vs_values .= "'',";
							$va_file_fields[] = $vs_field;
							break;
						# -----------------------------
						case (FT_TEXT):
						case (FT_PASSWORD):
							$vs_fields .= $vs_field.",";
							$vs_value = $this->quote($this->get($vs_field,0,0,0,0));
							$vs_values .= $vs_value.",";
							break;
						# -----------------------------
						case (FT_VARS):
							$vs_fields .= $vs_field.",";
							$vs_values .= $this->quote(caSerializeForDatabase($this->get($vs_field,((isset($va_attr['COMPRESS']) && $va_attr['COMPRESS']) ? true : false),0,0,0))).",";
							break;
						# -----------------------------
						default:
							# Should never be executed
							die("$vs_field not caught in insert()");
						# -----------------------------
					}
				}
			}

			if ($this->numErrors() == 0) {
				$vs_fields = substr($vs_fields,0,strlen($vs_fields)-1);	# remove trailing comma
				$vs_values = substr($vs_values,0,strlen($vs_values)-1);	# remove trailing comma


				$vs_sql = "INSERT INTO ".$this->TABLE." ($vs_fields) VALUES ($vs_values)";

				if ($this->debug) echo $vs_sql;
				$o_db->query($vs_sql);
				
				if ($o_db->numErrors() == 0) {
					if ($this->getFieldInfo($this->primaryKey(), "IDENTITY")) {
						$this->_FIELD_VALUES[$this->primaryKey()] = $o_db->getLastInsertID();
					}

					if ((sizeof($va_media_fields) > 0) || (sizeof($va_file_fields) > 0) || ($this->getHierarchyType() == __CA_HIER_TYPE_ADHOC_MONO__)) {
						$vs_sql  = "";
						if (sizeof($va_media_fields) > 0) {
							foreach($va_media_fields as $f) {
								if($vs_msql = $this->_processMedia($f, array('delete_old_media' => false))) {
									$vs_sql .= $vs_msql;
								}
							}
						}

						if (sizeof($va_file_fields) > 0) {
							foreach($va_file_fields as $f) {
								if($vs_msql = $this->_processFiles($f)) {
									$vs_sql .= $vs_msql;
								}
							}
						}

						if($this->getHierarchyType() == __CA_HIER_TYPE_ADHOC_MONO__) {	// Ad-hoc hierarchy
							if (!$this->get($this->getProperty('HIERARCHY_ID_FLD'))) {
								$vs_sql .= $this->getProperty('HIERARCHY_ID_FLD').' = '.$this->getPrimaryKey().' ';
							}
						}

						if ($this->numErrors() == 0) {
							$vs_sql = substr($vs_sql, 0, strlen($vs_sql) - 1);
							if ($vs_sql) {
								$o_db->query("UPDATE ".$this->tableName()." SET ".$vs_sql." WHERE ".$this->primaryKey()." = ?", $this->getPrimaryKey(1));
							}
						} else {
							# media and/or file error
							$o_db->query("DELETE FROM ".$this->tableName()." WHERE ".$this->primaryKey()." = ?", $this->getPrimaryKey(1));
							$this->_FIELD_VALUES[$this->primaryKey()] = "";
							if ($vb_we_set_transaction) { $this->removeTransaction(false); }
							return false;
						}
					}

					$this->_FILES_CLEAR = array();
					$this->logChange("I");


					#
					# update search index
					#
					$vn_id = $this->getPrimaryKey();
					
					if ((!isset($pa_options['dont_do_search_indexing']) || (!$pa_options['dont_do_search_indexing'])) && !defined('__CA_DONT_DO_SEARCH_INDEXING__')) {
						$this->doSearchIndexing();
					}

					if ($vb_we_set_transaction) { $this->removeTransaction(true); }
					
					$this->_FIELD_VALUE_CHANGED = array();
					return $vn_id;
				} else {
					foreach($o_db->errors() as $o_e) {
						$this->postError($o_e->getErrorNumber(), $o_e->getErrorDescription().' ['.$o_e->getErrorNumber().']', "BaseModel->insert()");
					}
					if ($vb_we_set_transaction) { $this->removeTransaction(false); }
					return false;
				}
			} else {
				foreach($o_db->errors() as $o_e) {
					switch($vn_err_num = $o_e->getErrorNumber()) {
						case 251:	// violation of unique key (duplicate record)
							$va_indices = $o_db->getIndices($this->tableName());	// try to get key info

							if (preg_match("/for key [']{0,1}([\w]+)[']{0,1}$/", $o_e->getErrorDescription(), $va_matches)) {
								$va_field_labels = array();
								foreach($va_indices[$va_matches[1]]['fields'] as $vs_col_name) {
									$va_tmp = $this->getFieldInfo($vs_col_name);
									$va_field_labels[] = $va_tmp['LABEL'];
								}

								$vs_last_name = array_pop($va_field_labels);
								if (sizeof($va_field_labels) > 0) {
									$vs_err_desc = _t("The combination of %1 and %2 must be unique", join(', ', $va_field_labels), $vs_last_name);
								} else {
									$vs_err_desc = _t("The value of %1 must be unique", $vs_last_name);
								}
							} else {
								$vs_err_desc = $o_e->getErrorDescription();
							}
							$this->postError($vn_err_num, $vs_err_desc, "BaseModel->insert()");
							break;
						default:
							$this->postError($vn_err_num, $o_e->getErrorDescription().' ['.$vn_err_num.']', "BaseModel->insert()");
							break;
					}

				}
				if ($vb_we_set_transaction) { $this->removeTransaction(false); }
				return false;
			}
		} else {
			$this->postError(400, _t("Mode was %1; must be write", $this->getMode(true)),"BaseModel->insert()");
			return false;
		}
	}

	/**
	 * Generic method to save content properties of object to database fields
	 * Use this, if you've contructed your BaseModel object to represent an existing record
	 * if you've loaded an existing record.
	 *
	 * @param array $pa_options options array
	 * possible options (keys):
	 * dont_check_circular_references = when dealing with strict monohierarchical lists (also known as trees), you can use this option to disable checks for circuits in the graph
	 * update_only_media_version = when set to a valid media version, media is only processed for the specified version
	 * @return bool success state
	 */
	public function update ($pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$this->field_conflicts = array();
		$this->clearErrors();
		
		if (!$this->getPrimaryKey()) {
			$this->postError(765, _t("Can't do update() on new record; use insert() instead"),"BaseModel->update()");
			return false;
		}
		if ($this->getMode() == ACCESS_WRITE) {
			// do form timestamp check
			if (isset($_REQUEST['form_timestamp']) && ($vn_form_timestamp = $_REQUEST['form_timestamp'])) {
				$va_possible_conflicts = $this->getChangeLog($vn_form_timestamp, null, null, true, $this->getCurrentLoggingUnitID());
				if (sizeof($va_possible_conflicts)) {
					$va_conflict_users = array();
					$va_conflict_fields = array();
					foreach($va_possible_conflicts as $va_conflict) {
						$vs_user_desc = trim($va_conflict['fname'].' '.$va_conflict['lname']);
						if ($vs_user_email = trim($va_conflict['email'])) {
							$vs_user_desc .= ' ('.$vs_user_email.')';
						}
						
						if ($vs_user_desc) { $va_conflict_users[$vs_user_desc] = true; }
						if(isset($va_conflict['snapshot']) && is_array($va_conflict['snapshot'])) {
							foreach($va_conflict['snapshot'] as $vs_field => $vs_value) {
								if ($va_conflict_fields[$vs_field]) { continue; }
							
								$va_conflict_fields[$vs_field] = true;
							}
						}
					}
					
					$this->field_conflicts = array_keys($va_conflict_fields);
					switch (sizeof($va_conflict_users)) {
						case 0:
							$this->postError(795, _t('Changes have been made since you loaded this data. Save again to overwrite the changes or cancel to keep the changes.'), "BaseModel->update()");
							break;
						case 1:
							$this->postError(795, _t('Changes have been made since you loaded this data by %1. Save again to overwrite the changes or cancel to keep the changes.', join(', ', array_keys($va_conflict_users))), "BaseModel->update()");
							break;
						default:
							$this->postError(795, _t('Changes have been made since you loaded this data by these users: %1. Save again to overwrite the changes or cancel to keep the changes.', join(', ', array_keys($va_conflict_users))), "BaseModel->update()");
							break;
					}
				}
			}
			$vb_we_set_transaction = false;
			if (!$this->inTransaction()) {
				$this->setTransaction(new Transaction($this->getDb()));

				$vb_we_set_transaction = true;
			}
			$o_db = $this->getDb();
			
			if ($vb_is_hierarchical = $this->isHierarchical()) {
				$vs_parent_id_fld 		= $this->getProperty('HIERARCHY_PARENT_ID_FLD');
				$vs_hier_left_fld 		= $this->getProperty('HIERARCHY_LEFT_INDEX_FLD');
				$vs_hier_right_fld 		= $this->getProperty('HIERARCHY_RIGHT_INDEX_FLD');
				$vs_hier_id_fld			= $this->getProperty('HIERARCHY_ID_FLD');
				
				// save original left/right index values - we need them later to recalculate
				// the indexing values for children of this record
				$vn_orig_hier_left 		= $this->get($vs_hier_left_fld);
				$vn_orig_hier_right 	= $this->get($vs_hier_right_fld);
				$vn_parent_id 			= $this->get($vs_parent_id_fld);
				
				if ($vb_parent_id_changed = $this->changed($vs_parent_id_fld)) {
					$va_parent_info = $this->_getHierarchyParent($vn_parent_id);
					
					if (!$pa_options['dont_check_circular_references']) {
						$va_ids = $this->getHierarchyIDs($this->getPrimaryKey());
						if (in_array($this->get($vs_parent_id_fld), $va_ids)) {
							$this->postError(2010,_t("Cannot move %1 under its sub-record", $this->getProperty('NAME_SINGULAR')),"BaseModel->update()");
							if ($vb_we_set_transaction) { $this->removeTransaction(false); }
							return false;
						}
					}
					
					if (is_null($this->getOriginalValue($vs_parent_id_fld))) {
						// don't allow moves of hierarchy roots - just ignore the move and keep on going with the update
						$this->set($vs_parent_id_fld, null);
						$vb_parent_id_changed = false;
					} else {
					
						switch($this->getHierarchyType()) {
							case __CA_HIER_TYPE_SIMPLE_MONO__:	// you only need to set parent_id for this type of hierarchy
								
								break;
							case __CA_HIER_TYPE_MULTI_MONO__:	// you need to set parent_id; you only need to set hierarchy_id if you're creating a root
								if (!($vn_hierarchy_id = $this->get($this->getProperty('HIERARCHY_ID_FLD')))) {
									$this->set($this->getProperty('HIERARCHY_ID_FLD'), $va_parent_info[$this->getProperty('HIERARCHY_ID_FLD')]);
								}
								
								if (!($vn_hierarchy_id = $this->get($vs_hier_id_fld))) {
									$this->postError(2030, _t("Hierarchy ID must be specified for this update"), "BaseModel->update()");
									if ($vb_we_set_transaction) { $this->removeTransaction(false); }
									
									return false;
								}
								break;
							case __CA_HIER_TYPE_ADHOC_MONO__:	// you need to set parent_id for this hierarchy; you never need to set hierarchy_id
								if (!($vn_hierarchy_id = $this->get($this->getProperty('HIERARCHY_ID_FLD')))) {
									if ($va_parent_info) {
										// set hierarchy to that of parent
										$this->set($this->getProperty('HIERARCHY_ID_FLD'), $va_parent_info[$this->getProperty('HIERARCHY_ID_FLD')]);
									} 
									
									// if there's no parent then this is a root in which case HIERARCHY_ID_FLD should be set to the primary
									// key of the row, which we'll know once we insert it (so we must set it after insert)
								}
								break;
							case __CA_HIER_TYPE_MULTI_POLY__:	// TODO: implement
							
								break;
							default:
								die("Invalid hierarchy type: ".$this->getHierarchyType());
								break;
						}
						
						if ($va_parent_info) {
							$va_hier_indexing = $this->_calcHierarchicalIndexing($va_parent_info);
						} else {
							$va_hier_indexing = array('left' => 1, 'right' => pow(2,32));
						}
						$this->set($this->getProperty('HIERARCHY_LEFT_INDEX_FLD'), $va_hier_indexing['left']);
						$this->set($this->getProperty('HIERARCHY_RIGHT_INDEX_FLD'), $va_hier_indexing['right']);
					}
				}
			}

			$vs_sql = "UPDATE ".$this->TABLE." SET ";
			$va_many_to_one_relations = $this->_DATAMODEL->getManyToOneRelations($this->tableName());

			$vn_fields_that_have_been_set = 0;
			foreach ($this->FIELDS as $vs_field => $va_attr) {
				if (isset($va_attr['IDENTITY']) && $va_attr['IDENTITY']) { continue; }	// never update identity fields
				
				$vs_field_type = isset($va_attr["FIELD_TYPE"]) ? $va_attr["FIELD_TYPE"] : null;				# field type
				$vn_datatype = $this->_getFieldTypeType($vs_field);	# field's underlying datatype
				$vs_field_value = $this->get($vs_field, array("TIMECODE_FORMAT" => "RAW"));

				# --- check bounds (value, length and choice lists)
				if (!$this->verifyFieldValue($vs_field, $vs_field_value)) {
					# verifyFieldValue() posts errors so we don't have to do anything here
					# No query will be run if there are errors so we don't have to worry about invalid
					# values being written into the database. By not immediately bailing on an error we
					# can return a list of *all* input errors to the caller; this is perfect listing all form input errors in
					# a form-based user interface
				}

				if (!isset($va_attr["IS_NULL"])) { $va_attr["IS_NULL"] = 0; }
				if ($va_attr["IS_NULL"] && ($vs_field_value=="")) {
					$vs_field_value_is_null = 1;
				} else {
					$vs_field_value_is_null = 0;
				}


				# --- check ->one relations
				if (isset($va_many_to_one_relations[$vs_field]) && $va_many_to_one_relations[$vs_field]) {
					# Nothing to verify if key is null
					if (!(($va_attr["IS_NULL"]) && ($vs_field_value == ""))) {
						if ($t_many_table = $this->_DATAMODEL->getTableInstance($va_many_to_one_relations[$vs_field]["one_table"])) {
							if ($this->inTransaction()) {
								$t_many_table->setTransaction($this->getTransaction());
							}
							$t_many_table->load($this->get($va_many_to_one_relations[$vs_field]["many_table_field"]));
						}


						if ($t_many_table->numErrors()) {
							$this->postError(750,_t("%1 record with $vs_field = %2  does not exist", $t_many_table->tableName(), $this->get($vs_field)),"BaseModel->update()");
						}
					}
				}

				if (($vs_field_value_is_null) && (!(isset($va_attr["UPDATE_ON_UPDATE"]) && $va_attr["UPDATE_ON_UPDATE"]))) {
					if (($vs_field_type == FT_DATERANGE) || ($vs_field_type == FT_HISTORIC_DATERANGE) || ($vs_field_type == FT_TIMERANGE)) {
						$start_field_name = $va_attr["START"];
						$end_field_name = $va_attr["END"];
						$vs_sql .= "$start_field_name = NULL, $end_field_name = NULL,";
					} else {
						$vs_sql .= "$vs_field = NULL,";
					}
					$vn_fields_that_have_been_set++;
				} else {
					if (!$this->changed($vs_field)) { continue; }		// don't try to update fields that haven't changed -- saves time, especially for large fields like FT_VARS and FT_TEXT when text is long
					$vn_fields_that_have_been_set++;
					switch($vs_field_type) {
						# -----------------------------
						case (FT_NUMBER):
						case (FT_BIT):
							$vm_val = isset($this->_FIELD_VALUES[$vs_field]) ? $this->_FIELD_VALUES[$vs_field] : null;
							if (!trim($vm_val)) { $vm_val = 0; }

							if (!is_numeric($vm_val)) {
								$this->postError(1100,_t("Number is invalid for %1", $vs_field),"BaseModel->update()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							$vs_sql .= "{$vs_field} = {$vm_val},";
							break;
						# -----------------------------
						case (FT_TEXT):
						case (FT_PASSWORD):
							$vm_val = isset($this->_FIELD_VALUES[$vs_field]) ? $this->_FIELD_VALUES[$vs_field] : null;
							$vs_sql .= "{$vs_field} = ".$this->quote($vm_val).",";

							break;
						# -----------------------------
						case (FT_VARS):
							$vm_val = isset($this->_FIELD_VALUES[$vs_field]) ? $this->_FIELD_VALUES[$vs_field] : null;
							$vs_sql .= "{$vs_field} = ".$this->quote(caSerializeForDatabase($vm_val, ((isset($va_attr['COMPRESS']) && $va_attr['COMPRESS']) ? true : false))).",";
							break;
						# -----------------------------
						case (FT_DATETIME):
						case (FT_HISTORIC_DATETIME):
						case (FT_DATE):
						case (FT_HISTORIC_DATE):
							$vm_val = isset($this->_FIELD_VALUES[$vs_field]) ? $this->_FIELD_VALUES[$vs_field] : null;
							if ($vm_val == "") {
								$this->postError(1805,_t("Date is undefined but field does not support NULL values"),"BaseModel->update()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							if (!is_numeric($vm_val)) {
								$this->postError(1100,_t("Date is invalid for %1", $vs_field),"BaseModel->update()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							$vs_sql .= "{$vs_field} = {$vm_val},";		# output as is
							break;
						# -----------------------------
						case (FT_TIME):
							$vm_val = isset($this->_FIELD_VALUES[$vs_field]) ? $this->_FIELD_VALUES[$vs_field] : null;
							if ($vm_val == "") {
								$this->postError(1805, _t("Time is undefined but field does not support NULL values"),"BaseModel->update()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							if (!is_numeric($vm_val)) {
								$this->postError(1100, _t("Time is invalid for %1", $vs_field),"BaseModel->update()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							$vs_sql .= "{$vs_field} = {$vm_val},";		# output as is
							break;
						# -----------------------------
						case (FT_TIMESTAMP):
							if (isset($va_attr["UPDATE_ON_UPDATE"]) && $va_attr["UPDATE_ON_UPDATE"]) {
								$vs_sql .= "{$vs_field} = ".time().",";
							}
							break;
						# -----------------------------
						case (FT_DATERANGE):
						case (FT_HISTORIC_DATERANGE):
							$vn_start_field_name = $va_attr["START"];
							$vn_end_field_name = $va_attr["END"];

							if (($this->_FIELD_VALUES[$vn_start_field_name] == "") || ($this->_FIELD_VALUES[$vn_end_field_name] == "")) {
								$this->postError(1805,_t("Daterange is undefined but field does not support NULL values"),"BaseModel->update()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							if (!is_numeric($this->_FIELD_VALUES[$vn_start_field_name])) {
								$this->postError(1100,_t("Starting date is invalid"),"BaseModel->update()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							if (!is_numeric($this->_FIELD_VALUES[$vn_end_field_name])) {
								$this->postError(1100,_t("Ending date is invalid"),"BaseModel->update()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							$vs_sql .= "{$vn_start_field_name} = ".$this->_FIELD_VALUES[$vn_start_field_name].", {$vn_end_field_name} = ".$this->_FIELD_VALUES[$vn_end_field_name].",";
							break;
						# -----------------------------
						case (FT_TIMERANGE):
							$vn_start_field_name = $va_attr["START"];
							$vn_end_field_name = $va_attr["END"];

							if (($this->_FIELD_VALUES[$vn_start_field_name] == "") || ($this->_FIELD_VALUES[$vn_end_field_name] == "")) {
								$this->postError(1805,_t("Time range is undefined but field does not support NULL values"),"BaseModel->update()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							if (!is_numeric($this->_FIELD_VALUES[$vn_start_field_name])) {
								$this->postError(1100,_t("Starting time is invalid"),"BaseModel->update()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							if (!is_numeric($this->_FIELD_VALUES[$vn_end_field_name])) {
								$this->postError(1100,_t("Ending time is invalid"),"BaseModel->update()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							$vs_sql .= "{$vn_start_field_name} = ".$this->_FIELD_VALUES[$vn_start_field_name].", {$vn_end_field_name} = ".$this->_FIELD_VALUES[$vn_end_field_name].",";
							break;
						# -----------------------------
						case (FT_TIMECODE):
							$vm_val = isset($this->_FIELD_VALUES[$vs_field]) ? $this->_FIELD_VALUES[$vs_field] : null;
							if (!trim($vm_val)) { $vm_val = 0; }
							if (!is_numeric($vm_val)) {
								$this->postError(1100,_t("Timecode is invalid"),"BaseModel->update()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
							$vs_sql .= "{$vs_field} = {$vm_val},";
							break;
						# -----------------------------
						case (FT_MEDIA):
							if ($vs_media_sql = $this->_processMedia($vs_field, array('this_version_only' => (isset($pa_options['update_only_media_version']) ? $pa_options['update_only_media_version'] : null)))) {
								$vs_sql .= $vs_media_sql;
							} else {
								if ($this->numErrors() > 0) {
									if ($vb_we_set_transaction) { $this->removeTransaction(false); }
									return false;
								}
							}
							break;
						# -----------------------------
						case (FT_FILE):
							if ($vs_file_sql = $this->_processFiles($vs_field)) {
								$vs_sql .= $vs_file_sql;
							}
							break;
						# -----------------------------
					}
				}
			}
			if ($this->numErrors() == 0) {
				if ($vn_fields_that_have_been_set > 0) {
					$vs_sql = substr($vs_sql,0,strlen($vs_sql)-1);	# remove trailing comma
	
					$vs_sql .= " WHERE ".$this->PRIMARY_KEY." = ".$this->getPrimaryKey(1);
					if ($this->debug) echo $vs_sql;
					$o_db->query($vs_sql);
	
					if ($o_db->numErrors()) {
						foreach($o_db->errors() as $o_e) {
							switch($vn_err_num = $o_e->getErrorNumber()) {
								case 251:	// violation of unique key (duplicate record)
									// try to get key info
									$va_indices = $o_db->getIndices($this->tableName());
	
									if (preg_match("/for key [']{0,1}([\w]+)[']{0,1}$/", $o_e->getErrorDescription(), $va_matches)) {
										$va_field_labels = array();
										foreach($va_indices[$va_matches[1]]['fields'] as $vs_col_name) {
											$va_tmp = $this->getFieldInfo($vs_col_name);
											$va_field_labels[] = $va_tmp['LABEL'];
										}
	
										$vs_last_name = array_pop($va_field_labels);
										if (sizeof($va_field_labels) > 0) {
											$vs_err_desc = "The combination of ".join(', ', $va_field_labels).' and '.$vs_last_name." must be unique";
										} else {
											$vs_err_desc = "The value of {$vs_last_name} must be unique";
										}
									} else {
										$vs_err_desc = $o_e->getErrorDescription();
									}
									$this->postError($vn_err_num, $vs_err_desc, "BaseModel->insert()");
									break;
								default:
									$this->postError($vn_err_num, $o_e->getErrorDescription().' ['.$vn_err_num.']', "BaseModel->update()");
									break;
							}
						}
						if ($vb_we_set_transaction) { $this->removeTransaction(false); }
						return false;
					} else {
						if (($vb_is_hierarchical) && ($vb_parent_id_changed)) {
							// recalulate left/right indexing of sub-records
							
							$vn_interval_start = $this->get($vs_hier_left_fld);
							$vn_interval_end = $this->get($vs_hier_right_fld);
							if (($vn_interval_start > 0) && ($vn_interval_end > 0)) {
	
								if ($vs_hier_id_fld) {
									$vs_hier_sql = ' AND ('.$vs_hier_id_fld.' = '.$this->get($vs_hier_id_fld).')';
								} else {
									$vs_hier_sql = "";
								}
								
								$vn_ratio = ($vn_interval_end - $vn_interval_start)/($vn_orig_hier_right - $vn_orig_hier_left);
								$vs_sql = "
									UPDATE ".$this->tableName()."
									SET
										$vs_hier_left_fld = ($vn_interval_start + (($vs_hier_left_fld - $vn_orig_hier_left) * $vn_ratio)),
										$vs_hier_right_fld = ($vn_interval_end + (($vs_hier_right_fld - $vn_orig_hier_right) * $vn_ratio))
									WHERE
										(".$vs_hier_left_fld." BETWEEN ".$vn_orig_hier_left." AND ".$vn_orig_hier_right.")
										$vs_hier_sql
								";
								//print "<hr>$vs_sql<hr>";
								$o_db->query($vs_sql);
								if ($vn_err = $o_db->numErrors()) {
									$this->postError(2030, _t("Could not update sub records in hierarchy: [%1] %2", $vs_sql, join(';', $o_db->getErrors())),"BaseModel->update()");
									if ($vb_we_set_transaction) { $this->removeTransaction(false); }
									return false;
								}
							} else {
								$this->postError(2045, _t("Parent record had invalid hierarchical indexing (should not happen!)"), "BaseModel->update()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
						}
					}
					if ((!isset($pa_options['dont_do_search_indexing']) || (!$pa_options['dont_do_search_indexing'])) &&  !defined('__CA_DONT_DO_SEARCH_INDEXING__')) {
						# update search index
						$this->doSearchIndexing();
					}
					
					$this->logChange("U");
	
					$this->_FILES_CLEAR = array();
				}

				if ($vb_we_set_transaction) { $this->removeTransaction(true); }
				$this->_FIELD_VALUE_CHANGED = array();
				return true;
			} else {
				if ($vb_we_set_transaction) { $this->removeTransaction(false); }
				return false;
			}
		} else {
			$this->postError(400, _t("Mode was %1; must be write or admin", $this->getMode(true)),"BaseModel->update()");
			return false;
		}
	}
	
	public function doSearchIndexing($pa_changed_field_values_array=null, $pb_reindex_mode=false, $ps_engine=null) {
		if (defined("__CA_DONT_DO_SEARCH_INDEXING__")) { return; }
		if (is_null($pa_changed_field_values_array)) { 
			$pa_changed_field_values_array = $this->getChangedFieldValuesArray();
		}
		
		if (!BaseModel::$search_indexer) {
			BaseModel::$search_indexer = new SearchIndexer($this->getDb(), $ps_engine);
		}
		BaseModel::$search_indexer->indexRow($this->tableNum(), $this->getPrimaryKey(), $this->getFieldValuesArray(), $pb_reindex_mode, null, $pa_changed_field_values_array, $this->_FIELD_VALUES_OLD);
	}

	/**
	 * Delete record represented by this object. Uses the Datamodel object
	 * to generate possible dependencies and relationships.
	 *
	 * @param bool $delete_related delete stuff related to the record? pass non-zero value if you want to.
	 * @param array $pa_fields instead of deleting the record represented by this object instance you can
	 * pass an array of field => value assignments which is used in a SQL-DELETE-WHERE clause.
	 * @param array $pa_table_list this is your possibility to pass an array of table name => true assignments
	 * to specify which tables to omit when deleting related stuff
	 */
	public function delete ($delete_related=0, $pa_fields=null, $pa_table_list=null) {
		$this->clearErrors();
		if ((!$this->getPrimaryKey()) && (!is_array($pa_fields))) {	# is there a record loaded?
			$this->postError(770, _t("No record loaded"),"BaseModel->delete()");
			return false;
		}
		if (!is_array($pa_table_list)) {
			$pa_table_list = array();
		}
		$pa_table_list[$this->tableName()] = true;

		if ($this->getMode() == ACCESS_WRITE) {
			$vb_we_set_transaction = false;
			if (!$this->inTransaction()) {
				$o_t = new Transaction($this->getDb());
				$this->setTransaction($o_t);
				$vb_we_set_transaction = true;
			}

			$o_db = $this->getDb();

			if (is_array($pa_fields)) {
				$vs_sql = "DELETE FROM ".$this->tableName()." WHERE ";

				$vs_wheres = "";
				while(list($vs_field, $vm_val) = each($pa_fields)) {
					$vn_datatype = $this->_getFieldTypeType($vs_field);
					switch($vn_datatype) {
						# -----------------------------
						case (0):	# number
							if ($vm_val == "") { $vm_val = 0; }
							break;
						# -----------------------------
						case (1):	# string
							$vm_val = $this->quote($vm_val);
							break;
						# -----------------------------
					}

					if ($vs_wheres) {
						$vs_wheres .= " AND ";
					}
					$vs_wheres .= "($vs_field = $vm_val)";
				}

				$vs_sql .= $vs_wheres;
			} else {
				$vs_sql = "DELETE FROM ".$this->tableName()." WHERE ".$this->primaryKey()." = ".$this->getPrimaryKey(1);
			}

			if ($this->isHierarchical()) {
				// TODO: implement delete of children records
				$vs_parent_id_fld 		= $this->getProperty('HIERARCHY_PARENT_ID_FLD');
				$qr_res = $o_db->query("
					SELECT ".$this->primaryKey()."
					FROM ".$this->tableName()."
					WHERE
						{$vs_parent_id_fld} = ?
				", $this->getPrimaryKey());
				
				if ($qr_res->nextRow()) {
					$this->postError(780, _t("Can't delete item because it has sub-records"),"BaseModel->delete()");
					if ($vb_we_set_transaction) { $this->removeTransaction(false); }
					return false;	
				}
			}



			#
			# --- delete search index entries
			#
			$vn_id = $this->getPrimaryKey();
			
			// TODO: FIX THIS ISSUE!
			// NOTE: we delete the indexing here, before we actually do the 
			// SQL delete because the search indexer relies upon the relevant
			// relationships to be intact (ie. exist) in order to properly remove the indexing for them.
			//
			// In particular, the incremental indexing used by the MySQL Fulltext plugin fails to properly
			// update if it can't traverse the relationships it is to remove.
			//
			// By removing the search indexing here we run the risk of corrupting the search index if the SQL
			// delete subsequently fails. Specifically, the indexing for rows that still exist in the database
			// will be removed. Wrapping everything in a MySQL transaction deals with it for MySQL Fulltext, but
			// other non-SQL engines (Lucene, SOLR, etc.) are still affected. 
			//
			// At some point we need to come up with something clever to handle this. Most likely it means moving all of the actual
			// analysis to startRowUnindexing() and only executing commands in commitRowUnIndexing(). For now we blithely assume that 
			// SQL deletes always succeed. If they don't we can always reindex. Only the indexing is affected, not the underlying data.
			if(!defined('__CA_DONT_DO_SEARCH_INDEXING__')) {
				$o_search_index = new SearchIndexer($o_db);
				$o_search_index->startRowUnIndexing($this->tableNum(), $vn_id);
				$o_search_index->commitRowUnIndexing($this->tableNum(), $vn_id);
			}

			# --- Check ->many and many<->many relations
			$va_one_to_many_relations = $this->_DATAMODEL->getOneToManyRelations($this->tableName());


			#
			# Note: cascading delete code is very slow when used
			# on a record with a large number of related records as
			# each record in check individually for cascading deletes...
			# it is possible to make this *much* faster by crafting clever-er queries
			#
			if (is_array($va_one_to_many_relations)) {
				foreach($va_one_to_many_relations as $vs_many_table => $va_info) {
					foreach($va_info as $va_relationship) {
						if (isset($pa_table_list[$vs_many_table.'/'.$va_relationship["many_table_field"]]) && $pa_table_list[$vs_many_table.'/'.$va_relationship["many_table_field"]]) { continue; }

						# do any records exist?
						$t_related = $this->_DATAMODEL->getTableInstance($vs_many_table);
						$t_related->setTransaction($this->getTransaction());
						$qr_record_check = $o_db->query("
							SELECT ".$t_related->primaryKey()."
							FROM ".$vs_many_table."
							WHERE
								(".$va_relationship["many_table_field"]." = ".$this->getPrimaryKey(1).")
						");
						
						$pa_table_list[$vs_many_table.'/'.$va_relationship["many_table_field"]] = true;

						//print "FOR ".$vs_many_table.'/'.$va_relationship["many_table_field"].":".$qr_record_check->numRows()."<br>\n";
						if ($qr_record_check->numRows() > 0) {
							if ($delete_related) {
								while($qr_record_check->nextRow()) {
									if ($t_related->load($qr_record_check->get($t_related->primaryKey()))) {
										$t_related->setMode(ACCESS_WRITE);
										$t_related->delete($delete_related, null, $pa_table_list);
										
										if ($t_related->numErrors()) {
											$this->postError(790, _t("Can't delete item because items related to it have sub-records (%1)", $vs_many_table),"BaseModel->delete()");
											if ($vb_we_set_transaction) { $this->removeTransaction(false); }
											return false;
										}
									}
								}
							} else {
								$this->postError(780, _t("Can't delete item because it is in use (%1)", $vs_many_table),"BaseModel->delete()");
								if ($vb_we_set_transaction) { $this->removeTransaction(false); }
								return false;
							}
						}
					}
				}
			}
			
			# --- do deletion
			if ($this->debug) echo $vs_sql;
			$o_db->query($vs_sql);
			if ($o_db->numErrors() > 0) {
				$this->errors = $o_db->errors();
				if ($vb_we_set_transaction) { $this->removeTransaction(false); }
				return false;
			}
			
			# cancel and pending queued tasks against this record
			$tq = new TaskQueue();
			$tq->cancelPendingTasksForRow(join("/", array($this->tableName(), $vn_id)));

			$this->_FILES_CLEAR = array();

			# --- delete media and file field files
			foreach($this->FIELDS as $f => $attr) {
				switch($attr['FIELD_TYPE']) {
					case FT_MEDIA:
						$versions = $this->getMediaVersions($f);
						foreach ($versions as $v) {
							$this->_removeMedia($f, $v);
						}
						break;
					case FT_FILE:
						@unlink($this->getFilePath($f));

						#--- delete conversions
						#
						foreach ($this->getFileConversions($f) as $vs_format => $va_file_conversion) {
							@unlink($this->getFileConversionPath($f, $vs_format));
						}
						break;
				}
			}


			if ($o_db->numErrors() == 0) {
				if ($this->tableNum()) {
					#
					# --- delete metadata associated with this record
					#
					$o_db->query("
						DELETE FROM ca_attributes
						WHERE
							(table_num = ?) AND (row_id = ?)
					", $this->tableNum(), $vn_id);
				}
				if ($vb_is_hierarchical = $this->isHierarchical()) {
					
				}
				# clear object
				$this->logChange("D");
				
				$this->clear();
			} else {
				if ($vb_we_set_transaction) { $this->removeTransaction(false); }
				return false;
			}

			if ($vb_we_set_transaction) { $this->removeTransaction(true); }
			return true;
		} else {
			$this->postError(400, _t("Mode was %1; must be write", $this->getMode(true)),"BaseModel->delete()");
			return false;
		}
	}

	# --------------------------------------------------------------------------------
	# --- Uploaded media handling
	# --------------------------------------------------------------------------------
	/**
	 * Check if media content is mirrored (depending on settings in configuration file)
	 *
	 * @return bool
	 */
	public function mediaIsMirrored($field, $version) {
		$media_info = $this->get($field);
		if (!is_array($media_info)) {
			return "";
		}
		$vi = $this->_MEDIA_VOLUMES->getVolumeInformation($media_info[$version]["VOLUME"]);
		if (!is_array($vi)) {
			return "";
		}
		if (is_array($vi["mirrors"])) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * Get status of media mirror
	 *
	 * @param string $field field name
	 * @param string $version version of the media file, as defined in media_processing.conf
	 * @param string media mirror name, as defined in media_volumes.conf
	 * @return mixed media mirror status
	 */
	public function getMediaMirrorStatus($field, $version, $mirror="") {
		$media_info = $this->get($field);
		if (!is_array($media_info)) {
			return "";
		}
		$vi = $this->_MEDIA_VOLUMES->getVolumeInformation($media_info[$version]["VOLUME"]);
		if (!is_array($vi)) {
			return "";
		}
		if ($mirror) {
			return $media_info["MIRROR_STATUS"][$mirror];
		} else {
			return $media_info["MIRROR_STATUS"][$vi["accessUsingMirror"]];
		}
	}
	/**
	 * Retry mirroring of given media field. Sets global error properties on failure.
	 *
	 * @param string $ps_field field name
	 * @param string $ps_version version of the media file, as defined in media_processing.conf
	 * @return null
	 */
	public function retryMediaMirror($ps_field, $ps_version) {
		global $AUTH_CURRENT_USER_ID;
		
		$va_media_info = $this->get($ps_field);
		if (!is_array($va_media_info)) {
			return "";
		}
		$va_volume_info = $this->_MEDIA_VOLUMES->getVolumeInformation($va_media_info[$ps_version]["VOLUME"]);
		if (!is_array($va_volume_info)) {
			return "";
		}

		$o_tq = new TaskQueue();
		$vs_row_key = join("/", array($this->tableName(), $this->getPrimaryKey()));
		$vs_entity_key = join("/", array($this->tableName(), $ps_field, $this->getPrimaryKey(), $ps_version));


		foreach($va_media_info["MIRROR_STATUS"] as $vs_mirror_code => $vs_status) {
			$va_mirror_info = $va_volume_info["mirrors"][$vs_mirror_code];
			$vs_mirror_method = $va_mirror_info["method"];
			$vs_queue = $vs_mirror_method."mirror";

			switch($vs_status) {
				case 'FAIL':
				case 'PARTIAL':
					if (!($o_tq->cancelPendingTasksForEntity($vs_entity_key))) {
						//$this->postError(560, _t("Could not cancel pending tasks: %1", $this->error),"BaseModel->retryMediaMirror()");
						//return false;
					}

					if ($o_tq->addTask(
						$vs_queue,
						array(
							"MIRROR" => $vs_mirror_code,
							"VOLUME" => $va_media_info[$ps_version]["VOLUME"],
							"FIELD" => $ps_field,
							"TABLE" => $this->tableName(),
							"VERSION" => $ps_version,
							"FILES" => array(
								array(
									"FILE_PATH" => $this->getMediaPath($ps_field, $ps_version),
									"ABS_PATH" => $va_volume_info["absolutePath"],
									"HASH" => $this->_FIELD_VALUES[$ps_field][$ps_version]["HASH"],
									"FILENAME" => $this->_FIELD_VALUES[$ps_field][$ps_version]["FILENAME"]
								)
							),

							"MIRROR_INFO" => $va_mirror_info,

							"PK" => $this->primaryKey(),
							"PK_VAL" => $this->getPrimaryKey()
						),
						array("priority" => 100, "entity_key" => $vs_entity_key, "row_key" => $vs_row_key, 'user_id' => $AUTH_CURRENT_USER_ID)))
					{
						$va_media_info["MIRROR_STATUS"][$vs_mirror_code] = ""; // pending
						$this->setMediaInfo($ps_field, $va_media_info);
						$this->setMode(ACCESS_WRITE);
						$this->update();
						continue;
					} else {
						$this->postError(100, _t("Couldn't queue mirror using '%1' for version '%2' (handler '%3')", $vs_mirror_method, $ps_version, $queue),"BaseModel->retryMediaMirror()");
					}
					break;
			}
		}

	}

	/**
	 * Returns url of media file
	 *
	 * @param string $ps_field field name
	 * @param string $ps_version version of the media file, as defined in media_processing.conf
	 * @param int $pn_page page number, defaults to 1
	 * @return string the url
	 */
	public function getMediaUrl($ps_field, $ps_version, $pn_page=1) {
		$va_media_info = $this->get($ps_field);
		if (!is_array($va_media_info)) {
			return "";
		}

		#
		# Is this version externally hosted?
		#
		if (isset($va_media_info[$ps_version]["EXTERNAL_URL"]) && ($va_media_info[$ps_version]["EXTERNAL_URL"])) {
			return $va_media_info[$ps_version]["EXTERNAL_URL"];
		}
		
		#
		# Is this version queued for processing?
		#
		if (isset($va_media_info[$ps_version]["QUEUED"]) && ($va_media_info[$ps_version]["QUEUED"])) {
			if ($va_media_info[$ps_version]["QUEUED_ICON"]["src"]) {
				return $va_media_info[$ps_version]["QUEUED_ICON"]["src"];
			} else {
				return "";
			}
		}

		$va_volume_info = $this->_MEDIA_VOLUMES->getVolumeInformation($va_media_info[$ps_version]["VOLUME"]);
		if (!is_array($va_volume_info)) {
			return "";
		}

		# is this mirrored?
		if (
			(isset($va_volume_info["accessUsingMirror"]) && $va_volume_info["accessUsingMirror"])
			&& 
			(
				isset($va_media_info["MIRROR_STATUS"][$va_volume_info["accessUsingMirror"]]) 
				&& 
				($va_media_info["MIRROR_STATUS"][$va_volume_info["accessUsingMirror"]] == "SUCCESS")
			)
		) {
			$vs_protocol = 	$va_volume_info["mirrors"][$va_volume_info["accessUsingMirror"]]["accessProtocol"];
			$vs_host = 		$va_volume_info["mirrors"][$va_volume_info["accessUsingMirror"]]["accessHostname"];
			$vs_url_path = 	$va_volume_info["mirrors"][$va_volume_info["accessUsingMirror"]]["accessUrlPath"];
		} else {
			$vs_protocol = 	$va_volume_info["protocol"];
			$vs_host = 		$va_volume_info["hostname"];
			$vs_url_path = 	$va_volume_info["urlPath"];
		}

		if ($va_media_info[$ps_version]["FILENAME"]) {
			$vs_fpath = join("/",array($vs_url_path, $va_media_info[$ps_version]["HASH"], $va_media_info[$ps_version]["MAGIC"]."_".$va_media_info[$ps_version]["FILENAME"]));
			return $vs_protocol."://$vs_host".$vs_fpath;
		} else {
			return "";
		}
	}

	/**
	 * Returns path of media file
	 *
	 * @param string $ps_field field name
	 * @param string $ps_version version of the media file, as defined in media_processing.conf
	 * @param int $pn_page page number, defaults to 1
	 * @return string path of the media file
	 */
	public function getMediaPath($ps_field, $ps_version, $pn_page=1) {
		$va_media_info = $this->get($ps_field);
		if (!is_array($va_media_info)) {
			return "";
		}

		#
		# Is this version externally hosted?
		#
		if (isset($va_media_info[$ps_version]["EXTERNAL_URL"]) && ($va_media_info[$ps_version]["EXTERNAL_URL"])) {
			return '';		// no local path for externally hosted media
		}

		#
		# Is this version queued for processing?
		#
		if (isset($va_media_info[$ps_version]["QUEUED"]) && $va_media_info[$ps_version]["QUEUED"]) {
			if ($va_media_info[$ps_version]["QUEUED_ICON"]["filepath"]) {
				return $va_media_info[$ps_version]["QUEUED_ICON"]["filepath"];
			} else {
				return "";
			}
		}

		$va_volume_info = $this->_MEDIA_VOLUMES->getVolumeInformation($va_media_info[$ps_version]["VOLUME"]);

		if (!is_array($va_volume_info)) {
			return "";
		}

		if ($va_media_info[$ps_version]["FILENAME"]) {
			return join("/",array($va_volume_info["absolutePath"], $va_media_info[$ps_version]["HASH"], $va_media_info[$ps_version]["MAGIC"]."_".$va_media_info[$ps_version]["FILENAME"]));
		} else {
			return "";
		}
	}

	/**
	 * Returns appropriate representation of that media version in an html tag, including attributes for display
	 *
	 * @param string $field field name
	 * @param string $version version of the media file, as defined in media_processing.conf
	 * @param string $name name attribute of the img tag
	 * @param string $vspace vspace attribute of the img tag - note: deprecated in HTML 4.01, not supported in XHTML 1.0 Strict
	 * @param string $hspace hspace attribute of the img tag - note: deprecated in HTML 4.01, not supported in XHTML 1.0 Strict
	 * @param string $alt alt attribute of the img tag
	 * @param int $border border attribute of the img tag - note: deprecated in HTML 4.01, not supported in XHTML 1.0 Strict
	 * @param string $usemap usemap attribute of the img tag
	 * @param int $align align attribute of the img tag - note: deprecated in HTML 4.01, not supported in XHTML 1.0 Strict
	 * @return string html tag
	 */
	public function getMediaTag($field, $version, $name="",$vspace="", $hspace="", $alt="", $border=0, $usemap="", $align="") {
		$media_info = $this->get($field);
		if (!is_array($media_info[$version])) {
			return "";
		}

		#
		# Is this version queued for processing?
		#
		if (isset($media_info[$version]["QUEUED"]) && ($media_info[$version]["QUEUED"])) {
			if ($media_info[$version]["QUEUED_ICON"]["src"]) {
				return "<img src='".$media_info[$version]["QUEUED_ICON"]["src"]."' width='".$media_info[$version]["QUEUED_ICON"]["width"]."' height='".$media_info[$version]["QUEUED_ICON"]["height"]."' alt='".$media_info[$version]["QUEUED_ICON"]["alt"]."'>";
			} else {
				return $media_info[$version]["QUEUED_MESSAGE"];
			}
		}

		if (!is_array($name)) {
			$options = array("name"=> $name, "vspace" => $vspace, "hspace" => $hspace, "alt" => $alt, "border" => $border, "usemap" => $usemap, "align" => $align);
		} else {
			$options = $name;
		}

		$url = $this->getMediaUrl($field, $version, isset($options["page"]) ? $options["page"] : null);
		$m = new Media();
		
		$o_vol = new MediaVolumes();
		$va_volume = $o_vol->getVolumeInformation($media_info[$version]['VOLUME']);

		return $m->htmlTag($media_info[$version]["MIMETYPE"], $url, $media_info[$version]["PROPERTIES"], $options, $va_volume);
	}

	/**
	 * Get media information for the given field
	 *
	 * @param string $field field name
	 * @param string $version version of the media file, as defined in media_processing.conf, can be omitted to retrieve information about all versions
	 * @param string $property this is your opportunity to restrict the result to a certain property for the given (field,version) pair.
	 * possible values are:
	 * -VOLUME
	 * -MIMETYPE
	 * -WIDTH
	 * -HEIGHT
	 * -PROPERTIES: returns an array with some media metadata like width, height, mimetype, etc.
	 * -FILENAME
	 * -HASH
	 * -MAGIC
	 * -EXTENSION
	 * -MD5
	 * @return mixed media information
	 */
	public function &getMediaInfo($field, $version="", $property="") {
		$media_info = $this->get($field, array('USE_MEDIA_FIELD_VALUES' => true));
		if (!is_array($media_info)) {
			return "";
		}

		if ($version) {
			if (!$property) {
				return $media_info[$version];
			} else {
				return $media_info[$version][$property];
			}
		} else {
			return $media_info;
		}
	}

	/**
	 * Fetches media input type for the given field, e.g. "image"
	 *
	 * @param $ps_field field name
	 * @return string media input type
	 */
	public function getMediaInputType($ps_field) {
		if ($va_media_info = $this->getMediaInfo($ps_field)) {
			$o_media_proc_settings = new MediaProcessingSettings($this, $ps_field);
			return $o_media_proc_settings->canAccept($va_media_info["INPUT"]["MIMETYPE"]);
		} else {
			return null;
		}
	}

	/**
	 * Returns default version to display for the given field based upon the currently loaded row
	 *
	 * @param string $ps_field field name
	 */
	public function getDefaultMediaVersion($ps_field) {
		if ($va_media_info = $this->getMediaInfo($ps_field)) {
			$o_media_proc_settings = new MediaProcessingSettings($this, $ps_field);
			$va_type_info = $o_media_proc_settings->getMediaTypeInfo($o_media_proc_settings->canAccept($va_media_info["INPUT"]["MIMETYPE"]));
			
			return $va_type_info['MEDIA_VIEW_DEFAULT_VERSION'];
		} else {
			return null;
		}	
	}
	
	/**
	 * Fetches available media versions for the given field (and optional mimetype), as defined in media_processing.conf
	 *
	 * @param string $ps_field field name
	 * @param string $ps_mimetype optional mimetype restriction
	 * @return array list of available media versions
	 */
	public function getMediaVersions($ps_field, $ps_mimetype="") {
		if (!$ps_mimetype) {
			# figure out mimetype from field content
			$va_media_desc = $this->get($ps_field);
			
			if (is_array($va_media_desc)) {
				unset($va_media_desc["ORIGINAL_FILENAME"]);
				unset($va_media_desc["INPUT"]);
				unset($va_media_desc["VOLUME"]);
				return array_keys($va_media_desc);
			}
		} else {
			$o_media_proc_settings = new MediaProcessingSettings($this, $ps_field);
			if ($vs_media_type = $o_media_proc_settings->canAccept($ps_mimetype)) {
				$va_version_list = $o_media_proc_settings->getMediaTypeVersions($vs_media_type);
				if (is_array($va_version_list)) {
					return array_keys($va_version_list);
				}
			}
		}
		return array();
	}

	/**
	 * Checks if a media version for the given field exists.
	 *
	 * @param string $ps_field field name
	 * @param string $ps_version string representation of the version you are asking for
	 * @return bool
	 */
	public function hasMediaVersion($ps_field, $ps_version) {
		return in_array($ps_version, $this->getMediaVersions($ps_field));
	}

	/**
	 * Fetches processing settings information for the given field with respect to the given mimetype
	 *
	 * @param string $ps_field field name
	 * @param string $ps_mimetype mimetype
	 * @return array containing the information defined in media_processing.conf
	 */
	public function &getMediaTypeInfo($ps_field, $ps_mimetype="") {
		$o_media_proc_settings = new MediaProcessingSettings($this, $ps_field);

		if (!$ps_mimetype) {
			# figure out mimetype from field content
			$va_media_desc = $this->get($ps_field);
			if ($vs_media_type = $o_media_proc_settings->canAccept($media_desc["INPUT"]["MIMETYPE"])) {
				return $o_media_proc_settings->getMediaTypeInfo($vs_media_type);
			}
		} else {
			if ($vs_media_type = $o_media_proc_settings->canAccept($ps_mimetype)) {
				return $o_media_proc_settings->getMediaTypeInfo($vs_media_type);
			}
		}
		return null;
	}

	/**
	 * Sets media information
	 *
	 * @param $field field name
	 * @param array $info
	 * @return bool success state
	 */
	public function setMediaInfo($field, $info) {
		if(($this->getFieldInfo($field,"FIELD_TYPE")) == FT_MEDIA) {
			$this->_FIELD_VALUES[$field] = $info;
			$this->_FIELD_VALUE_CHANGED[$field] = 1;
			return true;
		}
		return false;
	}

	/**
	 * Clear media
	 *
	 * @param string $field field name
	 * @return bool always true
	 */
	public function clearMedia($field) {
		$this->_FILES_CLEAR[$field] = 1;
		return true;
	}

	/**
	 * Generate name for media file representation.
	 * Makes the application die if you try to call this on a BaseModel object not representing an actual db row.
	 *
	 * @access private
	 * @param string $field
	 * @return string the media name
	 */
	public function _genMediaName($field) {
		$pk = $this->getPrimaryKey();
		if ($pk) {
			return $this->TABLE."_".$field."_".$pk;
		} else {
			die("NO PK TO MAKE media name for $field!");
		}
	}

	/**
	 * Removes media
	 *
	 * @access private
	 * @param string $ps_field field name
	 * @param string $ps_version string representation of the version (e.g. original)
	 * @param string $ps_dont_delete_path
	 * @param string $ps_dont_delete extension
	 * @return null
	 */
	public function _removeMedia($ps_field, $ps_version, $ps_dont_delete_path="", $ps_dont_delete_extension="") {
		global $AUTH_CURRENT_USER_ID;
		
		$va_media_info = $this->getMediaInfo($ps_field,$ps_version);
		if (!$va_media_info) { return true; }

		$vs_volume = $va_media_info["VOLUME"];
		$va_volume_info = $this->_MEDIA_VOLUMES->getVolumeInformation($vs_volume);

		#
		# Get list of media files to delete
		#
		$va_files_to_delete = array();
		
		$vs_delete_path = $va_volume_info["absolutePath"]."/".$va_media_info["HASH"]."/".$va_media_info["MAGIC"]."_".$va_media_info["FILENAME"];
		if (($va_media_info["FILENAME"]) && ($vs_delete_path != $ps_dont_delete_path.".".$ps_dont_delete_extension)) {
			$va_files_to_delete[] = $va_media_info["MAGIC"]."_".$va_media_info["FILENAME"];
			@unlink($vs_delete_path);
		}
		
		# if media is mirrored, delete file off of mirrored server
		if (is_array($va_volume_info["mirrors"]) && sizeof($va_volume_info["mirrors"]) > 0) {
			$o_tq = new TaskQueue();
			$vs_row_key = join("/", array($this->tableName(), $this->getPrimaryKey()));
			$vs_entity_key = join("/", array($this->tableName(), $ps_field, $this->getPrimaryKey(), $ps_version));

			if (!($o_tq->cancelPendingTasksForEntity($vs_entity_key))) {
				$this->postError(560, _t("Could not cancel pending tasks: %1", $this->error),"BaseModel->_removeMedia()");
				return false;
			}
			foreach ($va_volume_info["mirrors"] as $vs_mirror_code => $va_mirror_info) {
				$vs_mirror_method = $va_mirror_info["method"];
				$vs_queue = $vs_mirror_method."mirror";

				if (!($o_tq->cancelPendingTasksForEntity($vs_entity_key))) {
					$this->postError(560, _t("Could not cancel pending tasks: %1", $this->error),"BaseModel->_removeMedia()");
					return false;
				}

				$va_tq_filelist = array();
				foreach($va_files_to_delete as $vs_filename) {
					$va_tq_filelist[] = array(
						"HASH" => $va_media_info["HASH"],
						"FILENAME" => $vs_filename
					);
				}
				if ($o_tq->addTask(
					$vs_queue,
					array(
						"MIRROR" => $vs_mirror_code,
						"VOLUME" => $vs_volume,
						"FIELD" => $f,
						"TABLE" => $this->tableName(),
						"DELETE" => 1,
						"VERSION" => $ps_version,
						"FILES" => $va_tq_filelist,

						"MIRROR_INFO" => $va_mirror_info,

						"PK" => $this->primaryKey(),
						"PK_VAL" => $this->getPrimaryKey()
					),
					array("priority" => 50, "entity_key" => $vs_entity_key, "row_key" => $vs_row_key, 'user_id' => $AUTH_CURRENT_USER_ID)))
				{
					continue;
				} else {
					$this->postError(100, _t("Couldn't queue mirror using '%1' for version '%2' (handler '%3')", $vs_mirror_method, $v, $queue),"BaseModel->_removeMedia()");
				}
			}
		}
	}

	/**
	 * perform media processing for the given field if sth. has been uploaded
	 *
	 * @access private
	 * @param string $ps_field field name
	 * @param array options
	 * 
	 * Supported options:
	 * 		delete_old_media = set to zero to prevent that old media files are deleted; defaults to 1
	 *		this_version_only = if set to a valid version name, then only the specified version is updated with the currently updated file; ignored if no media already exists
	 */
	public function _processMedia($ps_field, $pa_options=null) {
		global $AUTH_CURRENT_USER_ID;
		if(!is_array($pa_options)) { $pa_options = array(); }
		if(!isset($pa_options['delete_old_media'])) { $pa_options['delete_old_media'] = true; }
		if(!isset($pa_options['this_version_only'])) { $pa_options['this_version_only'] = null; }
		
		$vs_sql = "";

	 	$vn_max_execution_time = ini_get('max_execution_time');
	 	set_time_limit(7200);
	 	
		$o_tq = new TaskQueue();
		$o_media_proc_settings = new MediaProcessingSettings($this, $ps_field);

		# only set file if something was uploaded
		# (ie. don't nuke an existing file because none
		#      was uploaded)
		$va_field_info = $this->getFieldInfo($ps_field);
		if ((isset($this->_FILES_CLEAR[$ps_field])) && ($this->_FILES_CLEAR[$ps_field])) {
			//
			// Clear files
			//
			$va_versions = $this->getMediaVersions($ps_field);

			#--- delete files
			foreach ($va_versions as $v) {
				$this->_removeMedia($ps_field, $v);
			}

			$this->_FILES[$ps_field] = null;
			$this->_FIELD_VALUES[$ps_field] = null;
			$vs_sql =  "{$ps_field} = ".$this->quote(caSerializeForDatabase($this->_FILES[$ps_field], true)).",";
		} else {
			//
			// Process incoming files
			//
			$m = new Media();
			
			// is it a URL?
			$vs_url_fetched_from = null;
			$vn_url_fetched_on = null;
			
			$vb_allow_fetching_of_urls = (bool)$this->_CONFIG->get('allow_fetching_of_media_from_remote_urls');
			$vb_is_fetched_file = false;
			if ($vb_allow_fetching_of_urls && (bool)ini_get('allow_url_fopen') && isURL($this->_SET_FILES[$ps_field]['tmp_name'])) {
				$vs_tmp_file = tempnam(__CA_APP_DIR__.'/tmp', 'caUrlCopy');
				$r_incoming_fp = @fopen($this->_SET_FILES[$ps_field]['tmp_name'], 'r');
				
				if (!$r_incoming_fp) {
					$this->postError(1600, _t('Cannot open remote URL [%1] to fetch media', $this->_SET_FILES[$ps_field]['tmp_name']),"BaseModel->_processMedia()");
					set_time_limit($vn_max_execution_time);
					return false;
				}
				
				$r_outgoing_fp = fopen($vs_tmp_file, 'w');
				if (!$r_outgoing_fp) {
					$this->postError(1600, _t('Cannot open file for media fetched from URL [%1]', $this->_SET_FILES[$ps_field]['tmp_name']),"BaseModel->_processMedia()");
					set_time_limit($vn_max_execution_time);
					return false;
				}
				while(($vs_content = fgets($r_incoming_fp, 4096)) !== false) {
					fwrite($r_outgoing_fp, $vs_content);
				}
				fclose($r_incoming_fp);
				fclose($r_outgoing_fp);
				
				$vs_url_fetched_from = $this->_SET_FILES[$ps_field]['tmp_name'];
				$vn_url_fetched_on = time();
				$this->_SET_FILES[$ps_field]['tmp_name'] = $vs_tmp_file;
				$vb_is_fetched_file = true;
			}
			
			if (isset($this->_SET_FILES[$ps_field]['tmp_name']) && (file_exists($this->_SET_FILES[$ps_field]['tmp_name']))) {

				// ImageMagick partly relies on file extensions to properly identify images (RAW images in particular)
				// therefore we rename the temporary file here (using the extension of the original filename, if any)
				$va_matches = array();
				$vb_renamed_tmpfile = false;
				preg_match("/[.]*\.([a-zA-Z0-9]+)/",$this->_SET_FILES[$ps_field]['tmp_name'],$va_matches);
				if(!isset($va_matches[1])){ // file has no extension, i.e. is probably PHP upload tmp file
					$va_matches = array();
					preg_match("/[.]*\.([a-zA-Z0-9]+)/",$this->_SET_FILES[$ps_field]['original_filename'],$va_matches);
					if(strlen($va_matches[1])>0){
						$va_parts = explode("/",$this->_SET_FILES[$ps_field]['tmp_name']);
						$vs_new_filename = sys_get_temp_dir()."/".$va_parts[sizeof($va_parts)-1].".".$va_matches[1];
						move_uploaded_file($this->_SET_FILES[$ps_field]['tmp_name'],$vs_new_filename);
						$this->_SET_FILES[$ps_field]['tmp_name'] = $vs_new_filename;
						$vb_renamed_tmpfile = true;
					}
				}

				$input_mimetype = $m->divineFileFormat($this->_SET_FILES[$ps_field]['tmp_name']);
				if (!$input_type = $o_media_proc_settings->canAccept($input_mimetype)) {
					# error - filetype not accepted by this field
					$this->postError(1600, ($input_mimetype) ? _t("File type %1 not accepted by %2", $input_mimetype, $ps_field) : _t("Unknown file type not accepted by %1", $ps_field),"BaseModel->_processMedia()");
					set_time_limit($vn_max_execution_time);
					if ($vb_is_fetched_file) { @unlink($vs_tmp_file); }
					return false;
				}

				# ok process file...
				if (!($m->read($this->_SET_FILES[$ps_field]['tmp_name']))) {
					$this->errors = array_merge($this->errors, $m->errors());	// copy into model plugin errors
					//$this->postError(1600, _t("File for %1 could not be read", $ps_field),"BaseModel->_processMedia()");
					set_time_limit($vn_max_execution_time);
					if ($vb_is_fetched_file) { @unlink($vs_tmp_file); }
					return false;
				}
				
				$media_desc = array(
					"ORIGINAL_FILENAME" => $this->_SET_FILES[$ps_field]['original_filename'],
					"INPUT" => array(
						"MIMETYPE" => $m->get("mimetype"),
						"WIDTH" => $m->get("width"),
						"HEIGHT" => $m->get("height"),
						"MD5" => md5_file($this->_SET_FILES[$ps_field]['tmp_name']),
						"FILESIZE" => filesize($this->_SET_FILES[$ps_field]['tmp_name']),
						"FETCHED_FROM" => $vs_url_fetched_from,
						"FETCHED_ON" => $vn_url_fetched_on
					 )
				);
				
				#
				# Extract metadata from file
				#
				$media_metadata = $m->getExtractedMetadata();
				# get versions to create
				$va_versions = $this->getMediaVersions($ps_field, $input_mimetype);
				$error = 0;

				# don't process files that are not going to be processed or converted
				# we don't want to waste time opening file we're not going to do anything with
				# also, we don't want to recompress JPEGs...
				$media_type = $o_media_proc_settings->canAccept($input_mimetype);
				$version_info = $o_media_proc_settings->getMediaTypeVersions($media_type);
				$va_default_queue_settings = $o_media_proc_settings->getMediaTypeQueueSettings($media_type);

				if (!($va_media_write_options = $this->_FILES[$ps_field]['options'])) {
					$va_media_write_options = $this->_SET_FILES[$ps_field]['options'];
				}
				
				$vs_process_this_version_only = null;
				if (in_array($pa_options['this_version_only'], $va_versions)) {
					if (is_array($va_tmp = $this->_FIELD_VALUES[$ps_field])) {
						$vs_process_this_version_only = $pa_options['this_version_only'];
					
						foreach ($va_versions as $v) {
							if ($v != $vs_process_this_version_only) {
								$media_desc[$v] = $va_tmp[$v];
							}
						}
					}
				}

				$va_files_to_delete 	= array();
				$va_queued_versions 	= array();
				$queue_enabled			= (!$vs_process_this_version_only && $this->getAppConfig()->get('queue_enabled')) ? true : false;
				
				$vs_path_to_queue_media = null;
				
				foreach ($va_versions as $v) {
					if ($vs_process_this_version_only && ($vs_process_this_version_only != $v)) {
						// only processing a single version... and this one isn't it so skip
						continue;
					}
					
					$queue 				= $va_default_queue_settings['QUEUE'];
					$queue_threshold 	= isset($version_info[$v]['QUEUE_WHEN_FILE_LARGER_THAN']) ? intval($version_info[$v]['QUEUE_WHEN_FILE_LARGER_THAN']) : (int)$va_default_queue_settings['QUEUE_WHEN_FILE_LARGER_THAN'];
					$rule 				= isset($version_info[$v]['RULE']) ? $version_info[$v]['RULE'] : '';
					$volume 			= isset($version_info[$v]['VOLUME']) ? $version_info[$v]['VOLUME'] : '';

					# get volume
					$vi = $this->_MEDIA_VOLUMES->getVolumeInformation($volume);

					if (!is_array($vi)) {
						print "Invalid volume '{$volume}'<br>";
						exit;
					}
					
					// Send to queue it it's too big to process here
					if (($queue_enabled) && ($queue) && ($queue_threshold > 0) && ($queue_threshold < (int)$media_desc["INPUT"]["FILESIZE"]) && ($va_default_queue_settings['QUEUE_USING_VERSION'] != $v)) {
						$va_queued_versions[$v] = array(
							'VOLUME' => $volume
						);
						$media_desc[$v]["QUEUED"] = $queue;						
						if ($version_info[$v]["QUEUED_MESSAGE"]) {
							$media_desc[$v]["QUEUED_MESSAGE"] = $version_info[$v]["QUEUED_MESSAGE"];
						} else {
							$media_desc[$v]["QUEUED_MESSAGE"] = ($va_default_queue_settings['QUEUED_MESSAGE']) ? $va_default_queue_settings['QUEUED_MESSAGE'] : _t("Media is being processed and will be available shortly.");
						}
						
						if ($pa_options['delete_old_media']) {
							$va_files_to_delete[] = array(
								'field' => $ps_field,
								'version' => $v
							);
						}
						continue;
					}

					# get transformation rules
					$rules = $o_media_proc_settings->getMediaTransformationRule($rule);


					if (sizeof($rules) == 0) {
						$output_mimetype = $input_mimetype;
						$m->set("version", $v);

						#
						# don't process this media, just copy the file
						#
						$ext = $m->mimetype2extension($output_mimetype);

						if (!$ext) {
							$this->postError(1600, _t("File could not be copied for %1; can't convert mimetype '%2' to extension", $ps_field, $output_mimetype),"BaseModel->_processMedia()");
							$m->cleanup();
							set_time_limit($vn_max_execution_time);
							if ($vb_is_fetched_file) { @unlink($vs_tmp_file); }
							return false;
						}

						if (($dirhash = $this->_getDirectoryHash($vi["absolutePath"], $this->getPrimaryKey())) === false) {
							$this->postError(1600, _t("Could not create subdirectory for uploaded file in %1. Please ask your administrator to check the permissions of your media directory.", $vi["absolutePath"]),"BaseModel->_processMedia()");
							set_time_limit($vn_max_execution_time);
							if ($vb_is_fetched_file) { @unlink($vs_tmp_file); }
							return false;
						}

						if ((bool)$version_info[$v]["USE_EXTERNAL_URL_WHEN_AVAILABLE"]) { 
							$filepath = $this->_SET_FILES[$ps_field]['tmp_name'];
							
							if ($pa_options['delete_old_media']) {
								$va_files_to_delete[] = array(
									'field' => $ps_field,
									'version' => $v
								);
							}
														
							$media_desc[$v] = array(
								"VOLUME" => $volume,
								"MIMETYPE" => $output_mimetype,
								"WIDTH" => $m->get("width"),
								"HEIGHT" => $m->get("height"),
								"PROPERTIES" => $m->getProperties(),
								"EXTERNAL_URL" => $media_desc['INPUT']['FETCHED_FROM'],
								"FILENAME" => null,
								"HASH" => null,
								"MAGIC" => null,
								"EXTENSION" => $ext,
								"MD5" => md5_file($filepath)
							);
						} else {
							$magic = rand(0,99999);
							$filepath = $vi["absolutePath"]."/".$dirhash."/".$magic."_".$this->_genMediaName($ps_field)."_".$v.".".$ext;
							
							if (!copy($this->_SET_FILES[$ps_field]['tmp_name'], $filepath)) {
								$this->postError(1600, _t("File could not be copied. Ask your administrator to check permissions and file space for %1",$vi["absolutePath"]),"BaseModel->_processMedia()");
								$m->cleanup();
								set_time_limit($vn_max_execution_time);
								if ($vb_is_fetched_file) { @unlink($vs_tmp_file); }
								return false;
							}
							
							
							if ($v === $va_default_queue_settings['QUEUE_USING_VERSION']) {
								$vs_path_to_queue_media = $filepath;
							}
	
							if ($pa_options['delete_old_media']) {
								$va_files_to_delete[] = array(
									'field' => $ps_field,
									'version' => $v,
									'dont_delete_path' => $vi["absolutePath"]."/".$dirhash."/".$magic."_".$this->_genMediaName($ps_field)."_".$v,
									'dont_delete_extension' => $ext
								);
							}
	
							if (is_array($vi["mirrors"]) && sizeof($vi["mirrors"]) > 0) {
								$vs_entity_key = join("/", array($this->tableName(), $ps_field, $this->getPrimaryKey(), $v));
								$vs_row_key = join("/", array($this->tableName(), $this->getPrimaryKey()));
	
								foreach ($vi["mirrors"] as $vs_mirror_code => $va_mirror_info) {
									$vs_mirror_method = $va_mirror_info["method"];
									$vs_queue = $vs_mirror_method."mirror";
	
									if (!($o_tq->cancelPendingTasksForEntity($vs_entity_key))) {
										//$this->postError(560, _t("Could not cancel pending tasks: %1", $this->error),"BaseModel->_processMedia()");
										//$m->cleanup();
										//return false;
									}
									if ($o_tq->addTask(
										$vs_queue,
										array(
											"MIRROR" => $vs_mirror_code,
											"VOLUME" => $volume,
											"FIELD" => $ps_field,
											"TABLE" => $this->tableName(),
											"VERSION" => $v,
											"FILES" => array(
												array(
													"FILE_PATH" => $filepath,
													"ABS_PATH" => $vi["absolutePath"],
													"HASH" => $dirhash,
													"FILENAME" => $magic."_".$this->_genMediaName($ps_field)."_".$v.".".$ext
												)
											),
	
											"MIRROR_INFO" => $va_mirror_info,
	
											"PK" => $this->primaryKey(),
											"PK_VAL" => $this->getPrimaryKey()
										),
										array("priority" => 100, "entity_key" => $vs_entity_key, "row_key" => $vs_row_key, 'user_id' => $AUTH_CURRENT_USER_ID)))
									{
										continue;
									} else {
										$this->postError(100, _t("Couldn't queue mirror using '%1' for version '%2' (handler '%3')", $vs_mirror_method, $v, $queue),"BaseModel->_processMedia()");
									}
	
								}
							}
							
							$media_desc[$v] = array(
								"VOLUME" => $volume,
								"MIMETYPE" => $output_mimetype,
								"WIDTH" => $m->get("width"),
								"HEIGHT" => $m->get("height"),
								"PROPERTIES" => $m->getProperties(),
								"FILENAME" => $this->_genMediaName($ps_field)."_".$v.".".$ext,
								"HASH" => $dirhash,
								"MAGIC" => $magic,
								"EXTENSION" => $ext,
								"MD5" => md5_file($filepath)
							);
						}
					} else {
						$m->set("version", $v);
						while(list($operation, $parameters) = each($rules)) {
							if ($operation === 'SET') {
								foreach($parameters as $pp => $pv) {
									if ($pp == 'format') {
										$output_mimetype = $pv;
									} else {
										$m->set($pp, $pv);
									}
								}
							} else {
								if (!($m->transform($operation, $parameters))) {
									$error = 1;
									$error_msg = "Couldn't do transformation '$operation'";
									break(2);
								}
							}
						}

						if (!$output_mimetype) { $output_mimetype = $input_mimetype; }

						if (!($ext = $m->mimetype2extension($output_mimetype))) {
							$this->postError(1600, _t("File could not be processed for %1; can't convert mimetype '%2' to extension", $ps_field, $output_mimetype),"BaseModel->_processMedia()");
							$m->cleanup();
							set_time_limit($vn_max_execution_time);
							if ($vb_is_fetched_file) { @unlink($vs_tmp_file); }
							return false;
						}

						if (($dirhash = $this->_getDirectoryHash($vi["absolutePath"], $this->getPrimaryKey())) === false) {
							$this->postError(1600, _t("Could not create subdirectory for uploaded file in %1. Please ask your administrator to check the permissions of your media directory.", $vi["absolutePath"]),"BaseModel->_processMedia()");
							set_time_limit($vn_max_execution_time);
							if ($vb_is_fetched_file) { @unlink($vs_tmp_file); }
							return false;
						}
						$magic = rand(0,99999);
						$filepath = $vi["absolutePath"]."/".$dirhash."/".$magic."_".$this->_genMediaName($ps_field)."_".$v;


						$va_output_files = array();
						
						if (!($vs_output_file = $m->write($filepath, $output_mimetype, $va_media_write_options))) {
							$this->postError(1600,_t("Couldn't write file: %1", join("; ", $m->getErrors())),"BaseModel->_processMedia()");
							$m->cleanup();
							set_time_limit($vn_max_execution_time);
							if ($vb_is_fetched_file) { @unlink($vs_tmp_file); }
							return false;
							break;
						} else {
							$va_output_files[] = $vs_output_file;
						}
						
						if ($v === $va_default_queue_settings['QUEUE_USING_VERSION']) {
							$vs_path_to_queue_media = $vs_output_file;
						}

						if (($pa_options['delete_old_media']) && (!$error)) {
							if($vs_old_media_path = $this->getMediaPath($ps_field, $v)) {
								$va_files_to_delete[] = array(
									'field' => $ps_field,
									'version' => $v,
									'dont_delete_path' => $filepath,
									'dont_delete_extension' => $ext
								);
							}
						}

						if (is_array($vi["mirrors"]) && sizeof($vi["mirrors"]) > 0) {
							$vs_entity_key = join("/", array($this->tableName(), $ps_field, $this->getPrimaryKey(), $v));
							$vs_row_key = join("/", array($this->tableName(), $this->getPrimaryKey()));

							foreach ($vi["mirrors"] as $vs_mirror_code => $va_mirror_info) {
								$vs_mirror_method = $va_mirror_info["method"];
								$vs_queue = $vs_mirror_method."mirror";

								if (!($o_tq->cancelPendingTasksForEntity($vs_entity_key))) {
									//$this->postError(560, _t("Could not cancel pending tasks: %1", $this->error),"BaseModel->_processMedia()");
									//$m->cleanup();
									//return false;
								}
								if ($o_tq->addTask(
									$vs_queue,
									array(
										"MIRROR" => $vs_mirror_code,
										"VOLUME" => $volume,
										"FIELD" => $ps_field, "TABLE" => $this->tableName(),
										"VERSION" => $v,
										"FILES" => array(
											array(
												"FILE_PATH" => $filepath.".".$ext,
												"ABS_PATH" => $vi["absolutePath"],
												"HASH" => $dirhash,
												"FILENAME" => $magic."_".$this->_genMediaName($ps_field)."_".$v.".".$ext
											)
										),

										"MIRROR_INFO" => $va_mirror_info,

										"PK" => $this->primaryKey(),
										"PK_VAL" => $this->getPrimaryKey()
									),
									array("priority" => 100, "entity_key" => $vs_entity_key, "row_key" => $vs_row_key, 'user_id' => $AUTH_CURRENT_USER_ID)))
								{
									continue;
								} else {
									$this->postError(100, _t("Couldn't queue mirror using '%1' for version '%2' (handler '%3')", $vs_mirror_method, $v, $queue),"BaseModel->_processMedia()");
								}
							}
						}

						
						$media_desc[$v] = array(
							"VOLUME" => $volume,
							"MIMETYPE" => $output_mimetype,
							"WIDTH" => $m->get("width"),
							"HEIGHT" => $m->get("height"),
							"PROPERTIES" => $m->getProperties(),
							"FILENAME" => $this->_genMediaName($ps_field)."_".$v.".".$ext,
							"HASH" => $dirhash,
							"MAGIC" => $magic,
							"EXTENSION" => $ext,
							"MD5" => md5_file($vi["absolutePath"]."/".$dirhash."/".$magic."_".$this->_genMediaName($ps_field)."_".$v.".".$ext)
						);

						$m->reset();
					}
				}
				
				if (sizeof($va_queued_versions)) {
					$vs_entity_key = md5(join("/", array_merge(array($this->tableName(), $ps_field, $this->getPrimaryKey()), array_keys($va_queued_versions))));
					$vs_row_key = join("/", array($this->tableName(), $this->getPrimaryKey()));
					
					if (!($o_tq->cancelPendingTasksForEntity($vs_entity_key, $queue))) {
						// TODO: log this
					}
					
					if (!($filename = $vs_path_to_queue_media)) {
						// if we're not using a designated not-queued representation to generate the queued ones
						// then copy the uploaded file to the tmp dir and use that
						$filename = $o_tq->copyFileToQueueTmp($va_default_queue_settings['QUEUE'], $this->_SET_FILES[$ps_field]['tmp_name']);
					}
					
					if ($filename) {
						if ($o_tq->addTask(
							$va_default_queue_settings['QUEUE'],
							array(
								"TABLE" => $this->tableName(), "FIELD" => $ps_field,
								"PK" => $this->primaryKey(), "PK_VAL" => $this->getPrimaryKey(),
								
								"INPUT_MIMETYPE" => $input_mimetype,
								"FILENAME" => $filename,
								"VERSIONS" => $va_queued_versions,
								
								"OPTIONS" => $va_media_write_options,
								"DONT_DELETE_OLD_MEDIA" => ($filename == $vs_path_to_queue_media) ? true : false
							),
							array("priority" => 100, "entity_key" => $vs_entity_key, "row_key" => $vs_row_key, 'user_id' => $AUTH_CURRENT_USER_ID)))
						{
							if ($pa_options['delete_old_media']) {
								foreach($va_queued_versions as $vs_version => $va_version_info) {
									$va_files_to_delete[] = array(
										'field' => $ps_field,
										'version' => $vs_version
									);
								}
							}
						} else {
							$this->postError(100, _t("Couldn't queue processing for version '%1' using handler '%2'", !$v, $queue),"BaseModel->_processMedia()");
						}
					} else {
						$this->errors = $o_tq->errors;
					}
				} else {
					// Generate preview frames for media that support that (Eg. video)
					// and add them as "multifiles" assuming the current model supports that (ca_object_representations does)
					if (((bool)$this->_CONFIG->get('video_preview_generate_frames') || (bool)$this->_CONFIG->get('document_preview_generate_pages')) && method_exists($this, 'addFile')) {
						$va_preview_frame_list = $m->writePreviews(
							array(
								'width' => $m->get("width"), 
								'height' => $m->get("height"),
								'minNumberOfFrames' => $this->_CONFIG->get('video_preview_min_number_of_frames'),
								'maxNumberOfFrames' => $this->_CONFIG->get('video_preview_max_number_of_frames'),
								'numberOfPages' => $this->_CONFIG->get('document_preview_max_number_of_pages'),
								'frameInterval' => $this->_CONFIG->get('video_preview_interval_between_frames'),
								'pageInterval' => $this->_CONFIG->get('document_preview_interval_between_pages'),
								'startAtTime' => $this->_CONFIG->get('video_preview_start_at'),
								'endAtTime' => $this->_CONFIG->get('video_preview_end_at'),
								'startAtPage' => $this->_CONFIG->get('document_preview_start_page'),
								'outputDirectory' => __CA_APP_DIR__.'/tmp'
							)
						);
						
						$this->removeAllFiles();		// get rid of any previously existing frames (they might be hanging around if we're editing an existing record)
						if (is_array($va_preview_frame_list)) {
							foreach($va_preview_frame_list as $vn_time => $vs_frame) {
								$this->addFile($vs_frame, $vn_time, true);	// the resource path for each frame is it's time, in seconds (may be fractional) for video, or page number for documents
								@unlink($vs_frame);		// clean up tmp preview frame file
							}
						}
					}
				}
				
				if (!$error) {
					#
					# --- Clean up old media from versions that are not supported in the new media
					#
					if ($pa_options['delete_old_media']) {
						foreach ($this->getMediaVersions($ps_field) as $old_version) {
							if (!is_array($media_desc[$old_version])) {
								$this->_removeMedia($ps_field, $old_version);
							}
						}
					}

					foreach($va_files_to_delete as $va_file_to_delete) {
						$this->_removeMedia($va_file_to_delete['field'], $va_file_to_delete['version'], $va_file_to_delete['dont_delete_path'], $va_file_to_delete['dont_delete_extension']);
					}

					$this->_FILES[$ps_field] = $media_desc;
					$this->_FIELD_VALUES[$ps_field] = $media_desc;

					$vs_serialized_data = caSerializeForDatabase($this->_FILES[$ps_field], true);
					$vs_sql =  "$ps_field = ".$this->quote($vs_serialized_data).",";
					if (($vs_metadata_field_name = $o_media_proc_settings->getMetadataFieldName()) && $this->hasField($vs_metadata_field_name)) {
						$this->_FIELD_VALUES[$vs_metadata_field_name] = $this->quote(caSerializeForDatabase($media_metadata, true));
						$vs_sql .= " ".$vs_metadata_field_name." = ".$this->_FIELD_VALUES[$vs_metadata_field_name].",";
					}
				
					
					if (($vs_content_field_name = $o_media_proc_settings->getMetadataContentName()) && $this->hasField($vs_content_field_name)) {
						$this->_FIELD_VALUES[$vs_content_field_name] = $this->quote($m->getExtractedText());
						$vs_sql .= " ".$vs_content_field_name." = ".$this->_FIELD_VALUES[$vs_content_field_name].",";
					}
				} else {
					# error - invalid media
					$this->postError(1600, _t("File could not be processed for %1: %2", $ps_field, $error_msg),"BaseModel->_processMedia()");
					#	    return false;
				}

				$m->cleanup();

				if($vb_renamed_tmpfile){
					@unlink($this->_SET_FILES[$ps_field]['tmp_name']);
				}
			} else {
				if(is_array($this->_FIELD_VALUES[$ps_field])) {
					$this->_FILES[$ps_field] = $this->_FIELD_VALUES[$ps_field];
					$vs_sql =  "$ps_field = ".$this->quote(caSerializeForDatabase($this->_FILES[$ps_field], true)).",";
					if ($vs_metadata_field_name = $o_media_proc_settings->getMetadataFieldName()) {
						$this->_FIELD_VALUES[$vs_metadata_field_name] = $this->quote(caSerializeForDatabase($media_metadata, true));
						$vs_sql .= " ".$vs_metadata_field_name." = ".$this->_FIELD_VALUES[$vs_metadata_field_name].",";
					}
					
					if (($vs_content_field_name = $o_media_proc_settings->getMetadataContentName()) && $this->hasField($vs_content_field_name)) {
						$this->_FIELD_VALUES[$vs_content_field_name] = $this->quote($m->getExtractedText());
						$vs_sql .= " ".$vs_content_field_name." = ".$this->_FIELD_VALUES[$vs_content_field_name].",";
					}
				}
			}

			$this->_SET_FILES[$ps_field] = null;
		}
		set_time_limit($vn_max_execution_time);
		if ($vb_is_fetched_file) { @unlink($vs_tmp_file); }
		return $vs_sql;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Fetches hash directory
	 * 
	 * @access private
	 * @param string $basepath path
	 * @param int $id identifier
	 * @return string directory
	 */
	public function _getDirectoryHash ($basepath, $id) {
		$n = intval($id / 100);
		$dirs = array();
		$l = strlen($n);
		for($i=0;$i<$l; $i++) {
			$dirs[] = substr($n,$i,1);
			if (!file_exists($basepath."/".join("/", $dirs))) {
				if (!@mkdir($basepath."/".join("/", $dirs))) {
					return false;
				}
			}
		}

		return join("/", $dirs);
	}
	# --------------------------------------------------------------------------------
	# --- Uploaded file handling
	# --------------------------------------------------------------------------------
	/**
	 * Returns url of file
	 * 
	 * @access public
	 * @param $field field name
	 * @return string file url
	 */ 
	public function getFileUrl($ps_field) {
		$va_file_info = $this->get($ps_field);
		
		if (!is_array($va_file_info)) {
			return null;
		}

		$va_volume_info = $this->_FILE_VOLUMES->getVolumeInformation($va_file_info["VOLUME"]);
		if (!is_array($va_volume_info)) {
			return null;
		}

		$vs_protocol = $va_volume_info["protocol"];
		$vs_host = $va_volume_info["hostname"];
		$vs_path = join("/",array($va_volume_info["urlPath"], $va_file_info["HASH"], $va_file_info["MAGIC"]."_".$va_file_info["FILENAME"]));
		return $va_file_info["FILENAME"] ? "{$vs_protocol}://{$vs_host}.{$vs_path}" : "";
	}
	# --------------------------------------------------------------------------------
	/**
	 * Returns path of file
	 * 
	 * @access public
	 * @param string $field field name
	 * @return string path in local filesystem
	 */
	public function getFilePath($ps_field) {
		$va_file_info = $this->get($ps_field);
		if (!is_array($va_file_info)) {
			return null;
		}

		$va_volume_info = $this->_FILE_VOLUMES->getVolumeInformation($va_file_info["VOLUME"]);
		
		if (!is_array($va_volume_info)) {
			return null;
		}
		return join("/",array($va_volume_info["absolutePath"], $va_file_info["HASH"], $va_file_info["MAGIC"]."_".$va_file_info["FILENAME"]));
	}
	# --------------------------------------------------------------------------------
	/**
	 * Wrapper around BaseModel::get(), used to fetch information about files
	 * 
	 * @access public
	 * @param string $field field name
	 * @return array file information
	 */
	public function &getFileInfo($ps_field) {
		$va_file_info = $this->get($ps_field);
		if (!is_array($va_file_info)) {
			return null;
		}
		return $va_file_info;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Clear file
	 * 
	 * @access public
	 * @param string $field field name
	 * @return bool always true
	 */
	public function clearFile($ps_field) {
		$this->_FILES_CLEAR[$ps_field] = 1;
		return true;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Returns list of mimetypes of available conversions of files
	 * 
	 * @access public
	 * @param string $ps_field field name
	 * @return array
	 */ 
	public function getFileConversions($ps_field) {
		$va_info = $this->getFileInfo($ps_field);
		if (!is_array($va_info["CONVERSIONS"])) {
			return array();
		}
		return $va_info["CONVERSIONS"];
	}
	# --------------------------------------------------------------------------------
	/**
	 * Returns file path to converted version of file
	 * 
	 * @access public
	 * @param string $ps_field field name
	 * @param string $ps_format format of the converted version
	 * @return string file path
	 */ 
	public function getFileConversionPath($ps_field, $ps_format) {
		$va_info = $this->getFileInfo($ps_field);
		if (!is_array($va_info)) {
			return "";
		}

		$vi = $this->_FILE_VOLUMES->getVolumeInformation($va_info["VOLUME"]);

		if (!is_array($vi)) {
			return "";
		}
		$va_conversions = $this->getFileConversions($ps_field);

		if ($va_conversions[$ps_format]) {
			return join("/",array($vi["absolutePath"], $va_info["HASH"], $va_info["MAGIC"]."_".$va_conversions[$ps_format]["FILENAME"]));
		} else {
			return "";
		}
	}
	# --------------------------------------------------------------------------------
	/**
	 * Returns url to converted version of file
	 * 
	 * @access public
	 * @param string $ps_field field name
	 * @param string $ps_format format of the converted version
	 * @return string url
	 */
	public function getFileConversionUrl($ps_field, $ps_format) {
		$va_info = $this->getFileInfo($ps_field);
		if (!is_array($va_info)) {
			return "";
		}

		$vi = $this->_FILE_VOLUMES->getVolumeInformation($va_info["VOLUME"]);

		if (!is_array($vi)) {
			return "";
		}
		$va_conversions = $this->getFileConversions($ps_field);


		if ($va_conversions[$ps_format]) {
			return $vi["protocol"]."://".join("/", array($vi["hostname"], $vi["urlPath"], $va_info["HASH"], $va_info["MAGIC"]."_".$va_conversions[$ps_format]["FILENAME"]));
		} else {
			return "";
		}
	}
	# -------------------------------------------------------------------------------
	/**
	 * Generates filenames as follows: <table>_<field>_<primary_key>
	 * Makes the application die if no record is loaded
	 * 
	 * @access private
	 * @param string $field field name
	 * @return string file name
	 */
	public function _genFileName($field) {
		$pk = $this->getPrimaryKey();
		if ($pk) {
			return $this->TABLE."_".$field."_".$pk;
		} else {
			die("NO PK TO MAKE file name for $field!");
		}
	}
	# --------------------------------------------------------------------------------
	/**
	 * Processes uploaded files (only if something was uploaded)
	 * 
	 * @access private
	 * @param string $field field name
	 * @return string
	 */
	public function _processFiles($field) {
		$vs_sql = "";

		# only set file if something was uploaded
		# (ie. don't nuke an existing file because none
		#      was uploaded)
		if ((isset($this->_FILES_CLEAR[$field])) && ($this->_FILES_CLEAR[$field])) {
			#--- delete file
			@unlink($this->getFilePath($field));
			#--- delete conversions
			#
			# TODO: wvWWare MSWord conversion to HTML generates untracked graphics files for embedded images... they are currently
			# *not* deleted when the file and associated conversions are deleted. We will need to parse the HTML to derive the names
			# of these files...
			#
			foreach ($this->getFileConversions($field) as $vs_format => $va_file_conversion) {
				@unlink($this->getFileConversionPath($field, $vs_format));
			}

			$this->_FILES[$field] = "";
			$this->_FIELD_VALUES[$field] = "";

			$vs_sql =  "$field = ".$this->quote(caSerializeForDatabase($this->_FILES[$field], true)).",";
		} else {
			$va_field_info = $this->getFieldInfo($field);
			if ((file_exists($this->_SET_FILES[$field]['tmp_name']))) {
				$ff = new File();
				$mimetype = $ff->divineFileFormat($this->_SET_FILES[$field]['tmp_name'], $this->_SET_FILES[$field]['original_filename']);

				if (is_array($va_field_info["FILE_FORMATS"]) && sizeof($va_field_info["FILE_FORMATS"]) > 0) {
					if (!in_array($mimetype, $va_field_info["FILE_FORMATS"])) {
						$this->postError(1605, _t("File is not a valid format"),"BaseModel->_processFiles()");
						return false;
					}
				}

				$vn_dangerous = 0;
				if (!$mimetype) {
					$mimetype = "application/octet-stream";
					$vn_dangerous = 1;
				}
				# get volume
				$vi = $this->_FILE_VOLUMES->getVolumeInformation($va_field_info["FILE_VOLUME"]);

				if (!is_array($vi)) {
					print "Invalid volume ".$va_field_info["FILE_VOLUME"]."<br>";
					exit;
				}

				$properties = $ff->getProperties();
				if ($properties['dangerous'] > 0) { $vn_dangerous = 1; }

				if (($dirhash = $this->_getDirectoryHash($vi["absolutePath"], $this->getPrimaryKey())) === false) {
					$this->postError(1600, _t("Could not create subdirectory for uploaded file in %1. Please ask your administrator to check the permissions of your media directory.", $vi["absolutePath"]),"BaseModel->_processFiles()");
					return false;
				}
				$magic = rand(0,99999);

				$va_pieces = explode("/", $this->_SET_FILES[$field]['original_filename']);
				$ext = array_pop($va_tmp = explode(".", array_pop($va_pieces)));
				if ($properties["dangerous"]) { $ext .= ".bin"; }
				if (!$ext) $ext = "bin";

				$filestem = $vi["absolutePath"]."/".$dirhash."/".$magic."_".$this->_genMediaName($field);
				$filepath = $filestem.".".$ext;


				$filesize = isset($properties["filesize"]) ? $properties["filesize"] : 0;
				if (!$filesize) {
					$properties["filesize"] = filesize($this->_SET_FILES[$field]['tmp_name']);
				}

				$file_desc = array(
					"FILE" => 1, # signifies is file
					"VOLUME" => $va_field_info["FILE_VOLUME"],
					"ORIGINAL_FILENAME" => $this->_SET_FILES[$field]['original_filename'],
					"MIMETYPE" => $mimetype,
					"FILENAME" => $this->_genMediaName($field).".".$ext,
					"HASH" => $dirhash,
					"MAGIC" => $magic,
					"PROPERTIES" => $properties,
					"DANGEROUS" => $vn_dangerous,
					"CONVERSIONS" => array(),
					"MD5" => md5_file($this->_SET_FILES[$field]['tmp_name'])
				);

				if (!copy($this->_SET_FILES[$field]['tmp_name'], $filepath)) {
					$this->postError(1600, _t("File could not be copied. Ask your administrator to check permissions and file space for %1",$vi["absolutePath"]),"BaseModel->_processFiles()");
					return false;
				}


				# -- delete old file if its name is different from the one we just wrote (otherwise, we overwrote it)
				if ($filepath != $this->getFilePath($field)) {
					@unlink($this->getFilePath($field));
				}


				#
				# -- Attempt to do file conversions
				#
				if (isset($va_field_info["FILE_CONVERSIONS"]) && is_array($va_field_info["FILE_CONVERSIONS"]) && (sizeof($va_field_info["FILE_CONVERSIONS"]) > 0)) {
					foreach($va_field_info["FILE_CONVERSIONS"] as $vs_output_format) {
						if ($va_tmp = $ff->convert($vs_output_format, $filepath,$filestem)) { # new extension is added to end of stem by conversion
							$vs_file_ext = 			$va_tmp["extension"];
							$vs_format_name = 		$va_tmp["format_name"];
							$vs_long_format_name = 	$va_tmp["long_format_name"];
							$file_desc["CONVERSIONS"][$vs_output_format] = array(
								"MIMETYPE" => $vs_output_format,
								"FILENAME" => $this->_genMediaName($field)."_conv.".$vs_file_ext,
								"PROPERTIES" => array(
													"filesize" => filesize($filestem."_conv.".$vs_file_ext),
													"extension" => $vs_file_ext,
													"format_name" => $vs_format_name,
													"long_format_name" => $vs_long_format_name
												)
							);
						}
					}
				}

				$this->_FILES[$field] = $file_desc;
				$vs_sql =  "$field = ".$this->quote(caSerializeForDatabase($this->_FILES[$field], true)).",";
				$this->_FIELD_VALUES[$field] = $file_desc;
			}
		}
		return $vs_sql;
	}
	# --------------------------------------------------------------------------------
	# --- Utilities
	# --------------------------------------------------------------------------------
	/**
	 * Can be called in two ways:
	 *	1. Called with two arguments: returns $val quoted and escaped for use with $field.
	 *     That is, it will only quote $val if the field type requires it.
	 *  2. Called with one argument: simply returns $val quoted and escaped.
	 * 
	 * @access public
	 * @param string $field field name
	 * @param string $val optional field value
	 */
	public function &quote ($field, $val=null) {
		if (is_null($val)) {	# just quote it!
			$field = "'".$this->escapeForDatabase($field)."'";
			return $field;# quote only if field needs it
		} else {
			if ($this->_getFieldTypeType($field) == 1) {
				$val = "'".$this->escapeForDatabase($val)."'";
			}
			return $val;
		}
	}
	# --------------------------------------------------------------------------------
	/**
	 * Escapes a string for SQL use
	 * 
	 * @access public
	 * @param string $ps_value
	 * @return string
	 */
	public function escapeForDatabase ($ps_value) {
		$o_db = $this->getDb();

		return $o_db->escape($ps_value);
	}
	# --------------------------------------------------------------------------------
	/**
	 * Make copy of BaseModel object with all fields information intact *EXCEPT* for the
	 * primary key value and all media and file fields, all of which are empty.
	 * 
	 * @access public
	 * @return BaseModel the copy
	 */
	public function &cloneRecord() {
		$o_clone = $this;

		$o_clone->set($o_clone->getPrimaryKey(), null);
		foreach($o_clone->getFields() as $vs_f) {
			switch($o_clone->getFieldInfo($vs_f, "FIELD_TYPE")) {
				case FT_MEDIA:
					$o_clone->_FIELD_VALUES[$vs_f] = "";
					break;
				case FT_FILE:
					$o_clone->_FIELD_VALUES[$vs_f] = "";
					break;
			}
		}
		return $o_clone;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Clears all fields in object 
	 * 
	 * @access public
	 */
	public function clear () {
		$this->clearErrors();

		foreach($this->FIELDS as $field => $attr) {
			if (isset($this->FIELDS[$field]['START']) && ($vs_start_fld = $this->FIELDS[$field]['START'])) {
				unset($this->_FIELD_VALUES[$vs_start_fld]);
				unset($this->_FIELD_VALUES[$this->FIELDS[$field]['END']]);
			}
			unset($this->_FIELD_VALUES[$field]);
		}
	}
	# --------------------------------------------------------------------------------
	/**
	 * Prints contents of all fields in object
	 * 
	 * @access public
	 */ 
	public function dump () {
		$this->clearErrors();
		reset($this->FIELDS);
		while (list($field, $attr) = each($this->FIELDS)) {
			echo "$field = ".$this->_FIELD_VALUES[$field]."<BR>\n";
		}
	}
	# --------------------------------------------------------------------------------
	/**
	 * Returns true if field exists in this object
	 * 
	 * @access public
	 * @param string $field field name
	 * @return bool
	 */ 
	public function hasField ($field) {
		return (isset($this->FIELDS[$field]) && $this->FIELDS[$field]) ? 1 : 0;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Returns underlying datatype for given field type
	 * 0 = numeric, 1 = string
	 * 
	 * @access private
	 * @param string $fieldname
	 * @return int 
	 */
	public function _getFieldTypeType ($fieldname) {

		switch($this->FIELDS[$fieldname]["FIELD_TYPE"]) {
			case (FT_TEXT):
			case (FT_MEDIA):
			case (FT_FILE):
			case (FT_PASSWORD):
			case (FT_VARS):
				return 1;
				break;
			case (FT_NUMBER):
			case (FT_TIMESTAMP):
			case (FT_DATETIME):
			case (FT_TIME):
			case (FT_TIMERANGE):
			case (FT_HISTORIC_DATETIME):
			case (FT_DATE):
			case (FT_HISTORIC_DATE):
			case (FT_DATERANGE):
			case (FT_TIMECODE):
			case (FT_HISTORIC_DATERANGE):
			case (FT_BIT):
				return 0;
				break;
			default:
				print "Invalid field type in _getFieldTypeType: ". $this->FIELDS[$fieldname]["FIELD_TYPE"];
				exit;
		}
	}
	# --------------------------------------------------------------------------------
	/**
	 * Fetches the choice list value for a given field
	 * 
	 * @access public
	 * @param string $field field name
	 * @param string $value choice list name
	 * @return string
	 */
	public function getChoiceListValue($field, $value) {
		$va_attr = $this->getFieldInfo($field);
		$va_list = $va_attr["BOUNDS_CHOICE_LIST"];
		
		if (isset($va_attr['LIST']) && $va_attr['LIST']) {
			$t_list = new ca_lists();
			if ($t_list->load(array('list_code' => $va_attr['LIST']))) {
				$va_items = caExtractValuesByUserLocale($t_list->getItemsForList($va_attr['LIST']));
				$va_list = array();
				
				foreach($va_items as $vn_item_id => $va_item_info) {
					$va_list[$va_item_info['name_singular']] = $va_item_info['item_value'];
				}
			}
		}
		if ($va_list) {
			foreach ($va_list as $k => $v) {
				if ($v == $value) {
					return $k;
				}
			}
		} else {
			return;
		}
	}
	# --------------------------------------------------------------------------------
	# --- Field input verification
	# --------------------------------------------------------------------------------
	/**
	 * Does bounds checks specified for field $field on value $value.
	 * Returns 0 and throws an exception is it fails, returns 1 on success.
	 * 
	 * @access public
	 * @param string $field field name
	 * @param string $value value 
	 */
	public function verifyFieldValue ($field, $value) {
		$va_attr = $this->FIELDS[$field];
		if (!$va_attr) {
			$this->postError(716,_t("%1 does not exist", $field),"BaseModel->verifyFieldValue()");
			return false;
		}

		$data_type = $this->_getFieldTypeType($field);
		$field_type = $this->getFieldInfo($field,"FIELD_TYPE");

		if ((isset($va_attr["FILTER"]) && ($filter = $va_attr["FILTER"]))) {
			if (!preg_match($filter, $value)) {
				$this->postError(1102,_t("%1 is invalid", $va_attr["LABEL"]),"BaseModel->verifyFieldValue()");
				return false;
			}
		}

		if ($data_type == 0) {	# number; check value
			if (isset($va_attr["BOUNDS_VALUE"][0])) { $min_value = $va_attr["BOUNDS_VALUE"][0]; }
			if (isset($va_attr["BOUNDS_VALUE"][1])) { $max_value = $va_attr["BOUNDS_VALUE"][1];
			}
			if (!($va_attr["IS_NULL"] && (!$value))) {
				if ((isset($min_value)) && ($value < $min_value)) {
					$this->postError(1101,_t("%1 must not be less than %2", $va_attr["LABEL"], $min_value),"BaseModel->verifyFieldValue()");
					return false;
				}
				if ((isset($max_value)) && ($value > $max_value)) {
					$this->postError(1101,_t("%1 must not be greater than %2", $va_attr["LABEL"], $max_value),"BaseModel->verifyFieldValue()");
					return false;
				}
			}
		}

		if (!isset($va_attr["IS_NULL"])) { $va_attr["IS_NULL"] = 0; }
		if (!($va_attr["IS_NULL"] && (!$value))) {
			# check length
			if (isset($va_attr["BOUNDS_LENGTH"]) && is_array($va_attr["BOUNDS_LENGTH"])) {
				$min_length = $va_attr["BOUNDS_LENGTH"][0];
				$max_length = $va_attr["BOUNDS_LENGTH"][1];
			}

			if ((isset($min_length)) && (strlen($value) < $min_length)) {
				$this->postError(1102, _t("%1 must be at least %2 characters", $va_attr["LABEL"], $min_length),"BaseModel->verifyFieldValue()");
				return false;
			}


			if ((isset($max_length)) && (strlen($value) > $max_length)) {
				$this->postError(1102,_t("%1 must not be more than %2 characters long", $va_attr["LABEL"], $max_length),"BaseModel->verifyFieldValue()");
				return false;
			}

			$va_list = isset($va_attr["BOUNDS_CHOICE_LIST"]) ? $va_attr["BOUNDS_CHOICE_LIST"] : null;
			if (isset($va_attr['LIST']) && $va_attr['LIST']) {
				$t_list = new ca_lists();
				if ($t_list->load(array('list_code' => $va_attr['LIST']))) {
					$va_items = caExtractValuesByUserLocale($t_list->getItemsForList($va_attr['LIST']));
					$va_list = array();
					
					foreach($va_items as $vn_item_id => $va_item_info) {
						$va_list[$va_item_info['name_singular']] = $va_item_info['item_value'];
					}
				}
			}
			
			if ((in_array($data_type, array(FT_NUMBER, FT_TEXT))) && (isset($va_list)) && (is_array($va_list)) && (count($va_list)>0)) { # string; check choice list
				if (!is_array($value)) $value = explode(":",$value);
				if (!isset($va_attr['LIST_MULTIPLE_DELIMITER']) || !($vs_list_multiple_delimiter = $va_attr['LIST_MULTIPLE_DELIMITER'])) { $vs_list_multiple_delimiter = ';'; }
				foreach($value as $v) {
					if (!$v) continue;

					if ($va_attr['DISPLAY_TYPE'] == DT_LIST_MULTIPLE) {
						$va_tmp = explode($vs_list_multiple_delimiter, $v);
						foreach($va_tmp as $vs_mult_item) {
							if (!in_array($vs_mult_item,$va_list)) {
								$this->postError(1103,_t("%1 is not valid choice for %2", $v, $va_attr["LABEL"]),"BaseModel->verifyFieldValue()");
								return false;
							}
						}
					} else {
						if (!in_array($v,$va_list)) {
							$this->postError(1103, _t("%1 is not valid choice for %2", $v, $va_attr["LABEL"]),"BaseModel->verifyFieldValue()");
							return false;
						}
					}
				}
			}
		}

		return true;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Verifies values of each field and returns a hash keyed on field name with values set to
	 * and array of error messages for each field. Returns false (0) if no errors.
	 * 
	 * @access public
	 * @return array|bool 
	 */
	public function verifyForm() {
		$this->clearErrors();

		$errors = array();
		$errors_found = 0;
		$fields = $this->getFormFields();

		$err_halt = $this->error->getHaltOnError();
		$err_report = $this->error->getReportOnError();
		$this->error->setErrorOutput(0);
		while(list($field,$attr) = each($fields)) {
			$this->verifyFieldValue ($field, $this->get($field));
			if ($errnum = $this->error->getErrorNumber()) {
				$errors[$field][$errnum] = $this->error->getErrorDescription();
				$errors_found++;
			}
		}

		$this->error->setHaltOnError($err_halt);
		$this->error->setReportOnError($err_report);

		if ($errors_found) {
			return $errors;
		} else {
			return false;
		}
	}
	# --------------------------------------------------------------------------------------------
	# --- Field info
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns a hash with field names as keys and attributes hashes as values
	 * If $names_only is set, only the field names are returned in an indexed array (NOT a hash)
	 * Only returns fields that belong in public forms - it omits those fields with a display type of 7 ("PRIVATE")
	 * 
	 * @param bool $return_all
	 * @param bool $names_only
	 * @return array  
	 */
	public function getFormFields ($return_all = 0, $names_only = 0) {
		if (($return_all) && (!$names_only)) {
			return $this->FIELDS;
		}

		$form_fields = array();

		if (!$names_only) {
			foreach($this->FIELDS as $field => $attr) {
				if ($attr["DISPLAY_TYPE"] != DT_OMIT) {
					$form_fields[$field] = $attr;
				}
			}
		} else {
			foreach($this->FIELDS as $field => $attr) {
				if (($return_all) || ($attr["DISPLAY_TYPE"] != DT_OMIT)) {
					$form_fields[] = $field;
				}
			}
		}
		return $form_fields;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns (array) snapshot of the record represented by this BaseModel object
	 * 
	 * @access public
	 * @param bool $pb_changes_only optional, just return changed fields
	 * @return array 
	 */
	public function &getSnapshot($pb_changes_only=false) {
		$va_field_list = $this->getFormFields(true, true);
		$va_snapshot = array();

		foreach($va_field_list as $vs_field) {
			if (!$pb_changes_only || ($pb_changes_only && $this->changed($vs_field))) {
				$va_snapshot[$vs_field] = $this->get($vs_field);
			}
		}
		
		// We need to include the element_id when storing snapshots of ca_attributes and ca_attribute_values
		// whether is has changed or not (actually, it shouldn't really be changing after insert in normal use)
		// We need it available to assist in proper display of attributes in the change log
		if (in_array($this->tableName(), array('ca_attributes', 'ca_attribute_values'))) {
			$va_snapshot['element_id'] = $this->get('element_id');
			$va_snapshot['attribute_id'] = $this->get('attribute_id');
		}

		return $va_snapshot;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns attributes hash for specified field
	 * 
	 * @access public
	 * @param string $field field name
	 * @param string $attribute optional restriction to a single attribute
	 */
	public function getFieldInfo($field, $attribute = "") {
		if (isset($this->FIELDS[$field])) {
			$fieldinfo = $this->FIELDS[$field];

			if ($attribute) {
				return (isset($fieldinfo[$attribute])) ? $fieldinfo[$attribute] : "";
			} else {
				return $fieldinfo;
			}
		} else {
			$this->postError(710,_t("'%1' does not exist in this object", $field),"BaseModel->getFieldInfo()");
			return false;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	  * Returns display label for element specified by standard "get" bundle code (eg. <table_name>.<field_name> format)
	  */
	public function getDisplayLabel($ps_field) {
		$va_tmp = explode('.', $ps_field);
		if ($va_tmp[0] != $this->tableName()) { return null; }
		if ($this->hasField($va_tmp[1])) {
			return $this->getFieldInfo($va_tmp[1], 'LABEL');	
		}
		return null;
	}
	# --------------------------------------------------------------------------------------------
	/**
	  * Returns display description for element specified by standard "get" bundle code (eg. <table_name>.<bundle_name> format)
	  */
	public function getDisplayDescription($ps_field) {
		$va_tmp = explode('.', $ps_field);
		if ($va_tmp[0] != $this->tableName()) { return null; }
		if ($this->hasField($va_tmp[1])) {
			return $this->getFieldInfo($va_tmp[1], 'DESCRIPTION');	
		}
		return null;
	}
	# --------------------------------------------------------------------------------------------
	/**
	  * Returns HTML search form input widget for bundle specified by standard "get" bundle code (eg. <table_name>.<bundle_name> format)
	  * This method handles generation of search form widgets for intrinsic fields in the primary table. If this method can't handle 
	  * the bundle (because it is not an intrinsic field in the primary table...) it will return null.
	  *
	  * @param $po_request HTTPRequest
	  * @param $ps_field string
	  * @param $pa_options array
	  * @return string HTML text of form element. Will return null if it is not possible to generate an HTML form widget for the bundle.
	  * 
	  */
	public function htmlFormElementForSearch($po_request, $ps_field, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		if (isset($pa_options['width'])) {
			if ($va_dim = caParseFormElementDimension($pa_options['width'])) {
				if ($va_dim['type'] == 'pixels') {
					unset($pa_options['width']);
					$pa_options['maxPixelWidth'] = $va_dim['dimension'];
				}
			}
		}
		
		$va_tmp = explode('.', $ps_field);
		if ($va_tmp[0] != $this->tableName()) { return null; }
		if ($this->hasField($va_tmp[1])) {
			return $this->htmlFormElement($va_tmp[1], '^ELEMENT', array_merge($pa_options, array(
							'name' => $ps_field,
							'id' => str_replace(".", "_", $ps_field),
							'nullOption' => '-',
							'value' => (isset($pa_options['values'][$ps_field]) ? $pa_options['values'][$ps_field] : ''),
							'height' => (isset($pa_options['height']) && ($pa_options['height'] > 0)) ? $pa_options['height'] : 1, 
							'no_tooltips' => true
					)));
		}
		return null;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Return list of fields that had conflicts with existing data during last update()
	 * (ie. someone else had already saved to this field while the user of this instance was working)
	 * 
	 * @access public
	 */
	public function getFieldConflicts() {
		return $this->field_conflicts;
	}
	# --------------------------------------------------------------------------------------------
	# --- Change log
	# --------------------------------------------------------------------------------------------
	/**
	 * Log a change
	 * 
	 * @access private
	 * @param string $ps_change_type 'I', 'U' or 'D', meaning INSERT, UPDATE or DELETE
	 * @param int $pn_user_id user identifier, defaults to null
	 */
	private function logChange($ps_change_type, $pn_user_id=null) {
		$vb_is_metadata = $vb_is_metadata_value = false;
		if ($this->tableName() == 'ca_attributes') {
			$vb_log_changes_to_self = false;
			$va_subject_config = null;
			$vb_is_metadata = true;
		} elseif($this->tableName() == 'ca_attribute_values') {
			$vb_log_changes_to_self = false;
			$va_subject_config = null;
			$vb_is_metadata_value = true;
		} else {
			$vb_log_changes_to_self = 	$this->getProperty('LOG_CHANGES_TO_SELF');
			$va_subject_config = 		$this->getProperty('LOG_CHANGES_USING_AS_SUBJECT');
		}

		global $AUTH_CURRENT_USER_ID;
		if (!$pn_user_id) { $pn_user_id = $AUTH_CURRENT_USER_ID; }
		if (!$pn_user_id) { $pn_user_id = null; }

		if (!in_array($ps_change_type, array('I', 'U', 'D'))) { return false; };		// invalid change type (shouldn't happen)

		if (!($vn_row_id = $this->getPrimaryKey())) { return false; }					// no logging without primary key value

		// get unit id (if set)
		global $g_change_log_unit_id;
		$vn_unit_id = $g_change_log_unit_id;
		if (!$vn_unit_id) { $vn_unit_id = null; }

		// get subject ids
		$va_subjects = array();
		if ($vb_is_metadata) {
			// special case for logging attribute changes
			if (($vn_id = $this->get('row_id')) > 0) {
				$va_subjects[$this->get('table_num')][] = $vn_id;
			}
		} elseif ($vb_is_metadata_value) {
			// special case for logging metadata changes
			$t_attr = new ca_attributes($this->get('attribute_id'));
			if (($vn_id = $t_attr->get('row_id')) > 0) {
				$va_subjects[$t_attr->get('table_num')][] = $vn_id;
			}
		} else {
			if (is_array($va_subject_config)) {
				if(is_array($va_subject_config['FOREIGN_KEYS'])) {
					foreach($va_subject_config['FOREIGN_KEYS'] as $vs_field) {
						$va_relationships = $this->_DATAMODEL->getManyToOneRelations($this->tableName(), $vs_field);
						if ($va_relationships['one_table']) {
							$vn_table_num = $this->_DATAMODEL->getTableNum($va_relationships['one_table']);
							if (!isset($va_subjects[$vn_table_num]) || !is_array($va_subjects[$vn_table_num])) { $va_subjects[$vn_table_num] = array(); }
							
							if (($vn_id = $this->get($vs_field)) > 0) {
								$va_subjects[$vn_table_num][] = $vn_id;
							}
						}
					}
				}
				if(is_array($va_subject_config['RELATED_TABLES'])) {
					if (!isset($o_db) || !$o_db) {
						$o_db = new Db();
						$o_db->dieOnError(false);
					}
					
					foreach($va_subject_config['RELATED_TABLES'] as $vs_dest_table => $va_path_to_dest) {

						$t_dest = $this->_DATAMODEL->getTableInstance($vs_dest_table);
						if (!$t_dest) { continue; }

						$vn_dest_table_num = $t_dest->tableNum();
						$vs_dest_primary_key = $t_dest->primaryKey();

						$va_path_to_dest[] = $vs_dest_table;

						$vs_cur_table = $this->tableName();

						$vs_sql = "SELECT ".$vs_dest_table.".".$vs_dest_primary_key." FROM ".$this->tableName()."\n";
						foreach($va_path_to_dest as $vs_ltable) {
							$va_relations = $this->_DATAMODEL->getRelationships($vs_cur_table, $vs_ltable);

							$vs_sql .= "INNER JOIN $vs_ltable ON $vs_cur_table.".$va_relations[$vs_cur_table][$vs_ltable][0][0]." = $vs_ltable.".$va_relations[$vs_cur_table][$vs_ltable][0][1]."\n";
							$vs_cur_table = $vs_ltable;
						}
						$vs_sql .= "WHERE ".$this->tableName().".".$this->primaryKey()." = ".$this->getPrimaryKey();

						if ($qr_subjects = $o_db->query($vs_sql)) {
							if (!isset($va_subjects[$vn_dest_table_num]) || !is_array($va_subjects[$vn_dest_table_num])) { $va_subjects[$vn_dest_table_num] = array(); }
							while($qr_subjects->nextRow()) {
								if (($vn_id = $qr_subjects->get($vs_dest_primary_key)) > 0) {
									$va_subjects[$vn_dest_table_num][] = $vn_id;
								}
							}
						} else {
							print "<hr>Error in subject logging: ";
							print "<br>$vs_sql<hr>\n";
						}
					}
				}
			}
		}

		if (!sizeof($va_subjects) && !$vb_log_changes_to_self) { return true; }

		if (!$this->opqs_change_log) {
			$o_db = $this->getDb();
			$o_db->dieOnError(false);

			$vs_change_log_database = '';
			if ($vs_change_log_database = $this->_CONFIG->get("change_log_database")) {
				$vs_change_log_database .= ".";
			}
			if (!($this->opqs_change_log = $o_db->prepare("
				INSERT INTO ".$vs_change_log_database."ca_change_log
				(
					log_datetime, user_id, unit_id, changetype,
					logged_table_num, logged_row_id, snapshot
				)
				VALUES
				(?, ?, ?, ?, ?, ?, ?)
			"))) {
				// prepare failed - shouldn't happen
				return false;
			}
			if (!($this->opqs_change_log_subjects = $o_db->prepare("
				INSERT INTO ".$vs_change_log_database."ca_change_log_subjects
				(
					log_id, subject_table_num, subject_row_id
				)
				VALUES
				(?, ?, ?)
			"))) {
				// prepare failed - shouldn't happen
				return false;
			}
		}

		// get snapshot of changes made to record
		$va_snapshot = $this->getSnapshot(($ps_change_type === 'U') ? true : false);

		$vs_snapshot = caSerializeForDatabase($va_snapshot, true);
		//if ($this->_CONFIG->get('compress_change_log')) {
		//	$vs_snapshot = gzcompress($vs_snapshot);
		//}


		if (!(($ps_change_type == 'U') && (!sizeof($va_snapshot)))) {
			// Create primary log entry
			$this->opqs_change_log->execute(
				time(), $pn_user_id, $vn_unit_id, $ps_change_type,
				$this->tableNum(), $vn_row_id, $vs_snapshot
			);

			$vn_log_id = $this->opqs_change_log->getLastInsertID();
			foreach($va_subjects as $vn_subject_table_num => $va_subject_ids) {
				foreach($va_subject_ids as $vn_subject_row_id) {
					$this->opqs_change_log_subjects->execute($vn_log_id, $vn_subject_table_num, $vn_subject_row_id);
				}
			}
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Get change logs for the current record represented by this BaseModel object
	 * 
	 * @access public
	 * @param int $pn_less_than_secs_ago optional, restrict to a timespan, e.g. 3600 for the last hour
	 * @param int $pn_max_num_entries_returned optional, maximal number of entries returned [default=all]
	 * @param int $pn_id optional, get change logs for a different record [default=null]
	 * @param bool $pb_for_table, only return changes made directly to the current table (ie. omit changes to related records that impact this record [default=false]
	 * @param string $ps_exclude_unit_id, if set, log records with the specific unit_id are not returned
	 */
	public function &getChangeLog($pn_less_than_secs_ago=null, $pn_max_num_entries_returned=null, $pn_id=null, $pb_for_table=false, $ps_exclude_unit_id=null) {
		if (!$pn_id) {
			if (!($pn_id = $this->getPrimaryKey())) {
				return array();
			}
		}

		if (!$this->opqs_get_change_log) {
			$vs_change_log_database = '';
			if ($vs_change_log_database = $this->_CONFIG->get("change_log_database")) {
				$vs_change_log_database .= ".";
			}

			$o_db = $this->getDb();
			
			if ($pb_for_table) {
				if (!($this->opqs_get_change_log = $o_db->prepare("
					SELECT DISTINCT
						wcl.log_id, wcl.log_datetime log_datetime, wcl.user_id, wcl.changetype, wcl.logged_table_num, wcl.logged_row_id,
						wcl.snapshot, wcl.unit_id, wu.email, wu.fname, wu.lname
					FROM ca_change_log wcl
					LEFT JOIN ca_change_log_subjects AS wcls ON wcl.log_id = wcls.log_id
					LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id
					WHERE
						(
							(wcl.logged_table_num = ".$this->tableNum().") AND
							(wcl.logged_row_id = ?)
						)
						AND
						(wcl.log_datetime > ?)
					ORDER BY log_datetime
				"))) {
					# should not happen
					return false;
				}
			} else {
			
				if (!($this->opqs_get_change_log = $o_db->prepare("
					SELECT DISTINCT
						wcl.log_id, wcl.log_datetime log_datetime, wcl.user_id, wcl.changetype, wcl.logged_table_num, wcl.logged_row_id,
						wcl.snapshot, wcl.unit_id, wu.email, wu.fname, wu.lname
					FROM ca_change_log wcl
					LEFT JOIN ca_change_log_subjects AS wcls ON wcl.log_id = wcls.log_id
					LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id
					WHERE
						(
							(wcl.logged_table_num = ".$this->tableNum().") AND
							(wcl.logged_row_id = ?)
						)
						AND
						(wcl.log_datetime > ?)
					UNION
					SELECT DISTINCT
						wcl.log_id, wcl.log_datetime, wcl.user_id, wcl.changetype, wcl.logged_table_num, wcl.logged_row_id,
						wcl.snapshot, wcl.unit_id, wu.email, wu.fname, wu.lname
					FROM ca_change_log wcl
					LEFT JOIN ca_change_log_subjects AS wcls ON wcl.log_id = wcls.log_id
					LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id
					WHERE
						 (
							(wcls.subject_table_num = ".$this->tableNum().") AND
							(wcls.subject_row_id = ?)
						)
						AND
						(wcl.log_datetime > ?)
					ORDER BY log_datetime
				"))) {
					# should not happen
					return false;
				}
			}
			if ($pn_max_num_entries_returned > 0) {
				$this->opqs_get_change_log->setLimit($pn_max_num_entries_returned);
			}
		}

		// get directly logged records
		$va_log = array();
		
		if ($pb_for_table) {
			$qr_log = $this->opqs_get_change_log->execute(intval($pn_id), intval($pn_less_than_secs_ago));
		} else {
			$qr_log = $this->opqs_get_change_log->execute(intval($pn_id), intval($pn_less_than_secs_ago), intval($pn_id), intval($pn_less_than_secs_ago));
		}
		
		while($qr_log->nextRow()) {
			if ($ps_exclude_unit_id && ($ps_exclude_unit_id == $qr_log->get('unit_id'))) { continue; }
			$va_log[] = $qr_log->getRow();
			$va_log[sizeof($va_log)-1]['snapshot'] = caUnserializeForDatabase($va_log[sizeof($va_log)-1]['snapshot']);
		}

		return $va_log;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Get list of users (containing username, user id, forename, last name and email adress) who changed something
	 * in a) this record (if the parameter is omitted) or b) the whole table (if the parameter is set).
	 * 
	 * @access public
	 * @param bool $pb_for_table
	 * @return array
	 */
	public function getChangeLogUsers($pb_for_table=false) {
		$o_db = $this->getDb();
		if ($pb_for_table) {
			$qr_users = $o_db->query("
				SELECT DISTINCT wu.user_id, wu.user_name, wu.fname, wu.lname, wu.email
				FROM ca_users wu
				INNER JOIN ca_change_log AS wcl ON wcl.user_id = wu.user_id
				LEFT JOIN ca_change_log_subjects AS wcls ON wcl.log_id = wcls.log_id
				WHERE
					((wcls.subject_table_num = ?) OR (wcl.logged_table_num = ?))
				ORDER BY wu.lname, wu.fname
			", $this->tableNum(), $this->tableNum());
		} else {
			$qr_users = $o_db->query("
				SELECT DISTINCT wu.user_id, wu.user_name, wu.fname, wu.lname,  wu.email
				FROM ca_users wu
				INNER JOIN ca_change_log AS wcl ON wcl.user_id = wu.user_id
				LEFT JOIN ca_change_log_subjects AS wcls ON wcl.log_id = wcls.log_id
				WHERE
					((wcls.subject_table_num = ?) OR (wcl.logged_table_num = ?))
					AND
					((wcl.logged_row_id = ?) OR (wcls.subject_row_id = ?))
				ORDER BY wu.lname, wu.fname
			", $this->tableNum(), $this->tableNum(), $this->getPrimaryKey(), $this->getPrimaryKey());
		}
		$va_users = array();
		while($qr_users->nextRow()) {
			$vs_user_name = $qr_users->get('user_name');

			$va_users[$vs_user_name] = array(
				'user_name' => $vs_user_name,
				'user_id' => $qr_users->get('user_id'),
				'fname' => $qr_users->get('fname'),
				'lname' => $qr_users->get('lname'),
				'email' => $qr_users->get('email')
			);
		}

		return $va_users;
	}
	# --------------------------------------------------------------------------------------------
	public function getCreationTimestamp() {
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
				SELECT wcl.log_datetime, wu.user_id, wu.fname, wu.lname, wu.email
				FROM ca_change_log wcl
				LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id
				WHERE
					(wcl.logged_table_num = ?) AND (wcl.logged_row_id = ?) AND(wcl.changetype = 'I')",
		$this->tableNum(), $vn_row_id);
		if ($qr_res->nextRow()) {
			return array(
				'user_id' => $qr_res->get('user_id'),
				'fname' => $qr_res->get('fname'),
				'lname' => $qr_res->get('lname'),
				'email' => $qr_res->get('email'),
				'timestamp' => $qr_res->get('log_datetime')
			);
		}
		
		return null;
	}
	# --------------------------------------------------------------------------------------------
	public function getLastChangeTimestamp() {
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
				SELECT wcl.log_datetime, wu.user_id, wu.fname, wu.lname, wu.email
				FROM ca_change_log wcl
				LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id
				INNER JOIN ca_change_log_subjects AS wcls ON wcl.log_id = wcls.log_id
				WHERE
					(wcls.subject_table_num = ?)
					AND
					(wcls.subject_row_id = ?)
					AND
					(wcl.changetype IN ('I', 'U'))
				ORDER BY wcl.log_datetime DESC LIMIT 1",
		$this->tableNum(), $vn_row_id);
		
		$vn_last_change_timestamp = 0;
		$va_last_change_info = null;
		if ($qr_res->nextRow()) {
			$vn_last_change_timestamp = $qr_res->get('log_datetime');
			$va_last_change_info = array(
				'user_id' => $qr_res->get('user_id'),
				'fname' => $qr_res->get('fname'),
				'lname' => $qr_res->get('lname'),
				'email' => $qr_res->get('email'),
				'timestamp' => $qr_res->get('log_datetime')
			);
		}
		
		$qr_res = $o_db->query("
				SELECT wcl.log_datetime, wu.user_id, wu.fname, wu.lname, wu.email
				FROM ca_change_log wcl 
				LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id
				WHERE
					(wcl.logged_table_num = ?)
					AND
					(wcl.logged_row_id = ?)
					AND
					(wcl.changetype IN ('I', 'U'))
				ORDER BY wcl.log_datetime DESC LIMIT 1",
		$this->tableNum(), $vn_row_id);
		
		if ($qr_res->nextRow()) {
			if ($qr_res->get('log_datetime') > $vn_last_change_timestamp) {
				$vn_last_change_timestamp = $qr_res->get('log_datetime');
				$va_last_change_info = array(
					'user_id' => $qr_res->get('user_id'),
					'fname' => $qr_res->get('fname'),
					'lname' => $qr_res->get('lname'),
					'email' => $qr_res->get('email'),
					'timestamp' => $qr_res->get('log_datetime')
				);
			}
		}
		
		if ($vn_last_change_timestamp > 0) {
			return $va_last_change_info;
		}
		return null;
	}
	# --------------------------------------------------------------------------------------------
	# --- Hierarchical functions
	# --------------------------------------------------------------------------------------------
	/**
	 * Are we dealing with a hierarchical structure in this table?
	 * 
	 * @access public
	 * @return bool
	 */
	public function isHierarchical() {
		return (!is_null($this->getProperty("HIERARCHY_TYPE"))) ? true : false;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * What type of hierarchical structure is used by this table?
	 * 
	 * @access public
	 * @return int (__CA_HIER_*__ constant)
	 */
	public function getHierarchyType() {
		return $this->getProperty("HIERARCHY_TYPE");
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Fetches primary key of the hierarchy root.
	 * DOES NOT CREATE ROOT - YOU HAVE TO DO THAT YOURSELF (this differs from previous versions of these libraries).
	 * 
	 * @param int $pn_hierarchy_id optional, points to record in related table containing hierarchy description
	 * @return int root id
	 */
	public function getHierarchyRootID($pn_hierarchy_id=null) {
		$vn_root_id = null;
		
		$o_db = $this->getDb();
		switch($this->getHierarchyType()) {
			# ------------------------------------------------------------------
			case __CA_HIER_TYPE_SIMPLE_MONO__:
				// For simple "table is one big hierarchy" setups all you need
				// to do is look for the row where parent_id is NULL
				$qr_res = $o_db->query("
					SELECT ".$this->primaryKey()." 
					FROM ".$this->tableName()." 
					WHERE 
						(".$this->getProperty('HIERARCHY_PARENT_ID_FLD')." IS NULL)
				");
				if ($qr_res->nextRow()) {
					$vn_root_id = $qr_res->get($this->primaryKey());
				}
				break;
			# ------------------------------------------------------------------
			case __CA_HIER_TYPE_MULTI_MONO__:
				// For tables that house multiple hierarchies defined in a second table
				// you need to look for the row where parent_id IS NULL and hierarchy_id = the value
				// passed in $pn_hierarchy_id
				
				if (!$pn_hierarchy_id) {	// if hierarchy_id is not explicitly set use the value in the currently loaded row
					$pn_hierarchy_id = $this->get($this->getProperty('HIERARCHY_PARENT_ID_FLD'));
				}
				$qr_res = $o_db->query("
					SELECT ".$this->primaryKey()." 
					FROM ".$this->tableName()." 
					WHERE 
						(".$this->getProperty('HIERARCHY_PARENT_ID_FLD')." IS NULL)
						AND
						(".$this->getProperty('HIERARCHY_ID_FLD')." = ?)
				", (int)$pn_hierarchy_id);
				if ($qr_res->nextRow()) {
					$vn_root_id = $qr_res->get($this->primaryKey());
				}
				break;
			# ------------------------------------------------------------------
			case __CA_HIER_TYPE_ADHOC_MONO__:
				// For ad-hoc hierarchies you just return the hierarchy_id value
				if (!$pn_hierarchy_id) {	// if hierarchy_id is not explicitly set use the value in the currently loaded row
					$pn_hierarchy_id = $this->get($this->getProperty('HIERARCHY_ID_FLD'));
				}
				$vn_root_id = $pn_hierarchy_id;
				break;
			# ------------------------------------------------------------------
			case __CA_HIER_TYPE_MULTI_POLY__:
				// TODO: implement this
				
				break;
			# ------------------------------------------------------------------
			default:
				die("Invalid hierarchy type: ".$this->getHierarchyType());
				break;
			# ------------------------------------------------------------------
		}
		
		return $vn_root_id;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Fetch a DbResult representation of the whole hierarchy
	 * 
	 * @access public
	 * @param int $pn_id optional, id of record to be treated as root
	 * @param string $ps_additional_table_to_join optional, name of additional table to join into and return with hierarchical query
	 * @return DbResult
	 */
	public function &getHierarchy($pn_id=null, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		if ($this->isHierarchical()) {
			$vs_hier_left_fld 		= $this->getProperty("HIERARCHY_LEFT_INDEX_FLD");
			$vs_hier_right_fld 		= $this->getProperty("HIERARCHY_RIGHT_INDEX_FLD");
			$vs_hier_parent_id_fld	= $this->getProperty("HIERARCHY_PARENT_ID_FLD");
			$vs_hier_id_fld 		= $this->getProperty("HIERARCHY_ID_FLD");
			$vs_hier_id_table 		= $this->getProperty("HIERARCHY_DEFINITION_TABLE");
			
			if (!$pn_id) {
				if (!($pn_id = $this->getHierarchyRootID($this->get($vs_hier_id_fld)))) {
					return null;
				}
			}
			$vn_hierarchy_id = $this->get($vs_hier_id_fld);
			
			$vs_hier_id_sql = "";
			if ($vn_hierarchy_id) {
				// TODO: verify hierarchy_id exists
				$vs_hier_id_sql = " AND (".$vs_hier_id_fld." = ".$vn_hierarchy_id.")";
			}
			
			$o_db = $this->getDb();
			$qr_root = $o_db->query("
				SELECT $vs_hier_left_fld, $vs_hier_right_fld ".(($this->hasField($vs_hier_id_fld)) ? ", $vs_hier_id_fld" : "")."
				FROM ".$this->tableName()."
				WHERE
					(".$this->primaryKey()." = ?)		
			", intval($pn_id));
			if ($o_db->numErrors()) {
				$this->errors = array_merge($this->errors, $o_db->errors());
				return null;
			} else {
				if ($qr_root->nextRow()) {
					
					$va_count = array();
					if (($this->hasField($vs_hier_id_fld)) && (!($vn_hierarchy_id = $this->get($vs_hier_id_fld))) && (!($vn_hierarchy_id = $qr_root->get($vs_hier_id_fld)))) {
						$this->postError(2030, _t("Hierarchy ID must be specified"), "Table->getHierarchy()");
						return false;
					}
					
					$vs_table_name = $this->tableName();
					
					$vs_hier_id_sql = "";
					if ($vn_hierarchy_id) {
						$vs_hier_id_sql = " AND ({$vs_table_name}.{$vs_hier_id_fld} = {$vn_hierarchy_id})";
					}
					
					$va_sql_joins = array();
					if (isset($pa_options['additionalTableToJoin']) && ($pa_options['additionalTableToJoin'])){ 
						$ps_additional_table_to_join = $pa_options['additionalTableToJoin'];
						
						// what kind of join are we doing for the additional table? LEFT or INNER? (default=INNER)
						$ps_additional_table_join_type = 'INNER';
						if (isset($pa_options['additionalTableJoinType']) && ($pa_options['additionalTableJoinType'] === 'LEFT')) {
							$ps_additional_table_join_type = 'LEFT';
						}
						if (is_array($va_rel = $this->getAppDatamodel()->getOneToManyRelations($this->tableName(), $ps_additional_table_to_join))) {
							// one-many rel
							$va_sql_joins[] = "{$ps_additional_table_join_type} JOIN {$ps_additional_table_to_join} ON ".$this->tableName().'.'.$va_rel['one_table_field']." = {$ps_additional_table_to_join}.".$va_rel['many_table_field'];
						} else {
							// TODO: handle many-many cases
						}
						
						// are there any SQL WHERE criteria for the additional table?
						$va_additional_table_wheres = null;
						if (isset($pa_options['additionalTableWheres']) && is_array($pa_options['additionalTableWheres'])) {
							$va_additional_table_wheres = $pa_options['additionalTableWheres'];
						}
						$vs_additional_wheres = '';
						if (is_array($va_additional_table_wheres) && (sizeof($va_additional_table_wheres) > 0)) {
							$vs_additional_wheres = ' AND ('.join(' AND ', $va_additional_table_wheres).') ';
						}
					}
					$vs_sql_joins = join("\n", $va_sql_joins);
					
					$vs_sql = "
						SELECT * 
						FROM {$vs_table_name}
						{$vs_sql_joins}
						WHERE
							({$vs_table_name}.{$vs_hier_left_fld} BETWEEN ".$qr_root->get($vs_hier_left_fld)." AND ".$qr_root->get($vs_hier_right_fld).")
							{$vs_hier_id_sql}
							{$vs_additional_wheres}
						ORDER BY
							{$vs_table_name}.{$vs_hier_left_fld}
					";
					//print $vs_sql;
					$qr_hier = $o_db->query($vs_sql);
					
					if ($o_db->numErrors()) {
						$this->errors = array_merge($this->errors, $o_db->errors());
						return null;
					} else {
						return $qr_hier;
					}
				} else {
					return null;
				}
			}
		} else {
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Get the hierarchy in list form
	 * 
	 * @param int $pn_id 
	 * @param string $ps_additional_table_to_join optional, name of additional table to join into and return with hierarchical query
	 * @param int $pn_max_levels options, if set specified the maximum number of levels of the hierarchy to return
	 * 
	 * @return array
	 */
	public function &getHierarchyAsList($pn_id=null, $pa_options=null) {
		$pb_ids_only = (isset($pa_options['idsOnly']) && $pa_options['idsOnly']) ? true : false;
		$pn_max_levels = isset($pa_options['maxLevels']) ? intval($pa_options['maxLevels']) : null;
		$ps_additional_table_to_join = isset($pa_options['additionalTableToJoin']) ? $pa_options['additionalTableToJoin'] : null;
		$pb_dont_include_root = (isset($pa_options['dontIncludeRoot']) && $pa_options['dontIncludeRoot']) ? true : false;
		
		
		if ($qr_hier = $this->getHierarchy($pn_id, $pa_options)) {
			$vs_hier_right_fld 			= $this->getProperty("HIERARCHY_RIGHT_INDEX_FLD");
			
			$va_indent_stack = array();
			$va_hier = array();
			
			$vn_cur_level = -1;
			$va_omit_stack = array();
			
			$vn_root_id = $pn_id;
			while($qr_hier->nextRow()) {
				$vn_row_id = $qr_hier->get($this->primaryKey());
				if (is_null($vn_root_id)) { $vn_root_id = $vn_row_id; }
				
				if ($pb_dont_include_root && ($vn_row_id == $vn_root_id)) { continue; } // skip root if desired
				
				$vn_r = $qr_hier->get($vs_hier_right_fld);
				$vn_c = sizeof($va_indent_stack);
				
				if($vn_c > 0) {
					while (($vn_c) && ($va_indent_stack[$vn_c - 1] <= $vn_r)){
						array_pop($va_indent_stack);
						$vn_c = sizeof($va_indent_stack);
					}
				}
				
				if($vn_cur_level != sizeof($va_indent_stack)) {
					if ($vn_cur_level > sizeof($va_indent_stack)) {
						$va_omit_stack = array();
					}
					$vn_cur_level = intval(sizeof($va_indent_stack));
				}
				
				if (is_null($pn_max_levels) || ($vn_cur_level < $pn_max_levels)) {
					$va_field_values = $qr_hier->getRow();
					foreach($va_field_values as $vs_key => $vs_val) {
						$va_field_values[$vs_key] = stripSlashes($vs_val);
					}
					if ($pb_ids_only) {					
						$va_hier[] = $vn_row_id;
					} else {
						$va_node = array(
							"NODE" => $va_field_values,
							"LEVEL" => $vn_cur_level
						);					
						$va_hier[] = $va_node;
					}

				}
				$va_indent_stack[] = $vn_r;
			}		
			return $va_hier;
		} else {
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns a list of primary keys comprising all child rows
	 * 
	 * @param int $pn_id node to start from - default is the hierarchy root
	 * @return array id list
	 */
	public function &getHierarchyIDs($pn_id=null) {
		if ($qr_hier = $this->getHierarchy($pn_id)) {
			$va_ids = array();
			$vs_pk = $this->primaryKey();
			while($qr_hier->nextRow()) {
				$va_ids[] = $qr_hier->get($vs_pk);
			}
			
			return $va_ids;
		} else {
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Get *direct* child records for currently loaded record or one specified by $pn_id
	 * Note that this only returns direct children, *NOT* children of children and further descendents
	 * If you need to get a chunk of the hierarchy use getHierarchy()
	 * 
	 * @access public
	 * @param int optional, primary key value of a record.
	 * 	Use this if you want to know the children of a record different than $this
	 * @param array, optional associative array of options. Valid keys for the array are:
	 *		additionalTableToJoin: name of table to join to hierarchical table (and return fields from); only fields related many-to-one are currently supported
	 *		returnChildCounts: if true, the number of children under each returned child is calculated and returned in the result set under the column name 'child_count'. Note that this count is always at least 1, even if there are no children. The 'has_children' column will be null if the row has, in fact no children, or non-null if it does have children. You should check 'has_children' before using 'child_count' and disregard 'child_count' if 'has_children' is null.
	 * @return DbResult 
	 */
	public function &getHierarchyChildrenAsQuery($pn_id=null, $pa_options=null) {
		$o_db = $this->getDb();
			
		// return counts of child records for each child found?
		$pb_return_child_counts = isset($pa_options['returnChildCounts']) ? true : false;
		
		$va_additional_table_wheres = array();
		$va_additional_table_select_fields = array();
			
		// additional table to join into query?
		$ps_additional_table_to_join = isset($pa_options['additionalTableToJoin']) ? $pa_options['additionalTableToJoin'] : null;
		if ($ps_additional_table_to_join) {		
			// what kind of join are we doing for the additional table? LEFT or INNER? (default=INNER)
			$ps_additional_table_join_type = 'INNER';
			if (isset($pa_options['additionalTableJoinType']) && ($pa_options['additionalTableJoinType'] === 'LEFT')) {
				$ps_additional_table_join_type = 'LEFT';
			}
			
			// what fields from the additional table are we going to return?
			if (isset($pa_options['additionalTableSelectFields']) && is_array($pa_options['additionalTableSelectFields'])) {
				foreach($pa_options['additionalTableSelectFields'] as $vs_fld) {
					$va_additional_table_select_fields[] = "{$ps_additional_table_to_join}.{$vs_fld}";
				}
			}
			
			// are there any SQL WHERE criteria for the additional table?
			if (isset($pa_options['additionalTableWheres']) && is_array($pa_options['additionalTableWheres'])) {
				$va_additional_table_wheres = $pa_options['additionalTableWheres'];
			}
		}
			
		if ($this->isHierarchical()) {
			if (!$pn_id) {
				if (!($pn_id = $this->getPrimaryKey())) {
					return null;
				}
			}
					
			$va_sql_joins = array();
			$vs_additional_table_to_join_group_by = '';
			if ($ps_additional_table_to_join){ 
				if (is_array($va_rel = $this->getAppDatamodel()->getOneToManyRelations($this->tableName(), $ps_additional_table_to_join))) {
					// one-many rel
					$va_sql_joins[] = $ps_additional_table_join_type." JOIN {$ps_additional_table_to_join} ON ".$this->tableName().'.'.$va_rel['one_table_field']." = {$ps_additional_table_to_join}.".$va_rel['many_table_field'];
				} else {
					// TODO: handle many-many cases
				}
				
				$t_additional_table_to_join = $this->_DATAMODEL->getTableInstance($ps_additional_table_to_join);
				$vs_additional_table_to_join_group_by = ', '.$ps_additional_table_to_join.'.'.$t_additional_table_to_join->primaryKey();
			}
			$vs_sql_joins = join("\n", $va_sql_joins);
			
			$vs_hier_parent_id_fld = $this->getProperty("HIERARCHY_PARENT_ID_FLD");
			
			if ($vs_rank_fld = $this->getProperty('RANK')) { 
				$vs_order_by = $this->tableName().'.'.$vs_rank_fld;
			} else {
				$vs_order_by = $this->tableName().".".$this->primaryKey();
			}
			
			if ($pb_return_child_counts) {
				$qr_hier = $o_db->query("
					SELECT ".$this->tableName().".* ".(sizeof($va_additional_table_select_fields) ? ', '.join(', ', $va_additional_table_select_fields) : '').", count(*) child_count, p2.".$this->primaryKey()." has_children
					FROM ".$this->tableName()."
					{$vs_sql_joins}
					LEFT JOIN ".$this->tableName()." AS p2 ON p2.".$vs_hier_parent_id_fld." = ".$this->tableName().".".$this->primaryKey()."
					WHERE
						(".$this->tableName().".{$vs_hier_parent_id_fld} = ?) ".((sizeof($va_additional_table_wheres) > 0) ? ' AND '.join(' AND ', $va_additional_table_wheres) : '')."
					GROUP BY
						".$this->tableName().".".$this->primaryKey()." {$vs_additional_table_to_join_group_by}
					ORDER BY
						".$vs_order_by."
				", $pn_id);
			} else {
				$qr_hier = $o_db->query("
					SELECT ".$this->tableName().".* ".(sizeof($va_additional_table_select_fields) ? ', '.join(', ', $va_additional_table_select_fields) : '')."
					FROM ".$this->tableName()."
					{$vs_sql_joins}
					WHERE
						(".$this->tableName().".{$vs_hier_parent_id_fld} = ?) ".((sizeof($va_additional_table_wheres) > 0) ? ' AND '.join(' AND ', $va_additional_table_wheres) : '')."
					ORDER BY
						".$vs_order_by."
				", $pn_id);
			}
			if ($o_db->numErrors()) {
				$this->errors = array_merge($this->errors, $o_db->errors());
				return null;
			} else {
				return $qr_hier;
			}
		} else {
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Get *direct* child records for currently loaded record or one specified by $pn_id
	 * Note that this only returns direct children, *NOT* children of children and further descendents
	 * If you need to get a chunk of the hierarchy use getHierarchy().
	 *
	 * Results are returned as an array with either associative array values for each child record, or if the
	 * idsOnly option is set, then the primary key values.
	 * 
	 * @access public
	 * @param int optional, primary key value of a record.
	 * 	Use this if you want to know the children of a record different than $this
	 * @param array, optional associative array of options. Valid keys for the array are:
	 *		additionalTableToJoin: name of table to join to hierarchical table (and return fields from); only fields related many-to-one are currently supported
	 *		returnChildCounts: if true, the number of children under each returned child is calculated and returned in the result set under the column name 'child_count'. Note that this count is always at least 1, even if there are no children. The 'has_children' column will be null if the row has, in fact no children, or non-null if it does have children. You should check 'has_children' before using 'child_count' and disregard 'child_count' if 'has_children' is null.
	 *		idsOnly: if true, only the primary key id values of the chidlren records are returned
	 * @return array 
	 */
	public function getHierarchyChildren($pn_id=null, $pa_options=null) {
		$pb_ids_only = (isset($pa_options['idsOnly']) && $pa_options['idsOnly']) ? true : false;
		
		if (!$pn_id) { $pn_id = $this->getPrimaryKey(); }
		if (!$pn_id) { return null; }
		$qr_children = $this->getHierarchyChildrenAsQuery($pn_id, $pa_options);
		
		
		$va_children = array();
		$vs_pk = $this->primaryKey();
		while($qr_children->nextRow()) {
			if ($pb_ids_only) {
				$va_row = $qr_children->getRow();
				$va_children[] = $va_row[$vs_pk];
			} else {
				$va_children[] = $qr_children->getRow();
			}
		}
		
		return $va_children;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Get "siblings" records - records with the same parent - as the currently loaded record
	 * or the record with its primary key = $pn_id
	 *
	 * Results are returned as an array with either associative array values for each sibling record, or if the
	 * idsOnly option is set, then the primary key values.
	 * 
	 * @access public
	 * @param int optional, primary key value of a record.
	 * 	Use this if you want to know the siblings of a record different than $this
	 * @param array, optional associative array of options. Valid keys for the array are:
	 *		additionalTableToJoin: name of table to join to hierarchical table (and return fields from); only fields related many-to-one are currently supported
	 *		returnChildCounts: if true, the number of children under each returned sibling is calculated and returned in the result set under the column name 'sibling_count'.d
	 *		idsOnly: if true, only the primary key id values of the chidlren records are returned
	 * @return array 
	 */
	public function &getHierarchySiblings($pn_id=null, $pa_options=null) {
		$pb_ids_only = (isset($pa_options['idsOnly']) && $pa_options['idsOnly']) ? true : false;
		
		if (!$pn_id) { $pn_id = $this->getPrimaryKey(); }
		if (!$pn_id) { return null; }
		
		// convert id into parent_id - get the children of the parent is equivalent to getting the siblings for the id
		if ($qr_parent = $this->getDb()->query("
			SELECT ".$this->getProperty('HIERARCHY_PARENT_ID_FLD')." 
			FROM ".$this->tableName()." 
			WHERE ".$this->primaryKey()." = ?", (int)$pn_id)) {
			if ($qr_parent->nextRow()) {
				$pn_id = $qr_parent->get($this->getProperty('HIERARCHY_PARENT_ID_FLD'));
			} else {
				$this->postError(250, _t('Could not get parent_id to load siblings by: %1', join(';', $this->getDb()->getErrors())), 'BaseModel->getHierarchySiblings');
				return false;
			}
		} else {
			$this->postError(250, _t('Could not get hierarchy siblings: %1', join(';', $this->getDb()->getErrors())), 'BaseModel->getHierarchySiblings');
			return false;
		}
		
		$qr_children = $this->getHierarchyChildrenAsQuery($pn_id, $pa_options);
		
		
		$va_children = array();
		$vs_pk = $this->primaryKey();
		while($qr_children->nextRow()) {
			if ($pb_ids_only) {
				$va_row = $qr_children->getRow();
				$va_children[] = $va_row[$vs_pk];
			} else {
				$va_children[] = $qr_children->getRow();
			}
		}
		
		return $va_children;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Get hierarchy ancestors
	 * 
	 * @access public
	 * @param int optional, primary key value of a record.
	 * 	Use this if you want to know the ancestors of a record different than $this
	 * @param array optional, options 
	 *			idsOnly = just return the ids of the ancestors (def. false)
	 *			includeSelf = include this record (def. false)
	 *			additionalTableToJoin = name of additonal table data to return
	 * @return array 
	 */
	public function &getHierarchyAncestors($pn_id=null, $pa_options=null) {
		$pb_include_self = (isset($pa_options['includeSelf']) && $pa_options['includeSelf']) ? true : false;
		$pb_ids_only = (isset($pa_options['idsOnly']) && $pa_options['idsOnly']) ? true : false;
		
		$va_additional_table_select_fields = array();
		$va_additional_table_wheres = array();
			
		// additional table to join into query?
		$ps_additional_table_to_join = isset($pa_options['additionalTableToJoin']) ? $pa_options['additionalTableToJoin'] : null;
		if ($ps_additional_table_to_join) {		
			// what kind of join are we doing for the additional table? LEFT or INNER? (default=INNER)
			$ps_additional_table_join_type = 'INNER';
			if (isset($pa_options['additionalTableJoinType']) && ($pa_options['additionalTableJoinType'] === 'LEFT')) {
				$ps_additional_table_join_type = 'LEFT';
			}
			
			// what fields from the additional table are we going to return?
			if (isset($pa_options['additionalTableSelectFields']) && is_array($pa_options['additionalTableSelectFields'])) {
				foreach($pa_options['additionalTableSelectFields'] as $vs_fld) {
					$va_additional_table_select_fields[] = "{$ps_additional_table_to_join}.{$vs_fld}";
				}
			}
			
			// are there any SQL WHERE criteria for the additional table?
			if (isset($pa_options['additionalTableWheres']) && is_array($pa_options['additionalTableWheres'])) {
				$va_additional_table_wheres = $pa_options['additionalTableWheres'];
			}
		}
		
		if ($this->isHierarchical()) {
			if (!$pn_id) {
				if (!($pn_id = $this->getPrimaryKey())) {
					return null;
				}
			}
			
			$vs_hier_left_fld 		= $this->getProperty("HIERARCHY_LEFT_INDEX_FLD");
			$vs_hier_right_fld 		= $this->getProperty("HIERARCHY_RIGHT_INDEX_FLD");
			$vs_hier_id_fld 		= $this->getProperty("HIERARCHY_ID_FLD");
			$vs_hier_id_table 		= $this->getProperty("HIERARCHY_DEFINITION_TABLE");
			$vs_hier_parent_id_fld 	= $this->getProperty("HIERARCHY_PARENT_ID_FLD");
			
			$vs_table_name = $this->tableName();
			
			$va_sql_joins = array();
			if ($ps_additional_table_to_join){ 
				$va_path = $this->getAppDatamodel()->getPath($vs_table_name, $ps_additional_table_to_join);
			
				switch(sizeof($va_path)) {
					case 2:
						$va_rels = $this->getAppDatamodel()->getRelationships($vs_table_name, $ps_additional_table_to_join);
						$va_sql_joins[] = $ps_additional_table_join_type." JOIN {$ps_additional_table_to_join} ON ".$vs_table_name.'.'.$va_rels[$ps_additional_table_to_join][$vs_table_name][0][1]." = {$ps_additional_table_to_join}.".$va_rels[$ps_additional_table_to_join][$vs_table_name][0][0];
						break;
					case 3:
						// TODO: handle many-many cases
						break;
				}
			}
			$vs_sql_joins = join("\n", $va_sql_joins);
			
			$o_db = $this->getDb();
			$qr_root = $o_db->query("
				SELECT {$vs_table_name}.* ".(sizeof($va_additional_table_select_fields) ? ', '.join(', ', $va_additional_table_select_fields) : '')."
				FROM {$vs_table_name}
				{$vs_sql_joins}
				WHERE
					({$vs_table_name}.".$this->primaryKey()." = ?) ".((sizeof($va_additional_table_wheres) > 0) ? ' AND '.join(' AND ', $va_additional_table_wheres) : '')."
			", intval($pn_id));
		
			if ($o_db->numErrors()) {
				$this->errors = array_merge($this->errors, $o_db->errors());
				return null;
			} else {
				if ($qr_root->numRows()) {
					$va_ancestors = array();
					
					$vn_parent_id = null;
					$vn_level = 0;
					if ($pb_include_self) {
						while ($qr_root->nextRow()) {
							if (!$vn_parent_id) { $vn_parent_id = $qr_root->get($vs_hier_parent_id_fld); }
							if ($pb_ids_only) {
								$va_ancestors[] = $qr_root->get($this->primaryKey());
							} else {
								$va_ancestors[] = array(
									"NODE" => $qr_root->getRow(),
									"LEVEL" => $vn_level
								);
							}
							$vn_level++;
						}
					} else {
						$qr_root->nextRow();
						$vn_parent_id = $qr_root->get($vs_hier_parent_id_fld);
					}
					
					if($vn_parent_id) {
						do {
							$vs_sql = "
								SELECT {$vs_table_name}.* ".(sizeof($va_additional_table_select_fields) ? ', '.join(', ', $va_additional_table_select_fields) : '')."
								FROM {$vs_table_name} 
								{$vs_sql_joins}
								WHERE ({$vs_table_name}.".$this->primaryKey()." = ?) ".((sizeof($va_additional_table_wheres) > 0) ? ' AND '.join(' AND ', $va_additional_table_wheres) : '')."
							";
							
							$qr_hier = $o_db->query($vs_sql, $vn_parent_id);
							$vn_parent_id = null;
							while ($qr_hier->nextRow()) {
								if (!$vn_parent_id) { $vn_parent_id = $qr_hier->get($vs_hier_parent_id_fld); }
								if ($pb_ids_only) {
									$va_ancestors[] = $qr_hier->get($this->primaryKey());
								} else {
									$va_ancestors[] = array(
										"NODE" => $qr_hier->getRow(),
										"LEVEL" => $vn_level
									);
								}
							}
							$vn_level++;
						} while($vn_parent_id);
						return $va_ancestors;
					} else {
						return $va_ancestors;
					}
				} else {
					return null;
				}
			}
		} else {
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	public function rebuildAllHierarchicalIndexes() {
		$vs_hier_left_fld 		= $this->getProperty("HIERARCHY_LEFT_INDEX_FLD");
		$vs_hier_right_fld 		= $this->getProperty("HIERARCHY_RIGHT_INDEX_FLD");
		$vs_hier_id_fld 		= $this->getProperty("HIERARCHY_ID_FLD");
		$vs_hier_id_table 		= $this->getProperty("HIERARCHY_DEFINITION_TABLE");
		$vs_hier_parent_id_fld 	= $this->getProperty("HIERARCHY_PARENT_ID_FLD");
		
		if (!$vs_hier_id_fld) { return false; }
		
		$o_db = $this->getDb();
		$qr_hier_ids = $o_db->query("
			SELECT DISTINCT ".$vs_hier_id_fld."
			FROM ".$this->tableName()."
		");
		while($qr_hier_ids->nextRow()) {
			$this->rebuildHierarchicalIndex($qr_hier_ids->get($vs_hier_id_fld));
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	public function rebuildHierarchicalIndex($pn_hierarchy_id=null) {
		if ($this->isHierarchical()) {
			$vb_we_set_transaction = false;
			if (!$this->inTransaction()) {
				$this->setTransaction(new Transaction($this->getDb()));
				$vb_we_set_transaction = true;
			}
			if ($vn_root_id = $this->getHierarchyRootID($pn_hierarchy_id)) {
				$this->_rebuildHierarchicalIndex($vn_root_id, 1);
				if ($vb_we_set_transaction) { $this->removeTransaction(true);}
				return true;
			} else {
				if ($vb_we_set_transaction) { $this->removeTransaction(false);}
				return null;
			}
		} else {
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	private function _rebuildHierarchicalIndex($pn_parent_id, $pn_hier_left) {
		$vs_hier_parent_id_fld 		= $this->getProperty("HIERARCHY_PARENT_ID_FLD");
		$vs_hier_left_fld 			= $this->getProperty("HIERARCHY_LEFT_INDEX_FLD");
		$vs_hier_right_fld 			= $this->getProperty("HIERARCHY_RIGHT_INDEX_FLD");
		$vs_hier_id_fld 			= $this->getProperty("HIERARCHY_ID_FLD");
		$vs_hier_id_table 			= $this->getProperty("HIERARCHY_DEFINITION_TABLE");

		$vn_hier_right = $pn_hier_left + 100;
		
		$vs_pk = $this->primaryKey();
		
		$o_db = $this->getDb();
		
		if (is_null($pn_parent_id)) {
			$vs_sql = "
				SELECT *
				FROM ".$this->tableName()."
				WHERE
					(".$vs_hier_parent_id_fld." IS NULL)
			";
		} else {
			$vs_sql = "
				SELECT *
				FROM ".$this->tableName()."
				WHERE
					(".$vs_hier_parent_id_fld." = ".intval($pn_parent_id).")
			";
		}
		$qr_level = $o_db->query($vs_sql);
		
		if ($o_db->numErrors()) {
			$this->errors = array_merge($this->errors, $o_db->errors());
			return null;
		} else {
			while($qr_level->nextRow()) {
				$vn_hier_right = $this->_rebuildHierarchicalIndex($qr_level->get($vs_pk), $vn_hier_right);
			}
			
			$qr_up = $o_db->query("
				UPDATE ".$this->tableName()."
				SET ".$vs_hier_left_fld." = ".intval($pn_hier_left).", ".$vs_hier_right_fld." = ".intval($vn_hier_right)."
				WHERE 
					(".$vs_pk." = ?)
			", intval($pn_parent_id));
			
			if ($o_db->numErrors()) {
				$this->errors = array_merge($this->errors, $o_db->errors());
				return null;
			} else {
				return $vn_hier_right + 100;
			}
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * HTML Form element generation
	 * Optional name parameter allows you to generate a form element for a field but give it a
	 * name different from the field name
	 * 
	 * @param string $ps_field field name
	 * @param string $ps_format field format
	 * @param array $pa_options additional options
	 * TODO: document them.
	 */
	public function htmlFormElement($ps_field, $ps_format=null, $pa_options=null) {
		$o_db = $this->getDb();
		
		// init options
		if (!is_array($pa_options)) { 
			$pa_options = array(); 
		}
		foreach (array(
				'display_form_field_tips', 'classname', 'maxOptionLength', 'textAreaTagName', 'display_use_count',
				'display_omit_items__with_zero_count', 'display_use_count_filters', 'display_use_count_filters',
				'selection', 'name', 'value', 'dont_show_null_value', 'size', 'multiple', 'show_text_field_for_vars',
				'nullOption', 'empty_message', 'displayMessageForFieldValues', 'DISPLAY_FIELD', 'WHERE',
				'select_item_text', 'hide_select_if_only_one_option', 'field_errors', 'display_form_field_tips', 'form_name',
				'no_tooltips', 'tooltip_namespace', 'extraLabelText', 'width', 'height', 'label', 'list_code', 'hide_select_if_no_options', 'id',
				'lookup_url', 'progress_indicator', 'error_icon', 'maxPixelWidth'
			) 
			as $vs_key) {
			if(!isset($pa_options[$vs_key])) { $pa_options[$vs_key] = null; }
		}
		

		$va_attr = $this->getFieldInfo($ps_field);
		
		foreach (array(
				'DISPLAY_WIDTH', 'DISPLAY_USE_COUNT', 'DISPLAY_SHOW_COUNT', 'DISPLAY_OMIT_ITEMS_WITH_ZERO_COUNT',
				'DISPLAY_TYPE', 'IS_NULL', 'DEFAULT_ON_NULL', 'DEFAULT', 'LIST_MULTIPLE_DELIMITER', 'FIELD_TYPE',
				'LIST_CODE', 'DISPLAY_FIELD', 'WHERE', 'DISPLAY_WHERE', 'DISPLAY_ORDERBY', 'LIST',
				'BOUNDS_CHOICE_LIST', 'BOUNDS_LENGTH', 'DISPLAY_DESCRIPTION', 'LABEL', 'DESCRIPTION',
				'SUB_LABEL', 'SUB_DESCRIPTION', 'MAX_PIXEL_WIDTH'
				) 
			as $vs_key) {
			if(!isset($va_attr[$vs_key])) { $va_attr[$vs_key] = null; }
		}
		
		
		$vn_display_width = (isset($pa_options['width']) && ($pa_options['width'] > 0)) ? $pa_options['width'] : $va_attr["DISPLAY_WIDTH"];
		$vn_display_height = (isset($pa_options['height']) && ($pa_options['height'] > 0)) ? $pa_options['height'] : $va_attr["DISPLAY_HEIGHT"];
		
		$vs_field_label = (isset($pa_options['label']) && (strlen($pa_options['label']) > 0)) ? $pa_options['label'] : $va_attr["LABEL"];
		
		$vs_errors = '';

// TODO: PULL THIS FROM A CONFIG FILE
$pa_options["display_form_field_tips"] = true;

		if (isset($pa_options['classname'])) {
			$vs_css_class_attr = ' class="'.$pa_options['classname'].'" ';
		} else {
			$vs_css_class_attr = '';
		}

		if (!isset($pa_options['id'])) { $pa_options['id'] = $pa_options['name']; }
		if (!isset($pa_options['id'])) { $pa_options['id'] = $ps_field; }

		if (!isset($pa_options['maxPixelWidth']) || ((int)$pa_options['maxPixelWidth'] <= 0)) { $vn_max_pixel_width = $va_attr['MAX_PIXEL_WIDTH']; } else { $vn_max_pixel_width = (int)$pa_options['maxPixelWidth']; }
		if ($vn_max_pixel_width <= 0) { $vn_max_pixel_width = null; }
		
		if (!isset($pa_options["maxOptionLength"]) && isset($vn_display_width)) {
			$pa_options["maxOptionLength"] = isset($vn_display_width) ? $vn_display_width : null;
		}
		
		$vs_text_area_tag_name = 'textarea';
		if (isset($pa_options["textAreaTagName"]) && $pa_options['textAreaTagName']) {
			$vs_text_area_tag_name = isset($pa_options['textAreaTagName']) ? $pa_options['textAreaTagName'] : null;
		}

		if (!isset($va_attr["DISPLAY_USE_COUNT"]) || !($vs_display_use_count = $va_attr["DISPLAY_USE_COUNT"])) {
			$vs_display_use_count = isset($pa_options["display_use_count"]) ? $pa_options["display_use_count"] : null;
		}
		if (!isset($va_attr["DISPLAY_SHOW_COUNT"]) || !($vb_display_show_count = (boolean)$va_attr["DISPLAY_SHOW_COUNT"])) {
			$vb_display_show_count = isset($pa_options["display_show_count"]) ? (boolean)$pa_options["display_show_count"] : null;
		}
		if (!isset($va_attr["DISPLAY_OMIT_ITEMS_WITH_ZERO_COUNT"]) || !($vb_display_omit_items__with_zero_count = (boolean)$va_attr["DISPLAY_OMIT_ITEMS_WITH_ZERO_COUNT"])) {
			$vb_display_omit_items__with_zero_count = isset($pa_options["display_omit_items__with_zero_count"]) ? (boolean)$pa_options["display_omit_items__with_zero_count"] : null;
		}
		if (!isset($va_attr["DISPLAY_OMIT_ITEMS_WITH_ZERO_COUNT"]) || !($va_display_use_count_filters = $va_attr["DISPLAY_USE_COUNT_FILTERS"])) {
			$va_display_use_count_filters = isset($pa_options["display_use_count_filters"]) ? $pa_options["display_use_count_filters"] : null;
		}
		if (!isset($va_display_use_count_filters) || !is_array($va_display_use_count_filters)) { $va_display_use_count_filters = null; }

		if (isset($pa_options["selection"]) && is_array($pa_options["selection"])) {
			$va_selection = isset($pa_options["selection"]) ? $pa_options["selection"] : null;
		} else {
			$va_selection = array();
		}

		$vs_element = $vs_subelement = "";
		if ($va_attr) {
			# --- Skip omitted fields completely
			if ($va_attr["DISPLAY_TYPE"] == DT_OMIT) {
				return "";
			}

			if (!isset($pa_options["name"]) || !$pa_options["name"]) {
				$pa_options["name"] = htmlspecialchars($ps_field, ENT_QUOTES, 'UTF-8');
			}

			$va_js = array();
			$va_handlers = array("onclick", "onchange", "onkeypress", "onkeydown", "onkeyup");
			foreach($va_handlers as $vs_handler) {
				if (isset($pa_options[$vs_handler]) && $pa_options[$vs_handler]) {
					$va_js[] = "$vs_handler='".($pa_options[$vs_handler])."'";
				}
			}
			$vs_js = join(" ", $va_js);

			if (!isset($pa_options["value"])) {	// allow field value to be overriden with value from options array
				$vm_field_value = $this->get($ps_field, $pa_options);
			} else {
				$vm_field_value = $pa_options["value"];
			}

			$vb_is_null = isset($va_attr["IS_NULL"]) ? $va_attr["IS_NULL"] : false;
			if (isset($pa_options['dont_show_null_value']) && $pa_options['dont_show_null_value']) { $vb_is_null = false; }

			if (
				(!is_array($vm_field_value) && (strlen($vm_field_value) == 0)) &&
				(
				(!isset($vb_is_null) || (!$vb_is_null)) ||
				((isset($va_attr["DEFAULT_ON_NULL"]) ? $va_attr["DEFAULT_ON_NULL"] : 0))
				)
			) {
				$vm_field_value = isset($va_attr["DEFAULT"]) ? $va_attr["DEFAULT"] : "";
			}

			# --- Return hidden fields
			if ($va_attr["DISPLAY_TYPE"] == DT_HIDDEN) {
				return '<input type="hidden" name="'.$pa_options["name"].'" value="'.$this->escapeHTML($vm_field_value).'"/>';
			}


			if (isset($pa_options["size"]) && ($pa_options["size"] > 0)) {
				$ps_size = " size='".$pa_options["size"]."'";
			} else{
				if ((($va_attr["DISPLAY_TYPE"] == DT_LIST_MULTIPLE) || ($va_attr["DISPLAY_TYPE"] == DT_LIST)) && ($vn_display_height > 1)) {
					$ps_size = " size='".$vn_display_height."'";
				} else {
					$ps_size = '';
				}
			}

			$vs_multiple_name_extension = '';
			if ($vs_is_multiple = ((isset($pa_options["multiple"]) && $pa_options["multiple"]) || ($va_attr["DISPLAY_TYPE"] == DT_LIST_MULTIPLE) ? "multiple='1'" : "")) {
				$vs_multiple_name_extension = '[]';

				if (!($vs_list_multiple_delimiter = $va_attr['LIST_MULTIPLE_DELIMITER'])) { $vs_list_multiple_delimiter = ';'; }
				$va_selection = array_merge($va_selection, explode($vs_list_multiple_delimiter, $vm_field_value));
			}


			# --- Return form element
			switch($va_attr["FIELD_TYPE"]) {
				# ----------------------------
				case(FT_NUMBER):
				case(FT_TEXT):
				case(FT_VARS):
					if ($va_attr["FIELD_TYPE"] == FT_VARS) {
						if (!$pa_options['show_text_field_for_vars']) {
							break;
						}
						if (!is_string($vm_field_value) && !is_numeric($vm_field_value)) { $vm_value = ''; }
					}

					$vs_width_style = $vs_width_style_attr = '';
					if ($vn_max_pixel_width) {
						$vs_width_style = "max-width: {$vn_max_pixel_width}px;";
						$vs_width_style_attr = "style='{$vs_width_style}'";
					}


					if (($vn_display_width > 0) && (in_array($va_attr["DISPLAY_TYPE"], array(DT_SELECT, DT_LIST, DT_LIST_MULTIPLE)))) {
						#
						# Generate auto generated <select> (from foreign key, from ca_lists or from field-defined choice list)
						#
						# TODO: CLEAN UP THIS CODE, RUNNING VARIOUS STAGES THROUGH HELPER FUNCTIONS; ALSO FORMALIZE AND DOCUMENT VARIOUS OPTIONS
						// -----
						// from ca_lists
						// -----
						if(!($vs_list_code = $pa_options['list_code'])) {
							if(isset($va_attr['LIST_CODE']) && $va_attr['LIST_CODE']) {
								$vs_list_code = $va_attr['LIST_CODE'];
							}
						}
						if ($vs_list_code) {
							
							$va_many_to_one_relations = $this->_DATAMODEL->getManyToOneRelations($this->tableName());
							
							if ($va_many_to_one_relations[$ps_field]) {
								$vs_key = 'item_id';
							} else {
								$vs_key = 'item_value';
							}
							
							$vs_null_option = null;
							if (!$pa_options["nullOption"] && $vb_is_null) {
								$vs_null_option = "- NONE -";
							} else {
								if ($pa_options["nullOption"]) {
									$vs_null_option = $pa_options["nullOption"];
								}
							}
							
							$t_list = new ca_lists();
							$va_list_attrs = array( 'id' => $pa_options['id']);
							if ($vn_max_pixel_width) { $va_list_attrs['style'] = $vs_width_style; }
							
							$vs_element = $t_list->getListAsHTMLFormElement($vs_list_code, $pa_options["name"].$vs_multiple_name_extension, $va_list_attrs, array('value' => $vm_field_value, 'key' => $vs_key, 'nullOption' => $vs_null_option));
							
							if (isset($pa_options['hide_select_if_no_options']) && $pa_options['hide_select_if_no_options'] && (!$vs_element)) {
								$vs_element = "";
								$ps_format = '^ERRORS^ELEMENT';
							} 
						} else {
							// -----
							// from related table
							// -----
							$va_many_to_one_relations = $this->_DATAMODEL->getManyToOneRelations($this->tableName());
							if (isset($va_many_to_one_relations[$ps_field]) && $va_many_to_one_relations[$ps_field]) {
								#
								# Use foreign  key to populate <select>
								#
								$o_one_table = $this->_DATAMODEL->getTableInstance($va_many_to_one_relations[$ps_field]["one_table"]);
								$vs_one_table_primary_key = $o_one_table->primaryKey();
	
								if ($o_one_table->isHierarchical()) {
									#
									# Hierarchical <select>
									#
									$va_hier = $o_one_table->getHierarchyAsList(0, $vs_display_use_count, $va_display_use_count_filters, $vb_display_omit_items__with_zero_count);
	
									$va_display_fields = $va_attr["DISPLAY_FIELD"];
									if (!in_array($vs_one_table_primary_key, $va_display_fields)) {
										$va_display_fields[] = $o_one_table->tableName().".".$vs_one_table_primary_key;
									}
									if (!is_array($va_display_fields) || sizeof($va_display_fields) < 1) {
										$va_display_fields = array("*");
									}
	
									$vs_hier_parent_id_fld = $o_one_table->getProperty("HIER_PARENT_ID_FLD");
	
									$va_options = array();
									if ($pa_options["nullOption"]) {
										$va_options[""] = array($pa_options["nullOption"]);
									}
									$va_suboptions = array();
									$va_suboption_values = array();
	
									$vn_selected = 0;
									$vm_cur_top_level_val = null;
									$vm_selected_top_level_val = null;
									foreach($va_hier as $va_option) {
										if (!$va_option["NODE"][$vs_hier_parent_id_fld]) { continue; }
										$vn_val = $va_option["NODE"][$o_one_table->primaryKey()];
										$vs_selected = ($vn_val == $vm_field_value) ? 'selected="1"' : "";
	
										$vn_indent = $va_option["LEVEL"] - 1;
	
										$va_display_data = array();
										foreach ($va_display_fields as $vs_fld) {
											$va_bits = explode(".", $vs_fld);
											if ($va_bits[1] != $vs_one_table_primary_key) {
												$va_display_data[] = $va_option["NODE"][$va_bits[1]];
											}
										}
										$vs_option_label = join(" ", $va_display_data);
										$va_options[$vn_val] = array($vs_option_label, $vn_indent, $va_option["HITS"], $va_option['NODE']);
									}
	
									if (sizeof($va_options) == 0) {
										$vs_element = isset($pa_options['empty_message']) ? $pa_options['empty_message'] : 'No options available';
									} else {
										$vs_element = "<select name='".$pa_options["name"].$vs_multiple_name_extension."' ".$vs_js." ".$vs_is_multiple." ".$ps_size." id='".$pa_options["id"].$vs_multiple_name_extension."' {$vs_css_class_attr} {$vs_width_style_attr}>\n";
	
										if (!$pa_options["nullOption"] && $vb_is_null) {
											$vs_element .= "<option value=''>- NONE -</option>\n";
										} else {
											if ($pa_options["nullOption"]) {
												$vs_element .= "<option value=''>".$pa_options["nullOption"]."</option>\n";
											}
										}
	
										foreach($va_options as $vn_val => $va_option_info) {
											$vs_selected = (($vn_val == $vm_field_value) || in_array($vn_val, $va_selection)) ? "selected='selected'" : "";
	
											$vs_element .= "<option value='".$vn_val."' $vs_selected>";
	
											$vn_indent = ($va_option_info[1]) * 2;
											$vs_indent = "";
	
											if ($vn_indent > 0) {
												$vs_indent = str_repeat("&nbsp;", ($vn_indent - 1) * 2)." ";
												$vn_indent++;
											}
	
											$vs_option_text = $va_option_info[0];
	
											$vs_use_count = "";
											if($vs_display_use_count && $vb_display_show_count&& ($vn_val != "")) {
												$vs_use_count = " (".intval($va_option_info[2]).")";
											}
	
											$vs_display_message = '';
											if (is_array($pa_options['displayMessageForFieldValues'])) {
													foreach($pa_options['displayMessageForFieldValues'] as $vs_df => $va_df_vals) {
														if ((isset($va_option_info[3][$vs_df])) && is_array($va_df_vals)) {
															$vs_tmp = $va_option_info[3][$vs_df];
															if (isset($va_df_vals[$vs_tmp])) {
																$vs_display_message = ' '.$va_df_vals[$vs_tmp];
															}
														}
													}
											}
	
											if (
												($pa_options["maxOptionLength"]) &&
												(strlen($vs_option_text) + strlen($vs_use_count) + $vn_indent > $pa_options["maxOptionLength"])
											)  {
												if (($vn_strlen = $pa_options["maxOptionLength"] - strlen($vs_indent) - strlen($vs_use_count) - 3) < $pa_options["maxOptionLength"]) {
													$vn_strlen = $pa_options["maxOptionLength"];
												}
	
												$vs_option_text = unicode_substr($vs_option_text, 0, $vn_strlen)."...";
											}
	
											$vs_element .= $vs_indent.$vs_option_text.$vs_use_count.$vs_display_message."</option>\n";
										}
										$vs_element .= "</select>\n";
									}
								} else {
									#
									# "Flat" <select>
									#
									if (!is_array($va_display_fields = $pa_options["DISPLAY_FIELD"])) { $va_display_fields = $va_attr["DISPLAY_FIELD"]; }
	
									if (!is_array($va_display_fields)) {
										return "Configuration error: DISPLAY_FIELD directive for field '$ps_field' must be an array of field names in the format tablename.fieldname";
									}
									if (!in_array($vs_one_table_primary_key, $va_display_fields)) {
										$va_display_fields[] = $o_one_table->tableName().".".$vs_one_table_primary_key;
									}
									if (!is_array($va_display_fields) || sizeof($va_display_fields) < 1) {
										$va_display_fields = array("*");
									}
	
									$vs_sql = "
											SELECT *
											FROM ".$va_many_to_one_relations[$ps_field]["one_table"]."
											";
	
									if (isset($pa_options["WHERE"]) && (is_array($pa_options["WHERE"]) && ($vs_where = join(" AND ",$pa_options["WHERE"]))) || ((is_array($va_attr["DISPLAY_WHERE"])) && ($vs_where = join(" AND ",$va_attr["DISPLAY_WHERE"])))) {
										$vs_sql .= " WHERE $vs_where ";
									}
	
									if ((isset($va_attr["DISPLAY_ORDERBY"])) && ($va_attr["DISPLAY_ORDERBY"]) && ($vs_orderby = join(",",$va_attr["DISPLAY_ORDERBY"]))) {
										$vs_sql .= " ORDER BY $vs_orderby ";
									}
	
									$qr_res = $o_db->query($vs_sql);
									if ($o_db->numErrors()) {
										$vs_element = "Error creating menu: ".join(';', $o_db->getErrors());
										break;
									}
									
									$va_opts = array();
	
									if (isset($pa_options["nullOption"]) && $pa_options["nullOption"]) {
										$va_opts[$pa_options["nullOption"]] = array($pa_options["nullOption"], null);
									} else {
										if ($vb_is_null) {
											$va_opts["- NONE -"] = array("- NONE -", null);
										}
									}
	
									if ($pa_options["select_item_text"]) {
										$va_opts[$pa_options["select_item_text"]] = array($pa_options["select_item_text"], null);
									}
	
									$va_fields = array();
									foreach($va_display_fields as $vs_field) {
										$va_tmp = explode(".", $vs_field);
										$va_fields[] = $va_tmp[1];
									}
	
	
									while ($qr_res->nextRow()) {
										$vs_display = "";
										foreach($va_fields as $vs_field) {
											if ($vs_field != $vs_one_table_primary_key) {
												$vs_display .= $qr_res->get($vs_field). " ";
											}
										}
	
										$va_opts[] = array($vs_display, $qr_res->get($vs_one_table_primary_key), $qr_res->getRow());
									}
	
									if (sizeof($va_opts) == 0) {
										$vs_element = isset($pa_options['empty_message']) ? $pa_options['empty_message'] : 'No options available';
									} else {
										if (isset($pa_options['hide_select_if_only_one_option']) && $pa_options['hide_select_if_only_one_option'] && (sizeof($va_opts) == 1)) {
											
											$vs_element = "<input type='hidden' name='".$pa_options["name"]."' ".$vs_js." ".$ps_size." id='".$pa_options["id"]."' value='".$va_opts[0][1]."' {$vs_css_class_attr}/>";
											$ps_format = '^ERRORS^ELEMENT';
										} else {
											$vs_element = "<select name='".$pa_options["name"].$vs_multiple_name_extension."' ".$vs_js." ".$vs_is_multiple." ".$ps_size." id='".$pa_options["id"].$vs_multiple_name_extension."' {$vs_css_class_attr} {$vs_width_style_attr}>\n";
											foreach ($va_opts as $va_opt) {
												$vs_option_text = $va_opt[0];
												$vs_value = $va_opt[1];
												$vs_selected = (($vs_value == $vm_field_value) || in_array($vs_value, $va_selection)) ? "selected='selected'" : "";
		
												$vs_use_count = "";
												if ($vs_display_use_count && $vb_display_show_count && ($vs_value != "")) {
													$vs_use_count = "(".intval($va_option_info[2]).")";
												}
		
												if (
													($pa_options["maxOptionLength"]) &&
													(strlen($vs_option_text) + strlen($vs_use_count) > $pa_options["maxOptionLength"])
												)  {
													$vs_option_text = unicode_substr($vs_option_text,0, $pa_options["maxOptionLength"] - 3 - strlen($vs_use_count))."...";
												}
		
												$vs_display_message = '';
												if (is_array($pa_options['displayMessageForFieldValues'])) {
														foreach($pa_options['displayMessageForFieldValues'] as $vs_df => $va_df_vals) {
															if ((isset($va_opt[2][$vs_df])) && is_array($va_df_vals)) {
																$vs_tmp = $va_opt[2][$vs_df];
																if (isset($va_df_vals[$vs_tmp])) {
																	$vs_display_message = ' '.$va_df_vals[$vs_tmp];
																}
															}
														}
												}
		
												$vs_element.= "<option value='$vs_value' $vs_selected>";
												$vs_element .= $vs_option_text.$vs_use_count.$vs_display_message;
												$vs_element .= "</option>\n";
											}
											$vs_element .= "</select>\n";
										}
									}
								}
							} else {
								#
								# choice list
								#
								
								// if 'LIST' is set try to stock over choice list with the contents of the list
								if (isset($va_attr['LIST']) && $va_attr['LIST']) {
									$t_list = new ca_lists();
									if ($t_list->load(array('list_code' => $va_attr['LIST']))) {
										$va_items = caExtractValuesByUserLocale($t_list->getItemsForList($va_attr['LIST'], array('returnHierarchyLevels' => true)));
										$va_attr["BOUNDS_CHOICE_LIST"] = array();
										
										foreach($va_items as $vn_item_id => $va_item_info) {
											$va_attr["BOUNDS_CHOICE_LIST"][str_repeat('&nbsp;', ((int)$va_item_info['LEVEL'] * 3)).' '.$va_item_info['name_singular']] = $va_item_info['item_value'];
										}
									}
								}
								if (isset($va_attr["BOUNDS_CHOICE_LIST"]) && is_array($va_attr["BOUNDS_CHOICE_LIST"])) {
	
									if (sizeof($va_attr["BOUNDS_CHOICE_LIST"]) == 0) {
										$vs_element = isset($pa_options['empty_message']) ? $pa_options['empty_message'] : 'No options available';
									} else {
										$vs_element = "<select name='".$pa_options["name"].$vs_multiple_name_extension."' ".$vs_js." ".$vs_is_multiple." ".$ps_size." id='".$pa_options['id'].$vs_multiple_name_extension."' {$vs_css_class_attr} {$vs_width_style_attr}>\n";
	
										if ($pa_options["select_item_text"]) {
											$vs_element.= "<option value=''>".$this->escapeHTML($pa_options["select_item_text"])."</option>\n";
										}
										if (!$pa_options["nullOption"] && $vb_is_null) {
											$vs_element .= "<option value=''>- NONE -</option>\n";
										} else {
											if ($pa_options["nullOption"]) {
												$vs_element .= "<option value=''>".$pa_options["nullOption"]."</option>\n";
											}
										}
										foreach($va_attr["BOUNDS_CHOICE_LIST"] as $vs_option => $vs_value) {
	
											$vs_selected = ((strval($vs_value) === strval($vm_field_value)) || in_array($vs_value, $va_selection)) ? "selected='selected'" : "";
	
											if (($pa_options["maxOptionLength"]) && (strlen($vs_option) > $pa_options["maxOptionLength"]))  {
												$vs_option = unicode_substr($vs_option, 0, $pa_options["maxOptionLength"] - 3)."...";
											}
	
											$vs_element.= "<option value='$vs_value' $vs_selected>".$this->escapeHTML($vs_option)."</option>\n";
										}
										$vs_element .= "</select>\n";
									}
								} 
							}
						}
					} else {
						if ($va_attr["DISPLAY_TYPE"] === DT_COLORPICKER) {		// COLORPICKER
							$vs_element = '<input name="'.$pa_options["name"].'" type="hidden" size="'.($pa_options['size'] ? $pa_options['size'] : $vn_display_width).'" value="'.$this->escapeHTML($vm_field_value).'" '.$vs_js.' id=\''.$pa_options["id"].'\'/>'."\n";
							$vs_element .= '<div id="'.$pa_options["id"].'_colorchip" class="colorpicker_chip" style="background-color: #'.$vm_field_value.'"><!-- empty --></div>';
							$vs_element .= "<script type='text/javascript'>jQuery(document).ready(function() { jQuery('#".$pa_options["name"]."_colorchip').ColorPicker({
								onShow: function (colpkr) {
									jQuery(colpkr).fadeIn(500);
									return false;
								},
								onHide: function (colpkr) {
									jQuery(colpkr).fadeOut(500);
									return false;
								},
								onChange: function (hsb, hex, rgb) {
									jQuery('#".$pa_options["name"]."').val(hex);
									jQuery('#".$pa_options["name"]."_colorchip').css('backgroundColor', '#' + hex);
								},
								color: jQuery('#".$pa_options["name"]."').val()
							})}); </script>\n";
							
							if (method_exists('JavascriptLoadManager', 'register')) {
								JavascriptLoadManager::register('jquery', 'colorpicker');
							}
						} else {
							# normal controls: all non-DT_SELECT display types are returned as DT_FIELD's. We could generate
							# radio-button controls for foreign key and choice lists, but we don't bother because it's never
							# really necessary.
							if ($vn_display_height > 1) {
								$vs_element = '<'.$vs_text_area_tag_name.' name="'.$pa_options["name"].'" rows="'.$vn_display_height.'" cols="'.$vn_display_width.'" wrap="soft" '.$vs_js.' id=\''.$pa_options["id"].'\'>'.$this->escapeHTML($vm_field_value).'</'.$vs_text_area_tag_name.'>'."\n";
							} else {
								$vs_element = '<input name="'.$pa_options["name"].'" type="text" size="'.($pa_options['size'] ? $pa_options['size'] : $vn_display_width).'" value="'.$this->escapeHTML($vm_field_value).'" '.$vs_js.' id=\''.$pa_options["id"].'\'. '.$vs_width_style_attr.'/>'."\n";
							}
							
							if (isset($va_attr['UNIQUE_WITHIN']) && is_array($va_attr['UNIQUE_WITHIN'])) {
								$va_within_fields = array();
								foreach($va_attr['UNIQUE_WITHIN'] as $vs_within_field) {
									$va_within_fields[$vs_within_field] = $this->get($vs_within_field);
								}
							
								$vs_element .= "<span id='".$pa_options["id"].'_uniqueness_status'."'></span>";
								$vs_element .= "<script type='text/javascript'>
						caUI.initUniquenessChecker({
							errorIcon: '".$pa_options['error_icon']."',
							processIndicator: '".$pa_options['progress_indicator']."',
							statusID: '".$pa_options["id"]."_uniqueness_status',
							lookupUrl: '".$pa_options['lookup_url']."',
							formElementID: '".$pa_options["id"]."',
							row_id: ".intval($this->getPrimaryKey()).",
							table_num: ".$this->tableNum().",
							field: '".$ps_field."',
							withinFields: ".json_encode($va_within_fields).",
							
							alreadyInUseMessage: '".addslashes(_t('Value must be unique. Please try another.'))."'
						});
					</script>";
							}
						}
					}
					break;
				# ----------------------------
				case (FT_TIMESTAMP):
					if ($this->get($ps_field)) { # is timestamp set?
						$vs_element = $this->escapeHTML($vm_field_value);  # return printed date
					} else {
						$vs_element = "[Not set]"; # return text instead of 1969 date
					}
					break;
				# ----------------------------
				case (FT_DATETIME):
				case (FT_HISTORIC_DATETIME):
				case (FT_DATE):
				case (FT_HISTORIC_DATE):
					if (!$vm_field_value) {
						$vm_field_value = $pa_options['value'];
					}
					switch($va_attr["DISPLAY_TYPE"]) {
						case DT_TEXT:
							$vs_element = $vm_field_value ? $vm_field_value : "[Not set]";
							break;
						default:
							$vn_max_length = $va_attr["BOUNDS_LENGTH"][1];
							$vs_max_length = '';
							if ($vn_max_length > 0) $vs_max_length = 'maxlength="'.$vn_max_length.'"';
							if ($vn_display_height > 1) {
								$vs_element = '<'.$vs_text_area_tag_name.' name="'.$pa_options["name"].'" rows="'.$vn_display_height.'" cols="'.$vn_display_width.'" wrap="soft" '.$vs_js.' '.$vs_css_class_attr.'>'.$this->escapeHTML($vm_field_value).'</'.$vs_text_area_tag_name.'>';
							} else {
								$vs_element = '<input type="text" name="'.$pa_options["name"].'" value="'.$this->escapeHTML($vm_field_value).'" size="'.$vn_display_width.'" '.$vs_max_length.' '.$vs_js.' '.$vs_css_class_attr.' '.$vs_width_style_attr.'/>';
							}
							break;
					}
					break;
				# ----------------------------
				case(FT_TIME):
					if (!$this->get($ps_field)) {
						$vm_field_value = "";
					}
					switch($va_attr["DISPLAY_TYPE"]) {
						case DT_TEXT:
							$vs_element = $vm_field_value ? $vm_field_value : "[Not set]";
							break;
						default:
							$vn_max_length = $va_attr["BOUNDS_LENGTH"][1];
							$vs_max_length = '';
							if ($vn_max_length > 0) $vs_max_length = 'maxlength="'.$vn_max_length.'"';
							if ($vn_display_height > 1) {
								$vs_element = '<'.$vs_text_area_tag_name.' name="'.$pa_options["name"].'" rows="'.$vn_display_height.'" cols="'.$vn_display_width.'" wrap="soft" '.$vs_js.' '.$vs_css_class_attr.'>'.$this->escapeHTML($vm_field_value).'</'.$vs_text_area_tag_name.'>';
							} else {
								$vs_element = '<input type="text" name="'.$pa_options["name"].'" value="'.$this->escapeHTML($vm_field_value).'" size="'.$vn_display_width.'" '.$vs_max_length.' '.$vs_js.' '.$vs_css_class_attr.' '.$vs_width_style_attr.'/>';
							}
							break;
					}
					break;
				# ----------------------------
				case(FT_DATERANGE):
				case(FT_HISTORIC_DATERANGE):
					switch($va_attr["DISPLAY_TYPE"]) {
						case DT_TEXT:
							$vs_element = $vm_field_value ? $vm_field_value : "[Not set]";
							break;
						default:
							$vn_max_length = $va_attr["BOUNDS_LENGTH"][1];
							$vs_max_length = '';
							if ($vn_max_length > 0) $vs_max_length = 'maxlength="'.$vn_max_length.'"';
							if ($vn_display_height > 1) {
								$vs_element = '<'.$vs_text_area_tag_name.' name="'.$pa_options["name"].'" rows="'.$vn_display_height.'" cols="'.$vn_display_width.'" wrap="soft" '.$vs_js.' '.$vs_css_class_attr.'>'.$this->escapeHTML($vm_field_value).'</'.$vs_text_area_tag_name.'>';
							} else {
								$vs_element = '<input type="text" name="'.$pa_options["name"].'" value="'.$this->escapeHTML($vm_field_value).'" size="'.$vn_display_width.'" '.$vn_max_length.' '.$vs_js.' '.$vs_css_class_attr.' '.$vs_width_style_attr.'/>';
							}
							break;
					}
					break;
				# ----------------------------
				case (FT_TIMERANGE):
					switch($va_attr["DISPLAY_TYPE"]) {
						case DT_TEXT:
							$vs_element = $vm_field_value ? $vm_field_value : "[Not set]";
							break;
						default:
							$vn_max_length = $va_attr["BOUNDS_LENGTH"][1];
							$vs_max_length = '';
							if ($vn_max_length > 0) $vs_max_length = 'maxlength="'.$vn_max_length.'"';
							if ($vn_display_height > 1) {
								$vs_element = '<'.$vs_text_area_tag_name.' name="'.$pa_options["name"].'" rows="'.$vn_display_height.'" cols="'.$vn_display_width.'" wrap="soft" '.$vs_js.' '.$vs_css_class_attr.'>'.$this->escapeHTML($vm_field_value).'</'.$vs_text_area_tag_name.'>';
							} else {
								$vs_element = '<input type="text" name="'.$pa_options["name"].'" value="'.$this->escapeHTML($vm_field_value).'" size="'.$vn_display_width.'" '.$vs_max_length.' '.$vs_js.' '.$vs_css_class_attr.' '.$vs_width_style_attr.'/>';
							}
							break;
					}
					break;
				# ----------------------------
				case(FT_TIMECODE):
					$o_tp = new TimecodeParser();
					$o_tp->setParsedValueInSeconds($vm_field_value);

					$vs_timecode = $o_tp->getText("COLON_DELIMITED", array("BLANK_ON_ZERO" => true));

					$vn_max_length = $va_attr["BOUNDS_LENGTH"][1];
					$vs_max_length = '';
					if ($vn_max_length > 0) $vs_max_length = 'maxlength="'.$vn_max_length.'"';
					if ($vn_display_height > 1) {
						$vs_element = '<'.$vs_text_area_tag_name.' name="'.$pa_options["name"].'" rows="'.$vn_display_height.'" cols="'.$vn_display_width.'" wrap="soft" '.$vs_js.' '.$vs_css_class_attr.'>'.$this->escapeHTML($vs_timecode).'</'.$vs_text_area_tag_name.'>';
					} else {
						$vs_element = '<input type="text" NAME="'.$pa_options["name"].'" value="'.$this->escapeHTML($vs_timecode).'" size="'.$vn_display_width.'" '.$vs_max_length.' '.$vs_js.' '.$vs_css_class_attr.' '.$vs_width_style_attr.'/>';
					}
					break;
				# ----------------------------
				case(FT_MEDIA):
				case(FT_FILE):
					$vs_element = '<input type="file" name="'.$pa_options["name"].'" '.$vs_js.'/>';
					
					// show current media icon (this is a hack; need to make configurable)
					// TODO: make configurable
					if ($vs_tag = $this->getMediaTag($ps_field, 'icon')) { $vs_element .= $vs_tag; }
					break;
				# ----------------------------
				case(FT_PASSWORD):
					$vn_max_length = $va_attr["BOUNDS_LENGTH"][1];
					$vs_max_length = '';
					if ($vn_max_length > 0) $vs_max_length = 'maxlength="'.$vn_max_length.'"';
					$vs_element = '<input type="password" name="'.$pa_options["name"].'" value="'.$this->escapeHTML($vm_field_value).'" size="'.$vn_display_width.'" '.$vs_max_length.' '.$vs_js.' autocomplete="off" '.$vs_css_class_attr.'/>';
					break;
				# ----------------------------
				case(FT_BIT):
					switch($va_attr["DISPLAY_TYPE"]) {
						case (DT_FIELD):
							$vs_element = '<input type="text" name="'.$pa_options["name"].'" value="'.$vm_field_value.'" maxlength="1" size="2" '.$vs_js.'/>';
							break;
						case (DT_SELECT):
							$vs_element = "<select name='".$pa_options["name"]."' ".$vs_js." id='".$pa_options["id"]."' {$vs_css_class_attr} {$vs_width_style_attr}>\n";
							foreach(array("Yes" => 1, "No" => 0) as $vs_option => $vs_value) {
								$vs_selected = ($vs_value == $vm_field_value) ? "selected='selected'" : "";
								$vs_element.= "<option value='$vs_value' $vs_selected>$vs_option</option>\n";
							}
							$vs_element .= "</select>\n";
							break;
						case (DT_CHECKBOXES):
							$vs_element = '<input type="checkbox" name="'.$pa_options["name"].'" value="1" '.($vm_field_value ? 'checked="1"' : '').' '.$vs_js.'/>';
							break;
						case (DT_RADIO_BUTTONS):
							$vs_element = 'Radio buttons not supported for bit-type fields';
							break;
					}
					break;
				# ----------------------------
			}

			# Apply format
			$vs_formatting = "";
			
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
			if ($ps_format != '') {
				$ps_formatted_element = $ps_format;
				$ps_formatted_element = str_replace("^ELEMENT", $vs_element, $ps_formatted_element);
				if ($vs_subelement) {
					$ps_formatted_element = str_replace("^SUB_ELEMENT", $vs_subelement, $ps_formatted_element);
				}

				$vb_fl_display_form_field_tips = false;
				
				if (
					$pa_options["display_form_field_tips"] ||
					(!isset($pa_options["display_form_field_tips"]) && $va_attr["DISPLAY_DESCRIPTION"]) ||
					(!isset($pa_options["display_form_field_tips"]) && !isset($va_attr["DISPLAY_DESCRIPTION"]) && $vb_fl_display_form_field_tips)
				) {
					if (preg_match("/\^DESCRIPTION/", $ps_formatted_element)) {
						$ps_formatted_element = str_replace("^LABEL",$vs_field_label, $ps_formatted_element);
						$ps_formatted_element = str_replace("^DESCRIPTION",$va_attr["DESCRIPTION"], $ps_formatted_element);
					} else {
						// no explicit placement of description text, so...
						$vs_field_id = '_'.$this->tableName().'_'.$this->getPrimaryKey().'_'.$pa_options["name"].'_'.$pa_options['form_name'];
						$ps_formatted_element = str_replace("^LABEL",'<span id="'.$vs_field_id.'">'.$vs_field_label.'</span>', $ps_formatted_element);


						if (!isset($pa_options['no_tooltips']) || !$pa_options['no_tooltips']) {
							TooltipManager::add('#'.$vs_field_id, "<h3>{$vs_field_label}</h3>".$va_attr["DESCRIPTION"], $pa_options['tooltip_namespace']);
						}
					}

					if (!isset($va_attr["SUB_LABEL"])) { $va_attr["SUB_LABEL"] = ''; }
					if (!isset($va_attr["SUB_DESCRIPTION"])) { $va_attr["SUB_DESCRIPTION"] = ''; }
					
					if (preg_match("/\^SUB_DESCRIPTION/", $ps_formatted_element)) {
						$ps_formatted_element = str_replace("^SUB_LABEL",$va_attr["SUB_LABEL"], $ps_formatted_element);
						$ps_formatted_element = str_replace("^SUB_DESCRIPTION", $va_attr["SUB_DESCRIPTION"], $ps_formatted_element);
					} else {
						// no explicit placement of description text, so...
						// ... make label text itself rollover for description text because no icon was specified
						$ps_formatted_element = str_replace("^SUB_LABEL",$va_attr["SUB_LABEL"], $ps_formatted_element);
					}
				} else {
					$ps_formatted_element = str_replace("^LABEL", $vs_field_label, $ps_formatted_element);
					$ps_formatted_element = str_replace("^DESCRIPTION", "", $ps_formatted_element);
					if ($vs_subelement) {
						$ps_formatted_element = str_replace("^SUB_LABEL", $va_attr["SUB_LABEL"], $ps_formatted_element);
						$ps_formatted_element = str_replace("^SUB_DESCRIPTION", "", $ps_formatted_element);
					}
				}

				$ps_formatted_element = str_replace("^ERRORS", $vs_errors, $ps_formatted_element);
				$ps_formatted_element = str_replace("^EXTRA", isset($pa_options['extraLabelText']) ? $pa_options['extraLabelText'] : '', $ps_formatted_element);
				$vs_element = $ps_formatted_element;
			} else {
				$vs_element .= "<br/>".$vs_subelement;
			}

			return $vs_element;
		} else {
			$this->postError(716,_t("'%1' does not exist in this object", $ps_field),"BaseModel->formElement()");
			return "";
		}
		return "";
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Get list name
	 * 
	 * @access public
	 * @return string
	 */
	public function getListName() {
		if (is_array($va_display_fields = $this->getProperty('LIST_FIELDS'))) {
			$va_tmp = array();
			$vs_delimiter = $this->getProperty('LIST_DELIMITER');
			foreach($va_display_fields as $vs_display_field) {
				$va_tmp[] = $this->get($vs_display_field);
			}
			return join($vs_delimiter, $va_tmp);
		}
		return null;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Modifies text for HTML use (i.e. passing it through stripslashes and htmlspecialchars)
	 * 
	 * @param string $ps_text
	 * @return string
	 */
	public function escapeHTML($ps_text) {
		$opa_php_version = caGetPHPVersion();

		if ($opa_php_version['versionInt'] >= 50203) {
			$ps_text = htmlspecialchars(stripslashes($ps_text), ENT_QUOTES, $this->getCharacterSet(), false);
		} else {
			$ps_text = htmlspecialchars(stripslashes($ps_text), ENT_QUOTES, $this->getCharacterSet());
		}
		return str_replace("&amp;#", "&#", $ps_text);
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns list of names of fields that must be defined
	 */
	public function getMandatoryFields() {
		$va_fields = $this->getFormFields(true);
		
		$va_many_to_one_relations = $this->_DATAMODEL->getManyToOneRelations($this->tableName());
		//print_r($va_many_to_one_relations);
		$va_mandatory_fields = array();
		foreach($va_fields as $vs_field => $va_info) {
			if (isset($va_info['IDENTITY']) && $va_info['IDENTITY']) { continue;}	
			
			if ((isset($va_many_to_one_relations[$vs_field]) && $va_many_to_one_relations[$vs_field]) && (!isset($va_info['IS_NULL']) || !$va_info['IS_NULL'])) {
				$va_mandatory_fields[] = $vs_field;
				continue;
			}
			if (isset($va_info['BOUNDS_LENGTH']) && is_array($va_info['BOUNDS_LENGTH'])) {
				if ($va_info['BOUNDS_LENGTH'][0] > 0) {
					$va_mandatory_fields[] = $vs_field;
					continue;
				}
			}
		}
		
		return $va_mandatory_fields;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns a list_code for a list in the ca_lists table that is used for the specified field
	 * Return null if the field has no list associated with it
	 */
	public function getFieldListCode($ps_field) {
		$va_field_info = $this->getFieldInfo($ps_field);
		return ($vs_list_code = $va_field_info['LIST_CODE']) ? $vs_list_code : null;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Return list of items specified for the given field.
	 *
	 * @param string $ps_field The name of the field
	 * @param string $ps_list_code Optional list code or list_id to force return of, overriding the list configured in the model
	 * @return array A list of items, filtered on the current user locale; the format is the same as that returned by ca_lists::getItemsForList()
	 */
	public function getFieldList($ps_field, $ps_list_code=null) {
		$t_list = new ca_lists();
		return caExtractValuesByUserLocale($t_list->getItemsForList($ps_list_code ? $ps_list_code : $this->getFieldListCode($ps_field)));
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Creates a relationship between the currently loaded row and the specified row.
	 *
	 * @param mixed $pm_rel_table_name_or_num Table name (eg. "ca_entities") or number as defined in datamodel.conf of table containing row to creation relationship to.
	 * @param int $pn_rel_id primary key value of row to creation relationship to.
	 * @param mixed $pm_type_id Relationship type type_code or type_id, as defined in the ca_relationship_types table. This is required for all relationships that use relationship types. This includes all of the most common types of relationships.
	 * @param string $ps_effective_date Optional date expression to qualify relation with. Any expression that the TimeExpressionParser can handle is supported here.
	 * @param string $ps_source_info Text field for storing information about source of relationship. Not currently used.
	 * @param string $ps_direction Optional direction specification for self-relationships (relationships linking two rows in the same table). Valid values are 'ltor' (left-to-right) and  'rtol' (right-to-left); the direction determines which "side" of the relationship the currently loaded row is on: 'ltor' puts the current row on the left side. For many self-relations the direction determines the nature and display text for the relationship.
	 * @return boolean True on success, false on error.
	 */
	public function addRelationship($pm_rel_table_name_or_num, $pn_rel_id, $pm_type_id=null, $ps_effective_date=null, $ps_source_info=null, $ps_direction=null) {
		if(!($va_rel_info = $this->_getRelationshipInfo($pm_rel_table_name_or_num))) { 
			$this->postError(1240, _t('Related table specification "%1" is not valid', $pm_rel_table_name_or_num), 'BaseModel->addRelationship()');
			return false; 
		}
		$t_item_rel = $va_rel_info['t_item_rel'];
		
		if ($pm_type_id && !is_numeric($pm_type_id)) {
			$t_rel_type = new ca_relationship_types();
			if ($vs_linking_table = $t_rel_type->getRelationshipTypeTable($this->tableName(), $t_item_rel->tableName())) {
				$pn_type_id = $t_rel_type->getRelationshipTypeID($vs_linking_table, $pm_type_id);
			}
		} else {
			$pn_type_id = $pm_type_id;
		}
		

		if ($va_rel_info['related_table_name'] == $this->tableName()) {
			// is self relation
			$t_item_rel->setMode(ACCESS_WRITE);
			
			// is self relationship
			if ($ps_direction == 'rtol') {
				$t_item_rel->set($t_item_rel->getRightTableFieldName(), $this->getPrimaryKey());
				$t_item_rel->set($t_item_rel->getLeftTableFieldName(), $pn_rel_id);
			} else {
				// default is left-to-right
				$t_item_rel->set($t_item_rel->getLeftTableFieldName(), $this->getPrimaryKey());
				$t_item_rel->set($t_item_rel->getRightTableFieldName(), $pn_rel_id);
			}
			$t_item_rel->set($t_item_rel->getTypeFieldName(), $pn_type_id);		// TODO: verify type_id based upon type_id's of each end of the relationship
					
			$t_item_rel->insert();
			if ($t_item_rel->numErrors()) {
				$this->errors = $t_item_rel->errors;
				return false;
			}
		} else {
			switch(sizeof($va_rel_info['path'])) {
				case 3:		// many-to-many relationship
					$t_rel = $this->getAppDatamodel()->getTableInstance($va_rel_info['related_table_name']);
					
					$t_item_rel->setMode(ACCESS_WRITE);
					
					$vs_left_table = $t_item_rel->getLeftTableName();
					$vs_right_table = $t_item_rel->getRightTableName();

					if ($this->tableName() == $vs_left_table) {
						// is lefty
						$t_item_rel->set($t_item_rel->getLeftTableFieldName(), $this->getPrimaryKey());
						$t_item_rel->set($t_item_rel->getRightTableFieldName(), $pn_rel_id);
					} else {
						// is righty
						$t_item_rel->set($t_item_rel->getRightTableFieldName(), $this->getPrimaryKey());
						$t_item_rel->set($t_item_rel->getLeftTableFieldName(), $pn_rel_id);
					}
						
					$t_item_rel->set($t_item_rel->getTypeFieldName(), $pn_type_id);		// TODO: verify type_id based upon type_id's of each end of the relationship
					if($ps_source_info != null){
						$t_item_rel->set("source_info",$ps_source_info);
					}
					$t_item_rel->insert();
					
					if ($t_item_rel->numErrors()) {
						$this->errors = $t_item_rel->errors;
						return false;
					}
				case 2:		// many-to-one relationship
					if ($this->tableName() == $va_rel_info['rel_keys']['one_table']) {
						if ($t_item_rel->load($pn_rel_id)) {
							$t_item_rel->setMode(ACCESS_WRITE);
							$t_item_rel->set($va_rel_info['rel_keys']['many_table_field'], $this->getPrimaryKey());
							$t_item_rel->update();
							
							if ($t_item_rel->numErrors()) {
								$this->errors = $t_item_rel->errors;
								return false;
							}
						} else {
							$t_item_rel->setMode(ACCESS_WRITE);
							$t_item_rel->set($t_item_rel->getLeftTableFieldName(), $this->getPrimaryKey());
							$t_item_rel->set($t_item_rel->getRightTableFieldName(), $pn_rel_id);
							$t_item_rel->set($t_item_rel->getTypeFieldName(), $pn_type_id);	
							$t_item_rel->insert();
							
							if ($t_item_rel->numErrors()) {
								$this->errors = $t_item_rel->errors;
								return false;
							}
						}
					} else {
						$this->setMode(ACCESS_WRITE);
						$this->set($va_rel_info['rel_keys']['many_table_field'], $pn_rel_id);
						$this->update();
						
						if ($this->numErrors()) {
							return false;
						}
					}
					break;
				default:
					return false;
					break;
			}
		}		
		return true;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Edits the data in an existing relationship between the currently loaded row and the specified row.
	 *
	 * @param mixed $pm_rel_table_name_or_num Table name (eg. "ca_entities") or number as defined in datamodel.conf of table containing row to create relationships to.
	 * @param int $pn_relation_id primary key value of the relation to edit.
	 * @param int $pn_rel_id primary key value of row to creation relationship to.
	 * @param mixed $pm_type_id Relationship type type_code or type_id, as defined in the ca_relationship_types table. This is required for all relationships that use relationship types. This includes all of the most common types of relationships.
	 * @param string $ps_effective_date Optional date expression to qualify relation with. Any expression that the TimeExpressionParser can handle is supported here.
	 * @param string $ps_source_info Text field for storing information about source of relationship. Not currently used.
	 * @param string $ps_direction Optional direction specification for self-relationships (relationships linking two rows in the same table). Valid values are 'ltor' (left-to-right) and  'rtol' (right-to-left); the direction determines which "side" of the relationship the currently loaded row is on: 'ltor' puts the current row on the left side. For many self-relations the direction determines the nature and display text for the relationship.
	 * @return boolean True on success, false on error.
	 */
	public function editRelationship($pm_rel_table_name_or_num, $pn_relation_id, $pn_rel_id, $pm_type_id=null, $ps_effective_date=null, $pa_source_info=null, $ps_direction=null) {
		if(!($va_rel_info = $this->_getRelationshipInfo($pm_rel_table_name_or_num))) { 
			$this->postError(1240, _t('Related table specification "%1" is not valid', $pm_rel_table_name_or_num), 'BaseModel->editRelationship()');
			return false; 
		}
		$t_item_rel = $va_rel_info['t_item_rel'];
		
		if ($pm_type_id && !is_numeric($pm_type_id)) {
			$t_rel_type = new ca_relationship_types();
			if ($vs_linking_table = $t_rel_type->getRelationshipTypeTable($this->tableName(), $t_item_rel->tableName())) {
				$pn_type_id = $t_rel_type->getRelationshipTypeID($vs_linking_table, $pm_type_id);
			}
		} else {
			$pn_type_id = $pm_type_id;
		}
		
		
		if ($va_rel_info['related_table_name'] == $this->tableName()) {
			// is self relation
			if ($t_item_rel->load($pn_relation_id)) {
				$t_item_rel->setMode(ACCESS_WRITE);
				
				if ($ps_direction == 'rtol') {
					$t_item_rel->set($t_item_rel->getRightTableFieldName(), $this->getPrimaryKey());
					$t_item_rel->set($t_item_rel->getLeftTableFieldName(), $pn_rel_id);
				} else {
					// default is left-to-right
					$t_item_rel->set($t_item_rel->getLeftTableFieldName(), $this->getPrimaryKey());
					$t_item_rel->set($t_item_rel->getRightTableFieldName(), $pn_rel_id);
				}
				$t_item_rel->set($t_item_rel->getTypeFieldName(), $pn_type_id);		// TODO: verify type_id based upon type_id's of each end of the relationship
						
				$t_item_rel->update();
				if ($t_item_rel->numErrors()) {
					$this->errors = $t_item_rel->errors;
					return false;
				}
			}
		} else {
			switch(sizeof($va_rel_info['path'])) {
				case 3:		// many-to-many relationship
					if ($t_item_rel->load($pn_relation_id)) {
						$t_item_rel->setMode(ACCESS_WRITE);
						$vs_left_table = $t_item_rel->getLeftTableName();
						$vs_right_table = $t_item_rel->getRightTableName();
						if ($this->tableName() == $vs_left_table) {
							// is lefty
							$t_item_rel->set($t_item_rel->getLeftTableFieldName(), $this->getPrimaryKey());
							$t_item_rel->set($t_item_rel->getRightTableFieldName(), $pn_rel_id);
						} else {
							// is righty
							$t_item_rel->set($t_item_rel->getRightTableFieldName(), $this->getPrimaryKey());
							$t_item_rel->set($t_item_rel->getLeftTableFieldName(), $pn_rel_id);
						}
						$t_item_rel->set($t_item_rel->getTypeFieldName(), $pn_type_id);		// TODO: verify type_id based upon type_id's of each end of the relationship
						
						$t_item_rel->update();
						
						if ($t_item_rel->numErrors()) {
							$this->errors = $t_item_rel->errors;
							return false;
						}
						
						return true;
					}
				case 2:		// many-to-one relations
					if ($this->tableName() == $va_rel_info['rel_keys']['one_table']) {
						if ($t_item_rel->load($pn_relation_id)) {
							$t_item_rel->setMode(ACCESS_WRITE);
							$t_item_rel->set($va_rel_info['rel_keys']['many_table_field'], null);
							$t_item_rel->update();
							
							if ($t_item_rel->numErrors()) {
								$this->errors = $t_item_rel->errors;
								return false;
							}
						}
						
						if ($t_item_rel->load($pn_rel_id)) {
							$t_item_rel->setMode(ACCESS_WRITE);
							$t_item_rel->set($va_rel_info['rel_keys']['many_table_field'], $this->getPrimaryKey());
							$t_item_rel->update();
							
							if ($t_item_rel->numErrors()) {
								$this->errors = $t_item_rel->errors;
								return false;
							}
						}
					} else {
						$this->setMode(ACCESS_WRITE);
						$this->set($va_rel_info['rel_keys']['many_table_field'], $pn_rel_id);
						$this->update();
						
						if ($this->numErrors()) {
							return false;
						}
					}
					break;
				default:
					return false;
					break;
			}
		}
		
		return false;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Removes the specified relationship
	 *
	 * @param mixed $pm_rel_table_name_or_num Table name (eg. "ca_entities") or number as defined in datamodel.conf of table containing row to edit relationships to.
	 * @param int $pn_relation_id primary key value of the relation to remove.
	 *  @return boolean True on success, false on error.
	 */
	public function removeRelationship($pm_rel_table_name_or_num, $pn_relation_id) {
		if(!($va_rel_info = $this->_getRelationshipInfo($pm_rel_table_name_or_num))) { 
			$this->postError(1240, _t('Related table specification "%1" is not valid', $pm_rel_table_name_or_num), 'BaseModel->removeRelationship()');
			return false; 
		}
		$t_item_rel = $va_rel_info['t_item_rel'];
		
		
		if ($va_rel_info['related_table_name'] == $this->tableName()) {
			if ($t_item_rel->load($pn_relation_id)) {
				$t_item_rel->setMode(ACCESS_WRITE);
				$t_item_rel->delete();
				
				if ($t_item_rel->numErrors()) {
					$this->errors = $t_item_rel->errors;
					return false;
				}
				return true;
			}	
		} else {
			switch(sizeof($va_rel_info['path'])) {
				case 3:		// many-to-one relationship
					if ($t_item_rel->load($pn_relation_id)) {
						$t_item_rel->setMode(ACCESS_WRITE);
						$t_item_rel->delete();
						
						if ($t_item_rel->numErrors()) {
							$this->errors = $t_item_rel->errors;
							return false;
						}
						return true;
					}	
				case 2:
					if ($this->tableName() == $va_rel_info['rel_keys']['one_table']) {
						if ($t_item_rel->load($pn_relation_id)) {
							$t_item_rel->setMode(ACCESS_WRITE);
							$t_item_rel->set($va_rel_info['rel_keys']['many_table_field'], null);
							$t_item_rel->update();
							
							if ($t_item_rel->numErrors()) {
								$this->errors = $t_item_rel->errors;
								return false;
							}
						}
					} else {
						$this->setMode(ACCESS_WRITE);
						$this->set($va_rel_info['rel_keys']['many_table_field'], null);
						$this->update();
						
						if ($this->numErrors()) {
							return false;
						}
					}
					break;
				default:
					return false;
					break;
			}
		}
		
		return false;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Remove all relations with the specified table from the currently loaded row
	 *
	 * @param mixed $pm_rel_table_name_or_num Table name (eg. "ca_entities") or number as defined in datamodel.conf of table containing row to removes relationships to.
	 * @return boolean True on success, false on error
	 */
	public function removeRelationships($pm_rel_table_name_or_num) {
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		if(!($va_rel_info = $this->_getRelationshipInfo($pm_rel_table_name_or_num))) { return null; }
		$t_item_rel = $va_rel_info['t_item_rel'];
		
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT relation_id FROM ".$t_item_rel->tableName()." WHERE ".$this->primaryKey()." = ?
		", (int)$vn_row_id);
		
		while($qr_res->nextRow()) {
			if (!$this->removeRelationship($pm_rel_table_name_or_num, $qr_res->get('relation_id'))) { 
				return false;
			}
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	private function _getRelationshipInfo($pm_rel_table_name_or_num) {
		if (is_numeric($pm_rel_table_name_or_num)) {
			$vs_related_table_name = $this->getAppDataModel()->getTableName($pm_rel_table_name_or_num);
		} else {
			$vs_related_table_name = $pm_rel_table_name_or_num;
		}
		
		$va_rel_keys = array();
		if ($this->tableName() == $vs_related_table_name) {
			// self relations
			if ($vs_self_relation_table = $this->getSelfRelationTableName()) {
				$t_item_rel = $this->getAppDatamodel()->getTableInstance($vs_self_relation_table);
			} else {
				return null;
			}
		} else {
			$va_path = array_keys($this->getAppDatamodel()->getPath($this->tableName(), $vs_related_table_name));
			
			switch(sizeof($va_path)) {
				case 3:
					$t_item_rel = $this->getAppDatamodel()->getTableInstance($va_path[1]);
					break;
				case 2:
					$t_item_rel = $this->getAppDatamodel()->getTableInstance($va_path[1]);
					if (!sizeof($va_rel_keys = $this->_DATAMODEL->getOneToManyRelations($this->tableName(), $va_path[1]))) {
						$va_rel_keys = $this->_DATAMODEL->getOneToManyRelations($va_path[1], $this->tableName());
					}
					break;
				default:
					// bad related table
					return null;
					break;
			}
		}
		
		return array(
			'related_table_name' => $vs_related_table_name,
			'path' => $va_path,
			'rel_keys' => $va_rel_keys,
			't_item_rel' => $t_item_rel
		);
	}
	# --------------------------------------------------------------------------------------------
	/**
	 *
	 */
	public function getDefaultLocaleList() {
		global $g_ui_locale_id;
		$va_locale_dedup = array();
		if ($g_ui_locale_id) {
			$va_locale_dedup[$g_ui_locale_id] = true;
		}
		
		$t_locale = new ca_locales();
		$va_locales = $t_locale->getLocaleList();
		
		if (is_array($va_locale_defaults = $this->getAppConfig()->getList('locale_defaults'))) {
			foreach($va_locale_defaults as $vs_locale_default) {
				$va_locale_dedup[$va_locales[$vs_locale_default]] = true;
			}
		}
		
		foreach($va_locales as $vn_locale_id => $vs_locale_code) {
			$va_locale_dedup[$vn_locale_id] = true;
		}
		
		return array_keys($va_locale_dedup);
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns name of self relation table (table that links two rows in this table) or NULL if no table exists
	 *
	 * @return string Name of table or null if no table is defined.
	 */
	public function getSelfRelationTableName() {
		if (isset($this->SELF_RELATION_TABLE_NAME)) {
			return $this->SELF_RELATION_TABLE_NAME;
		}
		return null;
	}
	# --------------------------------------------------------------------------------------------
	# User tagging
	# --------------------------------------------------------------------------------------------
	/**
	 * Adds a tag to currently loaded row. Returns null if no row is loaded. Otherwise returns true
	 * if tag was successfully added, false if an error occurred in which case the errors will be available
	 * via the model's standard error methods (getErrors() and friends.
	 *
	 * Most of the parameters are optional with the exception of $ps_tag - the text of the tag. Note that 
	 * tag text is monolingual; if you want to do multilingual tags then you must add multiple tags.
	 *
	 * The parameters are:
	 *
	 * @param $ps_tag [string] Text of the tag (mandatory)
	 * @param $pn_user_id [integer] A valid ca_users.user_id indicating the user who added the tag; is null for tags from non-logged-in users (optional - default is null)
	 * @param $pn_locale_id [integer] A valid ca_locales.locale_id indicating the language of the comment. If omitted or left null then the value in the global $g_ui_locale_id variable is used. If $g_ui_locale_id is not set and $pn_locale_id is not set then an error will occur (optional - default is to use $g_ui_locale_id)
	 * @param $pn_access [integer] Determines public visibility of tag; if set to 0 then tag is not visible to public; if set to 1 tag is visible (optional - default is 0)
	 * @param $pn_moderator [integer] A valid ca_users.user_id value indicating who moderated the tag; if omitted or set to null then moderation status will not be set unless app.conf setting dont_moderate_comments = 1 (optional - default is null)
	 */
	public function addTag($ps_tag, $pn_user_id=null, $pn_locale_id=null, $pn_access=0, $pn_moderator=null) {
		global $g_ui_locale_id;
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		if (!$pn_locale_id) { $pn_locale_id = $g_ui_locale_id; }
		if (!$pn_locale_id) { 
			$this->postErrors(2830, _t('No locale was set for tag'), 'BaseModel->addTag()');
			return false;
		}
		$t_tag = new ca_item_tags();
		
		if (!$t_tag->load(array('tag' => $ps_tag, 'locale_id' => $pn_locale_id))) {
			// create new new
			$t_tag->setMode(ACCESS_WRITE);
			$t_tag->set('tag', $ps_tag);
			$t_tag->set('locale_id', $pn_locale_id);
			$vn_tag_id = $t_tag->insert();
			
			if ($t_tag->numErrors()) {
				$this->errors = $t_tag->errors;
				return false;
			}
		} else {
			$vn_tag_id = $t_tag->getPrimaryKey();
		}
		
		$t_ixt = new ca_items_x_tags();
		$t_ixt->setMode(ACCESS_WRITE);
		$t_ixt->set('table_num', $this->tableNum());
		$t_ixt->set('row_id', $this->getPrimaryKey());
		$t_ixt->set('user_id', $pn_user_id);
		$t_ixt->set('tag_id', $vn_tag_id);
		$t_ixt->set('access', $pn_access);
		
		if (!is_null($pn_moderator)) {
			$t_ixt->set('moderated_by_user_id', $pn_moderator);
			$t_ixt->set('moderated_on', 'now');
		}elseif($this->_CONFIG->get("dont_moderate_comments")){
			$t_ixt->set('moderated_on', 'now');
		}
		
		$t_ixt->insert();
		
		if ($t_ixt->numErrors()) {
			$this->errors = $t_ixt->errors;
			return false;
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Changed the access value for an existing tag. Returns null if no row is loaded. Otherwise returns true
	 * if tag access setting was successfully changed, false if an error occurred in which case the errors will be available
	 * via the model's standard error methods (getErrors() and friends.
	 *
	 * If $pn_user_id is set then only tag relations created by the specified user can be modified. Attempts to modify
	 * tags created by users other than the one specified in $pn_user_id will return false and post an error.
	 *
	 * Most of the parameters are optional with the exception of $ps_tag - the text of the tag. Note that 
	 * tag text is monolingual; if you want to do multilingual tags then you must add multiple tags.
	 *
	 * The parameters are:
	 *
	 * @param $pn_relation_id [integer] A valid ca_items_x_tags.relation_id value specifying the tag relation to modify (mandatory)
	 * @param $pn_access [integer] Determines public visibility of tag; if set to 0 then tag is not visible to public; if set to 1 tag is visible (optional - default is 0)
	 * @param $pn_moderator [integer] A valid ca_users.user_id value indicating who moderated the tag; if omitted or set to null then moderation status will not be set (optional - default is null)
	 * @param $pn_user_id [integer] A valid ca_users.user_id valid; if set only tag relations created by the specified user will be modifed  (optional - default is null)
	 */
	public function changeTagAccess($pn_relation_id, $pn_access=0, $pn_moderator=null, $pn_user_id=null) {
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		
		$t_ixt = new ca_items_x_tags($pn_relation_id);
		
		if (!$t_ixt->getPrimaryKey()) {
			$this->postError(2800, _t('Tag relation id is invalid'), 'BaseModel->changeTagAccess()');
			return false;
		}
		if (
			($t_ixt->get('table_num') != $this->tableNum()) ||
			($t_ixt->get('row_id') != $vn_row_id)
		) {
			$this->postError(2810, _t('Tag is not part of the current row'), 'BaseModel->changeTagAccess()');
			return false;
		}
		
		if ($pn_user_id) {
			if ($t_ixt->get('user_id') != $pn_user_id) {
				$this->postError(2820, _t('Tag was not created by specified user'), 'BaseModel->changeTagAccess()');
				return false;
			}
		}
		
		$t_ixt->setMode(ACCESS_WRITE);
		$t_ixt->set('access', $pn_access);
		
		if (!is_null($pn_moderator)) {
			$t_ixt->set('moderated_by_user_id', $pn_moderator);
			$t_ixt->set('moderated_on', 'now');
		}
		
		$t_ixt->update();
		
		if ($t_ixt->numErrors()) {
			$this->errors = $t_ixt->errors;
			return false;
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Deletes the tag relation specified by $pn_relation_id (a ca_items_x_tags.relation_id value) from the currently loaded row. Will only delete 
	 * tags attached to the currently loaded row. If you attempt to delete a ca_items_x_tags.relation_id not attached to the current row 
	 * removeTag() will return false and post an error. If you attempt to call removeTag() with no row loaded null will be returned.
	 * If $pn_user_id is specified then only tags created by the specified user will be deleted; if the tag being
	 * deleted is not created by the user then false is returned and an error posted.
	 *
	 * @param $pn_relation_id [integer] a valid ca_items_x_tags.relation_id to be removed; must be related to the currently loaded row (mandatory)
	 * @param $pn_user_id [integer] a valid ca_users.user_id value; if specified then only tag relations added by the specified user will be deleted (optional - default is null)
	 */
	public function removeTag($pn_relation_id, $pn_user_id=null) {
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		
		$t_ixt = new ca_items_x_tags($pn_relation_id);
		
		if (!$t_ixt->getPrimaryKey()) {
			$this->postError(2800, _t('Tag relation id is invalid'), 'BaseModel->removeTag()');
			return false;
		}
		if (
			($t_ixt->get('table_num') != $this->tableNum()) ||
			($t_ixt->get('row_id') != $vn_row_id)
		) {
			$this->postError(2810, _t('Tag is not part of the current row'), 'BaseModel->removeTag()');
			return false;
		}
		
		if ($pn_user_id) {
			if ($t_ixt->get('user_id') != $pn_user_id) {
				$this->postError(2820, _t('Tag was not created by specified user'), 'BaseModel->removeTag()');
				return false;
			}
		}
		
		$t_ixt->setMode(ACCESS_WRITE);
		$t_ixt->delete();
		
		if ($t_ixt->numErrors()) {
			$this->errors = $t_ixt->errors;
			return false;
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Removes all tags associated with the currently loaded row. Will return null if no row is currently loaded.
	 * If the optional $ps_user_id parameter is passed then only tags added by the specified user will be removed.
	 *
	 * @param $pn_user_id [integer] A valid ca_users.user_id value. If specified, only tags added by the specified user will be removed. (optional - default is null)
	 */
	public function removeAllTags($pn_user_id=null) {
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		
		$va_tags = $this->getTags($pn_user_id);
		
		foreach($va_tags as $va_tag) {
			if (!$this->removeTag($va_tag['tag_id'], $pn_user_id)) {
				return false;
			}
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns all tags associated with the currently loaded row. Will return null if not row is currently loaded.
	 * If the optional $ps_user_id parameter is passed then only tags created by the specified user will be returned.
	 * If the optional $pb_moderation_status parameter is passed then only tags matching the criteria will be returned:
	 *		Passing $pb_moderation_status = TRUE will cause only moderated tags to be returned
	 *		Passing $pb_moderation_status = FALSE will cause only unmoderated tags to be returned
	 *		If you want both moderated and unmoderated tags to be returned then omit the parameter or pass a null value
	 *
	 * @param $pn_user_id [integer] A valid ca_users.user_id value. If specified, only tags added by the specified user will be returned. (optional - default is null)
	 * @param $pn_moderation_status [boolean] To return only unmoderated tags set to FALSE; to return only moderated tags set to TRUE; to return all tags set to null or omit
	 */
	public function getTags($pn_user_id=null, $pb_moderation_status=null, $pn_row_id=null) {
		if (!($vn_row_id = $pn_row_id)) {
			if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		}
		$o_db = $this->getDb();
		
		$vs_user_sql = ($pn_user_id) ? ' AND (cixt.user_id = '.intval($pn_user_id).')' : '';
		
		$vs_moderation_sql = '';
		if (!is_null($pb_moderation_status)) {
			$vs_moderation_sql = ($pb_moderation_status) ? ' AND (cixt.moderated_on IS NOT NULL)' : ' AND (cixt.moderated_on IS NULL)';
		}
		
		$qr_comments = $o_db->query("
			SELECT *
			FROM ca_item_tags cit
			INNER JOIN ca_items_x_tags AS cixt ON cit.tag_id = cixt.tag_id
			WHERE
				(cixt.table_num = ?) AND (cixt.row_id = ?) {$vs_user_sql} {$vs_moderation_sql}
		", $this->tableNum(), $vn_row_id);
		
		return $qr_comments->getAllRows();
	}
	# --------------------------------------------------------------------------------------------
	# User commenting
	# --------------------------------------------------------------------------------------------
	/**
	 * Adds a comment to currently loaded row. Returns null if no row is loaded. Otherwise returns true
	 * if comment was successfully added, false if an error occurred in which case the errors will be available
	 * via the model's standard error methods (getErrors() and friends.
	 *
	 * Most of the parameters are optional with the exception of $ps_comment - the text of the comment. Note that 
	 * comment text is monolingual; if you want to do multilingual comments (which aren't really comments then, are they?) then
	 * you should add multiple comments.
	 *
	 * The parameters are:
	 *
	 * @param $ps_comment [string] Text of the comment (mandatory)
	 * @param $pn_rating [integer] A number between 1 and 5 indicating the user's rating of the row; larger is better (optional - default is null)
	 * @param $pn_user_id [integer] A valid ca_users.user_id indicating the user who posted the comment; is null for comments from non-logged-in users (optional - default is null)
	 * @param $pn_locale_id [integer] A valid ca_locales.locale_id indicating the language of the comment. If omitted or left null then the value in the global $g_ui_locale_id variable is used. If $g_ui_locale_id is not set and $pn_locale_id is not set then an error will occur (optional - default is to use $g_ui_locale_id)
	 * @param $ps_name [string] Name of user posting comment. Only needs to be set if $pn_user_id is *not* set; used to identify comments posted by non-logged-in users (optional - default is null)
	 * @param $ps_email [string] E-mail address of user posting comment. Only needs to be set if $pn_user_id is *not* set; used to identify comments posted by non-logged-in users (optional - default is null)
	 * @param $pn_access [integer] Determines public visibility of comments; if set to 0 then comment is not visible to public; if set to 1 comment is visible (optional - default is 0)
	 * @param $pn_moderator [integer] A valid ca_users.user_id value indicating who moderated the comment; if omitted or set to null then moderation status will not be set unless app.conf setting dont_moderate_comments = 1 (optional - default is null)
	 */
	public function addComment($ps_comment, $pn_rating=null, $pn_user_id=null, $pn_locale_id=null, $ps_name=null, $ps_email=null, $pn_access=0, $pn_moderator=null) {
		global $g_ui_locale_id;
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		if (!$pn_locale_id) { $pn_locale_id = $g_ui_locale_id; }
		
		$t_comment = new ca_item_comments();
		$t_comment->setMode(ACCESS_WRITE);
		$t_comment->set('table_num', $this->tableNum());
		$t_comment->set('row_id', $vn_row_id);
		$t_comment->set('user_id', $pn_user_id);
		$t_comment->set('locale_id', $pn_locale_id);
		$t_comment->set('comment', $ps_comment);
		$t_comment->set('rating', $pn_rating);
		$t_comment->set('email', $ps_email);
		$t_comment->set('name', $ps_name);
		$t_comment->set('access', $pn_access);
		
		if (!is_null($pn_moderator)) {
			$t_comment->set('moderated_by_user_id', $pn_moderator);
			$t_comment->set('moderated_on', 'now');
		}elseif($this->_CONFIG->get("dont_moderate_comments")){
			$t_comment->set('moderated_on', 'now');
		}
		
		$t_comment->insert();
		
		if ($t_comment->numErrors()) {
			$this->errors = $t_comment->errors;
			return false;
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Edits an existing comment as specified by $pn_comment_id. Will only edit comments that are attached to the 
	 * currently loaded row. If called with no row loaded editComment() will return null. If you attempt to modify
	 * a comment not associated with the currently loaded row editComment() will return false and post an error.
	 * Note that all parameters are mandatory in the sense that the value passed (or the default value if not passed)
	 * will be written into the comment. For example, if you don't bother passing $ps_name then it will be set to null, even
	 * if there's an existing name value in the field. The only exception is $pn_locale_id; if set to null or omitted then 
	 * editComment() will attempt to use the locale value in the global $g_ui_locale_id variable. If this is not set then
	 * an error will be posted and editComment() will return false.
	 *
	 * The parameters are:
	 *
	 * @param $pn_comment_id [integer] a valid comment_id to be edited; must be related to the currently loaded row (mandatory)
	 * @param $ps_comment [string] the text of the comment (mandatory)
	 * @param $pn_rating [integer] a number between 1 and 5 indicating the user's rating of the row; higher is better (optional - default is null)
	 * @param $pn_user_id [integer] A valid ca_users.user_id indicating the user who posted the comment; is null for comments from non-logged-in users (optional - default is null)
	 * @param $pn_locale_id [integer] A valid ca_locales.locale_id indicating the language of the comment. If omitted or left null then the value in the global $g_ui_locale_id variable is used. If $g_ui_locale_id is not set and $pn_locale_id is not set then an error will occur (optional - default is to use $g_ui_locale_id)
	 * @param $ps_name [string] Name of user posting comment. Only needs to be set if $pn_user_id is *not* set; used to identify comments posted by non-logged-in users (optional - default is null)
	 * @param $ps_email [string] E-mail address of user posting comment. Only needs to be set if $pn_user_id is *not* set; used to identify comments posted by non-logged-in users (optional - default is null)
	 * @param $pn_access [integer] Determines public visibility of comments; if set to 0 then comment is not visible to public; if set to 1 comment is visible (optional - default is 0)
	 * @param $pn_moderator [integer] A valid ca_users.user_id value indicating who moderated the comment; if omitted or set to null then moderation status will not be set (optional - default is null)
	 */
	public function editComment($pn_comment_id, $ps_comment, $pn_rating=null, $pn_user_id=null, $pn_locale_id=null, $ps_name=null, $ps_email=null, $pn_access=null, $pn_moderator=null) {
		global $g_ui_locale_id;
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		if (!$pn_locale_id) { $pn_locale_id = $g_ui_locale_id; }
		
		$t_comment = new ca_item_comments($pn_comment_id);
		if (!$t_comment->getPrimaryKey()) {
			$this->postError(2800, _t('Comment id is invalid'), 'BaseModel->editComment()');
			return false;
		}
		if (
			($t_comment->get('table_num') != $this->tableNum()) ||
			($t_comment->get('row_id') != $vn_row_id)
		) {
			$this->postError(2810, _t('Comment is not part of the current row'), 'BaseModel->editComment()');
			return false;
		}
		
		$t_comment->setMode(ACCESS_WRITE);
		
		$this->set('comment', $ps_comment);
		$this->set('rating', $pn_rating);
		$this->set('user_id', $pn_user_id);
		$this->set('name', $ps_name);
		$this->set('email', $ps_email);
		
		if (!is_null($pn_moderator)) {
			$this->set('moderated_by_user_id', $pn_moderator);
			$this->set('moderated_on', 'now');
		}
		
		if (!is_null($pn_locale_id)) { $this->set('locale_id', $pn_locale_id); }
		
		$t_comment->update();
		if ($t_comment->numErrors()) {
			$this->errors = $t_comment->errors;
			return false;
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Permanently deletes the comment specified by $pn_comment_id. Will only delete comments attached to the
	 * currently loaded row. If you attempt to delete a comment_id not attached to the current row removeComment()
	 * will return false and post an error. If you attempt to call removeComment() with no row loaded null will be returned.
	 * If $pn_user_id is specified then only comments created by the specified user will be deleted; if the comment being
	 * deleted is not created by the user then false is returned and an error posted.
	 *
	 * @param $pn_comment_id [integer] a valid comment_id to be removed; must be related to the currently loaded row (mandatory)
	 * @param $pn_user_id [integer] a valid ca_users.user_id value; if specified then only comments by the specified user will be deleted (optional - default is null)
	 */
	public function removeComment($pn_comment_id, $pn_user_id=null) {
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		
		$t_comment = new ca_item_comments($pn_comment_id);
		if (!$t_comment->getPrimaryKey()) {
			$this->postError(2800, _t('Comment id is invalid'), 'BaseModel->removeComment()');
			return false;
		}
		if (
			($t_comment->get('table_num') != $this->tableNum()) ||
			($t_comment->get('row_id') != $vn_row_id)
		) {
			$this->postError(2810, _t('Comment is not part of the current row'), 'BaseModel->removeComment()');
			return false;
		}
		
		if ($pn_user_id) {
			if ($t_comment->get('user_id') != $pn_user_id) {
				$this->postError(2820, _t('Comment was not created by specified user'), 'BaseModel->removeComment()');
				return false;
			}
		}
		
		$t_comment->setMode(ACCESS_WRITE);
		$t_comment->delete();
		
		if ($t_comment->numErrors()) {
			$this->errors = $t_comment->errors;
			return false;
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Removes all comments associated with the currently loaded row. Will return null if no row is currently loaded.
	 * If the optional $ps_user_id parameter is passed then only comments created by the specified user will be removed.
	 *
	 * @param $pn_user_id [integer] A valid ca_users.user_id value. If specified, only comments by the specified user will be removed. (optional - default is null)
	 */
	public function removeAllComments($pn_user_id=null) {
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		
		$va_comments = $this->getComments($pn_user_id);
		
		foreach($va_comments as $va_comment) {
			if (!$this->removeComment($va_comment['comment_id'], $pn_user_id)) {
				return false;
			}
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns all comments associated with the currently loaded row. Will return null if not row is currently loaded.
	 * If the optional $ps_user_id parameter is passed then only comments created by the specified user will be returned.
	 * If the optional $pb_moderation_status parameter is passed then only comments matching the criteria will be returned:
	 *		Passing $pb_moderation_status = TRUE will cause only moderated comments to be returned
	 *		Passing $pb_moderation_status = FALSE will cause only unmoderated comments to be returned
	 *		If you want both moderated and unmoderated comments to be returned then omit the parameter or pass a null value
	 *
	 * @param $pn_user_id [integer] A valid ca_users.user_id value. If specified, only comments by the specified user will be returned. (optional - default is null)
	 * @param $pn_moderation_status [boolean] To return only unmoderated comments set to FALSE; to return only moderated comments set to TRUE; to return all comments set to null or omit
	 */
	public function getComments($pn_user_id=null, $pb_moderation_status=null) {
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDb();
		
		$vs_user_sql = ($pn_user_id) ? ' AND (user_id = '.intval($pn_user_id).')' : '';
		
		$vs_moderation_sql = '';
		if (!is_null($pb_moderation_status)) {
			$vs_moderation_sql = ($pb_moderation_status) ? ' AND (ca_item_comments.moderated_on IS NOT NULL)' : ' AND (ca_item_comments.moderated_on IS NULL)';
		}
		
		$qr_comments = $o_db->query("
			SELECT *
			FROM ca_item_comments
			WHERE
				(table_num = ?) AND (row_id = ?) {$vs_user_sql} {$vs_moderation_sql}
		", $this->tableNum(), $vn_row_id);
		
		return $qr_comments->getAllRows();
	}
	# --------------------------------------------------------------------------------------------
	/** 
	 * Returns average user rating of item
	 */ 
	public function getAverageRating($pb_moderation_status=true) {
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
	
		$vs_moderation_sql = '';
		if (!is_null($pb_moderation_status)) {
			$vs_moderation_sql = ($pb_moderation_status) ? ' AND (ca_item_comments.moderated_on IS NOT NULL)' : ' AND (ca_item_comments.moderated_on IS NULL)';
		}
		
		$o_db = $this->getDb();
		$qr_comments = $o_db->query("
			SELECT avg(rating) r
			FROM ca_item_comments
			WHERE
				(table_num = ?) AND (row_id = ?) {$vs_moderation_sql}
		", $this->tableNum(), $vn_row_id);
		
		if ($qr_comments->nextRow()) {
			return round($qr_comments->get('r'));
		} else {
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	/** 
	 * Returns number of user ratings for item
	 */ 
	public function getNumRatings($pb_moderation_status=true) {
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
	
		$vs_moderation_sql = '';
		if (!is_null($pb_moderation_status)) {
			$vs_moderation_sql = ($pb_moderation_status) ? ' AND (ca_item_comments.moderated_on IS NOT NULL)' : ' AND (ca_item_comments.moderated_on IS NULL)';
		}
		
		$o_db = $this->getDb();
		$qr_ratings = $o_db->query("
			SELECT count(*) c
			FROM ca_item_comments
			WHERE
				(rating > 0) AND (table_num = ?) AND (row_id = ?) {$vs_moderation_sql}
		", $this->tableNum(), $vn_row_id);
		
		if ($qr_ratings->nextRow()) {
			return round($qr_ratings->get('c'));
		} else {
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Return the highest rated item(s)
	 * Return an array of primary key values
	 */
	public function getHighestRated($pb_moderation_status=true, $pn_num_to_return=1, $va_access_values = array()) {
		$vs_moderation_sql = '';
		if (!is_null($pb_moderation_status)) {
			$vs_moderation_sql = ($pb_moderation_status) ? ' AND (ca_item_comments.moderated_on IS NOT NULL)' : ' AND (ca_item_comments.moderated_on IS NULL)';
		}
		$vs_access_join = "";
		$vs_access_where = "";
		if (isset($va_access_values) && is_array($va_access_values) && sizeof($va_access_values) && $this->hasField('access')) {		
			$vs_table_name = $this->tableName();
			$vs_primary_key = $this->PrimaryKey();
			if ($vs_table_name && $vs_primary_key) {
				$vs_access_join = 'INNER JOIN '.$vs_table_name.' as rel ON rel.'.$vs_primary_key." = ca_item_comments.row_id ";
				$vs_access_where = ' AND rel.access IN ('.join(',', $va_access_values).')';
			}
		}
		$o_db = $this->getDb();
		$qr_comments = $o_db->query("
			SELECT ca_item_comments.row_id
			FROM ca_item_comments
			{$vs_access_join}
			WHERE
				(ca_item_comments.table_num = ?)
				{$vs_moderation_sql}
				{$vs_access_where}
			GROUP BY
				ca_item_comments.row_id
			ORDER BY
				avg(ca_item_comments.rating) DESC, MAX(ca_item_comments.created_on) DESC
			LIMIT {$pn_num_to_return}
		", $this->tableNum());
		
		$va_row_ids = array();
		while ($qr_comments->nextRow()) {
			$va_row_ids[] = $qr_comments->get('row_id');
		}
		return $va_row_ids;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Return the number of ratings
	 * Return an integer count
	 */
	public function getRatingsCount($pb_moderation_status=true) {
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		$vs_moderation_sql = '';
		if (!is_null($pb_moderation_status)) {
			$vs_moderation_sql = ($pb_moderation_status) ? ' AND (ca_item_comments.moderated_on IS NOT NULL)' : ' AND (ca_item_comments.moderated_on IS NULL)';
		}
		
		$o_db = $this->getDb();
		$qr_comments = $o_db->query("
			SELECT count(*) c
			FROM ca_item_comments
			WHERE
				(ca_item_comments.table_num = ?) AND (ca_item_comments.row_id = ?)
				{$vs_moderation_sql}
		", $this->tableNum(), $vn_row_id);
		
		if ($qr_comments->nextRow()) {
			return $qr_comments->get('c');
		}
		return 0;
	}
	# --------------------------------------------------------------------------------------------
	/** 
	 * Increments the view count for this item
	 */ 
	public function registerItemView($pn_user_id=null) {
		global $g_ui_locale_id;
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		if (!$pn_locale_id) { $pn_locale_id = $g_ui_locale_id; }
		
		$t_view = new ca_item_views();
		$t_view->setMode(ACCESS_WRITE);
		$t_view->set('table_num', $this->tableNum());
		$t_view->set('row_id', $vn_row_id);
		$t_view->set('user_id', $pn_user_id);
		$t_view->set('locale_id', $pn_locale_id);
	
		$t_view->insert();
		
		if ($t_view->numErrors()) {
			$this->errors = $t_view->errors;
			return false;
		}
		
		$o_db = $this->getDb();
		
		// increment count
		$qr_res = $o_db->query("
			SELECT * 
			FROM ca_item_view_counts
			WHERE table_num = ? AND row_id = ?
		", $this->tableNum(), $vn_row_id);
		if ($qr_res->nextRow()) {
			$o_db->query("
				UPDATE ca_item_view_counts
				SET view_count = view_count + 1
				WHERE table_num = ? AND row_id = ? 
			", $this->tableNum(), $vn_row_id);
		} else {
			$o_db->query("
				INSERT INTO ca_item_view_counts
				(table_num, row_id, view_count)
				VALUES
				(?, ?, 1)
			", $this->tableNum(), $vn_row_id);
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	public function clearItemViewCount($pn_user_id=null) {
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		
		$o_db = $this->getDb();
		
		$vs_user_sql = '';
		if ($pn_user_id) {
			$vs_user_sql = " AND user_id = ".intval($pn_user_id);
		}
		
		$qr_res = $o_db->query("
			DELETE FROM ca_item_views
			WHERE table_num = ? AND row_id = ? {$vs_user_sql}
		", $this->tableNum(), $vn_row_id);
		
		$qr_res = $o_db->query("
			UPDATE ca_item_view_counts
			SET view_count = 0
			WHERE table_num = ? AND row_id = ? {$vs_user_sql}
		", $this->tableNum(), $vn_row_id);
		
		return $o_db->numErrors() ? true : false;
	}
	# --------------------------------------------------------------------------------------------
	public function getViewList($pn_user_id=null, $pa_options=null) {
		if (!($vn_row_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDb();
		
		$vs_limit_sql = '';
		if ($pn_limit > 0) {
			$vs_limit_sql = "LIMIT ".intval($pn_limit);
		}
		
		$va_wheres = array('(civc.table_num = ?)');
		if (is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && ($this->hasField('access'))) {
			$va_wheres[] = 't.access IN ('.join(',', $pa_options['checkAccess']).')';
		}
		
		$vs_join_sql = '';
		if (isset($pa_options['hasRepresentations']) && $pa_options['hasRepresentations'] && ($this->tableName() == 'ca_objects')) {
			$vs_join_sql = ' INNER JOIN ca_objects_x_object_representations ON ca_objects_x_object_representations.object_id = t.object_id';
			$va_wheres[] = 'ca_objects_x_object_representations.is_primary = 1';
		}
		
		if ($pn_user_id) {
			$va_wheres[] = "civ.user_id = ".intval($pn_user_id);
		}
		
		if ($vs_where_sql = join(' AND ', $va_wheres)) {
			$vs_where_sql = ' WHERE '.$vs_where_sql;
		}
		
		
		$qr_res = $o_db->query("
			SELECT t.*, count(*) cnt
			FROM ".$this->tableName()." t
			INNER JOIN ca_item_views AS civ ON civ.row_id = t.".$this->primaryKey()."
			WHERE
				civ.table_num = ? AND row_id = ? {$vs_user_sql} {$vs_access_sql}
			GROUP BY
				civ.row_id
			ORDER BY
				cnt DESC
			{$vs_limit_sql}
				
		", $this->tableNum(), $vn_row_id);
		
		$va_items = array();
		
		while($qr_res->nextRow()) {
			$va_items[] = $qr_res->getRow();
		}
		return $va_items;
	}
	# --------------------------------------------------------------------------------------------
	public function getMostViewedItems($pn_limit=10, $pa_options=null) {
		$o_db = $this->getDb();
		
		$vs_limit_sql = '';
		if ($pn_limit > 0) {
			$vs_limit_sql = "LIMIT ".intval($pn_limit);
		}
		
		$va_wheres = array('(civc.table_num = '.intval($this->tableNum()).')');
		if (is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && ($this->hasField('access'))) {
			$va_wheres[] = 't.access IN ('.join(',', $pa_options['checkAccess']).')';
		}
		
		$vs_join_sql = '';
		if (isset($pa_options['hasRepresentations']) && $pa_options['hasRepresentations'] && ($this->tableName() == 'ca_objects')) {
			$vs_join_sql = ' INNER JOIN ca_objects_x_object_representations ON ca_objects_x_object_representations.object_id = t.object_id';
			$va_wheres[] = 'ca_objects_x_object_representations.is_primary = 1';
		}
		
		if ($vs_where_sql = join(' AND ', $va_wheres)) {
			$vs_where_sql = ' WHERE '.$vs_where_sql;
		}
		
		
		$qr_res = $o_db->query("
			SELECT t.*, civc.view_count cnt
 			FROM ".$this->tableName()." t
 			INNER JOIN ca_item_view_counts AS civc ON civc.row_id = t.".$this->primaryKey()."
 			{$vs_join_sql}
			{$vs_where_sql}
			ORDER BY
				civc.view_count DESC
			{$vs_limit_sql}
		");
		
		$va_most_viewed_items = array();
		
		while($qr_res->nextRow()) {
			$va_most_viewed_items[$qr_res->get($this->primaryKey())] = $qr_res->getRow();
		}
		return $va_most_viewed_items;
	}
	# --------------------------------------------------------------------------------------------
	public function getRecentlyAddedItems($pn_limit=10, $pa_options=null) {
		$o_db = $this->getDb();
		
		$vs_limit_sql = '';
		if ($pn_limit > 0) {
			$vs_limit_sql = "LIMIT ".intval($pn_limit);
		}
		
		$va_wheres = array();
		if (is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && ($this->hasField('access'))) {
			$va_wheres[] = 't.access IN ('.join(',', $pa_options['checkAccess']).')';
		}
		
		$vs_join_sql = '';
		if (isset($pa_options['hasRepresentations']) && $pa_options['hasRepresentations'] && ($this->tableName() == 'ca_objects')) {
			$vs_join_sql = ' INNER JOIN ca_objects_x_object_representations ON ca_objects_x_object_representations.object_id = t.object_id';
			$va_wheres[] = 'ca_objects_x_object_representations.is_primary = 1';
		}
		
		if ($vs_where_sql = join(' AND ', $va_wheres)) {
			$vs_where_sql = ' WHERE '.$vs_where_sql;
		}
		
		$vs_primary_key = $this->primaryKey();
		
		$qr_res = $o_db->query("
			SELECT t.*
			FROM ".$this->tableName()." t
			{$vs_join_sql}
			{$vs_where_sql}
			ORDER BY
				t.".$vs_primary_key." DESC
			{$vs_limit_sql}
		");
		
		$va_recently_added_items = array();
		
		while($qr_res->nextRow()) {
			$va_recently_added_items[$qr_res->get($this->primaryKey())] = $qr_res->getRow();
		}
		return $va_recently_added_items;
	}
	# --------------------------------------------------------------------------------------------
	/** 
	 * Return set of random rows (up to $pn_limit) subject to access restriction in $pn_access
	 * Set $pn_access to null or omit to return items regardless of access control status
	 */
	public function getRandomItems($pn_limit=10, $pa_options=null) {
		$o_db = $this->getDb();
		
		$vs_limit_sql = '';
		if ($pn_limit > 0) {
			$vs_limit_sql = "LIMIT ".intval($pn_limit);
		}
		
		$vs_primary_key = $this->primaryKey();
		$vs_table_name = $this->tableName();
		
		$vs_access_sql = '';
		if (is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && ($this->hasField('access'))) {
			$vs_access_sql = ' WHERE '.$vs_table_name.'.access IN ('.join(',', $pa_options['checkAccess']).')';
		}
		
		$vs_join_sql = '';
		if (isset($pa_options['hasRepresentations']) && $pa_options['hasRepresentations'] && ($this->tableName() == 'ca_objects')) {
			$vs_join_sql = ' INNER JOIN ca_objects_x_object_representations ON ca_objects_x_object_representations.object_id = '.$vs_table_name.'.object_id';
		}
		
		$vs_sql = "
			SELECT {$vs_table_name}.* 
			FROM (
				SELECT {$vs_table_name}.{$vs_primary_key} FROM {$vs_table_name}
				{$vs_join_sql}
				{$vs_access_sql}
				ORDER BY RAND() 
				{$vs_limit_sql}
			) AS random_items 
			INNER JOIN {$vs_table_name} ON {$vs_table_name}.{$vs_primary_key} = random_items.{$vs_primary_key}	
		";
		$qr_res = $o_db->query($vs_sql);
		
		$va_random_items = array();
		
		while($qr_res->nextRow()) {
			$va_random_items[$qr_res->get($this->primaryKey())] = $qr_res->getRow();
		}
		return $va_random_items;
	}
	# --------------------------------------------------------------------------------------------
	# Change log display
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns change log for currently loaded row in displayable HTML format
	 */ 
	public function getChangeLogForDisplay($ps_css_id=null) {
		$o_log = new ApplicationChangeLog();
		
		return $o_log->getChangeLogForRowForDisplay($this, $ps_css_id);
	}
	# --------------------------------------------------------------------------------------------
	#
	# --------------------------------------------------------------------------------------------
	/**
	 *
	 */
	static public function getLoggingUnitID() {
		return md5(getmypid().microtime());
	}
	# --------------------------------------------------------------------------------------------
	/**
	 *
	 */
	static public function getCurrentLoggingUnitID() {
		global $g_change_log_unit_id;
		return $g_change_log_unit_id;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 *
	 */
	static public function setChangeLogUnitID() {
		global $g_change_log_unit_id;
		
		if (!$g_change_log_unit_id) {
			$g_change_log_unit_id = BaseModel::getLoggingUnitID();
			return true;
		}
		return false;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 *
	 */
	static public function unsetChangeLogUnitID() {
		global $g_change_log_unit_id;
		
		$g_change_log_unit_id = null;
			
		return true;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 *
	 */
	public function getCount($pa_access=null) {
		$o_db = new Db();
		
		$vs_access_sql = '';
		
		if (is_array($pa_access) && sizeof($pa_access) && $this->hasField('access')) {
			$vs_access_sql = "WHERE access IN (".join(',', $pa_access).")";
		}
		
		$qr_res = $o_db->query("
			SELECT count(*) c
			FROM ".$this->tableName()."
			{$vs_access_sql}
		");
		
		if ($qr_res->nextRow()) {
			return $qr_res->get('c');
		}
		return null;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Destructor
	 */
	public function __destruct() {
		//print "Destruct ".$this->tableName()."\n";
		//print (memory_get_usage()/1024)." used in ".$this->tableName()." destructor\n";
		unset($this->o_db);
		unset($this->_CONFIG);
		unset($this->_DATAMODEL);
		unset($this->_MEDIA_VOLUMES);
		unset($this->_FILE_VOLUMES);
		unset($this->opo_app_plugin_manager);
		unset($this->_TRANSACTION);
		
		parent::__destruct();
	}
	# --------------------------------------------------------------------------------------------
}

// includes for which BaseModel must already be defined
require_once(__CA_LIB_DIR__."/core/TaskQueue.php");
require_once(__CA_APP_DIR__.'/models/ca_lists.php');
require_once(__CA_APP_DIR__.'/models/ca_locales.php');
require_once(__CA_APP_DIR__.'/models/ca_item_tags.php');
require_once(__CA_APP_DIR__.'/models/ca_items_x_tags.php');
require_once(__CA_APP_DIR__.'/models/ca_item_comments.php');
require_once(__CA_APP_DIR__.'/models/ca_item_views.php');
?>
