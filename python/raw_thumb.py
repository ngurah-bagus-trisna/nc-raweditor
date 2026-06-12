#!/usr/bin/env python3
"""Extract embedded thumbnail from a RAF file."""

import argparse
import sys

from raw_common import error_exit, extract_thumbnail


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument('--path', required=True)
    parser.add_argument('--out', required=True)
    args = parser.parse_args()

    try:
        extract_thumbnail(args.path, args.out)
    except Exception as exc:
        error_exit(str(exc))

    sys.exit(0)


if __name__ == '__main__':
    main()
