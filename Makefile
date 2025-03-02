.PHONY: init

init:
	@if [ ! -f .env ]; then \
		cp .env.herd .env; \
		composer install; \
		php artisan key:generate; \
		php artisan migrate --force; \
		php artisan db:seed; \
		if command -v bun >/dev/null 2>&1; then \
			bun install; \
		else \
			npm install --legacy-peer-deps; \
		fi; \
	fi
