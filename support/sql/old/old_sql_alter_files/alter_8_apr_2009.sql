/* MIGRATION FOR dhenry MODIFICATION TO ca_editor_ui_screens */
ALTER TABLE ca_editor_ui_screens ADD COLUMN idno varchar(255) not null;

/* SUPPORT FOR SETTINGS ON INDIVIDUAL BUNDLE PLACEMENTS */
/* THIS WILL ALLOW US TO, FOR EXAMPLE, RESTRICT THE SCOPE OF THE ca_occurrences BUNDLE TO SPECIFIC OCCURRENCE TYPES */
ALTER TABLE ca_editor_ui_bundle_placements ADD COLUMN settings longtext not null;

/* ALTERATIONS TO ca_sets STUFF */
ALTER TABLE ca_sets DROP COLUMN is_system_set;
ALTER TABLE ca_set_labels DROP COLUMN description;
ALTER TABLE ca_set_item_labels DROP COLUMN description;

/* SUPPORT FOR GRANTING ACCESS TO SETS TO USER GROUPS */
CREATE TABLE ca_sets_x_user_groups (
	relation_id int unsigned not null auto_increment,
	set_id int unsigned not null references ca_sets(set_id),
	group_id int unsigned not null references ca_user_groups(group_id),
	access tinyint unsigned not null,
	
	primary key 				(relation_id),
	index i_set_id				(set_id),
	index i_group_id			(group_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/* SUPPORT FOR OBJECT REPRESENTATION LABELS - FOR CONSISTENCY WITH OTHER EDITABLE OBJECT (and it's kinda nice to be able to do) */
create table ca_object_representation_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   constraint fk_reference_248ax foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_reference_146ax foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_reference_74ax foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/* SUPPORT FOR RESTRICTION OF SCREENS TO SPECIFIC ITEM TYPES */
/* ANALOGOUS TO ca_metadata_type_restrictions TABLE FOR ATTRIBUTES */
create table ca_editor_ui_screen_type_restrictions (
   restriction_id                 int unsigned                   not null AUTO_INCREMENT,
   table_num                      tinyint unsigned               not null,
   type_id                        int unsigned,
   screen_id                      int unsigned              not null,
   settings                       longtext                       not null,
   rank                           smallint unsigned              not null,
   primary key (restriction_id),
   constraint fk_reference_16xx foreign key (screen_id)
      references ca_editor_ui_screens (screen_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==============================================================*/
/* Tables to support user commenting, rating and tagging        */
/*==============================================================*/
CREATE TABLE ca_item_comments (
	comment_id	int unsigned not null auto_increment,
	table_num	tinyint unsigned not null,
	row_id		int unsigned not null,
	
	user_id		int unsigned null references ca_users(user_id),
	locale_id	smallint unsigned not null references ca_locales(locale_id),
	
	comment		text not null,
	rating		tinyint not null,
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
