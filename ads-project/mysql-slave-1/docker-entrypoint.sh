#!/bin/bash

# set -e

trim() {
    local var=$1
    var="${var#"${var%%[![:space:]]*}"}"   # remove leading whitespace characters
    var="${var%"${var##*[![:space:]]}"}"   # remove trailing whitespace characters
    echo -n "$var"
}

echo ""
echo "Start mysqld"
systemctl start mysqld

echo ""
echo "Stop mysqld"
systemctl stop mysqld

echo ""
echo "Initialize mysqld"
chown -R mysql:mysql /var/lib/mysql
mysqld --initialize
systemctl set-environment MYSQLD_OPTS="--skip-grant-tables"

echo ""
echo "Start mysqld"
systemctl start mysqld
systemctl status mysqld

echo ""
echo "Running a preset option"
echo ""
echo "Create root user"
mysqladmin -u root password $MYSQL_ROOT_PASSWORD
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "CREATE DATABASE $MYSQL_DATABASE"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "create user 'root'@'%' identified by '$MYSQL_ROOT_PASSWORD';"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "grant all privileges on *.* to 'root'@'%' WITH GRANT OPTION; FLUSH PRIVILEGES;"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "select user, host FROM mysql.user;"

echo ""
echo "Create $MYSQL_USER user"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "create user '$MYSQL_USER'@'%' identified by '$MYSQL_PASSWORD';"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "grant all privileges on $MYSQL_DATABASE.* to '$MYSQL_USER'@'%'; FLUSH PRIVILEGES;"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "select user, host FROM mysql.user;"

echo ""
echo "Create repl-1 user (slave-1)"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -S /var/lib/mysql/mysql.sock -e "create user 'repl-1'@'172.16.0.%' identified with mysql_native_password"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -S /var/lib/mysql/mysql.sock -e "alter user 'repl-1'@'172.16.0.%' identified by 'repl-1'"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -S /var/lib/mysql/mysql.sock -e "grant replication slave on *.* to 'repl-1'@'172.16.0.%'; FLUSH PRIVILEGES;"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "select user, host FROM mysql.user;"

## get  status
echo ""
echo "Get master_log_file"
master_log_file=`mysql -uroot -proot -h 172.16.0.12 -S /var/lib/mysql/mysql.sock -e "show master status\G" | grep mysql-bin`
re="[a-z]*-bin.[0-9]*"
if [[ ${master_log_file} =~ $re ]];then
    master_log_file=${BASH_REMATCH[0]}
fi
echo "master_log_file: $master_log_file"

echo ""
echo "Get master_log_pos"
master_log_pos=`mysql -uroot -proot -h 172.16.0.12 -S /var/lib/mysql/mysql.sock -e "show master status\G" | grep Position`
re="[0-9]+"
if [[ ${master_log_pos} =~ $re ]];then
    master_log_pos=${BASH_REMATCH[0]}
fi
echo "master_log_pos: $master_log_pos"

echo ""
echo "Set db replication"
query="change master to master_host='172.16.0.12', master_user='repl-1', master_password='repl-1', master_log_file='${master_log_file}', master_log_pos=${master_log_pos}, master_port=3306"
mysql -uroot -proot -S /var/lib/mysql/mysql.sock -e "${query}"

echo ""
echo "Start slave"
mysql -uroot -proot -S /var/lib/mysql/mysql.sock -e "start slave"

echo ""
echo "Check replication status"
repl_master_log_file=`mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "show slave status \G;" | grep '^\s*Master_Log_File:'`
repl_read_master_log_pos=`mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "show slave status \G;" | grep '^\s*Read_Master_Log_Pos:'`
repl_slave_io_running=`mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "show slave status \G;" | grep '^\s*Slave_IO_Running:'`
repl_slave_sql_running=`mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "show slave status \G;" | grep '^\s*Slave_SQL_Running:'`

echo "$(trim "${repl_master_log_file}")"
echo "$(trim "${repl_read_master_log_pos}")"
echo "$(trim "${repl_slave_io_running}")"
echo "$(trim "${repl_slave_sql_running}")"