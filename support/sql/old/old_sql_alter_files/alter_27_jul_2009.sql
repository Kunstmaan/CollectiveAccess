/* Fix for typos in schema */
ALTER TABLE ca_occurrence_labels CHANGE COLUMN synonym_id label_id int unsigned not null auto_increment;
ALTER TABLE ca_object_events CHANGE COLUMN planned_edatetme planned_edatetime decimal(30,20) not null;
ALTER TABLE ca_object_lot_events CHANGE COLUMN planned_edatetme planned_edatetime decimal(30,20) not null;
ALTER TABLE ca_occurrences_x_occurrences CHANGE COLUMN edatetme edatetime decimal(30,20) null;