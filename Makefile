.PHONY: go

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
