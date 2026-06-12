#!/usr/bin/env python3
"""Generate preview JPEG from a RAF file."""

import argparse
import sys

from raw_common import error_exit, parse_params, process_raw, save_jpeg


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument('--path', required=True)
    parser.add_argument('--out', required=True)
    parser.add_argument('--params', default=None)
    parser.add_argument('--max-width', default='0')
    parser.add_argument('--quality', default='90')
    args = parser.parse_args()

    try:
        params = parse_params(args.params)
        max_width = int(args.max_width)
        max_width = max_width if max_width > 0 else None
        quality = max(1, min(100, int(args.quality)))
        rgb = process_raw(args.path, params, max_width=max_width)
        save_jpeg(rgb, args.out, quality=quality)
    except Exception as exc:
        error_exit(str(exc))

    sys.exit(0)


if __name__ == '__main__':
    main()
