<?php

declare(strict_types=1);

namespace OCA\RawEditor\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version004Date20250612140000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('raweditor_files')) {
			$table = $schema->getTable('raweditor_files');
			if (!$table->hasColumn('thumb_ready')) {
				$table->addColumn('thumb_ready', 'smallint', [
					'notnull' => true,
					'default' => 0,
				]);
				$table->addIndex(['owner', 'thumb_ready'], 'raweditor_files_owner_thumb_idx');
			}
		}

		return $schema;
	}
}
