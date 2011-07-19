alter table ca_lot_labels rename ca_object_lot_labels;
alter table ca_lot_events rename ca_object_lot_events;
alter table ca_lot_events_x_storage_locations rename ca_object_lot_events_x_storage_locations;

alter table ca_collections add column idno varchar(255) not null;
alter table ca_collections add column idno_sort varchar(255) not null;
create index i_idno on ca_collections(idno);
create index i_idno_sort on ca_collections(idno_sort);

alter table ca_entity_labels drop column title;
alter table ca_entity_labels add column prefix varchar(100) not null;
alter table ca_entity_labels add column suffix varchar(100) not null;