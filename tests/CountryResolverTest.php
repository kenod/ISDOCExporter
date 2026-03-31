<?php declare(strict_types=1);

namespace Kenod\IsdocExporter\Tests;

use Kenod\IsdocExporter\CountryResolver;
use PHPUnit\Framework\TestCase;

final class CountryResolverTest extends TestCase
{
	public function testCzechRepublic(): void
	{
		self::assertSame('CZ', CountryResolver::getCountryCode('Česká republika'));
		self::assertSame('CZ', CountryResolver::getCountryCode('Czech Republic'));
		self::assertSame('CZ', CountryResolver::getCountryCode('Czechia'));
	}

	public function testSlovakia(): void
	{
		self::assertSame('SK', CountryResolver::getCountryCode('Slovensko'));
		self::assertSame('SK', CountryResolver::getCountryCode('Slovakia'));
		self::assertSame('SK', CountryResolver::getCountryCode('Slovenská republika'));
	}

	public function testOtherCountries(): void
	{
		self::assertSame('DE', CountryResolver::getCountryCode('Německo'));
		self::assertSame('DE', CountryResolver::getCountryCode('Germany'));
		self::assertSame('AT', CountryResolver::getCountryCode('Austria'));
		self::assertSame('PL', CountryResolver::getCountryCode('Poland'));
	}

	public function testUnknownCountryReturnsDefault(): void
	{
		self::assertSame('CZ', CountryResolver::getCountryCode('Neznámá země'));
	}

	public function testCustomDefault(): void
	{
		self::assertSame('XX', CountryResolver::getCountryCode('Neznámá země', 'XX'));
	}
}
