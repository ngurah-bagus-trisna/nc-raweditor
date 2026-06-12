/**
 * Client-side realtime preview adjustments (GPU CSS filters).
 * SPDX-FileCopyrightText: 2026 RAW Editor contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export function paramsToCssFilter(params) {
	const expShift = Number(params.exp_shift) || 0
	const bright = Number(params.bright) || 1
	const saturation = Number(params.saturation) ?? 1
	const highlight = Number(params.highlight_tone ?? params.highlight) || 0
	const shadow = Number(params.shadow_tone ?? params.shadow_shift) || 0
	const color = Number(params.color) || 0

	const exposure = Math.pow(2, expShift) * bright
	const colorSat = 1 + color / 100
	const shadowLift = 1 + shadow / 250
	const highlightPull = 1 - highlight / 350
	const brightness = exposure * shadowLift * highlightPull
	const contrast = clamp(1 + (shadow - highlight) / 200, 0.55, 1.8)

	return `brightness(${brightness.toFixed(4)}) contrast(${contrast.toFixed(4)}) saturate(${(saturation * colorSat).toFixed(4)})`
}

function clamp(value, min, max) {
	return Math.min(max, Math.max(min, value))
}
