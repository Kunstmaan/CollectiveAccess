ALTER TABLE ca_sets ADD COLUMN type_id int unsigned not null;
ALTER TABLE ca_set_items ADD COLUMN type_id int unsigned not null;

ALTER TABLE ca_sets ADD COLUMN parent_id int unsigned null;
ALTER TABLE ca_sets ADD COLUMN hier_set_id int unsigned null;
ALTER TABLE ca_sets ADD COLUMN hier_left decimal(30,20) not null;
ALTER TABLE ca_sets ADD COLUMN hier_right decimal(30,20) not null;

CREATE INDEX i_parent_id ON ca_sets(parent_id);
CREATE INDEX i_hier_set_id ON ca_sets(hier_set_id);
CREATE INDEX i_hier_left ON ca_sets(hier_left);
CREATE INDEX i_hier_right ON ca_sets(hier_right);

ALTER TABLE ca_user_groups ADD COLUMN user_id int unsigned null;

ALTER TABLE ca_representation_annotations ADD COLUMN preview longtext not null;