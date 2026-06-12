<?php

declare(strict_types=1);

namespace OCA\RawEditor\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getId()
 * @method void setId(int $id)
 * @method int getFileid()
 * @method void setFileid(int $fileid)
 * @method string getOwner()
 * @method void setOwner(string $owner)
 * @method array getParams()
 * @method void setParams(array $params)
 * @method int getUpdatedAt()
 * @method void setUpdatedAt(int $updatedAt)
 */
class RawEdit extends Entity {
	protected $fileid;
	protected $owner;
	protected $params;
	protected $updatedAt;

	public function __construct() {
		$this->addType('fileid', 'integer');
		$this->addType('updatedAt', 'integer');
		$this->addType('params', 'json');
	}
}
