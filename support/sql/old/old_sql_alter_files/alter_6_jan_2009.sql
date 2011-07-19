alter table ca_metadata_elements add column hier_element_id smallint unsigned not null;
alter table ca_metadata_elements drop column is_abstract;
create index i_locale_id on ca_metadata_element_labels(locale_id);
drop index u_locale_id on ca_metadata_element_labels;