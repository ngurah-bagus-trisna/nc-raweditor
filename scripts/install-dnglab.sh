#!/usr/bin/env bash
# Install dnglab for RAF -> DNG conversion.
# Pinned to a commit with Fujifilm X-T30 III support (after v0.7.2 release).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
BIN="${APP_DIR}/bin/dnglab"
STAMP="${APP_DIR}/bin/.dnglab-rev"
# Commit adding X-T30 III: https://github.com/dnglab/dnglab/commit/e4b4b89
DNGLAB_REV="e4b4b89eb12dd20f824b3df09495895ea3068dc0"
VERSION="0.7.2"
ARCH="$(uname -m)"

if [ "${FORCE_DNGLAB_REINSTALL:-0}" != "1" ] \
	&& [ -x "${BIN}" ] \
	&& [ -f "${STAMP}" ] \
	&& [ "$(cat "${STAMP}")" = "${DNGLAB_REV}" ]; then
	echo "dnglab already installed at ${BIN} (${DNGLAB_REV})"
	exit 0
fi

mkdir -p "${APP_DIR}/bin"

ensure_cargo() {
	if command -v rustup >/dev/null 2>&1; then
		# shellcheck disable=SC1091
		[ -f "${HOME}/.cargo/env" ] && source "${HOME}/.cargo/env"
		return 0
	fi
	if ! command -v cargo >/dev/null 2>&1; then
		echo "==> Installing Rust toolchain (rustup)"
		apt-get update -qq
		apt-get install -y -qq curl pkg-config libssl-dev build-essential 2>/dev/null || true
		curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs \
			| sh -s -- -y --default-toolchain stable
	fi
	# shellcheck disable=SC1091
	[ -f "${HOME}/.cargo/env" ] && source "${HOME}/.cargo/env"
	if ! command -v cargo >/dev/null 2>&1; then
		echo "cargo not available; cannot build dnglab from source" >&2
		return 1
	fi
}

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
	echo "==> Downloading dnglab v${VERSION} (${ASSET}) — no X-T30 III support"
	curl -fsSL "${URL}" -o "${TMP}"
	cp "${TMP}" "${BIN}"
	chmod +x "${BIN}"
	echo "release-${VERSION}" > "${STAMP}"
}

install_from_source() {
	echo "==> Building dnglab ${DNGLAB_REV} (X-T30 III and newer cameras)"
	ensure_cargo || return 1
	local CARGO_ROOT="${APP_DIR}/.dnglab-cargo"
	mkdir -p "${CARGO_ROOT}/bin"
	CARGO_HOME="${APP_DIR}/.cargo-home" \
		cargo install --git https://github.com/dnglab/dnglab.git \
		--rev "${DNGLAB_REV}" \
		--root "${CARGO_ROOT}" \
		dnglab
	cp "${CARGO_ROOT}/bin/dnglab" "${BIN}"
	chmod +x "${BIN}"
	echo "${DNGLAB_REV}" > "${STAMP}"
}

if install_from_source; then
	echo "Installed ${BIN} (built from ${DNGLAB_REV})"
elif install_release_binary; then
	echo "Installed ${BIN} (release v${VERSION}, limited camera support)"
else
	echo "Failed to install dnglab" >&2
	exit 1
fi
