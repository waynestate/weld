#!/bin/sh
# weld.sh: run WELD locally on Linux and macOS with one command.
#
# The app and Vite run in Docker; PHP and Node live in the containers, so the
# host only needs Docker. Windows has its own native path: bin/weld.ps1.
#
# Usage: bin/weld.sh <up|down|test|doctor|logs> [--no-advisor]

set -eu

# Run from the repository root, no matter where the caller stands.
cd "$(dirname "$0")/.."

usage() {
    echo "Usage: bin/weld.sh <command>"
    echo ""
    echo "Commands:"
    echo "  up     Copy .env.example to .env if missing, install the PHP and"
    echo "         Node dependencies in the containers, write .mcp.json from"
    echo "         ADVISOR (skip with --no-advisor), start the app and Vite,"
    echo "         generate the app key if needed, and migrate."
    echo "  down   Stop and remove the containers, and clear public/hot."
    echo "  test   Run 'composer ci' in the app container."
    echo "  doctor Run 'php artisan weld:doctor' in the app container."
    echo "  logs   Follow the container logs; Ctrl+C stops."
}

cmd=""
no_advisor=0
for arg in "$@"; do
    case "$arg" in
        --no-advisor) no_advisor=1 ;;
        *) cmd="$arg" ;;
    esac
done

case "$cmd" in
    up)
        if [ ! -f .env ]; then
            echo "weld: cp .env.example .env"
            cp .env.example .env
        fi
        echo "weld: docker compose run --rm app composer install"
        docker compose run --rm app composer install
        if [ "$no_advisor" -eq 0 ]; then
            echo "weld: docker compose run --rm app php vendor/bin/advisor install --client=claude --write"
            docker compose run --rm app php vendor/bin/advisor install --client=claude --write
        fi
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
        # Vite's dev server writes public/hot and does not always get to remove
        # it when its container stops. Left behind, it points every page at a
        # dev server that is gone: blank pages under Parity, or on any server
        # that serves this folder. The cold weld is the one that holds.
        if [ -f public/hot ]; then
            echo "weld: rm public/hot"
            rm -f public/hot
        fi
        ;;
    test)
        echo "weld: docker compose run --rm app composer ci"
        docker compose run --rm app composer ci
        ;;
    doctor)
        echo "weld: docker compose run --rm app php artisan weld:doctor"
        docker compose run --rm app php artisan weld:doctor
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
