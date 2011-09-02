<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/SearchService.php
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */
  
require_once(__CA_LIB_DIR__."/ca/Service/BaseService.php");
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");
require_once(__CA_MODELS_DIR__."/ca_list_items.php");

class SearchService extends BaseService {
	# -------------------------------------------------------
	public function  __construct($po_request) {
		parent::__construct($po_request);
	}
	# -------------------------------------------------------
	/**
	 * Performs a search (for use with REST-style services)
	 * NOTE: This method cannot be used via the SOAP services since
	 * Zend_Service can't put DOMDocument objects in SOAP responses
	 *
	 * @param string $type can be one of: ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items
	 * @param string $query the search query
	 * @return DOMDocument
	 * @throws SoapFault
	 */
	public function queryRest($type,$query){
		if(!$ps_class = $this->mapTypeToSearchClassName($type)){
			throw new SoapFault("Server","Invalid type");
		}

		require_once(__CA_LIB_DIR__."/ca/Search/{$ps_class}.php");
		require_once(__CA_MODELS_DIR__."/{$type}.php");
		$vo_search = new $ps_class();
		$t_instance = new $type();

		$vo_result = $vo_search->search($query);

		$vo_dom = new DOMDocument('1.0', 'utf-8');
		$vo_root = $vo_dom->createElement('CaSearchResult');
		$vo_dom->appendChild($vo_root);
		while($vo_result->nextHit()){
			$t_instance->load($vo_result->get($t_instance->primaryKey()));

			// create element representing row
			$vo_item = $vo_dom->createElement($type);
			$vo_item->setAttribute($t_instance->primaryKey(),$vo_result->get($t_instance->primaryKey()));
			$vo_root->appendChild($vo_item);

			// add labels
			$vo_labels = $this->_getTableInstancePrefLabelsAsDOMElement($t_instance, $vo_dom);
			
			if($vo_labels instanceof DOMNode) {
				$vo_item->appendChild($vo_labels);
			}
			
			// add idno
			if($t_instance->hasField("idno") && ($vs_idno = $t_instance->get("idno"))){
				$vo_idno = $vo_dom->createElement("idno",htmlspecialchars($vs_idno));
				$vo_item->appendChild($vo_idno);
			}

			// add type
			if($t_instance->hasField("type_id") && ($vn_type_id = $t_instance->get("type_id"))){
				$t_list_item = new ca_list_items($vn_type_id);
				$vo_type = $vo_dom->createElement("type");
				$vo_item->appendChild($vo_type);
				$vo_labels = $this->_getTableInstancePrefLabelsAsDOMElement($t_list_item, $vo_dom);
				$vo_type->appendChild($vo_labels);
			}

		}
		return $vo_dom;
	}
	/**
	 * Performs a search (for SOAP services)
	 * @param string $type can be one of: ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items
	 * @param string $query the search query
	 * @return array
	 * @throws SoapFault
	 */
	public function querySoap($type,$query){
		if(!$ps_class = $this->mapTypeToSearchClassName($type)){
			throw new SoapFault("Server","Invalid type");
		}

		require_once(__CA_LIB_DIR__."/ca/Search/{$ps_class}.php");
		require_once(__CA_MODELS_DIR__."/{$type}.php");
		$vo_search = new $ps_class();
		$t_instance = new $type();

		$vo_result = $vo_search->search($query);

		$va_return = array();
		while($vo_result->nextHit()){
			$t_instance->load($vo_result->get($t_instance->primaryKey()));

			$va_return[$vo_result->get($t_instance->primaryKey())] = array(
				"display_label" => $t_instance->getLabelForDisplay(),
				"idno" => ($t_instance->hasField("idno") ? $t_instance->get("idno") : null),
				$t_instance->primaryKey() => $vo_result->get($t_instance->primaryKey())
			);
		}
		return $va_return;
	}

