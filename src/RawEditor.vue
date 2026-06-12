<!--
  - SPDX-FileCopyrightText: 2026 RAW Editor contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcContent app-name="raweditor" :class="{ 'raweditor--develop': isDevelopMode }">
		<NcAppNavigation v-if="!isDevelopMode">
			<NcAppNavigationList>
				<NcAppNavigationItem
					:name="t('raweditor', 'Library')"
					:to="{ name: 'gallery' }"
					:exact="true">
					<template #icon>
						<ImageMultiple :size="20" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem
					:name="t('raweditor', 'Settings')"
					:to="{ name: 'settings' }">
					<template #icon>
						<Cog :size="20" />
					</template>
				</NcAppNavigationItem>
			</NcAppNavigationList>
		</NcAppNavigation>
		<NcAppContent :class="{ 'raweditor-app-content--develop': isDevelopMode }">
			<router-view />
		</NcAppContent>
	</NcContent>
</template>

<script>
import ImageMultiple from 'vue-material-design-icons/ImageMultiple.vue'
import Cog from 'vue-material-design-icons/Cog.vue'
import {
	NcAppContent,
	NcAppNavigation,
	NcAppNavigationItem,
	NcAppNavigationList,
	NcContent,
} from '@nextcloud/vue'

export default {
	name: 'RawEditor',
	components: {
		NcContent,
		NcAppNavigation,
		NcAppNavigationList,
		NcAppNavigationItem,
		NcAppContent,
		ImageMultiple,
		Cog,
	},
	computed: {
		isDevelopMode() {
			return this.$route.name === 'editor'
		},
	},
}
</script>
