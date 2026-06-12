<!--
  - SPDX-FileCopyrightText: 2026 RAW Editor contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog
		:open="true"
		:name="title"
		:buttons="buttons"
		@closing="$emit('cancel')">
		<p>{{ description }}</p>
		<NcCheckboxRadioSwitch
			:checked.sync="overwrite"
			type="switch">
			{{ t('raweditor', 'Overwrite existing JPEG') }}
		</NcCheckboxRadioSwitch>
	</NcDialog>
</template>

<script>
import { NcCheckboxRadioSwitch, NcDialog } from '@nextcloud/vue'

export default {
	name: 'ExportDialog',
	components: {
		NcDialog,
		NcCheckboxRadioSwitch,
	},
	props: {
		batch: { type: Boolean, default: false },
	},
	data() {
		return {
			overwrite: false,
		}
	},
	computed: {
		title() {
			return this.batch
				? this.t('raweditor', 'Batch export to JPEG')
				: this.t('raweditor', 'Export to JPEG')
		},
		description() {
			return this.batch
				? this.t('raweditor', 'Export selected photos as high-quality JPEG files next to each .raf file.')
				: this.t('raweditor', 'Export a high-quality JPEG to the same folder as the .raf file.')
		},
		buttons() {
			return [
				{
					label: this.t('raweditor', 'Cancel'),
					callback: () => this.$emit('cancel'),
				},
				{
					label: this.t('raweditor', 'Export'),
					type: 'primary',
					callback: () => this.$emit('confirm', this.overwrite),
				},
			]
		},
	},
}
</script>
