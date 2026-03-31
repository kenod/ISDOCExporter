<?php declare(strict_types=1);

namespace Kenod\IsdocExporter;

use function preg_match;
use function str_replace;
use function trim;

final class AddressParser
{
	/**
	 * @return array{street: string, number: string}
	 */
	public static function parseStreetAndNumber(string $street): array
	{
		$street = trim($street);

		if (preg_match('/^(.+?)\s+((?:č\.?p\.?\s*)?[\d]+(?:\/[\d]+)?)$/u', $street, $matches) === 1) {
			return [
				'street' => trim($matches[1]),
				'number' => trim(str_replace(['čp.', 'č.p.', 'č. p.'], '', $matches[2])),
			];
		}

		return [
			'street' => $street,
			'number' => '',
		];
	}
}
