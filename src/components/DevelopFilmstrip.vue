<!--
  - SPDX-FileCopyrightText: 2026 RAW Editor contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="lr-filmstrip">
		<div class="lr-filmstrip__scroll">
			<div
				v-for="file in files"
				:key="file.fileid"
				class="lr-filmstrip__item"
				:class="{
					'lr-filmstrip__item--active': file.fileid === activeFileId,
					'lr-filmstrip__item--selected': selectedIds.includes(file.fileid),
				}"
				@click="onClick(file, $event)">
				<img
					:src="thumbUrl(file.fileid)"
					:alt="file.name"
					loading="lazy">
			</div>
		</div>
		<div class="lr-filmstrip__count">
			{{ selectedIds.length > 0 ? selectedLabel : totalLabel }}
		</div>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'DevelopFilmstrip',
	props: {
		files: { type: Array, required: true },
		activeFileId: { type: Number, required: true },
		selectedIds: { type: Array, required: true },
	},
	computed: {
		selectedLabel() {
			return this.t('raweditor', '{count} selected', { count: this.selectedIds.length })
		},
		totalLabel() {
			return this.t('raweditor', '{count} photos', { count: this.files.length })
		},
	},
	methods: {
		thumbUrl(fileId) {
			return generateUrl('/apps/raweditor/api/v1/preview/{fileId}?size=thumb', { fileId })
		},
		onClick(file, event) {
			this.$emit('select', file, {
				multi: event.ctrlKey || event.metaKey || event.shiftKey,
			})
		},
	},
}
</script>
