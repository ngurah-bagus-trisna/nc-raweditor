<?php

declare(strict_types=1);

namespace OCA\RawEditor\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getFileid()
 * @method void setFileid(int $fileid)
 * @method string getOwner()
 * @method void setOwner(string $owner)
 * @method int getFolderId()
 * @method void setFolderId(int $folderId)
 * @method int getMtime()
 * @method void setMtime(int $mtime)
 * @method int getSize()
 * @method void setSize(int $size)
 * @method int getThumbReady()
 * @method void setThumbReady(int $thumbReady)
 */
class RawFile extends Entity {
	protected $fileid;
	protected $owner;
	protected $folderId;
	protected $mtime;
	protected $size;
	protected $thumbReady;

	public function __construct() {
		$this->addType('fileid', 'integer');
		$this->addType('folderId', 'integer');
		$this->addType('mtime', 'integer');
		$this->addType('size', 'integer');
		$this->addType('thumbReady', 'integer');
	}

	public function getId(): int {
		return (int)$this->fileid;
	}

	public function setId($id): void {
		$this->fileid = (int)$id;
	}
}
