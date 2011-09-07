<?php

include_once(__CA_LIB_DIR__."/ca/Search/BaseSearchResult.php");

class UserImageAnnotationSearchResult extends BaseSearchResult {

	/**
	 * Name of labels table for this type of search subject (eg. for ca_objects, the label table is ca_object_labels)
	 */
	protected $ops_label_table_name = 'ca_user_annotations';

	/**
	 * Name of field in labels table to use for display for this type of search subject (eg. for ca_objects, the label display field is 'name')
	 */
	protected $ops_label_display_field = 'annotation';


	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
	}
}