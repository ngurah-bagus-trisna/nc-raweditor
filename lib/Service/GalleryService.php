<?php

declare(strict_types=1);

namespace OCA\RawEditor\Service;

use OCA\RawEditor\Db\RawFileMapper;
use OCP\Files\File;
use OCP\Files\NotFoundException;

class GalleryService {
	public const FOLDER_ID_ALL = 0;

	public function __construct(
		private readonly SettingsService $settingsService,
		private readonly RawFileService $rawFileService,
		private readonly RawFileMapper $rawFileMapper,
		private readonly ScanService $scanService,
	) {
	}

	/**
	 * @return array{files: array, total: int}
	 */
	public function listFiles(int $limit = 50, int $offset = 0): array {
		$owner = $this->rawFileService->getUserId();
		$this->scanService->markGalleryActive($owner);
		$this->scanService->queueUserScan($owner);

		$total = $this->rawFileMapper->countByOwner($owner);
		if ($total === 0 && $offset === 0) {
			$this->scanService->incrementalScan($owner);
			$total = $this->rawFileMapper->countByOwner($owner);
		}

		$entities = $this->rawFileMapper->findByOwner($owner, $limit, $offset);
		$userFolder = $this->rawFileService->getUserFolder();
		$files = [];

		foreach ($entities as $entity) {
			try {
				$node = $userFolder->getFirstNodeById($entity->getFileid());
				if ($node instanceof File && UtilsService::isRafFile($node->getName())) {
					$files[] = $this->rawFileService->fileToArrayFor(
						$node,
						$userFolder,
						(int)$entity->getThumbReady() === 1
					);
				}
			} catch (NotFoundException) {
				continue;
			}
		}

		return ['files' => $files, 'total' => $total];
	}

	/**
	 * @return array{total: int, scanMode: string, folders: array}
	 */
	public function getStats(): array {
		$owner = $this->rawFileService->getUserId();
		$settings = $this->settingsService->getSettings();

		return [
			'total' => $this->rawFileMapper->countByOwner($owner),
			'scanMode' => $settings['scanMode'],
			'folders' => $settings['folders'],
		];
	}

	public function rescan(): int {
		$owner = $this->rawFileService->getUserId();
		$this->rawFileMapper->deleteByOwner($owner);
		$result = $this->scanService->incrementalScan($owner);
		$this->scanService->queueUserScan($owner);
		return $result['total'];
	}
}
