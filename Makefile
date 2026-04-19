.PHONY: sw sw-native sw-docker env-status \
	k3d-create k3d-delete k3d-build k3d-push k3d-secrets k3d-deploy \
	k3d-redeploy k3d-up k3d-down k3d-status k3d-logs k3d-shell \
	k8s-deploy-tunnel k8s-deploy-direct

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

# ============================================================================
# k3d (local Kubernetes)
# ============================================================================

K3D_CLUSTER   = pingcrm
K3D_REGISTRY  = k3d-registry.localhost:5111
K3D_IMAGE     = $(K3D_REGISTRY)/pingcrm
K3D_CONFIG    = k8s/k3d-config.yaml
K3D_OVERLAY   = k8s/overlays/local
ENV_FILE      ?= .env.docker

# Créer cluster sur spin-network + registry
k3d-create:
	@echo "Creating k3d cluster..."
	k3d cluster create --config $(K3D_CONFIG)
	@echo "Waiting for cluster to be ready..."
	kubectl wait --for=condition=Ready nodes --all --timeout=120s
	kubectl create namespace pingcrm --dry-run=client -o yaml | kubectl apply -f -
	@echo "✓ Cluster $(K3D_CLUSTER) ready"

# Supprimer cluster
k3d-delete:
	k3d cluster delete $(K3D_CLUSTER)
	@echo "✓ Cluster $(K3D_CLUSTER) deleted"

# Build images app + ssr
k3d-build:
	@echo "Building app image..."
	docker build --target app \
		--build-arg ENV_HASH=$$(sha256sum $(ENV_FILE) | cut -c1-8) \
		--secret id=dotenv,src=$(ENV_FILE) \
		-t $(K3D_IMAGE):latest \
		.
	@echo "Building SSR image..."
	docker build --target ssr \
		--build-arg ENV_HASH=$$(sha256sum $(ENV_FILE) | cut -c1-8) \
		--secret id=dotenv,src=$(ENV_FILE) \
		-t $(K3D_IMAGE):ssr \
		.
	@echo "✓ Images built"

# Push vers registre local k3d
k3d-push:
	docker push $(K3D_IMAGE):latest
	docker push $(K3D_IMAGE):ssr
	@echo "✓ Images pushed to $(K3D_REGISTRY)"

# Créer/maj secrets depuis .env.docker
k3d-secrets:
	@echo "Creating secrets..."
	kubectl create secret generic app-dotenv \
		--from-file=.env=$(ENV_FILE) \
		--namespace=pingcrm \
		--dry-run=client -o yaml | kubectl apply -f -
	kubectl create secret generic app-env \
		--from-literal=DB_DATABASE=$$(grep '^DB_DATABASE=' $(ENV_FILE) | cut -d= -f2 | tr -d '"') \
		--from-literal=DB_USERNAME=$$(grep '^DB_USERNAME=' $(ENV_FILE) | cut -d= -f2 | tr -d '"') \
		--from-literal=DB_PASSWORD=$$(grep '^DB_PASSWORD=' $(ENV_FILE) | cut -d= -f2 | tr -d '"') \
		--from-literal=REDIS_PASSWORD=$$(grep '^REDIS_PASSWORD=' $(ENV_FILE) | cut -d= -f2 | tr -d '"') \
		--from-literal=TYPESENSE_API_KEY=$$(grep '^TYPESENSE_API_KEY=' $(ENV_FILE) | cut -d= -f2 | tr -d '"') \
		--namespace=pingcrm \
		--dry-run=client -o yaml | kubectl apply -f -
	@echo "✓ Secrets created"

# Appliquer Kustomize overlay local
k3d-deploy:
	kubectl apply -k $(K3D_OVERLAY)
	@echo "✓ Manifests applied"
	@echo "Waiting for deployments..."
	kubectl rollout status deployment/app -n pingcrm --timeout=300s
	kubectl rollout status deployment/ssr -n pingcrm --timeout=120s

# Build + push + restart complet
k3d-redeploy: k3d-build k3d-push
	kubectl rollout restart deployment/app -n pingcrm
	kubectl rollout restart deployment/ssr -n pingcrm
	kubectl rollout restart deployment/horizon -n pingcrm
	kubectl rollout restart deployment/scheduler -n pingcrm
	kubectl rollout restart deployment/reverb -n pingcrm
	@echo "Waiting for rollout..."
	kubectl rollout status deployment/app -n pingcrm --timeout=300s
	kubectl rollout status deployment/ssr -n pingcrm --timeout=120s
	@echo "✓ Redeployed"

# Pipeline complet (create → build → push → secrets → deploy)
k3d-up: k3d-create k3d-build k3d-push k3d-secrets k3d-deploy
	@echo "✓ k3d stack up and running"
	@echo "  https://pingcrm-react-inertia-laravel.dev.localhost"

# Teardown complet
k3d-down:
	k3d cluster delete $(K3D_CLUSTER)
	@echo "✓ k3d stack torn down"

# État cluster et pods
k3d-status:
	@echo "=== Cluster ==="
	@k3d cluster list 2>/dev/null || echo "No k3d clusters"
	@echo ""
	@echo "=== Pods ==="
	@kubectl get pods -n pingcrm -o wide 2>/dev/null || echo "No pods (cluster not running?)"
	@echo ""
	@echo "=== Services ==="
	@kubectl get svc -n pingcrm 2>/dev/null || true
	@echo ""
	@echo "=== Ingress ==="
	@kubectl get ingress -n pingcrm 2>/dev/null || true

# Logs d'un deployment (usage: make k3d-logs SVC=app)
SVC ?= app
k3d-logs:
	kubectl logs -n pingcrm -l app=$(SVC) --tail=100 -f

# Shell dans app pod
k3d-shell:
	kubectl exec -it -n pingcrm deployment/app -- sh

# ============================================================================
# Production Kubernetes
# ============================================================================

# Deploy production-tunnel
k8s-deploy-tunnel:
	kubectl apply -k k8s/overlays/production-tunnel

# Deploy production-direct
k8s-deploy-direct:
	kubectl apply -k k8s/overlays/production-direct
