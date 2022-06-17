
#!/bin/bash

# exit when any command fails
set -e

echo Create .env file - no overwrite
rsync -a -v --ignore-existing .env.example .env

echo Perform Migration
php /var/www/console migrate
