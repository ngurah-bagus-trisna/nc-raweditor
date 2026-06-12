<?php

declare(strict_types=1);

namespace OCA\RawEditor\Controller;

use OCA\RawEditor\AppInfo\Application;
use OCA\RawEditor\Service\GalleryService;
use OCA\RawEditor\Service\RawFileService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class GalleryController extends Controller {
	public function __construct(
		IRequest $request,
		private readonly GalleryService $galleryService,
		private readonly RawFileService $rawFileService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	public function index(): DataResponse {
		$limit = (int)($this->request->getParam('limit', 50));
		$offset = (int)($this->request->getParam('offset', 0));
		return new DataResponse($this->galleryService->listFiles($limit, $offset));
	}

	#[NoAdminRequired]
	public function stats(): DataResponse {
		return new DataResponse($this->galleryService->getStats());
	}

	#[NoAdminRequired]
	public function rescan(): DataResponse {
		$count = $this->galleryService->rescan();
		return new DataResponse(['scanned' => $count]);
	}

	#[NoAdminRequired]
	public function fileInfo(int $fileId): DataResponse {
		try {
			$file = $this->rawFileService->getFileById($fileId);
			return new DataResponse($this->rawFileService->fileToArray($file));
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 404);
		}
	}
}
