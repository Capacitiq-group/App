#!/usr/bin/env sh

set -eu

cd /var/www

# Ensure required writable directories exist
mkdir -p \
	storage/framework/cache/data \
	storage/framework/sessions \
	storage/framework/views \
	storage/logs \
	bootstrap/cache

# Initialize storage volume content (best-effort)
if [ -d "/var/www/storage-init" ]; then
	if [ ! -d "/var/www/storage" ] || [ -z "$(ls -A /var/www/storage 2>/dev/null || true)" ]; then
		echo "[entrypoint] Initializing storage volume..."
		cp -a /var/www/storage-init/. /var/www/storage/ 2>/dev/null || true
	fi
fi

# Ensure perms are writable by the running user (best-effort)
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

# Create the storage symlink for Laravel (best-effort)
if [ ! -e public/storage ]; then
	ln -s /var/www/storage/app/public public/storage 2>/dev/null || true
fi

# Database bootstrap.
#
# Only ONE service in the stack may own this (php-fpm); the worker and reverb
# containers share the same image and would otherwise race each other.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
	echo "[entrypoint] Waiting for the database to accept connections..."
	attempt=0
	until php artisan db:show >/dev/null 2>&1; do
		attempt=$((attempt + 1))
		if [ "$attempt" -ge 40 ]; then
			echo "[entrypoint] WARNING: database still unreachable after $attempt attempts; continuing."
			break
		fi
		sleep 3
	done

	echo "[entrypoint] Running database migrations..."
	if php artisan migrate --force --no-interaction; then
		echo "[entrypoint] Migrations complete."

		if [ "${RUN_SYSTEM_ADMIN:-false}" = "true" ]; then
			echo "[entrypoint] Ensuring system administrator account..."
			php artisan app:ensure-system-admin --no-interaction \
				|| echo "[entrypoint] WARNING: could not create the system administrator account."
		fi
	else
		echo "[entrypoint] ERROR: database migrations failed. The API will return errors until this is resolved."
	fi
fi

exec "$@"
