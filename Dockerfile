# Application container for docket
# Can be used to run a webserver container, or a cli container.
# Docket contains a few maintenace commands that should be periodically
# scheduled with cron.
FROM docker.io/library/php:8.3-apache

RUN apt-get update && apt-get install -y libicu-dev libonig-dev libzip-dev && \
    docker-php-ext-install dom && \
    docker-php-ext-install intl && \
    docker-php-ext-install pdo_pgsql && \
    docker-php-ext-install pdo_mysql && \
    docker-php-ext-install mbstring;

RUN mkdir /opt/docket-app
COPY --exclude=drivers --exclude=flutterapp \
    --exclude=tools --exclude=config/app_local.php . /opt/docket-app;

RUN cd /opt/docket-app && \


RUN ln -s /opt/docket-app/webroot /var/www/html


