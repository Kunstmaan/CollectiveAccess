<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Search/StorageLocationSearchResult.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

include_once(__CA_LIB_DIR__."/ca/Search/BaseSearchResult.php");

class StorageLocationSearchResult extends BaseSearchResult {
	
	/**
	 * Name of labels table for this type of search subject (eg. for ca_objects, the label table is ca_object_labels)
	 */
	protected $ops_label_table_name = 'ca_storage_location_labels';
	
	/**
	 * Name of field in labels table to use for display for this type of search subject (eg. for ca_objects, the label display field is 'name')
	 */
	protected $ops_label_display_field = 'name';


	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
	}
}