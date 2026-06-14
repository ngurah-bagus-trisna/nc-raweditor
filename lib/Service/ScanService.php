<?php

declare(strict_types=1);

namespace OCA\RawEditor\Service;

use OCA\RawEditor\AppInfo\Application;
use OCA\RawEditor\BackgroundJob\ScanUserJob;
use OCA\RawEditor\Db\RawFile;
use OCA\RawEditor\Db\RawFileMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\BackgroundJob\IJobList;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class ScanService {
	private const CONFIG_SCAN_STATE = 'scan_state';
	private const CONFIG_SCAN_LAST_AT = 'scan_last_at';
	private const CONFIG_SCAN_QUEUED_AT = 'scan_queued_at';
	private const CONFIG_SCAN_LAST_RESULT = 'scan_last_result';

	/** @var string[] */
	private const RAF_MIMES = [
		'image/x-dcraw',
		'image/x-fuji-raf',
		'image/x-raw',
	];

	public function __construct(
		private readonly SettingsService $settingsService,
		private readonly RawFileService $rawFileService,
		private readonly RawFileMapper $rawFileMapper,
		private readonly PreviewService $previewService,
		private readonly IConfig $config,
		private readonly IJobList $jobList,
		private readonly IUserManager $userManager,
		private readonly LoggerInterface $logger,
	) {
	}

	public function markGalleryActive(string $uid): void {
		$this->settingsService->markGalleryActive($uid);
	}

	public function queueUserScan(string $uid): void {
		$queuedAt = (int)$this->config->getUserValue($uid, Application::APP_ID, self::CONFIG_SCAN_QUEUED_AT, '0');
		if (time() - $queuedAt < 90) {
			return;
		}

		$this->config->setUserValue($uid, Application::APP_ID, self::CONFIG_SCAN_QUEUED_AT, (string)time());
		$this->jobList->add(ScanUserJob::class, ['uid' => $uid]);
	}

	/**
	 * @return array{state: string, lastAt: int, thumbsPending: int, total: int, lastScan: array}
	 */
	public function getStatus(string $uid): array {
		$state = $this->config->getUserValue($uid, Application::APP_ID, self::CONFIG_SCAN_STATE, 'idle');
		$lastAt = (int)$this->config->getUserValue($uid, Application::APP_ID, self::CONFIG_SCAN_LAST_AT, '0');
		$lastScanRaw = $this->config->getUserValue($uid, Application::APP_ID, self::CONFIG_SCAN_LAST_RESULT, '{}');
		$lastScan = json_decode($lastScanRaw, true);
		if (!is_array($lastScan)) {
			$lastScan = [];
		}

		return [
			'state' => $state,
			'lastAt' => $lastAt,
			'thumbsPending' => $this->rawFileMapper->countThumbsPending($uid),
			'total' => $this->rawFileMapper->countByOwner($uid),
			'lastScan' => $lastScan,
		];
	}

	public function runMaintenance(string $uid, int $thumbBatchSize = 30): void {
		$this->setState($uid, 'scanning');

		try {
			$scanResult = $this->incrementalScan($uid);
			$this->setState($uid, 'warming');
			$warmed = $this->warmThumbnails($uid, $thumbBatchSize);
			$pending = $this->rawFileMapper->countThumbsPending($uid);

			$this->config->setUserValue($uid, Application::APP_ID, self::CONFIG_SCAN_LAST_AT, (string)time());
			$this->config->setUserValue(
				$uid,
				Application::APP_ID,
				self::CONFIG_SCAN_LAST_RESULT,
				json_encode([
					'added' => $scanResult['added'],
					'updated' => $scanResult['updated'],
					'removed' => $scanResult['removed'],
					'warmed' => $warmed,
				])
			);
			$this->setState($uid, $pending > 0 ? 'warming' : 'idle');
		} catch (\Throwable $e) {
			$this->setState($uid, 'idle');
			$this->logger->error('RAW Editor maintenance failed for ' . $uid . ': ' . $e->getMessage(), [
				'app' => Application::APP_ID,
				'exception' => $e,
			]);
			throw $e;
		}
	}

	/**
	 * @return array{added: int, updated: int, removed: int, total: int}
	 */
	public function incrementalScan(string $uid): array {
		$seen = [];
		$added = 0;
		$updated = 0;

		foreach ($this->getScanRoots($uid) as $root) {
			$this->scanFolder($root['folder'], $root['folderId'], $uid, $seen, $added, $updated);
		}

		$removed = 0;
		foreach ($this->rawFileMapper->findAllFileIdsByOwner($uid) as $fileId) {
			if (!isset($seen[$fileId])) {
				$this->rawFileMapper->deleteByFileId($fileId);
				$removed++;
			}
		}

		return [
			'added' => $added,
			'updated' => $updated,
			'removed' => $removed,
			'total' => count($seen),
		];
	}

	public function indexFile(string $uid, File $file): void {
		if (!$this->isFileInScope($file, $uid)) {
			return;
		}

		$folderId = $this->resolveRootFolderId($file, $uid);
		$added = 0;
		$updated = 0;
		$this->upsertFile($file, $folderId, $uid, $added, $updated);
	}

	public function isFileInScope(File $file, string $uid): bool {
		$owner = $file->getOwner();
		if ($owner === null || $owner->getUID() !== $uid) {
			return false;
		}

		if ($this->settingsService->scansAllFoldersForUser($uid)) {
			return true;
		}

		$folderIds = $this->settingsService->getFolderIdsForUser($uid);
		if ($folderIds === []) {
			return false;
		}

		$parent = $file->getParent();
		while ($parent !== null) {
			if (in_array($parent->getId(), $folderIds, true)) {
				return true;
			}
			$parent = $parent->getParent();
		}

		return false;
	}

	public function warmThumbnails(string $uid, int $limit): int {
		$userFolder = $this->rawFileService->getUserFolderFor($uid);
		$entities = $this->rawFileMapper->findWithoutThumb($uid, $limit);
		$warmed = 0;

		foreach ($entities as $entity) {
			try {
				$node = $userFolder->getFirstNodeById($entity->getFileid());
				if (!($node instanceof File) || !UtilsService::isRafFile($node->getName())) {
					$this->rawFileMapper->deleteByFileId((int)$entity->getFileid());
					continue;
				}

				$this->previewService->warmThumb($node);
				$this->rawFileMapper->markThumbReady((int)$entity->getFileid());
				$warmed++;
			} catch (\Throwable $e) {
				$this->logger->warning('RAW Editor thumb warm failed for file ' . $entity->getFileid() . ': ' . $e->getMessage(), [
					'app' => Application::APP_ID,
				]);
			}
		}

		return $warmed;
	}

	/**
	 * @return string[]
	 */
	public function getUsersToMaintain(): array {
		$uids = $this->rawFileMapper->findDistinctOwners();

		foreach ($this->userManager->search('') as $user) {
			$uid = $user->getUID();
			if ($this->config->getUserValue($uid, Application::APP_ID, 'gallery_active', '0') === '1') {
				$uids[] = $uid;
			}
		}

		return array_values(array_unique($uids));
	}

	/**
	 * @return array<int, array{folder: Folder, folderId: int}>
	 */
	private function getScanRoots(string $uid): array {
		$roots = [];

		if ($this->settingsService->scansAllFoldersForUser($uid)) {
			$roots[] = [
				'folder' => $this->settingsService->getUserRootFolderFor($uid),
				'folderId' => GalleryService::FOLDER_ID_ALL,
			];
			return $roots;
		}

		foreach ($this->settingsService->getFolderIdsForUser($uid) as $folderId) {
			try {
				$folder = $this->rawFileService->getFolderByIdFor($folderId, $uid);
				$roots[] = [
					'folder' => $folder,
					'folderId' => $folderId,
				];
			} catch (NotFoundException) {
				continue;
			}
		}

		return $roots;
	}

	/**
	 * @param array<int, true> $seen
	 */
	private function scanFolder(Folder $folder, int $rootFolderId, string $owner, array &$seen, int &$added, int &$updated): void {
		foreach ($this->findRafFilesInFolder($folder) as $node) {
			$fileId = $node->getId();
			$seen[$fileId] = true;
			$this->upsertFile($node, $rootFolderId, $owner, $added, $updated);
		}
	}

	/**
	 * @return File[]
	 */
	private function findRafFilesInFolder(Folder $folder): array {
		$found = [];
		$seenIds = [];

		foreach (self::RAF_MIMES as $mime) {
			try {
				foreach ($folder->searchByMime($mime) as $node) {
					if (!($node instanceof File) || !UtilsService::isRafFile($node->getName())) {
						continue;
					}
					$id = $node->getId();
					if (!isset($seenIds[$id])) {
						$seenIds[$id] = true;
						$found[] = $node;
					}
				}
			} catch (\Throwable $e) {
				$this->logger->warning('RAW Editor mime search failed for ' . $mime . ': ' . $e->getMessage(), [
					'app' => Application::APP_ID,
				]);
			}
		}

		if ($found !== []) {
			return $found;
		}

		return $this->findRafFilesRecursive($folder);
	}

	/**
	 * @return File[]
	 */
	private function findRafFilesRecursive(Folder $folder): array {
		$found = [];
		try {
			$listing = $folder->getDirectoryListing();
		} catch (\Throwable $e) {
			$this->logger->warning('RAW Editor folder listing failed: ' . $e->getMessage(), [
				'app' => Application::APP_ID,
			]);
			return $found;
		}

		foreach ($listing as $node) {
			if ($node instanceof Folder) {
				foreach ($this->findRafFilesRecursive($node) as $file) {
					$found[] = $file;
				}
			} elseif ($node instanceof File && UtilsService::isRafFile($node->getName())) {
				$found[] = $node;
			}
		}

		return $found;
	}

	private function upsertFile(File $node, int $rootFolderId, string $owner, int &$added, int &$updated): void {
		$fileId = $node->getId();
		$isNew = true;

		try {
			$this->rawFileMapper->findByFileId($fileId);
			$isNew = false;
		} catch (DoesNotExistException) {
		}

		$entity = new RawFile();
		$entity->setFileid($fileId);
		$entity->setOwner($owner);
		$entity->setFolderId($rootFolderId);
		$entity->setMtime($node->getMTime());
		$entity->setSize($node->getSize());
		$entity->setThumbReady(0);
		$this->rawFileMapper->upsert($entity);

		if ($isNew) {
			$added++;
		} else {
			$updated++;
		}
	}

	private function resolveRootFolderId(File $file, string $uid): int {
		if ($this->settingsService->scansAllFoldersForUser($uid)) {
			return GalleryService::FOLDER_ID_ALL;
		}

		$folderIds = $this->settingsService->getFolderIdsForUser($uid);
		$parent = $file->getParent();
		while ($parent !== null) {
			if (in_array($parent->getId(), $folderIds, true)) {
				return $parent->getId();
			}
			$parent = $parent->getParent();
		}

		return GalleryService::FOLDER_ID_ALL;
	}

	private function setState(string $uid, string $state): void {
		$this->config->setUserValue($uid, Application::APP_ID, self::CONFIG_SCAN_STATE, $state);
	}
}
