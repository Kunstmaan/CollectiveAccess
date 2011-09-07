<?php

require_once(__CA_LIB_DIR__.'/core/BaseModel.php');
require_once(__CA_MODELS_DIR__.'/ca_objects.php');

global $g_ca_models_definitions;
$g_ca_models_definitions['ca_user_annotations'] = array(
 	'NAME_SINGULAR' 	=> _t('user annotation'),
 	'NAME_PLURAL' 		=> _t('user annotations'),
 	'FIELDS' 			=> array(
 		'user_annotation_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN,
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => 'Unique id for this user annotation', 'DESCRIPTION' => 'Unique id for this user annotation'
		),
		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true,
				'DISPLAY_FIELD' => array('ca_users.lname', 'ca_users.fname'),
				'DEFAULT' => '',
				'LABEL' => _t('User'), 'DESCRIPTION' => _t('The user who created the annotation.')
		),
		'row_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => 'Row ID', 'DESCRIPTION' => 'Primary key value of the representation this annotation belongs to.'
		),
		'locale_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT,
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DISPLAY_FIELD' => array('ca_locales.name'),
				'DEFAULT' => '',
				'LABEL' => _t('Locale'), 'DESCRIPTION' => _t('The locale of the annotation.')
		),
		'original_top' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => 'Top coördinate of the annotation', 'DESCRIPTION' => 'The top coördinate of the annotation based on the original version.'
		),
		'original_left' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => 'Left coördinate of the annotation', 'DESCRIPTION' => 'The left coördinate of the annotation based on the original version.'
		),
		'original_width' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => 'The width of the annootation', 'DESCRIPTION' => 'The width of the annootation based on the original version.'
		),
		'original_height' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => 'Height of the annotation', 'DESCRIPTION' => 'The height of the annootation based on the original version.'
		),
		'annotation' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD,
				'DISPLAY_WIDTH' => 50, 'DISPLAY_HEIGHT' => 4,
				'IS_NULL' => true,
				'DEFAULT' => '',
				'LABEL' => _t('Annotation'), 'DESCRIPTION' => _t('Text of annotation.'),
				'BOUNDS_LENGTH' => array(0,65535)
		),
		'email' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD,
				'DISPLAY_WIDTH' => 50, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true,
				'DEFAULT' => '',
				'LABEL' => _t('E-mail address'), 'DESCRIPTION' => _t('E-mail address of annotator.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'name' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD,
				'DISPLAY_WIDTH' => 50, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true,
				'DEFAULT' => '',
				'LABEL' => _t('Name'), 'DESCRIPTION' => _t('Name of annotator.'),
				'BOUNDS_LENGTH' => array(0,255)
		),
		'created_on' => array(
				'FIELD_TYPE' => FT_TIMESTAMP, 'DISPLAY_TYPE' => DT_FIELD,
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => _t('Annotation creation date'), 'DESCRIPTION' => _t('The date and time the annotation was created.')
		),
		'ip_addr' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD,
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true,
				'DEFAULT' => '',
				'LABEL' => _t('IP address of annotator'), 'DESCRIPTION' => _t('The IP address of the annotator.'),
				'BOUNDS_LENGTH' => array(0,39)
		),
		'moderated_by_user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true,
				'DISPLAY_FIELD' => array('ca_users.lname', 'ca_users.fname'),
				'DEFAULT' => null,
				'LABEL' => _t('Moderator'), 'DESCRIPTION' => _t('The user who examined the annotation for validity and applicability.')
		),
		'moderated_on' => array(
				'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true,
				'DEFAULT' => null,
				'LABEL' => _t('Moderation date'), 'DESCRIPTION' => _t('The date and time the annotation was examined for validity and applicability.')
		)
 	)
);

class ca_user_annotations extends BaseModel {
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
	protected $TABLE = 'ca_user_annotations';

	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'user_annotation_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	#
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('name', 'email', 'original_top', 'original_left', 'original_height', 'original_width', 'annotation');

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
	protected $ORDER_BY = array('name');

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

