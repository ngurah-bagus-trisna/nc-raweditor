/**
 * SPDX-FileCopyrightText: 2026 RAW Editor contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateFilePath } from '@nextcloud/router'
import { getRequestToken } from '@nextcloud/auth'
import { translate, translatePlural } from '@nextcloud/l10n'
import Vue from 'vue'

import RawEditor from './RawEditor.vue'
import router from './router/index.js'

// eslint-disable-next-line
__webpack_nonce__ = btoa(getRequestToken())
// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('raweditor', '', 'js/')

Vue.prototype.t = translate
Vue.prototype.n = translatePlural
Vue.prototype.OC = window.OC
Vue.prototype.OCA = window.OCA

document.addEventListener('DOMContentLoaded', () => {
	// eslint-disable-next-line no-new
	new Vue({
		el: '#content',
		router,
		render: h => h(RawEditor),
	})
})
