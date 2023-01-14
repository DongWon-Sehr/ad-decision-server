#!/bin/bash

# set -e

echo ""
echo "start mysqld"
systemctl start mysqld

echo ""
echo "stop mysqld"
systemctl stop mysqld

echo ""
echo "initialize mysqld"
chown -R mysql:mysql /var/lib/mysql
mysqld --initialize
systemctl set-environment MYSQLD_OPTS="--skip-grant-tables"

echo ""
echo "start mysqld"
systemctl start mysqld
systemctl status mysqld

echo ""
echo "Running a preset option"
echo "Create root user"
mysqladmin -u root password $MYSQL_ROOT_PASSWORD
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "CREATE DATABASE $MYSQL_DATABASE"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "create user 'root'@'%' identified by '$MYSQL_ROOT_PASSWORD';"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "grant all privileges on *.* to 'root'@'%' WITH GRANT OPTION; FLUSH PRIVILEGES;"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "select user, host FROM mysql.user;"

echo "Create $MYSQL_USER user"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "create user '$MYSQL_USER'@'%' identified by '$MYSQL_PASSWORD';"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "grant all privileges on $MYSQL_DATABASE.* to '$MYSQL_USER'@'%'; FLUSH PRIVILEGES;"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "select user, host FROM mysql.user;"

echo "Create slave user"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -S /var/lib/mysql/mysql.sock -e "create user 'repl'@'172.16.0.%' identified with mysql_native_password"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -S /var/lib/mysql/mysql.sock -e "alter user 'repl'@'172.16.0.%' identified by 'repl'"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -S /var/lib/mysql/mysql.sock -e "grant replication slave on *.* to 'repl'@'172.16.0.%'; FLUSH PRIVILEGES;"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "select user, host FROM mysql.user;"
