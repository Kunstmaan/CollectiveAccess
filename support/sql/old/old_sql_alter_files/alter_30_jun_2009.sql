CREATE TABLE IF NOT EXISTS ca_mysql_fulltext_date_search (
	index_id			int unsigned		not null auto_increment,
	
	table_num			tinyint unsigned 	not null,
	row_id				int unsigned 		not null,
	
	field_table_num		tinyint unsigned	not null,
	field_num			tinyint unsigned	not null,
	field_row_id		int unsigned		not null,
	
	sdatetime			decimal(30,20) 		not null,
	edatetime			decimal(30,20) 		not null,
	
	PRIMARY KEY								(index_id),
	INDEX				i_table_num			(table_num),
	INDEX				i_row_id			(row_id),
	INDEX				i_field_table_num	(field_table_num),
	INDEX				i_field_num			(field_num),
	INDEX				i_field_row_id		(field_row_id),
	INDEX				i_sdatetime			(sdatetime),
	INDEX				i_edatetime			(edatetime)
	
) TYPE=innodb character set utf8 collate utf8_general_ci;