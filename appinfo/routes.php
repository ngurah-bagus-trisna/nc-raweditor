<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#index', 'url' => '/editor/{fileId}', 'verb' => 'GET', 'postfix' => 'editor'],

		['name' => 'settings#getFolders', 'url' => '/api/v1/settings/folders', 'verb' => 'GET'],
		['name' => 'settings#updateFolders', 'url' => '/api/v1/settings/folders', 'verb' => 'PUT'],

		['name' => 'gallery#index', 'url' => '/api/v1/gallery', 'verb' => 'GET'],
		['name' => 'gallery#stats', 'url' => '/api/v1/gallery/stats', 'verb' => 'GET'],
		['name' => 'gallery#rescan', 'url' => '/api/v1/gallery/rescan', 'verb' => 'POST'],
		['name' => 'gallery#fileInfo', 'url' => '/api/v1/files/{fileId}', 'verb' => 'GET'],
		['name' => 'metadata#get', 'url' => '/api/v1/files/{fileId}/metadata', 'verb' => 'GET'],

		['name' => 'preview#get', 'url' => '/api/v1/preview/{fileId}', 'verb' => 'GET'],
		['name' => 'preview#render', 'url' => '/api/v1/preview/{fileId}/render', 'verb' => 'POST'],

		['name' => 'export#export', 'url' => '/api/v1/export/{fileId}', 'verb' => 'POST'],

		['name' => 'edit#get', 'url' => '/api/v1/edits/{fileId}', 'verb' => 'GET'],
		['name' => 'edit#save', 'url' => '/api/v1/edits/{fileId}', 'verb' => 'PUT'],

		['name' => 'preset#index', 'url' => '/api/v1/presets', 'verb' => 'GET'],
		['name' => 'preset#create', 'url' => '/api/v1/presets', 'verb' => 'POST'],
		['name' => 'preset#update', 'url' => '/api/v1/presets/{id}', 'verb' => 'PUT'],
		['name' => 'preset#delete', 'url' => '/api/v1/presets/{id}', 'verb' => 'DELETE'],
		['name' => 'preset#apply', 'url' => '/api/v1/presets/{id}/apply', 'verb' => 'POST'],

		['name' => 'batch#applyParams', 'url' => '/api/v1/batch/apply-params', 'verb' => 'POST'],
		['name' => 'batch#export', 'url' => '/api/v1/batch/export', 'verb' => 'POST'],
		['name' => 'batch#convertDng', 'url' => '/api/v1/batch/convert-dng', 'verb' => 'POST'],
	],
];
