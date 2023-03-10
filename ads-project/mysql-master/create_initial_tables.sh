#!/bin/bash

set -e

echo ""
echo "Create a table: ads.ad_campaigns"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e " \
CREATE TABLE ads.ad_campaigns ( \
id INT(11) AUTO_INCREMENT, \
name VARCHAR(64) NOT NULL, \
image_url VARCHAR(128) NOT NULL, \
landing_url VARCHAR(128) NOT NULL, \
weight INT(11) DEFAULT NULL, \
target_country VARCHAR(64) DEFAULT NULL, \
target_gender VARCHAR(64) DEFAULT NULL, \
reward INT(11) DEFAULT NULL, \
PRIMARY KEY(id)
);"

echo ""
echo "Load & insert csv data into ads.ad_campaigns"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e " \
LOAD DATA \
INFILE '/var/lib/mysql-files/ad_campaigns.csv' \
INTO TABLE ads.ad_campaigns \
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '' \
LINES STARTING BY '' TERMINATED BY '\r\n' \
IGNORE 1 LINES; \
"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "SELECT COUNT(*) FROM ads.ad_campaigns;"

echo ""
echo "Create a table: ads.user"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e " \
CREATE TABLE ads.user ( \
id INT(11) AUTO_INCREMENT, \
name VARCHAR(64) NOT NULL, \
gender VARCHAR(64) NOT NULL, \
country VARCHAR(64) NOT NULL, \
reward INT(11) NOT NULL DEFAULT 0, \
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, \
updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, \
PRIMARY KEY(id) \
);"

echo ""
echo "Insert sample users: ads.user"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e " \
INSERT INTO \
ads.user (name, gender, country) \
VALUES \
('dongwon', 'M', 'KR'), \
('wanna', 'F', 'US'), \
('join', 'M', 'HK'), \
('new', 'F', 'JP'), \
('company', 'M', 'TW') \
;"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "SELECT * FROM ads.user;"

echo ""
echo "Create a table: ads.user_reward_queue"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e " \
CREATE TABLE ads.user_reward_queue ( \
id INT(11) AUTO_INCREMENT, \
type varchar(64) NOT NULL, \
user_id INT(11), \
reward INT(11) NOT NULL, \
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, \
approved_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, \
PRIMARY KEY(id),
CONSTRAINT FK_user_user_reward_queue FOREIGN KEY(user_id) REFERENCES user(id) ON UPDATE CASCADE
);"

echo ""
echo "Create a table: ads.ad_issue"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e " \
CREATE TABLE ads.ad_issue ( \
id VARCHAR(64), \
user_id INT(11), \
ad_id INT(11), \
reward INT(11) NOT NULL, \
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, \
user_reward_queue_id INT(11) DEFAULT NULL, \
PRIMARY KEY(id, ad_id, user_id),
CONSTRAINT FK_user_ad_issue FOREIGN KEY(user_id) REFERENCES user(id) ON UPDATE CASCADE,
CONSTRAINT FK_ad_campaigns_ad_issue FOREIGN KEY(ad_id) REFERENCES ad_campaigns(id) ON UPDATE CASCADE,
CONSTRAINT FK_user_reward_queue_ad_issue FOREIGN KEY(user_reward_queue_id) REFERENCES user_reward_queue(id) ON UPDATE CASCADE
);"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "SHOW tables FROM ads;"