<?php
/** ---------------------------------------------------------------------
 * app/models/ca_object_representations.php : table access class for table ca_object_representations
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
require_once(__CA_LIB_DIR__."/ca/IBundleProvider.php");
require_once(__CA_LIB_DIR__."/ca/BundlableLabelableBaseModelWithAttributes.php");
require_once(__CA_MODELS_DIR__."/ca_object_representation_labels.php");
require_once(__CA_MODELS_DIR__."/ca_representation_annotations.php");
require_once(__CA_MODELS_DIR__."/ca_representation_annotation_labels.php");
require_once(__CA_MODELS_DIR__."/ca_object_representation_multifiles.php");


BaseModel::$s_ca_models_definitions['ca_object_representations'] = array(
 	'NAME_SINGULAR' 	=> _t('object representation'),
 	'NAME_PLURAL' 		=> _t('object representations'),
 	'FIELDS' 			=> array(
 		'representation_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Representation id', 'DESCRIPTION' => 'Identifier for Representation'
		),
		'locale_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'DISPLAY_FIELD' => array('ca_locales.name'),
				'LABEL' => _t('Locale'), 'DESCRIPTION' => _t('The locale from which the representation originates.')
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'DISPLAY_FIELD' => array('ca_list_items.item_value'),
				'DISPLAY_ORDERBY' => array('ca_list_items.item_value'),
				'IS_NULL' => false, 
				'LIST_CODE' => 'object_representation_types',
				'DEFAULT' => '',
				'LABEL' => _t('Type'), 'DESCRIPTION' => _t('Indicates the type of the representation. The type can only be set when creating a new representation and cannot be changed once the representation is saved.')
		),
		'media' => array(
				'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				
				"MEDIA_PROCESSING_SETTING" => 'ca_object_representations',
				
				'LABEL' => _t('Media to upload'), 'DESCRIPTION' => _t('Use this control to select media from your computer to upload.')
		),
		'media_metadata' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Media metadata'), 'DESCRIPTION' => _t('Media metadata')
		),
		'media_content' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Media content'), 'DESCRIPTION' => _t('Media content')
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
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if representation is accessible to the public or not. ')
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
				'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Indicates the current state of the representation.')
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('Sort order'),
		)
 	)
);

class ca_object_representations extends BundlableLabelableBaseModelWithAttributes implements IBundleProvider {
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
	protected $TABLE = 'ca_object_representations';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'representation_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('media');

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
	protected $ORDER_BY = array('media');

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
	protected $LOG_CHANGES_TO_SELF = true;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
		
		),
		"RELATED_TABLES" => array(
		
		)
	);
	
	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_object_representation_labels';
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = 'type_id';								// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = 'object_representation_types';		// list code (ca_lists.list_code) of list defining types for this table

	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'ObjectRepresentationSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'ObjectRepresentationSearchResult';
	
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
		$this->BUNDLES['ca_representation_annotations'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related annotations'));
		$this->BUNDLES['ca_list_items'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related vocabulary terms'));
	}
	# ------------------------------------------------------
	public function insert() {
		// reject is media is empty
		if ($this->mediaIsEmpty()) {
			$this->postError(2710, _t('No media was specified'), 'ca_object_representations->insert()');
			return false;
		}
		
		// do insert
		return parent::insert();
	}
	# ------------------------------------------------------
	public function update($pa_options=null) {
		return parent::update($pa_options);
	}
	# ------------------------------------------------------
	public function delete($pn_delete_related=false) {
		return parent::delete($pn_delete_related);
	}
	# ------------------------------------------------------
	/**
	 * Returns true if the media field is set to a non-empty file
	 **/
	private function mediaIsEmpty() {
		if ($vs_media_path = $this->get('media')) {
			if (file_exists($vs_media_path) && (filesize($vs_media_path) > 0)) {
				return false;
			}
		}
		// is it a URL?
		if ($this->_CONFIG->get('allow_fetching_of_media_from_remote_urls')) {
			if  (isURL($vs_media_path)) {
				return false;
			}
		}
		return true;
	}
	# ------------------------------------------------------
	# Annotations
	# ------------------------------------------------------
	/**
	 * Returns annotation type code for currently loaded representation
	 * Type codes are based upon the mimetype of the representation's media as defined in the annotation_types.conf file
	 * 
	 * If you pass the options $pn_representation_id parameter then the returned type is for the specified representation rather
	 * than the currently loaded one.
	 */
 	public function getAnnotationType($pn_representation_id=null) {
 		if (!$pn_representation_id) {
			$t_rep = $this;
		} else {
			$t_rep = new ca_object_representations($pn_representation_id);
		}
 		
 		$va_media_info = $t_rep->getMediaInfo('media');
 		if (!isset($va_media_info['INPUT'])) { return null; }
 		if (!isset($va_media_info['INPUT']['MIMETYPE'])) { return null; }
 		
 		$vs_mimetype = $va_media_info['INPUT']['MIMETYPE'];
 		
 		$o_type_config = Configuration::load($this->getAppConfig()->get('annotation_type_config'));
 		$va_mappings = $o_type_config->getAssoc('mappings');
 		
 		return $va_mappings[$vs_mimetype];
 	}
 	# ------------------------------------------------------
 	public function getAnnotationPropertyCoderInstance($ps_type) {
 		return ca_representation_annotations::getPropertiesCoderInstance($ps_type);
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns number of annotations attached to current representation
 	 *
 	 * @param array $pa_options Optional array of options. Supported options are:
 	 *			checkAccess - array of access codes to filter count by. Only annotations with an access value set to one of the specified values will be counted.
 	 * @return int Number of annotations
 	 */
 	public function getAnnotationCount($pa_options=null) {
 		if (!($vn_representation_id = $this->getPrimaryKey())) { return null; }
 		
 		if (!is_array($pa_options)) { $pa_options = array(); }
 		
 		if (!($o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType()))) {
 			// does not support annotations
 			return null;
 		}
 		
 		$vs_access_sql = '';
 		if (is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess'])) {
			$vs_access_sql = ' AND cra.access IN ('.join(',', $pa_options['checkAccess']).')';
		}
		
 		$o_db = $this->getDb();
 		
 		$qr_annotations = $o_db->query("
 			SELECT 	cra.annotation_id, cra.locale_id, cra.props, cra.representation_id, cra.user_id, cra.type_code, cra.access, cra.status
 			FROM ca_representation_annotations cra
 			WHERE
 				cra.representation_id = ? {$vs_access_sql}
 		", (int)$vn_representation_id);
 		
 		return $qr_annotations->numRows();
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns data for annotations attached to current representation
 	 *
 	 * @param array $pa_options Optional array of options. Supported options are:
 	 *			checkAccess - array of access codes to filter count by. Only annotations with an access value set to one of the specified values will be returned
 	 * @return array List of annotations attached to the current representation, key'ed on annotation_id. Value is an array will all values; annotation labels are returned in the current locale.
 	 */
 	public function getAnnotations($pa_options=null) {
 		if (!($vn_representation_id = $this->getPrimaryKey())) { return null; }
 		
 		if (!is_array($pa_options)) { $pa_options = array(); }
 		
 		if (!($o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType()))) {
 			// does not support annotations
 			return null;
 		}
 		$o_db = $this->getDb();
 		
 		$vs_access_sql = '';
 		if (is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess'])) {
			$vs_access_sql = ' AND cra.access IN ('.join(',', $pa_options['checkAccess']).')';
		}
 		
 		$qr_annotations = $o_db->query("
 			SELECT 	cra.annotation_id, cra.locale_id, cra.props, cra.representation_id, cra.user_id, cra.type_code, cra.access, cra.status
 			FROM ca_representation_annotations cra
 			WHERE
 				cra.representation_id = ? {$vs_access_sql}
 		", (int)$vn_representation_id);
 		
 		$vs_sort_by_property = $this->getAnnotationSortProperty();
 		$va_annotations = array();
 		while($qr_annotations->nextRow()) {
 			$va_tmp = $qr_annotations->getRow();
 			$o_coder->setPropertyValues($qr_annotations->getVars('props'));
 			foreach($o_coder->getPropertyList() as $vs_property) {
 				$va_tmp[$vs_property] = $o_coder->getProperty($vs_property);
 				$va_tmp[$vs_property.'_raw'] = $o_coder->getProperty($vs_property, true);
 			}
 			
 			if (!($vs_sort_key = $va_tmp[$vs_sort_by_property])) {
 				$vs_sort_key = '_default_';
 			}
 			
 			$va_annotations[$vs_sort_key][$qr_annotations->get('annotation_id')] = $va_tmp;
 		}
 		
 		// get annotation labels
 		$qr_annotation_labels = $o_db->query("
 			SELECT 	cral.annotation_id, cral.locale_id, cral.name, cral.label_id
 			FROM ca_representation_annotation_labels cral
 			INNER JOIN ca_representation_annotations AS cra ON cra.annotation_id = cral.annotation_id
 			WHERE
 				cra.representation_id = ? AND cral.is_preferred = 1
 		", (int)$vn_representation_id);
 		
 		$va_labels = array();
 		while($qr_annotation_labels->nextRow()) {
 			$va_labels[$qr_annotation_labels->get('annotation_id')][$qr_annotation_labels->get('locale_id')] = $qr_annotation_labels->get('name');
 		}
 		
 		if (!isset($pa_options['dontExtraValuesByUserLocale']) || !$pa_options['dontExtraValuesByUserLocale']) {
 			$va_labels = caExtractValuesByUserLocale($va_labels);
 		}
 		
 		ksort($va_annotations, SORT_REGULAR);
 		$va_sorted_annotations = array();
 		foreach($va_annotations as $vs_key => $va_values) {
 			foreach($va_values as $va_val) {
 				$va_val['labels'] = $va_labels[$va_val['annotation_id']] ? $va_labels[$va_val['annotation_id']] : array();
 				$va_sorted_annotations[$va_val['annotation_id']] = $va_val;
 			}
 		}
 		return $va_sorted_annotations;
 	} 
 	# ------------------------------------------------------
 	public function addAnnotation($pn_locale_id, $pn_user_id, $pa_properties, $pn_status, $pn_access) {
 		if (!($vn_representation_id = $this->getPrimaryKey())) { return null; }
 		
 		if (!($o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType()))) {
 			// does not support annotations
 			return null;
 		}
 		
 		foreach($o_coder->getPropertyList() as $vs_property) {
			if (!$o_coder->setProperty($vs_property, $pa_properties[$vs_property])) {
				// error setting values
				$this->errors = $o_coder->errors;
				return false;
			}
		}
		
		if (!$o_coder->validate()) {
			$this->errors = $o_coder->errors;
			return false;
		}
 		
 		$t_annotation = new ca_representation_annotations();
 		$t_annotation->setMode(ACCESS_WRITE);
 		
 		$t_annotation->set('representation_id', $vn_representation_id);
 		$t_annotation->set('type_code', $o_coder->getType());
 		$t_annotation->set('locale_id', $pn_locale_id);
 		$t_annotation->set('user_id', $pn_user_id);
 		$t_annotation->set('status', $pn_status);
 		$t_annotation->set('access', $pn_access);
 		
 		$t_annotation->insert();
 		
 		if ($t_annotation->numErrors()) {
			$this->errors = $t_annotation->errors;
			return false;
		}
		foreach($o_coder->getPropertyList() as $vs_property) {
			$t_annotation->setPropertyValue($vs_property, $o_coder->getProperty($vs_property));
		}
		
		$t_annotation->update();
 		
 		if ($t_annotation->numErrors()) {
			$this->errors = $t_annotation->errors;
			return false;
		}
		
 		
 		return $t_annotation->getPrimaryKey();
 	}
 	# ------------------------------------------------------
 	public function editAnnotation($pn_annotation_id, $pn_locale_id, $pa_properties, $pn_status, $pn_access) {
 		if (!($vn_representation_id = $this->getPrimaryKey())) { return null; }
 	
 		if (!($o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType()))) {
 			// does not support annotations
 			return null;
 		}
 		foreach($o_coder->getPropertyList() as $vs_property) {
			if (!$o_coder->setProperty($vs_property, $pa_properties[$vs_property])) {
				// error setting values
				$this->errors = $o_coder->errors;
				return false;
			}
		}
		
		if (!$o_coder->validate()) {
			$this->errors = $o_coder->errors;
			return false;
		}
		
 		$t_annotation = new ca_representation_annotations($pn_annotation_id);
 		if ($t_annotation->getPrimaryKey() && ($t_annotation->get('representation_id') == $vn_representation_id)) {
 			foreach($o_coder->getPropertyList() as $vs_property) {
 				$t_annotation->setPropertyValue($vs_property, $o_coder->getProperty($vs_property));
 			}
 		
 			$t_annotation->setMode(ACCESS_WRITE);
 		
			$t_annotation->set('type_code', $o_coder->getType());
			$t_annotation->set('locale_id', $pn_locale_id);
			$t_annotation->set('status', $pn_status);
			$t_annotation->set('access', $pn_access);
			
			$t_annotation->update();
			if ($t_annotation->numErrors()) {
				$this->errors = $t_annotation->errors;
				return false;
			}
			
			return true;
 		}
 		
 		return false;
 	}
 	# ------------------------------------------------------
 	public function removeAnnotation($pn_annotation_id) {
 		if (!($vn_representation_id = $this->getPrimaryKey())) { return null; }
 		
 		$t_annotation = new ca_representation_annotations($pn_annotation_id);
 		if ($t_annotation->get('representation_id') == $vn_representation_id) {
 			$t_annotation->setMode(ACCESS_WRITE);
 			$t_annotation->delete(true);
 			
 			if ($t_annotation->numErrors()) {
 				$this->errors = $t_annotation->errors;
 				return false;
 			}
 			return true;
 		}
 		
 		return false;
 	}
 	# ------------------------------------------------------
 	#
 	# ------------------------------------------------------
 	/**
 	 * Return list of representations that are related to the object(s) this representation is related to
 	 */ 
 	public function getOtherRepresentationsInRelatedObjects() {
 		if (!($vn_representation_id = $this->getPrimaryKey())) { return null; }
 		
 		$o_db = $this->getDb();
 		
 		$qr_res = $o_db->query("
 			SELECT *
 			FROM ca_object_representations cor
 			INNER JOIN ca_objects_x_object_representations AS coxor ON cor.representation_id = coxor.representation_id
 			WHERE
 				coxor.object_id IN (
 					SELECT object_id
 					FROM ca_objects_x_object_representations 
 					WHERE 
 						representation_id = ?
 				)
 		", (int)$vn_representation_id);
 		
 		$va_reps = array();
 		while($qr_res->nextRow()) {
 			$va_reps[$qr_res->get('representation_id')] = $qr_res->getRow();
 		}
 		
 		return $va_reps;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Bundle generator - called from BundlableLabelableBaseModelWithAttributes::getBundleFormHTML()
 	 */
	protected function getRepresentationAnnotationHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
		//if (!$this->getAnnotationType()) { return; }	// don't show bundle if this representation doesn't support annotations
		
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		$t_item = new ca_representation_annotations();
		$t_item_label = new ca_representation_annotation_labels();
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
		$o_view->setVar('t_item', $t_item);
		$o_view->setVar('t_item_label', $t_item_label);
		
		$o_view->setVar('t_subject', $this);
		$o_view->setVar('settings', $pa_bundle_settings);
		
		$va_inital_values = array();
		if (sizeof($va_items = $this->getAnnotations(array('dontExtraValuesByUserLocale' => true)))) {
			$t_rel = $this->getAppDatamodel()->getInstanceByTableName('ca_representation_annotations', true);
			$vs_rel_pk = $t_rel->primaryKey();
			foreach ($va_items as $vn_id => $va_item) {
				if (!($vs_label = $va_item['labels'][$va_item['locale_id']])) { $vs_label = ''; }
				$va_inital_values[$va_item[$t_item->primaryKey()]] = array_merge($va_item, array('id' => $va_item[$vs_rel_pk], 'item_type_id' => $va_item['item_type_id'], 'relationship_type_id' => $va_item['relationship_type_id'], 'label' => $vs_label));
			}
		}
		
		$o_view->setVar('initialValues', $va_inital_values);
		
		return $o_view->render('ca_representation_annotations.php');
	}	
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	protected function _processRepresentationAnnotations($po_request, $ps_form_prefix, $ps_placement_code) {
 		$va_rel_items = $this->getAnnotations(array('dontExtraValuesByUserLocale' => true));
		$o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType());
		foreach($va_rel_items as $vn_id => $va_rel_item) {
			$this->clearErrors();
			if (strlen($vn_status = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_status_'.$va_rel_item['annotation_id'], pString))) {
				$vn_access = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_access_'.$va_rel_item['annotation_id'], pInteger);
				$vn_locale_id = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_locale_id_'.$va_rel_item['annotation_id'], pInteger);
				
				$va_properties = array();
				foreach($o_coder->getPropertyList() as $vs_property) {
					$va_properties[$vs_property] = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_'.$vs_property.'_'.$va_rel_item['annotation_id'], pString);
				}

				// edit annotation
				$this->editAnnotation($va_rel_item['annotation_id'], $vn_locale_id, $va_properties, $vn_status, $vn_access);
			
				if ($this->numErrors()) {
					$po_request->addActionErrors($this->errors(), 'ca_representation_annotations', $va_rel_item['annotation_id']);
				} else {
					// try to add/edit label
					if ($vs_label = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_label_'.$va_rel_item['annotation_id'], pString)) {
						$t_annotation = new ca_representation_annotations($va_rel_item['annotation_id']);
						if ($t_annotation->getPrimaryKey()) {
							$t_annotation->setMode(ACCESS_WRITE);
							
							$va_pref_labels = $t_annotation->getPreferredLabels(array($vn_locale_id), false);
							
							if (sizeof($va_pref_labels)) {
								// edit existing label
								foreach($va_pref_labels as $vn_annotation_dummy_id => $va_labels_by_locale) {
									foreach($va_labels_by_locale as $vn_locale_dummy_id => $va_labels) {
										$t_annotation->editLabel($va_labels[0]['label_id'], array('name' => $vs_label), $vn_locale_id, null, true);
									}
								}
							} else {
								// create new label
								$t_annotation->addLabel(array('name' => $vs_label), $vn_locale_id, null, true);
							}
							
							if ($t_annotation->numErrors()) {
								$po_request->addActionErrors($t_annotation->errors(), 'ca_representation_annotations', 'new_'.$vn_c);
							}
						}
					}
				}
			} else {
				// is it a delete key?
				$this->clearErrors();
				if (($po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_'.$va_rel_item['annotation_id'].'_delete', pInteger)) > 0) {
					// delete!
					$this->removeAnnotation($va_rel_item['annotation_id']);
					if ($this->numErrors()) {
						$po_request->addActionErrors($this->errors(), 'ca_representation_annotations', $va_rel_item['annotation_id']);
					}
				}
			}
		}
 		
 		// check for new annotations to add
 		foreach($_REQUEST as $vs_key => $vs_value ) {
			if (!preg_match('/^'.$ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_status_new_([\d]+)/', $vs_key, $va_matches)) { continue; }
			$vn_c = intval($va_matches[1]);
			if (strlen($vn_status = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_status_new_'.$vn_c, pString)) > 0) {
				$vn_access = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_access_new_'.$vn_c, pInteger);
				$vn_locale_id = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_locale_id_new_'.$vn_c, pInteger);
				
				$va_properties = array();
				foreach($o_coder->getPropertyList() as $vs_property) {
					$va_properties[$vs_property] = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_'.$vs_property.'_new_'.$vn_c, pString);
				}
				
				// create annotation
				$vn_annotation_id = $this->addAnnotation($vn_locale_id, $po_request->getUserID(), $va_properties, $vn_status, $vn_access);
				
				if ($this->numErrors()) {
					$po_request->addActionErrors($this->errors(), 'ca_representation_annotations', 'new_'.$vn_c);
				} else {
					// try to add label
					if ($vs_label = $po_request->getParameter($ps_placement_code.$ps_form_prefix.'_ca_representation_annotations_label_new_'.$vn_c, pString)) {
						$t_annotation = new ca_representation_annotations($vn_annotation_id);
						if ($t_annotation->getPrimaryKey()) {
							$t_annotation->setMode(ACCESS_WRITE);
							$t_annotation->addLabel(array('name' => $vs_label), $vn_locale_id, null, true);
							
							if ($t_annotation->numErrors()) {
								$po_request->addActionErrors($t_annotation->errors(), 'ca_representation_annotations', 'new_'.$vn_c);
							}
						}
					}
				}
			}
		}
		
		return true;
 	}
 	# ------------------------------------------------------
 	public function useBundleBasedAnnotationEditor() {
 		if (!($o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType()))) {
 			// does not support annotations
 			return false;
 		}
 		
 		return $o_coder->useBundleBasedAnnotationEditor();
 	}
 	# ------------------------------------------------------
 	public function getAnnotationSortProperty() {
 		if (!($o_coder = $this->getAnnotationPropertyCoderInstance($this->getAnnotationType()))) {
 			// does not support annotations
 			return false;
 		}
 		
 		return $o_coder->getAnnotationSortProperty();
 	}
 	# ------------------------------------------------------
 	# Annotation display
 	# ------------------------------------------------------
 	public function getDisplayMediaWithAnnotationsHTMLBundle($po_request, $ps_version, $pa_options=null) {
 		if (!is_array($pa_options)) { $pa_options = array(); }
 		$pa_options['poster_frame_url'] = $this->getMediaUrl('media', 'medium');
 		
 		if (!($vs_tag = $this->getMediaTag('media', $ps_version, $pa_options))) {
 			return '';
 		}
 		
 		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		$o_view->setVar('viewer_tag', $vs_tag);
		$o_view->setVar('annotations', $this->getAnnotations($pa_options));
		
		return $o_view->render('ca_object_representations_display_with_annotations.php', false);
 	}
 	# ------------------------------------------------------
 	# Multifiles
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function addFile($ps_filepath, $ps_resource_path='/', $pb_allow_duplicates=true) {
 		if(!$this->getPrimaryKey()) { return null; }
 		if (!trim($ps_resource_path)) { $ps_resource_path = '/'; }
 		
 		$t_multifile = new ca_object_representation_multifiles();
 		if (!$pb_allow_duplicates) {
 			if ($t_multifile->load(array('resource_path' => $ps_resource_path, 'representation_id' => $this->getPrimaryKey()))) {
 				return null;
 			}
 		}
 		$t_multifile->setMode(ACCESS_WRITE);
 		$t_multifile->set('representation_id', $this->getPrimaryKey());
 		$t_multifile->set('media', $ps_filepath);
 		$t_multifile->set('resource_path', $ps_resource_path);
 		
 		$t_multifile->insert();
 		
 		if ($t_multifile->numErrors()) {
 			$this->errors = array_merge($this->errors, $t_multifile->errors);
 			return false;
 		}
 		
 		return $t_multifile;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function removeFile($pn_multifile_id) {
 		if(!$this->getPrimaryKey()) { return null; }
 		
 		$t_multifile = new ca_object_representation_multifiles($pn_multifile_id);
 		
 		if ($t_multifile->get('representation_id') == $this->getPrimaryKey()) {
 			$t_multifile->setMode(ACCESS_WRITE);
 			$t_multifile->delete();
 			
			if ($t_multifile->numErrors()) {
				$this->errors = array_merge($this->errors, $t_multifile->errors);
				return false;
			}
		} else {
			$this->postError(2720, _t('File is not part of this representation'), 'ca_object_representations->removeFile()');
			return false;
		}
		return true;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function removeAllFiles() {
 		if(!$this->getPrimaryKey()) { return null; }
 		
 		$va_file_ids = array_keys($this->getFileList());
 		
 		foreach($va_file_ids as $vn_id) {
 			$this->removeFile($vn_id);
 			
 			if($this->numErrors()) {
 				return false;
 			}
 		}
 		
 		return true;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function getFileList($pn_representation_id=null, $pn_start=null, $pn_num_files=null) {
 		if(!($vn_representation_id = $pn_representation_id)) { 
 			if (!($vn_representation_id = $this->getPrimaryKey())) {
 				return null; 
 			}
 		}
 		
 		$vs_limit_sql = '';
 		if (!is_null($pn_start) && !is_null($pn_num_files)) {
 			if (($pn_start >= 0) && ($pn_num_files >= 1)) {
 				$vs_limit_sql = "LIMIT {$pn_start}, {$pn_num_files}";
 			}
 		}
 		
 		$o_db= $this->getDb();
 		$qr_res = $o_db->query("
 			SELECT *
 			FROM ca_object_representation_multifiles
 			WHERE
 				representation_id = ?
 			{$vs_limit_sql}
 		", (int)$vn_representation_id);
 		
 		$va_files = array();
 		while($qr_res->nextRow()) {
 			$va_files[$qr_res->get('multifile_id')] = $qr_res->getRow();
 			unset($va_files[$qr_res->get('multifile_id')]['media']);
 			$va_files[$qr_res->get('multifile_id')]['preview_tag'] = $qr_res->getMediaTag('media', 'preview');
 			$va_files[$qr_res->get('multifile_id')]['preview_url'] = $qr_res->getMediaUrl('media', 'preview');
 		}
 		return $va_files;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function getFileInstance($pn_multifile_id) {
 		if(!$this->getPrimaryKey()) { return null; }
 	
 		$t_multifile = new ca_object_representation_multifiles($pn_multifile_id);
 		
 		if ($t_multifile->get('representation_id') == $this->getPrimaryKey()) {
 			return $t_multifile;
 		}
 		return null;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function numFiles($pn_representation_id=null) { 		
 		if(!($vn_representation_id = $pn_representation_id)) { 
 			if (!($vn_representation_id = $this->getPrimaryKey())) {
 				return null; 
 			}
 		}
 		
 		$o_db= $this->getDb();
 		$qr_res = $o_db->query("
 			SELECT count(*) c
 			FROM ca_object_representation_multifiles
 			WHERE
 				representation_id = ?
 		", (int)$vn_representation_id);
 		
 		if($qr_res->nextRow()) {
 			return intval($qr_res->get('c'));
 		}
 		return 0;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Matching method to ca_objects::getRepresentations(), except this one only returns a single representation - the currently loaded one
 	 */
 	public function getRepresentations($pa_versions=null, $pa_version_sizes=null, $pa_options=null) {
 		if (!($vn_object_id = $this->getPrimaryKey())) { return null; }
 		if (!is_array($pa_options)) { $pa_options = array(); }
 		
 		if (!is_array($pa_versions)) { 
 			$pa_versions = array('preview170');
 		}
 		
 		$o_db = $this->getDb();
 		
 		$qr_reps = $o_db->query("
 			SELECT caor.representation_id, caor.media, caor.access, caor.status, l.name, caor.locale_id, caor.media_metadata, caor.type_id
 			FROM ca_object_representations caor
 			LEFT JOIN ca_locales AS l ON caor.locale_id = l.locale_id
 			WHERE
 				caor.representation_id = ? 
 				{$vs_is_primary_sql}
 				{$vs_access_sql}
 			ORDER BY
 				l.name ASC 
 		", (int)$this->getPrimaryKey());
 		
 		$va_reps = array();
 		while($qr_reps->nextRow()) {
 			$va_tmp = $qr_reps->getRow();
 			$va_tmp['tags'] = array();
 			$va_tmp['urls'] = array();
 			
 			$va_info = $qr_reps->getMediaInfo('media');
 			$va_tmp['info'] = array('original_filename' => $va_info['ORIGINAL_FILENAME']);
 			foreach ($pa_versions as $vs_version) {
 				if (is_array($pa_version_sizes) && isset($pa_version_sizes[$vs_version])) {
 					$vn_width = $pa_version_sizes[$vs_version]['width'];
 					$vn_height = $pa_version_sizes[$vs_version]['height'];
 				} else {
 					$vn_width = $vn_height = 0;
 				}
 				
 				if ($vn_width && $vn_height) {
 					$va_tmp['tags'][$vs_version] = $qr_reps->getMediaTag('media', $vs_version, array_merge($pa_options, array('viewer_width' => $vn_width, 'viewer_height' => $vn_height)));
 				} else {
 					$va_tmp['tags'][$vs_version] = $qr_reps->getMediaTag('media', $vs_version, $pa_options);
 				}
 				$va_tmp['urls'][$vs_version] = $qr_reps->getMediaUrl('media', $vs_version);
 				$va_tmp['paths'][$vs_version] = $qr_reps->getMediaPath('media', $vs_version);
 				$va_tmp['info'][$vs_version] = $qr_reps->getMediaInfo('media', $vs_version);
 				
 				$va_dimensions = array();
 				if (isset($va_tmp['info'][$vs_version]['WIDTH']) && isset($va_tmp['info'][$vs_version]['HEIGHT'])) {
					if (($vn_w = $va_tmp['info'][$vs_version]['WIDTH']) && ($vn_h = $va_tmp['info'][$vs_version]['WIDTH'])) {
						$va_dimensions[] = $va_tmp['info'][$vs_version]['WIDTH'].'p x '.$va_tmp['info'][$vs_version]['HEIGHT'].'p';
					}
				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['bitdepth']) && ($vn_depth = $va_tmp['info'][$vs_version]['PROPERTIES']['bitdepth'])) {
 					$va_dimensions[] = intval($vn_depth).' bpp';
 				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['colorspace']) && ($vs_colorspace = $va_tmp['info'][$vs_version]['PROPERTIES']['colorspace'])) {
 					$va_dimensions[] = $vs_colorspace;
 				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['resolution']) && is_array($va_resolution = $va_tmp['info'][$vs_version]['PROPERTIES']['resolution'])) {
 					if (isset($va_resolution['x']) && isset($va_resolution['y']) && $va_resolution['x'] && $va_resolution['y']) {
 						// TODO: units for resolution? right now assume pixels per inch
 						if ($va_resolution['x'] == $va_resolution['y']) {
 							$va_dimensions[] = $va_resolution['x'].'ppi';
 						} else {
 							$va_dimensions[] = $va_resolution['x'].'x'.$va_resolution['y'].'ppi';
 						}
 					}
 				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['duration']) && ($vn_duration = $va_tmp['info'][$vs_version]['PROPERTIES']['duration'])) {
 					$va_dimensions[] = sprintf("%4.1f", $vn_duration).'s';
 				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['pages']) && ($vn_pages = $va_tmp['info'][$vs_version]['PROPERTIES']['pages'])) {
 					$va_dimensions[] = $vn_pages.' '.(($vn_pages == 1) ? _t('page') : _t('pages'));
 				}
 				if (!isset($va_tmp['info'][$vs_version]['PROPERTIES']['filesize']) || !($vn_filesize = $va_tmp['info'][$vs_version]['PROPERTIES']['filesize'])) {
 					$vn_filesize = @filesize($qr_reps->getMediaPath('media', $vs_version));
 				}
 				if ($vn_filesize) {
 					$va_dimensions[] = sprintf("%4.1f", $vn_filesize/(1024*1024)).'mb';
 				}
 				$va_tmp['dimensions'][$vs_version] = join('; ', $va_dimensions);
 			}
 			
 				
			if (isset($va_info['INPUT']['FETCHED_FROM']) && ($vs_fetched_from_url = $va_info['INPUT']['FETCHED_FROM'])) {
				$va_tmp['fetched_from'] = $vs_fetched_from_url;
				$va_tmp['fetched_on'] = (int)$va_info['INPUT']['FETCHED_ON'];
			}
 			
 			$va_tmp['num_multifiles'] = $this->numFiles($this->get('representation_id'));
 			$va_reps[] = $va_tmp;
 		}
 		return $va_reps;
 	}
 	# ------------------------------------------------------
 	public function getVersionsForInformationService($versions = array()) {
 		$result = array();
 		foreach($versions as $version) {
			$result[$version]['url'] = $this->getMediaUrl('media', $version);
			$result[$version]['info'] = $this->getMediaInfo('media', $version);
			// windows media player (asf) puts some weird stuff in this, this doesn't work in the service because it isn't UTF-8
			$result[$version]['info']['PROPERTIES']['type_specific'] = array();
		}
		return $result;
 	}
 	# ------------------------------------------------------
 	public function getItemInformationForService($return_options = array()) {
		$result = array();

		if(isset($return_options['versions']) && is_array($return_options['versions']) && !empty($return_options['versions'])) {
			$representations_to_return = $return_options['versions'];
		} else {
			$representations_to_return = $this->getMediaVersions('media');
		}

		$result['versions'] = $this->getVersionsForInformationService($representations_to_return);
		$result['annotations'] = $this->getAnnotations();
		return $result;
 	}
 	# ------------------------------------------------------
}
?>