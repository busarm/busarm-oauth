#!/bin/bash

# Turn on bash's job control
set -m

# Install dependencies
# TODO Remove if deployment build stage is set up
echo 'Installing dependencies'
composer install

echo 'Create .env file - no overwrite'
rsync -a -v --ignore-existing .env.example .env

echo 'Perform Migration'
php /var/www/console migrate

# Start Supervisor
echo 'Start Supervisor...'
/usr/bin/supervisord -c /etc/supervisord.conf
