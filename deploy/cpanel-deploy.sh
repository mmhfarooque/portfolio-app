#!/bin/bash

#==============================================================================
# Laravel Portfolio - cPanel Deployment Script
#==============================================================================
# This script deploys the Laravel application to a cPanel shared hosting
# environment using a split structure:
#
#   /home/user/portfolio-app/    <- Laravel application files
#   /home/user/public_html/      <- Public files only
#
# Usage:
#   First time:  ./cpanel-deploy.sh --install
#   Updates:     ./cpanel-deploy.sh --update
#   Full reset:  ./cpanel-deploy.sh --fresh
#
#==============================================================================

set -e

# Configuration - EDIT THESE VALUES
GIT_REPO="https://github.com/mmhfarooque/portfolio-app.git"
GIT_BRANCH="main"
APP_NAME="portfolio-app"

# Auto-detect paths (assumes script is run from user's home directory)
HOME_DIR="$HOME"
APP_DIR="$HOME_DIR/$APP_NAME"
PUBLIC_HTML="$HOME_DIR/public_html"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_header() {
    echo -e "${BLUE}================================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}================================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

check_requirements() {
    print_header "Checking Requirements"

    # Check PHP
    if command -v php &> /dev/null; then
        PHP_VERSION=$(php -r "echo PHP_VERSION;")
        print_success "PHP $PHP_VERSION found"
    else
        print_error "PHP not found"
        exit 1
    fi

    # Check PHP version >= 8.1
    PHP_MAJOR=$(php -r "echo PHP_MAJOR_VERSION;")
    PHP_MINOR=$(php -r "echo PHP_MINOR_VERSION;")
    if [ "$PHP_MAJOR" -lt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 1 ]); then
        print_error "PHP 8.1 or higher required (found $PHP_VERSION)"
        exit 1
    fi

    # Check Git
    if command -v git &> /dev/null; then
        print_success "Git found"
    else
        print_error "Git not found"
        exit 1
    fi

    # Check Composer
    if command -v composer &> /dev/null; then
        print_success "Composer found"
    elif [ -f "$HOME_DIR/composer.phar" ]; then
        print_success "Composer.phar found"
        alias composer="php $HOME_DIR/composer.phar"
    else
        print_warning "Composer not found - will try to download"
    fi

    # Check required PHP extensions
    REQUIRED_EXTENSIONS=("pdo" "mbstring" "tokenizer" "xml" "ctype" "json" "bcmath" "gd")
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -qi "^$ext$"; then
            print_success "PHP extension: $ext"
        else
            print_warning "PHP extension missing: $ext"
        fi
    done
}

install_composer() {
    if ! command -v composer &> /dev/null && [ ! -f "$HOME_DIR/composer.phar" ]; then
        print_header "Installing Composer"
        cd "$HOME_DIR"
        curl -sS https://getcomposer.org/installer | php
        print_success "Composer installed"
    fi
}

clone_repository() {
    print_header "Cloning Repository"

    if [ -d "$APP_DIR" ]; then
        print_warning "Application directory exists. Use --update to pull changes."
        return 1
    fi

    cd "$HOME_DIR"
    git clone --branch "$GIT_BRANCH" "$GIT_REPO" "$APP_NAME"
    print_success "Repository cloned to $APP_DIR"
}

update_repository() {
    print_header "Updating Repository"

    if [ ! -d "$APP_DIR" ]; then
        print_error "Application directory not found. Use --install first."
        exit 1
    fi

    cd "$APP_DIR"
    git fetch origin
    git reset --hard "origin/$GIT_BRANCH"
    print_success "Repository updated"
}

install_dependencies() {
    print_header "Installing Dependencies"

    cd "$APP_DIR"

    if command -v composer &> /dev/null; then
        composer install --no-dev --optimize-autoloader
    else
        php "$HOME_DIR/composer.phar" install --no-dev --optimize-autoloader
    fi

    print_success "Dependencies installed"
}

setup_environment() {
    print_header "Setting Up Environment"

    cd "$APP_DIR"

    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            cp .env.example .env
            print_success "Created .env from .env.example"

            # Generate app key
            php artisan key:generate
            print_success "Application key generated"
        else
            print_error ".env.example not found"
            exit 1
        fi
    else
        print_warning ".env already exists - skipping"
    fi

    echo ""
    print_warning "IMPORTANT: Edit $APP_DIR/.env with your database credentials!"
    echo ""
}

