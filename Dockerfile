FROM php:7.3-buster

WORKDIR /app

RUN apt-get update && apt-get -y install dmtx-utils bash curl git

COPY composer.json composer.lock ./

RUN curl -s https://getcomposer.org/installer | php
RUN ./composer.phar i

COPY . .

CMD ["php", "-S", "0.0.0.0:8080", "-t", "./public"]
