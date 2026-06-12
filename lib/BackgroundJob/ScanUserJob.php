<?php

declare(strict_types=1);

namespace OCA\RawEditor\BackgroundJob;

use OCA\RawEditor\Service\ScanService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;

class ScanUserJob extends QueuedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private readonly ScanService $scanService,
	) {
		parent::__construct($timeFactory);
	}

	protected function run($argument): void {
		$uid = is_array($argument) ? (string)($argument['uid'] ?? '') : '';
		if ($uid === '') {
			return;
		}

		$this->scanService->runMaintenance($uid, 40);
	}
}
