<!--
  - SPDX-FileCopyrightText: 2026 RAW Editor contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="lr-develop">
		<div class="lr-develop__toolbar">
			<NcButton type="tertiary" class="lr-develop__back" @click="goBack">
				<template #icon>
					<ArrowLeft :size="20" />
				</template>
				{{ t('raweditor', 'Library') }}
			</NcButton>
			<span class="lr-develop__module">{{ t('raweditor', 'Develop') }}</span>
			<span class="lr-develop__filename">{{ fileName }}</span>
			<div class="spacer" />
			<NcButton
				type="secondary"
				:disabled="exporting || selectedIds.length === 0"
				@click="batchExport">
				<template #icon>
					<Export :size="20" />
				</template>
				{{ exportLabel }}
			</NcButton>
		</div>

		<div class="lr-develop__workspace">
			<div class="lr-develop__canvas">
				<RealtimePreview
					:file-id="fileId"
					:params="params"
					:alt="fileName" />
			</div>
			<DevelopPanel
				:params="params"
				:presets="presets"
				:selected-count="selectedIds.length"
				@update="onParamsUpdate"
				@reset="resetEdits"
				@save-preset="promptSavePreset"
				@load-preset="loadPresetParams"
				@apply-preset="applyPresetToSelected"
				@sync-settings="syncSettingsToSelected" />
		</div>

		<DevelopFilmstrip
			:files="filmstripFiles"
			:active-file-id="activeFileId"
			:selected-ids="selectedIds"
			@select="onFilmstripSelect" />

		<ExportDialog
			v-if="showExportDialog"
			:batch="exportBatch"
			@confirm="confirmExport"
			@cancel="showExportDialog = false" />

		<NcDialog
			v-if="showSavePresetDialog"
			:open="true"
			:name="t('raweditor', 'Save preset')"
			:buttons="savePresetButtons"
			@closing="showSavePresetDialog = false">
			<NcTextField
				:value.sync="newPresetName"
				:label="t('raweditor', 'Preset name')"
				:placeholder="t('raweditor', 'My Fuji look')" />
		</NcDialog>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import ArrowLeft from 'vue-material-design-icons/ArrowLeft.vue'
import Export from 'vue-material-design-icons/Export.vue'
import { NcButton, NcDialog, NcTextField } from '@nextcloud/vue'
import DevelopFilmstrip from '../components/DevelopFilmstrip.vue'
import DevelopPanel from '../components/DevelopPanel.vue'
import ExportDialog from '../components/ExportDialog.vue'
import RealtimePreview from '../components/RealtimePreview.vue'
import { DEFAULT_PARAMS, mergeParams } from '../constants/defaultParams.js'

export { DEFAULT_PARAMS }

