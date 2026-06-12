<?php

declare(strict_types=1);

namespace OCA\RawEditor\Service;

use OCP\Files\File;

class MetadataService {
	public function __construct(
		private readonly RawFileService $rawFileService,
		private readonly PythonService $pythonService,
		private readonly FujiParamsService $fujiParamsService,
	) {
	}

	/**
	 * @return array{metadata: array<string, mixed>, params: array<string, mixed>}
	 */
	public function getMetadata(File $file): array {
		$sourcePath = $this->rawFileService->getReadablePath($file);
		$result = $this->pythonService->run('fuji_metadata.py', [
			'--path' => $sourcePath,
		]);

		if ($result['result_code'] !== 0) {
			throw new \RuntimeException('Metadata read failed: ' . $result['errors']);
		}

		$json = implode("\n", $result['output']);
		$data = json_decode($json, true);
		if (!is_array($data)) {
			throw new \RuntimeException('Invalid metadata response');
		}

		if (isset($data['error'])) {
			throw new \RuntimeException((string)$data['error']);
		}

		$params = $this->fujiParamsService->sanitize($data['params'] ?? []);

		return [
			'metadata' => $data['metadata'] ?? [],
			'params' => $params,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getCameraParams(File $file): array {
		try {
			return $this->getMetadata($file)['params'];
		} catch (\Exception) {
			return FujiParamsService::DEFAULT_PARAMS;
		}
	}
}
