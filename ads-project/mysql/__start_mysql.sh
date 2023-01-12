#!/bin/bash

echo ""
echo "start mysqld"
systemctl start mysqld

echo ""
echo "stop mysqld"
systemctl stop mysqld

echo ""
echo "initialize mysqld"
mysqld --initialize
chown -R mysql:mysql /var/lib/mysql
systemctl set-environment MYSQLD_OPTS="--skip-grant-tables"

echo ""
echo "start mysqld"
systemctl start mysqld
systemctl status mysqld

echo ""
echo "Running a preset option"
mysqladmin -u root password $MYSQL_ROOT_PASSWORD
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "CREATE DATABASE $MYSQL_DATABASE"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY '$MYSQL_ROOT_PASSWORD' WITH GRANT OPTION; FLUSH PRIVILEGES;"

mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "create user '$MYSQL_USER'@'%' identified by '$MYSQL_PASSWORD';"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "grant all privileges on $MYSQL_DATABASE.* to '$MYSQL_USER'@'%'; FLUSH PRIVILEGES;"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "select user, host FROM mysql.user;"

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
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e " \
LOAD DATA \
INFILE '/var/lib/mysql-files/ad_campaigns.csv' \
INTO TABLE buzzvil.ad_campaigns \
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '' \
LINES STARTING BY '' TERMINATED BY '\r\n' \
IGNORE 1 LINES; \
"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "SELECT COUNT(*) FROM buzzvil.ad_campaigns;"

echo ""
echo "killall mysqld"
killall mysqld
echo "sleep 1 seconds ..."
sleep 1
echo ""

echo "Start mysqld"
systemctl start mysqld
echo "sleep 1 seconds ..."
sleep 1
echo ""
