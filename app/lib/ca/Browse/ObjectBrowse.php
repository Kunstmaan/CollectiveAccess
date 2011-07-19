<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Browse/ObjectBrowse.php : ca_objects faceted browse
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
 * @subpackage Browse
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
  /**
  *
  */
  
 	require_once(__CA_LIB_DIR__.'/ca/Browse/BaseBrowse.php');
 	require_once(__CA_LIB_DIR__.'/ca/Browse/ObjectBrowseResult.php');
 
	class ObjectBrowse extends BaseBrowse {
		# ------------------------------------------------------
		/**
		 * Which table does this class represent?
		 */
		protected $ops_tablename = "ca_objects";
		protected $ops_primary_key = "object_id";
		# ----------------------------------------------------------------------
		public function __construct($pn_browse_id=null, $ps_context='') {
			parent::__construct($this->ops_tablename, $pn_browse_id, $ps_context);
		}
		# ------------------------------------------------------
		public function getResults($pa_options=null) {
			return parent::getResults(new ObjectBrowseResult(), $pa_options);
		}
		# ----------------------------------------------------------------------
	}
?>