export default {
	name: 'EditorView',
	components: {
		RealtimePreview,
		DevelopFilmstrip,
		DevelopPanel,
		ExportDialog,
		NcButton,
		NcDialog,
		NcTextField,
		ArrowLeft,
		Export,
	},
	props: {
		fileId: { type: String, required: true },
	},
	data() {
		return {
			fileName: '',
			params: { ...DEFAULT_PARAMS },
			presets: [],
			filmstripFiles: [],
			selectedIds: [],
			exporting: false,
			showExportDialog: false,
			exportBatch: false,
			showSavePresetDialog: false,
			newPresetName: '',
			saveTimer: null,
		}
	},
	computed: {
		activeFileId() {
			return parseInt(this.fileId, 10)
		},
		exportLabel() {
			if (this.selectedIds.length > 1) {
				return this.t('raweditor', 'Export {count}', { count: this.selectedIds.length })
			}
			return this.t('raweditor', 'Export JPEG')
		},
		savePresetButtons() {
			return [
				{
					label: this.t('raweditor', 'Cancel'),
					callback: () => { this.showSavePresetDialog = false },
				},
				{
					label: this.t('raweditor', 'Save'),
					type: 'primary',
					callback: () => this.savePreset(),
				},
			]
		},
	},
	watch: {
		fileId: {
			immediate: true,
			handler() {
				this.initEditor()
			},
		},
	},
	beforeDestroy() {
		clearTimeout(this.saveTimer)
	},
	methods: {
		goBack() {
			this.$router.push({ name: 'gallery' })
		},
		parseSelectedFromQuery() {
			const raw = this.$route.query.selected
			if (!raw) {
				return []
			}
			return String(raw).split(',').map(id => parseInt(id, 10)).filter(id => !Number.isNaN(id))
		},
		async initEditor() {
			await Promise.all([this.loadFilmstrip(), this.loadPresets()])
			const fromQuery = this.parseSelectedFromQuery()
			this.selectedIds = fromQuery.length > 0 ? fromQuery : [this.activeFileId]
			if (!this.selectedIds.includes(this.activeFileId)) {
				this.selectedIds.push(this.activeFileId)
			}
			await this.loadCurrentPhoto()
		},
		async loadFilmstrip() {
			const res = await axios.get(generateUrl('/apps/raweditor/api/v1/gallery'), {
				params: { limit: 500, offset: 0 },
			})
			this.filmstripFiles = res.data.files || []
			if (this.filmstripFiles.length === 0) {
				const fileRes = await axios.get(generateUrl('/apps/raweditor/api/v1/files/{fileId}', { fileId: this.fileId }))
				this.filmstripFiles = [fileRes.data]
			}
		},
		async loadPresets() {
			const res = await axios.get(generateUrl('/apps/raweditor/api/v1/presets'))
			this.presets = res.data.presets || []
		},
		async loadCurrentPhoto() {
			try {
				const [editsRes, fileRes] = await Promise.all([
					axios.get(generateUrl('/apps/raweditor/api/v1/edits/{fileId}', { fileId: this.fileId })),
					axios.get(generateUrl('/apps/raweditor/api/v1/files/{fileId}', { fileId: this.fileId })),
				])
				this.params = mergeParams(editsRes.data.params)
				this.fileName = fileRes.data.name || `file-${this.fileId}`
			} catch (e) {
				console.error(e)
			}
		},
		onFilmstripSelect(file, { multi }) {
			if (multi) {
				const idx = this.selectedIds.indexOf(file.fileid)
				if (idx >= 0) {
					this.selectedIds.splice(idx, 1)
				} else {
					this.selectedIds.push(file.fileid)
				}
			} else {
				this.selectedIds = [file.fileid]
				if (file.fileid !== this.activeFileId) {
					this.$router.replace({
						name: 'editor',
						params: { fileId: String(file.fileid) },
						query: { selected: this.selectedIds.join(',') },
					})
				}
			}
		},
		onParamsUpdate(params) {
			this.params = params
			this.debouncedSave()
		},
		debouncedSave() {
			clearTimeout(this.saveTimer)
			this.saveTimer = setTimeout(() => {
				this.saveEdits()
			}, 600)
		},
		async saveEdits() {
			try {
				await axios.put(generateUrl('/apps/raweditor/api/v1/edits/{fileId}', { fileId: this.fileId }), this.params)
			} catch (e) {
				console.error(e)
			}
		},
		resetEdits() {
			this.params = { ...DEFAULT_PARAMS }
			this.saveEdits()
		},
		loadPresetParams(params) {
			this.params = mergeParams(params)
			this.debouncedSave()
		},
		promptSavePreset() {
			this.newPresetName = ''
			this.showSavePresetDialog = true
		},
		async savePreset() {
			if (!this.newPresetName.trim()) {
				return
			}
			try {
				const res = await axios.post(generateUrl('/apps/raweditor/api/v1/presets'), {
					name: this.newPresetName.trim(),
					params: this.params,
				})
				this.presets.push(res.data)
				this.showSavePresetDialog = false
				showSuccess(this.t('raweditor', 'Preset saved'))
			} catch (e) {
				showError(this.t('raweditor', 'Failed to save preset'))
			}
		},
		async applyPresetToSelected(presetId) {
			if (!presetId) {
				return
			}
			try {
				const res = await axios.post(generateUrl('/apps/raweditor/api/v1/presets/{id}/apply', { id: presetId }), {
					fileIds: this.selectedIds,
				})
				showSuccess(this.t('raweditor', 'Applied to {count} photos', { count: res.data.applied }))
				if (this.selectedIds.includes(this.activeFileId)) {
					const preset = this.presets.find(p => p.id === presetId)
					if (preset) {
						this.params = mergeParams(preset.params)
					}
				}
			} catch (e) {
				showError(this.t('raweditor', 'Failed to apply preset'))
			}
		},
		async syncSettingsToSelected() {
			const targets = this.selectedIds.filter(id => id !== this.activeFileId)
			if (targets.length === 0) {
				return
			}
			try {
				const res = await axios.post(generateUrl('/apps/raweditor/api/v1/batch/apply-params'), {
					fileIds: targets,
					params: this.params,
				})
				showSuccess(this.t('raweditor', 'Synced to {count} photos', { count: res.data.applied }))
			} catch (e) {
				showError(this.t('raweditor', 'Failed to sync settings'))
			}
		},
		batchExport() {
			this.exportBatch = this.selectedIds.length > 1
			this.showExportDialog = true
		},
		async confirmExport(overwrite) {
			this.showExportDialog = false
			this.exporting = true
			try {
				await this.saveEdits()
				if (this.selectedIds.length > 1) {
					const res = await axios.post(generateUrl('/apps/raweditor/api/v1/batch/export'), {
						fileIds: this.selectedIds,
						quality: 98,
						overwrite,
					})
					showSuccess(this.t('raweditor', 'Exported {count} photos', { count: res.data.exported }))
				} else {
					const res = await axios.post(generateUrl('/apps/raweditor/api/v1/export/{fileId}', { fileId: this.fileId }), {
						quality: 98,
						overwrite,
						params: this.params,
					})
					showSuccess(this.t('raweditor', 'Exported to {path}', { path: res.data.path }))
				}
			} catch (e) {
				if (e.response?.status === 409) {
					this.showExportDialog = true
					showError(this.t('raweditor', 'JPEG already exists. Confirm to overwrite.'))
				} else {
					showError(e.response?.data?.error || this.t('raweditor', 'Export failed'))
				}
			} finally {
				this.exporting = false
			}
		},
	},
}
</script>
