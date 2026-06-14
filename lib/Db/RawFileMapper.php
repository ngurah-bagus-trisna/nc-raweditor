<?php

declare(strict_types=1);

namespace OCA\RawEditor\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/** @extends QBMapper<RawFile> */
class RawFileMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'raweditor_files', RawFile::class);
	}

	/**
	 * @return RawFile[]
	 */
	public function findByOwner(string $owner, int $limit, int $offset): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('owner', $qb->createNamedParameter($owner)))
			->orderBy('mtime', 'DESC')
			->setMaxResults($limit)
			->setFirstResult($offset);
		return $this->findEntities($qb);
	}

	public function countByOwner(string $owner): int {
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->createFunction('COUNT(*)'))
			->from($this->getTableName())
			->where($qb->expr()->eq('owner', $qb->createNamedParameter($owner)));
		$result = $qb->executeQuery();
		$count = (int)$result->fetchOne();
		$result->closeCursor();
		return $count;
	}

	public function deleteByOwner(string $owner): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('owner', $qb->createNamedParameter($owner)));
		$qb->executeStatement();
	}

	public function findByFileId(int $fileId): RawFile {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileId)));
		return $this->findEntity($qb);
	}

	public function upsert(RawFile $entity): void {
		try {
			$existing = $this->findByFileId((int)$entity->getFileid());
			$existing->setOwner($entity->getOwner());
			$existing->setFolderId($entity->getFolderId());
			if ($existing->getMtime() !== $entity->getMtime()) {
				$existing->setThumbReady(0);
			}
			$existing->setMtime($entity->getMtime());
			$existing->setSize($entity->getSize());
			$this->updateRawFile($existing);
		} catch (DoesNotExistException) {
			$this->insertRawFile($entity);
		}
	}

	private function insertRawFile(RawFile $entity): void {
		$qb = $this->db->getQueryBuilder();
		$qb->insert($this->getTableName())
			->values([
				'fileid' => $qb->createNamedParameter((int)$entity->getFileid()),
				'owner' => $qb->createNamedParameter($entity->getOwner()),
				'folder_id' => $qb->createNamedParameter((int)$entity->getFolderId()),
				'mtime' => $qb->createNamedParameter((int)$entity->getMtime()),
				'size' => $qb->createNamedParameter((int)$entity->getSize()),
				'thumb_ready' => $qb->createNamedParameter((int)($entity->getThumbReady() ?? 0)),
			]);
		$qb->executeStatement();
	}

	private function updateRawFile(RawFile $entity): void {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('owner', $qb->createNamedParameter($entity->getOwner()))
			->set('folder_id', $qb->createNamedParameter((int)$entity->getFolderId()))
			->set('mtime', $qb->createNamedParameter((int)$entity->getMtime()))
			->set('size', $qb->createNamedParameter((int)$entity->getSize()))
			->set('thumb_ready', $qb->createNamedParameter((int)$entity->getThumbReady()))
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter((int)$entity->getFileid())));
		$qb->executeStatement();
	}

	public function deleteByFileId(int $fileId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileId)));
		$qb->executeStatement();
	}

	/**
	 * @return int[]
	 */
	public function findAllFileIdsByOwner(string $owner): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('fileid')
			->from($this->getTableName())
			->where($qb->expr()->eq('owner', $qb->createNamedParameter($owner)));
		$result = $qb->executeQuery();
		$ids = [];
		while (($row = $result->fetch()) !== false) {
			$ids[] = (int)$row['fileid'];
		}
		$result->closeCursor();
		return $ids;
	}

	/**
	 * @return string[]
	 */
	public function findDistinctOwners(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->selectDistinct('owner')
			->from($this->getTableName());
		$result = $qb->executeQuery();
		$owners = [];
		while (($row = $result->fetch()) !== false) {
			$owners[] = (string)$row['owner'];
		}
		$result->closeCursor();
		return $owners;
	}

	public function countThumbsPending(string $owner): int {
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->createFunction('COUNT(*)'))
			->from($this->getTableName())
			->where($qb->expr()->eq('owner', $qb->createNamedParameter($owner)))
			->andWhere($qb->expr()->eq('thumb_ready', $qb->createNamedParameter(0)));
		$result = $qb->executeQuery();
		$count = (int)$result->fetchOne();
		$result->closeCursor();
		return $count;
	}

	/**
	 * @return RawFile[]
	 */
	public function findWithoutThumb(string $owner, int $limit): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('owner', $qb->createNamedParameter($owner)))
			->andWhere($qb->expr()->eq('thumb_ready', $qb->createNamedParameter(0)))
			->orderBy('mtime', 'DESC')
			->setMaxResults($limit);
		return $this->findEntities($qb);
	}

	public function markThumbReady(int $fileId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('thumb_ready', $qb->createNamedParameter(1))
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileId)));
		$qb->executeStatement();
	}
}
