#workspace
FROM --platform=linux/amd64 centos:7

# Install EPEL Repo
RUN rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
RUN rpm -Uvh http://rpms.remirepo.net/enterprise/remi-release-7.rpm

# install tools
RUN yum -y install htop mailx tmux vim lynx sshfs wget finger ntp ntpdate git unzip

# install php
RUN yum --enablerepo=remi-php73 -y install php php-bcmath php-cli php-common php-gd php-intl php-ldap php-mbstring \
    php-mysqlnd php-pear php-soap php-xml php-xmlrpc php-zip

# Update PHP Configuration
RUN sed -i "s/;date.timezone =/date.timezone = Asia\/Seoul/gi" /etc/php.ini
RUN sed -i "s/error_reporting = E_ALL \& ~E_DEPRECATED \& ~E_STRICT/error_reporting = E_ALL \& ~E_NOTICE/gi" /etc/php.ini
RUN sed -i "s/display_errors = Off/display_errors = On/gi" /etc/php.ini
RUN sed -i "s/display_startup_errors = Off/display_startup_errors = On/gi" /etc/php.ini
RUN sed -i "s/short_open_tag = Off/short_open_tag = On/gi" /etc/php.ini
RUN sed -i "s/register_argc_argv = Off/register_argc_argv = On/gi" /etc/php.ini
RUN sed -i 's/;include_path = ".:\/php\/includes"/include_path = ".:..:\/pub\/development:\/php\/includes"/gi' /etc/php.ini

# Install composer and add its bin to the PATH.
RUN curl -s http://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer
EXPOSE 8080

WORKDIR /var/www
