<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Search/BaseSearch.php : Base class for ca_* searches
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
  
include_once(__CA_LIB_DIR__."/core/Search/SearchEngine.php");
 
	class BaseSearch extends SearchEngine {
		# -------------------------------------------------------
		
		# -------------------------------------------------------
		public function __construct() {
			parent::__construct();
		}
		# -------------------------------------------------------
		public function &search($ps_search, $po_results, $pa_options=null) {
			if (isset($pa_options['appendToSearch']) && !empty($pa_options['appendToSearch'])) {
				$appendToSearch = trim($pa_options['appendToSearch']);
				if(!(strpos(strtolower($appendToSearch), "and") === 0) && !(strpos(strtolower($appendToSearch), "or") === 0)) {
					$appendToSearch = "AND ".$appendToSearch;
				}
				$ps_search = "{$ps_search} {$appendToSearch}";
			}
	
			return parent::search($ps_search, $po_results, $pa_options);
		}
		# -------------------------------------------------------
	}
?>