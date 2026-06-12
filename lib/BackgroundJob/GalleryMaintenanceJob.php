<?php

declare(strict_types=1);

namespace OCA\RawEditor\BackgroundJob;

use OCA\RawEditor\Service\ScanService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class GalleryMaintenanceJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private readonly ScanService $scanService,
	) {
		parent::__construct($time);
		$this->setInterval(5 * 60);
	}

	protected function run($argument): void {
		foreach ($this->scanService->getUsersToMaintain() as $uid) {
			try {
				$this->scanService->runMaintenance($uid, 25);
			} catch (\Throwable) {
				continue;
			}
		}
	}
}
