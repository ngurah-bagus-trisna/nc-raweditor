#!/usr/bin/env python3
"""Shared RAW processing utilities for RAW Editor."""

from __future__ import annotations

import json
import sys
from typing import Any

import numpy as np
import rawpy
from PIL import Image, ImageEnhance, ImageFilter

from fuji_params import DEFAULT_PARAMS, merge_params


def parse_params(params_json: str | None) -> dict[str, Any]:
    if not params_json:
        return dict(DEFAULT_PARAMS)
    try:
        data = json.loads(params_json)
        return merge_params(data if isinstance(data, dict) else {})
    except json.JSONDecodeError:
        return dict(DEFAULT_PARAMS)


def is_raf_path(path: str) -> bool:
    return path.lower().endswith('.raf')


def build_rawpy_params(params: dict[str, Any], *, half_size: bool = False) -> rawpy.Params:
    rp = rawpy.Params()
    rp.use_camera_wb = bool(params.get('use_camera_wb', True))
    rp.no_auto_bright = False
    rp.half_size = half_size
    rp.output_bps = 8

    if 'bright' in params:
        rp.bright = float(params['bright'])
    if 'exp_shift' in params:
        rp.exp_shift = float(params['exp_shift'])

    highlight = int(params.get('highlight_tone', params.get('highlight', 0)))
    shadow = int(params.get('shadow_tone', params.get('shadow_shift', 0)))
    dr = str(params.get('dynamic_range', '100'))
    if dr == '200':
        highlight = max(highlight, 50)
    elif dr == '400':
        highlight = max(highlight, 80)

    rp.highlight = highlight
    rp.shadow_shift = shadow

    return rp


def apply_saturation(rgb: np.ndarray, saturation: float) -> np.ndarray:
    if saturation == 1.0:
        return rgb
    image = Image.fromarray(rgb)
    enhancer = ImageEnhance.Color(image)
    return np.asarray(enhancer.enhance(float(saturation)))


def apply_color_slider(rgb: np.ndarray, color: int) -> np.ndarray:
    if color == 0:
        return rgb
    sat = 1.0 + color / 100.0
    return apply_saturation(rgb, max(0.0, sat))


def apply_clarity(rgb: np.ndarray, clarity: int) -> np.ndarray:
    if clarity == 0:
        return rgb
    image = Image.fromarray(rgb)
    amount = abs(clarity) / 100.0
    if clarity > 0:
        blurred = image.filter(ImageFilter.GaussianBlur(radius=2))
        arr = np.asarray(image, dtype=np.float32)
        blur = np.asarray(blurred, dtype=np.float32)
        return np.clip(arr + (arr - blur) * amount * 0.8, 0, 255).astype(np.uint8)
    return np.asarray(image.filter(ImageFilter.GaussianBlur(radius=1)))


def apply_chrome(rgb: np.ndarray, chrome: str, blue: str) -> np.ndarray:
    arr = rgb.astype(np.float32)
    strength = {'off': 0, 'weak': 0.15, 'strong': 0.3}
    s = strength.get(chrome, 0)
    if s > 0:
        luma = arr[..., 0] * 0.299 + arr[..., 1] * 0.587 + arr[..., 2] * 0.114
        sat_mask = 1 - np.clip(np.abs(arr - luma[..., None]) / 128.0, 0, 1)
        arr = arr - sat_mask * s * 20
    bs = strength.get(blue, 0)
    if bs > 0:
        arr[..., 2] = np.clip(arr[..., 2] - bs * 25, 0, 255)
    return np.clip(arr, 0, 255).astype(np.uint8)


def apply_grain(rgb: np.ndarray, grain: str, grain_size: str) -> np.ndarray:
    if grain == 'off':
        return rgb
    strength = 6 if grain == 'weak' else 14
    if grain_size == 'large':
        strength *= 1.5
    noise = np.random.normal(0, strength, rgb.shape).astype(np.float32)
    return np.clip(rgb.astype(np.float32) + noise, 0, 255).astype(np.uint8)


def process_raw(
    path: str,
    params: dict[str, Any] | None = None,
    max_width: int | None = None,
) -> np.ndarray:
    params = merge_params(params)
    use_half = max_width is not None and max_width > 0 and not is_raf_path(path)
    rp = build_rawpy_params(params, half_size=use_half)
    with rawpy.imread(path) as raw:
        rgb = raw.postprocess(rp)

    rgb = apply_color_slider(rgb, int(params.get('color', 0)))
    rgb = apply_saturation(rgb, float(params.get('saturation', 1.0)))
    rgb = apply_chrome(rgb, str(params.get('color_chrome', 'off')), str(params.get('color_chrome_blue', 'off')))
    rgb = apply_clarity(rgb, int(params.get('clarity', 0)))
    rgb = apply_grain(rgb, str(params.get('grain', 'off')), str(params.get('grain_size', 'small')))

    if max_width is not None and max_width > 0:
        rgb = resize_max_width(rgb, max_width)
    return rgb


def resize_max_width(rgb: np.ndarray, max_width: int) -> np.ndarray:
    h, w = rgb.shape[:2]
    if max(w, h) <= max_width:
        return rgb
    scale = max_width / max(w, h)
    new_w = max(1, int(w * scale))
    new_h = max(1, int(h * scale))
    image = Image.fromarray(rgb)
    image = image.resize((new_w, new_h), Image.Resampling.LANCZOS)
    return np.asarray(image)


def save_jpeg(rgb: np.ndarray, out_path: str, quality: int = 98) -> None:
    image = Image.fromarray(rgb)
    image.save(out_path, 'JPEG', quality=quality, subsampling=0, optimize=True)


def extract_thumbnail(path: str, out_path: str) -> None:
    with rawpy.imread(path) as raw:
        try:
            thumb = raw.extract_thumb()
        except (rawpy.LibRawNoThumbnailError, rawpy.LibRawUnsupportedThumbnailError):
            rgb = raw.postprocess(build_rawpy_params({}))
            save_jpeg(rgb, out_path, quality=85)
            return

        if thumb.format == rawpy.ThumbFormat.JPEG:
            with open(out_path, 'wb') as f:
                f.write(thumb.data)
        elif thumb.format == rawpy.ThumbFormat.BITMAP:
            save_jpeg(thumb.data, out_path, quality=85)
        else:
            rgb = raw.postprocess(build_rawpy_params({}))
            save_jpeg(rgb, out_path, quality=85)


def error_exit(message: str) -> None:
    print(message, file=sys.stderr)
    sys.exit(1)
