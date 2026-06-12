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
			$existing->setMtime($entity->getMtime());
			$existing->setSize($entity->getSize());
			$this->update($existing);
		} catch (DoesNotExistException) {
			$this->insert($entity);
		}
	}
}
