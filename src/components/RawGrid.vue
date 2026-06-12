<!--
  - SPDX-FileCopyrightText: 2026 RAW Editor contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="raweditor-gallery__grid">
		<div
			v-for="file in files"
			:key="file.fileid"
			class="raweditor-gallery__item"
			@click="$emit('open', file)">
			<img
				class="raweditor-gallery__thumb"
				:src="thumbUrl(file.fileid)"
				:alt="file.name"
				loading="lazy">
			<div class="raweditor-gallery__name" :title="file.name">
				{{ file.name }}
			</div>
		</div>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'RawGrid',
	props: {
		files: {
			type: Array,
			required: true,
		},
	},
	methods: {
		thumbUrl(fileId) {
			return generateUrl('/apps/raweditor/api/v1/preview/{fileId}?size=thumb', { fileId })
		},
	},
}
</script>
