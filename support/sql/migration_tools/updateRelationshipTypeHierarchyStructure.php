#!/usr/local/bin/php
<?php
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	if (!$argv[1]) {
		die("\updateRelationshipTypeHierarchyStructure.php: Updates relationship type data contained in ca_relationship_types tables of installations made prior to 25 November 2009 to conform to new hierarchical arrangement. If you don't know why you're running this, you shouldn't be running it.\n\nUSAGE: updateRelationshipTypeHierarchyStructure.php 'instance_name'\nExample: ./updateRelationshipTypeHierarchyStructure.php 'www.mycollection.org'\n");
	}
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	
	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/core/Db.php");
	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
	require_once(__CA_MODELS_DIR__."/ca_relationship_types.php");
	require_once(__CA_MODELS_DIR__.'/ca_locales.php');
	
	
	$_ = new Zend_Translate('gettext', __CA_APP_DIR__.'/locale/en_US/messages.mo', 'en_US');
	
	$t_locale = new ca_locales();
	$pn_locale_id = $t_locale->loadLocaleByCode('en_US');		// default locale_id
	
	$o_db = new Db();
	$o_dm = new Datamodel();
	
	$va_tables = $o_dm->getTableNames();

	foreach($va_tables as $vs_table) {
		if (!preg_match('!_x_!', $vs_table)) { continue; }
		print "UPDATING RELATIONSHIPS FOR {$vs_table}\n";
		require_once(__CA_MODELS_DIR__."/".$vs_table.".php");
		$t_table = new $vs_table;
		$vs_pk = $t_table->primaryKey();
		$vn_table_num = $t_table->tableNum();
		
		//$qr_res = $o_db->query('SELECT '.$vs_pk.' FROM '.$vs_table);
		
		
		// Create root ca_relationship_types row for table
		$t_root = new ca_relationship_types();
		if (!$t_root->load(array('type_code' => 'root_for_table_'.$vn_table_num))) {
			$t_root->setMode(ACCESS_WRITE);
			$t_root->set('table_num', $vn_table_num);
			$t_root->set('type_code', 'root_for_table_'.$vn_table_num);
			$t_root->set('rank', 1);
			$t_root->set('is_default', 0);
			$t_root->set('parent_id', null);
			$t_root->insert();
			
			if ($t_root->numErrors()) {
				print "\tERROR INSERTING ROOT FOR TABLE {$vs_table}/{$vn_table_num}: ".join('; ', $t_root->getErrors())."\n";
				continue;
			}
			$t_root->addLabel(
				array(
					'typename' => 'Root for table '.$vn_table_num,
					'typename_reverse' => 'Root for table '.$vn_table_num
				), $pn_locale_id, null, true
			);
			if ($t_root->numErrors()) {
				print "\tERROR ADDING LABEL TO ROOT FOR TABLE {$vs_table}/{$vn_table_num}: ".join('; ', $t_root->getErrors())."\n";
			}
		}
		
		$vn_root_id = $t_root->getPrimaryKey();
		
		// Move existing types under root
		$qr_types = $o_db->query("
			UPDATE ca_relationship_types
			SET parent_id = ?, hier_type_id = ?
			WHERE
				(table_num = ?) AND (type_id <> ?)
		", (integer)$vn_root_id, (integer)$vn_root_id, (integer)$vn_table_num, (integer)$vn_root_id);
	}
	
	$t_root->rebuildAllHierarchicalIndexes();
?>
