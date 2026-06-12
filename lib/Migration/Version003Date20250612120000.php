<?php

declare(strict_types=1);

namespace OCA\RawEditor\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version003Date20250612120000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		// Params JSON schema expanded in application code; no DB column changes required.
		return null;
	}
}
