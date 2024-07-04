#!/bin/sh
mkdir -p var/cache var/log

setfacl -dR -m u:"www-data":rwX -m u:$(whoami):rwX var
setfacl -R -m u:"www-data":rwX -m u:$(whoami):rwX var

composer install --ignore-platform-reqs --prefer-dist --no-interaction

bin/console cache:pool:clear cache.redis cache_pool

bin/console d:m:m --no-interaction
bin/console d:m:m --no-interaction --env=test

bin/console lexik:jwt:generate-keypair --skip-if-exists

exec "$@";