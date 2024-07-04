Project setup:

1. Create `.docker.env` file with the example structure of `.docker.env.dist`
2. Run `docker-compose --env-file .docker.env up -d`

Test:

1. Exec to php container: `docker exec -it platinium-tech-task-php /bin/bash`
2. Run `bin/console h:f:l` in order to populate main `platinium_tickets` database
3. Run tests: `bin/phpunit`

UI:

Open http://localhost:${NGINX_HTTP_PORT-from .docker.env}/docs