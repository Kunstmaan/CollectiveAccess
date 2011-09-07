<?php
	/**
	 * Use this install script to get the database up to date.
	 */

	require_once("../../../setup.php");
	require_once(__CA_LIB_DIR__."/core/Db.php");

	$o_db = new Db();

	if(!table_exists('ca_user_annotations', $o_db)) {
		// 1. create the table
		$vs_statement = "
			CREATE TABLE ca_user_annotations (
				user_annotation_id	int unsigned not null auto_increment,
				row_id		int unsigned not null,

				user_id		int unsigned null references ca_users(user_id),
				locale_id	smallint unsigned not null references ca_locales(locale_id),

				original_top		int unsigned not null,
				original_left		int unsigned not null,
				original_width		int unsigned not null,
				original_height		int unsigned not null,
				annotation		text null,

				email		varchar(255),
				name		varchar(255),
				created_on	int unsigned not null,
				ip_addr		varchar(39) null,

				moderated_on int unsigned null,
				moderated_by_user_id int unsigned null references ca_users(user_id),

				primary key (user_annotation_id),
				key i_row_id (row_id),
				key i_email (email),
				key i_user_id (user_id),
				key i_created_on (created_on),
				key i_moderated_on (moderated_on)
			) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;
		";

		$o_db->query($vs_statement);

		echo "Table ca_user_annotations created! \n";
	} else {
		echo "Table ca_user_annotations already exists! \n";
	}

	// 2. create a symlink
	if(symlink(__CA_APP_DIR__.'/plugins/userImageAnnotations/models/ca_user_annotations.php', __CA_MODELS_DIR__.'/ca_user_annotations.php')) {
		echo "Model symlink created! \n";
	} else {
		echo "Model symlink failed creating! \n";
	}

	/**
	 * 3.
	 * Add this line to the datamodel.conf
	 * "ca_user_annotations"						= 150,
	 */


	function table_exists($table='', $o_db = NULL) {
		if(empty($table) || is_null($o_db)) {
			return false;
		}

		$qr_tables = $o_db->query("SHOW TABLES");

		while($qr_tables->nextRow()) {
			$vs_table = $qr_tables->getFieldAtIndex(0);
			if ($vs_table == $table) {
				return true;
			}
		}
		return false;
	}

?>