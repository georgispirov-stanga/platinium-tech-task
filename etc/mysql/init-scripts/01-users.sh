#!/bin/sh

set -e

mysql -uroot -p${MYSQL_ROOT_PASSWORD} <<- EOSQL
    CREATE USER '${MYSQL_ADMIN_USER}'@'%' IDENTIFIED BY '${MYSQL_ADMIN_PASS}';
    GRANT ALL PRIVILEGES ON *.* TO '${MYSQL_ADMIN_USER}'@'%';

    CREATE USER '${MYSQL_WEB_USER}'@'%' IDENTIFIED BY '${MYSQL_WEB_PASS}';
    GRANT SELECT, INSERT, UPDATE, DELETE ON *.* TO '${MYSQL_WEB_USER}'@'%';
    FLUSH PRIVILEGES;
EOSQL