#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT
npm install --prefix "$tmp" --ignore-scripts --no-audit --no-fund qrcodejs@1.0.0 jsqr@1.4.0
mkdir -p public/assets
cp "$tmp/node_modules/qrcodejs/qrcode.min.js" public/assets/qrcode.min.js
cp "$tmp/node_modules/jsqr/dist/jsQR.js" public/assets/jsQR.js
cp "$tmp/node_modules/qrcodejs/LICENSE" public/assets/LICENSE-qrcodejs.txt
cp "$tmp/node_modules/jsqr/LICENSE" public/assets/LICENSE-jsqr.txt
