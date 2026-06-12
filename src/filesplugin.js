/**
 * SPDX-FileCopyrightText: 2026 RAW Editor contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { FileAction, DefaultType, registerFileAction } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

function isRafFile(node) {
	const name = node.basename || node.displayname || node.name || ''
	return name.toLowerCase().endsWith('.raf')
}

window.addEventListener('DOMContentLoaded', () => {
	registerFileAction(new FileAction({
		id: 'raweditor-open',
		displayName: () => t('raweditor', 'Open in RAW Editor'),
		default: DefaultType.HIDDEN,
		enabled(nodes) {
			return nodes.length === 1 && isRafFile(nodes[0])
		},
		async exec(node) {
			const fileId = node.fileid || node.id
			window.location.href = generateUrl('/apps/raweditor/editor/{fileId}', { fileId })
			return null
		},
		order: 50,
	}))
})
