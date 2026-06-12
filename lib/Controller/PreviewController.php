<?php

declare(strict_types=1);

namespace OCA\RawEditor\Controller;

use OCA\RawEditor\AppInfo\Application;
use OCA\RawEditor\Service\PreviewService;
use OCA\RawEditor\Service\RawFileService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\IRequest;

class PreviewController extends Controller {
	public function __construct(
		IRequest $request,
		private readonly RawFileService $rawFileService,
		private readonly PreviewService $previewService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function get(int $fileId): DataDownloadResponse|DataResponse {
		try {
			$file = $this->rawFileService->getFileById($fileId);
			$tier = $this->request->getParam('size', 'thumb');
			if (!in_array($tier, ['thumb', 'edit', 'large'], true)) {
				$tier = 'thumb';
			}
			$content = $this->previewService->getPreviewContent($file, $tier);
			$response = new DataDownloadResponse($content, 'preview.jpg', 'image/jpeg');
			$response->addHeader('Cache-Control', 'private, max-age=3600');
			return $response;
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	public function render(int $fileId): DataDownloadResponse|DataResponse {
		try {
			$file = $this->rawFileService->getFileById($fileId);
			$params = $this->request->getParams();
			unset($params['_route'], $params['fileId']);
			$content = $this->previewService->renderWithParams($file, $params);
			$response = new DataDownloadResponse($content, 'preview.jpg', 'image/jpeg');
			$response->addHeader('Cache-Control', 'no-cache');
			return $response;
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}
}
