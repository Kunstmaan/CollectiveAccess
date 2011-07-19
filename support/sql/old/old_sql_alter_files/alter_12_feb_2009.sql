/** locale-less attributes **/
ALTER TABLE ca_attributes MODIFY COLUMN locale_id smallint unsigned null;

/** remove obsolete table **/
DROP TABLE ca_sessions;

/** sets table - user defined groupings of items (objects, entities, places, etc.) **/
CREATE TABLE ca_sets (
	set_id		int unsigned not null auto_increment,
	user_id		int unsigned null references ca_users(user_id),
	set_code    varchar(100) not null,
	is_system_set tinyint unsigned not null,
	table_num	tinyint unsigned not null,
	status		tinyint unsigned not null,
	access		tinyint unsigned not null,
	
	primary key (set_id),
	key i_user_id (user_id),
	unique key u_set_code (set_code),
	key i_table_num (table_num)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE ca_set_labels (
	label_id	int unsigned not null auto_increment,
	set_id		int unsigned not null references ca_sets(set_id),
	locale_id	smallint unsigned not null references ca_locales(locale_id),
	
	name		varchar(255) not null,
	description	text not null,
	
	primary key (label_id),
	key i_set_id (set_id),
	key i_locale_id (locale_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE ca_set_items (
	item_id		int unsigned not null auto_increment,
	set_id		int unsigned not null references ca_sets(set_id),
	row_id		int unsigned not null,
	rank		int unsigned not null,
	
	primary key (item_id),
	key i_set_id (set_id),
	key i_row_id (row_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE ca_set_item_labels (
	label_id	int unsigned not null auto_increment,
	item_id		int unsigned not null references ca_set_items(item_id),
	
	locale_id	smallint unsigned not null references ca_locales(locale_id),
	
	caption		text not null,
	description	text not null,
	
	primary key (label_id),
	key i_set_id (item_id),
	key i_locale_id (locale_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/** Revision and generalization of time-based cataloguing tables ("clips") to annotations **/

DROP TABLE ca_representation_annotations;

DROP TABLE ca_clips_x_entities;
DROP TABLE ca_clips_x_objects;
DROP TABLE ca_clips_x_occurrences;
DROP TABLE ca_clips_x_places;
DROP TABLE ca_clips_x_vocabulary_terms;

DROP TABLE ca_representation_clip_labels;
DROP TABLE ca_representation_clips;


/*==============================================================*/
/* Table: ca_representation_annotations                         */
/*==============================================================*/
create table ca_representation_annotations
(
   annotation_id                  int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   user_id                        int unsigned                   null,
   type_code                      varchar(30)                    not null,
   props                          longtext                       not null,
   source_info                    longtext                       not null,
   status                         tinyint unsigned               not null,
   access                         tinyint unsigned               not null,
   primary key (annotation_id),
   constraint fk_reference_283 foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_reference_239 foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
   constraint fk_reference_283x foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/*==============================================================*/
/* Index: i_representation_id                                   */
/*==============================================================*/
create index i_representation_id on ca_representation_annotations
(
   representation_id
);

/*==============================================================*/
/* Index: i_locale_id                                           */
/*==============================================================*/
create index i_locale_id on ca_representation_annotations
(
   locale_id
);

/*==============================================================*/
/* Index: i_user_id                                             */
/*==============================================================*/
create index i_user_id on ca_representation_annotations
(
   user_id
);

/*==============================================================*/
/* Table: ca_representation_annotation_labels                   */
/*==============================================================*/
create table ca_representation_annotation_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   annotation_id                  int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(255)                   not null,
   name_sort                      varchar(255)                   not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   constraint fk_reference_284 foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
   constraint fk_reference_285 foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_reference_286 foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/*==============================================================*/
/* Index: i_annotation_id                                             */
/*==============================================================*/
create index i_annotation_id on ca_representation_annotation_labels
(
   annotation_id
);

/*==============================================================*/
/* Index: i_name                                                */
/*==============================================================*/
create index i_name on ca_representation_annotation_labels
(
   name
);

/*==============================================================*/
/* Index: u_all                                                 */
/*==============================================================*/
create unique index u_all on ca_representation_annotation_labels
(
   name,
   locale_id,
   type_id,
   annotation_id
);

/*==============================================================*/
/* Index: i_locale_id                                           */
/*==============================================================*/
create index i_locale_id on ca_representation_annotation_labels
(
   locale_id
);

/*==============================================================*/
/* Index: i_name_sort                                           */
/*==============================================================*/
create index i_name_sort on ca_representation_annotation_labels
(
   name_sort
);

/*==============================================================*/
/* Index: i_type_id                                             */
/*==============================================================*/
create index i_type_id on ca_representation_annotation_labels
(
   type_id
);


/*==============================================================*/
/* Table: ca_representation_annotations_x_entities                                   */
/*==============================================================*/
create table ca_representation_annotations_x_entities
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   annotation_id                        int unsigned                   not null,
   entity_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_reference_98 foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
   constraint fk_reference_102 foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
   constraint fk_reference_103 foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;



/*==============================================================*/
/* Index: i_entity_id                                           */
/*==============================================================*/
create index i_entity_id on ca_representation_annotations_x_entities
(
   entity_id
);

/*==============================================================*/
/* Index: i_annotation_id                                             */
/*==============================================================*/
create index i_annotation_id on ca_representation_annotations_x_entities
(
   annotation_id
);

/*==============================================================*/
/* Index: i_type_id                                             */
/*==============================================================*/
create index i_type_id on ca_representation_annotations_x_entities
(
   type_id
);

/*==============================================================*/
/* Index: u_all                                                 */
/*==============================================================*/
create unique index u_all on ca_representation_annotations_x_entities
(
   entity_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);

/*==============================================================*/
/* Table: ca_representation_annotations_x_objects                                    */
/*==============================================================*/
create table ca_representation_annotations_x_objects
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   annotation_id                        int unsigned                   not null,
   object_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_reference_111 foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
   constraint fk_reference_112 foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
   constraint fk_reference_113 foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;



/*==============================================================*/
/* Index: i_object_id                                           */
/*==============================================================*/
create index i_object_id on ca_representation_annotations_x_objects
(
   object_id
);

/*==============================================================*/
/* Index: i_annotation_id                                             */
/*==============================================================*/
create index i_annotation_id on ca_representation_annotations_x_objects
(
   annotation_id
);

/*==============================================================*/
/* Index: i_type_id                                             */
/*==============================================================*/
create index i_type_id on ca_representation_annotations_x_objects
(
   type_id
);

/*==============================================================*/
/* Index: u_all                                                 */
/*==============================================================*/
create unique index u_all on ca_representation_annotations_x_objects
(
   object_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);

/*==============================================================*/
/* Table: ca_representation_annotations_x_occurrences                                */
/*==============================================================*/
create table ca_representation_annotations_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   annotation_id                        int unsigned                   not null,
   occurrence_id                  int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_reference_100 foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
   constraint fk_reference_105 foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
   constraint fk_reference_108 foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;



/*==============================================================*/
/* Index: i_occurrence_id                                       */
/*==============================================================*/
create index i_occurrence_id on ca_representation_annotations_x_occurrences
(
   occurrence_id
);

/*==============================================================*/
/* Index: i_annotation_id                                             */
/*==============================================================*/
create index i_annotation_id on ca_representation_annotations_x_occurrences
(
   annotation_id
);

/*==============================================================*/
/* Index: i_type_id                                             */
/*==============================================================*/
create index i_type_id on ca_representation_annotations_x_occurrences
(
   type_id
);

/*==============================================================*/
/* Index: u_all                                                 */
/*==============================================================*/
create unique index u_all on ca_representation_annotations_x_occurrences
(
   occurrence_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);

/*==============================================================*/
/* Table: ca_representation_annotations_x_places                                     */
/*==============================================================*/
create table ca_representation_annotations_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   annotation_id                        int unsigned                   not null,
   place_id                       int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_reference_99 foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
   constraint fk_reference_104 foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
   constraint fk_reference_107 foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;



/*==============================================================*/
/* Index: i_place_id                                            */
/*==============================================================*/
create index i_place_id on ca_representation_annotations_x_places
(
   place_id
);

/*==============================================================*/
/* Index: i_annotation_id                                             */
/*==============================================================*/
create index i_annotation_id on ca_representation_annotations_x_places
(
   annotation_id
);

/*==============================================================*/
/* Index: i_type_id                                             */
/*==============================================================*/
create index i_type_id on ca_representation_annotations_x_places
(
   type_id
);

/*==============================================================*/
/* Index: u_all                                                 */
/*==============================================================*/
create unique index u_all on ca_representation_annotations_x_places
(
   place_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);

/*==============================================================*/
/* Table: ca_representation_annotations_x_vocabulary_terms                           */
/*==============================================================*/
create table ca_representation_annotations_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   annotation_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_reference_101 foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
   constraint fk_reference_106 foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
   constraint fk_reference_235 foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;



/*==============================================================*/
/* Index: i_label_id                                            */
/*==============================================================*/
create index i_item_id on ca_representation_annotations_x_vocabulary_terms
(
   item_id
);

/*==============================================================*/
/* Index: i_annotation_id                                             */
/*==============================================================*/
create index i_annotation_id on ca_representation_annotations_x_vocabulary_terms
(
   annotation_id
);

/*==============================================================*/
/* Index: i_type_id                                             */
/*==============================================================*/
create index i_type_id on ca_representation_annotations_x_vocabulary_terms
(
   type_id
);

/*==============================================================*/
/* Index: u_all                                                 */
/*==============================================================*/
create unique index u_all on ca_representation_annotations_x_vocabulary_terms
(
   type_id,
   annotation_id,
   sdatetime,
   edatetime,
   item_id
);


/** Rank fields for orderable relationships **/
ALTER TABLE ca_objects_x_object_representations DROP COLUMN rank;
ALTER TABLE ca_objects_x_object_representations ADD COLUMN rank int unsigned not null;

ALTER TABLE ca_representations_x_occurrences ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_representations_x_places ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_object_lot_events_x_storage_locations ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_collections_x_collections ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_objects_x_collections ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_objects_x_objects ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_objects_x_object_events ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_objects_x_occurrences ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_objects_x_places ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_object_events_x_occurrences ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_object_events_x_places ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_object_events_x_storage_locations ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_object_lots_x_collections ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_object_lots_x_occurrences ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_object_lots_x_places ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_occurrences_x_collections ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_occurrences_x_occurrences ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_entities_x_collections ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_places_x_collections ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_places_x_occurrences ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_places_x_places ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_entities_x_occurrences ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_entities_x_places ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_representations_x_entities ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_entities_x_entities ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_groups_x_roles ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_list_items_x_list_items ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_users_x_groups ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_users_x_roles ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_objects_x_vocabulary_terms ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_object_lots_x_entities ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_objects_x_entities ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_places_x_vocabulary_terms ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_occurrences_x_vocabulary_terms ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_object_events_x_entities ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_collections_x_vocabulary_terms ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_entities_x_vocabulary_terms ADD COLUMN rank int unsigned not null;

/*==============================================================*/
/* Table for MYSQL Fulltext-based search backend                */
/*==============================================================*/
CREATE TABLE IF NOT EXISTS ca_mysql_fulltext_search (
	index_id			int unsigned		not null auto_increment,
	
	table_num			tinyint unsigned 	not null,
	row_id				int unsigned 		not null,
	
	field_table_num		tinyint unsigned	not null,
	field_num			tinyint unsigned	not null,
	field_row_id		int unsigned		not null,
	
	fieldtext			longtext 			not null,
	
	PRIMARY KEY								(index_id),
	FULLTEXT INDEX		f_fulltext			(fieldtext),
	INDEX				i_table_num			(table_num),
	INDEX				i_row_id			(row_id),
	INDEX				i_field_table_num	(field_table_num),
	INDEX				i_field_num			(field_num),
	INDEX				i_field_row_id		(field_row_id)
	
) TYPE=myisam character set utf8 collate utf8_general_ci;

/*==============================================================*/
/* Tables for MYSQL inverted index in a table search backend    */
/*==============================================================*/
CREATE TABLE IF NOT EXISTS ca_sql_search_word_index (
	index_id			int unsigned		not null auto_increment,
	
	table_num			tinyint unsigned 	not null,
	row_id				int unsigned 		not null,
	
	field_table_num		tinyint unsigned	not null,
	field_num			tinyint unsigned	not null,
	field_row_id		int unsigned		not null,
	
	word_id				int unsigned		not null references ca_sql_search_words(word_id),
	seq					int unsigned		not null,
	
	PRIMARY KEY								(index_id),
	
	INDEX				i_table_num			(table_num),
	INDEX				i_row_id			(row_id),
	INDEX				i_field_table_num	(field_table_num),
	INDEX				i_field_num			(field_num),
	INDEX				i_field_row_id		(field_row_id),
	INDEX				i_word_id			(word_id)
) TYPE=innodb character set utf8 collate utf8_general_ci;

# -----------------------------------------------------------------

CREATE TABLE IF NOT EXISTS ca_sql_search_text (
	index_id			int unsigned		not null auto_increment,
	
	table_num			tinyint unsigned 	not null,
	row_id				int unsigned 		not null,
	
	field_table_num		tinyint unsigned	not null,
	field_num			tinyint unsigned	not null,
	field_row_id		int unsigned		not null,
	
	fieldtext			longtext 			not null,
	
	PRIMARY KEY								(index_id),
	FULLTEXT INDEX		f_fulltext			(fieldtext),
	INDEX				i_table_num			(table_num),
	INDEX				i_row_id			(row_id),
	INDEX				i_field_table_num	(field_table_num),
	INDEX				i_field_num			(field_num),
	INDEX				i_field_row_id		(field_row_id)
	
) TYPE=myisam character set utf8 collate utf8_general_ci;

# -----------------------------------------------------------------

CREATE TABLE IF NOT EXISTS ca_sql_search_date_index (	
	table_num			tinyint unsigned 	not null,
	row_id				int unsigned 		not null,
	
	field_table_num		tinyint unsigned	not null,
	field_num			tinyint unsigned	not null,
	field_row_id		int unsigned		not null,
	
	sdatetime			decimal(20,10)		not null,
	edatetime			decimal(20,10)		not null,
	seq					int unsigned		not null,
	
	PRIMARY KEY								(table_num, row_id, field_table_num, field_num, field_row_id, sdatetime, edatetime, seq),
	INDEX				i_table_num			(table_num),
	INDEX				i_row_id			(row_id),
	INDEX				i_field_table_num	(field_table_num),
	INDEX				i_field_num			(field_num),
	INDEX				i_field_row_id		(field_row_id),
	INDEX				i_date				(sdatetime, edatetime)
) TYPE=innodb character set utf8 collate utf8_general_ci;

# -----------------------------------------------------------------

CREATE TABLE IF NOT EXISTS ca_sql_search_words (
	word_id				int unsigned 		not null auto_increment,
	word				varchar(255)		not null,
	soundex				varchar(4)			not null,
	metaphone			varchar(255)		not null,
	stem				varchar(255)		not null,
	
	PRIMARY KEY								(word_id),
	UNIQUE INDEX		u_word				(word),
	INDEX				i_soundex			(soundex),
	INDEX				i_metaphone			(metaphone),
	INDEX				i_stem				(stem)
) TYPE=innodb character set utf8 collate utf8_general_ci;

# -----------------------------------------------------------------

CREATE TABLE IF NOT EXISTS ca_sql_search_ngrams (
	word_id				int unsigned		not null references ca_sql_search_words(word_id),
	ngram				char(4)				not null,
	seq					tinyint unsigned	not null,
	
	PRIMARY KEY								(word_id, seq),
	INDEX				i_ngram				(ngram)
) TYPE=innodb character set utf8 collate utf8_general_ci;
