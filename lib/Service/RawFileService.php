<?php

declare(strict_types=1);

namespace OCA\RawEditor\Service;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IUserSession;

class RawFileService {
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
	) {
	}

	public function getUserId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new \RuntimeException('User not logged in');
		}
		return $user->getUID();
	}

	public function getUserFolder(): Folder {
		return $this->getUserFolderFor($this->getUserId());
	}

	public function getUserFolderFor(string $uid): Folder {
		return $this->rootFolder->getUserFolder($uid);
	}

	public function getFileById(int $fileId): File {
		return $this->getFileByIdFor($fileId, $this->getUserId());
	}

	public function getFileByIdFor(int $fileId, string $uid): File {
		$userFolder = $this->getUserFolderFor($uid);
		$node = $userFolder->getFirstNodeById($fileId);
		if ($node === null || !($node instanceof File)) {
			throw new NotFoundException('File not found');
		}
		if (!UtilsService::isRafFile($node->getName())) {
			throw new NotFoundException('Not a RAF file');
		}
		return $node;
	}

	public function getFolderById(int $folderId): Folder {
		return $this->getFolderByIdFor($folderId, $this->getUserId());
	}

	public function getFolderByIdFor(int $folderId, string $uid): Folder {
		$userFolder = $this->getUserFolderFor($uid);
		$node = $userFolder->getFirstNodeById($folderId);
		if ($node === null || !($node instanceof Folder)) {
			throw new NotFoundException('Folder not found');
		}
		return $node;
	}

	/**
	 * Resolve a readable local path for the file, copying to temp if needed.
	 */
	public function getReadablePath(File $file): string {
		$storage = $file->getStorage();
		$internalPath = $file->getInternalPath();

		if (method_exists($storage, 'getLocalFile')) {
			$local = $storage->getLocalFile($internalPath);
			if ($local !== false && is_readable($local)) {
				return $local;
			}
		}

		$temp = sys_get_temp_dir() . '/raweditor_' . $file->getId() . '_' . $file->getMTime() . '.raf';
		if (!is_file($temp)) {
			$content = $file->fopen('r');
			if ($content === false) {
				throw new NotPermittedException('Cannot read file');
			}
			$dest = fopen($temp, 'w');
			if ($dest === false) {
				fclose($content);
				throw new \RuntimeException('Cannot create temp file');
			}
			stream_copy_to_stream($content, $dest);
			fclose($content);
			fclose($dest);
		}
		return $temp;
	}

	/**
	 * @return array{fileid: int, name: string, path: string, mtime: int, size: int, thumbReady: bool}
	 */
	public function fileToArray(File $file, bool $thumbReady = true): array {
		$userFolder = $this->getUserFolder();
		return $this->fileToArrayFor($file, $userFolder, $thumbReady);
	}

	/**
	 * @return array{fileid: int, name: string, path: string, mtime: int, size: int, thumbReady: bool}
	 */
	public function fileToArrayFor(File $file, Folder $userFolder, bool $thumbReady = true): array {
		return [
			'fileid' => $file->getId(),
			'name' => $file->getName(),
			'path' => $userFolder->getRelativePath($file->getPath()) ?? $file->getName(),
			'mtime' => $file->getMTime(),
			'size' => $file->getSize(),
			'thumbReady' => $thumbReady,
		];
	}
}
