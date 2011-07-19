#!/usr/local/bin/php
<?php
	error_reporting(E_ALL);

	set_time_limit(60 * 60); /* an hour should be sufficient */
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	if (!$argv[1]) {
		die("\nexportProfile.php: exports the current profile to the target file path.\n\nUSAGE: exportProfile.php 'file_path'\nExample: ./exportProfile.php /path/to/exported/profile\nLeave off the filename - it will be created as export_{timestamp}.profile\n\n");
	}
	
	$profile_path = $argv[1];
	
	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/core/Configuration.php");
	require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
	require_once(__CA_MODELS_DIR__.'/ca_metadata_type_restrictions.php');
	require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
	
	if (!file_exists($profile_path)) { die("Profile file path '{$profile_path}' does not exist\n"); }
	if (!is_writable($profile_path)) { die("Profile file path '{$profile_path}' is not writeable\n"); }

	$ts = time();

	$profile_path .= '/export_'.$ts.'.profile';
	
	//functions
	function exp_addTabs($level) {
		$tabs='';
		while($level>0) {
			$tabs .= chr(9);
			$level--;
		}
		return $tabs;
	}
	function exp_arrayToProfile($name,$data,$level=0,$header_label=NULL,$divider=false) {
		$contents = '';
		$_t = exp_addTabs($level);
		if($header_label) {
			//add header
			$contents .= $_t."# ---------------------------------------------------------------------------------------------------------\n";
			$contents .= $_t."# ".$header_label."\n";
			$contents .= $_t."# ---------------------------------------------------------------------------------------------------------\n";
		}
		$contents .= $_t.$name. " = {\n";
		if($divider) {
			$contents .= $_t."# --------------------------------------------------\n";
		}
		$next_level = $level+1;
		foreach($data as $key=>$value) {
			if(is_array($value)) {
				$contents .= exp_arrayToProfile($key,$value,$next_level).",\n";
			} else {
				$contents .= $_t.chr(9).$key . " = " . $value . ",\n";
			}
		}
		$contents .= $_t."}";
		return $contents;
	}
	function exp_getPrefLabels($obj) {
		global $locales_ref;
		$labels = $obj->getLabels();
		$label_fields = $obj->getLabelUIFields();
		$preferred_labels = array();
		foreach($labels as $label_id=>$label_info) {
			$current_labels = array();
			foreach($locales_ref as $locale_id=>$locale_name) {
				if(is_array($label_info[$locale_id])){
					foreach($label_info[$locale_id] as $key=>$current_label) {
						foreach($current_label as $current_field=>$current_value) {
							if(in_array($current_field,$label_fields)) {
								$current_value = mb_ereg_replace('"','',$current_value);
								$current_labels[$current_field] = $current_value;
							}
						}
					}
				}
				$preferred_labels[$locale_name] = $current_labels;		
			}
		}
		return $preferred_labels;
	}
	function exp_getList($list_id,$item_id=null) {
		global $ca_lists,$ca_list_items,$items_used,$item_label_fields,$locales_ref;
		$data=array();
		if($item_id) {
			$ca_list_items->load($item_id);
			$data['preferred_labels'] = exp_getPrefLabels($ca_list_items);
			$data['is_enabled'] = $ca_list_items->get('is_enabled');
			$data['is_default'] = $ca_list_items->get('is_default');
			$items = array();
			$list_items = $ca_lists->getChildItemsForList($list_id, $item_id);
		} else {
			$ca_lists->load($list_id);
			$data['preferred_labels'] = exp_getPrefLabels($ca_lists);
			$data['is_hierarchical'] = $ca_lists->get('is_hierarchical');
			$data['use_as_vocabulary'] = $ca_lists->get('use_as_vocabulary');
			$items = array();
			$list_items = $ca_lists->getItemsForList($list_id);
		}
		
		if (is_array($list_items)) {
			foreach($list_items as $item_id=>$item_info) {
				$current_item = array();
				foreach($locales_ref as $locale_id=>$locale_name) {
					$current_item = $item_info[$locale_id];
					if (!is_array($current_item)) { continue; }
					
					if(strlen($current_item['idno'])==0) {
						print "WARNING: no idno value for item ".$current_item['item_id'] . "\n";
						$current_item['idno'] = 'list_item_'.$current_item['item_id'];
					}
					$ca_list_items->load($current_item['item_id']);
					if(in_array($current_item['item_id'],$items_used)) continue;
					$items_used[] = $current_item['item_id'];
					$child_items = $ca_lists->getChildItemsForList($list_id, $current_item['item_id']);
					if(count($child_items)>0) {
						$items[$current_item['idno']] = exp_getList($list_id,$current_item['item_id']);
					} else {
						$is_enabled = $current_item['is_enabled'];
						$is_default = $current_item['is_default'];
						$pref_labels = exp_getPrefLabels($ca_list_items);
						$items[$current_item['idno']] = array('is_enabled'=>$is_enabled,'is_default'=>$is_default,'preferred_labels'=>$pref_labels);
					}
				}
			}
		}
		$data['items'] = $items;
		return $data;
	}
	
	function exp_getElement($element_id) {
		global $o_dm,$attr_types,$ca_metadata_elements,$ca_metadata_type_restrictions,$ca_lists;
		$ca_metadata_elements->load($element_id);
		$pref_labels = exp_getPrefLabels($ca_metadata_elements);
		$type_restrictions = array();
		$restrictions_list = $ca_metadata_elements->getTypeRestrictions();
		$n=1;
		if(is_array($restrictions_list)){
			foreach($restrictions_list as $restriction) {
				$ca_metadata_type_restrictions->load($restriction['restriction_id']);
				if($r_table = $o_dm->getInstanceByTableNum($ca_metadata_type_restrictions->get('table_num'))) {
					$type_name = '';
					if($item = $ca_lists->getItemFromListByItemID($r_table->getTypeListCode(), $ca_metadata_type_restrictions->get('type_id'))) {
						$type_name = $item['idno'];
					}
					$r_settings = $ca_metadata_type_restrictions->getSettings();
					$type_restrictions['r'.$n] = array(
						'table'=>$r_table->tableName(),
						'type'=>$type_name,
						'settings'=>$r_settings
					);
					$n++;
				}
			}
		}
		$datatype_key = $ca_metadata_elements->get('datatype');
		$settings = $ca_metadata_elements->getSettings();
		$documentation_url = $ca_metadata_elements->get('documentation_url');
		
		$child_elements = array();
		$elements_set = $ca_metadata_elements->getElementsInSet($element_id);
	
		$element =  array(
			'datatype'=>$attr_types[$datatype_key],
			'preferred_labels'=>$pref_labels,
			'settings'=>$settings,
			'documentation_url'=>$documentation_url
		);
		if ($vn_list_id = $ca_metadata_elements->get('list_id')) {
			$ca_lists->load($vn_list_id);
			$element['list'] = $ca_lists->get('list_code');
		}
		
		if(is_array($elements_set) && (sizeof($elements_set) > 1)){
			$child_elements = _getSubElementTree($element_id, $elements_set);
		}
		if(count($child_elements)>0) {
			$element['elements'] = $child_elements;	
		}
		if(count($type_restrictions)>0) {
			$element['type_restrictions'] = $type_restrictions;
		}
		return $element;
	}
	
	function _getSubElementTree($pn_parent_id, $pa_element_set) {
		global $o_dm,$attr_types,$ca_metadata_elements,$ca_metadata_type_restrictions,$ca_lists;
		
		$va_elements = array();
		foreach($pa_element_set as $va_element) {
			if ($va_element['parent_id'] == $pn_parent_id) {
				$ca_metadata_elements->load($va_element['element_id']);
				$va_pref_labels = exp_getPrefLabels($ca_metadata_elements);
				$va_elements[$va_element['element_code']] = array(
					'datatype' => $attr_types[$va_element['datatype']],
					'preferred_labels' => $va_pref_labels,
					'documentation_url' => $va_element['documentation_url']
				);
				
				if (is_array($va_element['settings'])) {
					$va_elements[$va_element['element_code']]['settings'] = $va_element['settings'];
				}
				
				if ($vn_list_id = $va_element['list_id']) {
					$ca_lists->load($vn_list_id);
					$va_elements[$va_element['element_code']]['list'] = $ca_lists->get('list_code');
				}
				
				if ($va_tmp = _getSubElementTree($va_element['element_id'], $pa_element_set)) {
					$va_elements[$va_element['element_code']]['elements'] = $va_tmp;
				}
			}
		}
		return $va_elements;
	}
	
	# ====================================
	# Script
	# ====================================
	
	$profile_contents = '';
	
	//write the profile header
	$header_contents = "profile_name = ![export] export_".$ts."\n";
	$header_contents .= "profile_description = Profile export from ".__CA_APP_NAME__." on ".date("D M j G:i:s T Y")."\n";
	$header_contents .= "profile_use_for_configuration = 1\n";
	
	$profile_contents .= $header_contents."\n\n";
	
	$o_dm = Datamodel::load();
	$o_db = new Db();
	$o_db->dieOnError(false);

	//LOCALES
	$ca_locales = new ca_locales();
	$locales = $ca_locales->getLocaleList();
	$locales_profile = array();
	$locales_ref = array();
	foreach($locales as $id=>$info) {
		$locales_profile[$info['language'].'_'.$info['country']] = $info['name'];
		$locales_ref[$info['locale_id']] = $info['language'].'_'.$info['country'];
	}
	$locales_content = exp_arrayToProfile('locales',$locales_profile,0,'Locales definition');
	$profile_contents .= $locales_content."\n\n";

	//LISTS
	$ca_lists = new ca_lists();
	$ca_list_items = new ca_list_items();
	$items_used=array();
	$item_label_fields = $ca_list_items->getLabelUIFields();
	$all_lists = $ca_lists->getListOfLists();
	$lists_profile = array();
	foreach($all_lists as $id=>$info) {
		foreach($info as $profile_id=>$list_info) {
			$lists_profile[$list_info['list_code']] = exp_getList($list_info['list_id']);
		}
		
	}
	$lists_content = exp_arrayToProfile('lists',$lists_profile,0,'Lists definition');
	$profile_contents .= $lists_content."\n\n";

	//ELEMENTS
	$ca_metadata_elements = new ca_metadata_elements();
	$ca_metadata_type_restrictions = new ca_metadata_type_restrictions();
	$attr_types = $ca_metadata_elements->getAttributeTypes();
	$elements_list = $ca_metadata_elements->getRootElementsAsList();
	$elements_profile = array();
	foreach($elements_list as $element) {
		$elements_profile[$element['element_code']] = exp_getElement($element['element_id']);
	}
	$elements_content = exp_arrayToProfile('element_sets',$elements_profile,0,'Metadata element set (attribute) definitions');
	$profile_contents .= $elements_content."\n\n";

	//UI
	$ca_editor_uis = new ca_editor_uis();
	$ca_editor_ui_screens = new ca_editor_ui_screens();
	$ca_editor_ui_bundle_placements = new ca_editor_ui_bundle_placements();
	$ui_list = $ca_editor_uis->getUIList();
	$field_list = $ca_editor_uis->getFieldsArray();
	$editor_types_choice_list = $field_list['editor_type']['BOUNDS_CHOICE_LIST'];
	$ui_profile = array();
	foreach($ui_list as $ui) {
		$ui_key = 'ui_'.$ui['ui_id'];
		$ca_editor_uis->load($ui['ui_id']);
		$pref_labels = exp_getPrefLabels($ca_editor_uis);
		//$editor_type_label = array_search($ui['editor_type'],$editor_types_choice_list);
		
		$t_instance = $o_dm->getInstanceByTableNum($ui['editor_type']);
		
		//$editor_type = 'ca_'.str_replace(' ','_',$editor_type_label);
		$editor_type = $t_instance->tableName();
		
		$ui_profile[$ui_key] = array('preferred_labels'=>$pref_labels,'type'=>$editor_type);
		$screens = $ca_editor_uis->getScreens();
		$screens_profile = array();
		foreach($screens as $screen_id=>$screen_info) {
			$ca_editor_ui_screens->load($screen_id);
			//get bundles
			$bundle_placements = $ca_editor_ui_screens->getBundlePlacements();
			$bundles_profile = array();
			foreach($bundle_placements as $placement_info) {
				$ca_editor_ui_bundle_placements->load($placement_info['placement_id']);
				$bundle_settings = $ca_editor_ui_bundle_placements->getSettings();
				if(strlen($placement_info['bundle_name'])>0) {
					$bundle_settings['bundle'] = $placement_info['bundle_name'];
				} else {
					$bundle_settings['bundle'] = $placement_info['placement_code'];
				}
				$bundles_profile[$placement_info['placement_code']] = $bundle_settings;
			}
			//get preferred labels
			$pref_labels = exp_getPrefLabels($ca_editor_ui_screens);
			$screens_profile[$screen_info['idno']] = array(
				'is_default'=>$ca_editor_ui_screens->get('is_default'),
				'preferred_labels'=>$pref_labels,
				'bundles'=>$bundles_profile
			);
		}
		$ui_profile[$ui_key]['screens'] = $screens_profile;
	}
	$ui_content = exp_arrayToProfile('uis',$ui_profile,0,'User interface definitions');
	$profile_contents .= $ui_content."\n\n";
	
	//RELATIONSHIPS
	$ca_relationship_types = new ca_relationship_types();
	$relationships_profile = array();
	$qr = $o_db->query('
		SELECT DISTINCT table_num 
		FROM ca_relationship_types
		');
	while($qr->nextRow()) {
		$r_table = $o_dm->getInstanceByTableNum($qr->get('table_num'));
		if(!($r_table instanceof BaseRelationshipModel)){ // this catches ca_items_x_tags, which is not a "typical" relationship table
			continue; // TODO: export that relationship type using some other means
		}
		$left_table = $o_dm->getInstanceByTableNum($r_table->getLeftTableNum());
		$right_table = $o_dm->getInstanceByTableNum($r_table->getRightTableNum());
		if(!($right_table instanceof BaseModelWithAttributes) || !($left_table instanceof BaseModelWithAttributes)){
			continue;
		}
		$left_type_list = $left_table->getTypeListCode();
		$right_type_list = $right_table->getTypeListCode();
		//get types
		$types_profile = array();
		$rel_types = $r_table->getRelationshipTypes();
		foreach($rel_types as $rel_type_id=>$rel_info) {
			$ca_relationship_types->load($rel_type_id);
			$pref_labels = exp_getPrefLabels($ca_relationship_types);
			//define sub_types
			$subtype_left = '';
			if($rel_info['sub_type_left_id']) {
				$subtype_left_item = $ca_lists->getItemFromListByItemID($left_type_list, $rel_info['sub_type_left_id']);
				$subtype_left = $subtype_left_item['idno'];
			}
			$subtype_right = '';
			if($rel_info['sub_type_right_id']) {
				$subtype_right_item = $ca_lists->getItemFromListByItemID($right_type_list, $rel_info['sub_type_right_id']);
				$subtype_right = $subtype_right_item['idno'];
			}
			
			if (!$rel_info['type_code']) { $rel_info['type_code'] = 'default'; }
			
			$types_profile[$rel_info['type_code']] = array(
				'is_default'=>$ca_relationship_types->get('is_default'),
				'preferred_labels'=>$pref_labels,
				'subtype_left'=>$subtype_left,
				'subtype_right'=>$subtype_right
			);			
		}
		$relationships_profile[$r_table->tableName()] = array('types'=>$types_profile);
	}
	$rel_content = exp_arrayToProfile('relationship_types',$relationships_profile,0,'Relationship types');
	$profile_contents .= $rel_content."\n\n";

	$fh=fopen($profile_path,'w+');
	fwrite($fh,$profile_contents);
	
	fclose($fh);
?>
