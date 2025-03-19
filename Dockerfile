# Application container for docket
# Can be used to run a webserver container, or a cli container.
# Docket contains a few maintenace commands that should be periodically
# scheduled with cron.
FROM docker.io/library/php:8.3-fpm

# Install additional extensions and nginx
RUN apt-get update \
  && apt-get install -y libicu72 libicu-dev libzip4 libzip-dev \
    libxml2-dev nginx libonig-dev libpq-dev nodejs npm \
  && docker-php-ext-install dom intl mbstring pdo pdo_mysql pdo_pgsql pcntl zip;

# Copy php.ini in
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Setup nginx site
COPY ./docker/nginx.conf /etc/nginx/sites-available/default

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
  && chmod +x /usr/local/bin/composer;

# Copy application code
COPY . /opt/app

# Enable production mode
ENV DEBUG=false

RUN chmod -R 0777 /opt/app/logs/ \
    && chmod -R 0777 /opt/app/tmp \
    && rm /opt/app/config/app_local.php

# Install composer + php deps
RUN cd /opt/app && composer install --no-dev --no-plugins;

# Build assets with nodejs
RUN cd /opt/app && \
    npm install && \
    npm run build;

# Symlink application webroot to apache document root
RUN rm -r /var/www/html \
    && ln -s /opt/app/webroot/ /var/www/html;

EXPOSE 5000

CMD "/opt/app/run.sh"
