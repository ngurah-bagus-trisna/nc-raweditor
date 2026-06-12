<?php

declare(strict_types=1);

namespace OCA\RawEditor\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version002Date20250612100000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('raweditor_presets')) {
			$table = $schema->createTable('raweditor_presets');
			$table->addColumn('id', 'bigint', ['autoincrement' => true, 'notnull' => true]);
			$table->addColumn('owner', 'string', ['notnull' => true, 'length' => 64]);
			$table->addColumn('name', 'string', ['notnull' => true, 'length' => 128]);
			$table->addColumn('params', 'json', ['notnull' => true]);
			$table->addColumn('created_at', 'bigint', ['notnull' => true, 'default' => 0]);
			$table->addColumn('updated_at', 'bigint', ['notnull' => true, 'default' => 0]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['owner'], 'raweditor_presets_owner_idx');
		}

		return $schema;
	}
}
