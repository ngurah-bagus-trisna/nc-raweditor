<?php

declare(strict_types=1);

namespace OCA\RawEditor\Service;

use OCA\RawEditor\AppInfo\Application;
use OCP\Files\File;
use OCP\Files\NotPermittedException;

class DngService {
	public function __construct(
		private readonly RawFileService $rawFileService,
		private readonly PythonService $pythonService,
	) {
	}

	/**
	 * @return array{path: string, fileid: int, name: string}
	 */
	public function convertToDng(File $file, bool $overwrite = false): array {
		$parent = $file->getParent();
		if ($parent === null) {
			throw new \RuntimeException('File has no parent folder');
		}

		if (!$parent->isCreatable()) {
			throw new NotPermittedException('Cannot write to folder');
		}

		$baseName = pathinfo($file->getName(), PATHINFO_FILENAME);
		$dngName = $baseName . '.dng';
		$tiffName = $baseName . '.tif';

		if (!$overwrite && ($parent->nodeExists($dngName) || $parent->nodeExists($tiffName))) {
			throw new \RuntimeException('Converted file already exists');
		}

		$sourcePath = $this->rawFileService->getReadablePath($file);
		$tempOut = sys_get_temp_dir() . '/raweditor_dng_' . $file->getId() . '_' . time() . '.dng';
		$appRoot = \OC::$SERVERROOT . '/apps/' . Application::APP_ID;

		$scriptParams = [
			'--path' => $sourcePath,
			'--out' => $tempOut,
			'--app-root' => $appRoot,
		];
		if ($overwrite) {
			$scriptParams['--overwrite'] = '';
		}
		$result = $this->pythonService->run('raf_to_dng.py', $scriptParams);

		if ($result['result_code'] !== 0) {
			throw new \RuntimeException('DNG conversion failed: ' . $result['errors']);
		}

		$outputPath = trim(implode("\n", $result['output']));
		if ($outputPath === '' || !is_file($outputPath)) {
			if (is_file($tempOut)) {
				$outputPath = $tempOut;
			} else {
				throw new \RuntimeException('DNG conversion failed: no output file');
			}
		}

		$outName = basename($outputPath);
		if ($parent->nodeExists($outName)) {
			$parent->get($outName)->delete();
		}

		$newFile = $parent->newFile($outName);
		$newFile->putContent(file_get_contents($outputPath));
		@unlink($outputPath);
		if ($outputPath !== $tempOut && is_file($tempOut)) {
			@unlink($tempOut);
		}

		$userFolder = $this->rawFileService->getUserFolder();

		$ext = strtolower(pathinfo($outName, PATHINFO_EXTENSION));

		return [
			'fileid' => $newFile->getId(),
			'name' => $newFile->getName(),
			'path' => $userFolder->getRelativePath($newFile->getPath()) ?? $newFile->getName(),
			'format' => $ext === 'dng' ? 'dng' : 'tiff',
		];
	}
}
