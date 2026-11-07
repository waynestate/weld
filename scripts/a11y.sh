#!/bin/sh
# Site accessibility gate (axe-core in Playwright), run in Docker so no
# host browser is needed. Linux and macOS; on Windows use scripts/a11y.ps1,
# or this script from Git Bash (the MSYSTEM block below handles its path
# conversion).
#
# The repo is mounted read-write: Docker Desktop refuses the nested named
# volume that used to hold tools/a11y/node_modules inside the bind mount.
# node_modules is gitignored, so npm installs once and later runs reuse it.
set -eu

cd "$(dirname "$0")/.."

# Git Bash on Windows rewrites absolute-looking arguments for native programs
# (docker would turn -w /work into a Windows path). Turn that off and pass the
# Windows form of the repo path instead.
if [ -n "${MSYSTEM:-}" ]; then
  export MSYS_NO_PATHCONV=1
  REPO="$(pwd -W)"
else
  REPO="$PWD"
fi

IMAGE="mcr.microsoft.com/playwright:v1.49.1-noble"

# Every committed HTML page under site/, relative to site/. The gate checks
# them all in one run, so a new page is covered the moment it is committed.
PAGE_ARGS=""
for PAGE in $(git ls-files 'site/*.html'); do
  PAGE_ARGS="$PAGE_ARGS --page=${PAGE#site/}"
done

docker run --rm \
  -v "${REPO}:/work" \
  -w /work \
  "$IMAGE" \
  sh -c "if [ ! -d tools/a11y/node_modules/playwright ]; then if [ -f tools/a11y/package-lock.json ]; then (cd tools/a11y && npm ci --no-audit --no-fund); else (cd tools/a11y && npm install --no-audit --no-fund); fi; fi && node tools/a11y/check.mjs --root=site${PAGE_ARGS}"
