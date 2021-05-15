# The PHP_EXTENSIONS ARG will apply to the "slim" image
ARG PHP_EXTENSIONS="apcu bcmath gd mysqli opcache pdo_mysql soap zip"
ARG PHP_VERSION=7.4

FROM thecodingmachine/php:$PHP_VERSION-v4-apache-node14

USER root

RUN apt-get update \
 && apt-get install -y mysql-client libgtk2.0-0 libgtk-3-0 libgbm-dev libnotify-dev libgconf-2-4 libnss3 libxss1 libasound2 libxtst6 xauth xvfb --no-install-recommends

USER docker

ENV STARTUP_COMMAND_1='[ -z "$GIT_USER_NAME" ] || git config --global user.name "$GIT_USER_NAME"' \
    STARTUP_COMMAND_2='[ -z "$GIT_USER_EMAIL" ] || git config --global user.email "$GIT_USER_EMAIL"' \
    STARTUP_COMMAND_3='composer install' \
    STARTUP_COMMAND_4='npm install'

