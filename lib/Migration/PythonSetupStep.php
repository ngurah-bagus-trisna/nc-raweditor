<?php

declare(strict_types=1);

namespace OCA\RawEditor\Migration;

use OCA\RawEditor\Service\PythonSetupService;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class PythonSetupStep implements IRepairStep {
	public function __construct(
		private readonly PythonSetupService $pythonSetupService,
	) {
	}

	public function getName(): string {
		return 'Set up RAW Editor Python environment';
	}

	public function run(IOutput $output): void {
		$output->info('Setting up Python environment (this may take a few minutes)...');
		$result = $this->pythonSetupService->setup();
		if (!$result['ready']) {
			$output->warning($result['message']);
			return;
		}
		$output->info($result['message']);
	}
}