	# ------------------------------------------------------
	# Labels
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = null;

	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = null;

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
	public function insert($pa_options=null) {
		$this->set('ip_addr', $_SERVER['REMOTE_ADDR']);
		return parent::insert($pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Marks the currently loaded row as moderated, setting the moderator as the $pn_user_id parameter and the moderated time as the current time.
	 * "Moderated" status indicates that the annotation has been reviewed for content; it does *not* indicate that the annotation is ok for publication only
	 * that is has been reviewed. The publication status is indicated by the value of the 'access' field.
	 *
	 * @param $pn_user_id [integer] Valid ca_users.user_id value indicating the user who moderated the comment.
	 */
	public function moderate($pn_user_id) {
		if (!$this->getPrimaryKey()) { return null; }
		$this->setMode(ACCESS_WRITE);
		$this->set('moderated_by_user_id', $pn_user_id);
		$this->set('moderated_on', 'now');
		return $this->update();
	}
	# ------------------------------------------------------
	public function getModeratedAnnotations() {
		$o_db = $this->getDb();

		$o_tep = new TimeExpressionParser();
		$qr_res = $o_db->query("
			SELECT cua.*, u.user_id, u.fname, u.lname, u.email user_email
			FROM ca_user_annotations cua
			LEFT JOIN ca_users AS u ON u.user_id = cua.user_id
			WHERE
				cua.moderated_by_user_id IS NOT NULL
		");

		$o_datamodel = $this->getAppDatamodel();

		$va_user_annotations = array();
		while($qr_res->nextRow()) {
			$vn_datetime = $qr_res->get('created_on');
			$o_tep->setUnixTimestamps($vn_datetime, $vn_datetime);

			$va_row = $qr_res->getRow();
			$va_row['created_on'] = $o_tep->getText();

			$t_representation = new ca_object_representations();
			if ($t_representation->load($qr_res->get('row_id'))) {
				$va_row['annotated_on'] = $t_representation;

		 		$qr_res2 = $o_db->query("
		 			SELECT object_id
		 			FROM ca_objects_x_object_representations
		 			WHERE
		 				representation_id = ?
		 		", $t_representation->getPrimaryKey());

		 		if($qr_res2->nextRow()) {
					$t_object = new ca_objects();
					if($t_object->load($qr_res2->get('object_id'))) {
						$va_row['annotated_object'] = $t_object->getLabelForDisplay(false).' ['.$t_object->get('idno').']';
					}
		 		}
			}

			$va_user_annotations[] = $va_row;
		}
		return $va_user_annotations;
	}
	# ------------------------------------------------------
	public function getUnmoderatedAnnotations() {
		$o_db = $this->getDb();

		$o_tep = new TimeExpressionParser();
		$qr_res = $o_db->query("
			SELECT cua.*, u.user_id, u.fname, u.lname, u.email user_email
			FROM ca_user_annotations cua
			LEFT JOIN ca_users AS u ON u.user_id = cua.user_id
			WHERE
				cua.moderated_by_user_id IS NULL
		");

		$o_datamodel = $this->getAppDatamodel();

		$va_user_annotations = array();
		while($qr_res->nextRow()) {
			$vn_datetime = $qr_res->get('created_on');
			$o_tep->setUnixTimestamps($vn_datetime, $vn_datetime);

			$va_row = $qr_res->getRow();
			$va_row['created_on'] = $o_tep->getText();

			$t_representation = new ca_object_representations();
			if ($t_representation->load($qr_res->get('row_id'))) {
				$va_row['annotated_on'] = $t_representation;

		 		$qr_res2 = $o_db->query("
		 			SELECT object_id
		 			FROM ca_objects_x_object_representations
		 			WHERE
		 				representation_id = ?
		 		", $t_representation->getPrimaryKey());

		 		if($qr_res2->nextRow()) {
					$t_object = new ca_objects();
					if($t_object->load($qr_res2->get('object_id'))) {
						$va_row['annotated_object'] = $t_object->getLabelForDisplay(false).' ['.$t_object->get('idno').']';
					}
		 		}
			}

			$va_user_annotations[] = $va_row;
		}
		return $va_user_annotations;
	}
	# ------------------------------------------------------
}
?>
