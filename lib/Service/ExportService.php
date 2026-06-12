<?php

declare(strict_types=1);

namespace OCA\RawEditor\Service;

use OCP\Files\File;
use OCP\Files\NotPermittedException;

class ExportService {
	public function __construct(
		private readonly RawFileService $rawFileService,
		private readonly PythonService $pythonService,
		private readonly EditService $editService,
	) {
	}

	/**
	 * @return array{path: string, fileid: int, name: string}
	 */
	public function exportToJpeg(File $file, int $quality = 98, bool $overwrite = false, ?array $params = null): array {
		$parent = $file->getParent();
		if ($parent === null) {
			throw new \RuntimeException('File has no parent folder');
		}

		if (!$parent->isCreatable()) {
			throw new NotPermittedException('Cannot write to folder');
		}

		$baseName = pathinfo($file->getName(), PATHINFO_FILENAME);
		$jpegName = $baseName . '.jpg';

		if ($parent->nodeExists($jpegName) && !$overwrite) {
			throw new \RuntimeException('JPEG file already exists');
		}

		if ($params === null) {
			$params = $this->editService->getParams($file->getId());
		}

		$sourcePath = $this->rawFileService->getReadablePath($file);
		$tempOut = sys_get_temp_dir() . '/raweditor_export_' . $file->getId() . '_' . time() . '.jpg';

		$scriptParams = [
			'--path' => $sourcePath,
			'--out' => $tempOut,
			'--quality' => (string)$quality,
		];

		if ($params !== null) {
			$scriptParams['--params'] = json_encode($params);
		}

		$result = $this->pythonService->run('raw_export.py', $scriptParams);
		if ($result['result_code'] !== 0 || !is_file($tempOut)) {
			throw new \RuntimeException('Export failed: ' . $result['errors']);
		}

		if ($parent->nodeExists($jpegName)) {
			$existing = $parent->get($jpegName);
			$existing->delete();
		}

		$newFile = $parent->newFile($jpegName);
		$newFile->putContent(file_get_contents($tempOut));
		@unlink($tempOut);

		$userFolder = $this->rawFileService->getUserFolder();

		return [
			'fileid' => $newFile->getId(),
			'name' => $newFile->getName(),
			'path' => $userFolder->getRelativePath($newFile->getPath()) ?? $newFile->getName(),
		];
	}
}
