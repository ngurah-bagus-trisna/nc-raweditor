<?php

declare(strict_types=1);

namespace OCA\RawEditor\Service;

use OCA\RawEditor\Db\RawEdit;
use OCA\RawEditor\Db\RawEditMapper;

class EditService {
	public function __construct(
		private readonly RawFileService $rawFileService,
		private readonly RawEditMapper $rawEditMapper,
		private readonly MetadataService $metadataService,
		private readonly FujiParamsService $fujiParamsService,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getParams(int $fileId): array {
		$file = $this->rawFileService->getFileById($fileId);
		$owner = $this->rawFileService->getUserId();
		$edit = $this->rawEditMapper->findByFileAndOwnerOrNull($fileId, $owner);

		if ($edit === null) {
			$cameraParams = $this->metadataService->getCameraParams($file);
			return $this->fujiParamsService->sanitize($cameraParams);
		}

		return $this->fujiParamsService->sanitize($edit->getParams());
	}

	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	public function saveParams(int $fileId, array $params): array {
		$this->rawFileService->getFileById($fileId);
		$owner = $this->rawFileService->getUserId();
		$merged = $this->fujiParamsService->sanitize($params);

		$edit = $this->rawEditMapper->findByFileAndOwnerOrNull($fileId, $owner);
		if ($edit === null) {
			$edit = new RawEdit();
			$edit->setFileid($fileId);
			$edit->setOwner($owner);
		}
		$edit->setParams($merged);
		$edit->setUpdatedAt(time());

		if ($edit->getId() === null) {
			$this->rawEditMapper->insert($edit);
		} else {
			$this->rawEditMapper->update($edit);
		}

		return $merged;
	}

	public function hasSavedEdits(int $fileId): bool {
		$owner = $this->rawFileService->getUserId();
		return $this->rawEditMapper->findByFileAndOwnerOrNull($fileId, $owner) !== null;
	}
}
