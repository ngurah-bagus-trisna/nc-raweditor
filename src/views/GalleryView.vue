<!--
  - SPDX-FileCopyrightText: 2026 RAW Editor contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="raweditor-gallery">
		<div class="raweditor-gallery__header">
			<div class="raweditor-gallery__title">
				<h2>{{ t('raweditor', 'RAF Library') }}</h2>
				<span v-if="scanStatusLabel" class="raweditor-gallery__scan-status">
					<NcLoadingIcon v-if="isScanning" :size="16" />
					{{ scanStatusLabel }}
				</span>
			</div>
			<div class="raweditor-gallery__actions">
				<NcButton
					v-if="selectedIds.length > 0"
					type="primary"
					@click="developSelected">
					<template #icon>
						<Tune :size="20" />
					</template>
					{{ developLabel }}
				</NcButton>
				<NcButton
					v-if="selectedIds.length > 0"
					type="secondary"
					:disabled="convertingDng"
					@click="showDngDialog = true">
					<template #icon>
						<FileSwap :size="20" />
					</template>
					{{ dngLabel }}
				</NcButton>
				<NcButton
					type="secondary"
					:disabled="loading"
					@click="rescan">
					<template #icon>
						<Refresh :size="20" />
					</template>
					{{ t('raweditor', 'Rescan') }}
				</NcButton>
			</div>
		</div>

		<NcEmptyContent
			v-if="!loading && files.length === 0"
			:name="t('raweditor', 'No .raf files found')"
			:description="emptyDescription">
			<template #icon>
				<ImageMultiple :size="64" />
			</template>
			<template #action>
				<NcButton type="primary" :disabled="loading" @click="rescan">
					{{ t('raweditor', 'Scan all folders') }}
				</NcButton>
			</template>
		</NcEmptyContent>

		<div v-else-if="loading" class="raweditor-empty">
			<NcLoadingIcon :size="48" />
		</div>

		<div v-else class="raweditor-gallery__grid">
			<div
				v-for="file in files"
				:key="file.fileid"
				class="raweditor-gallery__item"
				:class="{ 'raweditor-gallery__item--selected': selectedIds.includes(file.fileid) }"
				@click="onItemClick(file, $event)">
				<div class="raweditor-gallery__checkbox">
					<input
						type="checkbox"
						:checked="selectedIds.includes(file.fileid)"
						@click.stop
						@change="toggleSelect(file.fileid)">
				</div>
				<div v-if="!file.thumbReady" class="raweditor-gallery__thumb raweditor-gallery__thumb--pending">
					<NcLoadingIcon :size="24" />
				</div>
				<img
					v-else
					class="raweditor-gallery__thumb"
					:src="thumbUrl(file.fileid)"
					:alt="file.name"
					loading="lazy">
				<div class="raweditor-gallery__name" :title="file.name">
					{{ file.name }}
				</div>
			</div>
		</div>

		<div v-if="total > files.length" class="raweditor-gallery__more">
			<NcButton type="secondary" :disabled="loadingMore" @click="loadMore">
				{{ t('raweditor', 'Load more') }}
			</NcButton>
		</div>

		<DngConvertDialog
			v-if="showDngDialog"
			:count="selectedIds.length"
			@confirm="confirmDngConvert"
			@cancel="showDngDialog = false" />
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import FileSwap from 'vue-material-design-icons/FileSwap.vue'
import ImageMultiple from 'vue-material-design-icons/ImageMultiple.vue'
import Refresh from 'vue-material-design-icons/Refresh.vue'
import Tune from 'vue-material-design-icons/Tune.vue'
import { NcButton, NcEmptyContent, NcLoadingIcon } from '@nextcloud/vue'
import DngConvertDialog from '../components/DngConvertDialog.vue'

