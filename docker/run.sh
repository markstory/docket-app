#!/bin/bash
echo 'starting php-fpm in background'
php-fpm &

echo 'starting nginx'
nginx -g 'daemon off;'
