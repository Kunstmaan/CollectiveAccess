<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Search/Common/Solr/SolrConfiguration.php : 
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

require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__."/core/Search/SearchBase.php");
require_once(__CA_LIB_DIR__."/core/Zend/Cache.php");


class SolrConfiguration {
	# ------------------------------------------------
	public function __construct(){
		// noop
	}
	# ------------------------------------------------
	public static function updateSolrConfiguration($pb_invoked_from_command_line=false){
		/* get search and search indexing configuration */
		$po_app_config = Configuration::load();
		$po_search_config = Configuration::load($po_app_config->get("search_config"));
		$po_search_indexing_config = Configuration::load($po_search_config->get("search_indexing_config"));

		$ps_solr_home_dir = $po_search_config->get('search_solr_home_dir');

		$po_datamodel = Datamodel::load();
		$po_search_base = new SearchBase();
		global $o_db;
		if(!is_object($o_db)){ /* catch command line usage */
			$o_db = new Db();
		}

		/* parse search indexing configuration to see which tables are indexed */
		$va_tables = $po_search_indexing_config->getAssocKeys();

		/* create solr.xml first to support multicore */
		$vs_solr_xml = "";
		$vs_solr_xml.='<?xml version="1.0" encoding="UTF-8" ?>'.SolrConfiguration::nl();
		$vs_solr_xml.='<solr persistent="true">'.SolrConfiguration::nl();
		$vs_solr_xml.=SolrConfiguration::tabs(1).'<cores adminPath="/admin/cores">'.SolrConfiguration::nl();
		foreach($va_tables as $vs_table){
			/* I don't like tablenums, so we use the table name to name the cores */
			$vs_solr_xml.=SolrConfiguration::tabs(2).'<core name="'.$vs_table.'" instanceDir="'.$vs_table.'" />'.SolrConfiguration::nl();
		}
		$vs_solr_xml.=SolrConfiguration::tabs(1).'</cores>'.SolrConfiguration::nl();
		$vs_solr_xml.='</solr>'.SolrConfiguration::nl();

		/* try to write configuration file */
		$vr_solr_xml_file = fopen($ps_solr_home_dir."/solr.xml", 'w+'); // overwrite old one
		if(!is_resource($vr_solr_xml_file)) {
			die("Couldn't write to solr.xml file in Solr home directory. Please check the permissions.\n");
		}
		fprintf($vr_solr_xml_file,"%s",$vs_solr_xml);
		fclose($vr_solr_xml_file);

		/* configure the cores */
		foreach($va_tables as $vs_table){
			/* create core directory */
			if(!file_exists($ps_solr_home_dir."/".$vs_table)){
				if(!mkdir($ps_solr_home_dir."/".$vs_table, 0777)){ /* TODO: think about permissions */
					die("Couldn't create directory in Solr home. Please check the permissions.\n");
				}
			}

			/* create conf directory */
			if(!file_exists($ps_solr_home_dir."/".$vs_table."/conf")){
				if(!mkdir($ps_solr_home_dir."/".$vs_table."/conf", 0777)){
					die("Couldn't create directory in core directory. Please check the permissions.\n");
				}
			}

			/* create solrconfig.xml for this core */
			$vr_solrconfig_xml_file = fopen($ps_solr_home_dir."/".$vs_table."/conf/solrconfig.xml", 'w+');
			if(!is_resource($vr_solrconfig_xml_file)){
				die("Couldn't write to solrconfig.xml file for core $vs_table. Please check the permissions.\n");
			}
			/* read template and copy it */
			$va_solrconfig_xml_template = file(__CA_LIB_DIR__."/core/Search/Solr/solrplugin_templates/solrconfig.xml");
			if(!is_array($va_solrconfig_xml_template)){
				die("Couldn't read solrconfig.xml template.");
			}
			foreach($va_solrconfig_xml_template as $vs_line){
				fprintf($vr_solrconfig_xml_file,"%s",$vs_line);
			}
			fclose($vr_solrconfig_xml_file);

			/* create schema.xml for this core */
			$vr_schema_xml_file = fopen($ps_solr_home_dir."/".$vs_table."/conf/schema.xml", 'w+');
			if(!is_resource($vr_schema_xml_file)){
				die("Couldn't write to schema.xml file for core $vs_table. Please check the permissions.\n");
			}
			/* read template, modify it, add table-specific fields and write to schema.xml configuration for this core */
			$va_schema_xml_template = file(__CA_LIB_DIR__."/core/Search/Solr/solrplugin_templates/schema.xml");
			if(!is_array($va_schema_xml_template)){
				die("Couldn't read solrconfig.xml template.");
			}
			foreach($va_schema_xml_template as $vs_line){
				/* 1st replacement: core name */
				if(strpos($vs_line,"CORE_NAME")!==false){
					fprintf($vr_schema_xml_file,"%s",str_replace("CORE_NAME",$vs_table,$vs_line));
					continue;
				}
				/* 2nd replacement: fields - the big part */
				if(strpos($vs_line,"<!--FIELDS-->")!==false){
					$vs_field_schema = "";
					$vs_subject_table_copyfields = "";
					/* the schema is very very hardcoded, so we have to create a design that still fits
					 * when new metadata elements are created or sth like that. for now, we're just considering
					 * the "straightforward" fields
					 */
					$va_schema_fields = array(); /* list of all fields created - is used for copyField directives after field block */
					/* subject table */
					/* we add the PK - this is used for incremental indexing */
					$vs_field_schema.=SolrConfiguration::tabs(2).'<field name="'.$vs_table.'.'.
						$po_datamodel->getTableInstance($vs_table)->primaryKey()
						.'" type="string" indexed="true" stored="true" />'.SolrConfiguration::nl();
					$vs_field_schema.=SolrConfiguration::tabs(2).'<field name="'.
						$po_datamodel->getTableInstance($vs_table)->primaryKey()
						.'" type="string" indexed="true" stored="true" />'.SolrConfiguration::nl();
					$vs_subject_table_copyfields.=SolrConfiguration::tabs(1).'<copyField source="'.$vs_table.'.'.
						$po_datamodel->getTableInstance($vs_table)->primaryKey().
						'" dest="'.$po_datamodel->getTableInstance($vs_table)->primaryKey().'" />'.SolrConfiguration::nl();
					
					/* get fields-to-index from search indexing configuration */
					$va_table_fields = $po_search_base->getFieldsToIndex($vs_table);

					/* replace virtual _metadata field with actual _ca_attribute_N type fields */
					if(isset($va_table_fields['_metadata'])){
						unset($va_table_fields['_metadata']);
						$vn_table_num = $po_datamodel->getTableNum($vs_table);
						$qr_type_restrictions = $o_db->query('
							SELECT DISTINCT element_id
							FROM ca_metadata_type_restrictions
							WHERE table_num = ?
						',(int)$vn_table_num);
						$va_type_restrictions = array();
						while($qr_type_restrictions->nextRow()){
							$va_type_restrictions[] = $qr_type_restrictions->get('element_id');
						}
						foreach($va_type_restrictions as $vn_element_id){
							$va_table_fields['_ca_attribute_'.$vn_element_id] = array();
						}
					}

					/* we now have the current configuration */

					/* since Solr supports live updates only if changes are 'backwards-compatible'
					 * (i.e. no fields are deleted), we have to merge the current configuration with the
					 * cached one, create the new configuration based upon that and cache it.
					 *
					 * Invocation of the command-line script support/utils/createSolrConfiguration.php,
					 * however, creates a completely fresh configuration and caches it.
					 */

					$va_frontend_options = array(
						'lifetime' => null, 				/* cache lives forever (until manual destruction) */
						'logging' => false,					/* do not use Zend_Log to log what happens */
						'write_control' => true,			/* immediate read after write is enabled (we don't write often) */
						'automatic_cleaning_factor' => 0, 	/* no automatic cache cleaning */
						'automatic_serialization' => true	/* we store arrays, so we have to enable that */
					);
					$vs_cache_dir = __CA_APP_DIR__.'/tmp';

					$va_backend_options = array(
						'cache_dir' => $vs_cache_dir,		/* where to store cache data? */
						'file_locking' => true,				/* cache corruption avoidance */
						'read_control' => false,			/* no read control */
						'file_name_prefix' => 'ca_cache',	/* prefix of cache files */
						'cache_file_umask' => 0777			/* permissions of cache files */
					);
					$vo_cache = Zend_Cache::factory('Core', 'File', $va_frontend_options, $va_backend_options);

					if (!($va_cache_data = $vo_cache->load('ca_search_indexing_info_'.$vs_table))) {
						$va_cache_data = array();
					}

					if(!$pb_invoked_from_command_line){
						$va_table_fields = array_merge($va_cache_data,$va_table_fields);
					}
					
					$vo_cache->save($va_table_fields,'ca_search_indexing_info_'.$vs_table);

					if(is_array($va_table_fields)){
						foreach($va_table_fields as $vs_field_name => $va_field_options){
							if(in_array("STORE",$va_field_options)){
								$vb_field_is_stored = true;
							} else {
								$vb_field_is_stored = false;
							}
							if(in_array("DONT_TOKENIZE",$va_field_options)){
								$vb_field_is_tokenized = false;
							} else {
								$vb_field_is_tokenized = true;
							}

							$va_schema_fields[] = $vs_table.'.'.$vs_field_name;
							$vs_field_schema.=SolrConfiguration::tabs(2).'<field name="'.$vs_table.'.'.$vs_field_name.'" type="';
							$vb_field_is_tokenized ? $vs_field_schema.='text' : $vs_field_schema.='string';
							$vs_field_schema.='" indexed="true" ';
							$vb_field_is_stored ? $vs_field_schema.='stored="true" ' : $vs_field_schema.='stored="false" ';
							$vs_field_schema.='/>'.SolrConfiguration::nl();
						}
					}
					/* related tables */
					$va_related_tables = $po_search_base->getRelatedIndexingTables($vs_table);
					foreach($va_related_tables as $vs_related_table){
						$va_related_table_fields = $po_search_base->getFieldsToIndex($vs_table, $vs_related_table);
						foreach($va_related_table_fields as $vs_related_table_field => $va_related_table_field_options){
							if(in_array("STORE",$va_related_table_field_options)){
								$vb_field_is_stored = true;
							} else {
								$vb_field_is_stored = false;
							}
							if(in_array("DONT_TOKENIZE",$va_related_table_field_options)){
								$vb_field_is_tokenized = false;
							} else {
								$vb_field_is_tokenized = true;
							}
							$va_schema_fields[] = $vs_related_table.'.'.$vs_related_table_field;
							$vs_field_schema.=SolrConfiguration::tabs(2).'<field name="'.$vs_related_table.'.'.$vs_related_table_field.'" type="';
							$vb_field_is_tokenized ? $vs_field_schema.='text' : $vs_field_schema.='string';
							$vs_field_schema.='" indexed="true" ';
							$vb_field_is_stored ? $vs_field_schema.='stored="true" ' : $vs_field_schema.='stored="false" ';
							$vs_field_schema.='/>'.SolrConfiguration::nl();
						}
					}
					/* write field indexing config into file */
					fprintf($vr_schema_xml_file,"%s",$vs_field_schema);

					/* copyfield directives
					 * we use a single field in each index (called "text") where
					 * all other fields are copied. the text field is the default
					 * search field. it is used if a field name specification is
					 * omitted in a search query.
					 */
					$vs_copyfields = "";
					foreach($va_schema_fields as $vs_schema_field){
						$vs_copyfields.= SolrConfiguration::tabs(1).'<copyField source="'.$vs_schema_field.'" dest="text" />'.SolrConfiguration::nl();
					}
					continue;
				}
				/* 3rd replacement: uniquekey */
				if(strpos($vs_line,"<!--KEY-->")!==false){
					$vs_pk = $po_datamodel->getTableInstance($vs_table)->primaryKey();
					fprintf($vr_schema_xml_file,"%s",str_replace("<!--KEY-->",$vs_table.".".$vs_pk,$vs_line));
					continue;
				}
				/* 4th replacement: copyFields */
				if(strpos($vs_line,"<!--COPYFIELDS-->")!==false){
					/* $vs_copyfields *should* be set, otherwise the template has been messed up */
					fprintf($vr_schema_xml_file,"%s",$vs_copyfields);
					// add copyField for the subject table fields so that the pk can be adressed in 2 ways:
					// "objects.object_id" or "object.id"
					fprintf($vr_schema_xml_file,"%s",$vs_subject_table_copyfields);
					continue;
				}
				/* "normal" line */
				fprintf($vr_schema_xml_file,"%s",$vs_line);
			}
			fclose($vr_schema_xml_file);
		}
	}
	# ------------------------------------------------
	// formatting helpers
	# ------------------------------------------------
	private static function nl(){
		return "\n";
	}
	# ------------------------------------------------
	private static function tabs($pn_num_tabs){
		$vs_return = "";
		for($i=0;$i<$pn_num_tabs;$i++){
			$vs_return.="\t";
		}
		return $vs_return;
	}
	# ------------------------------------------------
}