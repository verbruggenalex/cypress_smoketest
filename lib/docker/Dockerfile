# The PHP_EXTENSIONS ARG will apply to the "slim" image
ARG PHP_EXTENSIONS="apcu bcmath gd mysqli opcache pdo_mysql soap zip"
ARG PHP_VERSION=7.4

FROM thecodingmachine/php:$PHP_VERSION-v4-apache-node12

USER root

RUN apt-get update \
 && apt-get install -y mysql-client --no-install-recommends

USER docker