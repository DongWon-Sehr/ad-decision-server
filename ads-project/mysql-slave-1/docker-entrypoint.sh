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

echo "Create slave-1 user"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -S /var/lib/mysql/mysql.sock -e "create user 'repl-1'@'172.16.0.%' identified with mysql_native_password"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -S /var/lib/mysql/mysql.sock -e "alter user 'repl-1'@'172.16.0.%' identified by 'repl-1'"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -S /var/lib/mysql/mysql.sock -e "grant replication slave on *.* to 'repl-1'@'172.16.0.%'; FLUSH PRIVILEGES;"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "select user, host FROM mysql.user;"

## get  status
master_log_file=`mysql -uroot -proot -h 172.16.0.12 -S /var/lib/mysql/mysql.sock -e "show master status\G" | grep mysql-bin`
re="[a-z]*-bin.[0-9]*"

if [[ ${master_log_file} =~ $re ]];then
    master_log_file=${BASH_REMATCH[0]}
fi
echo "master_log_file: $master_log_file"

master_log_pos=`mysql -uroot -proot -h 172.16.0.12 -S /var/lib/mysql/mysql.sock -e "show master status\G" | grep Position`

re="[0-9]+"

if [[ ${master_log_pos} =~ $re ]];then
    master_log_pos=${BASH_REMATCH[0]}
fi
echo "master_log_pos: $master_log_pos"

query="change master to master_host='172.16.0.12', master_user='repl-1', master_password='repl-1', master_log_file='${master_log_file}', master_log_pos=${master_log_pos}, master_port=3306"

mysql -uroot -proot -S /var/lib/mysql/mysql.sock -e "${query}"
mysql -uroot -proot -S /var/lib/mysql/mysql.sock -e "start slave"