setup_public_html() {
    print_header "Setting Up Public HTML"

    # Backup existing public_html content (except hidden files)
    if [ -d "$PUBLIC_HTML" ] && [ "$(ls -A $PUBLIC_HTML 2>/dev/null)" ]; then
        BACKUP_DIR="$HOME_DIR/public_html_backup_$(date +%Y%m%d_%H%M%S)"
        print_warning "Backing up existing public_html to $BACKUP_DIR"
        mkdir -p "$BACKUP_DIR"
        cp -r "$PUBLIC_HTML"/* "$BACKUP_DIR"/ 2>/dev/null || true
    fi

    # Clear public_html (keep hidden files like .htaccess if they exist)
    find "$PUBLIC_HTML" -mindepth 1 -maxdepth 1 ! -name ".*" -exec rm -rf {} \; 2>/dev/null || true

    # Copy public files to public_html
    cp -r "$APP_DIR/public/"* "$PUBLIC_HTML/"

    # Create app-path.php for the index.php to find the app
    cat > "$PUBLIC_HTML/app-path.php" << EOF
<?php
// Auto-generated by deployment script
// Points to the Laravel application directory
return '$APP_DIR';
EOF

    print_success "Public files deployed to public_html"

    # Create storage symlink in public_html
    if [ -L "$PUBLIC_HTML/storage" ]; then
        rm "$PUBLIC_HTML/storage"
    fi
    ln -s "$APP_DIR/storage/app/public" "$PUBLIC_HTML/storage"
    print_success "Storage symlink created"
}

set_permissions() {
    print_header "Setting Permissions"

    cd "$APP_DIR"

    # Storage and cache directories need to be writable
    chmod -R 775 storage
    chmod -R 775 bootstrap/cache

    # Create required directories if they don't exist
    mkdir -p storage/framework/{sessions,views,cache}
    mkdir -p storage/logs
    mkdir -p storage/app/public

    print_success "Permissions set"
}

run_migrations() {
    print_header "Running Migrations"

    cd "$APP_DIR"
    php artisan migrate --force
    print_success "Migrations completed"
}

run_seeders() {
    print_header "Running Seeders"

    cd "$APP_DIR"
    php artisan db:seed --force
    print_success "Seeders completed"
}

clear_caches() {
    print_header "Clearing Caches"

    cd "$APP_DIR"
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
    php artisan route:clear
    print_success "Caches cleared"
}

optimize_app() {
    print_header "Optimizing Application"

    cd "$APP_DIR"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    print_success "Application optimized"
}

show_help() {
    echo "Laravel Portfolio - cPanel Deployment Script"
    echo ""
    echo "Usage: $0 [option]"
    echo ""
    echo "Options:"
    echo "  --install    First-time installation"
    echo "  --update     Update existing installation"
    echo "  --fresh      Fresh install (removes existing app)"
    echo "  --public     Only update public_html files"
    echo "  --migrate    Run database migrations"
    echo "  --seed       Run database seeders"
    echo "  --optimize   Clear and rebuild caches"
    echo "  --help       Show this help message"
    echo ""
    echo "Before running, edit this script and set:"
    echo "  GIT_REPO     Your Git repository URL"
    echo "  GIT_BRANCH   Branch to deploy (default: main)"
    echo ""
}

# Main script
case "$1" in
    --install)
        check_requirements
        install_composer
        clone_repository
        install_dependencies
        setup_environment
        set_permissions
        setup_public_html
        echo ""
        print_header "Installation Complete!"
        echo ""
        echo "Next steps:"
        echo "1. Edit $APP_DIR/.env with your database credentials"
        echo "2. Run: $0 --migrate"
        echo "3. Run: $0 --seed"
        echo "4. Run: $0 --optimize"
        echo "5. Visit your website!"
        ;;
    --update)
        check_requirements
        update_repository
        install_dependencies
        set_permissions
        setup_public_html
        clear_caches
        run_migrations
        optimize_app
        print_header "Update Complete!"
        ;;
    --fresh)
        print_warning "This will DELETE the existing installation!"
        read -p "Are you sure? (yes/no): " confirm
        if [ "$confirm" = "yes" ]; then
            rm -rf "$APP_DIR"
            check_requirements
            install_composer
            clone_repository
            install_dependencies
            setup_environment
            set_permissions
            setup_public_html
            print_header "Fresh Installation Complete!"
            echo "Next: Edit .env, then run --migrate and --seed"
        else
            echo "Aborted."
        fi
        ;;
    --public)
        setup_public_html
        print_success "Public files updated"
        ;;
    --migrate)
        run_migrations
        ;;
    --seed)
        run_seeders
        ;;
    --optimize)
        clear_caches
        optimize_app
        ;;
    --help|*)
        show_help
        ;;
esac
