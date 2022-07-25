#!/bin/bash
set -o errexit -o nounset -o pipefail

initialize_system() {
  echo "Initializing Battery Chain container ..."

  if [ "$APP_ENV" == "dev" ]; then
      echo "Dev Container ..."
      composer global require hirak/prestissimo
      composer install --working-dir=/var/www/html
      php bin/console doctrine:fixtures:load --append
  else
      echo "Prod Container ..."
      composer global require hirak/prestissimo
      composer install --no-dev --optimize-autoloader --working-dir=/var/www/html
  fi

  # Run Migrations
  php bin/console doctrine:migrations:migrate -n --all-or-nothing
}

# Initialize system as nginx user
su nginx -s /bin/sh -c "$(declare -f initialize_system); initialize_system"
