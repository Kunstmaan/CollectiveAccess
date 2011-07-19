CREATE TABLE ca_item_views (
	view_id		int unsigned not null auto_increment,
	table_num	tinyint unsigned not null,
	row_id		int unsigned not null,
	
	user_id		int unsigned null references ca_users(user_id),
	locale_id	smallint unsigned not null references ca_locales(locale_id),
	
	viewed_on	int unsigned not null,
	ip_addr		varchar(39) null,
	
	primary key (view_id),
	key i_row_id (row_id),
	key i_table_num (table_num),
	key i_user_id (user_id),
	key i_created_on (viewed_on)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;