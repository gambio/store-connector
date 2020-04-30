FROM php:{{phpVersion}}-apache
WORKDIR "/var/www/html"

# Fix debconf warnings upon build
ARG DEBIAN_FRONTEND=noninteractive

# Install selected extensions and other stuff
RUN apt-get update && apt-get install -y --no-install-recommends \
		apt-utils \
		libfreetype6-dev \
		libjpeg62-turbo-dev \
		libpng-dev \
		libxml2-dev

RUN docker-php-ext-install mysqli gettext pdo_mysql gd soap

RUN pecl install xdebug-2.7.0 \
    && docker-php-ext-enable xdebug soap