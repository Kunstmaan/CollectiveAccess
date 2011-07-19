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
