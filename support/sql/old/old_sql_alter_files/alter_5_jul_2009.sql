DROP TABLE IF EXISTS ca_did_you_mean_phrases;
CREATE TABLE IF NOT EXISTS ca_did_you_mean_phrases (
	phrase_id			int unsigned		not null auto_increment,
	
	table_num			tinyint unsigned 	not null,
	
	phrase				varchar(255) 		not null,
	num_words			tinyint unsigned	not null,
	
	PRIMARY KEY								(phrase_id),
	INDEX				i_table_num			(table_num),
	INDEX				i_num_words			(num_words),
	UNIQUE INDEX		u_all				(table_num, phrase)
	
) TYPE=innodb character set utf8 collate utf8_general_ci;


# -----------------------------------------------------------------
DROP TABLE IF EXISTS ca_did_you_mean_ngrams;
CREATE TABLE IF NOT EXISTS ca_did_you_mean_ngrams (
	phrase_id			int unsigned		not null references ca_did_you_mean_phrases(phrase_id),
	ngram				varchar(255)		not null,
	endpoint			tinyint unsigned	not null,
	
	INDEX				i_phrase_id			(phrase_id),
	INDEX				i_ngram				(ngram)
) TYPE=innodb character set utf8 collate utf8_general_ci;

# -----------------------------------------------------------------
DROP TABLE IF EXISTS ca_bundle_displays;
CREATE TABLE ca_bundle_displays (
	display_id int unsigned not null auto_increment,
	
	
	bundle_name varchar(255) not null,
	
	rank smallint unsigned not null,
    settings longtext not null,
	
	primary key 				(display_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;