	# -------------------------------------------------------
	/**
	 * Performs a search
	 *
	 * @param int $item_limit specifies the number of items that will be returned
	 * @param array $check_access
	 * @param boolean $has_representations
	 * @param array $to_return array explaining which info you need. If this is empty everything will be returned.
	 * @param array $representation_versions array explaining which representation versions you need.
	 * @return array $result
	 * @throws SoapFault
	 */
	public function getRecentlyAddedItems($item_limit=10, $check_access=null, $has_representations=true, $to_return = array(), $representation_versions = array()){
		require_once(__CA_MODELS_DIR__."/ca_objects.php");
		$t_object = new ca_objects();
		$recently_added_ids = $t_object->getRecentlyAddedItems($item_limit, array('checkAccess' => $check_access, 'hasRepresentations' => $has_representations));

		$return_options = $this->generateReturnOptions($to_return, $representation_versions);

		$result = array();
		foreach($recently_added_ids as $key => $values) {
			$t_object = new ca_objects($values['object_id']);
			$result[$values['object_id']] = $t_object->getItemInformationForService($return_options);
		}

		return $result;
	}

	/**
	 * Performs a search
	 *
	 * @parafacm int $item_limit specifies the number of items that will be returned
	 * @param array $check_access
	 * @param boolean $has_representations
	 * @param array $to_return array explaining which info you need. If this is empty everything will be returned.
	 * @param array $representation_versions array explaining which representation versions you need.
	 * @return array $result
	 * @throws SoapFault
	 */
	public function getRandomItems($item_limit=10, $check_access=null, $has_representations=true, $to_return = array(), $representation_versions = array()) {
		require_once(__CA_MODELS_DIR__."/ca_objects.php");
		$t_object = new ca_objects();
		$random_item_ids = $t_object->getRandomItems($item_limit, array('checkAccess' => $check_access, 'hasRepresentations' => $has_representations));

		$return_options = $this->generateReturnOptions($to_return, $representation_versions);

		$result = array();
		foreach($random_item_ids as $key => $values) {
			$t_object = new ca_objects($values['object_id']);
			$result[$values['object_id']] = $t_object->getItemInformationForService($return_options);
		}

		return $result;
	}

	/**
	 * Performs a search
	 *
	 * @param int $item_limit specifies the number of items that will be returned
	 * @param array $check_access
	 * @param boolean $has_representations
	 * @return array $result
	 * @throws SoapFault
	 */
	public function getMostViewedItems($item_limit=10, $check_access=null, $has_representations=true) {
		require_once(__CA_MODELS_DIR__."/ca_objects.php");
		$t_object = new ca_objects();
		$most_viewed_ids = $t_object->getMostViewedItems($item_limit, array('checkAccess' => $check_access, 'hasRepresentations' => $has_representations));

		$result = array();
		foreach($most_viewed_ids as $key => $values) {
			$t_object = new ca_objects($values['object_id']);
			$result[$values['object_id']] = $t_object->getItemInformationForService();
		}

		return $result;
	}

	/**
	 * Performs a search
	 *
	 * @param boolean $pb_moderation_status, default true
	 * @param int $pb_num_to_return , default 1
	 * @param array $check_access
	 * @param boolean $has_representations
	 * @param array $to_return array explaining which info you need. If this is empty everything will be returned.
	 * @param array $representation_versions array explaining which representation versions you need.
	 * @return array $result
	 * @throws SoapFault
	 */
	public function getHighestRated($pb_moderation_status=true, $pn_num_to_return=1, $check_access=null, $has_representations=true, $to_return = array(), $representation_versions = array()) {
		require_once(__CA_MODELS_DIR__."/ca_objects.php");

		$t_object = new ca_objects();

		$highest_rated_ids = $t_object->getHighestRated($pb_moderation_status, $pn_num_to_return, array('checkAccess' => $check_access, 'hasRepresentations' => $has_representations));

		$return_options = $this->generateReturnOptions($to_return, $representation_versions);

		$result = array();
		foreach($highest_rated_ids as $row_id) {
			$t_object = new ca_objects($row_id);
			$result[$row_id] = $t_object->getItemInformationForService($return_options);
		}

		return $result;

	}

