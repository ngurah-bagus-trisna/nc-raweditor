<?php

declare(strict_types=1);

namespace OCA\RawEditor\Controller;

use OCA\RawEditor\AppInfo\Application;
use OCA\RawEditor\Service\ExportService;
use OCA\RawEditor\Service\RawFileService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class ExportController extends Controller {
	public function __construct(
		IRequest $request,
		private readonly RawFileService $rawFileService,
		private readonly ExportService $exportService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	public function export(int $fileId): DataResponse {
		try {
			$file = $this->rawFileService->getFileById($fileId);
			$params = $this->request->getParams();
			$quality = (int)($params['quality'] ?? 98);
			$overwrite = filter_var($params['overwrite'] ?? false, FILTER_VALIDATE_BOOLEAN);
			$editParams = $params['params'] ?? null;
			if (is_string($editParams)) {
				$editParams = json_decode($editParams, true);
			}

			$result = $this->exportService->exportToJpeg($file, $quality, $overwrite, $editParams);
			return new DataResponse($result);
		} catch (\Exception $e) {
			$code = str_contains($e->getMessage(), 'already exists') ? 409 : 400;
			return new DataResponse(['error' => $e->getMessage()], $code);
		}
	}
}
