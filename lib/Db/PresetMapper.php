<?php

declare(strict_types=1);

namespace OCA\RawEditor\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/** @extends QBMapper<Preset> */
class PresetMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'raweditor_presets', Preset::class);
	}

	/**
	 * @return Preset[]
	 */
	public function findByOwner(string $owner): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('owner', $qb->createNamedParameter($owner)))
			->orderBy('name', 'ASC');
		return $this->findEntities($qb);
	}

	public function findByIdAndOwner(int $id, string $owner): Preset {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('owner', $qb->createNamedParameter($owner)));
		return $this->findEntity($qb);
	}

	public function findByIdAndOwnerOrNull(int $id, string $owner): ?Preset {
		try {
			return $this->findByIdAndOwner($id, $owner);
		} catch (DoesNotExistException) {
			return null;
		}
	}
}
