<?php

declare(strict_types=1);

namespace OCA\RawEditor\Service;

use OCA\RawEditor\Db\Preset;
use OCA\RawEditor\Db\PresetMapper;

class PresetService {
	public function __construct(
		private readonly PresetMapper $presetMapper,
		private readonly RawFileService $rawFileService,
		private readonly EditService $editService,
	) {
	}

	/**
	 * @return array<int, array{id: int, name: string, params: array, updatedAt: int}>
	 */
	public function listPresets(): array {
		$owner = $this->rawFileService->getUserId();
		$presets = $this->presetMapper->findByOwner($owner);
		return array_map(static fn (Preset $p) => [
			'id' => $p->getId(),
			'name' => $p->getName(),
			'params' => $p->getParams(),
			'updatedAt' => $p->getUpdatedAt(),
		], $presets);
	}

	public function create(string $name, array $params): array {
		$owner = $this->rawFileService->getUserId();
		$merged = array_merge(EditService::DEFAULT_PARAMS, $params);
		$now = time();

		$preset = new Preset();
		$preset->setOwner($owner);
		$preset->setName(trim($name));
		$preset->setParams($merged);
		$preset->setCreatedAt($now);
		$preset->setUpdatedAt($now);
		$preset = $this->presetMapper->insert($preset);

		return [
			'id' => $preset->getId(),
			'name' => $preset->getName(),
			'params' => $preset->getParams(),
			'updatedAt' => $preset->getUpdatedAt(),
		];
	}

	public function update(int $id, ?string $name = null, ?array $params = null): array {
		$owner = $this->rawFileService->getUserId();
		$preset = $this->presetMapper->findByIdAndOwner($id, $owner);

		if ($name !== null) {
			$preset->setName(trim($name));
		}
		if ($params !== null) {
			$preset->setParams(array_merge(EditService::DEFAULT_PARAMS, $params));
		}
		$preset->setUpdatedAt(time());
		$this->presetMapper->update($preset);

		return [
			'id' => $preset->getId(),
			'name' => $preset->getName(),
			'params' => $preset->getParams(),
			'updatedAt' => $preset->getUpdatedAt(),
		];
	}

	public function delete(int $id): void {
		$owner = $this->rawFileService->getUserId();
		$preset = $this->presetMapper->findByIdAndOwner($id, $owner);
		$this->presetMapper->delete($preset);
	}

	public function getParams(int $id): array {
		$owner = $this->rawFileService->getUserId();
		$preset = $this->presetMapper->findByIdAndOwner($id, $owner);
		return $preset->getParams();
	}

	/**
	 * @param int[] $fileIds
	 * @return array{applied: int, errors: array}
	 */
	public function applyToFiles(int $presetId, array $fileIds): array {
		$params = $this->getParams($presetId);
		$applied = 0;
		$errors = [];

		foreach ($fileIds as $fileId) {
			try {
				$this->editService->saveParams((int)$fileId, $params);
				$applied++;
			} catch (\Exception $e) {
				$errors[] = ['fileId' => (int)$fileId, 'error' => $e->getMessage()];
			}
		}

		return ['applied' => $applied, 'errors' => $errors];
	}
}
