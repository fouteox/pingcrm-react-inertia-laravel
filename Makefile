.PHONY: go

# ============================================================================
# Full check pipeline
# ============================================================================

# Formatte puis vérifie le code et les tests PHP/frontend.
go:
	@echo "==> Pint (PHP formatter)"
	vendor/bin/pint --format agent
	@echo "==> oxlint"
	vp exec bun run lint
	@echo "==> oxfmt"
	vp exec bun run format
	@echo "==> TypeScript"
	vp exec bun run types
	@echo "==> Larastan"
	composer analyse
	@echo "==> Knip"
	vp run deadcode
	@echo "==> Frontend unit tests"
	vp run test
	@echo "==> JavaScript security audit"
	vp exec bun audit
	@echo "==> Composer security audit"
	composer audit --locked
	@echo "==> Pest (Unit + Feature + Arch + Browser)"
	php artisan test --compact
	@echo "✓ All checks passed"
