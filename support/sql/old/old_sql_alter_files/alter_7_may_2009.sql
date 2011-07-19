ALTER TABLE ca_objects MODIFY COLUMN locale_id smallint unsigned null;
ALTER TABLE ca_objects MODIFY COLUMN item_status_id int unsigned null;
ALTER TABLE ca_objects MODIFY COLUMN acquisition_type_id int unsigned null;

ALTER TABLE ca_entities MODIFY COLUMN locale_id smallint unsigned null;
ALTER TABLE ca_places MODIFY COLUMN locale_id smallint unsigned null;
ALTER TABLE ca_occurrences MODIFY COLUMN locale_id smallint unsigned null;
ALTER TABLE ca_collections MODIFY COLUMN locale_id smallint unsigned null;
ALTER TABLE ca_representation_annotations MODIFY COLUMN locale_id smallint unsigned null;