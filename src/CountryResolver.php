<?php declare(strict_types=1);

namespace Kenod\IsdocExporter;

final class CountryResolver
{
	private const array CODES = [
		'Česká republika' => 'CZ',
		'Slovensko' => 'SK',
		'Polsko' => 'PL',
		'Německo' => 'DE',
		'Rakousko' => 'AT',
		'Maďarsko' => 'HU',
		'Slovenská republika' => 'SK',
		'Czech Republic' => 'CZ',
		'Czechia' => 'CZ',
		'Slovakia' => 'SK',
		'Poland' => 'PL',
		'Germany' => 'DE',
		'Austria' => 'AT',
		'Hungary' => 'HU',
		'France' => 'FR',
		'Italy' => 'IT',
		'Spain' => 'ES',
		'Netherlands' => 'NL',
		'Belgium' => 'BE',
		'United Kingdom' => 'GB',
		'Switzerland' => 'CH',
		'Romania' => 'RO',
		'Bulgaria' => 'BG',
		'Croatia' => 'HR',
		'Slovenia' => 'SI',
		'Lithuania' => 'LT',
		'Latvia' => 'LV',
		'Estonia' => 'EE',
		'Finland' => 'FI',
		'Sweden' => 'SE',
		'Denmark' => 'DK',
		'Ireland' => 'IE',
		'Portugal' => 'PT',
		'Greece' => 'GR',
		'Luxembourg' => 'LU',
		'Malta' => 'MT',
		'Cyprus' => 'CY',
	];

	public static function getCountryCode(string $country, string $default = 'CZ'): string
	{
		return self::CODES[$country] ?? $default;
	}
}
