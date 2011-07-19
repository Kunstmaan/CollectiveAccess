ALTER TABLE ca_objects_x_object_representations ADD COLUMN is_primary tinyint unsigned not null;
ALTER TABLE ca_object_representations DROP COLUMN is_primary;