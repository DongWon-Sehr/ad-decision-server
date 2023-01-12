#!/bin/bash

__mysql_config() {
# Hack to get MySQL up and running... I need to look into it more.
echo ""
echo "Running the mysql_config function."
yum -y erase mysql mysql-server
rm -rf /var/lib/mysql/ /etc/my.cnf
yum -y install mysql-server

echo "[mysqld]" | tee -a /etc/my.cnf
echo "max_allowed_packet=16M" | tee -a /etc/my.cnf
echo "log_bin_trust_function_creators=1" | tee -a /etc/my.cnf
echo "lower_case_table_names=1" | tee -a /etc/my.cnf
echo "secure-file-priv=\"/var/lib/mysql-files/\"" | tee -a /etc/my.cnf # for DATA LOAD csv file

mysqld --initialize
# mysqld --defaults-file=/etc/my.cnf --initialize-insecure
chown -R mysql:mysql /var/lib/mysql
systemctl set-environment MYSQLD_OPTS="--skip-grant-tables"
systemctl start mysqld
echo "sleep 1 seconds ..."
sleep 1
echo ""
}

# Call all functions
__mysql_config
sh /__start_mysql.sh
