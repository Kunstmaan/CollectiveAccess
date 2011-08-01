<?php

/* ----------------------------------------------------------------------
 * app/controllers/lookup/KeywordsController.php :
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
require_once(__CA_LIB_DIR__ . "/core/Db.php");

class KeywordsController extends ActionController {
    # -------------------------------------------------------

    public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
        parent::__construct($po_request, $po_response, $pa_view_paths);
    }

    # -------------------------------------------------------
    # AJAX handlers
    # -------------------------------------------------------

    public function Get() {
        $this->ops_db = new Db();
        $ps_query = $this->request->getParameter('q', pString);
        $ps_type = $this->request->getParameter('type', pString);
        $va_items = array();
        $type_query = '';
        // lookup data type id
        $va_types = Attribute::getAttributeTypes();
        $datatype_id = array_search("Keywords", $va_types);
        if (!empty($ps_type)) {
            $type_query = " AND ca_attribute_values.source_info = '$ps_type'";
        }
        $ft_sql = "SELECT distinct(value_longtext1) FROM ca_attribute_values LEFT JOIN ca_metadata_elements ON ca_attribute_values.element_id = ca_metadata_elements.element_id WHERE datatype = " . $datatype_id . " AND value_longtext1 LIKE '" . $ps_query . "%'" . $type_query;
        $result = $this->ops_db->query($ft_sql);
        while ($result->nextRow()) {
            $va_items[] = array('displayname' => htmlspecialchars($result->get('value_longtext1')));
        }
        $this->view->setVar('keyword_list', $va_items);
        return $this->render('ajax_keywords_list_html.php');
    }

    # -------------------------------------------------------
}
?>
