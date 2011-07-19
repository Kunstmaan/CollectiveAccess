alter table ca_objects_x_object_representations change column link_id relation_id int unsigned not null auto_increment;
alter table ca_groups_x_roles change column link_id relation_id int unsigned not null auto_increment;
alter table ca_users_x_groups change column link_id relation_id int unsigned not null auto_increment;
alter table ca_users_x_roles change column link_id relation_id int unsigned not null auto_increment;

alter table ca_clips_x_vocabulary_terms change column label_id item_id int unsigned not null;
alter table ca_objects_x_vocabulary_terms change column label_id item_id int unsigned not null;
alter table ca_places_x_vocabulary_terms change column label_id item_id int unsigned not null;
alter table ca_occurrences_x_vocabulary_terms change column label_id item_id int unsigned not null;
alter table ca_collections_x_vocabulary_terms change column label_id item_id int unsigned not null;
alter table ca_entities_x_vocabulary_terms change column label_id item_id int unsigned not null;