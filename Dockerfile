FROM wyveo/nginx-php-fpm:php72

RUN apt-get update -y
RUN apt-get install -y dmtx-utils

ADD . /usr/share/nginx/html
WORKDIR /usr/share/nginx/html