ALTER TABLE ca_relationship_types DROP COLUMN typename;
ALTER TABLE ca_relationship_types DROP COLUMN typename_reverse;
ALTER TABLE ca_relationship_types DROP COLUMN description;
ALTER TABLE ca_relationship_types DROP COLUMN description_reverse;

ALTER TABLE ca_relationship_types ADD COLUMN type_code varchar(100) not null;
CREATE UNIQUE INDEX u_type_code ON ca_relationship_types(type_code, table_num);