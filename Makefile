# =============================================================================
# PHP Sandbox – Makefile for Docker build/push/deploy
# Usage: make build DOCKER_USER=yourname
#        make push  DOCKER_USER=yourname
# =============================================================================

DOCKER_USER ?= phpsandbox
TAG         ?= latest

APP_IMAGE     = $(DOCKER_USER)/phpsandbox-app:$(TAG)
NGINX_IMAGE   = $(DOCKER_USER)/phpsandbox-nginx:$(TAG)
SANDBOX_IMAGE = $(DOCKER_USER)/phpsandbox-sandbox:$(TAG)

.PHONY: build build-app build-nginx build-sandbox push push-all login deploy clean help

## Build all production images
build: build-app build-nginx build-sandbox
	@echo ""
	@echo "✅  All images built:"
	@echo "    $(APP_IMAGE)"
	@echo "    $(NGINX_IMAGE)"
	@echo "    $(SANDBOX_IMAGE)"

## Build the PHP-FPM app image (bakes in code + composer + npm assets)
build-app:
	@echo "🔨 Building app image: $(APP_IMAGE)"
	docker build \
		--file docker/php/Dockerfile.prod \
		--tag $(APP_IMAGE) \
		--tag $(DOCKER_USER)/phpsandbox-app:latest \
		.

## Build the Nginx image (copies public/ from app image)
build-nginx:
	@echo "🔨 Building nginx image: $(NGINX_IMAGE)"
	docker build \
		--file docker/nginx/Dockerfile.prod \
		--build-arg APP_IMAGE=$(APP_IMAGE) \
		--tag $(NGINX_IMAGE) \
		--tag $(DOCKER_USER)/phpsandbox-nginx:latest \
		.

## Build the sandbox execution service image
build-sandbox:
	@echo "🔨 Building sandbox image: $(SANDBOX_IMAGE)"
	docker build \
		--file docker/sandbox/Dockerfile.prod \
		--tag $(SANDBOX_IMAGE) \
		--tag $(DOCKER_USER)/phpsandbox-sandbox:latest \
		.

## Log in to Docker Hub
login:
	docker login

## Push all images to Docker Hub
push: push-all

push-all: build
	@echo "🚀 Pushing to Docker Hub..."
	docker push $(APP_IMAGE)
	docker push $(DOCKER_USER)/phpsandbox-app:latest
	docker push $(NGINX_IMAGE)
	docker push $(DOCKER_USER)/phpsandbox-nginx:latest
	docker push $(SANDBOX_IMAGE)
	docker push $(DOCKER_USER)/phpsandbox-sandbox:latest
	@echo ""
	@echo "✅  Images pushed to Docker Hub:"
	@echo "    docker pull $(APP_IMAGE)"
	@echo "    docker pull $(NGINX_IMAGE)"
	@echo "    docker pull $(SANDBOX_IMAGE)"

## Remove local built images
clean:
	docker rmi $(APP_IMAGE) $(NGINX_IMAGE) $(SANDBOX_IMAGE) 2>/dev/null || true

## Show help
help:
	@echo "PHP Sandbox Docker Build"
	@echo ""
	@echo "Commands:"
	@echo "  make build          DOCKER_USER=<name>   Build all 3 images"
	@echo "  make push           DOCKER_USER=<name>   Build + push to Docker Hub"
	@echo "  make build-app      DOCKER_USER=<name>   Build app image only"
	@echo "  make build-nginx    DOCKER_USER=<name>   Build nginx image only"
	@echo "  make build-sandbox  DOCKER_USER=<name>   Build sandbox image only"
	@echo "  make login                                Login to Docker Hub"
	@echo "  make clean          DOCKER_USER=<name>   Remove local images"
	@echo ""
	@echo "Variables:"
	@echo "  DOCKER_USER   Docker Hub username (default: phpsandbox)"
	@echo "  TAG           Image tag (default: latest)"
