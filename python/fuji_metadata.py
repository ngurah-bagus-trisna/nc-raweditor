#!/usr/bin/env python3
"""Read Fujifilm RAF metadata via ExifTool and map to app params."""

from __future__ import annotations

import argparse
import json
import shutil
import subprocess
import sys

from fuji_params import DEFAULT_PARAMS, metadata_to_params

TAGS = [
    'FilmMode',
    'Saturation',
    'WhiteBalance',
    'ColorTemperature',
    'WhiteBalanceFineTune',
    'DynamicRangeSetting',
    'DevelopmentDynamicRange',
    'ShadowTone',
    'HighlightTone',
    'ColorChromeEffect',
    'ColorChromeFXBlue',
    'Clarity',
    'GrainEffectRoughness',
    'GrainEffectSize',
    'BWAdjustment',
    'BWMagentaGreen',
    'RawExposureBias',
    'DRangePriority',
]


def read_exiftool(path: str) -> dict:
    exiftool = shutil.which('exiftool')
    if not exiftool:
        raise RuntimeError('exiftool not found — install libimage-exiftool-perl')

    cmd = [exiftool, '-json', '-n']
    for tag in TAGS:
        cmd.append(f'-{tag}')
    cmd.append(path)

    result = subprocess.run(cmd, capture_output=True, text=True, check=False)
    if result.returncode != 0:
        raise RuntimeError(result.stderr.strip() or 'exiftool failed')

    data = json.loads(result.stdout)
    if not data:
        return {}
    return data[0]


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument('--path', required=True)
    parser.add_argument('--params-only', action='store_true')
    args = parser.parse_args()

    try:
        meta = read_exiftool(args.path)
        params = metadata_to_params(meta)
        if args.params_only:
            print(json.dumps(params))
        else:
            print(json.dumps({
                'metadata': meta,
                'params': params,
                'defaults': DEFAULT_PARAMS,
            }))
    except Exception as exc:
        print(json.dumps({'error': str(exc)}), file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()
