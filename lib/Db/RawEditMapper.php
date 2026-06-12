<?php

declare(strict_types=1);

namespace OCA\RawEditor\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/** @extends QBMapper<RawEdit> */
class RawEditMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'raweditor_edits', RawEdit::class);
	}

	public function findByFileAndOwner(int $fileId, string $owner): RawEdit {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileId)))
			->andWhere($qb->expr()->eq('owner', $qb->createNamedParameter($owner)));
		return $this->findEntity($qb);
	}

	public function findByFileAndOwnerOrNull(int $fileId, string $owner): ?RawEdit {
		try {
			return $this->findByFileAndOwner($fileId, $owner);
		} catch (DoesNotExistException) {
			return null;
		}
	}
}
