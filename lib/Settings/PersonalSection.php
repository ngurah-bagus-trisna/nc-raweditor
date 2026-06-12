<?php

declare(strict_types=1);

namespace OCA\RawEditor\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class PersonalSection implements IIconSection {
	public function __construct(
		private readonly IL10N $l,
		private readonly IURLGenerator $urlGenerator,
	) {
	}

	public function getID(): string {
		return 'raweditor';
	}

	public function getName(): string {
		return $this->l->t('RAW Editor');
	}

	public function getPriority(): int {
		return 80;
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath('raweditor', 'raweditor.svg');
	}
}
