#!/usr/local/bin/php
<?php
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	if (!$argv[1]) {
		die("12_install_representation_types.php: Installs newly required (as of migration 12 - 28 February 2010) object_representation_types list.\n\nUSAGE: 12_install_representation_types.php 'instance_name'\nExample: ./12_install_representation_types.php 'www.mycollection.org'\n");
	}
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	
	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/core/Db.php");
	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
	require_once(__CA_MODELS_DIR__."/ca_lists.php");
	require_once(__CA_MODELS_DIR__.'/ca_locales.php');
	
	
	$_ = new Zend_Translate('gettext', __CA_APP_DIR__.'/locale/en_US/messages.mo', 'en_US');
	
	$t_locale = new ca_locales();
	$pn_locale_id = $t_locale->loadLocaleByCode('en_US');		// default locale_id
	
	$t_list = new ca_lists();
	$t_list->setMode(ACCESS_WRITE);
	$t_list->set('list_code', 'object_representation_types');
	$t_list->set('is_system_list', 1);
	$t_list->set('is_hierarchical', 1);
	$t_list->set('is_vocabulary', 0);
	$t_list->insert();
	
	if($t_list->numErrors()) {
		die("ERROR: Could not create 'object_representation_types' list: ".join('; ', $t_list->getErrors())."\n");
	}
	
	$t_list->addLabel(
		array('name' => 'Object representation types'),
		$pn_locale_id, null, true
	);
	
	if($t_list->numErrors()) {
		die("ERROR: Could not create label for 'object_representation_types' list: ".join('; ', $t_list->getErrors())."\n");
	}
	
	$t_item = $t_list->addItem(
		'default', true, true, null, null, 'default'
	);
	
	if(!$t_item) {
		die("ERROR: Could not create default list item for 'object_representation_types' list: ".join('; ', $t_list->getErrors())."\n");
	}
	
	$t_item->addLabel(
		array('name_singular' => 'Default', 'name_plural' => 'Default'),
		$pn_locale_id, null, true
	);
	
	$o_db = new Db();
	$o_db->query('UPDATE ca_object_representations SET type_id = ?', $t_item->getPrimaryKey());
	
	print "UPDATE COMPLETE\n";
?>
