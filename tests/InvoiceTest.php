<?php declare(strict_types=1);

namespace Kenod\IsdocExporter\Tests;

use Kenod\IsdocExporter\DocumentType;
use Kenod\IsdocExporter\Invoice;
use Kenod\IsdocExporter\IsdocExporter;
use PHPUnit\Framework\TestCase;

final class InvoiceTest extends TestCase
{
	public function testDefaultValues(): void
	{
		$invoice = new Invoice();

		self::assertSame(DocumentType::Invoice, $invoice->getDocumentType());
		self::assertSame('', $invoice->getNumber());
		self::assertSame('', $invoice->getIssueDate());
		self::assertSame('', $invoice->getDueDate());
		self::assertSame('', $invoice->getDeliveryDate());
		self::assertFalse($invoice->isVatPayer());
		self::assertFalse($invoice->isReverseCharge());
		self::assertSame('', $invoice->getReverseChargeType());
		self::assertSame('', $invoice->getFooterText());
		self::assertSame('', $invoice->getOrderNumber());
		self::assertSame('', $invoice->getOriginalDocumentNumber());
		self::assertFalse($invoice->isRoundTotal());
		self::assertSame('', $invoice->getIssuingSystem());
		self::assertSame('CZK', $invoice->getCurrencyCode());
		self::assertSame('', $invoice->getForeignCurrencyCode());
		self::assertSame(1.0, $invoice->getCurrencyRate());
		self::assertSame(1.0, $invoice->getRefCurrencyRate());
		self::assertSame([], $invoice->getItems());
	}

	public function testSubObjectsArePreInitialized(): void
	{
		$invoice = new Invoice();

		self::assertSame('', $invoice->supplier->getName());
		self::assertSame('', $invoice->customer->getName());
		self::assertFalse($invoice->payment->hasBankAccount());
	}

	public function testFluentSetters(): void
	{
		$invoice = new Invoice();
		$result = $invoice
			->setDocumentType(DocumentType::CreditNote)
			->setNumber('2024001')
			->setIssueDate('2024-01-15')
			->setDueDate('2024-02-15')
			->setDeliveryDate('2024-01-15')
			->setVatPayer(true)
			->setReverseCharge(true)
			->setReverseChargeType('4')
			->setFooterText('Footer')
			->setOrderNumber('OBJ-001')
			->setOriginalDocumentNumber('FV-2023-100')
			->setRoundTotal(true)
			->setIssuingSystem('TestSystem')
			->setCurrencyCode('EUR')
			->setForeignCurrencyCode('USD')
			->setCurrencyRate(25.5)
			->setRefCurrencyRate(1.0);

		self::assertSame($invoice, $result);
		self::assertSame(DocumentType::CreditNote, $invoice->getDocumentType());
		self::assertSame('2024001', $invoice->getNumber());
		self::assertTrue($invoice->isVatPayer());
		self::assertTrue($invoice->isReverseCharge());
		self::assertSame('4', $invoice->getReverseChargeType());
		self::assertSame('FV-2023-100', $invoice->getOriginalDocumentNumber());
		self::assertSame('EUR', $invoice->getCurrencyCode());
		self::assertSame('USD', $invoice->getForeignCurrencyCode());
		self::assertSame(25.5, $invoice->getCurrencyRate());
	}

	public function testAddItems(): void
	{
		$invoice = new Invoice();

		$result = $invoice
			->addItem('Item 1', 1.0, 'pcs', 100.0, 121.0, 21.0)
			->addItem('Item 2', 2.0, 'pcs', 200.0, 242.0, 21.0);

		self::assertSame($invoice, $result);
		self::assertCount(2, $invoice->getItems());
		self::assertSame('Item 1', $invoice->getItems()[0]->getName());
		self::assertSame('Item 2', $invoice->getItems()[1]->getName());
	}

	public function testSupplierCustomerFluentAccess(): void
	{
		$invoice = new Invoice();
		$invoice->supplier->setName('Supplier Ltd.')->setStreet('Street 1')->setCity('Prague')->setZip('11000');
		$invoice->customer->setName('Customer Inc.')->setStreet('Street 2')->setCity('Brno')->setZip('60200');

		self::assertSame('Supplier Ltd.', $invoice->supplier->getName());
		self::assertSame('Customer Inc.', $invoice->customer->getName());
	}

	public function testPaymentFluentAccess(): void
	{
		$invoice = new Invoice();
		$invoice->payment->setAccountNumber('123456789')->setBankCode('0100')->setVariableSymbol('2024001');

		self::assertTrue($invoice->payment->hasBankAccount());
		self::assertSame('123456789', $invoice->payment->getAccountNumber());
	}

	public function testExportReturnsExporter(): void
	{
		$invoice = new Invoice();
		$invoice->setNumber('1')->setIssueDate('2024-01-01')->setDueDate('2024-01-31');
		$invoice->supplier->setName('Supplier')->setStreet('Street 1')->setCity('Prague')->setZip('11000');
		$invoice->customer->setName('Customer')->setStreet('Street 2')->setCity('Brno')->setZip('60200');
		$invoice->addItem('Item', 1.0, 'pcs', 100.0, 100.0, 0.0);

		$exporter = $invoice->export();

		self::assertInstanceOf(IsdocExporter::class, $exporter);
	}
}
