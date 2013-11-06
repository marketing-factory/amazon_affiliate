#
# Table structure for table 'tx_amazonaffiliate_products'
#
CREATE TABLE tx_amazonaffiliate_products (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	name tinytext,
	status tinyint(4) unsigned DEFAULT '0' NOT NULL,
	asin tinytext,
	
	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY asin (asin(10))
);



#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
	tx_amazonaffiliate_amazon_asin text
);