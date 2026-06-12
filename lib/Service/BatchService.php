<?php

declare(strict_types=1);

namespace OCA\RawEditor\Service;

class BatchService {
	public function __construct(
		private readonly RawFileService $rawFileService,
		private readonly ExportService $exportService,
		private readonly DngService $dngService,
		private readonly PresetService $presetService,
		private readonly EditService $editService,
	) {
	}

	/**
	 * @param int[] $fileIds
	 * @return array{exported: int, results: array, errors: array}
	 */
	public function exportFiles(array $fileIds, int $quality = 98, bool $overwrite = false, ?int $presetId = null): array {
		$params = null;
		if ($presetId !== null) {
			$params = $this->presetService->getParams($presetId);
		}

		$exported = 0;
		$results = [];
		$errors = [];

		foreach ($fileIds as $fileId) {
			try {
				$file = $this->rawFileService->getFileById((int)$fileId);
				$fileParams = $params;
				if ($fileParams === null) {
					$fileParams = $this->editService->getParams((int)$fileId);
				}
				$result = $this->exportService->exportToJpeg($file, $quality, $overwrite, $fileParams);
				$results[] = $result;
				$exported++;
			} catch (\Exception $e) {
				$errors[] = ['fileId' => (int)$fileId, 'error' => $e->getMessage()];
			}
		}

		return ['exported' => $exported, 'results' => $results, 'errors' => $errors];
	}

	/**
	 * @param int[] $fileIds
	 * @return array{converted: int, results: array, errors: array}
	 */
	public function convertToDng(array $fileIds, bool $overwrite = false): array {
		$converted = 0;
		$results = [];
		$errors = [];

		foreach ($fileIds as $fileId) {
			try {
				$file = $this->rawFileService->getFileById((int)$fileId);
				$result = $this->dngService->convertToDng($file, $overwrite);
				$results[] = $result;
				$converted++;
			} catch (\Exception $e) {
				$errors[] = ['fileId' => (int)$fileId, 'error' => $e->getMessage()];
			}
		}

		return ['converted' => $converted, 'results' => $results, 'errors' => $errors];
	}
}
