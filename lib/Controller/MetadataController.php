<?php

declare(strict_types=1);

namespace OCA\RawEditor\Controller;

use OCA\RawEditor\AppInfo\Application;
use OCA\RawEditor\Service\MetadataService;
use OCA\RawEditor\Service\RawFileService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class MetadataController extends Controller {
	public function __construct(
		IRequest $request,
		private readonly RawFileService $rawFileService,
		private readonly MetadataService $metadataService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	public function get(int $fileId): DataResponse {
		try {
			$file = $this->rawFileService->getFileById($fileId);
			$data = $this->metadataService->getMetadata($file);
			return new DataResponse($data);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}
}
