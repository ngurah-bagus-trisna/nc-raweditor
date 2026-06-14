#!/usr/bin/env python3
"""Convert Fujifilm RAF to DNG using dnglab, with 16-bit TIFF fallback."""

from __future__ import annotations

import argparse
import os
import shutil
import subprocess
import sys
from pathlib import Path

import rawpy
import tifffile


def find_dnglab(app_root: Path | None = None) -> str:
    candidates: list[Path] = []
    if app_root is not None:
        candidates.append(app_root / 'bin' / 'dnglab')
        candidates.append(app_root / '.dnglab-cargo' / 'bin' / 'dnglab')
    which = shutil.which('dnglab')
    if which:
        candidates.append(Path(which))
    candidates.append(Path('/usr/bin/dnglab'))

    for path in candidates:
        if path.is_file() and os.access(path, os.X_OK):
            return str(path)
    raise RuntimeError(
        'dnglab not found. Run scripts/install-dnglab.sh on the server.'
    )


def convert_with_dnglab(dnglab: str, input_path: str, output_path: str, *, overwrite: bool) -> None:
    cmd = [dnglab, 'convert']
    if overwrite:
        cmd.append('-f')
    cmd.extend([input_path, output_path])
    result = subprocess.run(cmd, capture_output=True, text=True, check=False)
    if result.returncode != 0:
        msg = (result.stderr or result.stdout or 'dnglab convert failed').strip()
        raise RuntimeError(msg)
    if not Path(output_path).is_file():
        raise RuntimeError('dnglab did not produce output file')


def convert_to_tiff_fallback(input_path: str, output_path: str) -> None:
    """16-bit TIFF fallback when DNG is not supported for this camera."""
    with rawpy.imread(input_path) as raw:
        rgb = raw.postprocess(use_camera_wb=True, output_bps=16)
    tifffile.imwrite(output_path, rgb, photometric='rgb', compression='zlib')


def convert_raf(
    input_path: str,
    output_path: str,
    *,
    overwrite: bool = False,
    app_root: str | None = None,
    allow_tiff_fallback: bool = False,
) -> str:
    out = Path(output_path)
    if out.suffix.lower() != '.dng':
        output_path = str(out.with_suffix('.dng'))

    try:
        dnglab = find_dnglab(Path(app_root) if app_root else None)
        convert_with_dnglab(dnglab, input_path, output_path, overwrite=overwrite)
        return output_path
    except RuntimeError as exc:
        if not allow_tiff_fallback:
            raise
        msg = str(exc).lower()
        unsupported = 'unknown camera' in msg or 'unsupported' in msg
        if not unsupported:
            raise
        tiff_path = str(Path(output_path).with_suffix('.tif'))
        convert_to_tiff_fallback(input_path, tiff_path)
        return tiff_path


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument('--path', required=True)
    parser.add_argument('--out', required=True)
    parser.add_argument('--overwrite', action='store_true')
    parser.add_argument('--app-root', default=None)
    parser.add_argument('--no-tiff-fallback', action='store_true')
    args = parser.parse_args()

    try:
        result = convert_raf(
            args.path,
            args.out,
            overwrite=args.overwrite,
            app_root=args.app_root,
            allow_tiff_fallback=not args.no_tiff_fallback,
        )
        print(result)
    except Exception as exc:
        print(str(exc), file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()
