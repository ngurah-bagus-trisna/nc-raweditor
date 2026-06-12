<!--
  - SPDX-FileCopyrightText: 2026 RAW Editor contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="lr-panel">
		<div class="lr-panel__section">
			<button class="lr-panel__header" @click="openWb = !openWb">
				<span>{{ t('raweditor', 'White Balance') }}</span>
				<ChevronDown :size="16" :class="{ 'lr-panel__chevron--open': openWb }" />
			</button>
			<div v-show="openWb" class="lr-panel__body">
				<select :value="params.wb_mode" class="lr-panel__select" @change="update('wb_mode', $event.target.value)">
					<option v-for="wb in wbPresets" :key="wb.id" :value="wb.id">{{ wb.label }}</option>
				</select>
				<DevelopSlider
					v-if="params.wb_mode === 'kelvin'"
					:label="t('raweditor', 'Kelvin')"
					:value="params.wb_kelvin"
					:min="2500"
					:max="10000"
					:step="100"
					@input="update('wb_kelvin', $event)" />
				<DevelopSlider
					:label="t('raweditor', 'WB Shift Red')"
					:value="params.wb_shift_r"
					:min="-9"
					:max="9"
					:step="1"
					@input="update('wb_shift_r', $event)" />
				<DevelopSlider
					:label="t('raweditor', 'WB Shift Blue')"
					:value="params.wb_shift_b"
					:min="-9"
					:max="9"
					:step="1"
					@input="update('wb_shift_b', $event)" />
			</div>
		</div>

		<div class="lr-panel__section">
			<button class="lr-panel__header" @click="openDr = !openDr">
				<span>{{ t('raweditor', 'Dynamic Range') }}</span>
				<ChevronDown :size="16" :class="{ 'lr-panel__chevron--open': openDr }" />
			</button>
			<div v-show="openDr" class="lr-panel__body">
				<select :value="params.dynamic_range" class="lr-panel__select" @change="update('dynamic_range', $event.target.value)">
					<option v-for="dr in drOptions" :key="dr.id" :value="dr.id">{{ dr.label }}</option>
				</select>
			</div>
		</div>

		<div class="lr-panel__section">
			<button class="lr-panel__header" @click="openPresets = !openPresets">
				<span>{{ t('raweditor', 'Presets') }}</span>
				<ChevronDown :size="16" :class="{ 'lr-panel__chevron--open': openPresets }" />
			</button>
			<div v-show="openPresets" class="lr-panel__body">
				<select v-model="selectedPresetId" class="lr-panel__select" @change="onPresetSelect">
					<option value="">{{ t('raweditor', 'Choose preset…') }}</option>
					<option v-for="preset in presets" :key="preset.id" :value="preset.id">{{ preset.name }}</option>
				</select>
				<div class="lr-panel__actions">
					<NcButton type="secondary" @click="$emit('save-preset')">{{ t('raweditor', 'Save current') }}</NcButton>
					<NcButton type="primary" :disabled="!canApplyBatch" @click="$emit('apply-preset', selectedPresetId)">
						{{ applyLabel }}
					</NcButton>
				</div>
			</div>
		</div>

		<div class="lr-panel__section">
			<button class="lr-panel__header" @click="openLight = !openLight">
				<span>{{ t('raweditor', 'Light') }}</span>
				<ChevronDown :size="16" :class="{ 'lr-panel__chevron--open': openLight }" />
			</button>
			<div v-show="openLight" class="lr-panel__body">
				<DevelopSlider :label="t('raweditor', 'Exposure')" :value="params.exp_shift" :min="-2" :max="2" :step="0.1" @input="update('exp_shift', $event)" />
				<DevelopSlider :label="t('raweditor', 'Brightness')" :value="params.bright" :min="0.5" :max="2" :step="0.05" @input="update('bright', $event)" />
				<DevelopSlider :label="t('raweditor', 'Highlight Tone')" :value="params.highlight_tone" :min="-100" :max="100" :step="1" @input="updateTone('highlight_tone', 'highlight', $event)" />
				<DevelopSlider :label="t('raweditor', 'Shadow Tone')" :value="params.shadow_tone" :min="-100" :max="100" :step="1" @input="updateTone('shadow_tone', 'shadow_shift', $event)" />
			</div>
		</div>

		<div class="lr-panel__section">
			<button class="lr-panel__header" @click="openColor = !openColor">
				<span>{{ t('raweditor', 'Color') }}</span>
				<ChevronDown :size="16" :class="{ 'lr-panel__chevron--open': openColor }" />
			</button>
			<div v-show="openColor" class="lr-panel__body">
				<DevelopSlider :label="t('raweditor', 'Color')" :value="params.color" :min="-100" :max="100" :step="1" @input="update('color', $event)" />
				<DevelopSlider :label="t('raweditor', 'Saturation')" :value="params.saturation" :min="0" :max="2" :step="0.05" @input="update('saturation', $event)" />
			</div>
		</div>

		<div class="lr-panel__section">
			<button class="lr-panel__header" @click="openEffects = !openEffects">
				<span>{{ t('raweditor', 'Effects') }}</span>
				<ChevronDown :size="16" :class="{ 'lr-panel__chevron--open': openEffects }" />
			</button>
			<div v-show="openEffects" class="lr-panel__body">
				<label class="lr-panel__field-label">{{ t('raweditor', 'Color Chrome Effect') }}</label>
				<select :value="params.color_chrome" class="lr-panel__select" @change="update('color_chrome', $event.target.value)">
					<option v-for="o in chromeOptions" :key="o.id" :value="o.id">{{ o.label }}</option>
				</select>
				<label class="lr-panel__field-label">{{ t('raweditor', 'Color Chrome FX Blue') }}</label>
				<select :value="params.color_chrome_blue" class="lr-panel__select" @change="update('color_chrome_blue', $event.target.value)">
					<option v-for="o in chromeOptions" :key="o.id" :value="o.id">{{ o.label }}</option>
				</select>
				<DevelopSlider :label="t('raweditor', 'Clarity')" :value="params.clarity" :min="-100" :max="100" :step="1" @input="update('clarity', $event)" />
				<label class="lr-panel__field-label">{{ t('raweditor', 'Grain Effect') }}</label>
				<select :value="params.grain" class="lr-panel__select" @change="update('grain', $event.target.value)">
					<option v-for="o in grainOptions" :key="o.id" :value="o.id">{{ o.label }}</option>
				</select>
			</div>
		</div>

		<div class="lr-panel__footer">
			<NcButton type="tertiary" @click="$emit('reset')">{{ t('raweditor', 'Reset all') }}</NcButton>
			<NcButton type="secondary" :disabled="!canSyncBatch" @click="$emit('sync-settings')">{{ syncLabel }}</NcButton>
		</div>
	</div>
