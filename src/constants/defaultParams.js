/**
 * SPDX-FileCopyrightText: 2026 RAW Editor contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export const DEFAULT_PARAMS = {
	wb_mode: 'auto',
	wb_kelvin: 5500,
	wb_shift_r: 0,
	wb_shift_b: 0,
	dynamic_range: '100',
	dr_priority: 'off',
	exp_shift: 0.0,
	bright: 1.0,
	highlight_tone: 0,
	shadow_tone: 0,
	highlight: 0,
	shadow_shift: 0,
	color: 0,
	saturation: 1.0,
	color_chrome: 'off',
	color_chrome_blue: 'off',
	clarity: 0,
	grain: 'off',
	grain_size: 'small',
	use_camera_wb: true,
}

export function mergeParams(incoming = {}) {
	const merged = { ...DEFAULT_PARAMS, ...incoming }
	merged.shadow_shift = merged.shadow_tone ?? merged.shadow_shift
	merged.highlight = merged.highlight_tone ?? merged.highlight
	return merged
}
