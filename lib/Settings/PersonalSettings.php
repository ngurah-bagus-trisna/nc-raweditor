<?php

declare(strict_types=1);

namespace OCA\RawEditor\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

class PersonalSettings implements ISettings {
	public function getForm(): TemplateResponse {
		return new TemplateResponse('raweditor', 'settings/personal');
	}

	public function getSection(): string {
		return 'raweditor';
	}

	public function getPriority(): int {
		return 10;
	}
}
