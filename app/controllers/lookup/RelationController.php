<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/CollectionController.php :
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
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_APP_DIR__.'/models/ca_metadata_elements.php');

class RelationController extends ActionController {

    # -------------------------------------------------------
    protected $opb_uses_hierarchy_browser = false;
    protected $ops_db;
    protected $ops_table_name = '';
    protected $ops_name_singular = '';
    protected $ops_search_class = '';
    protected $opo_item_instance;
    protected $opo_item_type = '';
    protected $pa_options = array();
    protected $opo_app_plugin_manager;
    # -------------------------------------------------------
    public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
        parent::__construct($po_request, $po_response, $pa_view_paths);
        $this->opo_app_plugin_manager = new ApplicationPluginManager();
    }
    #--
    public function Get() {
        $this->ops_db = new Db();
        $ps_query = $this->request->getParameter('q', pString);

        // get element_id from requestURL
        $parts = explode("/element/",$this->request->getRequestURL());
        if(strstr("/",$parts[1])) {
            $subparts = explode("/",$parts[1]);
            $element_id = $subparts[0];
        } else {
            $element_id  = $parts[1];
        }
        // get item_table from requestURL
        $parts = explode("/item/",$this->request->getRequestURL());
        if(strstr("/",$parts[1])) {
            $subparts = explode("/",$parts[1]);
            $item_table = $subparts[0];
        } else {
            $item_table  = $parts[1];
        }

        //load the element
        $ca_element = new ca_metadata_elements($element_id);
        //$rel_table = $ca_element->getAppDatamodel()->getTableInstance($ca_element->getSetting('RelTable'));
        //$this->ops_table_name = $rel_table->getRightTableName();
        $this->ops_table_name = $item_table;
        require_once(__CA_APP_DIR__.'/models/'.$this->ops_table_name.'.php');
        $this->opo_item_instance = new $this->ops_table_name();
        $vs_label_table_name = $this->opo_item_instance->getLabelTableName();
        $vs_label_display_field_name = $this->opo_item_instance->getLabelDisplayField();
        $vs_pk = $this->opo_item_instance->primaryKey();
        $vs_hier_parent_id_fld = $this->opo_item_instance->getProperty('HIERARCHY_PARENT_ID_FLD');
        //check for an ops_search_class in element settings
        $this->ops_search_class = $ca_element->getSetting('SearchClass');
        //check for an item type
        $right_item_type = $ca_element->getSetting('RightItemType');
        $t_list = new ca_lists();
        $this->opo_item_type = $t_list->getItemIDFromList($this->opo_item_instance->getTypeListCode(), $right_item_type);
        $vb_return_vocabulary_only = false;
        switch($this->ops_table_name) {
            case 'ca_objects':
                $this->ops_name_singular = 'object';
                if(strlen($this->ops_search_class)==0) $this->ops_search_class = 'ObjectSearch';
                break;
            case 'ca_entities':
                // Fix problem when looking up entries like "A. Vos"
                $parts = explode(' ', $ps_query);
                $newParts = array();
                foreach($parts as $part) {
                    $newParts[] = '+' . $part . '*';
                }
                $ps_query = implode(' ', $newParts);

                //query the fulltext table directly
                $item_table_num = $this->opo_item_instance->tableNum();
                $ft_sql = "SELECT row_id, SUM(boost) FROM `ca_mysql_fulltext_search`
                    WHERE table_num = ".$item_table_num." AND MATCH(fieldtext) AGAINST('".$ps_query."' IN BOOLEAN MODE)
                    GROUP BY row_id
                    ORDER BY SUM(boost) DESC";
                $ft_result = $this->ops_db->query($ft_sql);
                $item_ids = array();
                while($ft_result->nextRow()) {
                    $item_ids[] = $ft_result->get('row_id');
                }
                if(count($item_ids) == 0) { return null;}
                //search the table directly for idno_stub
                $sql = "SELECT ca_entity_labels.*,ca_entities.entity_id,ca_entities.type_id item_type_id
                    FROM ca_entities
                    INNER JOIN ca_entity_labels ON ca_entities.entity_id = ca_entity_labels.entity_id
                    WHERE
                        ca_entities.entity_id IN (".implode(",",$item_ids).")";
                $result = $this->ops_db->query($sql);
                while($result->nextRow()) {
                    //limit by type if an item_type exists
                    if(strlen($this->opo_item_type)>0) {
                        if($this->opo_item_type != $qr_res->get('type_id')) {
                            continue;
                        }
                    }
                    $entity_id = $result->get('ca_entities.entity_id');
                    $va_items[$entity_id]= array($vs_label_table_name.'.type_id'=>$result->get($vs_label_table_name.'.type_id'),'_display' => htmlspecialchars($result->get($vs_label_table_name.'.displayname'), ENT_COMPAT, 'UTF-8'), 'idno' => $result->get('idno'), 'parent_id' => null);
                }
                $this->view->setVar('entity_list', $va_items);
                return $this->render('ajax_entity_list_html.php');
                break;
            case 'ca_object_events':
                $this->ops_name_singular = 'object_event';
                if(strlen($this->ops_search_class)==0) $this->ops_search_class = 'SimpleLabelSearch';
                $this->pa_options['table_instance'] = $this->opo_item_instance;
                break;
            case 'ca_collections':
                $this->ops_name_singular = 'collection';
                if(strlen($this->ops_search_class)==0) $this->ops_search_class = 'CollectionSearch';
                break;
            case 'ca_list_items':
                $voconly = $this->request->getParameter('onlyvoc', pString);
                $vb_return_vocabulary_only = (substr($voconly,0,1) == 't')? true : false;
                $this->ops_name_singular = 'list_item';
                if(strlen($this->ops_search_class)==0) $this->ops_search_class = 'ListItemSearch';
                break;
            case 'ca_occurrences':
                $this->ops_name_singular = 'occurrence';
                if(strlen($this->ops_search_class)==0) $this->ops_search_class = 'OccurrenceSearch';
                break;
            case 'ca_places':
                $this->ops_name_singular = 'place';
                if(strlen($this->ops_search_class)==0) $this->ops_search_class = 'PlaceSearch';
                break;
            case 'ca_storage_locations':
                $this->ops_name_singular = 'storage_location';
                if(strlen($this->ops_search_class)==0) $this->ops_search_class = 'StorageLocationSearch';
                break;
            case 'ca_object_lots':
                //search the table directly for idno_stub
                $sql = "SELECT ca_object_lots.*,ca_object_lot_labels.type_id,ca_object_lot_labels.name FROM ca_object_lots ";
                $sql .= " LEFT JOIN ca_object_lot_labels ON (ca_object_lot_labels.lot_id = ca_object_lots.lot_id) ";
                $sql .= " WHERE idno_stub LIKE '".$ps_query."%'";
                $result = $this->ops_db->query($sql);
                while($result->nextRow()) {
                    //limit by type if an item_type exists
                    if(strlen($this->opo_item_type)>0) {
                        if($this->opo_item_type != $qr_res->get('type_id')) {
                            continue;
                        }
                    }
                    $lot_id = $result->get('lot_id');
                    $va_items[$lot_id]= array($vs_label_table_name.'.type_id'=>$result->get($vs_label_table_name.'.type_id'),'displayname' => htmlspecialchars($result->get($vs_label_table_name.'.name'), ENT_COMPAT, 'UTF-8'), 'idno' => $result->get('idno_stub'), 'parent_id' => null);
                }
                $this->view->setVar('object_lot_list', $va_items);
                return $this->render('ajax_object_lot_list_html.php');
                break;
                //TODO: need to handle vocabulary search and object_events
        }
        require_once(__CA_LIB_DIR__."/ca/Search/".$this->ops_search_class.".php");
        if (unicode_strlen($ps_query) >= 3) {
            if(class_exists($this->ops_search_class)) {
                $o_search = new $this->ops_search_class();
            } else {
                return null;
            }

            $qr_res = $o_search->search("'".$ps_query."*'",$this->pa_options);

            $va_parent_ids = array();

            //This is supposed to be a simple lookup - so limit the number of hits
            if($qr_res->numHits() > 1000) {
                $va_items[0]['displayname'] = "Please narrow your search.";
                $this->view->setVar($this->ops_name_singular.'_list', $va_items);
                return $this->render('ajax_'.$this->ops_name_singular.'_list_html.php');
            }

            while($qr_res->nextHit()) {
                if ($vb_return_vocabulary_only && ($qr_res->get('ca_lists.use_as_vocabulary') != 1)) { continue; }
                //limit by type if an item_type exists
                if(strlen($this->opo_item_type)>0) {
                    if($this->opo_item_type != $qr_res->get('type_id')) {
                        continue;
                    }
                }
                $va_preferred_label_locales = $qr_res->get($vs_label_table_name.'.locale_id', array('return_values_where' => array('field' => 'is_preferred', 'value' => 1)));
                $va_preferred_labels = $qr_res->get($vs_label_table_name.'.'.$vs_label_display_field_name, array('return_values_where' => array('field' => 'is_preferred', 'value' => 1)));
                $vn_i = 0;
                foreach($va_preferred_label_locales as $vn_locale_id) {
                    $search_label = $va_preferred_labels[$vn_i];
                    //only add items where the preferred label starts with the ps_query
                    if(substr(strtolower($search_label),0,strlen($ps_query)) == strtolower($ps_query)) {
                        $va_items[$qr_res->get($vs_label_table_name.'.'.$vs_pk)][$vn_locale_id] = array($vs_label_table_name.'.type_id' => $qr_res->get('type_id'), 'displayname' => htmlspecialchars($va_preferred_labels[$vn_i], ENT_COMPAT, 'UTF-8'), 'idno' => $qr_res->get('idno'), 'parent_id' => $qr_res->get('parent_id'), 'table_name' => $this->ops_table_name);
                    }
                    $vn_i++;
                    if ($vn_parent_id = $qr_res->get($vs_hier_parent_id_fld)) {
                        $va_parent_ids[] = $vn_parent_id;
                    }
                }
            }

            $va_items = caExtractValuesByUserLocale($va_items, null, null, array());
            $va_parent_labels = $this->opo_item_instance->getPreferredDisplayLabelsForIDs($va_parent_ids);

            if ($this->opo_item_instance->isHierarchical()) {
                foreach($va_items as $vn_id => $va_info) {
                    if ($vs_parent_label = $va_parent_labels[$va_items[$vn_id]['parent_id']]) {
                        $va_items[$vn_id]['parent'] = $vs_parent_label;
                    }
                }
            }
        }

        $va_items = $this->opo_app_plugin_manager->hookFilterLookup($va_items);
        $va_items_filtered = array();
        foreach($va_items as $key => $value) {
            if(isset($value) && $value != null) {
                $va_items_filtered[$key] = $value;
            }
        }
        $this->view->setVar($this->ops_name_singular.'_list', $va_items_filtered);
        return $this->render('ajax_'.$this->ops_name_singular.'_list_html.php');
    }
}
?>