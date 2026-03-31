<?php declare(strict_types=1);

namespace Kenod\IsdocExporter\Tests;

use Kenod\IsdocExporter\AddressParser;
use PHPUnit\Framework\TestCase;

final class AddressParserTest extends TestCase
{
	public function testSimpleStreetWithNumber(): void
	{
		$result = AddressParser::parseStreetAndNumber('Václavské náměstí 123');

		self::assertSame('Václavské náměstí', $result['street']);
		self::assertSame('123', $result['number']);
	}

	public function testStreetWithCompoundNumber(): void
	{
		$result = AddressParser::parseStreetAndNumber('Ulice 123/45');

		self::assertSame('Ulice', $result['street']);
		self::assertSame('123/45', $result['number']);
	}

	public function testStreetWithCp(): void
	{
		$result = AddressParser::parseStreetAndNumber('Ulice čp. 123');

		self::assertSame('Ulice', $result['street']);
		self::assertSame('123', $result['number']);
	}

	public function testStreetWithoutNumber(): void
	{
		$result = AddressParser::parseStreetAndNumber('Náměstí Míru');

		self::assertSame('Náměstí Míru', $result['street']);
		self::assertSame('', $result['number']);
	}

	public function testEmptyString(): void
	{
		$result = AddressParser::parseStreetAndNumber('');

		self::assertSame('', $result['street']);
		self::assertSame('', $result['number']);
	}

	public function testTrimsWhitespace(): void
	{
		$result = AddressParser::parseStreetAndNumber('  Ulice 5  ');

		self::assertSame('Ulice', $result['street']);
		self::assertSame('5', $result['number']);
	}
}
