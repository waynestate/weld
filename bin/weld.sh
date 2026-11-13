#!/bin/sh
# weld.sh: thin pointer for one release (D-087). The local runtime is Parity
# (D-082), and bin/weld is deleted in the next release.
#
# `up`, `down` and `logs` print the Parity commands that replace them and
# exit 2, so a script cannot mistake the pointer for a running app. `test`
# and `doctor` still run here, because Parity cannot run them yet.
#
# Usage: bin/weld.sh <test|doctor>

set -eu

# Run from the repository root, no matter where the caller stands.
cd "$(dirname "$0")/.."

usage() {
    echo "Usage: bin/weld.sh <command>"
    echo ""
    echo "Commands still run here:"
    echo "  test    Run 'composer ci' in the app container."
    echo "  doctor  Run 'php artisan weld:doctor' in the app container."
    echo ""
    echo "Moved to Parity (D-082):"
    echo "  up      parity up"
    echo "  down    parity down"
    echo "  logs    parity logs -f"
}

# One argument at most: the command. Anything else is unknown.
if [ "$#" -gt 1 ]; then
    usage
    exit 2
fi

case "${1:-}" in
    up)
        echo 'weld: "up" moved to Parity (D-082). Run:'
        echo '  parity init --mysql (first time only)'
        echo '  parity up'
        echo '  parity test (smoke test)'
        exit 2
        ;;
    down)
        echo 'weld: "down" moved to Parity (D-082). Run:'
        echo '  parity down'
        echo '  (parity down --volumes empties the database)'
        exit 2
        ;;
    logs)
        echo 'weld: "logs" moved to Parity (D-082). Run:'
        echo '  parity logs -f'
        exit 2
        ;;
    test)
        echo "weld: docker compose run --rm app composer ci"
        docker compose run --rm app composer ci
        ;;
    doctor)
        echo "weld: docker compose run --rm app php artisan weld:doctor"
        docker compose run --rm app php artisan weld:doctor
        ;;
    *)
        usage
        exit 2
        ;;
esac
