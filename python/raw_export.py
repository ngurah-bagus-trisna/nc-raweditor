#!/usr/bin/env python3
"""Export RAF file to high-quality JPEG."""

import argparse
import sys

from raw_common import error_exit, parse_params, process_raw, save_jpeg


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument('--path', required=True)
    parser.add_argument('--out', required=True)
    parser.add_argument('--quality', default='98')
    parser.add_argument('--params', default=None)
    args = parser.parse_args()

    try:
        params = parse_params(args.params)
        rgb = process_raw(args.path, params)
        quality = max(1, min(100, int(args.quality)))
        save_jpeg(rgb, args.out, quality=quality)
    except Exception as exc:
        error_exit(str(exc))

    sys.exit(0)


if __name__ == '__main__':
    main()
