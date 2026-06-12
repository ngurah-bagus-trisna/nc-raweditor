<?php

declare(strict_types=1);

namespace OCA\RawEditor\Service;

use OCA\RawEditor\AppInfo\Application;
use Psr\Log\LoggerInterface;

class PythonSetupService {
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly UtilsService $utils,
	) {
	}

	public function isReady(): bool {
		$appPath = $this->getAppPath();
		$pythonBin = $appPath . '/.venv/bin/python3';
		return is_file($pythonBin) && is_executable($pythonBin);
	}

	/**
	 * @return array{success: bool, message: string, ready: bool}
	 */
	public function setup(): array {
		if ($this->isReady()) {
			return ['success' => true, 'message' => 'Python environment already set up', 'ready' => true];
		}

		if (!$this->utils->isFunctionEnabled('exec')) {
			$msg = 'PHP exec() is disabled — cannot set up Python environment';
			$this->logger->error($msg);
			return ['success' => false, 'message' => $msg, 'ready' => false];
		}

		$appPath = $this->getAppPath();
		$venvPath = $appPath . '/.venv';

		exec('which python3 2>/dev/null', $whichOutput, $rc);
		if ($rc !== 0) {
			$msg = 'python3 not found on system — please install Python 3';
			$this->logger->error($msg);
			return ['success' => false, 'message' => $msg, 'ready' => false];
		}

		exec('python3 -m venv ' . escapeshellarg($venvPath) . ' 2>&1', $output, $rc);
		if ($rc !== 0) {
			$msg = 'Failed to create Python venv: ' . implode("\n", $output);
			$this->logger->error($msg);
			return ['success' => false, 'message' => $msg, 'ready' => false];
		}

		$pipBin = $venvPath . '/bin/pip';
		$requirementsPath = $appPath . '/python/requirements.txt';

		if (!is_file($requirementsPath)) {
			$msg = 'python/requirements.txt not found';
			$this->logger->error($msg);
			return ['success' => false, 'message' => $msg, 'ready' => false];
		}

		exec(
			escapeshellarg($pipBin) . ' install --quiet -r ' . escapeshellarg($requirementsPath) . ' 2>&1',
			$pipOutput,
			$pipRc
		);

		if ($pipRc !== 0) {
			$msg = 'Failed to install Python packages: ' . implode("\n", $pipOutput);
			$this->logger->error($msg);
			return ['success' => false, 'message' => $msg, 'ready' => false];
		}

		$this->logger->info('Python environment set up successfully');
		return ['success' => true, 'message' => 'Python environment set up successfully', 'ready' => true];
	}

	private function getAppPath(): string {
		return \OC::$SERVERROOT . '/apps/' . Application::APP_ID;
	}
}
