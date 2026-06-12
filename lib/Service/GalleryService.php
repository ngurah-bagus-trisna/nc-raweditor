<?php

declare(strict_types=1);

namespace OCA\RawEditor\Service;

use OCA\RawEditor\Db\RawFile;
use OCA\RawEditor\Db\RawFileMapper;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;

class GalleryService {
	public const FOLDER_ID_ALL = 0;

	public function __construct(
		private readonly SettingsService $settingsService,
		private readonly RawFileService $rawFileService,
		private readonly RawFileMapper $rawFileMapper,
	) {
	}

	/**
	 * @return array{files: array, total: int}
	 */
	public function listFiles(int $limit = 50, int $offset = 0): array {
		$owner = $this->rawFileService->getUserId();
		$entities = $this->rawFileMapper->findByOwner($owner, $limit, $offset);
		$total = $this->rawFileMapper->countByOwner($owner);
		$userFolder = $this->rawFileService->getUserFolder();
		$files = [];

		foreach ($entities as $entity) {
			try {
				$node = $userFolder->getFirstNodeById($entity->getFileid());
				if ($node instanceof File && UtilsService::isRafFile($node->getName())) {
					$files[] = $this->rawFileService->fileToArray($node);
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

		if ($this->settingsService->scansAllFolders()) {
			return $this->scanFolder(
				$this->settingsService->getUserRootFolder(),
				self::FOLDER_ID_ALL,
				$owner
			);
		}

		$count = 0;
		foreach ($this->settingsService->getFolderIds() as $folderId) {
			try {
				$folder = $this->rawFileService->getFolderById($folderId);
				$count += $this->scanFolder($folder, $folderId, $owner);
			} catch (NotFoundException) {
				continue;
			}
		}

		return $count;
	}

	private function scanFolder(Folder $folder, int $rootFolderId, string $owner): int {
		$count = 0;

		foreach ($folder->getDirectoryListing() as $node) {
			if ($node instanceof Folder) {
				$count += $this->scanFolder($node, $rootFolderId, $owner);
			} elseif ($node instanceof File && UtilsService::isRafFile($node->getName())) {
				$entity = new RawFile();
				$entity->setFileid($node->getId());
				$entity->setOwner($owner);
				$entity->setFolderId($rootFolderId);
				$entity->setMtime($node->getMTime());
				$entity->setSize($node->getSize());
				$this->rawFileMapper->upsert($entity);
				$count++;
			}
		}

		return $count;
	}
}
