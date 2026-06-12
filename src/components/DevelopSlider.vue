<!--
  - SPDX-FileCopyrightText: 2026 RAW Editor contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="lr-slider">
		<div class="lr-slider__label">
			<span>{{ label }}</span>
			<span class="lr-slider__value">{{ displayValue }}</span>
		</div>
		<input
			type="range"
			:min="min"
			:max="max"
			:step="step"
			:value="value"
			@input="$emit('input', parseFloat($event.target.value))">
	</div>
</template>

<script>
export default {
	name: 'DevelopSlider',
	props: {
		label: { type: String, required: true },
		value: { type: Number, required: true },
		min: { type: Number, required: true },
		max: { type: Number, required: true },
		step: { type: Number, required: true },
	},
	computed: {
		displayValue() {
			return Number.isInteger(this.step) ? this.value : this.value.toFixed(2)
		},
	},
}
</script>
