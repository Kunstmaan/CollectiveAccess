/*
 	Tables to support user commenting, rating and tagging
*/
CREATE TABLE ca_item_comments (
	comment_id	int unsigned not null auto_increment,
	table_num	tinyint unsigned not null,
	row_id		int unsigned not null,
	
	user_id		int unsigned null references ca_users(user_id),
	locale_id	smallint unsigned not null references ca_locales(locale_id),
	
	comment		text not null,
	email		varchar(255),
	name		varchar(255),
	
	primary key (comment_id),
	key i_row_id (row_id),
	key i_table_num (table_num),
	key i_email (email),
	key i_user_id (user_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE ca_item_tags (
	tag_id		int unsigned not null auto_increment,

	locale_id	smallint unsigned not null references ca_locales(locale_id),
	tag			varchar(255) not null,
	
	primary key (tag_id),
	key u_tag (tag, locale_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE ca_items_x_tags (
	relation_id	int unsigned not null auto_increment,
	table_num	tinyint unsigned not null,
	row_id		int unsigned not null,
	
	tag_id		int unsigned not null references ca_tags(tag_id),
	
	user_id		int unsigned null references ca_users(user_id),
	
	created_on	int unsigned not null,
	
	primary key (relation_id),
	key i_row_id (row_id),
	key i_table_num (table_num),
	key i_tag_id (tag_id),
	key i_user_id (user_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;
