FROM --platform=linux/amd64 centos:7

# Install Apache
RUN yum -y update
RUN yum -y install httpd httpd-tools

# Install EPEL Repo
RUN rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
RUN rpm -Uvh http://rpms.remirepo.net/enterprise/remi-release-7.rpm

# install tools
RUN yum -y install htop mailx tmux vim lynx sshfs wget finger ntp ntpdate git unzip

# install php
RUN yum --enablerepo=remi-php73 -y install php php-bcmath php-cli php-common php-gd php-intl php-ldap php-mbstring \
    php-mysqlnd php-pear php-soap php-xml php-xmlrpc php-zip php-redis

# install supervisord
RUN yum install -y python-pip && pip install
RUN pip install supervisor

# install sshd
RUN yum install -y openssh-server openssh-clients passwd

RUN ssh-keygen -q -N "" -t dsa -f /etc/ssh/ssh_host_dsa_key && ssh-keygen -q -N "" -t rsa -f /etc/ssh/ssh_host_rsa_key 
RUN sed -ri 's/UsePAM yes/UsePAM no/g' /etc/ssh/sshd_config && echo 'root:changeme' | chpasswd

#install composer
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

# Update Apache Configuration
RUN search="Options Indexes FollowSymLinks MultiViews"; ret="$(sed -n "/<Directory \/>/,/<\/Directory>/s/$search/$search/p" /etc/httpd/conf/httpd.conf)"; if [ "$ret" != "$search" ]; then sed -E -i -e "/<Directory \/>/a\    $search" /etc/httpd/conf/httpd.conf; fi
RUN search="Header set Access-Control-Allow-Origin"; ret="$(sed -n "/<Directory \/>/,/<\/Directory>/s/$search/$search/p" /etc/httpd/conf/httpd.conf)"; if [ "$ret" == "$search" ]; then sed -E -i -e "/<Directory \/>/,/<\/Directory>/s/$search.*/$search \"\*\"/i" /etc/httpd/conf/httpd.conf; else sed -E -i -e "/<Directory \/>/a\    $search \"\*\"" /etc/httpd/conf/httpd.conf; fi
RUN sed -E -i -e '/<Directory \/>/,/<\/Directory>/s/AllowOverride None/AllowOverride All/i' /etc/httpd/conf/httpd.conf
RUN sed -E -i -e '/<Directory \/>/,/<\/Directory>/s/Require all denied/Require all granted/i' /etc/httpd/conf/httpd.conf
RUN sed -E -i -e '/<Directory "\/var\/www\/html">/,/<\/Directory>/s/AllowOverride None/AllowOverride All/' /etc/httpd/conf/httpd.conf
RUN sed -E -i -e 's/DirectoryIndex (.*)$/DirectoryIndex index.php \1/g' /etc/httpd/conf/httpd.conf

# Update PHP Configuration
RUN sed -i "s/;date.timezone =/date.timezone = Asia\/Seoul/gi" /etc/php.ini
RUN sed -i "s/error_reporting = E_ALL \& ~E_DEPRECATED \& ~E_STRICT/error_reporting = E_ALL \& ~E_NOTICE/gi" /etc/php.ini
RUN sed -i "s/display_errors = Off/display_errors = On/gi" /etc/php.ini
RUN sed -i "s/display_startup_errors = Off/display_startup_errors = On/gi" /etc/php.ini
RUN sed -i "s/short_open_tag = Off/short_open_tag = On/gi" /etc/php.ini
RUN sed -i "s/register_argc_argv = Off/register_argc_argv = On/gi" /etc/php.ini
RUN sed -i 's/;include_path = ".:\/php\/includes"/include_path = ".:..:\/pub\/development:\/php\/includes"/gi' /etc/php.ini

# Add /etc/httpd/conf.d/vhosts.conf
RUN set -x \
    && { \
    echo "<Directory />"; \
    echo "  Options Indexes FollowSymLinks MultiViews"; \
    echo "  AllowOverride All"; \
    echo "  Require all granted"; \
    echo "</Directory>"; \
    echo '<VirtualHost *:80>'; \
    echo '        DocumentRoot "/pub/development"'; \
    echo '</VirtualHost>'; \
    } > /etc/httpd/conf.d/vhosts.conf

ADD supervisord.conf /etc/
EXPOSE 80 443

WORKDIR /pub/development

CMD ["supervisord", "-n"]