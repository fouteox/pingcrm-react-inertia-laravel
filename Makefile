.PHONY: sw sw-native sw-docker env-status

# Switch interactif entre native et docker
sw:
	@if [ -L .env ] && [ "$$(readlink .env)" = ".env.docker" ]; then \
		ln -sf .env.native .env; \
		echo "✓ Switched to NATIVE environment"; \
	else \
		ln -sf .env.docker .env; \
		echo "✓ Switched to DOCKER environment"; \
	fi
	@make env-status

# Switch explicite vers native
sw-native:
	@ln -sf .env.native .env
	@echo "✓ Switched to NATIVE environment"
	@make env-status

# Switch explicite vers docker
sw-docker:
	@ln -sf .env.docker .env
	@echo "✓ Switched to DOCKER environment"
	@make env-status

# Affiche l'environnement actuel
env-status:
	@echo "Current: $$(readlink .env 2>/dev/null || echo '.env (not a symlink)')"
	@echo "DB_HOST: $$(grep '^DB_HOST=' .env | cut -d= -f2)"
	@echo "REDIS_HOST: $$(grep '^REDIS_HOST=' .env | cut -d= -f2)"
