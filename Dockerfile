FROM wyveo/nginx-php-fpm:php72

RUN apt-get update -y
RUN apt-get install -y dmtx-utils

WORKDIR /var/www/html

COPY composer.json /usr/share/nginx/html
COPY composer.lock /usr/share/nginx/html

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer global require hirak/prestissimo

COPY . .
