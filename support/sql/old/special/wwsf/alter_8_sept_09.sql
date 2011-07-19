DROP TABLE IF EXISTS ca_item_view_counts;
CREATE TABLE ca_item_view_counts (
	table_num	tinyint unsigned not null,
	row_id		int unsigned not null,
	view_count	int unsigned not null,
	
	KEY u_row (row_id, table_num),
	KEY i_row_id (row_id),
	KEY i_table_num (table_num),
	KEY i_view_count (view_count)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

INSERT INTO ca_item_view_counts
SELECT table_num, row_id, count(*)
FROM ca_item_views 
GROUP BY table_num, row_id;