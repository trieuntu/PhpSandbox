#!/usr/bin/env bash
set -Eeuo pipefail

# One-shot deploy for source-based Docker Compose setup.
# Usage:
#   ./deploy/one-shot-deploy.sh
#   ./deploy/one-shot-deploy.sh --skip-pull
#   ./deploy/one-shot-deploy.sh --no-migrate

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

COMPOSE_FILE="docker-compose.yml"
ENV_FILE=".env"
SKIP_PULL=0
RUN_MIGRATE=1

for arg in "$@"; do
  case "$arg" in
    --skip-pull)
      SKIP_PULL=1
      ;;
    --no-migrate)
      RUN_MIGRATE=0
      ;;
    *)
      echo "Unknown option: $arg"
      echo "Valid options: --skip-pull, --no-migrate"
      exit 1
      ;;
  esac
done

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "Missing ${ENV_FILE}. Create it before deploying."
  exit 1
fi

if [[ ! -f "${COMPOSE_FILE}" ]]; then
  echo "Missing ${COMPOSE_FILE}."
  exit 1
fi

APP_KEY_LINE="$(grep -E '^APP_KEY=' "${ENV_FILE}" || true)"
if [[ -z "${APP_KEY_LINE}" || "${APP_KEY_LINE}" == "APP_KEY=" ]]; then
  echo "APP_KEY is empty in ${ENV_FILE}."
  echo "Run: docker compose --env-file .env run --rm app php artisan key:generate --show"
  exit 1
fi

if ! command -v docker >/dev/null 2>&1; then
  echo "docker command not found."
  exit 1
fi

DOCKER_CMD=(docker)
if ! docker info >/dev/null 2>&1; then
  if command -v sudo >/dev/null 2>&1 && sudo -n docker info >/dev/null 2>&1; then
    DOCKER_CMD=(sudo docker)
  else
    echo "Cannot access Docker daemon. Run with a user in docker group or use sudo-enabled session."
    exit 1
  fi
fi

compose() {
  ${DOCKER_CMD[@]} compose --env-file "${ENV_FILE}" -f "${COMPOSE_FILE}" "$@"
}

NGINX_HOST_PORT="$(grep -E '^NGINX_HOST_PORT=' "${ENV_FILE}" | cut -d'=' -f2- || true)"
if [[ -z "${NGINX_HOST_PORT}" ]]; then
  NGINX_HOST_PORT="8081"
fi

echo "==> Deploy dir: ${ROOT_DIR}"
echo "==> Compose file: ${COMPOSE_FILE}"
echo "==> NGINX host port: ${NGINX_HOST_PORT}"

if [[ "${SKIP_PULL}" -eq 0 ]]; then
  echo "==> Pull latest source"
  git fetch --all --prune
  git pull --rebase
else
  echo "==> Skip source pull"
fi

echo "==> Build and start core services"
compose up -d --build --force-recreate --no-deps app queue nginx

echo "==> Ensure all services are up"
compose up -d --remove-orphans

echo "==> Fix permissions for Laravel writable dirs"
${DOCKER_CMD[@]} compose exec -T app sh -lc 'mkdir -p storage/logs bootstrap/cache && chown -R 82:82 storage bootstrap/cache && chmod -R ug+rwX storage bootstrap/cache'

echo "==> Clear stale caches"
compose exec -T app php artisan optimize:clear

if [[ "${RUN_MIGRATE}" -eq 1 ]]; then
  echo "==> Run migrations"
  compose exec -T app php artisan migrate --force
else
  echo "==> Skip migrations"
fi

echo "==> Build production caches"
compose exec -T app php artisan config:cache
compose exec -T app php artisan route:cache
compose exec -T app php artisan view:cache

echo "==> Restart edge services"
compose restart app queue nginx

echo "==> Health check"
sleep 3
HTTP_STATUS="$(curl -s -o /dev/null -w '%{http_code}' "http://127.0.0.1:${NGINX_HOST_PORT}" || true)"

echo "==> HTTP status: ${HTTP_STATUS}"
compose ps

if [[ "${HTTP_STATUS}" != "200" && "${HTTP_STATUS}" != "302" ]]; then
  echo "Deployment completed but health check is not OK (expected 200/302)."
  echo "Recent logs:"
  compose logs --tail=80 nginx
  compose logs --tail=80 app
  exit 1
fi

echo "✅ One-shot deploy completed successfully."
