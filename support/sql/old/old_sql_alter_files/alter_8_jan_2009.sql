alter table ca_attribute_values drop foreign key fk_reference_205;
alter table ca_attribute_values drop column label_id;
alter table ca_attribute_values add column item_id int unsigned null references ca_list_items(item_id);