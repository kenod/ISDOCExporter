<?php declare(strict_types=1);

namespace Kenod\IsdocExporter\Tests;

use Kenod\IsdocExporter\InvoiceItem;
use PHPUnit\Framework\TestCase;

final class InvoiceItemTest extends TestCase
{
	public function testConstructor(): void
	{
		$item = new InvoiceItem('Web development', 1.0, 'pcs', 10000.0, 12100.0, 21.0);

		self::assertSame('Web development', $item->getName());
		self::assertSame(1.0, $item->getQuantity());
		self::assertSame('pcs', $item->getUnit());
		self::assertSame(10000.0, $item->getUnitPrice());
		self::assertSame(12100.0, $item->getUnitPriceWithVat());
		self::assertSame(21.0, $item->getVatRate());
		self::assertSame('', $item->getNote());
	}

	public function testConstructorWithNote(): void
	{
		$item = new InvoiceItem('Service', 2.0, 'hrs', 500.0, 605.0, 21.0, 'Appendix text');

		self::assertSame('Appendix text', $item->getNote());
	}

	public function testFluentSetters(): void
	{
		$item = new InvoiceItem('Original', 1.0, 'pcs', 100.0, 121.0, 21.0);
		$result = $item
			->setName('Updated')
			->setQuantity(5.0)
			->setUnit('hrs')
			->setUnitPrice(200.0)
			->setUnitPriceWithVat(242.0)
			->setVatRate(12.0)
			->setNote('A note');

		self::assertSame($item, $result);
		self::assertSame('Updated', $item->getName());
		self::assertSame(5.0, $item->getQuantity());
		self::assertSame('hrs', $item->getUnit());
		self::assertSame(200.0, $item->getUnitPrice());
		self::assertSame(242.0, $item->getUnitPriceWithVat());
		self::assertSame(12.0, $item->getVatRate());
		self::assertSame('A note', $item->getNote());
	}

	public function testLineCalculations(): void
	{
		$item = new InvoiceItem('Item', 3.0, 'pcs', 100.0, 121.0, 21.0);

		self::assertSame(300.0, $item->getLineTotal());
		self::assertSame(363.0, $item->getLineTotalWithVat());
		self::assertSame(63.0, $item->getLineTaxAmount());
	}

	public function testLineTotalSingleQuantity(): void
	{
		$item = new InvoiceItem('Item', 1.0, 'pcs', 1000.0, 1210.0, 21.0);

		self::assertSame(1000.0, $item->getLineTotal());
		self::assertSame(1210.0, $item->getLineTotalWithVat());
		self::assertSame(210.0, $item->getLineTaxAmount());
	}
}
