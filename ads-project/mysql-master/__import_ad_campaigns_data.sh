#!/bin/bash

echo "Create a table: buzzvil.ad_campaigns"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e " \
CREATE TABLE buzzvil.ad_campaigns ( \
id INT(11) PRIMARY KEY, \
name VARCHAR(64) NOT NULL, \
image_url VARCHAR(128) NOT NULL, \
landing_url VARCHAR(128) NOT NULL, \
weight INT(11) DEFAULT NULL, \
target_country VARCHAR(64) DEFAULT NULL, \
target_gender VARCHAR(64) DEFAULT NULL, \
reward INT(11) DEFAULT NULL \
); \
"

echo "Load & insert ad_campaigns data from csv file"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e " \
LOAD DATA \
INFILE '/var/lib/mysql-files/ad_campaigns.csv' \
INTO TABLE buzzvil.ad_campaigns \
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '' \
LINES STARTING BY '' TERMINATED BY '\r\n' \
IGNORE 1 LINES; \
"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "SELECT COUNT(*) FROM buzzvil.ad_campaigns;"