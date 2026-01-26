#!/bin/bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Minimum versions
MIN_PHP_VERSION="8.2"
MIN_NODE_VERSION="18"

echo -e "${BLUE}"
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║           CEFET-RJ Seminários - Update Script                 ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Function to compare versions
version_gte() {
    [ "$(printf '%s\n' "$1" "$2" | sort -V | head -n1)" = "$2" ]
}

# Function to print status
print_status() {
    if [ "$2" = "ok" ]; then
        echo -e "  ${GREEN}✓${NC} $1"
    elif [ "$2" = "warn" ]; then
        echo -e "  ${YELLOW}!${NC} $1"
    else
        echo -e "  ${RED}✗${NC} $1"
    fi
}

# Function to print section header
print_section() {
    echo ""
    echo -e "${BLUE}▸ $1${NC}"
    echo ""
}

#######################################
# STEP 1: Verify Dependencies
#######################################
print_section "Checking dependencies"

DEPS_OK=true

# Check Git
if command -v git &> /dev/null; then
    GIT_VERSION=$(git --version | awk '{print $3}')
    print_status "Git installed (v$GIT_VERSION)" "ok"
else
    print_status "Git is not installed" "error"
    DEPS_OK=false
fi

# Check PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    if version_gte "$PHP_VERSION" "$MIN_PHP_VERSION"; then
        print_status "PHP installed (v$PHP_VERSION >= $MIN_PHP_VERSION)" "ok"
    else
        print_status "PHP version $PHP_VERSION is below minimum $MIN_PHP_VERSION" "error"
        DEPS_OK=false
    fi
else
    print_status "PHP is not installed" "error"
    DEPS_OK=false
fi

# Check Node.js
if command -v node &> /dev/null; then
    NODE_VERSION=$(node -v | sed 's/v//' | cut -d. -f1)
    if [ "$NODE_VERSION" -ge "$MIN_NODE_VERSION" ]; then
        print_status "Node.js installed (v$(node -v | sed 's/v//') >= $MIN_NODE_VERSION)" "ok"
    else
        print_status "Node.js version $NODE_VERSION is below minimum $MIN_NODE_VERSION" "error"
        DEPS_OK=false
    fi
else
    print_status "Node.js is not installed" "error"
    DEPS_OK=false
fi

# Check pnpm
if command -v pnpm &> /dev/null; then
    PNPM_VERSION=$(pnpm -v)
    print_status "pnpm installed (v$PNPM_VERSION)" "ok"
else
    print_status "pnpm is not installed" "error"
    echo ""
    echo -e "  ${YELLOW}Tip: Install pnpm with: npm install -g pnpm${NC}"
    DEPS_OK=false
fi

# Check Composer
COMPOSER_CMD=""
if command -v composer &> /dev/null; then
    COMPOSER_CMD="composer"
    COMPOSER_VERSION=$(composer --version 2>/dev/null | awk '{print $3}')
    print_status "Composer installed (v$COMPOSER_VERSION)" "ok"
elif [ -f "composer.phar" ]; then
    COMPOSER_CMD="php composer.phar"
    echo "  Updating local composer.phar..."
    php composer.phar self-update --quiet
    COMPOSER_VERSION=$(php composer.phar --version 2>/dev/null | awk '{print $3}')
    print_status "composer.phar found and updated (v$COMPOSER_VERSION)" "ok"
else
    print_status "Composer not found, installing composer.phar..." "warn"
    echo "  Downloading composer.phar..."
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --quiet
    php -r "unlink('composer-setup.php');"
    if [ -f "composer.phar" ]; then
        COMPOSER_CMD="php composer.phar"
        COMPOSER_VERSION=$(php composer.phar --version 2>/dev/null | awk '{print $3}')
        print_status "composer.phar installed (v$COMPOSER_VERSION)" "ok"
    else
        print_status "Failed to install composer.phar" "error"
        DEPS_OK=false
    fi
fi

if [ "$DEPS_OK" = false ]; then
    echo ""
    echo -e "${RED}Error: Some dependencies are missing or have incompatible versions.${NC}"
    echo "Please install the required dependencies and try again."
    exit 1
fi

#######################################
# STEP 2: Check for Updates
#######################################
print_section "Checking for updates"

