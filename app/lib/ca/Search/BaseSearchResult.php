<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Search/BaseSearchResult.php : Base class for ca_* search results
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
  
 require_once(__CA_LIB_DIR__.'/core/Search/SearchResult.php');
 require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 require_once(__CA_MODELS_DIR__.'/ca_locales.php');
 
	class BaseSearchResult extends SearchResult {
		# -------------------------------------------------------
		private $opo_list = null;
		private $opo_datamodel = null;
		private $opa_locales = null;
		# -------------------------------------------------------
		public function __construct() {
			parent::__construct();
			$this->opo_list = new ca_lists();
			$this->opo_datamodel = Datamodel::load();
			
			$t_locale = new ca_locales();
			$this->opa_locales = $t_locale->getLocaleList();
		}
		# -------------------------------------------------------
		/**
		 * Returns label(s) from current row ready for display (ie. in the current users locale)
		 *
		 * @param bool $pb_has_preferred_flag If set then only preferred label is returned, otherwise all labels for the users locale are returned. Default is true.
		 * @return array List of labels ready for display
		 */
		public function getDisplayLabels($pb_has_preferred_flag=true) {
			$va_labels = $this->get($this->ops_label_table_name.'.'.$this->ops_label_display_field, array('returnAsArray' => true));
			if ($pb_has_preferred_flag) {
				$va_is_preferred = $this->get($this->ops_label_table_name.'.is_preferred', array('returnAsArray' => true));
			}
			$va_locales = $this->get($this->ops_label_table_name.'.locale_id', array('returnAsArray' => true));
			$va_display_labels = array();
			
			$vn_num_locales = sizeof($va_locales);
			for($vn_i=0; $vn_i < $vn_num_locales; $vn_i++) {
				if ($pb_has_preferred_flag && !$va_is_preferred[$vn_i]) { continue; }
				$va_display_labels[0][$va_locales[$vn_i]] = $va_labels[$vn_i];
			}
			return caExtractValuesByUserLocale($va_display_labels);
		}
		# -------------------------------------------------------
		/**
		 * Limits result set to rows where specified field contains any of the values listed in $pa_values
		 *
		 * @param string $ps_field Fully qualified field name (<table>.<field> format - eg. ca_objects.type_id)
		 * @param array $pa_values List of values to filter rows by; only rows where the specified field contains at least one of the values in $pa_values will be returned
		 * @return void
		 */
		public function filterResult($ps_field, $pa_values) {
			if (!is_array($pa_values)) { $pa_values = array($pa_values); }
			$this->ops_filter_field = $ps_field;
			$this->opa_filter_values = $pa_values;
		}
		# -------------------------------------------------------
		/**
		 * Fetched next hit in result set.
		 * Overrides SearchResult::nextHit() to implement result filtering
		 *
		 * @return bool True if next hit was loaded, false if there are no more hits to iterate through
		 */
		public function nextHit() {
			if ($this->ops_filter_field) {
				while($vb_r = parent::nextHit()) {
					if (in_array($this->get($this->ops_filter_field), $this->opa_filter_values)) {
						return $vb_r;
					}
				}
			}
			
			return parent::nextHit();
		}
		# -------------------------------------------------------
		/**
		 * Number of hits in the result set.
		 * Overrides SearchResult::numHits() to implement result filtering
		 */
		public function numHits() {
			if ($this->ops_filter_field) {
				$va_r = $this->getResultCountForFieldValues(array($this->ops_filter_field));
			
				$vn_num = 0;
				foreach($this->opa_filter_values as $vm_value) {
					$vn_num .= $va_r[$this->ops_filter_field][$vm_value];
				}
				return $vn_num;
			}
			return parent::numHits();
		}
		# -------------------------------------------------------
	}
?>