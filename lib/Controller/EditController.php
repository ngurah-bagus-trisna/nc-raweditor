<?php

declare(strict_types=1);

namespace OCA\RawEditor\Controller;

use OCA\RawEditor\AppInfo\Application;
use OCA\RawEditor\Service\EditService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class EditController extends Controller {
	public function __construct(
		IRequest $request,
		private readonly EditService $editService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	public function get(int $fileId): DataResponse {
		try {
			return new DataResponse([
				'params' => $this->editService->getParams($fileId),
				'has_saved_edits' => $this->editService->hasSavedEdits($fileId),
			]);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	public function save(int $fileId): DataResponse {
		try {
			$params = $this->request->getParams();
			unset($params['_route'], $params['fileId']);
			return new DataResponse([
				'params' => $this->editService->saveParams($fileId, $params),
			]);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}
}