# Fetch latest tags from remote
echo "  Fetching latest tags from remote..."
git fetch --tags --quiet

# Get current tag (if on a tag) or commit
CURRENT_TAG=$(git describe --tags --exact-match 2>/dev/null || echo "")
CURRENT_COMMIT=$(git rev-parse --short HEAD)

if [ -n "$CURRENT_TAG" ]; then
    print_status "Current version: $CURRENT_TAG" "ok"
else
    print_status "Current commit: $CURRENT_COMMIT (not on a tagged release)" "warn"
fi

# Get latest tag
LATEST_TAG=$(git describe --tags --abbrev=0 $(git rev-list --tags --max-count=1) 2>/dev/null || echo "")

if [ -z "$LATEST_TAG" ]; then
    print_status "No tags found in repository" "warn"
    echo ""
    echo -e "${GREEN}No releases available. Nothing to update.${NC}"
    exit 0
fi

print_status "Latest release: $LATEST_TAG" "ok"

if [ "$CURRENT_TAG" = "$LATEST_TAG" ]; then
    echo ""
    echo -e "${GREEN}Already on the latest release ($LATEST_TAG). Nothing to update.${NC}"
    exit 0
fi

echo ""
echo -e "${YELLOW}New release available: $LATEST_TAG${NC}"

# Show changelog if available
if [ -n "$CURRENT_TAG" ]; then
    echo ""
    echo "Changes since $CURRENT_TAG:"
    git log --oneline "$CURRENT_TAG".."$LATEST_TAG" 2>/dev/null | head -10 || echo "  (unable to show changes)"
fi

UPDATE_TYPE="release"
TARGET_TAG="$LATEST_TAG"

#######################################
# STEP 3: Enable Maintenance Mode
#######################################
print_section "Enabling maintenance mode"

php artisan down --retry=60 --refresh=15 || true
print_status "Maintenance mode enabled" "ok"

#######################################
# STEP 4: Checkout New Version (if release update)
#######################################
if [ "$UPDATE_TYPE" = "release" ]; then
    print_section "Updating to $TARGET_TAG"

    # Check for uncommitted changes
    if ! git diff-index --quiet HEAD --; then
        echo -e "${RED}Error: Uncommitted changes detected. Cannot update.${NC}"
        echo "Please commit or stash your changes before running the update."
        php artisan up
        exit 1
    fi

    git checkout "$TARGET_TAG"
    print_status "Checked out $TARGET_TAG" "ok"
fi

#######################################
# STEP 5: Install Dependencies
#######################################
print_section "Installing dependencies"

echo "  Installing PHP dependencies..."
$COMPOSER_CMD install --no-interaction --prefer-dist --optimize-autoloader --no-dev
print_status "PHP dependencies installed" "ok"

echo "  Installing Node.js dependencies..."
pnpm install --frozen-lockfile
print_status "Node.js dependencies installed" "ok"

#######################################
# STEP 6: Run Migrations
#######################################
print_section "Running database migrations"

php artisan migrate --force
print_status "Migrations completed" "ok"

#######################################
# STEP 7: Build Assets
#######################################
print_section "Building assets"

pnpm run build
print_status "Assets built" "ok"

#######################################
# STEP 8: Clear and Rebuild Caches
#######################################
print_section "Optimizing application"

php artisan config:cache
print_status "Configuration cached" "ok"

php artisan route:cache
print_status "Routes cached" "ok"

php artisan view:cache
print_status "Views cached" "ok"

php artisan event:cache
print_status "Events cached" "ok"

#######################################
# STEP 9: Restart Queue Workers (if running)
#######################################
print_section "Restarting queue workers"

php artisan queue:restart
print_status "Queue restart signal sent" "ok"

#######################################
# STEP 10: Disable Maintenance Mode
#######################################
print_section "Disabling maintenance mode"

php artisan up
print_status "Application is live" "ok"

#######################################
# Done!
#######################################
echo ""
echo -e "${GREEN}╔═══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                    Update completed!                          ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════════════════╝${NC}"
echo ""

if [ "$UPDATE_TYPE" = "release" ]; then
    echo -e "Updated to version: ${GREEN}$TARGET_TAG${NC}"
else
    echo -e "Maintenance update completed on: ${GREEN}$(git describe --tags --always)${NC}"
fi

echo ""
