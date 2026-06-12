#!/usr/bin/env bash
# Install dnglab for RAF -> DNG conversion.
# Release binaries may lag new cameras (e.g. X-T30 III); build from main when needed.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
BIN="${APP_DIR}/bin/dnglab"
VERSION="0.7.2"
ARCH="$(uname -m)"

if [ -x "${BIN}" ]; then
	echo "dnglab already installed at ${BIN}"
	exit 0
fi

if command -v dnglab >/dev/null 2>&1; then
	echo "system dnglab found: $(command -v dnglab)"
	exit 0
fi

mkdir -p "${APP_DIR}/bin"

install_release_binary() {
	case "${ARCH}" in
		x86_64|amd64) ASSET="dnglab_linux_x64" ;;
		aarch64|arm64) ASSET="dnglab_linux_aarch64" ;;
		*)
			echo "Unsupported architecture: ${ARCH}" >&2
			return 1
			;;
	esac
	local TMP
	TMP="$(mktemp)"
	trap 'rm -f "${TMP}"' RETURN
	local URL="https://github.com/dnglab/dnglab/releases/download/v${VERSION}/${ASSET}"
	echo "==> Downloading dnglab v${VERSION} (${ASSET})"
	curl -fsSL "${URL}" -o "${TMP}"
	cp "${TMP}" "${BIN}"
	chmod +x "${BIN}"
}

install_from_source() {
	echo "==> Building dnglab from main (newer camera support)"
	if ! command -v cargo >/dev/null 2>&1; then
		echo "==> Installing Rust toolchain"
		apt-get update -qq
		apt-get install -y -qq cargo rustc pkg-config libssl-dev 2>/dev/null || true
	fi
	if ! command -v cargo >/dev/null 2>&1; then
		echo "cargo not available; cannot build dnglab from source" >&2
		return 1
	fi
	local CARGO_ROOT="${APP_DIR}/.dnglab-cargo"
	mkdir -p "${CARGO_ROOT}/bin"
	CARGO_HOME="${APP_DIR}/.cargo-home" \
		cargo install --git https://github.com/dnglab/dnglab.git --branch main --root "${CARGO_ROOT}" dnglab
	cp "${CARGO_ROOT}/bin/dnglab" "${BIN}"
	chmod +x "${BIN}"
}

if install_from_source; then
	echo "Installed ${BIN} (built from main)"
elif install_release_binary; then
	echo "Installed ${BIN} (release v${VERSION})"
else
	echo "Failed to install dnglab" >&2
	exit 1
fi
