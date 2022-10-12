FROM php:7.2-alpine3.12

WORKDIR /app

RUN apk add --update libdmtx bash curl

COPY composer.json composer.lock ./

RUN curl -s https://getcomposer.org/installer | php
RUN ./composer.phar i

COPY . .

CMD ["php", "-S", "localhost:8000", "-t", "./public"]
