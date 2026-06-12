<?php

declare(strict_types=1);

namespace OCA\RawEditor\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getId()
 * @method void setId(int $id)
 * @method string getOwner()
 * @method void setOwner(string $owner)
 * @method string getName()
 * @method void setName(string $name)
 * @method array getParams()
 * @method void setParams(array $params)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method int getUpdatedAt()
 * @method void setUpdatedAt(int $updatedAt)
 */
class Preset extends Entity {
	protected $owner;
	protected $name;
	protected $params;
	protected $createdAt;
	protected $updatedAt;

	public function __construct() {
		$this->addType('createdAt', 'integer');
		$this->addType('updatedAt', 'integer');
		$this->addType('params', 'json');
	}
}
