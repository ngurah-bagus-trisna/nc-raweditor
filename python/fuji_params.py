#!/usr/bin/env python3
"""Fujifilm RAF settings mapping and defaults."""

from __future__ import annotations

from typing import Any

DEFAULT_PARAMS: dict[str, Any] = {
    'wb_mode': 'auto',
    'wb_kelvin': 5500,
    'wb_shift_r': 0,
    'wb_shift_b': 0,
    'dynamic_range': '100',
    'dr_priority': 'off',
    'exp_shift': 0.0,
    'bright': 1.0,
    'highlight_tone': 0,
    'shadow_tone': 0,
    'highlight': 0,
    'shadow_shift': 0,
    'color': 0,
    'saturation': 1.0,
    'color_chrome': 'off',
    'color_chrome_blue': 'off',
    'clarity': 0,
    'grain': 'off',
    'grain_size': 'small',
    'use_camera_wb': True,
}

WB_MODE_MAP = {
    'Auto': 'auto',
    'Auto (white priority)': 'auto_white',
    'Auto (ambiance priority)': 'auto_ambiance',
    'Daylight': 'daylight',
    'Cloudy': 'cloudy',
    'Daylight Fluorescent': 'fluorescent_1',
    'Day White Fluorescent': 'fluorescent_2',
    'White Fluorescent': 'fluorescent_3',
    'Warm White Fluorescent': 'warm_white_fluorescent',
    'Living Room Warm White Fluorescent': 'living_room_warm_white_fluorescent',
    'Incandescent': 'incandescent',
    'Flash': 'flash',
    'Underwater': 'underwater',
    'Custom': 'custom_1',
    'Custom2': 'custom_2',
    'Custom3': 'custom_3',
    'Custom4': 'custom_4',
    'Custom5': 'custom_5',
    'Kelvin': 'kelvin',
}

DR_MAP = {
    'Standard (100%)': '100',
    'Wide (200%)': '200',
    'Wide (400%)': '400',
    'Auto': 'auto',
    '100': '100',
    '200': '200',
    '400': '400',
}

CHROME_MAP = {
    'Off': 'off',
    'Weak': 'weak',
    'Strong': 'strong',
    0: 'off',
    32: 'weak',
    64: 'strong',
}

GRAIN_MAP = {
    'Off': 'off',
    'Weak': 'weak',
    'Strong': 'strong',
    0: 'off',
    32: 'weak',
    64: 'strong',
}

SATURATION_BW_FLAGS = {
    'None (B&W)', 'B&W Red Filter', 'B&W Yellow Filter', 'B&W Green Filter', 'B&W Sepia',
    'Acros', 'Acros Red Filter', 'Acros Yellow Filter', 'Acros Green Filter',
}


def tone_to_slider(value: Any) -> int:
    if value is None:
        return 0
    if isinstance(value, (int, float)):
        raw = int(value)
        if raw >= -5000:
            return max(-100, min(100, raw // 100))
        return max(-100, min(100, raw))
    return 0


def chrome_value(value: Any) -> str:
    if value is None:
        return 'off'
    if isinstance(value, str):
        return CHROME_MAP.get(value, 'off')
    return CHROME_MAP.get(int(value), 'off')


def grain_value(value: Any) -> str:
    if value is None:
        return 'off'
    if isinstance(value, str):
        return GRAIN_MAP.get(value, 'off')
    return GRAIN_MAP.get(int(value), 'off')


def wb_fine_tune(value: Any, index: int = 0) -> int:
    if not value:
        return 0
    if isinstance(value, (list, tuple)) and len(value) > index:
        return max(-9, min(9, int(value[index]) // 20))
    if isinstance(value, str) and ',' in value:
        parts = [int(p.strip()) for p in value.split(',') if p.strip().lstrip('-').isdigit()]
        if len(parts) > index:
            return max(-9, min(9, parts[index] // 20))
    return 0


def metadata_to_params(meta: dict[str, Any]) -> dict[str, Any]:
    params = dict(DEFAULT_PARAMS)

    wb = meta.get('WhiteBalance')
    if wb:
        params['wb_mode'] = WB_MODE_MAP.get(str(wb), 'auto')
    if meta.get('ColorTemperature'):
        try:
            params['wb_kelvin'] = int(meta['ColorTemperature'])
            if params['wb_mode'] == 'auto':
                params['wb_mode'] = 'kelvin'
        except (TypeError, ValueError):
            pass

    params['wb_shift_r'] = wb_fine_tune(meta.get('WhiteBalanceFineTune'), 0)
    params['wb_shift_b'] = wb_fine_tune(meta.get('WhiteBalanceFineTune'), 1)

    dr = meta.get('DynamicRangeSetting') or meta.get('DevelopmentDynamicRange')
    if dr:
        params['dynamic_range'] = DR_MAP.get(str(dr), '100')

    params['shadow_tone'] = tone_to_slider(meta.get('ShadowTone'))
    params['shadow_shift'] = params['shadow_tone']
    params['highlight_tone'] = tone_to_slider(meta.get('HighlightTone'))
    params['highlight'] = params['highlight_tone']

    sat_tag = meta.get('Saturation')
    if sat_tag and str(sat_tag) not in SATURATION_BW_FLAGS and str(sat_tag).lower() not in ('film simulation', 'n/a', ''):
        params['color'] = tone_to_slider(sat_tag)

    params['color_chrome'] = chrome_value(meta.get('ColorChromeEffect'))
    params['color_chrome_blue'] = chrome_value(meta.get('ColorChromeFXBlue'))
    params['clarity'] = tone_to_slider(meta.get('Clarity'))
    params['grain'] = grain_value(meta.get('GrainEffectRoughness'))
    if meta.get('GrainEffectSize') == 'Large':
        params['grain_size'] = 'large'

    if meta.get('RawExposureBias') is not None:
        try:
            params['exp_shift'] = float(meta['RawExposureBias'])
        except (TypeError, ValueError):
            pass

    params['use_camera_wb'] = params['wb_mode'] in ('auto', 'auto_white', 'auto_ambiance')
    return params


def merge_params(incoming: dict[str, Any] | None) -> dict[str, Any]:
    merged = dict(DEFAULT_PARAMS)
    if incoming:
        merged.update(incoming)
    if incoming and 'shadow_tone' in incoming and 'shadow_shift' not in incoming:
        merged['shadow_shift'] = merged['shadow_tone']
    if incoming and 'highlight_tone' in incoming and 'highlight' not in incoming:
        merged['highlight'] = merged['highlight_tone']
    return merged
