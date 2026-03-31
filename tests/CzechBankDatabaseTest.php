<?php declare(strict_types=1);

namespace Kenod\IsdocExporter\Tests;

use Kenod\IsdocExporter\CzechBankDatabase;
use PHPUnit\Framework\TestCase;

final class CzechBankDatabaseTest extends TestCase
{
	public function testGetKnownBankName(): void
	{
		self::assertSame('Komerční banka, a.s.', CzechBankDatabase::getBankName('0100'));
		self::assertSame('Česká spořitelna, a.s.', CzechBankDatabase::getBankName('0800'));
		self::assertSame('Fio banka, a.s.', CzechBankDatabase::getBankName('2010'));
	}

	public function testGetUnknownBankName(): void
	{
		self::assertSame('Unknown bank', CzechBankDatabase::getBankName('9999'));
	}

	public function testGetKnownBic(): void
	{
		self::assertSame('KOMBCZPP', CzechBankDatabase::getBic('0100'));
		self::assertSame('GIBACZPX', CzechBankDatabase::getBic('0800'));
		self::assertSame('FIOBCZPP', CzechBankDatabase::getBic('2010'));
	}

	public function testGetUnknownBic(): void
	{
		self::assertNull(CzechBankDatabase::getBic('9999'));
	}

	public function testGenerateIbanSimpleAccount(): void
	{
		$iban = CzechBankDatabase::generateIban('19-2000145399', '0800');

		self::assertStringStartsWith('CZ', $iban);
		self::assertSame(24, strlen($iban));
	}

	public function testGenerateIbanWithoutPrefix(): void
	{
		$iban = CzechBankDatabase::generateIban('2000145399', '0800');

		self::assertStringStartsWith('CZ', $iban);
		self::assertSame(24, strlen($iban));
	}

	public function testGenerateIbanKnownValue(): void
	{
		// Known test: 19-2000145399/0800 = CZ6508000000192000145399
		$iban = CzechBankDatabase::generateIban('19-2000145399', '0800');

		self::assertSame('CZ6508000000192000145399', $iban);
	}
}
