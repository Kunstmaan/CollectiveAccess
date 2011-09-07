<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/UserContentService.php
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
require_once(__CA_MODELS_DIR__."/ca_item_comments.php");
require_once(__CA_MODELS_DIR__."/ca_sets.php");
require_once(__CA_MODELS_DIR__."/ca_set_items.php");

class UserContentService extends BaseService {
	# -------------------------------------------------------
	protected $opo_dm;
	# -------------------------------------------------------
	public function  __construct($po_request) {
		parent::__construct($po_request);
		$this->opo_dm = Datamodel::load();
	}
	# -------------------------------------------------------
	/**
	 * Add comment to specified item
	 * 
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key
	 * @param array $comment_info_array
	 * @return boolean
	 * @throws SoapFault
	 */
	public function addComment($type, $item_id, $comment_info_array){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		$t_comment = new ca_item_comments();
		$t_comment->setMode(ACCESS_WRITE);
		$t_comment->set($comment_info_array);
		$t_comment->set('table_num', $t_subject_instance->tableNum());
		$t_comment->set('row_id', $t_subject_instance->getPrimaryKey());
		$t_comment->set('user_id', $this->getUserID());
		$vn_id = $t_comment->insert();
		if($t_comment->numErrors()==0){
			return $vn_id;
		} else {
			throw new SoapFault("Server", "There were errors while adding the comment: ".join(";",$t_comment->getErrors()));
		}
	}
	# -------------------------------------------------------
	/**
	 * Get comments attached to specified item
	 *
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key
	 * @return array
	 */
	public function getComments($type, $item_id){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		return $t_subject_instance->getComments();
	}
	# -------------------------------------------------------
	/**
	 * Add tag to specified item
	 *
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key
	 * @param array $tag_info_array
	 * @return boolean
	 * @throws SoapFault
	 */
	public function addTag($type, $item_id, $tag_info_array){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		$ps_tag = isset($tag_info_array["tag"]) ? $tag_info_array["tag"] : "";
		$pn_locale_id = isset($tag_info_array["locale_id"]) ? $tag_info_array["locale_id"] : null;
		$pn_access = isset($tag_info_array["access"]) ? $tag_info_array["access"] : 0;
		$pn_moderator = isset($tag_info_array["moderator"]) ? $tag_info_array["moderator"] : null;
		$t_subject_instance->addTag($ps_tag, $this->getUserID(), $pn_locale_id, $pn_access, $pn_moderator);
		if($t_subject_instance->numErrors()==0){
			return true;
		} else {
			throw new SoapFault("Server", "There were errors while adding the comment: ".join(";",$t_subject_instance->getErrors()));
		}
	}
	# -------------------------------------------------------
	/**
	 * Get tags attached to specified items
	 *
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key
	 * @return array
	 */
	public function getTags($type, $item_id){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		return $t_subject_instance->getTags();
	}
	# -------------------------------------------------------
	/**
	 * Creates new set
	 *
	 * @param string $type a table name like "ca_objects"
	 * @param array $set_info_array an associative array containing the data for the ca_sets fields
	 * @return int set_id of the newly created set
	 * @throws SoapFault
	 */
	public function addSet($type, $set_info_array){
		if(!($vn_tablenum = $this->opo_dm->getTableNum($type))){
			throw new SoapFault("Server", "Invalid set type");
		}
		$t_new_set = new ca_sets();
		$t_new_set->setMode(ACCESS_WRITE);
		$t_new_set->set("table_num",$vn_tablenum);
		$t_new_set->set($set_info_array);
		$vn_id = $t_new_set->insert();
		if($t_new_set->numErrors()==0){
			return $vn_id;
		} else {
			throw new SoapFault("Server", "There were errors while inserting the set: ".join(";",$t_new_set->getErrors()));
		}
	}
	# -------------------------------------------------------
	/**
	 * Updates set information for specified set
	 *
	 * @param int $set_id
	 * @param array $set_info_array
	 * @return boolean
	 * @throws SoapFault
	 */
	public function updateSet($set_id, $set_info_array){
		$t_set = new ca_sets();
		if(!$t_set->load($set_id)){
			throw new SoapFault("Server", "Invalid set_id");
		}
		$t_set->setMode(ACCESS_WRITE);
		$t_set->set($set_info_array);
		$t_set->update();
		if($t_set->numErrors()==0){
			return true;
		} else {
			throw new SoapFault("Server", "There were errors while updating the set: ".join(";",$t_set->getErrors()));
		}
	}
	# -------------------------------------------------------
	/**
	 * Removes specified set from database
	 *
	 * @param int $set_id
	 * @return boolean
	 * @throws SoapFault
	 */
	public function removeSet($set_id){
		$t_set = new ca_sets();
		if(!$t_set->load($set_id)){
			throw new SoapFault("Server", "Invalid set_id");
		}
		$t_set->setMode(ACCESS_WRITE);
		$t_set->delete();
		if($t_set->numErrors()==0){
			return true;
		} else {
			throw new SoapFault("Server", "There were errors while deleting the set: ".join(";",$t_set->getErrors()));
		}
	}
	# -------------------------------------------------------
	/**
	 * Adds specified item (row_id) to set
	 *
	 * @param int $set_id
	 * @param string $type
	 * @param int $item_id
	 * @param array $set_item_info_array
	 * @return int the set_item_id of the newly created record
	 * @throws SoapFault
	 */
	public function addItemToSet($set_id, $type, $item_id, $set_item_info_array){
		if(!($vn_tablenum = $this->opo_dm->getTableNum($type))){
			throw new SoapFault("Server", "Invalid type");
		}
		$t_set_item = new ca_set_items();
		$t_set_item->setMode(ACCESS_WRITE);
		$t_set_item->set("set_id",$set_id);
		$t_set_item->set("row_id",$item_id);
		$t_set_item->set("table_num",$vn_tablenum);
		$t_set_item->set($set_item_info_array);
		$vn_item_id = $t_set_item->insert();
		if($t_set_item->numErrors()==0){
			return $vn_item_id;
		} else {
			throw new SoapFault("Server", "There were errors while adding the item: ".join(";",$t_set_item->getErrors()));
		}
	}
	# -------------------------------------------------------
	/**
	 * Updates existing set item information
	 *
	 * @param int $set_item_id
	 * @param array $set_item_info_array
	 * @return boolean
	 * @throws SoapFault
	 */
	public function updateSetItem($set_item_id, $set_item_info_array){
		$t_set_item = new ca_set_items();
		if(!$t_set_item->load($set_item_id)){
			throw new SoapFault("Server", "Invalid set_item id");
		}
		$t_set_item->setMode(ACCESS_WRITE);
		$t_set_item->set($set_item_info_array);
		$t_set_item->update();
		if($t_set_item->numErrors()==0){
			return true;
		} else {
			throw new SoapFault("Server", "There were errors while updating the item: ".join(";",$t_set_item->getErrors()));
		}
	}
	# -------------------------------------------------------
	/**
	 * Removes item from set
	 *
	 * @param int $set_id
	 * @param int $set_item_id
	 * @return boolean
	 * @throws SoapFault
	 */
	public function removeItemFromSet($set_id, $set_item_id){
		$t_set_item = new ca_set_items();
		if(!$t_set_item->load($set_item_id)){
			throw new SoapFault("Server", "Invalid set_item id");
		}
		$t_set_item->setMode(ACCESS_WRITE);
		$t_set_item->delete();
		if($t_set_item->numErrors()==0){
			return true;
		} else {
			throw new SoapFault("Server", "There were errors while updating the item: ".join(";",$t_set_item->getErrors()));
		}
	}
	# -------------------------------------------------------
	/**
	 * Add image annotation to specified representations. this request needs the User Image Annotation plugin to be activated!! This plugin adds the possibillity of validating the annotations.
	 *
	 * @param int $representation_id primary key
	 * @param int $original_top top position of the annotation
	 * @param int $original_left left position of the annotation
	 * @param int $original_width width of the annotation
	 * @param int $original_height height of the annotation
	 * @param string $annotation text
	 * @param int  $locale_id
	 * @param string $email
	 * @param string $name text
	 * @param int $user_id
	 * @return int
	 * @throws SoapFault
	 */
	public function addImageAnnotation($representation_id, $original_top, $original_left, $original_width, $original_height, $annotation='', $locale_id=null, $email=null, $name=null, $user_id=null) {
		$this->checkLoginAndPermissions();
		require_once(__CA_MODELS_DIR__.'/ca_object_representations.php');
		if(!($representation = new ca_object_representations($representation_id))){
			throw new SoapFault("Server", "Invalid representation");
		}
		if(!isset($original_top) || !is_numeric($original_top) || !isset($original_left) || !is_numeric($original_left) || !isset($original_width) || !is_numeric($original_width) || !isset($original_height) || !is_numeric($original_height)) {
			throw new SoapFault("Server", "Invalid annotation parameters");
		}

		if($user_id == null) {
			$user_id = $this->getUserID();
		}

		require_once(__CA_MODELS_DIR__.'/ca_user_annotations.php');
		$t_user_annotation = new ca_user_annotations();
		$t_user_annotation->setMode(ACCESS_WRITE);
		$t_user_annotation->set('row_id', $representation_id);
		$t_user_annotation->set('user_id', $user_id);
		$t_user_annotation->set('locale_id', $locale_id);
		$t_user_annotation->set('original_top', $original_top);
		$t_user_annotation->set('original_left', $original_left);
		$t_user_annotation->set('original_width', $original_width);
		$t_user_annotation->set('original_height', $original_height);

		$t_user_annotation->set('annotation', $annotation);
		$t_user_annotation->set('email', $email);
		$t_user_annotation->set('name', $name);

		$t_user_annotation->insert();

		if($t_user_annotation->numErrors()==0){
			return $t_user_annotation->get('user_annotation_id');
		} else {
			throw new SoapFault("Server", "There were errors while adding the annotation: ".join(";",$t_user_annotation->getErrors()));
		}
	}
	# -------------------------------------------------------
	/**
	 * Update image annotation to specified representations. this request needs the User Image Annotation plugin to be activated!! This plugin adds the possibillity of validating the annotations.
	 *
	 * @param int $annotation_id primary key
	 * @param int $original_top top position of the annotation
	 * @param int $original_left left position of the annotation
	 * @param int $original_width width of the annotation
	 * @param int $original_height height of the annotation
	 * @param string $annotation text
	 * @param int  $locale_id
	 * @param string $email
	 * @param string $name text
	 * @param int $user_id
	 * @return int
	 * @throws SoapFault
	 */
	public function updateImageAnnotation($annotation_id, $original_top, $original_left, $original_width, $original_height, $annotation='', $locale_id=null, $email=null, $name=null, $user_id=null) {
		$this->checkLoginAndPermissions();

		if(!isset($original_top) || !is_numeric($original_top) || !isset($original_left) || !is_numeric($original_left) || !isset($original_width) || !is_numeric($original_width) || !isset($original_height) || !is_numeric($original_height)) {
			throw new SoapFault("Server", "Invalid annotation parameters");
		}

		require_once(__CA_MODELS_DIR__.'/ca_user_annotations.php');
		if(!($t_user_annotation = new ca_user_annotations($annotation_id))){
			throw new SoapFault("Server", "Invalid annotation");
		}

		$t_user_annotation->setMode(ACCESS_WRITE);
		if(isset($user_id)) {
			$t_user_annotation->set('user_id', $user_id);
		}
		if(isset($locale_id)) {
			$t_user_annotation->set('locale_id', $locale_id);
		}
		$t_user_annotation->set('original_top', $original_top);
		$t_user_annotation->set('original_left', $original_left);
		$t_user_annotation->set('original_width', $original_width);
		$t_user_annotation->set('original_height', $original_height);

		$t_user_annotation->set('annotation', $annotation);

		if(isset($email) && !empty($email)) {
			$t_user_annotation->set('email', $email);
		}
		if(isset($name) && !empty($name)) {
			$t_user_annotation->set('name', $name);
		}

		$t_user_annotation->update();

		if($t_user_annotation->numErrors()==0){
			return $t_user_annotation->get('user_annotation_id');
		} else {
			throw new SoapFault("Server", "There were errors while updating the annotation: ".join(";",$t_user_annotation->getErrors()));
		}
	}
	# -------------------------------------------------------
	/**
	 * Delete image annotation.
	 *
	 * @param int $annotation_id primary key
	 * @return boolean
	 * @throws SoapFault
	 */
	public function deleteImageAnnotation($annotation_id) {
		$this->checkLoginAndPermissions();

		require_once(__CA_MODELS_DIR__.'/ca_user_annotations.php');
		if(!($t_user_annotation = new ca_user_annotations($annotation_id))){
			throw new SoapFault("Server", "Invalid annotation");
		}

		$t_user_annotation->setMode(ACCESS_WRITE);
		$t_user_annotation->delete();

		if($t_user_annotation->numErrors()==0){
			return true;
		} else {
			throw new SoapFault("Server", "There were errors while deleting the annotation: ".join(";",$t_user_annotation->getErrors()));
		}
	}

	# -------------------------------------------------------
	# Utilities
	# -------------------------------------------------------
	private function getTableInstance($ps_type,$pn_type_id_to_load=null){
		if(!in_array($ps_type, array("ca_objects", "ca_entities", "ca_places", "ca_occurrences", "ca_collections", "ca_list_items"))){
			throw new SoapFault("Server", "Invalid type or item_id");
		} else {
			require_once(__CA_MODELS_DIR__."/{$ps_type}.php");
			$t_instance = new $ps_type();
			if($pn_type_id_to_load){
				if(!$t_instance->load($pn_type_id_to_load)){
					return false;
				} else {
					$t_instance->setMode(ACCESS_WRITE);
					return $t_instance;
				}
			} else {
				return $t_instance;
			}
		}
	}
}