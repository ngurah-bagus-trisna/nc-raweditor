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
			{{ t('raweditor', 'Overwrite existing DNG') }}
		</NcCheckboxRadioSwitch>
	</NcDialog>
</template>

<script>
import { NcCheckboxRadioSwitch, NcDialog } from '@nextcloud/vue'

export default {
	name: 'DngConvertDialog',
	components: { NcDialog, NcCheckboxRadioSwitch },
	props: {
		count: { type: Number, required: true },
	},
	data() {
		return {
			overwrite: false,
		}
	},
	computed: {
		title() {
			return this.t('raweditor', 'Convert to DNG ({count})', { count: this.count })
		},
		description() {
			return this.t('raweditor', 'Convert selected .raf files to DNG in the same folder for editing on Android (Lightroom Mobile, etc.). If DNG is not supported for your camera model yet, a 16-bit TIFF is created instead.')
		},
		buttons() {
			return [
				{
					label: this.t('raweditor', 'Cancel'),
					callback: () => this.$emit('cancel'),
				},
				{
					label: this.t('raweditor', 'Convert'),
					type: 'primary',
					callback: () => this.$emit('confirm', this.overwrite),
				},
			]
		},
	},
}
</script>