	/**
	 * Performs a search
	 * @param string $type
	 * @param string $query
	 * @param string $sort
	 * @param string $appendToSearch
	 * @param string $sort_direction ('asc' or 'desc'), default "asc"
	 * @return array $search_result_ids
	 * @throws SoapFault
	 */
	public function search($type, $query, $sort, $appendToSearch, $sort_direction = "asc"){
		if(!$ps_class = $this->mapTypeToSearchClassName($type)){
			throw new SoapFault("Server","Invalid type");
		}

		require_once(__CA_LIB_DIR__."/ca/Search/{$ps_class}.php");
		require_once(__CA_MODELS_DIR__."/{$type}.php");
		$vo_search = new $ps_class();
		$t_instance = new $type();
		$primary_key_field = $t_instance->primaryKey();

		$options = array (
		  'sort' => $sort,
		  'sort_direction' => $sort_direction,
		  'appendToSearch' => $appendToSearch,
		);

		$vo_result = $vo_search->search($query, $options);
		$va_return = array();
		$prim_keys = array();
		$o_db = new Db();
		while($vo_result->nextHit()){
			// primkey
			$primary_key = $vo_result->get($primary_key_field);
			$va_return[$primary_key] = array($primary_key_field => $primary_key);
			$prim_keys[] = $primary_key;
		}
		// idno
		$idno = null;

		if($t_instance->hasField("idno")) {
			$q_idno = "SELECT {$primary_key_field}, idno FROM {$type} WHERE {$primary_key_field} IN (".join(",", $prim_keys).");";
			$idno_res = $o_db->query($q_idno);
			while($idno_res->nextRow()) {
				$key = $idno_res->get($primary_key_field);
				$idno = $idno_res->get('idno');
				$va_return[$key]['idno'] = $idno;
			}
		}

		// label
		$t_label = $this->opo_dm->getInstanceByTableName($t_instance->getLabelTableName());
		$display_field = $t_label->getDisplayField();
		$q_label = "
 			SELECT l.{$primary_key_field}, l.{$display_field}
 			FROM ".$t_instance->getLabelTableName()." l
 			INNER JOIN ca_locales AS loc ON loc.locale_id = l.locale_id
 			WHERE (l.{$primary_key_field} IN (".join(",", $prim_keys)."))
 			AND (l.is_preferred = 1)
 			ORDER BY
 				loc.name
 		";
		$qr_res = $o_db->query($q_label);
		while($qr_res->nextRow()) {
			$key = $qr_res->get($primary_key_field);
			$label = $qr_res->get($display_field);
			$va_return[$key]['display_label'] = $label;
		}

		if($sort == 'rating'){

		  // List of ID's
  		  $itemIds = array();
          foreach($va_return as $key => $result) {
            $itemIds[$key] = $result[$primary_key_field];
          }

          // Sort the list of ID's by their rating
		  require_once(__CA_LIB_DIR__."/ca/Service/UtilsService.php");
		  $utilsService = new UtilsService($this->opo_request);
		  $sorted = $utilsService->sortByRating($type, $itemIds, false);

		  // Put them back in the original structured list
		  $results = array();
		  foreach($sorted as $sorted_item){
		    $results[] = $va_return[$sorted_item];
		  }
		  return $results;
		}
		return $va_return;
	}

	# -------------------------------------------------------
	# Utilities
	# -------------------------------------------------------
	private function mapTypeToSearchClassName($ps_type){
		switch($ps_type){
			case "ca_objects":
				return "ObjectSearch";
			case "ca_entities":
				return "EntitySearch";
			case "ca_places":
				return "PlaceSearch";
			case "ca_occurrences":
				return "OccurrenceSearch";
			case "ca_collections":
				return "CollectionSearch";
			case "ca_list_items":
				return "ListItemSearch";
			default:
				return false;
		}
	}
	# -------------------------------------------------------
	private function _getTableInstancePrefLabelsAsDOMElement($t_instance,&$po_dom){
		/* add labels */
		if($t_instance instanceof LabelableBaseModelWithAttributes){
			$va_labels = array();
			if(sizeof($t_instance->getPreferredLabels())>0) {
				foreach($t_instance->getPreferredLabels() as $va_level1){
					foreach($va_level1 as $va_level2){
						foreach($va_level2 as $va_label){
							$va_labels[] = array(
								"language" => $va_label["locale_language"],
								"country" => $va_label["locale_country"],
								"display" => $va_label[$t_instance->getLabelDisplayField()]
							);
						}
					}
				}
			} else {
				return null;
			}
			if(sizeof($va_labels)>0){
				$vo_labels = $po_dom->createElement('ca_labels');
				foreach($va_labels as $va_label){
					if(!isset($va_label["display"])){
						continue;
					}
					$vo_label = $po_dom->createElement('ca_label', htmlspecialchars($va_label["display"]));
					$vo_labels->appendChild($vo_label);
					$vo_label->setAttribute('lang',$va_label["language"]);
					$vo_label->setAttribute('country',$va_label['country']);
				}
			}

			return $vo_labels;
		} else {
			return null;
		}
	}
	# -------------------------------------------------------
}
