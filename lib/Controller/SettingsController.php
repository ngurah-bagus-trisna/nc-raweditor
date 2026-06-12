<?php

declare(strict_types=1);

namespace OCA\RawEditor\Controller;

use OCA\RawEditor\AppInfo\Application;
use OCA\RawEditor\Service\SettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class SettingsController extends Controller {
	public function __construct(
		IRequest $request,
		private readonly SettingsService $settingsService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	public function getFolders(): DataResponse {
		return new DataResponse($this->settingsService->getSettings());
	}

	#[NoAdminRequired]
	public function updateFolders(): DataResponse {
		$body = $this->request->getParams();
		if (isset($body['scanMode'])) {
			$this->settingsService->setScanMode((string)$body['scanMode']);
		}
		if (isset($body['folderIds']) && is_array($body['folderIds'])) {
			$this->settingsService->setFolderIds(array_map('intval', $body['folderIds']));
		}
		return new DataResponse($this->settingsService->getSettings());
	}
}
