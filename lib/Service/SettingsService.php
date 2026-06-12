<?php

declare(strict_types=1);

namespace OCA\RawEditor\Service;

use OCA\RawEditor\AppInfo\Application;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IUserSession;

class SettingsService {
	public const SCAN_MODE_ALL = 'all';
	public const SCAN_MODE_SELECTED = 'selected';

	private const CONFIG_KEY_FOLDERS = 'gallery_folders';
	private const CONFIG_KEY_SCAN_MODE = 'scan_mode';

	public function __construct(
		private readonly IConfig $config,
		private readonly IUserSession $userSession,
		private readonly IRootFolder $rootFolder,
	) {
	}

	public function getUserId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new \RuntimeException('User not logged in');
		}
		return $user->getUID();
	}

	public function getScanMode(): string {
		return $this->getScanModeForUser($this->getUserId());
	}

	public function getScanModeForUser(string $uid): string {
		$mode = $this->config->getUserValue($uid, Application::APP_ID, self::CONFIG_KEY_SCAN_MODE, self::SCAN_MODE_ALL);
		return $mode === self::SCAN_MODE_SELECTED ? self::SCAN_MODE_SELECTED : self::SCAN_MODE_ALL;
	}

	public function setScanMode(string $mode): void {
		$uid = $this->getUserId();
		$valid = $mode === self::SCAN_MODE_SELECTED ? self::SCAN_MODE_SELECTED : self::SCAN_MODE_ALL;
		$this->config->setUserValue($uid, Application::APP_ID, self::CONFIG_KEY_SCAN_MODE, $valid);
	}

	public function scansAllFolders(): bool {
		return $this->scansAllFoldersForUser($this->getUserId());
	}

	public function scansAllFoldersForUser(string $uid): bool {
		return $this->getScanModeForUser($uid) === self::SCAN_MODE_ALL;
	}

	/**
	 * @return int[]
	 */
	public function getFolderIds(): array {
		return $this->getFolderIdsForUser($this->getUserId());
	}

	/**
	 * @return int[]
	 */
	public function getFolderIdsForUser(string $uid): array {
		if ($this->scansAllFoldersForUser($uid)) {
			return [];
		}
		$raw = $this->config->getUserValue($uid, Application::APP_ID, self::CONFIG_KEY_FOLDERS, '[]');
		$ids = json_decode($raw, true);
		if (!is_array($ids)) {
			return [];
		}
		return array_values(array_filter(array_map('intval', $ids)));
	}

	/**
	 * @return array{scanMode: string, folders: array<int, array{id: int, path: string, name: string}>}
	 */
	public function getSettings(): array {
		return [
			'scanMode' => $this->getScanMode(),
			'folders' => $this->getFoldersWithInfo(),
		];
	}

	/**
	 * @param int[]|null $folderIds
	 * @return array<int, array{id: int, path: string, name: string}>
	 */
	public function getFoldersWithInfo(?array $folderIds = null): array {
		$folderIds ??= $this->getFolderIds();
		$uid = $this->getUserId();
		$userFolder = $this->rootFolder->getUserFolder($uid);
		$result = [];

		foreach ($folderIds as $folderId) {
			try {
				$node = $userFolder->getFirstNodeById($folderId);
				if ($node instanceof Folder) {
					$result[] = [
						'id' => $folderId,
						'path' => $userFolder->getRelativePath($node->getPath()),
						'name' => $node->getName(),
					];
				}
			} catch (NotFoundException) {
				continue;
			}
		}

		return $result;
	}

	/**
	 * @param int[] $folderIds
	 */
	public function setFolderIds(array $folderIds): void {
		$uid = $this->getUserId();
		$userFolder = $this->rootFolder->getUserFolder($uid);
		$valid = [];

		foreach ($folderIds as $folderId) {
			try {
				$node = $userFolder->getFirstNodeById((int)$folderId);
				if ($node instanceof Folder) {
					$valid[] = (int)$folderId;
				}
			} catch (NotFoundException) {
				continue;
			}
		}

		$this->config->setUserValue(
			$uid,
			Application::APP_ID,
			self::CONFIG_KEY_FOLDERS,
			json_encode(array_values(array_unique($valid)))
		);
	}

	public function getUserRootFolder(): Folder {
		return $this->getUserRootFolderFor($this->getUserId());
	}

	public function getUserRootFolderFor(string $uid): Folder {
		return $this->rootFolder->getUserFolder($uid);
	}

	public function markGalleryActive(string $uid): void {
		$this->config->setUserValue($uid, Application::APP_ID, 'gallery_active', '1');
	}
}
