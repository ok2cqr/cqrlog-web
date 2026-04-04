#!/bin/sh
set -eu

IMPORT_SOURCE="${DB_IMPORT_SOURCE:-schema.sql}"
SOURCE_FILE="/docker-entrypoint-imports/${IMPORT_SOURCE}"

if [ ! -f "${SOURCE_FILE}" ]; then
    echo "Selected import source '${IMPORT_SOURCE}' was not found." >&2
    exit 1
fi

echo "Importing database dump: ${IMPORT_SOURCE}"
mariadb \
    --protocol=socket \
    -uroot \
    -p"${MARIADB_ROOT_PASSWORD}" \
    "${MARIADB_DATABASE}" < "${SOURCE_FILE}"
