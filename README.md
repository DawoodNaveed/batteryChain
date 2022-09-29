# BatteryChain
# Symfony 5.4.9 - Docker containers

```
git clone git@github.com:4artechnologies-ag/battery-chain-web.git

cd battery-chain-web

touch .env
copy parameters from .env.copy into .env and update values
You can get values from Jenkins Job

cd deploy

copy docker-compose.yml.dist as docker-compose.yml

copy .env-sample as .env and update requirement parameters

docker-compose up
```

### Install Composer (not required for setup, it is added in scripts/initialize-system.sh)
### Run Fixture for Super Admin (not required for setup, it is added in scripts/initialize-system.sh)
````
php bin/console doctrine:fixtures:load --append
````
### Any script you want to add at every deployment can be added here.


```
docker exec -it bc_web sh
composer install
php bin/console assets:install

for migrations, use below command after ssh to the web container

php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate

and push changes, docker compose will automatically run migrations and fixtures
```
#ssh to db container

```
docker exec -it bc_mysql sh
```

Maintained by M. Saad Haroon

Requested by Waqas Mehmood
