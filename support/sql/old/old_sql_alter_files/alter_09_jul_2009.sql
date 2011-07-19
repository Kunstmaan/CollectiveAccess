/*==============================================================*/
/* Table: ca_object_event_labels                                  */
/*==============================================================*/
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
   constraint fk_reference_311 foreign key (event_id)
      references ca_object_events (event_id) on delete restrict on update restrict,
   constraint fk_reference_312 foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_reference_313 foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;



/*==============================================================*/
/* Index: i_name                                                */
/*==============================================================*/
create index i_name on ca_object_event_labels
(
   name
);

/*==============================================================*/
/* Index: i_event_id                                              */
/*==============================================================*/
create index i_event_id on ca_object_event_labels
(
   event_id
);

/*==============================================================*/
/* Index: u_all                                                 */
/*==============================================================*/
create unique index u_all on ca_object_event_labels
(
   event_id,
   name(255),
   type_id,
   locale_id
);

/*==============================================================*/
/* Index: i_name_sort                                           */
/*==============================================================*/
create index i_name_sort on ca_object_event_labels
(
   name_sort
);

/*==============================================================*/
/* Index: i_type_id                                             */
/*==============================================================*/
create index i_type_id on ca_object_event_labels
(
   type_id
);

/*==============================================================*/
/* Index: i_locale_id                                           */
/*==============================================================*/
create index i_locale_id on ca_object_event_labels
(
   locale_id
);


