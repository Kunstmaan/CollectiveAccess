ALTER TABLE ca_collection_labels DROP COLUMN status;
ALTER TABLE ca_entity_labels DROP COLUMN status;
ALTER TABLE ca_occurrence_labels DROP COLUMN status;
ALTER TABLE ca_place_labels DROP COLUMN status;
ALTER TABLE ca_representation_clip_labels DROP COLUMN  status;
ALTER TABLE ca_list_item_labels DROP COLUMN status;

ALTER TABLE ca_storage_location ADD COLUMN deleted tinyint unsigned not null;
ALTER TABLE ca_entities ADD COLUMN hier_entity_id int unsigned not null;