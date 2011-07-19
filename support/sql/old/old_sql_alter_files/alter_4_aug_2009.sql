ALTER TABLE ca_set_items ADD COLUMN table_num tinyint not null;
UPDATE ca_set_items, ca_sets SET ca_set_items.table_num = ca_sets.table_num WHERE ca_set_items.set_id = ca_sets.set_id;