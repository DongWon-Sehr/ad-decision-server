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
RUN yum search wget
RUN yum install -y wget
# RUN yum install -y mysql-server mysql pwgen supervisor
# RUN yum install -y gcc make gcc-c++


RUN yum -y update; yum clean all
RUN yum -y install epel-release; yum clean all
RUN yum -y install mysql-server mysql pwgen supervisor bash-completion psmisc net-tools vim make; yum clean all


# install tools
# RUN yum -y install htop mailx tmux vim lynx sshfs wget finger ntp ntpdate git unzip

ADD ./start.sh /start.sh
ADD ./config_mysql.sh /config_mysql.sh
ADD ./__start_mysql.sh /__start_mysql.sh
ADD ./supervisord.conf /etc/supervisord.conf

# RUN echo %sudo	ALL=NOPASSWD: ALL >> /etc/sudoers

RUN chmod 755 /start.sh
RUN chmod 755 /config_mysql.sh
RUN /config_mysql.sh

EXPOSE 3306

CMD ["/bin/bash", "/start.sh"]
# CMD ["/sbin/init", "/start.sh"]