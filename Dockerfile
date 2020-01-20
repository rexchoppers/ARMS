FROM wyveo/nginx-php-fpm:php72

RUN apt-get update -y
RUN apt-get install -y dmtx-utils

ADD ./default.conf /etc/nginx/conf.d/default.conf

ADD . /usr/share/nginx/html/

WORKDIR /var/www/html

EXPOSE 8080