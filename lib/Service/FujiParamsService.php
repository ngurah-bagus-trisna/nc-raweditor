<?php

declare(strict_types=1);

namespace OCA\RawEditor\Service;

class FujiParamsService {
	public const DEFAULT_PARAMS = [
		'wb_mode' => 'auto',
		'wb_kelvin' => 5500,
		'wb_shift_r' => 0,
		'wb_shift_b' => 0,
		'dynamic_range' => '100',
		'dr_priority' => 'off',
		'exp_shift' => 0.0,
		'bright' => 1.0,
		'highlight_tone' => 0,
		'shadow_tone' => 0,
		'highlight' => 0,
		'shadow_shift' => 0,
		'color' => 0,
		'saturation' => 1.0,
		'color_chrome' => 'off',
		'color_chrome_blue' => 'off',
		'clarity' => 0,
		'grain' => 'off',
		'grain_size' => 'small',
		'use_camera_wb' => true,
	];

	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	public function sanitize(array $params): array {
		$merged = array_merge(self::DEFAULT_PARAMS, $params);

		$merged['wb_kelvin'] = max(2500, min(10000, (int)$merged['wb_kelvin']));
		$merged['wb_shift_r'] = max(-9, min(9, (int)$merged['wb_shift_r']));
		$merged['wb_shift_b'] = max(-9, min(9, (int)$merged['wb_shift_b']));
		$merged['exp_shift'] = max(-3.0, min(3.0, (float)$merged['exp_shift']));
		$merged['bright'] = max(0.5, min(2.0, (float)$merged['bright']));
		$merged['saturation'] = max(0.0, min(2.0, (float)$merged['saturation']));

		foreach (['highlight_tone', 'shadow_tone', 'highlight', 'shadow_shift', 'color', 'clarity'] as $key) {
			$merged[$key] = max(-100, min(100, (int)$merged[$key]));
		}

		$merged['shadow_shift'] = (int)($merged['shadow_tone'] ?? $merged['shadow_shift']);
		$merged['highlight'] = (int)($merged['highlight_tone'] ?? $merged['highlight']);

		foreach (['color_chrome', 'color_chrome_blue', 'grain'] as $key) {
			$val = (string)$merged[$key];
			$merged[$key] = in_array($val, ['off', 'weak', 'strong'], true) ? $val : 'off';
		}

		$merged['grain_size'] = ($merged['grain_size'] ?? 'small') === 'large' ? 'large' : 'small';
		$merged['dynamic_range'] = in_array((string)$merged['dynamic_range'], ['100', '200', '400', 'auto'], true)
			? (string)$merged['dynamic_range'] : '100';
		$merged['dr_priority'] = in_array((string)$merged['dr_priority'], ['off', 'auto', 'weak', 'strong'], true)
			? (string)$merged['dr_priority'] : 'off';

		$merged['use_camera_wb'] = in_array((string)$merged['wb_mode'], ['auto', 'auto_white', 'auto_ambiance'], true);

		return $merged;
	}
}
