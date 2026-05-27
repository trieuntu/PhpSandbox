#!/usr/bin/env bash
# =============================================================================
# PHP Sandbox – Ubuntu 22.04 Server Deployment Script
# Run this script on a fresh Ubuntu 22.04 server to pull and start the system.
# =============================================================================
set -e

DOCKER_USER="${DOCKER_USER:-phpsandbox}"
TAG="${TAG:-latest}"
DEPLOY_DIR="${DEPLOY_DIR:-/opt/phpsandbox}"

echo "================================================"
echo "  PHP Sandbox Deployment"
echo "  Docker Hub: ${DOCKER_USER}"
echo "  Deploy to:  ${DEPLOY_DIR}"
echo "================================================"
echo ""

# ── 1. Install Docker (if not present) ────────────────────────────────────────
if ! command -v docker &>/dev/null; then
    echo "[1/5] Installing Docker..."
    curl -fsSL https://get.docker.com | sh
    sudo usermod -aG docker "$USER"
    echo "      Docker installed. NOTE: You may need to re-login for group changes."
else
    echo "[1/5] Docker already installed: $(docker --version)"
fi

# ── 2. Install Docker Compose plugin (if not present) ─────────────────────────
if ! docker compose version &>/dev/null; then
    echo "[2/5] Installing Docker Compose plugin..."
    sudo apt-get install -y docker-compose-plugin
else
    echo "[2/5] Docker Compose already installed: $(docker compose version)"
fi

# ── 3. Create deployment directory ────────────────────────────────────────────
echo "[3/5] Setting up deployment directory at ${DEPLOY_DIR}..."
sudo mkdir -p "${DEPLOY_DIR}"
sudo chown "$USER":"$USER" "${DEPLOY_DIR}"

# Download docker-compose.deploy.yml if not already present
if [ ! -f "${DEPLOY_DIR}/docker-compose.deploy.yml" ]; then
    echo "      Copying docker-compose.deploy.yml..."
    cp "$(dirname "$0")/docker-compose.deploy.yml" "${DEPLOY_DIR}/" 2>/dev/null || \
    echo "      WARNING: Could not copy docker-compose.deploy.yml. Copy it manually."
fi

# Copy mysql init scripts (required for first run)
if [ ! -d "${DEPLOY_DIR}/docker/mysql/init" ]; then
    echo "      Copying MySQL init scripts..."
    mkdir -p "${DEPLOY_DIR}/docker/mysql/init"
    cp "$(dirname "$0")/docker/mysql/init/"*.sql "${DEPLOY_DIR}/docker/mysql/init/" 2>/dev/null || \
    echo "      WARNING: Could not copy MySQL init scripts. Copy them manually."
fi

# ── 4. Create .env file ────────────────────────────────────────────────────────
if [ ! -f "${DEPLOY_DIR}/.env" ]; then
    echo "[4/5] Creating .env from template..."
    cp "$(dirname "$0")/.env.deploy.example" "${DEPLOY_DIR}/.env" 2>/dev/null || true
    echo ""
    echo "      ⚠️  IMPORTANT: Edit ${DEPLOY_DIR}/.env and set your passwords!"
    echo "      Required changes:"
    echo "        DB_PASSWORD=<strong_password>"
    echo "        MYSQL_ROOT_PASSWORD=<strong_password>"
    echo "        REDIS_PASSWORD=<strong_password>"
    echo "        SANDBOX_SECRET=<random_32_chars>"
    echo "        SANDBOX_DB_ADMIN_PASS=<strong_password>"
    echo "        APP_URL=http://<your-server-ip-or-domain>"
    echo "        DOCKER_USER=${DOCKER_USER}"
    echo ""
    read -p "Press ENTER after editing .env to continue..."
else
    echo "[4/5] .env already exists. Skipping."
fi

# ── 5. Pull images and start ───────────────────────────────────────────────────
echo "[5/5] Pulling images and starting services..."
cd "${DEPLOY_DIR}"

DOCKER_USER="${DOCKER_USER}" TAG="${TAG}" docker compose \
    -f docker-compose.deploy.yml \
    --env-file .env \
    pull

DOCKER_USER="${DOCKER_USER}" TAG="${TAG}" docker compose \
    -f docker-compose.deploy.yml \
    --env-file .env \
    up -d

echo ""
echo "✅  Deployment complete!"
echo ""
echo "Services:"
docker compose -f "${DEPLOY_DIR}/docker-compose.deploy.yml" --env-file "${DEPLOY_DIR}/.env" ps
echo ""
echo "Application URL: $(grep APP_URL ${DEPLOY_DIR}/.env | cut -d= -f2)"
echo ""
echo "Useful commands:"
echo "  View logs:    docker compose -f ${DEPLOY_DIR}/docker-compose.deploy.yml logs -f"
echo "  Stop:         docker compose -f ${DEPLOY_DIR}/docker-compose.deploy.yml down"
echo "  Update:       docker compose -f ${DEPLOY_DIR}/docker-compose.deploy.yml pull && docker compose -f ${DEPLOY_DIR}/docker-compose.deploy.yml up -d"
