SET FOREIGN_KEY_CHECKS = 0;

/* type_id field in *_labels tables is now nullable */

ALTER TABLE ca_occurrence_labels MODIFY COLUMN type_id int unsigned null;
ALTER TABLE ca_place_labels MODIFY COLUMN type_id int unsigned null;
ALTER TABLE ca_representation_clip_labels MODIFY COLUMN type_id int unsigned null;
ALTER TABLE ca_storage_location_labels MODIFY COLUMN type_id int unsigned null;
ALTER TABLE ca_lot_labels MODIFY COLUMN type_id int unsigned null;
ALTER TABLE ca_collection_labels MODIFY COLUMN type_id int unsigned null;
ALTER TABLE ca_object_labels MODIFY COLUMN type_id int unsigned null;
ALTER TABLE ca_georeference_type_labels MODIFY COLUMN type_id int unsigned null;
ALTER TABLE ca_list_item_labels MODIFY COLUMN type_id int unsigned null;
ALTER TABLE ca_list_items MODIFY COLUMN type_id int unsigned null;

/* fix unique index on ca_lists */
drop index u_locale_id on ca_list_labels;
create unique index u_locale_id on ca_list_labels(list_id, locale_id);

/* fix unique index on ca_list_item_labels */
create index i_foo on ca_list_item_labels(locale_id);
drop index u_locale on ca_list_item_labels;
create index i_locale on ca_list_item_labels(locale_id, is_preferred, item_id);
drop index i_foo on ca_list_item_labels;


SET FOREIGN_KEY_CHECKS = 1;
