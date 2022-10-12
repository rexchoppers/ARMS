FROM php:7.4-alpine3.12

WORKDIR /app

RUN apk add --update libdmtx bash curl

COPY composer.json composer.lock ./

RUN curl -s https://getcomposer.org/installer | php
RUN ./composer.phar i

COPY . .

CMD ["php", "-S", "0.0.0.0:8080", "-t", "./public"]
