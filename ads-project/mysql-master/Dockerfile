FROM centos:latest

ARG MYSQL_ROOT_PASSWORD="root"
ENV MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD

ARG MYSQL_DATABASE="buzzvil"
ENV MYSQL_DATABASE=$MYSQL_DATABASE

ARG MYSQL_USER="buzzvil"
ENV MYSQL_USER=$MYSQL_USER

ARG MYSQL_PASSWORD="buzzvil"
ENV MYSQL_PASSWORD=$MYSQL_PASSWORD

# change mirror site to vault
RUN sed -i 's/mirrorlist/#mirrorlist/g' /etc/yum.repos.d/CentOS-Linux-*
RUN sed -i 's|#baseurl=http://mirror.centos.org|baseurl=http://vault.centos.org|g' /etc/yum.repos.d/CentOS-Linux-*

# install mysql
RUN yum -y update; yum clean all
RUN yum -y install epel-release; yum clean all
RUN yum -y install mysql-server mysql pwgen supervisor bash-completion psmisc net-tools vim make; yum clean all

# ADD mysql config
RUN echo "[mysqld]" | tee -a /etc/my.cnf
RUN echo "log-bin=mysql-bin" | tee -a /etc/my.cnf
RUN echo "server-id=10" | tee -a /etc/my.cnf

# Add files
ADD ./my.cnf /etc/my.cnf.d/my.cnf
ADD docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
ADD ./__import_ad_campaigns_data.sh /usr/local/bin/__import_ad_campaigns_data.sh
ADD ./supervisord.conf /etc/supervisord.conf
ADD ./start.sh /start.sh

RUN chmod 640 /etc/my.cnf.d/my.cnf
RUN chmod 755 /usr/local/bin/docker-entrypoint.sh
RUN chmod 755 /usr/local/bin/__import_ad_campaigns_data.sh
RUN chmod 755 /start.sh

# RUN sh /usr/local/bin/docker-entrypoint.sh

EXPOSE 3306

CMD ["/bin/bash", "/start.sh"]