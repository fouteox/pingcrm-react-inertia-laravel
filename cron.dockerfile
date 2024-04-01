FROM php:8.3-cli-alpine3.19

RUN docker-php-ext-install pdo pdo_mysql

COPY crontab /etc/crontabs/root

CMD ["crond", "-f"]
