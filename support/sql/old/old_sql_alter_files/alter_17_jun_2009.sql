ALTER TABLE ca_relationship_types ADD COLUMN hier_left decimal(30,20) unsigned not null;
ALTER TABLE ca_relationship_types ADD COLUMN hier_right decimal(30,20) unsigned not null;
ALTER TABLE ca_relationship_types ADD COLUMN hier_type_id smallint unsigned;
ALTER TABLE ca_relationship_types ADD COLUMN parent_id int unsigned;

create index i_parent_id on ca_relationship_types(parent_id);
create index i_hier_type_id on ca_relationship_types(hier_type_id);
create index i_hier_left on ca_relationship_types(hier_left);
create index i_hier_right on ca_relationship_types(hier_right);