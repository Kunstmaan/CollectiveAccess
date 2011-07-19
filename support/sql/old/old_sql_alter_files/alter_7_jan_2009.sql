alter table ca_attribute_values drop column locale_id;
alter table ca_attribute_values drop column table_num;
alter table ca_attribute_values drop column row_id;
alter table ca_attribute_values drop column sequence_number;

alter table ca_attributes add column locale_id smallint unsigned not null;
alter table ca_attributes add column table_num tinyint unsigned not null;
alter table ca_attributes add column row_id int unsigned not null;