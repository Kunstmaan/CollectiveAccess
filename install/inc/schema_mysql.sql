/*==========================================================================*/
create table ca_locales
(
   locale_id                      smallint unsigned              not null AUTO_INCREMENT,
   name                           varchar(255)                   not null,
   language                       varchar(3)                     not null,
   country                        char(2)                        not null,
   dialect                        varchar(8),
   dont_use_for_cataloguing	tinyint unsigned not null,
   primary key (locale_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index u_language_country on ca_locales(language, country);


/*==========================================================================*/
create table ca_users
(
   user_id                        int unsigned                   not null AUTO_INCREMENT,
   user_name                      varchar(255)                   not null,
   userclass                      tinyint unsigned                not null,
   password                       varchar(100)                   not null,
   fname                          varchar(255)                   not null,
   lname                          varchar(255)                   not null,
   email                          varchar(255)                   not null,
   vars                           longtext                       not null,
   volatile_vars               text                       not null,
   active                         tinyint unsigned               not null,
   confirmed_on                   int unsigned,
   confirmation_key               char(32),
   primary key (user_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create unique index u_user_name on ca_users(user_name);
create unique index u_confirmation_key on ca_users(confirmation_key);
create index i_userclass on ca_users(userclass);


/*==========================================================================*/
create table ca_application_vars
(
   vars                           longtext                       not null
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_change_log
(
   log_id                         bigint                         not null AUTO_INCREMENT,
   log_datetime                   int unsigned                   not null,
   user_id                        int unsigned,
   changetype                     char(1)                        not null,
   logged_table_num               tinyint unsigned               not null,
   logged_row_id                  int unsigned                   not null,
   snapshot                       longblob                       not null,
   rolledback                     tinyint unsigned               not null,
   unit_id                        char(32),
   primary key (log_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_datetime on ca_change_log(log_datetime);
create index i_user_id on ca_change_log(user_id);
create index i_logged on ca_change_log(logged_row_id, logged_table_num);
create index i_unit_id on ca_change_log(unit_id);


/*==========================================================================*/
create table ca_change_log_subjects
(
   log_id                         bigint                         not null,
   subject_table_num              tinyint unsigned               not null,
   subject_row_id                 int unsigned                   not null,
   
   constraint fk_ca_change_log_subjects_log_id foreign key (log_id)
      references ca_change_log (log_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_log_id on ca_change_log_subjects(log_id);
create index i_subject on ca_change_log_subjects(subject_row_id, subject_table_num);


/*==========================================================================*/
create table ca_eventlog
(
   date_time                      int unsigned                   not null,
   code                           CHAR(4)                        not null,
   message                        text                           not null,
   source                         varchar(255)                   not null
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_when on ca_eventlog(date_time);
create index i_source on ca_eventlog(source);


/*==========================================================================*/
create table ca_lists
(
   list_id                        smallint unsigned              not null AUTO_INCREMENT,
   list_code                      varchar(100)                   not null,
   is_system_list                 tinyint unsigned               not null,
   is_hierarchical                tinyint unsigned               not null,
   use_as_vocabulary              tinyint unsigned               not null,
   default_sort                   tinyint unsigned               not null,
   primary key (list_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create unique index u_code on ca_lists(list_code);


/*==========================================================================*/
create table ca_list_labels
(
   label_id                       smallint unsigned              not null AUTO_INCREMENT,
   list_id                        smallint unsigned              not null,
   locale_id                      smallint unsigned              not null,
   name                           varchar(255)                   not null,
   primary key (label_id),
   
   constraint fk_ca_list_labels_list_id foreign key (list_id)
      references ca_lists (list_id) on delete restrict on update restrict,
      
   constraint fk_ca_list_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_list_id on ca_list_labels(list_id);
create index i_name on ca_list_labels(name);
create unique index u_locale_id on ca_list_labels(list_id, locale_id);


/*==========================================================================*/
create table ca_list_items
(
   item_id                        int unsigned                   not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   list_id                        smallint unsigned              not null,
   type_id                        int unsigned                   null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   item_value                     varchar(255)                   not null,
   rank                           int unsigned              not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   is_enabled                     tinyint unsigned               not null,
   is_default                     tinyint unsigned               not null,
   validation_format              varchar(255)                   not null,
   color                          char(6)                        null,
   icon                           longblob                       not null,
   access                         tinyint unsigned               not null,
   status                         tinyint unsigned               not null,
   deleted                        tinyint unsigned               not null,
   primary key (item_id),
   
   constraint fk_ca_list_items_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_list_items_list_id foreign key (list_id)
      references ca_lists (list_id) on delete restrict on update restrict,
      
   constraint fk_ca_list_items_parent_id foreign key (parent_id)
      references ca_list_items (item_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_list_id on ca_list_items(list_id);
create index i_parent_id on ca_list_items(parent_id);
create index i_idno on ca_list_items(idno);
create index i_idno_sort on ca_list_items(idno_sort);
create index i_hier_left on ca_list_items(hier_left);
create index i_hier_right on ca_list_items(hier_right);
create index i_value_text on ca_list_items(item_value);
create index i_type_id on ca_list_items(type_id);


/*==========================================================================*/
create table ca_list_item_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   item_id                        int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name_singular                  varchar(255)                   not null,
   name_plural                    varchar(255)                   not null,
   name_sort                      varchar(255)                   not null,
   description                    text                           not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   constraint fk_ca_list_item_labels_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_list_item_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_list_item_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_name_singular on ca_list_item_labels
(
   item_id,
   name_singular
);
create index i_name on ca_list_item_labels
(
   item_id,
   name_plural
);
create index i_item_id on ca_list_item_labels(item_id);
create unique index u_all on ca_list_item_labels
(
   item_id,
   name_singular,
   name_plural,
   type_id,
   locale_id
);
create index i_name_sort on ca_list_item_labels(name_sort);
create index i_type_id on ca_list_item_labels(type_id);


/*==========================================================================*/
create table ca_metadata_elements
(
   element_id                     smallint unsigned              not null AUTO_INCREMENT,
   parent_id                      smallint unsigned,
   list_id                        smallint unsigned,
   element_code                   varchar(30)                    not null,
   documentation_url              varchar(255)                   not null,
   datatype                       tinyint unsigned               not null,
   settings                       longtext                       not null,
   rank                           smallint unsigned              not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   hier_element_id                smallint unsigned              null,
   primary key (element_id),
   
   constraint fk_ca_metadata_elements_list_id foreign key (list_id)
      references ca_lists (list_id) on delete restrict on update restrict,
      
   constraint fk_ca_metadata_elements_parent_id foreign key (parent_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_hier_element_id on ca_metadata_elements(hier_element_id);
create unique index u_name_short on ca_metadata_elements(element_code);
create index i_parent_id on ca_metadata_elements(parent_id);
create index i_hier_left on ca_metadata_elements(hier_left);
create index i_hier_right on ca_metadata_elements(hier_right);
create index i_list_id on ca_metadata_elements(list_id);


/*==========================================================================*/
create table ca_metadata_element_labels
(
   label_id                       smallint unsigned              not null AUTO_INCREMENT,
   element_id                     smallint unsigned              not null,
   locale_id                      smallint unsigned              not null,
   name                           varchar(255)                   not null,
   description                    text                           not null,
   primary key (label_id),
   
   constraint fk_ca_metadata_element_labels_element_id foreign key (element_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict,
      
   constraint fk_ca_metadata_element_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_element_id on ca_metadata_element_labels(element_id);
create index i_name on ca_metadata_element_labels(name);
create index i_locale_id on ca_metadata_element_labels(locale_id);


/*==========================================================================*/
create table ca_metadata_type_restrictions
(
   restriction_id                 int unsigned                   not null AUTO_INCREMENT,
   table_num                      tinyint unsigned               not null,
   type_id                        int unsigned,
   element_id                     smallint unsigned              not null,
   settings                       longtext                       not null,
   rank                           smallint unsigned              not null,
   primary key (restriction_id),
   
   constraint fk_ca_metadata_type_restrictions_element_id foreign key (element_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_table_num on ca_metadata_type_restrictions(table_num);
create index i_type_id on ca_metadata_type_restrictions(type_id);
create index i_element_id on ca_metadata_type_restrictions(element_id);


/*==========================================================================*/
create table ca_multipart_idno_sequences
(
   idno_stub                      varchar(255)                   not null,
   format                         varchar(100)                   not null,
   element                        varchar(100)                   not null,
   seq                            int unsigned                   not null,
   primary key (idno_stub, format, element)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_object_lots
(
   lot_id                         int unsigned                   not null AUTO_INCREMENT,
   type_id                        int unsigned                   not null,
   lot_status_id                  int unsigned                   not null,
   idno_stub                      varchar(255)                   not null,
   idno_stub_sort                 varchar(255)                   not null,
   is_template                    tinyint unsigned               not null,
   commenting_status              tinyint unsigned               not null,
   tagging_status                 tinyint unsigned               not null,
   rating_status                  tinyint unsigned               not null,
   extent                         smallint unsigned              not null,
   extent_units                   varchar(255)                   not null,
   access                         tinyint                        not null,
   status                         tinyint unsigned               not null,
   source_info                    longtext                       not null,
   deleted                        tinyint unsigned               not null,
   rank                             int unsigned                     not null,
   primary key (lot_id),
   
   constraint fk_ca_object_lots_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_lot_status_id foreign key (lot_status_id)
      references ca_list_items (item_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_admin_idno_stub on ca_object_lots(idno_stub);
create index i_type_id on ca_object_lots(type_id);
create index i_admin_idno_stub_sort on ca_object_lots(idno_stub_sort);
create index i_lot_status_id on ca_object_lots(lot_status_id);


/*==========================================================================*/
create table ca_object_representations
(
   representation_id              int unsigned                   not null AUTO_INCREMENT,
   locale_id                      smallint unsigned,
   type_id                        int unsigned                   not null,
   media                          longblob                       not null,
   media_metadata                 longblob                       not null,
   media_content                  longtext                       not null,
   is_template                    tinyint unsigned               not null,
   commenting_status              tinyint unsigned               not null,
   tagging_status                 tinyint unsigned               not null,
   rating_status                  tinyint unsigned               not null,
   access                         tinyint unsigned               not null,
   status                         tinyint unsigned               not null,
   rank                             int unsigned                     not null,
   primary key (representation_id),
   
   constraint fk_ca_object_representations_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_locale_id on ca_object_representations(locale_id);
create index i_type_id on ca_object_representations(type_id);


/*==========================================================================*/
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
   
   constraint fk_ca_object_representation_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representation_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representation_labels_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_object_representation_multifiles (
	multifile_id		int unsigned not null auto_increment,
	representation_id	int unsigned not null references ca_object_representations(representation_id),
	resource_path		text not null,
	media				longblob not null,
	media_metadata		longblob not null,
	media_content		longtext not null,
	rank				int unsigned not null,	
	primary key (multifile_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_resource_path on ca_object_representation_multifiles(resource_path(255));
create index i_representation_id on ca_object_representation_multifiles(representation_id);


/*==========================================================================*/
create table ca_occurrences
(
   occurrence_id                  int unsigned                   not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   locale_id                      smallint unsigned,
   type_id                        int unsigned                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   is_template                    tinyint unsigned               not null,
   commenting_status              tinyint unsigned               not null,
   tagging_status                 tinyint unsigned               not null,
   rating_status                  tinyint unsigned               not null,
   source_id                      int unsigned,
   source_info                    longtext                       not null,
   hier_occurrence_id             int unsigned                   not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   access                         tinyint unsigned               not null,
   status                         tinyint unsigned               not null,
   deleted                        tinyint unsigned               not null,
   rank                             int unsigned                     not null,
   primary key (occurrence_id),
   
   constraint fk_ca_occurrences_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_parent_id foreign key (parent_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_parent_id on ca_occurrences(parent_id);
create index i_source_id on ca_occurrences(source_id);
create index i_type_id on ca_occurrences(type_id);
create index i_locale_id on ca_occurrences(locale_id);
create index i_hier_left on ca_occurrences(hier_left);
create index i_hier_right on ca_occurrences(hier_right);
create index i_hier_occurrence_id on ca_occurrences(hier_occurrence_id);


/*==========================================================================*/
create table ca_occurrence_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                  int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(1024)                   not null,
   name_sort                      varchar(1024)                   not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   constraint fk_ca_occurrence_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_occurrence_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_occurrence_labels_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_occurrence_id on ca_occurrence_labels(occurrence_id);
create index i_name on ca_occurrence_labels(name);
create unique index u_all on ca_occurrence_labels(
   occurrence_id,
   name(255),
   type_id,
   locale_id
);
create index i_locale_id on ca_occurrence_labels(locale_id);
create index i_name_sort on ca_occurrence_labels(name_sort(255));
create index i_type_id on ca_occurrence_labels(type_id);


/*==========================================================================*/
create table ca_places
(
   place_id                       int unsigned               not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   locale_id                      smallint unsigned,
   type_id                        int unsigned                   null,
   source_id                      int unsigned,
   hierarchy_id                   int unsigned                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   is_template                    tinyint unsigned               not null,
   commenting_status              tinyint unsigned               not null,
   tagging_status                 tinyint unsigned               not null,
   rating_status                  tinyint unsigned               not null,
   source_info                    longtext                       not null,
   lifespan_sdate                 decimal(30,20),
   lifespan_edate                 decimal(30,20),
   access                         tinyint unsigned               not null,
   status                         tinyint unsigned               not null,
   deleted                        tinyint unsigned               not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   rank                             int unsigned                     not null,
   primary key (place_id),
   constraint fk_ca_places_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_hierarchy_id foreign key (hierarchy_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_parent_id foreign key (parent_id)
      references ca_places (place_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_hierarchy_id on ca_places(hierarchy_id);
create index i_type_id on ca_places(type_id);
create index i_idno on ca_places(idno);
create index i_idno_sort on ca_places(idno_sort);
create index i_locale_id on ca_places(locale_id);
create index i_source_id on ca_places(source_id);
create index i_life_sdatetime on ca_places(lifespan_sdate);
create index i_life_edatetime on ca_places(lifespan_edate);
create index i_parent_id on ca_places(parent_id);


/*==========================================================================*/
create table ca_place_labels
(
   label_id                       int unsigned               not null AUTO_INCREMENT,
   place_id                       int unsigned               not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(255)                   not null,
   name_sort                      varchar(255)                   not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   constraint fk_ca_place_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_place_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_place_labels_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_place_id on ca_place_labels(place_id);
create index i_name on ca_place_labels(name);
create index i_name_sort on ca_place_labels(name_sort);
create unique index u_all on ca_place_labels
(
   place_id,
   name,
   type_id,
   locale_id
);
create index i_locale_id on ca_place_labels(locale_id);
create index i_type_id on ca_place_labels(type_id);


/*==========================================================================*/
create table ca_relationship_types
(
   type_id                        smallint unsigned              not null AUTO_INCREMENT,
   parent_id                      smallint unsigned,
   sub_type_left_id               int unsigned,
   sub_type_right_id              int unsigned,
   hier_left                      decimal(30,20) unsigned        not null,
   hier_right                     decimal(30,20) unsigned        not null,
   hier_type_id                   smallint unsigned,
   table_num                      tinyint unsigned               not null,
   type_code                      varchar(30)                    not null,
   rank                           smallint unsigned              not null,
   is_default                     tinyint unsigned               not null,
   primary key (type_id),
      
   constraint fk_ca_relationship_types_parent_id foreign key (parent_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create unique index u_type_code on ca_relationship_types(type_code, table_num);
create index i_table_num on ca_relationship_types(table_num);
create index i_sub_type_left_id on ca_relationship_types(sub_type_left_id);
create index i_sub_type_right_id on ca_relationship_types(sub_type_right_id);
create index i_parent_id on ca_relationship_types(parent_id);
create index i_hier_type_id on ca_relationship_types(hier_type_id);
create index i_hier_left on ca_relationship_types(hier_left);
create index i_hier_right on ca_relationship_types(hier_right);


/*==========================================================================*/
create table ca_relationship_type_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   type_id                        smallint unsigned              not null,
   locale_id                      smallint unsigned              not null,
   typename                       varchar(255)                   not null,
   typename_reverse               varchar(255)                   not null,
   description                    text                           not null,
   description_reverse            text                           not null,
   primary key (label_id),
   constraint fk_ca_relationship_type_labels_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
   constraint fk_ca_relationship_type_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_type_id on ca_relationship_type_labels(type_id);
create index i_locale_id on ca_relationship_type_labels(locale_id);
create unique index u_typename on ca_relationship_type_labels
(
   type_id,
   locale_id,
   typename
);
create unique index u_typename_reverse on ca_relationship_type_labels
(
   typename_reverse,
   type_id,
   locale_id
);


/*==========================================================================*/
create table ca_object_representations_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   occurrence_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_representations_x_occurrences_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_representation_id on ca_object_representations_x_occurrences(representation_id);
create index i_occurrence_id on ca_object_representations_x_occurrences(occurrence_id);
create index i_type_id on ca_object_representations_x_occurrences(type_id);
create unique index u_all on ca_object_representations_x_occurrences
(
   type_id,
   representation_id,
   occurrence_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_representations_x_occurrences(label_left_id);
create index i_label_right_id on ca_object_representations_x_occurrences(label_right_id);

/*==========================================================================*/
create table ca_object_representations_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   place_id                       int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_representations_x_places_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_places_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_representation_id on ca_object_representations_x_places(representation_id);
create index i_place_id on ca_object_representations_x_places(place_id);
create index i_type_id on ca_object_representations_x_places(type_id);
create unique index u_all on ca_object_representations_x_places
(
   type_id,
   representation_id,
   place_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_representations_x_places(label_left_id);
create index i_label_right_id on ca_object_representations_x_places(label_right_id);

/*==========================================================================*/
create table ca_representation_annotations
(
   annotation_id                  int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   locale_id                      smallint unsigned,
   user_id                        int unsigned                   null,
   type_code                      varchar(30)                    not null,
   props                          longtext                       not null,
   preview                        longtext                       not null,
   source_info                    longtext                       not null,
   status                         tinyint unsigned               not null,
   access                         tinyint unsigned               not null,
   primary key (annotation_id),
   constraint fk_ca_rep_annot_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_rep_annot_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
   constraint fk_ca_rep_annot_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_representation_id on ca_representation_annotations(representation_id);
create index i_locale_id on ca_representation_annotations(locale_id);
create index i_user_id on ca_representation_annotations(user_id);


/*==========================================================================*/
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
   constraint fk_ca_representation_annotation_labels_annotation_id foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
   constraint fk_ca_representation_annotation_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_representation_annotation_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_annotation_id on ca_representation_annotation_labels(annotation_id);
create index i_name on ca_representation_annotation_labels(name);
create unique index u_all on ca_representation_annotation_labels
(
   name,
   locale_id,
   type_id,
   annotation_id
);
create index i_locale_id on ca_representation_annotation_labels(locale_id);
create index i_name_sort on ca_representation_annotation_labels(name_sort);
create index i_type_id on ca_representation_annotation_labels(type_id);


/*==========================================================================*/
create table ca_storage_locations
(
   location_id                    int unsigned                   not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   type_id                        int unsigned,
   is_template                    tinyint unsigned               not null,
   source_info                    longtext                       not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   status                         tinyint unsigned               not null,
   deleted                        tinyint unsigned               not null,
   rank                             int unsigned                     not null,
   primary key (location_id),
   constraint fk_ca_storage_locations_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_parent_id foreign key (parent_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_parent_id on ca_storage_locations(parent_id);
create index i_type_id on ca_storage_locations(type_id);
create index i_hier_left on ca_storage_locations(hier_left);
create index i_hier_right on ca_storage_locations(hier_right);


/*==========================================================================*/
create table ca_storage_location_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   location_id                    int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(255)                   not null,
   name_sort                      varchar(255)                   not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   constraint fk_ca_storage_location_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_storage_location_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_storage_location_labels_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_name on ca_storage_location_labels(name);
create index i_location_id on ca_storage_location_labels(location_id);
create unique index u_all on ca_storage_location_labels
(
   location_id,
   name,
   locale_id,
   type_id
);
create index i_locale_id on ca_storage_location_labels(locale_id);
create index i_type_id on ca_storage_location_labels(type_id);
create index i_name_sort on ca_storage_location_labels(name_sort);


/*==========================================================================*/
create table ca_task_queue
(
   task_id                        int unsigned                   not null AUTO_INCREMENT,
   user_id                        int unsigned,
   row_key                        CHAR(32),
   entity_key                     CHAR(32),
   created_on                     int unsigned                   not null,
   completed_on                   int unsigned,
   priority                       smallint unsigned              not null,
   handler                        varchar(20)                    not null,
   parameters                     text                           not null,
   notes                          text                           not null,
   error_code                     smallint unsigned              not null,
   primary key (task_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_user_id on ca_task_queue(user_id);
create index i_completed_on on ca_task_queue(completed_on);
create index i_entity_key on ca_task_queue(entity_key);
create index i_row_key on ca_task_queue(row_key);
create index i_error_code on ca_task_queue(error_code);


/*==========================================================================*/
create table ca_user_groups
(
   group_id                       int unsigned                   not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   name                           varchar(255)                   not null,
   code                           varchar(20)                    not null,
   description                    text                           not null,
   user_id                        int unsigned                   null references ca_users(user_id),
   rank                           smallint unsigned              not null,
   vars                           text                           not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   primary key (group_id),
      
   constraint fk_ca_user_groups_parent_id foreign key (parent_id)
      references ca_user_groups (group_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_hier_left on ca_user_groups(hier_left);
create index i_hier_right on ca_user_groups(hier_right);
create index i_parent_id on ca_user_groups(parent_id);
create index i_user_id on ca_user_groups(user_id);
create unique index u_name on ca_user_groups(name);
create unique index u_code on ca_user_groups(code);


/*==========================================================================*/
create table ca_user_roles
(
   role_id                        smallint unsigned              not null AUTO_INCREMENT,
   name                           varchar(255)                   not null,
   code                           varchar(20)                    not null,
   description                    text                           not null,
   rank                           smallint unsigned              not null,
   vars                           text                           not null,
   field_access                   text                           not null,
   primary key (role_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create unique index u_name on ca_user_roles(name);
create unique index u_code on ca_user_roles(code);


/*==========================================================================*/
create table ca_object_lot_events
(
   event_id                       int unsigned                   not null AUTO_INCREMENT,
   lot_id                         int unsigned                   not null,
   type_id                        int unsigned                   not null,
   is_template                    tinyint unsigned               not null,
   planned_sdatetime              decimal(30,20)                 not null,
   planned_edatetime              decimal(30,20)                 not null,
   event_sdatetime                decimal(30,20),
   event_edatetime                decimal(30,20),
   primary key (event_id),
   constraint fk_ca_object_lot_events_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_lot_id on ca_object_lot_events(lot_id);
create index i_type_id on ca_object_lot_events(type_id);
create index i_planned_sdatetime on ca_object_lot_events(planned_sdatetime);
create index i_planned_edatetime on ca_object_lot_events(planned_edatetime);
create index i_event_sdatetime on ca_object_lot_events(event_sdatetime);
create index i_event_edatetime on ca_object_lot_events(event_edatetime);


/*==========================================================================*/
create table ca_object_lot_event_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   event_id                       int unsigned               not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   constraint fk_ca_object_lot_event_labels_event_id foreign key (event_id)
      references ca_object_lot_events (event_id) on delete restrict on update restrict,
   constraint fk_ca_object_lot_event_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_object_lot_event_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_name on ca_object_lot_event_labels(name);
create index i_event_id on ca_object_lot_event_labels(event_id);
create unique index u_all on ca_object_lot_event_labels
(
   event_id,
   name(255),
   type_id,
   locale_id
);
create index i_name_sort on ca_object_lot_event_labels(name_sort);
create index i_type_id on ca_object_lot_event_labels(type_id);
create index i_locale_id on ca_object_lot_event_labels(locale_id);




/*==========================================================================*/
create table ca_object_lot_events_x_storage_locations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   event_id                       int unsigned                   not null,
   location_id                    int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_lot_events_x_storage_locations_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_x_storage_locations_event_id foreign key (event_id)
      references ca_object_lot_events (event_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_object_lot_event_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_event_id on ca_object_lot_events_x_storage_locations(event_id);
create index i_location_id on ca_object_lot_events_x_storage_locations(location_id);
create index i_type_id on ca_object_lot_events_x_storage_locations(type_id);
create unique index u_all on ca_object_lot_events_x_storage_locations
(
   type_id,
   event_id,
   location_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_lot_events_x_storage_locations(label_left_id);
create index i_label_right_id on ca_object_lot_events_x_storage_locations(label_right_id);

/*==========================================================================*/
create table ca_object_lot_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   lot_id                         int unsigned               not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   constraint fk_ca_object_lot_labels_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
   constraint fk_ca_object_lot_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_object_lot_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_name on ca_object_lot_labels(name);
create index i_lot_id on ca_object_lot_labels(lot_id);
create unique index u_all on ca_object_lot_labels
(
   lot_id,
   name(255),
   type_id,
   locale_id
);
create index i_name_sort on ca_object_lot_labels(name_sort);
create index i_type_id on ca_object_lot_labels(type_id);
create index i_locale_id on ca_object_lot_labels(locale_id);


/*==========================================================================*/
create table ca_collections
(
   collection_id                  int unsigned                   not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   locale_id                      smallint unsigned,
   type_id                        int unsigned                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   is_template                    tinyint unsigned               not null,
   commenting_status              tinyint unsigned               not null,
   tagging_status                 tinyint unsigned               not null,
   rating_status                  tinyint unsigned               not null,
   source_id                      int unsigned,
   source_info                    longtext                       not null,
   hier_collection_id             int unsigned                   not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   access                         tinyint unsigned               not null,
   status                         tinyint unsigned               not null,
   deleted                        tinyint unsigned               not null,
   rank                             int unsigned                     not null,
   primary key (collection_id),
   constraint fk_ca_collections_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_parent_id foreign key (parent_id)
      references ca_collections (collection_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_parent_id on ca_collections(parent_id);
create index i_type_id on ca_collections(type_id);
create index i_idno on ca_collections(idno);
create index i_idno_sort on ca_collections(idno_sort);
create index i_locale_id on ca_collections(locale_id);
create index i_source_id on ca_collections(source_id);
create index i_hier_collection_id on ca_collections(hier_collection_id);
create index i_hier_left on ca_collections(hier_left);
create index i_hier_right on ca_collections(hier_right);


/*==========================================================================*/
create table ca_collection_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   collection_id                  int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(255)                   not null,
   name_sort                      varchar(255)                   not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   constraint fk_ca_collection_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_collection_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_collection_labels_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_collection_id on ca_collection_labels(collection_id);
create index i_name on ca_collection_labels(name);
create unique index u_all on ca_collection_labels
(
   collection_id,
   name,
   type_id,
   locale_id
);
create index i_locale_id on ca_collection_labels(locale_id);
create index i_type_id on ca_collection_labels(type_id);
create index i_name_sort on ca_collection_labels(name_sort);


/*==========================================================================*/
create table ca_collections_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   collection_left_id             int unsigned                   not null,
   collection_right_id            int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_collections_x_collections_collection_left_id foreign key (collection_left_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_collections_collection_right_id foreign key (collection_right_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_collections_label_left_id foreign key (label_left_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_collection_left_id on ca_collections_x_collections(collection_left_id);
create index i_collection_right_id on ca_collections_x_collections(collection_right_id);
create index i_type_id on ca_collections_x_collections(type_id);
create unique index u_all on ca_collections_x_collections
(
   collection_left_id,
   collection_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_collections_x_collections(label_left_id);
create index i_label_right_id on ca_collections_x_collections(label_right_id);


/*==========================================================================*/
create table ca_collections_x_storage_locations (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   collection_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   location_id                    int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   
   constraint fk_ca_collections_x_storage_locations_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint ca_collections_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint ca_collections_x_storage_locations_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint ca_collections_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict,
      
   constraint ca_collections_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels(label_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_collection_id on ca_collections_x_storage_locations (collection_id);
create index i_location_id on ca_collections_x_storage_locations (location_id);
create index i_type_id on ca_collections_x_storage_locations (type_id);
create unique index u_all on ca_collections_x_storage_locations (
   collection_id,
   location_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_collections_x_storage_locations(label_left_id);
create index i_label_right_id on ca_collections_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_objects
(
   object_id                      int unsigned               not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   lot_id                         int unsigned,
   locale_id                      smallint unsigned,
   source_id                      int unsigned,
   is_template                    tinyint unsigned               not null,
   commenting_status              tinyint unsigned               not null,
   tagging_status                 tinyint unsigned               not null,
   rating_status                  tinyint unsigned               not null,
   type_id                        int unsigned                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   acquisition_type_id            int unsigned,
   item_status_id                 int unsigned,
   source_info                    longtext                       not null,
   hier_object_id                 int unsigned                   not null,
   hier_left                      decimal(30,20) unsigned        not null,
   hier_right                     decimal(30,20) unsigned        not null,
   extent                         int unsigned                   not null,
   extent_units                   varchar(255)                   not null,
   access                         tinyint unsigned               not null,
   status                         tinyint unsigned               not null,
   deleted                        tinyint unsigned               not null,
   rank                             int unsigned                     not null,
   primary key (object_id),
   constraint fk_ca_objects_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_acquisition_type_id foreign key (acquisition_type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_item_status_id foreign key (item_status_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_parent_id foreign key (parent_id)
      references ca_objects (object_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_parent_id on ca_objects(parent_id);
create index i_idno on ca_objects(idno);
create index i_idno_sort on ca_objects(idno_sort);
create index i_type_id on ca_objects(type_id);
create index i_hier_left on ca_objects(hier_left);
create index i_hier_right on ca_objects(hier_right);
create index i_lot_id on ca_objects(lot_id);
create index i_locale_id on ca_objects(locale_id);
create index i_hier_object_id on ca_objects(hier_object_id);
create index i_acqusition_type_id on ca_objects
(
   source_id,
   acquisition_type_id
);
create index i_source_id on ca_objects(source_id);
create index i_item_status_id on ca_objects(item_status_id);


/*==========================================================================*/
create table ca_object_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   object_id                      int unsigned               not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   constraint fk_ca_object_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_object_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_object_labels_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_name on ca_object_labels(name);
create index i_object_id on ca_object_labels(object_id);
create unique index u_all on ca_object_labels
(
   object_id,
   name(255),
   type_id,
   locale_id
);
create index i_name_sort on ca_object_labels(name_sort);
create index i_type_id on ca_object_labels(type_id);
create index i_locale_id on ca_object_labels(locale_id);


/*==========================================================================*/
create table ca_object_events
(
   event_id                       int unsigned                   not null AUTO_INCREMENT,
   type_id                        int unsigned                   not null,
   object_id                      int unsigned                   not null,
   is_template                    tinyint unsigned               not null,
   planned_sdatetime              decimal(30,20)                 not null,
   planned_edatetime              decimal(30,20)                 not null,
   event_sdatetime                decimal(30,20),
   event_edatetime                decimal(30,20),
   primary key (event_id),
   
   constraint fk_ca_object_events_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_object_id on ca_object_events(object_id);
create index i_type_id on ca_object_events(type_id);
create index i_planned_sdatetime on ca_object_events(planned_sdatetime);
create index i_planned_edatetime on ca_object_events(planned_edatetime);
create index i_event_sdatetime on ca_object_events(event_sdatetime);
create index i_event_edatetime on ca_object_events(event_edatetime);


/*==========================================================================*/
create table ca_object_event_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   event_id                       int unsigned               not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   constraint fk_ca_object_event_labels_event_id foreign key (event_id)
      references ca_object_events (event_id) on delete restrict on update restrict,
   constraint fk_ca_object_event_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_object_event_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_name on ca_object_event_labels(name);
create index i_event_id on ca_object_event_labels(event_id);
create unique index u_all on ca_object_event_labels
(
   event_id,
   name(255),
   type_id,
   locale_id
);
create index i_name_sort on ca_object_event_labels(name_sort);
create index i_type_id on ca_object_event_labels(type_id);
create index i_locale_id on ca_object_event_labels(locale_id);




/*==========================================================================*/
create table ca_objects_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   object_id                      int unsigned               not null,
   collection_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_objects_x_collections_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_collections_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_collections_label_left_id foreign key (label_left_id)
      references ca_object_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_object_id on ca_objects_x_collections(object_id);
create index i_collection_id on ca_objects_x_collections(collection_id);
create index i_type_id on ca_objects_x_collections(type_id);
create unique index u_all on ca_objects_x_collections
(
   object_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_objects_x_collections(label_left_id);
create index i_label_right_id on ca_objects_x_collections(label_right_id);


/*==========================================================================*/
create table ca_objects_x_objects
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   object_left_id                 int unsigned               not null,
   object_right_id                int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_notes                   text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_objects_x_objects_object_left_id foreign key (object_left_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_objects_object_right_id foreign key (object_right_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_objects_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_objects_label_left_id foreign key (label_left_id)
      references ca_object_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_objects_label_right_id foreign key (label_right_id)
      references ca_object_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_object_left_id on ca_objects_x_objects(object_left_id);
create index i_object_right_id on ca_objects_x_objects(object_right_id);
create index i_type_id on ca_objects_x_objects(type_id);
create unique index u_all on ca_objects_x_objects
(
   object_left_id,
   object_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_objects_x_objects(label_left_id);
create index i_label_right_id on ca_objects_x_objects(label_right_id);


/*==========================================================================*/
create table ca_objects_x_object_representations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   object_id                      int unsigned                   not null,
   representation_id              int unsigned                   not null,
   is_primary                     tinyint                        not null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_objects_x_object_representations_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
   constraint fk_ca_objects_x_object_representations_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_object_id on ca_objects_x_object_representations(object_id);
create index i_representation_id on ca_objects_x_object_representations(representation_id);
create unique index u_all on ca_objects_x_object_representations
(
   object_id,
   representation_id
);


/*==========================================================================*/
create table ca_objects_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                  int unsigned                   not null,
   object_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_objects_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_occurrences_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_object_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_occurrence_id on ca_objects_x_occurrences(occurrence_id);
create index i_object_id on ca_objects_x_occurrences(object_id);
create index i_type_id on ca_objects_x_occurrences(type_id);
create unique index u_all on ca_objects_x_occurrences
(
   occurrence_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_objects_x_occurrences(label_left_id);
create index i_label_right_id on ca_objects_x_occurrences(label_right_id);

/*==========================================================================*/
create table ca_objects_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   place_id                       int unsigned               not null,
   object_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_objects_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_places_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_places_label_left_id foreign key (label_left_id)
      references ca_object_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_place_id on ca_objects_x_places(place_id);
create index i_object_id on ca_objects_x_places(object_id);
create index i_type_id on ca_objects_x_places(type_id);
create unique index u_all on ca_objects_x_places
(
   place_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_objects_x_places(label_left_id);
create index i_label_right_id on ca_objects_x_places(label_right_id);


/*==========================================================================*/
create table ca_attributes
(
   attribute_id                   int unsigned                   not null AUTO_INCREMENT,
   element_id                     smallint unsigned              not null,
   locale_id                      smallint unsigned              null,
   table_num                      tinyint unsigned               not null,
   row_id                         int unsigned                   not null,
   primary key (attribute_id),
   constraint fk_ca_attributes_element_id foreign key (element_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict,
   constraint fk_ca_attributes_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_locale_id on ca_attributes(locale_id);
create index i_row_id on ca_attributes(row_id);
create index i_table_num on ca_attributes(table_num);
create index i_element_id on ca_attributes(element_id);


/*==========================================================================*/
create table ca_object_events_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   event_id                       int unsigned                   not null,
   occurrence_id                  int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_events_x_occurrences_event_id foreign key (event_id)
      references ca_object_events (event_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_object_event_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_event_id on ca_object_events_x_occurrences(event_id);
create index i_occurrence_id on ca_object_events_x_occurrences(occurrence_id);
create index i_type_id on ca_object_events_x_occurrences(type_id);
create unique index u_all on ca_object_events_x_occurrences
(
   type_id,
   event_id,
   occurrence_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_events_x_occurrences(label_left_id);
create index i_label_right_id on ca_object_events_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_object_events_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   event_id                       int unsigned                   not null,
   place_id                       int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_events_x_places_event_id foreign key (event_id)
      references ca_object_events (event_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_places_label_left_id foreign key (label_left_id)
      references ca_object_event_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_event_id on ca_object_events_x_places(event_id);
create index i_place_id on ca_object_events_x_places(place_id);
create index i_type_id on ca_object_events_x_places(type_id);
create unique index u_all on ca_object_events_x_places
(
   type_id,
   event_id,
   place_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_events_x_places(label_left_id);
create index i_label_right_id on ca_object_events_x_places(label_right_id);


/*==========================================================================*/
create table ca_object_events_x_storage_locations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   event_id                       int unsigned                   not null,
   location_id                    int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_events_x_storage_locations_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_storage_locations_event_id foreign key (event_id)
      references ca_object_events (event_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_storage_locations_left_id foreign key (label_left_id)
      references ca_object_event_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_event_id on ca_object_events_x_storage_locations(event_id);
create index i_location_id on ca_object_events_x_storage_locations(location_id);
create index i_type_id on ca_object_events_x_storage_locations(type_id);
create unique index u_all on ca_object_events_x_storage_locations
(
   type_id,
   event_id,
   location_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_events_x_storage_locations(label_left_id);
create index i_label_right_id on ca_object_events_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_data_import_events
(
   event_id                       int unsigned                   not null AUTO_INCREMENT,
   occurred_on                    int unsigned                   not null,
   user_id                        int unsigned,
   description                    text                           not null,
   type_code                      char(10)                       not null,
   source                         text                           not null,
   primary key (event_id),
   constraint fk_ca_data_import_events_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_user_id on ca_data_import_events(user_id);


/*==========================================================================*/
create table ca_data_import_items
(
   item_id                        int unsigned                  not null AUTO_INCREMENT,
   event_id                       int unsigned                  not null,
   source_ref                    varchar(255)                  not null,
   table_num                    tinyint unsigned            null,
   row_id                          int unsigned                  null,
   type_code                     char(1)                          null,
   started_on                    int unsigned                 not null,
   completed_on               int unsigned                 null,
   elapsed_time                decimal(8,4)                  null,
   success                        tinyint unsigned            null,
   message                       text                              not null,
   primary key (item_id),
   constraint fk_ca_data_import_items_event_id foreign key (event_id)
      references ca_data_import_events (event_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_event_id on ca_data_import_items(event_id);
create index i_row_id on ca_data_import_items(table_num, row_id);


/*==========================================================================*/
create table ca_data_import_event_log
(
   log_id                       int unsigned                   not null AUTO_INCREMENT,
   event_id                    int unsigned                   not null,
   item_id                      int unsigned                   null,
   type_code                  char(10)                       not null,
   date_time                  int unsigned                   not null,
   message                    text                           not null,
   source                       varchar(255)                   not null,
   primary key (log_id),
   constraint fk_ca_data_import_events_event_id foreign key (event_id)
      references ca_data_import_events (event_id) on delete restrict on update restrict,
    constraint fk_ca_data_import_events_item_id foreign key (item_id)
      references ca_data_import_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_event_id on ca_data_import_event_log(event_id);
create index i_item_id on ca_data_import_event_log(item_id);


/*==========================================================================*/
create table ca_object_lots_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   lot_id                         int unsigned               not null,
   collection_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_lots_x_collections_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_collections_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_collections_left_id foreign key (label_left_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_lot_id on ca_object_lots_x_collections(lot_id);
create index i_collection_id on ca_object_lots_x_collections(collection_id);
create index i_type_id on ca_object_lots_x_collections(type_id);
create unique index u_all on ca_object_lots_x_collections
(
   lot_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_lots_x_collections(label_left_id);
create index i_label_right_id on ca_object_lots_x_collections(label_right_id);


/*==========================================================================*/
create table ca_object_lots_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                  int unsigned                   not null,
   lot_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_lots_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_occurrences_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_occurrences_left_id foreign key (label_left_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_occurrence_id on ca_object_lots_x_occurrences(occurrence_id);
create index i_lot_id on ca_object_lots_x_occurrences(lot_id);
create index i_type_id on ca_object_lots_x_occurrences(type_id);
create unique index u_all on ca_object_lots_x_occurrences
(
   occurrence_id,
   lot_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_lots_x_occurrences(label_left_id);
create index i_label_right_id on ca_object_lots_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_object_lots_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   place_id                       int unsigned               not null,
   lot_id                         int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_lots_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_places_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_places_label_left_id foreign key (label_left_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_lot_id on ca_object_lots_x_places(lot_id);
create index i_place_id on ca_object_lots_x_places(place_id);
create index i_type_id on ca_object_lots_x_places(type_id);
create unique index u_all on ca_object_lots_x_places
(
   place_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_lots_x_places(label_left_id);
create index i_label_right_id on ca_object_lots_x_places(label_right_id);


/*==========================================================================*/
create table ca_acl
(
   aci_id                         int unsigned                   not null AUTO_INCREMENT,
   group_id                       int unsigned,
   user_id                        int unsigned,
   table_num                      tinyint unsigned               not null,
   row_id                         int unsigned                   not null,
   access                         tinyint unsigned               not null,
   notes                          char(10)                       not null,
   primary key (aci_id),
   constraint fk_ca_acl_group_id foreign key (group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,
   constraint fk_ca_acl_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_row_id on ca_acl(row_id, table_num);
create index i_user_id on ca_acl(user_id);
create index i_group_id on ca_acl(group_id);


/*==========================================================================*/
create table ca_occurrences_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                  int unsigned               not null,
   collection_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_occurrences_x_collections_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_collections_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_collections_label_left_id foreign key (label_left_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_occurrence_id on ca_occurrences_x_collections(occurrence_id);
create index i_collection_id on ca_occurrences_x_collections(collection_id);
create index i_type_id on ca_occurrences_x_collections(type_id);
create unique index u_all on ca_occurrences_x_collections
(
   occurrence_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_occurrences_x_collections(label_left_id);
create index i_label_right_id on ca_occurrences_x_collections(label_right_id);


/*==========================================================================*/
create table ca_occurrences_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_left_id             int unsigned                   not null,
   occurrence_right_id            int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_occurrences_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_occurrences_occurrence_left_id foreign key (occurrence_left_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_occurrences_occurrence_right_id foreign key (occurrence_right_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_occurrence_left_id on ca_occurrences_x_occurrences(occurrence_left_id);
create index i_occurrence_right_id on ca_occurrences_x_occurrences(occurrence_right_id);
create index i_type_id on ca_occurrences_x_occurrences(type_id);
create unique index u_all on ca_occurrences_x_occurrences
(
   occurrence_left_id,
   occurrence_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_occurrences_x_occurrences(label_left_id);
create index i_label_right_id on ca_occurrences_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_entities
(
   entity_id                      int unsigned               not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   locale_id                      smallint unsigned,
   source_id                      int unsigned,
   type_id                        int unsigned                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   is_template                    tinyint unsigned               not null,
   commenting_status              tinyint unsigned               not null,
   tagging_status                 tinyint unsigned               not null,
   rating_status                  tinyint unsigned               not null,
   source_info                    longtext                       not null,
   life_sdatetime                 decimal(30,20),
   life_edatetime                 decimal(30,20),
   hier_entity_id                 int unsigned                   not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   access                         tinyint unsigned               not null,
   status                         tinyint unsigned               not null,
   deleted                        tinyint unsigned               not null,
   rank                             int unsigned                     not null,
   primary key (entity_id),
   constraint fk_ca_entities_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_parent_id foreign key (parent_id)
      references ca_entities (entity_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_source_id on ca_entities(source_id);
create index i_type_id on ca_entities(type_id);
create index i_idno on ca_entities(idno);
create index i_idno_sort on ca_entities(idno_sort);
create index i_hier_entity_id on ca_entities(hier_entity_id);
create index i_locale_id on ca_entities(locale_id);
create index i_parent_id on ca_entities(parent_id);
create index i_hier_left on ca_entities(hier_left);
create index i_hier_right on ca_entities(hier_right);
create index i_life_sdatetime on ca_entities(life_sdatetime);
create index i_life_edatetime on ca_entities(life_edatetime);


/*==========================================================================*/
create table ca_entity_labels
(
   label_id                       int unsigned               not null AUTO_INCREMENT,
   entity_id                      int unsigned               not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   displayname                    varchar(512)                   not null,
   forename                       varchar(100)                   not null,
   other_forenames                varchar(100)                   not null,
   middlename                     varchar(100)                   not null,
   surname                        varchar(100)                   not null,
   prefix                         varchar(100)                   not null,
   suffix                         varchar(100)                   not null,
   name_sort                      varchar(512)                   not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   constraint fk_ca_entity_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_entity_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_entity_labels_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_entity_id on ca_entity_labels(entity_id);
create index i_forename on ca_entity_labels(forename);
create index i_surname on ca_entity_labels(surname);
create unique index u_all on ca_entity_labels
(
   entity_id,
   forename,
   other_forenames,
   middlename,
   surname,
   type_id,
   locale_id
);
create index i_locale_id on ca_entity_labels(locale_id);
create index i_type_id on ca_entity_labels(type_id);
create index i_name_sort on ca_entity_labels(name_sort);


/*==========================================================================*/
create table ca_entities_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   entity_id                      int unsigned               not null,
   collection_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_entities_x_collections_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_collections_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_collections_label_left_id foreign key (label_left_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_entity_id on ca_entities_x_collections(entity_id);
create index i_collection_id on ca_entities_x_collections(collection_id);
create index i_type_id on ca_entities_x_collections(type_id);
create unique index u_all on ca_entities_x_collections
(
   entity_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_entities_x_collections(label_left_id);
create index i_label_right_id on ca_entities_x_collections(label_right_id);


/*==========================================================================*/
create table ca_places_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   place_id                       int unsigned               not null,
   collection_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_places_x_collections_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_collections_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_collections_label_left_id foreign key (label_left_id)
      references ca_place_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_place_id on ca_places_x_collections(place_id);
create index i_collection_id on ca_places_x_collections(collection_id);
create index i_type_id on ca_places_x_collections(type_id);
create unique index u_all on ca_places_x_collections
(
   place_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_places_x_collections(label_left_id);
create index i_label_right_id on ca_places_x_collections(label_right_id);


/*==========================================================================*/
create table ca_places_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                  int unsigned                   not null,
   place_id                       int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_places_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_occurrences_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_place_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_occurrence_id on ca_places_x_occurrences(occurrence_id);
create index i_place_id on ca_places_x_occurrences(place_id);
create index i_type_id on ca_places_x_occurrences(type_id);
create unique index u_all on ca_places_x_occurrences
(
   place_id,
   occurrence_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_places_x_occurrences(label_left_id);
create index i_label_right_id on ca_places_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_places_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   place_left_id                  int unsigned               not null,
   place_right_id                 int unsigned               not null,
   type_id                        smallint unsigned              null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_places_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_places_place_left_id foreign key (place_left_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_places_place_right_id foreign key (place_right_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_places_label_left_id foreign key (label_left_id)
      references ca_place_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_place_left_id on ca_places_x_places(place_left_id);
create index i_place_right_id on ca_places_x_places(place_right_id);
create index i_type_id on ca_places_x_places(type_id);
create unique index u_all on ca_places_x_places
(
   place_left_id,
   place_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_places_x_places(label_left_id);
create index i_label_right_id on ca_places_x_places(label_right_id);


/*==========================================================================*/
create table ca_entities_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   entity_id                      int unsigned               not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_entities_x_occurrences_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_entity_id on ca_entities_x_occurrences(entity_id);
create index i_occurrence_id on ca_entities_x_occurrences(occurrence_id);
create index i_type_id on ca_entities_x_occurrences(type_id);
create unique index u_all on ca_entities_x_occurrences
(
   occurrence_id,
   type_id,
   entity_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_entities_x_occurrences(label_left_id);
create index i_label_right_id on ca_entities_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_relationship_relationships
(
   reification_id                 int unsigned                   not null AUTO_INCREMENT,
   type_id                        smallint unsigned              not null,
   relationship_table_num         tinyint unsigned               not null,
   relation_id                    int unsigned                   not null,
   table_num                      tinyint                        not null,
   row_id                         int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   primary key (reification_id),
   constraint ca_relationship_relationships_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_type_id on ca_relationship_relationships(type_id);
create index i_relation_row on ca_relationship_relationships
(
   relation_id,
   relationship_table_num
);
create index i_target_row on ca_relationship_relationships
(
   row_id,
   table_num
);


/*==========================================================================*/
create table ca_entities_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   entity_id                      int unsigned               not null,
   place_id                       int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_entities_x_places_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_places_label_left_id foreign key (label_left_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_place_id on ca_entities_x_places(place_id);
create index i_entity_id on ca_entities_x_places(entity_id);
create index i_type_id on ca_entities_x_places(type_id);
create unique index u_all on ca_entities_x_places
(
   entity_id,
   place_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_entities_x_places(label_left_id);
create index i_label_right_id on ca_entities_x_places(label_right_id);


/*==========================================================================*/
create table ca_object_representations_x_entities
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   entity_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_representations_x_entities_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_entities_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_representation_id on ca_object_representations_x_entities(representation_id);
create index i_entity_id on ca_object_representations_x_entities(entity_id);
create index i_type_id on ca_object_representations_x_entities(type_id);
create unique index u_all on ca_object_representations_x_entities
(
   type_id,
   representation_id,
   entity_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_representations_x_entities(label_left_id);
create index i_label_right_id on ca_object_representations_x_entities(label_right_id);


/*==========================================================================*/
create table ca_entities_x_entities
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   entity_left_id                 int unsigned               not null,
   entity_right_id                int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_entities_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_entities_entity_left_id foreign key (entity_left_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_entities_entity_right_id foreign key (entity_right_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_entities_label_left_id foreign key (label_left_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_entity_left_id on ca_entities_x_entities(entity_left_id);
create index i_entity_right_id on ca_entities_x_entities(entity_right_id);
create index i_type_id on ca_entities_x_entities(type_id);
create unique index u_all on ca_entities_x_entities
(
   entity_left_id,
   entity_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_entities_x_entities(label_left_id);
create index i_label_right_id on ca_entities_x_entities(label_right_id);


/*==========================================================================*/
create table ca_representation_annotations_x_entities
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   annotation_id                  int unsigned                   not null,
   entity_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_rep_annot_x_entities_annotation_id foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_entities_label_left_id foreign key (label_left_id)
      references ca_representation_annotation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_entity_id on ca_representation_annotations_x_entities(entity_id);
create index i_annotation_id on ca_representation_annotations_x_entities(annotation_id);
create index i_type_id on ca_representation_annotations_x_entities(type_id);
create unique index u_all on ca_representation_annotations_x_entities
(
   entity_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_representation_annotations_x_entities(label_left_id);
create index i_label_right_id on ca_representation_annotations_x_entities(label_right_id);


/*==========================================================================*/
create table ca_groups_x_roles
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   group_id                       int unsigned                   not null,
   role_id                        smallint unsigned              not null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_groups_x_roles_group_id foreign key (group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,
   constraint fk_ca_groups_x_roles_role_id foreign key (role_id)
      references ca_user_roles (role_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_group_id on ca_groups_x_roles(group_id);
create index i_role_id on ca_groups_x_roles(role_id);
create index u_all on ca_groups_x_roles
(
   group_id,
   role_id
);


/*==========================================================================*/
create table ca_ips
(
   ip_id                          int unsigned                   not null AUTO_INCREMENT,
   user_id                        int unsigned                   not null,
   ip1                            tinyint unsigned               not null,
   ip2                            tinyint unsigned,
   ip3                            tinyint unsigned,
   ip4s                           tinyint unsigned,
   ip4e                           tinyint unsigned,
   notes                          text                           not null,
   primary key (ip_id),
   constraint fk_ca_ips_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create unique index u_ip on ca_ips
(
   ip1,
   ip2,
   ip3,
   ip4s,
   ip4e
);
create index i_user_id on ca_ips(user_id);


/*==========================================================================*/
create table ca_representation_annotations_x_objects
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   annotation_id                        int unsigned                   not null,
   object_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_rep_annot_x_objects_annotation_id foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_objects_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_objects_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_objects_label_left_id foreign key (label_left_id)
      references ca_representation_annotation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_objects_label_right_id foreign key (label_right_id)
      references ca_object_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_object_id on ca_representation_annotations_x_objects(object_id);
create index i_annotation_id on ca_representation_annotations_x_objects(annotation_id);
create index i_type_id on ca_representation_annotations_x_objects(type_id);
create unique index u_all on ca_representation_annotations_x_objects
(
   object_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_representation_annotations_x_objects(label_left_id);
create index i_label_right_id on ca_representation_annotations_x_objects(label_right_id);


/*==========================================================================*/
create table ca_representation_annotations_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   annotation_id                        int unsigned                   not null,
   occurrence_id                  int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_rep_annot_x_occurrences_annotation_id foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_representation_annotation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_occurrence_id on ca_representation_annotations_x_occurrences(occurrence_id);
create index i_annotation_id on ca_representation_annotations_x_occurrences(annotation_id);
create index i_type_id on ca_representation_annotations_x_occurrences(type_id);
create unique index u_all on ca_representation_annotations_x_occurrences
(
   occurrence_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_representation_annotations_x_occurrences(label_left_id);
create index i_label_right_id on ca_representation_annotations_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_list_items_x_list_items
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   term_left_id                   int unsigned                   not null,
   term_right_id                  int unsigned                   not null,
   type_id                        smallint unsigned              null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint ca_ca_list_items_x_list_items_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint ca_ca_list_items_x_list_items_term_left_id foreign key (term_left_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint ca_ca_list_items_x_list_items_term_right_id foreign key (term_right_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_list_items_x_list_items_label_left_id foreign key (label_left_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_list_items_x_list_items_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_term_left_id on ca_list_items_x_list_items(term_left_id);
create index i_term_right_id on ca_list_items_x_list_items(term_right_id);
create index i_type_id on ca_list_items_x_list_items(type_id);
create unique index u_all on ca_list_items_x_list_items
(
   term_left_id,
   term_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_list_items_x_list_items(label_left_id);
create index i_label_right_id on ca_list_items_x_list_items(label_right_id);


/*==========================================================================*/
create table ca_objects_x_storage_locations (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   object_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   location_id                    int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_objects_x_storage_locations_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_storage_locations_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_object_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_object_id on ca_objects_x_storage_locations (object_id);
create index i_location_id on ca_objects_x_storage_locations (location_id);
create index i_type_id on ca_objects_x_storage_locations (type_id);
create unique index u_all on ca_objects_x_storage_locations (
   object_id,
   type_id,
   sdatetime,
   edatetime,
   location_id
);
create index i_label_left_id on ca_objects_x_storage_locations(label_left_id);
create index i_label_right_id on ca_objects_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_object_lots_x_storage_locations (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   lot_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   location_id                    int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_lots_x_storage_locations_relation_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_storage_locations_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_lot_id on ca_object_lots_x_storage_locations (lot_id);
create index i_location_id on ca_object_lots_x_storage_locations (location_id);
create index i_type_id on ca_object_lots_x_storage_locations (type_id);
create unique index u_all on ca_object_lots_x_storage_locations (
   lot_id,
   type_id,
   sdatetime,
   edatetime,
   location_id
);
create index i_label_left_id on ca_object_lots_x_storage_locations(label_left_id);
create index i_label_right_id on ca_object_lots_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_object_lots_x_vocabulary_terms (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   lot_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_lots_x_vocabulary_terms_relation_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_vocabulary_terms_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_lot_id on ca_object_lots_x_vocabulary_terms (lot_id);
create index i_item_id on ca_object_lots_x_vocabulary_terms (item_id);
create index i_type_id on ca_object_lots_x_vocabulary_terms (type_id);
create unique index u_all on ca_object_lots_x_vocabulary_terms (
   lot_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_object_lots_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_object_lots_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_object_representations_x_vocabulary_terms (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned               not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_obj_rep_x_voc_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_obj_rep_x_voc_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_obj_rep_x_voc_terms_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_obj_rep_x_voc_terms_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_obj_rep_x_voc_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_representation_id on ca_object_representations_x_vocabulary_terms (representation_id);
create index i_item_id on ca_object_representations_x_vocabulary_terms (item_id);
create index i_type_id on ca_object_representations_x_vocabulary_terms (type_id);
create unique index u_all on ca_object_representations_x_vocabulary_terms (
   representation_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_object_representations_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_object_representations_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_object_events_x_vocabulary_terms (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   event_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_events_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_vocabulary_terms_event_id foreign key (event_id)
      references ca_object_events (event_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_object_event_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_event_id on ca_object_events_x_vocabulary_terms (event_id);
create index i_item_id on ca_object_events_x_vocabulary_terms (item_id);
create index i_type_id on ca_object_events_x_vocabulary_terms (type_id);
create unique index u_all on ca_object_events_x_vocabulary_terms (
   event_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_object_events_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_object_events_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_object_lot_events_x_vocabulary_terms (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   event_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_lot_events_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_x_vocabulary_terms_event_id foreign key (event_id)
      references ca_object_lot_events (event_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_object_lot_event_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_event_id on ca_object_lot_events_x_vocabulary_terms (event_id);
create index i_item_id on ca_object_lot_events_x_vocabulary_terms (item_id);
create index i_type_id on ca_object_lot_events_x_vocabulary_terms (type_id);
create unique index u_all on ca_object_lot_events_x_vocabulary_terms (
   event_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_object_lot_events_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_object_lot_events_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_users_x_groups
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   user_id                        int unsigned                   not null,
   group_id                       int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_users_x_groups_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict,
   constraint fk_ca_users_x_groups_group_id foreign key (group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_user_id on ca_users_x_groups(user_id);
create index i_group_id on ca_users_x_groups(group_id);
create unique index u_all on ca_users_x_groups
(
   user_id,
   group_id
);


/*==========================================================================*/
create table ca_users_x_roles
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   user_id                        int unsigned                   not null,
   role_id                        smallint unsigned              not null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_users_x_roles_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict,
   constraint fk_ca_users_x_roles_role_id foreign key (role_id)
      references ca_user_roles (role_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_user_id on ca_users_x_roles(user_id);
create index i_role_id on ca_users_x_roles(role_id);
create unique index u_all on ca_users_x_roles
(
   user_id,
   role_id
);


/*==========================================================================*/
create table ca_representation_annotations_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   annotation_id                        int unsigned                   not null,
   place_id                       int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_rep_annot_x_places_annotation_id foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_places_label_left_id foreign key (label_left_id)
      references ca_representation_annotation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_place_id on ca_representation_annotations_x_places(place_id);
create index i_annotation_id on ca_representation_annotations_x_places(annotation_id);
create index i_type_id on ca_representation_annotations_x_places(type_id);
create unique index u_all on ca_representation_annotations_x_places
(
   place_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_representation_annotations_x_places(label_left_id);
create index i_label_right_id on ca_representation_annotations_x_places(label_right_id);


/*==========================================================================*/
create table ca_representation_annotations_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   annotation_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_rep_annot_x_vocabulary_terms_annotation_id foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_representation_annotation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_item_id on ca_representation_annotations_x_vocabulary_terms(item_id);
create index i_annotation_id on ca_representation_annotations_x_vocabulary_terms(annotation_id);
create index i_type_id on ca_representation_annotations_x_vocabulary_terms(type_id);
create unique index u_all on ca_representation_annotations_x_vocabulary_terms
(
   type_id,
   annotation_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_representation_annotations_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_representation_annotations_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_objects_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   object_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_objects_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_vocabulary_terms_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_object_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_object_id on ca_objects_x_vocabulary_terms(object_id);
create index i_item_id on ca_objects_x_vocabulary_terms(item_id);
create index i_type_id on ca_objects_x_vocabulary_terms(type_id);
create unique index u_all on ca_objects_x_vocabulary_terms
(
   object_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_objects_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_objects_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_object_lots_x_entities
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   entity_id                      int unsigned               not null,
   lot_id                         int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_lots_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_entities_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_entities_label_left_id foreign key (label_left_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_lot_id on ca_object_lots_x_entities(lot_id);
create index i_entity_id on ca_object_lots_x_entities(entity_id);
create index i_type_id on ca_object_lots_x_entities(type_id);
create unique index u_all on ca_object_lots_x_entities
(
   entity_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_lots_x_entities(label_left_id);
create index i_label_right_id on ca_object_lots_x_entities(label_right_id);


/*==========================================================================*/
create table ca_objects_x_entities
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   entity_id                      int unsigned               not null,
   object_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_objects_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_entities_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_entities_label_left_id foreign key (label_left_id)
      references ca_object_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_entity_id on ca_objects_x_entities(entity_id);
create index i_object_id on ca_objects_x_entities(object_id);
create index i_type_id on ca_objects_x_entities(type_id);
create unique index u_all on ca_objects_x_entities
(
   entity_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_objects_x_entities(label_left_id);
create index i_label_right_id on ca_objects_x_entities(label_right_id);


/*==========================================================================*/
create table ca_places_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   place_id                       int unsigned               not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_places_x_vocabulary_terms_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_place_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_place_id on ca_places_x_vocabulary_terms(place_id);
create index i_item_id on ca_places_x_vocabulary_terms(item_id);
create index i_type_id on ca_places_x_vocabulary_terms(type_id);
create unique index u_all on ca_places_x_vocabulary_terms
(
   place_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_places_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_places_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_loans (
   loan_id                        int unsigned                   not null AUTO_INCREMENT,
   parent_id                      int unsigned                   null,
   type_id                        int unsigned                   not null,
   locale_id                      smallint unsigned              null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   is_template                    tinyint unsigned               not null,
   source_info                    longtext                       not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   hier_loan_id                   int unsigned                   not null,
   status                         tinyint unsigned               not null,
   deleted                        tinyint unsigned               not null,
   rank                             int unsigned                     not null,
   primary key (loan_id),
   
   constraint fk_ca_loans_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_parent_id foreign key (parent_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_parent_id on ca_loans(parent_id);
create index i_type_id on ca_loans(type_id);
create index i_locale_id on ca_loans(locale_id);
create index idno on ca_loans(idno);
create index idno_sort on ca_loans(idno_sort);
create index hier_left on ca_loans(hier_left);
create index hier_right on ca_loans(hier_right);
create index hier_loan_id on ca_loans(hier_loan_id);


/*==========================================================================*/
create table ca_loan_labels (
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   loan_id                        int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   
   constraint fk_ca_loan_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_loan_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_loan_labels_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_loan_id on ca_loan_labels(loan_id);
create index i_locale_id_id on ca_loan_labels(locale_id);
create index i_type_id on ca_loan_labels(type_id);
create index i_name on ca_loan_labels(name);
create index i_name_sort on ca_loan_labels(name_sort);


/*==========================================================================*/
create table ca_loans_x_objects (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   loan_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   object_id                    int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   
   constraint fk_ca_loans_x_objects_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_objects_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_objects_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_objects_label_left_id foreign key (label_left_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_objects_label_right_id foreign key (label_right_id)
      references ca_object_labels (label_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_loan_id on ca_loans_x_objects (loan_id);
create index i_object_id on ca_loans_x_objects (object_id);
create index i_type_id on ca_loans_x_objects (type_id);
create unique index u_all on ca_loans_x_objects (
   loan_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_loans_x_objects (label_left_id);
create index i_label_right_id on ca_loans_x_objects (label_right_id);


/*==========================================================================*/
create table ca_loans_x_entities (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   loan_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   entity_id                    int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   
   constraint fk_ca_loans_x_entities_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_entities_label_left_id foreign key (label_left_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_loan_id on ca_loans_x_entities (loan_id);
create index i_entity_id on ca_loans_x_entities (entity_id);
create index i_type_id on ca_loans_x_entities (type_id);
create unique index u_all on ca_loans_x_entities (
   loan_id,
   entity_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_loans_x_entities (label_left_id);
create index i_label_right_id on ca_loans_x_entities (label_right_id);


/*==========================================================================*/
create table ca_movements (
   movement_id                    int unsigned                   not null AUTO_INCREMENT,
   type_id                        int unsigned                   not null,
   locale_id                      smallint unsigned              null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   is_template                    tinyint unsigned               not null,
   source_info                    longtext                       not null,
   status                         tinyint unsigned               not null,
   deleted                        tinyint unsigned               not null,
   rank                             int unsigned                     not null,
   primary key (movement_id),
   
    constraint fk_ca_movements_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
       constraint fk_ca_movements_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_type_id on ca_movements(type_id);
create index i_locale_id on ca_movements(locale_id);
create index idno on ca_movements(idno);
create index idno_sort on ca_movements(idno_sort);


/*==========================================================================*/
create table ca_movement_labels (
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   movement_id                    int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   
   constraint fk_ca_movement_labels_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movement_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_movement_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_movement_id on ca_movement_labels(movement_id);
create index i_locale_id_id on ca_movement_labels(locale_id);
create index i_type_id on ca_movement_labels(type_id);
create index i_name on ca_movement_labels(name);
create index i_name_sort on ca_movement_labels(name_sort);


/*==========================================================================*/
create table ca_movements_x_objects (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   movement_id                    int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   object_id                      int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   
   constraint fk_ca_movements_x_objects_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_objects_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_objects_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_objects_label_left_id foreign key (label_left_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_objects_label_right_id foreign key (label_right_id)
      references ca_object_labels (label_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_movement_id on ca_movements_x_objects (movement_id);
create index i_object_id on ca_movements_x_objects (object_id);
create index i_type_id on ca_movements_x_objects (type_id);
create unique index u_all on ca_movements_x_objects (
   movement_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_movements_x_objects (label_left_id);
create index i_label_right_id on ca_movements_x_objects (label_right_id);


/*==========================================================================*/
create table ca_movements_x_object_lots (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   movement_id                    int unsigned               not null,
   type_id                        smallint unsigned              not null,
   lot_id                         int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   
   constraint fk_ca_movements_x_object_lots_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_object_lots_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_object_lots_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_object_lots_label_left_id foreign key (label_left_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_object_lots_label_right_id foreign key (label_right_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_movement_id on ca_movements_x_object_lots (movement_id);
create index i_lot_id on ca_movements_x_object_lots (lot_id);
create index i_type_id on ca_movements_x_object_lots (type_id);
create unique index u_all on ca_movements_x_object_lots (
   movement_id,
   lot_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_movements_x_object_lots (label_left_id);
create index i_label_right_id on ca_movements_x_object_lots (label_right_id);


/*==========================================================================*/
create table ca_movements_x_entities (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   movement_id                    int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   entity_id                      int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   
   constraint fk_ca_movements_x_entities_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_entities_label_left_id foreign key (label_left_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_movement_id on ca_movements_x_entities (movement_id);
create index i_entity_id on ca_movements_x_entities (entity_id);
create index i_type_id on ca_movements_x_entities (type_id);
create unique index u_all on ca_movements_x_entities (
   movement_id,
   entity_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_movements_x_entities (label_left_id);
create index i_label_right_id on ca_movements_x_entities (label_right_id);


/*==========================================================================*/
create table ca_loans_x_movements (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   loan_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   movement_id                    int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   
   constraint fk_ca_loans_x_movements_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_movements_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_movements_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_movements_label_left_id foreign key (label_left_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_movement_label_right_id foreign key (label_right_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_loan_id on ca_loans_x_movements (loan_id);
create index i_movement_id on ca_loans_x_movements (movement_id);
create index i_type_id on ca_loans_x_movements (type_id);
create unique index u_all on ca_loans_x_movements (
   loan_id,
   movement_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_loans_x_movements (label_left_id);
create index i_label_right_id on ca_loans_x_movements (label_right_id);


/*==========================================================================*/
create table ca_attribute_values
(
   value_id                   	  int unsigned                   not null AUTO_INCREMENT,
   element_id                     smallint unsigned              not null,
   attribute_id                   int unsigned                   not null,
   item_id                        int unsigned,
   value_longtext1                longtext,
   value_longtext2                longtext,
   value_blob                     longblob,
   value_decimal1                 decimal(40,20),
   value_decimal2                 decimal(40,20),
   value_integer1                 int unsigned,
   source_info                    longtext                       not null,
   primary key (value_id),
   constraint fk_ca_attribute_values_attribute_id foreign key (attribute_id)
      references ca_attributes (attribute_id) on delete restrict on update restrict,
   constraint fk_ca_attribute_values_element_id foreign key (element_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict,
   constraint fk_ca_attribute_values_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_element_id on ca_attribute_values(element_id);
create index i_attribute_id on ca_attribute_values(attribute_id);
create index i_value_integer1 on ca_attribute_values(value_integer1);
create index i_value_decimal1 on ca_attribute_values(value_decimal1);
create index i_value_decimal2 on ca_attribute_values(value_decimal2);
create index i_item_id on ca_attribute_values(item_id);
create index i_value_longtext1 on ca_attribute_values
(
   value_longtext1(1024)
);
create index i_value_longtext2 on ca_attribute_values
(
   value_longtext2(1024)
);


/*==========================================================================*/
create table ca_occurrences_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_occurrences_x_vocabulary_terms_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_object_id on ca_occurrences_x_vocabulary_terms(occurrence_id);
create index i_item_id on ca_occurrences_x_vocabulary_terms(item_id);
create index i_type_id on ca_occurrences_x_vocabulary_terms(type_id);
create unique index u_all on ca_occurrences_x_vocabulary_terms
(
   occurrence_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_occurrences_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_occurrences_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_object_events_x_entities
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   event_id                       int unsigned                   not null,
   entity_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_events_x_entities_event_id foreign key (event_id)
      references ca_object_events (event_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_entities_label_left_id foreign key (label_left_id)
      references ca_object_event_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_event_id on ca_object_events_x_entities(event_id);
create index i_entity_id on ca_object_events_x_entities(entity_id);
create index i_type_id on ca_object_events_x_entities(type_id);
create unique index u_all on ca_object_events_x_entities
(
   type_id,
   event_id,
   entity_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_events_x_entities(label_left_id);
create index i_label_right_id on ca_object_events_x_entities(label_right_id);


/*==========================================================================*/
create table ca_collections_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   collection_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_collections_x_vocabulary_terms_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_item_id on ca_collections_x_vocabulary_terms(item_id);
create index i_collection_id on ca_collections_x_vocabulary_terms(collection_id);
create index i_type_id on ca_collections_x_vocabulary_terms(type_id);
create unique index u_all on ca_collections_x_vocabulary_terms
(
   collection_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_collections_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_collections_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_entities_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   entity_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_entities_x_vocabulary_terms_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_object_id on ca_entities_x_vocabulary_terms(entity_id);
create index i_item_id on ca_entities_x_vocabulary_terms(item_id);
create index i_type_id on ca_entities_x_vocabulary_terms(type_id);
create unique index u_all on ca_entities_x_vocabulary_terms
(
   entity_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_entities_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_entities_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_editor_uis (
	ui_id int unsigned not null auto_increment,
	user_id int unsigned null references ca_users(user_id),		/* owner of ui */
	is_system_ui tinyint unsigned not null,
	editor_type tinyint unsigned not null,							/* tablenum of editor */
	color char(6) null,
	icon longblob not null,
	
	primary key 				(ui_id),
	index i_user_id				(user_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_editor_ui_labels (
	label_id int unsigned not null auto_increment,
	ui_id int unsigned not null references ca_editor_uis(ui_id),
	name varchar(255) not null,
	description text not null,
	locale_id smallint not null references ca_locales(locale_id),
	
	primary key 				(label_id),
	index i_ui_id				(ui_id),
	index i_locale_id			(locale_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_editor_uis_x_user_groups (
	relation_id int unsigned not null auto_increment,
	ui_id int unsigned not null references ca_editor_uis(ui_id),
	group_id int unsigned not null references ca_user_groups(group_id),
	access 			tinyint unsigned not null,
	
	primary key 				(relation_id),
	index i_ui_id				(ui_id),
	index i_group_id			(group_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_editor_uis_x_users (
	relation_id int unsigned not null auto_increment,
	ui_id int unsigned not null references ca_editor_uis(ui_id),
	user_id int unsigned not null references ca_users(user_id),
	access 			tinyint unsigned not null,
	
	primary key 				(relation_id),
	index i_ui_id				(ui_id),
	index i_user_id			(user_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_editor_ui_screens (
	screen_id int unsigned not null auto_increment,
	parent_id int unsigned null,
	ui_id int unsigned not null references ca_editor_uis(ui_id),
	idno varchar(255) not null,
	rank smallint unsigned not null,
	is_default tinyint unsigned not null,
	color char(6) null,
	icon longblob not null,
	
	hier_left decimal(30,20) not null,
	hier_right decimal (30,20) not null,
	
	primary key 				(screen_id),
	index i_ui_id 				(ui_id),
	index i_parent_id			(parent_id),
	index i_hier_left			(hier_left),
	index i_hier_right			(hier_right),
      
   constraint fk_ca_editor_ui_screens_parent_id foreign key (parent_id)
      references ca_editor_ui_screens (screen_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_editor_ui_screen_labels (
	label_id int unsigned not null auto_increment,
	screen_id int unsigned not null references ca_editor_ui_screens(screen_id),
	name varchar(255) not null,
	description text not null,
	locale_id smallint not null references ca_locales(locale_id),
	
	primary key 				(label_id),
	index i_screen_id			(screen_id),
	index i_locale_id			(locale_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_editor_ui_bundle_placements (
	placement_id int unsigned not null auto_increment,
	screen_id int unsigned not null references ca_editor_ui_screens(screen_id),
	placement_code varchar(255) not null,
	bundle_name varchar(255) not null,
	
	rank smallint unsigned not null,
    settings longtext not null,
	
	primary key 				(placement_id),
	index i_screen_id			(screen_id),
	unique index u_bundle_name	(bundle_name, screen_id, placement_code)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_editor_ui_screen_type_restrictions (
   restriction_id                 int unsigned                   not null AUTO_INCREMENT,
   table_num                      tinyint unsigned               not null,
   type_id                        int unsigned,
   screen_id                      int unsigned                   not null,
   settings                       longtext                       not null,
   rank                           smallint unsigned              not null,
   primary key (restriction_id),
   constraint fk_ca_editor_ui_screen_type_restrictions_screen_id foreign key (screen_id)
      references ca_editor_ui_screens (screen_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_sets (
	set_id		int unsigned not null auto_increment,
	parent_id	int unsigned,
	hier_set_id int unsigned not null,
	user_id		int unsigned null references ca_users(user_id),
    type_id     int unsigned not null,
    commenting_status tinyint unsigned not null,
    tagging_status tinyint unsigned not null,
    rating_status tinyint unsigned not null,
	set_code    varchar(100) null,
	table_num	tinyint unsigned not null,
	status		tinyint unsigned not null,
	access		tinyint unsigned not null,	
	hier_left	decimal(30,20) unsigned not null,
	hier_right	decimal(30,20) unsigned not null,
   rank                             int unsigned                     not null,
	
	primary key (set_id),
      
	key i_user_id (user_id),
	key i_type_id (type_id),
	unique key u_set_code (set_code),
	key i_hier_left (hier_left),
	key i_hier_right (hier_right),
	key i_parent_id (parent_id),
	key i_hier_set_id (hier_set_id),
	key i_table_num (table_num),
      
   constraint fk_ca_sets_parent_id foreign key (parent_id)
      references ca_sets (set_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_set_labels (
	label_id	int unsigned not null auto_increment,
	set_id		int unsigned not null references ca_sets(set_id),
	locale_id	smallint unsigned not null references ca_locales(locale_id),
	
	name		varchar(255) not null,
	
	primary key (label_id),
	key i_set_id (set_id),
	key i_locale_id (locale_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_set_items (
	item_id		int unsigned not null auto_increment,
	set_id		int unsigned not null references ca_sets(set_id),
	table_num	tinyint unsigned not null,
	row_id		int unsigned not null,
    type_id     int unsigned not null,
	rank		int unsigned not null,
	
	primary key (item_id),
	key i_set_id (set_id),
	key i_type_id (type_id),
	key i_row_id (row_id),
	key i_table_num (table_num)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_set_item_labels (
	label_id	int unsigned not null auto_increment,
	item_id		int unsigned not null references ca_set_items(item_id),
	
	locale_id	smallint unsigned not null references ca_locales(locale_id),
	
	caption		text not null,
	
	primary key (label_id),
	key i_set_id (item_id),
	key i_locale_id (locale_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_sets_x_user_groups (
	relation_id int unsigned not null auto_increment,
	set_id int unsigned not null references ca_sets(set_id),
	group_id int unsigned not null references ca_user_groups(group_id),
	access tinyint unsigned not null,
	sdatetime int unsigned null,
	edatetime int unsigned null,
	
	primary key 				(relation_id),
	index i_set_id				(set_id),
	index i_group_id			(group_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_sets_x_users (
	relation_id int unsigned not null auto_increment,
	set_id int unsigned not null references ca_sets(set_id),
	user_id int unsigned not null references ca_user(user_id),
	access tinyint unsigned not null,
	sdatetime int unsigned null,
	edatetime int unsigned null,
	
	primary key 				(relation_id),
	index i_set_id				(set_id),
	index i_user_id			(user_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_item_comments (
	comment_id	int unsigned not null auto_increment,
	table_num	tinyint unsigned not null,
	row_id		int unsigned not null,
	
	user_id		int unsigned null references ca_users(user_id),
	locale_id	smallint unsigned not null references ca_locales(locale_id),
	
	comment		text null,
	rating		tinyint null,
	email		varchar(255),
	name		varchar(255),
	created_on	int unsigned not null,
	access		tinyint unsigned not null,
	ip_addr		varchar(39) null,
	moderated_on int unsigned null,
	moderated_by_user_id int unsigned null references ca_users(user_id),
	
	primary key (comment_id),
	key i_row_id (row_id),
	key i_table_num (table_num),
	key i_email (email),
	key i_user_id (user_id),
	key i_created_on (created_on),
	key i_access (access),
	key i_moderated_on (moderated_on)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_item_tags (
	tag_id		int unsigned not null auto_increment,

	locale_id	smallint unsigned not null references ca_locales(locale_id),
	tag			varchar(255) not null,
	
	primary key (tag_id),
	key u_tag (tag, locale_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_items_x_tags (
	relation_id	int unsigned not null auto_increment,
	table_num	tinyint unsigned not null,
	row_id		int unsigned not null,
	
	tag_id		int unsigned not null references ca_tags(tag_id),
	
	user_id		int unsigned null references ca_users(user_id),
	access		tinyint unsigned not null,
	
	ip_addr		char(39) null,
	
	created_on	int unsigned not null,
	
	moderated_on int unsigned null,
	moderated_by_user_id int unsigned null references ca_users(user_id),
	
	primary key (relation_id),
	key i_row_id (row_id),
	key i_table_num (table_num),
	key i_tag_id (tag_id),
	key i_user_id (user_id),
	key i_access (access),
	key i_created_on (created_on),
	key i_moderated_on (moderated_on)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_item_views (
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
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_item_view_counts (
	table_num	tinyint unsigned not null,
	row_id		int unsigned not null,
	view_count	int unsigned not null,
	
	KEY u_row (row_id, table_num),
	KEY i_row_id (row_id),
	KEY i_table_num (table_num),
	KEY i_view_count (view_count)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_search_forms (
	form_id			int unsigned not null primary key auto_increment,
	user_id			int unsigned null references ca_users(user_id),
	
	form_code		varchar(100) null,
	table_num		tinyint unsigned not null,
	
	is_system		tinyint unsigned not null,
	
	settings		text not null,
	
	UNIQUE KEY u_form_code (form_code),
	KEY i_user_id (user_id),
	KEY i_table_num (table_num)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_search_form_labels (
	label_id		int unsigned not null primary key auto_increment,
	form_id			int unsigned null references ca_search_forms(form_id),
	locale_id		smallint unsigned not null references ca_locales(locale_id),
	name			varchar(255) not null,
	name_sort		varchar(255) not null,
	description		text not null,
	source_info		longtext not null,
	is_preferred	tinyint unsigned not null,
	
	KEY i_form_id (form_id),
	KEY i_locale_id (locale_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_search_form_placements (
	placement_id	int unsigned not null primary key auto_increment,
	form_id		int unsigned not null references ca_search_forms(form_id),
	
	bundle_name 	varchar(255) not null,
	rank			int unsigned not null,
	settings		longtext not null,
	
	KEY i_bundle_name (bundle_name),
	KEY i_rank (rank),
	KEY i_form_id (form_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_search_forms_x_user_groups (
	relation_id 	int unsigned not null auto_increment,
	form_id 		int unsigned not null references ca_search_forms(form_id),
	group_id 		int unsigned not null references ca_user_groups(group_id),
	access 			tinyint unsigned not null,
	
	primary key 				(relation_id),
	index i_form_id				(form_id),
	index i_group_id			(group_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_search_forms_x_users (
	relation_id 	int unsigned not null auto_increment,
	form_id 		int unsigned not null references ca_search_forms(form_id),
	user_id 		int unsigned not null references ca_users(user_id),
	access 			tinyint unsigned not null,
	
	primary key 				(relation_id),
	index i_form_id			(form_id),
	index i_user_id			(user_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_search_log (
	search_id			int unsigned not null primary key auto_increment,
	log_datetime		int unsigned not null,
	user_id				int unsigned null references ca_users(user_id),
	table_num			tinyint unsigned not null,
	search_expression	varchar(1024) not null,
	num_hits			int unsigned not null,
	form_id				int unsigned null references ca_search_forms(form_id),
	ip_addr				char(15) null,
	details				text not null,
	execution_time 		decimal(7,3) not null,
	search_source 		varchar(40) not null,
	
	KEY i_log_datetime (log_datetime),
	KEY i_user_id (user_id),
	KEY i_form_id (form_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_bundle_displays (
	display_id		int unsigned not null primary key auto_increment,
	user_id			int unsigned null references ca_users(user_id),
	
	display_code	varchar(100) null,
	table_num		tinyint unsigned not null,
	
	is_system		tinyint unsigned not null,
	
	settings		text not null,
	
	UNIQUE KEY u_display_code (display_code),
	KEY i_user_id (user_id),
	KEY i_table_num (table_num)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_bundle_display_labels (
	label_id		int unsigned not null primary key auto_increment,
	display_id		int unsigned null references ca_bundle_displays(display_id),
	locale_id		smallint unsigned not null references ca_locales(locale_id),
	name			varchar(255) not null,
	name_sort		varchar(255) not null,
	description		text not null,
	source_info		longtext not null,
	is_preferred	tinyint unsigned not null,
	
	KEY i_display_id (display_id),
	KEY i_locale_id (locale_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_bundle_display_placements (
	placement_id	int unsigned not null primary key auto_increment,
	display_id		int unsigned not null references ca_bundle_displays(display_id),
	
	bundle_name 	varchar(255) not null,
	rank			int unsigned not null,
	settings		longtext not null,
	
	KEY i_bundle_name (bundle_name),
	KEY i_rank (rank),
	KEY i_display_id (display_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_bundle_displays_x_user_groups (
	relation_id 	int unsigned not null auto_increment,
	display_id 		int unsigned not null references ca_bundle_displays(display_id),
	group_id 		int unsigned not null references ca_user_groups(group_id),
	access 			tinyint unsigned not null,
	
	primary key 				(relation_id),
	index i_display_id			(display_id),
	index i_group_id			(group_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_bundle_displays_x_users (
	relation_id 	int unsigned not null auto_increment,
	display_id 	int unsigned not null references ca_bundle_displays(display_id),
	user_id 		int unsigned not null references ca_users(user_id),
	access 			tinyint unsigned not null,
	
	primary key 				(relation_id),
	index i_display_id			(display_id),
	index i_user_id			(user_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_bundle_mappings (
	mapping_id		int unsigned not null primary key auto_increment,
	
	direction		char(1) not null,
	table_num		tinyint unsigned not null,
	mapping_code	varchar(100) null,
	target			varchar(100) not null,
    access          tinyint unsigned not null,
	settings		text not null,
	
	UNIQUE KEY u_mapping_code (mapping_code),
	KEY i_target (target)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_bundle_mapping_labels (
	label_id		int unsigned not null primary key auto_increment,
	mapping_id		int unsigned null references ca_bundle_mappings(mapping_id),
	locale_id		smallint unsigned not null references ca_locales(locale_id),
	name			varchar(255) not null,
	name_sort		varchar(255) not null,
	description		text not null,
	source_info		longtext not null,
	is_preferred	tinyint unsigned not null,
	
	KEY i_mapping_id (mapping_id),
	KEY i_locale_id (locale_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_bundle_mapping_relationships (
	relation_id			int unsigned not null primary key auto_increment,
	mapping_id			int unsigned not null references ca_bundle_mappings(mapping_id),
	type_id				int unsigned null references ca_list_items(item_id),
	
	group_code			varchar(100) not null,
	bundle_name 		varchar(255) not null,
	element_name		varchar(100) not null,
	destination			varchar(1024) not null,	
	
	settings			text not null,
	
	KEY i_mapping_id (mapping_id),
	KEY i_type_id (type_id),
	KEY i_bundle_name (bundle_name),
	KEY i_destination (destination(255))
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
/* Support for tour content
/*==========================================================================*/
create table ca_tours
(
   tour_id                       int unsigned                   not null AUTO_INCREMENT,
   tour_code                  varchar(100)                   not null,
   type_id                        int unsigned                   null,
   rank                           int unsigned              not null,
   color                          char(6)                        null,
   icon                           longblob                       not null,
   access                        tinyint unsigned               not null,
   status                         tinyint unsigned               not null,
   user_id                        int unsigned                   null,
   primary key (tour_id),
   
   constraint fk_ca_tours_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_type_id on ca_tours(type_id);
create index i_user_id on ca_tours(user_id);
create index i_tour_code on ca_tours(tour_code);


/*==========================================================================*/
create table ca_tour_labels
(
   label_id                       int unsigned              not null AUTO_INCREMENT,
   tour_id                        int unsigned              not null,
   locale_id                      smallint unsigned              not null,
   name_sort                      varchar(255)                   not null,
   name                           varchar(255)                   not null,
   primary key (label_id),
   
   constraint fk_ca_tour_labels_tour_id foreign key (tour_id)
      references ca_tours (tour_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_tour_id on ca_tour_labels(tour_id);
create index i_name on ca_tour_labels(name);
create index i_name_sort on ca_tour_labels(name_sort);
create unique index u_locale_id on ca_tour_labels(tour_id, locale_id);


/*==========================================================================*/
create table ca_tour_stops
(
   stop_id                       int unsigned              not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   tour_id                        int unsigned              not null,
   type_id                        int unsigned                   null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   rank                           int unsigned              not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   hier_stop_id				int unsigned 				not null,
   color                          char(6)                        null,
   icon                           longblob                       not null,
   access                         tinyint unsigned               not null,
   status                         tinyint unsigned               not null,
   deleted                        tinyint unsigned               not null,
   primary key (stop_id),
   
   constraint fk_ca_tour_stops_tour_id foreign key (tour_id)
      references ca_tours (tour_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_tour_id on ca_tour_stops(tour_id);
create index i_type_id on ca_tour_stops(type_id);
create index i_parent_id on ca_tour_stops(parent_id);
create index i_hier_stop_id on ca_tour_stops(hier_stop_id);
create index i_hier_left on ca_tour_stops(hier_left);
create index i_hier_right on ca_tour_stops(hier_right);
create index i_idno on ca_tour_stops(idno);
create index i_idno_sort on ca_tour_stops(idno_sort);


/*==========================================================================*/
create table ca_tour_stop_labels
(
   label_id                       int unsigned              not null AUTO_INCREMENT,
   stop_id                        int unsigned              not null,
   locale_id                      smallint unsigned              not null,
   name                           varchar(255)                   not null,
   name_sort                      varchar(255)                   not null,
   primary key (label_id),
   
   constraint fk_ca_tour_stop_labels_stop_id foreign key (stop_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stop_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_stop_id on ca_tour_stop_labels(stop_id);
create index i_name on ca_tour_stop_labels(name);
create index i_name_sort on ca_tour_stop_labels(name_sort);
create unique index u_locale_id on ca_tour_stop_labels(stop_id, locale_id);


/*==========================================================================*/
create table ca_tour_stops_x_objects
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   object_id                      int unsigned               not null,
   stop_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_tour_stops_x_objects_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_objects_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_objects_stop_id foreign key (stop_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_objects_label_left_id foreign key (label_left_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_objects_label_right_id foreign key (label_right_id)
      references ca_object_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_object_id on  ca_tour_stops_x_objects(object_id);
create index i_stop_id on  ca_tour_stops_x_objects(stop_id);
create index i_type_id on  ca_tour_stops_x_objects(type_id);
create unique index u_all on  ca_tour_stops_x_objects
(
   object_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on  ca_tour_stops_x_objects(label_left_id);
create index i_label_right_id on  ca_tour_stops_x_objects(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_entities
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   entity_id                      int unsigned               not null,
   stop_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_tour_stops_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_entities_stop_id foreign key (stop_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_entities_label_left_id foreign key (label_left_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_entity_id on  ca_tour_stops_x_entities(entity_id);
create index i_stop_id on  ca_tour_stops_x_entities(stop_id);
create index i_type_id on  ca_tour_stops_x_entities(type_id);
create unique index u_all on  ca_tour_stops_x_entities
(
   entity_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on  ca_tour_stops_x_entities(label_left_id);
create index i_label_right_id on  ca_tour_stops_x_entities(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   place_id                      int unsigned               not null,
   stop_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_tour_stops_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_places_stop_id foreign key (stop_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_places_label_left_id foreign key (label_left_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_place_id on  ca_tour_stops_x_places(place_id);
create index i_stop_id on  ca_tour_stops_x_places(stop_id);
create index i_type_id on  ca_tour_stops_x_places(type_id);
create unique index u_all on  ca_tour_stops_x_places
(
   place_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on  ca_tour_stops_x_places(label_left_id);
create index i_label_right_id on  ca_tour_stops_x_places(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                      int unsigned               not null,
   stop_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_tour_stops_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_occurrences_stop_id foreign key (stop_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_occurrence_id on  ca_tour_stops_x_occurrences(occurrence_id);
create index i_stop_id on  ca_tour_stops_x_occurrences(stop_id);
create index i_type_id on  ca_tour_stops_x_occurrences(type_id);
create unique index u_all on  ca_tour_stops_x_occurrences
(
   occurrence_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on  ca_tour_stops_x_occurrences(label_left_id);
create index i_label_right_id on  ca_tour_stops_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   collection_id                      int unsigned               not null,
   stop_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_tour_stops_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_collections_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_collections_stop_id foreign key (stop_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_collections_label_left_id foreign key (label_left_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_collection_id on  ca_tour_stops_x_collections(collection_id);
create index i_stop_id on  ca_tour_stops_x_collections(stop_id);
create index i_type_id on  ca_tour_stops_x_collections(type_id);
create unique index u_all on  ca_tour_stops_x_collections
(
   collection_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on  ca_tour_stops_x_collections(label_left_id);
create index i_label_right_id on  ca_tour_stops_x_collections(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   item_id                      int unsigned               not null,
   stop_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_tour_stops_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_vocabulary_terms_stop_id foreign key (stop_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_item_id on  ca_tour_stops_x_vocabulary_terms(item_id);
create index i_stop_id on  ca_tour_stops_x_vocabulary_terms(stop_id);
create index i_type_id on  ca_tour_stops_x_vocabulary_terms(type_id);
create unique index u_all on  ca_tour_stops_x_vocabulary_terms
(
   item_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on  ca_tour_stops_x_vocabulary_terms(label_left_id);
create index i_label_right_id on  ca_tour_stops_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_tour_stops
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   stop_left_id                 int unsigned               not null,
   stop_right_id                int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_notes                   text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_tour_stops_x_tour_stops_stop_left_id foreign key (stop_left_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_tour_stops_stop_right_id foreign key (stop_right_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_tour_stops_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_tour_stops_label_left_id foreign key (label_left_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_tour_stops_label_right_id foreign key (label_right_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_stop_left_id on ca_tour_stops_x_tour_stops(stop_left_id);
create index i_stop_right_id on ca_tour_stops_x_tour_stops(stop_right_id);
create index i_type_id on ca_tour_stops_x_tour_stops(type_id);
create unique index u_all on ca_tour_stops_x_tour_stops
(
   stop_left_id,
   stop_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_tour_stops_x_tour_stops(label_left_id);
create index i_label_right_id on ca_tour_stops_x_tour_stops(label_right_id);


/*==========================================================================*/
create table ca_mysql_fulltext_search (
	index_id			int unsigned		not null auto_increment,
	
	table_num			tinyint unsigned 	not null,
	row_id				int unsigned 		not null,
	
	field_table_num		tinyint unsigned	not null,
	field_num			tinyint unsigned	not null,
	field_row_id		int unsigned		not null,
	
	fieldtext			longtext 			not null,
	
	boost				int 				not null default 1,
	
	PRIMARY KEY								(index_id),
	FULLTEXT INDEX		f_fulltext			(fieldtext),
	INDEX				i_table_num			(table_num),
	INDEX				i_row_id			(row_id),
	INDEX				i_field_table_num	(field_table_num),
	INDEX				i_field_num			(field_num),
	INDEX				i_boost				(boost),
	INDEX				i_field_row_id		(field_row_id)
	
) engine=myisam character set utf8 collate utf8_general_ci;


/*==========================================================================*/
create table ca_did_you_mean_phrases (
	phrase_id			int unsigned		not null auto_increment,
	
	table_num			tinyint unsigned 	not null,
	
	phrase				varchar(255) 		not null,
	num_words			tinyint unsigned	not null,
	
	PRIMARY KEY								(phrase_id),
	INDEX				i_table_num			(table_num),
	INDEX				i_num_words			(num_words),
	UNIQUE INDEX		u_all				(table_num, phrase)
	
) engine=innodb character set utf8 collate utf8_general_ci;


/*==========================================================================*/
create table ca_did_you_mean_ngrams (
	phrase_id			int unsigned		not null references ca_did_you_mean_phrases(phrase_id),
	ngram				varchar(255)		not null,
	endpoint			tinyint unsigned	not null,
	
	INDEX				i_phrase_id			(phrase_id),
	INDEX				i_ngram				(ngram)
) engine=innodb character set utf8 collate utf8_general_ci;


/*==========================================================================*/
create table ca_watch_list
(
   watch_id                       int unsigned                   not null AUTO_INCREMENT,
   table_num                      tinyint unsigned               not null,
   row_id                         int unsigned                   not null,
   user_id                        int unsigned                   not null,
   primary key (watch_id),
   
   constraint fk_ca_watch_list_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_row_id on ca_watch_list(row_id, table_num);
create index i_user_id on ca_watch_list(user_id);
create unique index u_all on ca_watch_list(row_id, table_num, user_id);


/*==========================================================================*/
create table ca_user_notes
(
   note_id                       int unsigned                   not null AUTO_INCREMENT,
   table_num                     tinyint unsigned               not null,
   row_id                        int unsigned                   not null,
   user_id                       int unsigned                   not null,
   bundle_name                   varchar(255)                   not null,
   note                          longtext                       not null,
   created_on                    int unsigned                   not null,
   primary key (note_id),
   
   constraint fk_ca_user_notes_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_row_id on ca_user_notes(row_id, table_num);
create index i_user_id on ca_user_notes(user_id);
create index i_bundle_name on ca_user_notes(bundle_name);


/*==========================================================================*/
/* Schema update tracking                                                   */
/*==========================================================================*/
create table ca_schema_updates (
	version_num		int unsigned not null,
	datetime		int unsigned not null,
	
	UNIQUE KEY u_version_num (version_num)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/* Indicate up to what migration this schema definition covers */
/* CURRENT MIGRATION: 38 */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (38, unix_timestamp());
