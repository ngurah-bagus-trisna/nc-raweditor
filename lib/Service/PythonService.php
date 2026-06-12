<?php

declare(strict_types=1);

namespace OCA\RawEditor\Service;

use OCA\RawEditor\AppInfo\Application;
use Psr\Log\LoggerInterface;

class PythonService {
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly UtilsService $utils,
		private readonly PythonSetupService $pythonSetup,
	) {
	}

	/**
	 * @param array<string, string> $scriptParams
	 * @return array{output: array, result_code: int, errors: string}
	 */
	public function run(string $scriptName, array $scriptParams = []): array {
		if (!$this->utils->isFunctionEnabled('exec')) {
			$msg = 'PHP exec() is not available';
			$this->logger->error($msg);
			return ['output' => [], 'result_code' => -1, 'errors' => $msg];
		}

		if (!$this->pythonSetup->isReady()) {
			$setupResult = $this->pythonSetup->setup();
			if (!$setupResult['ready']) {
				$msg = 'Python environment not ready: ' . $setupResult['message'];
				$this->logger->error($msg);
				return ['output' => [], 'result_code' => -1, 'errors' => $msg];
			}
		}

		$appPath = \OC::$SERVERROOT . '/apps/' . Application::APP_ID;
		$pythonBin = $appPath . '/.venv/bin/python3';
		$cmd = escapeshellarg($pythonBin) . ' ' . escapeshellarg($appPath . '/python/' . $scriptName);

		foreach ($scriptParams as $key => $value) {
			if ($value === '') {
				$cmd .= ' ' . escapeshellarg($key);
			} else {
				$cmd .= ' ' . escapeshellarg($key) . ' ' . escapeshellarg((string)$value);
			}
		}

		exec($cmd . ' 2>&1', $output, $resultCode);
		$errors = '';

		if ($resultCode !== 0) {
			$errors = implode("\n", $output);
		}

		return [
			'output' => $output,
			'result_code' => $resultCode,
			'errors' => $errors,
		];
	}
}
