<?php

declare(strict_types=1);

namespace OCA\RawEditor\Controller;

use OCA\RawEditor\AppInfo\Application;
use OCA\RawEditor\Service\BatchService;
use OCA\RawEditor\Service\EditService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class BatchController extends Controller {
	public function __construct(
		IRequest $request,
		private readonly BatchService $batchService,
		private readonly EditService $editService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	public function applyParams(): DataResponse {
		$body = $this->request->getParams();
		$fileIds = $body['fileIds'] ?? [];
		$params = $body['params'] ?? null;
		if (!is_array($fileIds) || $fileIds === []) {
			return new DataResponse(['error' => 'fileIds must be a non-empty array'], 400);
		}
		if (!is_array($params)) {
			return new DataResponse(['error' => 'params must be an object'], 400);
		}

		$applied = 0;
		$errors = [];
		foreach (array_map('intval', $fileIds) as $fileId) {
			try {
				$this->editService->saveParams($fileId, $params);
				$applied++;
			} catch (\Exception $e) {
				$errors[] = ['fileId' => $fileId, 'error' => $e->getMessage()];
			}
		}

		return new DataResponse(['applied' => $applied, 'errors' => $errors]);
	}

	#[NoAdminRequired]
	public function export(): DataResponse {
		$body = $this->request->getParams();
		$fileIds = $body['fileIds'] ?? [];
		if (!is_array($fileIds) || $fileIds === []) {
			return new DataResponse(['error' => 'fileIds must be a non-empty array'], 400);
		}
		$quality = (int)($body['quality'] ?? 98);
		$overwrite = filter_var($body['overwrite'] ?? false, FILTER_VALIDATE_BOOLEAN);
		$presetId = isset($body['presetId']) ? (int)$body['presetId'] : null;

		return new DataResponse(
			$this->batchService->exportFiles(array_map('intval', $fileIds), $quality, $overwrite, $presetId)
		);
	}

	#[NoAdminRequired]
	public function convertDng(): DataResponse {
		$body = $this->request->getParams();
		$fileIds = $body['fileIds'] ?? [];
		if (!is_array($fileIds) || $fileIds === []) {
			return new DataResponse(['error' => 'fileIds must be a non-empty array'], 400);
		}
		$overwrite = filter_var($body['overwrite'] ?? false, FILTER_VALIDATE_BOOLEAN);

		return new DataResponse(
			$this->batchService->convertToDng(array_map('intval', $fileIds), $overwrite)
		);
	}
}
