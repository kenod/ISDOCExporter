<?php declare(strict_types=1);

namespace Kenod\IsdocExporter\Tests;

use Kenod\IsdocExporter\Party;
use PHPUnit\Framework\TestCase;

final class PartyTest extends TestCase
{
	public function testFluentSetters(): void
	{
		$party = new Party();
		$result = $party
			->setName('Test s.r.o.')
			->setStreet('Main Street 123')
			->setCity('Prague')
			->setZip('110 00')
			->setCountry('Czech Republic')
			->setCompanyId('12345678')
			->setVatId('CZ12345678')
			->setRegisterInfo('Registered in CR')
			->setEmail('info@test.cz')
			->setPhone('+420123456789')
			->setWeb('https://test.cz');

		self::assertSame($party, $result);
		self::assertSame('Test s.r.o.', $party->getName());
		self::assertSame('Main Street 123', $party->getStreet());
		self::assertSame('Prague', $party->getCity());
		self::assertSame('110 00', $party->getZip());
		self::assertSame('Czech Republic', $party->getCountry());
		self::assertSame('12345678', $party->getCompanyId());
		self::assertSame('CZ12345678', $party->getVatId());
		self::assertSame('Registered in CR', $party->getRegisterInfo());
		self::assertSame('info@test.cz', $party->getEmail());
		self::assertSame('+420123456789', $party->getPhone());
		self::assertSame('https://test.cz', $party->getWeb());
	}

	public function testDefaultsAreEmpty(): void
	{
		$party = new Party();

		self::assertSame('', $party->getName());
		self::assertSame('', $party->getStreet());
		self::assertSame('', $party->getCity());
		self::assertSame('', $party->getZip());
		self::assertSame('', $party->getCountry());
		self::assertSame('', $party->getCompanyId());
		self::assertSame('', $party->getVatId());
		self::assertSame('', $party->getRegisterInfo());
		self::assertSame('', $party->getEmail());
		self::assertSame('', $party->getPhone());
		self::assertSame('', $party->getWeb());
	}
}
