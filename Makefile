.PHONY: sw sw-native sw-docker env-status \
	go

# ============================================================================
# Full check pipeline
# ============================================================================

# Lance TOUT : pint, oxlint, oxfmt, types, tests (Unit + Feature + Arch + Browser)
go:
	@echo "==> Pint (PHP formatter)"
	vendor/bin/pint --format agent
	@echo "==> oxlint"
	vp exec bun run lint
	@echo "==> oxfmt"
	vp exec bun run format
	@echo "==> TypeScript"
	vp exec bun run types
	@echo "==> Pest (Unit + Feature + Arch + Browser)"
	php artisan test --compact
	@echo "✓ All checks passed"

# ============================================================================
# Environment switching
# ============================================================================

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
