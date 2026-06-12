<?php

declare(strict_types=1);

namespace OCA\RawEditor\Service;

use OCP\Files\File;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;

class PreviewService {
	public function __construct(
		private readonly RawFileService $rawFileService,
		private readonly PythonService $pythonService,
		private readonly IAppData $appData,
	) {
	}

	public function getPreviewContent(File $file, string $tier = 'thumb', ?array $params = null): string {
		$cacheKey = $this->getCacheKey($file, $tier, $params);
		$folder = $this->getPreviewFolder();
		$cached = $this->getCachedFile($folder, $cacheKey);

		if ($cached !== null) {
			return $cached->getContent();
		}

		$sourcePath = $this->rawFileService->getReadablePath($file);
		$tempOut = sys_get_temp_dir() . '/raweditor_preview_' . $file->getId() . '_' . md5($cacheKey) . '.jpg';
		$script = $tier === 'thumb' ? 'raw_thumb.py' : 'raw_preview.py';

		$scriptParams = [
			'--path' => $sourcePath,
			'--out' => $tempOut,
		];

		if ($tier === 'edit') {
			$scriptParams['--max-width'] = '1920';
			$scriptParams['--quality'] = '82';
		} elseif ($tier === 'large') {
			$scriptParams['--quality'] = '90';
		}

		if ($params !== null && $tier !== 'thumb') {
			$scriptParams['--params'] = json_encode($params);
		}

		$result = $this->pythonService->run($script, $scriptParams);
		if ($result['result_code'] !== 0 || !is_file($tempOut)) {
			throw new \RuntimeException('Preview generation failed: ' . $result['errors']);
		}

		$content = file_get_contents($tempOut);
		@unlink($tempOut);

		if ($content === false) {
			throw new \RuntimeException('Failed to read preview output');
		}

		if ($folder->fileExists($cacheKey)) {
			$folder->getFile($cacheKey)->putContent($content);
		} else {
			$folder->newFile($cacheKey, $content);
		}

		return $content;
	}

	public function renderWithParams(File $file, array $params): string {
		return $this->getPreviewContent($file, 'large', $params);
	}

	private function getCacheKey(File $file, string $tier, ?array $params): string {
		$paramsHash = $params !== null ? '_' . md5(json_encode($params)) : '';
		return $file->getId() . '_' . $file->getMTime() . '_' . $tier . $paramsHash . '.jpg';
	}

	private function getCachedFile(ISimpleFolder $folder, string $cacheKey): ?ISimpleFile {
		if (!$folder->fileExists($cacheKey)) {
			return null;
		}
		return $folder->getFile($cacheKey);
	}

	private function getPreviewFolder(): ISimpleFolder {
		try {
			return $this->appData->getFolder('previews');
		} catch (NotFoundException) {
			return $this->appData->newFolder('previews');
		}
	}
}
