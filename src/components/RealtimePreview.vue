<!--
  - SPDX-FileCopyrightText: 2026 RAW Editor contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="realtime-preview">
		<NcLoadingIcon v-if="loading && !displayUrl" :size="48" class="lr-develop__loader" />
		<img
			v-show="displayUrl"
			:key="displayUrl"
			:src="displayUrl"
			:alt="alt"
			class="lr-develop__image realtime-preview__image"
			:style="imageStyle"
			@load="onLoad">
		<p v-if="error" class="lr-develop__error">{{ error }}</p>
		<p v-if="loadingHd && displayUrl" class="realtime-preview__hint">
			{{ t('raweditor', 'Loading full preview…') }}
		</p>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { NcLoadingIcon } from '@nextcloud/vue'
import { paramsToCssFilter } from '../utils/realtimeAdjust.js'

export default {
	name: 'RealtimePreview',
	components: { NcLoadingIcon },
	props: {
		fileId: { type: [String, Number], required: true },
		params: { type: Object, required: true },
		alt: { type: String, default: '' },
	},
	data() {
		return {
			displayUrl: '',
			loading: true,
			loadingHd: false,
			error: '',
			blobUrls: [],
		}
	},
	computed: {
		imageStyle() {
			return {
				filter: paramsToCssFilter(this.params),
				willChange: 'filter',
			}
		},
	},
	watch: {
		fileId: {
			immediate: true,
			handler() {
				this.loadPreview()
			},
		},
	},
	beforeDestroy() {
		for (const url of this.blobUrls) {
			URL.revokeObjectURL(url)
		}
	},
	methods: {
		previewUrl(size) {
			return generateUrl('/apps/raweditor/api/v1/preview/{fileId}?size={size}', {
				fileId: this.fileId,
				size,
			})
		},
		async loadPreview() {
			for (const url of this.blobUrls) {
				URL.revokeObjectURL(url)
			}
			this.blobUrls = []
			this.displayUrl = ''
			this.error = ''
			this.loading = true
			this.loadingHd = false
			this.displayUrl = this.previewUrl('thumb')
			this.loading = false

			this.loadingHd = true
			try {
				const res = await axios.get(this.previewUrl('edit'), { responseType: 'blob' })
				const url = URL.createObjectURL(res.data)
				this.blobUrls.push(url)
				this.displayUrl = url
			} catch (e) {
				if (!this.displayUrl) {
					this.error = this.t('raweditor', 'Failed to load preview')
				}
			} finally {
				this.loadingHd = false
			}
		},
		onLoad() {
			this.loading = false
		},
	},
}
</script>

<style scoped>
.realtime-preview {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	width: 100%;
	height: 100%;
	position: relative;
}

.realtime-preview__image {
	transition: none;
}

.realtime-preview__hint {
	position: absolute;
	bottom: 12px;
	font-size: 11px;
	color: #888;
	pointer-events: none;
}
</style>
