<?php

declare(strict_types=1);

namespace OCA\RawEditor\Service;

class UtilsService {
	public function isFunctionEnabled(string $functionName): bool {
		if (!function_exists($functionName)) {
			return false;
		}
		$disabled = explode(',', ini_get('disable_functions') ?: '');
		$disabled = array_map('trim', $disabled);
		return !in_array($functionName, $disabled, true);
	}

	public static function isRafFile(string $name): bool {
		return strcasecmp(pathinfo($name, PATHINFO_EXTENSION), 'raf') === 0;
	}
}
