/**
 * SPDX-FileCopyrightText: 2026 RAW Editor contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import VueRouter from 'vue-router'
import GalleryView from '../views/GalleryView.vue'
import EditorView from '../views/EditorView.vue'
import SettingsView from '../views/SettingsView.vue'

Vue.use(VueRouter)

export default new VueRouter({
	mode: 'history',
	base: OC.generateUrl('/apps/raweditor'),
	routes: [
		{ path: '/', name: 'gallery', component: GalleryView },
		{ path: '/settings', name: 'settings', component: SettingsView },
		{ path: '/editor/:fileId', name: 'editor', component: EditorView, props: true },
	],
})
