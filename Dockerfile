# Application container for docket
# Can be used to run a webserver container, or a cli container.
# Docket contains a few maintenace commands that should be periodically
# scheduled with cron.
#
# It is expected that you have already run
#
# ```
# composer install
# npm install
# npm run build
# ```
#
# Before running docker build. Keeping installers out of the container
# makes a much lighter and simpler runtime container.
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

# Copy application code in
COPY . /opt/app
COPY ./docker/run.sh /opt/app/run.sh

# Install composer + php deps
RUN cd /opt/app && composer install --no-dev --no-plugins;

# Build assets with nodejs
RUN cd /opt/app && \
    npm install && \
    npm run build;

# Symlink application webroot to apache document root
RUN ln -s /opt/app/webroot /var/www/html

EXPOSE 5000

CMD "/opt/app/run.sh"