export default {
	name: 'GalleryView',
	components: {
		DngConvertDialog,
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
		FileSwap,
		ImageMultiple,
		Refresh,
		Tune,
	},
	data() {
		return {
			files: [],
			total: 0,
			offset: 0,
			limit: 50,
			loading: true,
			loadingMore: false,
			selectedIds: [],
			showDngDialog: false,
			convertingDng: false,
			scanState: 'idle',
			thumbsPending: 0,
			pollTimer: null,
		}
	},
	computed: {
		isScanning() {
			return this.scanState === 'scanning' || this.scanState === 'warming'
		},
		scanStatusLabel() {
			if (this.scanState === 'scanning') {
				return this.t('raweditor', 'Scanning library…')
			}
			if (this.scanState === 'warming') {
				return this.t('raweditor', 'Preparing thumbnails ({count})', { count: this.thumbsPending })
			}
			return ''
		},
		emptyDescription() {
			if (this.isScanning) {
				return this.t('raweditor', 'Scanning your Nextcloud folders for .raf files in the background.')
			}
			return this.t('raweditor', 'Add Fujifilm .raf files to your Nextcloud. The library updates automatically.')
		},
		developLabel() {
			return this.t('raweditor', 'Develop ({count})', { count: this.selectedIds.length })
		},
		dngLabel() {
			return this.t('raweditor', 'DNG ({count})', { count: this.selectedIds.length })
		},
	},
	mounted() {
		this.loadGallery()
		this.pollTimer = setInterval(() => this.pollScanStatus(), 8000)
	},
	beforeDestroy() {
		if (this.pollTimer) {
			clearInterval(this.pollTimer)
		}
	},
	methods: {
		thumbUrl(fileId) {
			return generateUrl('/apps/raweditor/api/v1/preview/{fileId}?size=thumb', { fileId })
		},
		async loadGallery(append = false) {
			if (append) {
				this.loadingMore = true
			} else {
				this.loading = true
				this.offset = 0
			}
			try {
				const res = await axios.get(generateUrl('/apps/raweditor/api/v1/gallery'), {
					params: { limit: this.limit, offset: this.offset },
				})
				const newFiles = res.data.files || []
				this.files = append ? [...this.files, ...newFiles] : newFiles
				this.total = res.data.total || 0
				this.offset += newFiles.length

				if (!append) {
					await this.pollScanStatus()
				}
			} catch (e) {
				console.error(e)
			} finally {
				this.loading = false
				this.loadingMore = false
			}
		},
		loadMore() {
			this.loadGallery(true)
		},
		async pollScanStatus() {
			try {
				const res = await axios.get(generateUrl('/apps/raweditor/api/v1/gallery/status'))
				const { state, thumbsPending, total } = res.data
				const prevTotal = this.total
				const prevPending = this.thumbsPending
				this.scanState = state || 'idle'
				this.thumbsPending = thumbsPending || 0

				const needsRefresh = total > prevTotal
					|| (prevPending > 0 && this.thumbsPending < prevPending)
					|| (this.files.length > 0 && this.files.some((f) => !f.thumbReady) && this.thumbsPending < prevPending)

				if (needsRefresh && !this.loading && !this.loadingMore) {
					await this.loadGallery()
				} else if (total !== undefined) {
					this.total = total
				}
			} catch (e) {
				console.error(e)
			}
		},
		async rescan() {
			this.loading = true
			try {
				await axios.post(generateUrl('/apps/raweditor/api/v1/gallery/rescan'))
				this.selectedIds = []
				await this.loadGallery()
			} catch (e) {
				console.error(e)
				this.loading = false
			}
		},
		toggleSelect(fileId) {
			const idx = this.selectedIds.indexOf(fileId)
			if (idx >= 0) {
				this.selectedIds.splice(idx, 1)
			} else {
				this.selectedIds.push(fileId)
			}
		},
		onItemClick(file, event) {
			if (event.ctrlKey || event.metaKey || event.shiftKey) {
				this.toggleSelect(file.fileid)
				return
			}
			if (this.selectedIds.length > 0) {
				this.openEditor(file.fileid, this.selectedIds)
			} else {
				this.openEditor(file.fileid, [file.fileid])
			}
		},
		developSelected() {
			if (this.selectedIds.length === 0) {
				return
			}
			this.openEditor(this.selectedIds[0], this.selectedIds)
		},
		openEditor(fileId, selected) {
			this.$router.push({
				name: 'editor',
				params: { fileId: String(fileId) },
				query: { selected: selected.join(',') },
			})
		},
		async confirmDngConvert(overwrite) {
			this.showDngDialog = false
			this.convertingDng = true
			try {
				const res = await axios.post(generateUrl('/apps/raweditor/api/v1/batch/convert-dng'), {
					fileIds: this.selectedIds,
					overwrite,
				})
				const { converted, results, errors } = res.data
				if (converted > 0) {
					showSuccess(this.t('raweditor', 'Converted {count} photos to DNG', { count: converted }))
				}
				if (errors?.length > 0) {
					const first = errors[0].error || this.t('raweditor', 'Conversion failed')
					if (converted === 0) {
						showError(first)
					} else {
						showError(this.t('raweditor', '{count} failed: {error}', { count: errors.length, error: first }))
					}
				}
			} catch (e) {
				showError(e.response?.data?.error || this.t('raweditor', 'DNG conversion failed'))
			} finally {
				this.convertingDng = false
			}
		},
	},
}
</script>

<style scoped>
.raweditor-gallery__header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 16px;
}

.raweditor-gallery__title {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.raweditor-gallery__scan-status {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	font-size: 13px;
	color: var(--color-text-maxcontrast);
}

.raweditor-gallery__thumb--pending {
	display: flex;
	align-items: center;
	justify-content: center;
	background: var(--color-background-dark);
	min-height: 120px;
}

.raweditor-gallery__actions {
	display: flex;
	gap: 8px;
}

.raweditor-gallery__more {
	text-align: center;
	margin-top: 16px;
}
</style>
