<?php

declare(strict_types=1);

namespace OCA\RawEditor\Controller;

use OCA\RawEditor\AppInfo\Application;
use OCA\RawEditor\Service\PresetService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class PresetController extends Controller {
	public function __construct(
		IRequest $request,
		private readonly PresetService $presetService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	public function index(): DataResponse {
		return new DataResponse(['presets' => $this->presetService->listPresets()]);
	}

	#[NoAdminRequired]
	public function create(): DataResponse {
		$body = $this->request->getParams();
		$name = trim((string)($body['name'] ?? ''));
		if ($name === '') {
			return new DataResponse(['error' => 'Preset name is required'], 400);
		}
		$params = $body['params'] ?? [];
		if (!is_array($params)) {
			return new DataResponse(['error' => 'params must be an object'], 400);
		}
		return new DataResponse($this->presetService->create($name, $params));
	}

	#[NoAdminRequired]
	public function update(int $id): DataResponse {
		$body = $this->request->getParams();
		$name = isset($body['name']) ? (string)$body['name'] : null;
		$params = isset($body['params']) && is_array($body['params']) ? $body['params'] : null;
		try {
			return new DataResponse($this->presetService->update($id, $name, $params));
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 404);
		}
	}

	#[NoAdminRequired]
	public function delete(int $id): DataResponse {
		try {
			$this->presetService->delete($id);
			return new DataResponse(['success' => true]);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 404);
		}
	}

	#[NoAdminRequired]
	public function apply(int $id): DataResponse {
		$body = $this->request->getParams();
		$fileIds = $body['fileIds'] ?? [];
		if (!is_array($fileIds) || $fileIds === []) {
			return new DataResponse(['error' => 'fileIds must be a non-empty array'], 400);
		}
		try {
			return new DataResponse($this->presetService->applyToFiles($id, array_map('intval', $fileIds)));
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}
}