</template>

<script>
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue'
import { NcButton } from '@nextcloud/vue'
import { CHROME_OPTIONS, DR_OPTIONS, GRAIN_OPTIONS, WB_PRESETS } from '../constants/fujiWhiteBalance.js'
import DevelopSlider from './DevelopSlider.vue'

export default {
	name: 'DevelopPanel',
	components: { DevelopSlider, NcButton, ChevronDown },
	props: {
		params: { type: Object, required: true },
		presets: { type: Array, required: true },
		selectedCount: { type: Number, default: 1 },
	},
	data() {
		return {
			openWb: false,
			openDr: false,
			openPresets: false,
			openLight: true,
			openColor: true,
			openEffects: false,
			selectedPresetId: '',
			wbPresets: WB_PRESETS,
			drOptions: DR_OPTIONS,
			chromeOptions: CHROME_OPTIONS,
			grainOptions: GRAIN_OPTIONS,
		}
	},
	computed: {
		canApplyBatch() {
			return this.selectedCount > 0 && this.selectedPresetId !== ''
		},
		canSyncBatch() {
			return this.selectedCount > 1
		},
		applyLabel() {
			if (this.selectedCount > 1) {
				return this.t('raweditor', 'Apply to {count} photos', { count: this.selectedCount })
			}
			return this.t('raweditor', 'Apply preset')
		},
		syncLabel() {
			if (this.selectedCount > 1) {
				return this.t('raweditor', 'Sync to {count} photos', { count: this.selectedCount })
			}
			return this.t('raweditor', 'Sync settings')
		},
	},
	methods: {
		update(key, value) {
			const next = { ...this.params, [key]: value }
			if (['auto', 'auto_white', 'auto_ambiance'].includes(value) && key === 'wb_mode') {
				next.use_camera_wb = true
			} else if (key === 'wb_mode') {
				next.use_camera_wb = false
			}
			this.$emit('update', next)
		},
		updateTone(primary, legacy, value) {
			this.$emit('update', { ...this.params, [primary]: value, [legacy]: value })
		},
		onPresetSelect() {
			if (this.selectedPresetId === '') return
			const preset = this.presets.find(p => p.id === Number(this.selectedPresetId))
			if (preset) this.$emit('load-preset', preset.params)
		},
	},
}
</script>
