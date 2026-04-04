#!/bin/sh
set -eu

if [ -n "${BASIC_AUTH_USERNAME:-}" ] && [ -n "${BASIC_AUTH_PASSWORD:-}" ]; then
    htpasswd -bc /etc/apache2/.htpasswd "$BASIC_AUTH_USERNAME" "$BASIC_AUTH_PASSWORD"
    a2enconf basic-auth >/dev/null
else
    rm -f /etc/apache2/.htpasswd
    a2disconf basic-auth >/dev/null 2>&1 || true
fi

exec docker-php-entrypoint "$@"
