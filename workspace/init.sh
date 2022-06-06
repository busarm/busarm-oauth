#!/bin/bash

# exit when any command fails
set -e

# Install dependencies
# TODO Remove if deployment build stage is set up
echo 'Installing dependencies'
composer install

# Perform after install function
echo 'Running start up script'
/var/www/scripts/startup.sh

# Start Supervisor
echo 'Start Supervisor...'
/usr/bin/supervisord -c /etc/supervisord.conf
