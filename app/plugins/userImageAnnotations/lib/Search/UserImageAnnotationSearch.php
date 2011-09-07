<?php


include_once(__CA_LIB_DIR__."/ca/Search/BaseSearch.php");
include_once(__CA_APP_DIR__."/plugins/userImageAnnotations/lib/Search/UserImageAnnotationSearchResult.php");

class UserImageAnnotationSearch extends BaseSearch {
	# ----------------------------------------------------------------------
	/**
	 * Which table does this class represent?
	 */
	protected $ops_tablename = "ca_user_annotations";
	protected $ops_primary_key = "user_annotation_id";
	# ----------------------------------------------------------------------
	public function __construct() {
		parent::__construct();
	}
	# ----------------------------------------------------------------------
	public function &search($ps_search, $pa_options=null) {
		return parent::search($ps_search, new UserImageAnnotationSearchResult(), $pa_options);
	}
	# ----------------------------------------------------------------------
}