<!--
  - SPDX-FileCopyrightText: 2026 RAW Editor contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="raweditor-settings">
		<h2>{{ t('raweditor', 'Library source') }}</h2>

		<NcCheckboxRadioSwitch
			:checked.sync="scanAll"
			type="switch"
			@update:checked="onScanModeChange">
			{{ t('raweditor', 'Scan all folders (default)') }}
		</NcCheckboxRadioSwitch>
		<p class="raweditor-settings__hint">
			{{ scanAll
				? t('raweditor', 'All .raf files in your Nextcloud account are included in the library.')
				: t('raweditor', 'Only .raf files inside the folders below are included.') }}
		</p>

		<div v-if="!scanAll" class="raweditor-settings__folders">
			<NcButton type="primary" @click="pickFolder">
				<template #icon>
					<FolderPlus :size="20" />
				</template>
				{{ t('raweditor', 'Add folder') }}
			</NcButton>

			<div class="raweditor-settings__folder-list">
				<div
					v-for="folder in folders"
					:key="folder.id"
					class="raweditor-settings__folder-item">
					<span>{{ folder.path || folder.name }}</span>
					<NcButton type="tertiary" aria-label="Remove" @click="removeFolder(folder.id)">
						<template #icon>
							<Close :size="20" />
						</template>
					</NcButton>
				</div>
			</div>
		</div>

		<NcButton type="secondary" :disabled="saving" @click="saveAndRescan">
			{{ t('raweditor', 'Save and rescan library') }}
		</NcButton>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { getFilePickerBuilder, showSuccess } from '@nextcloud/dialogs'
import Close from 'vue-material-design-icons/Close.vue'
import FolderPlus from 'vue-material-design-icons/FolderPlus.vue'
import { NcButton, NcCheckboxRadioSwitch } from '@nextcloud/vue'

export default {
	name: 'SettingsView',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		Close,
		FolderPlus,
	},
	data() {
		return {
			scanAll: true,
			folders: [],
			folderIds: [],
			saving: false,
		}
	},
	mounted() {
		this.loadSettings()
	},
	methods: {
		async loadSettings() {
			const res = await axios.get(generateUrl('/apps/raweditor/api/v1/settings/folders'))
			this.scanAll = res.data.scanMode !== 'selected'
			this.folders = res.data.folders || []
			this.folderIds = this.folders.map(f => f.id)
		},
		onScanModeChange(checked) {
			this.scanAll = checked
		},
		async pickFolder() {
			const picker = getFilePickerBuilder(this.t('raweditor', 'Pick a folder'))
				.setMultiSelect(false)
				.allowDirectories(true)
				.build()
			const nodes = await picker.pick()
			if (!nodes || nodes.length === 0) {
				return
			}
			const node = nodes[0]
			const id = typeof node === 'object' ? node.fileid || node.id : node
			if (!this.folderIds.includes(id)) {
				this.folderIds.push(id)
				this.folders.push({
					id,
					path: typeof node === 'object' ? (node.path || node.basename) : String(id),
					name: typeof node === 'object' ? (node.basename || node.name) : String(id),
				})
			}
		},
		removeFolder(id) {
			this.folderIds = this.folderIds.filter(fid => fid !== id)
			this.folders = this.folders.filter(f => f.id !== id)
		},
		async saveAndRescan() {
			this.saving = true
			try {
				await axios.put(generateUrl('/apps/raweditor/api/v1/settings/folders'), {
					scanMode: this.scanAll ? 'all' : 'selected',
					folderIds: this.folderIds,
				})
				await axios.post(generateUrl('/apps/raweditor/api/v1/gallery/rescan'))
				showSuccess(this.t('raweditor', 'Settings saved and library rescanned'))
			} catch (e) {
				console.error(e)
			} finally {
				this.saving = false
			}
		},
	},
}
</script>

<style scoped>
.raweditor-settings__hint {
	color: var(--color-text-maxcontrast);
	margin: 8px 0 20px;
}

.raweditor-settings__folders {
	margin-bottom: 16px;
}
</style>
