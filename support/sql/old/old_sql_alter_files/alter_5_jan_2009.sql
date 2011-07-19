/* ALTER TABLE ca_metadata_type_restrictions DROP COLUMN usage_notes; */

DROP TABLE ca_attributes;
DROP TABLE ca_attribute_sets;


create table ca_attributes
(
   attribute_id                   int unsigned                   not null AUTO_INCREMENT,
   element_id                     smallint unsigned              not null,
   primary key (attribute_id),
   constraint fk_reference_211 foreign key (element_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_element_id on ca_attributes(element_id);

create table ca_attribute_values
(
   value_id                   	  int unsigned                   not null AUTO_INCREMENT,
   element_id                     smallint unsigned              not null,
   locale_id                      smallint unsigned              not null,
   sequence_number                tinyint unsigned               not null,
   attribute_id                   int unsigned                   not null,
   label_id                       int unsigned,
   table_num                      tinyint unsigned               not null,
   row_id                         int unsigned                   not null,
   value_longtext1                longtext,
   value_longtext2                longtext,
   value_decimal1                 decimal(40,20),
   value_decimal2                 decimal(40,20),
   value_integer1                 int unsigned,
   source_info                    longtext                       not null,
   primary key (value_id),
   constraint fk_reference_176 foreign key (attribute_id)
      references ca_attributes (attribute_id) on delete restrict on update restrict,
   constraint fk_reference_177 foreign key (element_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict,
   constraint fk_reference_179 foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_reference_205 foreign key (label_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_element_id on ca_attribute_values(element_id);
create index i_locale_id on ca_attribute_values(locale_id);
create index i_row_id on ca_attribute_values(row_id);
create index i_attribute_id on ca_attribute_values(attribute_id);
create index i_value_integer1 on ca_attribute_values(value_integer1);
create index i_table_num on ca_attribute_values(table_num);
create index i_value_decimal1 on ca_attribute_values(value_decimal1);
create index i_value_decimal2 on ca_attribute_values(value_decimal2);
create index i_label_id on ca_attribute_values(label_id);
