/* Definition of editor custom user interface specification tables */

DROP TABLE ca_editor_uis;
DROP TABLE ca_editor_ui_labels;
DROP TABLE ca_editor_uis_x_user_groups;
DROP TABLE ca_editor_ui_screens;
DROP TABLE ca_editor_ui_screen_labels;
DROP TABLE ca_editor_ui_bundle_placements;

CREATE TABLE ca_editor_uis (
	ui_id int unsigned not null auto_increment,
	user_id int unsigned not null references ca_users(user_id),		/* owner of ui */
	is_system_ui tinyint unsigned not null,
	editor_type tinyint unsigned not null,							/* tablenum of editor */
	
	primary key 				(ui_id),
	index i_user_id				(user_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE ca_editor_ui_labels (
	label_id int unsigned not null auto_increment,
	ui_id int unsigned not null references ca_editor_uis(ui_id),
	name varchar(255) not null,
	description text not null,
	locale_id smallint not null references ca_locales(locale_id),
	
	primary key 				(label_id),
	index i_ui_id				(ui_id),
	index i_locale_id			(locale_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


CREATE TABLE ca_editor_uis_x_user_groups (
	relation_id int unsigned not null auto_increment,
	ui_id int unsigned not null references ca_editor_uis(ui_id),
	group_id int unsigned not null references ca_user_groups(group_id),
	
	primary key 				(relation_id),
	index i_ui_id				(ui_id),
	index i_group_id			(group_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


CREATE TABLE ca_editor_ui_screens (
	screen_id int unsigned not null auto_increment,
	parent_id int unsigned null,
	ui_id int unsigned not null references ca_editor_uis(ui_id),
	
	rank smallint unsigned not null,
	is_default tinyint unsigned not null,
	
	hier_left decimal(30,20) not null,
	hier_right decimal (30,20) not null,
	
	primary key 				(screen_id),
	index i_ui_id 				(ui_id),
	index i_parent_id			(parent_id),
	index i_hier_left			(hier_left),
	index i_hier_right			(hier_right)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE ca_editor_ui_screen_labels (
	label_id int unsigned not null auto_increment,
	screen_id int unsigned not null references ca_editor_ui_screens(screen_id),
	name varchar(255) not null,
	description text not null,
	locale_id smallint not null references ca_locales(locale_id),
	
	primary key 				(label_id),
	index i_screen_id			(screen_id),
	index i_locale_id			(locale_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE ca_editor_ui_bundle_placements (
	placement_id int unsigned not null auto_increment,
	screen_id int unsigned not null references ca_editor_ui_screens(screen_id),
	
	bundle_name varchar(255) not null,
	
	rank smallint unsigned not null,
	
	primary key 				(placement_id),
	index i_screen_id			(screen_id),
	unique index u_bundle_name	(bundle_name, screen_id),
	index i_element_id			(element_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;
