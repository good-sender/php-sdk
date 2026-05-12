#!/usr/bin/env bash
# Single-language conformance driver for good-sender/php-sdk.
#
# Usage:
#   tests/run.sh            # mock smoke against Prism (6 methods)
#   tests/run.sh mock       # same as default
#   tests/run.sh dev        # 17-scenario conformance against the real API
#                           # (requires tests/.env.dev — see .env.example)

set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SDK_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

SPEC="${SPEC:-$SDK_DIR/openapi/goodsender.yaml}"
[[ -f "$SPEC" ]] || { echo "!! spec not found at $SPEC — set SPEC env var" >&2; exit 1; }

MODE="${1:-mock}"
PORT="${PRISM_PORT:-4010}"
ENV_FILE="$SCRIPT_DIR/.env.dev"

PRISM_PID=""; PRISM_LOG=""
cleanup() {
  if [[ -n "$PRISM_PID" ]] && kill -0 "$PRISM_PID" 2>/dev/null; then
    kill "$PRISM_PID" 2>/dev/null || true
    wait "$PRISM_PID" 2>/dev/null || true
  fi
  [[ -n "$PRISM_LOG" && -f "$PRISM_LOG" ]] && rm -f "$PRISM_LOG"
}
trap cleanup EXIT

start_prism() {
  PRISM_LOG="$(mktemp -t prism.XXXXXX.log)"
  echo ">> starting prism mock at http://localhost:$PORT"
  (npx --yes -p @stoplight/prism-cli@5 prism mock -p "$PORT" -h 127.0.0.1 "$SPEC" >"$PRISM_LOG" 2>&1) &
  PRISM_PID=$!
  for _ in $(seq 1 40); do
    curl -fsS -o /dev/null -H "Authorization: Bearer probe" "http://localhost:$PORT/v1/domains" 2>/dev/null && { echo ">> prism is ready"; return 0; }
    sleep 0.5
  done
  echo "!! prism failed to start; log:"; cat "$PRISM_LOG"; exit 1
}

build_sdk() { :; }

install_runner() {
  if [[ ! -d "$SCRIPT_DIR/vendor" ]]; then
    (cd "$SCRIPT_DIR" && composer install --quiet --no-interaction) || exit 1
  fi
}

case "$MODE" in
  mock)
    export BASE_URL="http://localhost:$PORT"
    export GOODSENDER_API_KEY="${GOODSENDER_API_KEY:-test-key}"
    export ALLOW_DESTRUCTIVE="${ALLOW_DESTRUCTIVE:-1}"  # mock is side-effect-free
    start_prism
    build_sdk
    install_runner
    (cd "$SCRIPT_DIR" && php runner.php)
    ;;
  dev)
    [[ -f "$ENV_FILE" ]] || { echo "!! $ENV_FILE not found — copy from .env.example"; exit 1; }
    while IFS= read -r line || [[ -n "$line" ]]; do
      [[ "$line" =~ ^[[:space:]]*# ]] && continue
      [[ "$line" =~ ^[[:space:]]*$ ]] && continue
      if [[ "$line" =~ ^[[:space:]]*([A-Za-z_][A-Za-z0-9_]*)[[:space:]]*=(.*)$ ]]; then
        key="${BASH_REMATCH[1]}"; val="${BASH_REMATCH[2]}"
        val="${val%"${val##*[![:space:]]}"}"
        [[ "$val" =~ ^\"(.*)\"$ ]] && val="${BASH_REMATCH[1]}"
        [[ "$val" =~ ^\'(.*)\'$ ]] && val="${BASH_REMATCH[1]}"
        [[ -z "${!key:-}" ]] && export "$key=$val"
      fi
    done < "$ENV_FILE"
    : "${BASE_URL:=https://api.dev.goodsender.com}"; export BASE_URL
    build_sdk
    install_runner
    (cd "$SCRIPT_DIR" && php conformance.php)
    ;;
  *) echo "!! unknown mode: $MODE (expected: mock or dev)" >&2; exit 1 ;;
esac
