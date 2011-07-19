<?php
/* ----------------------------------------------------------------------
 * app/lib/ca/RepresentationAnnotationPropertyCoders/ImageBasedRepresentationAnnotationCoder.php :
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
 * ----------------------------------------------------------------------
 */
 	require_once(__CA_LIB_DIR__.'/ca/RepresentationAnnotationPropertyCoders/IRepresentationAnnotationPropertyCoder.php');
 	require_once(__CA_LIB_DIR__.'/ca/RepresentationAnnotationPropertyCoders/BaseRepresentationAnnotationCoder.php');
 	require_once(__CA_APP_DIR__.'/helpers/htmlFormHelpers.php');

	class ImageBasedRepresentationAnnotationCoder extends BaseRepresentationAnnotationCoder implements IRepresentationAnnotationPropertyCoder {
	# ------------------------------------------------------------------
		public function __construct() {
			parent::__construct();
			$this->ops_type = 'ImageBased';
		}

		# ------------------------------------------------------------------
		/* return HTML form element for specified property */
		public function htmlFormElement($ps_property, $pa_attributes=null) {
			$vs_element = $vs_label = '';

			if (!($vs_format = $pa_attributes['format'])) {
				$vs_format = $this->opo_config->get('form_element_display_format');
			}

			if ($va_property_info = $this->getPropertyInfo($ps_property)) {
				switch($va_property_info['fieldType']) {
					case 'FT_NUMBER':
					$vs_element = caHTMLHiddenInput($pa_attributes['name'] ? $pa_attributes['name'] : $ps_property,$pa_attributes);
						return $vs_element;
						break;
					case 'FT_TEXT':
						$vs_element = caHTMLHiddenInput($pa_attributes['name'] ? $pa_attributes['name'] : $ps_property,$pa_attributes);
						return $vs_element;
						break;
					default:
						return 'Invalid field type for \''.$ps_property.'\'';
						break;
				}
			}

			return $vs_element;
		}

		# ------------------------------------------------------------------
		/* Set the specified property; return true on success, false if value is invalid, null if property doesn't exist */
		public function setProperty($ps_property, $pm_value) {
			if (!($va_info = $this->getPropertyInfo($ps_property))) { return null; }	// invalid property

            switch($va_info['fieldType']) {
				case 'FT_NUMBER':
					$this->opa_property_values[$ps_property] = $pm_value;
					return true;
					break;
				case 'FT_TEXT':
					$this->opa_property_values[$ps_property] = $pm_value;
					return true;
					break;
				default:
					// unsupported property?
					$this->postError(1500, _t("Invalid property '%1'", $ps_property), 'ImageBasedRepresentationAnnotationCoder->setProperty()');
					return null;
					break;
			}
		}

		# ------------------------------------------------------------------
		/* returns the property value or null if the property doesn't exist */
		public function getProperty($ps_property, $pb_return_raw_value=false) {
			if (!($va_info = $this->getPropertyInfo($ps_property))) { return null; }	// invalid property

			if ($pb_return_raw_value) {
				return $this->opa_property_values[$ps_property];
			}

			switch($va_info['fieldType']) {
				case 'FT_NUMBER':
					return $this->opa_property_values[$ps_property];
					break;
				case 'FT_TEXT':
					return $this->opa_property_values[$ps_property];
					break;
				default:
					return null;
					break;
			}
		}
	}

?>
