#!/bin/bash

echo "Create a table: buzzvil.ad_campaigns"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e " \
CREATE TABLE buzzvil.ad_campaigns ( \
id INT(11) AUTO_INCREMENT, \
name VARCHAR(64) NOT NULL, \
image_url VARCHAR(128) NOT NULL, \
landing_url VARCHAR(128) NOT NULL, \
weight INT(11) DEFAULT NULL, \
target_country VARCHAR(64) DEFAULT NULL, \
target_gender VARCHAR(64) DEFAULT NULL, \
reward INT(11) DEFAULT NULL, \
PRIMARY KEY(id)
); \
"

echo "Load & insert csv data into buzzvil.ad_campaigns"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e " \
LOAD DATA \
INFILE '/var/lib/mysql-files/ad_campaigns.csv' \
INTO TABLE buzzvil.ad_campaigns \
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '' \
LINES STARTING BY '' TERMINATED BY '\r\n' \
IGNORE 1 LINES; \
"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "SELECT COUNT(*) FROM buzzvil.ad_campaigns;"

echo "Create a table: buzzvil.user"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e " \
CREATE TABLE buzzvil.user ( \
id INT(11) AUTO_INCREMENT, \
name VARCHAR(64) NOT NULL, \
gender VARCHAR(64) NOT NULL, \
country VARCHAR(64) NOT NULL, \
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, \
updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, \
reward INT(11) NOT NULL DEFAULT 0, \
PRIMARY KEY(id) \
); \
"

echo "Insert sample users: buzzvil.user"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e " \
INSERT INTO \
buzzvil.user (name) \
VALUES \
('dongwon', 'M', 'KR'), \
('wanna', 'F', 'US'), \
('join', 'M', 'HK'), \
('buzz', 'F', 'JP'), \
('vill', 'M', 'TW') \
;"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "SELECT * FROM buzzvil.user;"

echo "Create a table: buzzvil.ad_issue"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e " \
CREATE TABLE buzzvil.ad_issue ( \
id VARCHAR(64), \
user_id INT(11), \
ad_id INT(11), \
reward INT(11) NOT NULL, \
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, \
PRIMARY KEY(id, ad_id, user_id),
CONSTRAINT FK_ad_campaigns_ad_issue FOREIGN KEY(ad_id) REFERENCES ad_campaigns(id),
CONSTRAINT FK_user_ad_issue FOREIGN KEY(user_id) REFERENCES user(id)
); \
"

echo "Create a table: buzzvil.reward_history"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e " \
CREATE TABLE buzzvil.reward_history ( \
id INT(11) AUTO_INCREMENT, \
type varchar(64) NOT NULL, \
ad_issue_id VARCHAR(64), \
user_id INT(11), \
ad_id INT(11), \
reward INT(11) NOT NULL, \
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, \
approved_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, \
PRIMARY KEY(id),
CONSTRAINT FK_ad_campaigns_reward_history FOREIGN KEY(ad_id) REFERENCES ad_campaigns(id),
CONSTRAINT FK_user_reward_history FOREIGN KEY(user_id) REFERENCES user(id),
CONSTRAINT FK_ad_issue_reward_history FOREIGN KEY(ad_issue_id) REFERENCES ad_issue(id)
); \
"

mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "SHOW tables FROM buzzvil;"