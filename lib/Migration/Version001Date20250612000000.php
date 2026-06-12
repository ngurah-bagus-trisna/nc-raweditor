<?php

declare(strict_types=1);

namespace OCA\RawEditor\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version001Date20250612000000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('raweditor_files')) {
			$table = $schema->createTable('raweditor_files');
			$table->addColumn('fileid', 'bigint', ['notnull' => true]);
			$table->addColumn('owner', 'string', ['notnull' => true, 'length' => 64]);
			$table->addColumn('folder_id', 'bigint', ['notnull' => true]);
			$table->addColumn('mtime', 'bigint', ['notnull' => true, 'default' => 0]);
			$table->addColumn('size', 'bigint', ['notnull' => true, 'default' => 0]);
			$table->setPrimaryKey(['fileid']);
			$table->addIndex(['owner'], 'raweditor_files_owner_idx');
			$table->addIndex(['mtime'], 'raweditor_files_mtime_idx');
		}

		if (!$schema->hasTable('raweditor_edits')) {
			$table = $schema->createTable('raweditor_edits');
			$table->addColumn('id', 'bigint', ['autoincrement' => true, 'notnull' => true]);
			$table->addColumn('fileid', 'bigint', ['notnull' => true]);
			$table->addColumn('owner', 'string', ['notnull' => true, 'length' => 64]);
			$table->addColumn('params', 'json', ['notnull' => true]);
			$table->addColumn('updated_at', 'bigint', ['notnull' => true, 'default' => 0]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['fileid', 'owner'], 'raweditor_edits_file_owner_idx');
		}

		return $schema;
	}
}
