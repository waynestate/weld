#!/bin/sh
# weld.sh — run WELD locally on Linux and macOS with one command.
#
# The app and Vite run in Docker; PHP and Node live in the containers, so the
# host only needs Docker. Windows has its own native path: bin/weld.ps1.
#
# Usage: bin/weld.sh <up|down|test|logs>

set -eu

# Run from the repository root, no matter where the caller stands.
cd "$(dirname "$0")/.."

usage() {
    echo "Usage: bin/weld.sh <command>"
    echo ""
    echo "Commands:"
    echo "  up     Copy .env.example to .env if missing, install the PHP and"
    echo "         Node dependencies in the containers, start the app and"
    echo "         Vite, generate the app key if needed, and migrate."
    echo "  down   Stop and remove the containers."
    echo "  test   Run 'composer ci' in the app container."
    echo "  logs   Follow the container logs; Ctrl+C stops."
}

cmd="${1:-}"

case "$cmd" in
    up)
        if [ ! -f .env ]; then
            echo "weld: cp .env.example .env"
            cp .env.example .env
        fi
        echo "weld: docker compose run --rm app composer install"
        docker compose run --rm app composer install
        echo "weld: docker compose run --rm vite npm ci"
        docker compose run --rm vite npm ci
        echo "weld: docker compose up -d"
        docker compose up -d
        if ! grep -q '^APP_KEY=.' .env; then
            echo "weld: docker compose run --rm app php artisan key:generate"
            docker compose run --rm app php artisan key:generate
        fi
        echo "weld: touch database/database.sqlite"
        touch database/database.sqlite
        echo "weld: docker compose run --rm app php artisan migrate"
        docker compose run --rm app php artisan migrate
        echo "weld: done. Open http://localhost:8000"
        ;;
    down)
        echo "weld: docker compose down"
        docker compose down
        ;;
    test)
        echo "weld: docker compose run --rm app composer ci"
        docker compose run --rm app composer ci
        ;;
    logs)
        echo "weld: docker compose logs -f"
        docker compose logs -f
        ;;
    *)
        usage
        exit 2
        ;;
esac
