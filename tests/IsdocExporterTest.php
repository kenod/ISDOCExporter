<?php declare(strict_types=1);

namespace Kenod\IsdocExporter\Tests;

use DOMDocument;
use Kenod\IsdocExporter\DocumentType;
use Kenod\IsdocExporter\Invoice;
use PHPUnit\Framework\TestCase;
use function file_exists;
use function file_get_contents;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function strlen;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

final class IsdocExporterTest extends TestCase
{
	private const XSD_PATH = __DIR__ . '/isdoc-invoice-6.0.1.xsd';

	private function assertValidIsdocXml(string $xml): void
	{
		$doc = new DOMDocument();
		$doc->loadXML($xml);

		$previousUseErrors = libxml_use_internal_errors(true);
		$valid = $doc->schemaValidate(self::XSD_PATH);
		$errors = libxml_get_errors();
		libxml_use_internal_errors($previousUseErrors);

		$messages = [];

		foreach ($errors as $error) {
			$messages[] = trim($error->message);
		}

		self::assertTrue($valid, "XSD validation failed:\n" . implode("\n", $messages));
	}

	private function createBasicInvoice(): Invoice
	{
		$invoice = new Invoice();
		$invoice->setNumber('2024001')->setIssueDate('2024-01-15')->setDueDate('2024-02-15');

		$invoice->supplier
			->setName('Supplier s.r.o.')
			->setStreet('Supplier Street 123')
			->setCity('Prague')
			->setZip('110 00')
			->setCompanyId('12345678')
			->setVatId('CZ12345678');

		$invoice->customer
			->setName('Customer a.s.')
			->setStreet('Customer Street 456/7')
			->setCity('Brno')
			->setZip('602 00')
			->setCompanyId('87654321');

		$invoice->addItem('Web development', 1.0, 'pcs', 10000.0, 12100.0, 21.0);

		return $invoice;
	}

	public function testGenerateProducesValidXml(): void
	{
		$xml = $this->createBasicInvoice()->export()->toString();

		self::assertNotEmpty($xml);
		self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
		self::assertStringContainsString('xmlns="http://isdoc.cz/namespace/2013"', $xml);
		self::assertStringContainsString('version="6.0.2"', $xml);
	}

	public function testContainsInvoiceNumber(): void
	{
		$xml = $this->createBasicInvoice()->export()->toString();

		self::assertStringContainsString('<ID>2024001</ID>', $xml);
	}

	public function testContainsSupplierInfo(): void
	{
		$xml = $this->createBasicInvoice()->export()->toString();

		self::assertStringContainsString('AccountingSupplierParty', $xml);
		self::assertStringContainsString('Supplier s.r.o.', $xml);
		self::assertStringContainsString('12345678', $xml);
		self::assertStringContainsString('CZ12345678', $xml);
	}

	public function testContainsCustomerInfo(): void
	{
		$xml = $this->createBasicInvoice()->export()->toString();

		self::assertStringContainsString('AccountingCustomerParty', $xml);
		self::assertStringContainsString('Customer a.s.', $xml);
		self::assertStringContainsString('87654321', $xml);
	}

	public function testNonVatPayerInvoice(): void
	{
		$xml = $this->createBasicInvoice()->export()->toString();

		self::assertStringContainsString('<VATApplicable>false</VATApplicable>', $xml);
	}

	public function testVatPayerInvoice(): void
	{
		$invoice = $this->createBasicInvoice();
		$invoice->setVatPayer(true)->setDeliveryDate('2024-01-15');
		$xml = $invoice->export()->toString();

		self::assertStringContainsString('<VATApplicable>true</VATApplicable>', $xml);
		self::assertStringContainsString('<TaxPointDate>2024-01-15</TaxPointDate>', $xml);
	}

	public function testReverseChargeInvoice(): void
	{
		$invoice = $this->createBasicInvoice();
		$invoice->setVatPayer(true)->setDeliveryDate('2024-01-15');
		$invoice->setReverseCharge(true)->setReverseChargeType('4');
		$xml = $invoice->export()->toString();

		self::assertStringContainsString('LocalReverseCharge', $xml);
		self::assertStringContainsString('LocalReverseChargeCode', $xml);
		self::assertStringContainsString('LocalReverseChargeFlag', $xml);
	}

	public function testPaymentInfoIncluded(): void
	{
		$invoice = $this->createBasicInvoice();
		$invoice->payment
			->setAccountNumber('19-2000145399')
			->setBankCode('0800')
			->setVariableSymbol('2024001')
			->setConstantSymbol('0308');
		$xml = $invoice->export()->toString();

		self::assertStringContainsString('<BankCode>0800</BankCode>', $xml);
		self::assertStringContainsString('<VariableSymbol>2024001</VariableSymbol>', $xml);
		self::assertStringContainsString('<ConstantSymbol>0308</ConstantSymbol>', $xml);
		self::assertStringContainsString('IBAN', $xml);
		self::assertStringContainsString('BIC', $xml);
	}

	public function testOrderReferenceIncluded(): void
	{
		$invoice = $this->createBasicInvoice();
		$invoice->setOrderNumber('OBJ-2024-001');
		$xml = $invoice->export()->toString();

		self::assertStringContainsString('OrderReferences', $xml);
		self::assertStringContainsString('OBJ-2024-001', $xml);
	}

	public function testOriginalDocumentReference(): void
	{
		$invoice = $this->createBasicInvoice();
		$invoice->setDocumentType(DocumentType::CreditNote);
		$invoice->setOriginalDocumentNumber('FV-2023-100');
		$invoice->setOriginalDocumentDate('2023-11-15');
		$xml = $invoice->export()->toString();

		self::assertStringContainsString('<DocumentType>2</DocumentType>', $xml);
		self::assertStringContainsString('OriginalDocumentReferences', $xml);
		self::assertStringContainsString('FV-2023-100', $xml);
		self::assertStringContainsString('<IssueDate>2023-11-15</IssueDate>', $xml);
	}

	public function testFooterTextIncluded(): void
	{
		$invoice = $this->createBasicInvoice();
		$invoice->setFooterText('Thank you for your order.');
		$xml = $invoice->export()->toString();

		self::assertStringContainsString('Thank you for your order.', $xml);
	}

	public function testMultipleVatRates(): void
	{
		$invoice = $this->createBasicInvoice();
		$invoice->setVatPayer(true)->setDeliveryDate('2024-01-15');
		$invoice->addItem('Book', 1.0, 'pcs', 200.0, 224.0, 12.0);
		$xml = $invoice->export()->toString();

		self::assertStringContainsString('<Percent>21.00</Percent>', $xml);
		self::assertStringContainsString('<Percent>12.00</Percent>', $xml);
	}

	public function testRounding(): void
	{
		$invoice = $this->createBasicInvoice();
		$invoice->setRoundTotal(true);
		$xml = $invoice->export()->toString();

		self::assertStringContainsString('PayableRoundingAmount', $xml);
		self::assertStringContainsString('PayableAmount', $xml);
	}

	public function testSaveToFile(): void
	{
		$tempFile = sys_get_temp_dir() . '/test_isdoc_' . uniqid() . '.isdoc';

		try {
			$bytes = $this->createBasicInvoice()->export()->save($tempFile);

			self::assertGreaterThan(0, $bytes);
			self::assertFileExists($tempFile);

			$content = file_get_contents($tempFile);
			self::assertIsString($content);
			self::assertStringContainsString('<?xml', $content);
		} finally {
			if (file_exists($tempFile)) {
				unlink($tempFile);
			}
		}
	}

	public function testProducesValidXmlAgainstXsd(): void
	{
		$invoice = $this->createBasicInvoice();
		$invoice->setVatPayer(true)->setDeliveryDate('2024-01-15');
		$invoice->payment->setAccountNumber('123456789')->setBankCode('0100');

		$this->assertValidIsdocXml($invoice->export()->toString());
	}

	public function testNonVatPayerPassesXsd(): void
	{
		$this->assertValidIsdocXml($this->createBasicInvoice()->export()->toString());
	}

	public function testForeignCurrencyPassesXsd(): void
	{
		$invoice = $this->createBasicInvoice();
		$invoice->setVatPayer(true)->setDeliveryDate('2024-01-15');
		$invoice->setCurrencyCode('CZK')->setForeignCurrencyCode('EUR')->setCurrencyRate(25.5)->setRefCurrencyRate(1.0);
		$invoice->payment->setAccountNumber('123456789')->setBankCode('0100');

		$this->assertValidIsdocXml($invoice->export()->toString());
	}

	public function testReverseChargePassesXsd(): void
	{
		$invoice = $this->createBasicInvoice();
		$invoice->setVatPayer(true)->setDeliveryDate('2024-01-15');
		$invoice->setReverseCharge(true)->setReverseChargeType('4');
		$invoice->payment->setAccountNumber('123456789')->setBankCode('0100');

		$this->assertValidIsdocXml($invoice->export()->toString());
	}

	public function testForeignCurrencyWithNotePassesXsd(): void
	{
		$invoice = $this->createBasicInvoice();
		$invoice->setVatPayer(true)->setDeliveryDate('2024-01-15');
		$invoice->setCurrencyCode('CZK')->setForeignCurrencyCode('EUR')->setCurrencyRate(25.5)->setRefCurrencyRate(1.0);
		$invoice->payment->setAccountNumber('123456789')->setBankCode('0100');
		$invoice->addItem('Extra service', 2.0, 'hrs', 500.0, 605.0, 21.0, 'Detailed note');

		$this->assertValidIsdocXml($invoice->export()->toString());
	}

	public function testIssuingSystemIncluded(): void
	{
		$invoice = $this->createBasicInvoice();
		$invoice->setIssuingSystem('MySystem v1.0');
		$xml = $invoice->export()->toString();

		self::assertStringContainsString('<IssuingSystem>MySystem v1.0</IssuingSystem>', $xml);
	}

	public function testIssuingSystemOmittedWhenEmpty(): void
	{
		$xml = $this->createBasicInvoice()->export()->toString();

		self::assertStringNotContainsString('IssuingSystem', $xml);
	}

	public function testItemWithNoteCreatesExtraLine(): void
	{
		$invoice = new Invoice();
		$invoice->setNumber('1')->setIssueDate('2024-01-01')->setDueDate('2024-01-31');
		$invoice->supplier->setName('S')->setStreet('Street 1')->setCity('Prague')->setZip('11000');
		$invoice->customer->setName('C')->setStreet('Street 2')->setCity('Brno')->setZip('60200');
		$invoice->addItem('Service', 1.0, 'pcs', 1000.0, 1210.0, 21.0, 'Detailed description');
		$xml = $invoice->export()->toString();

		self::assertStringContainsString('1-D', $xml);
		self::assertStringContainsString('Service - Detailed description', $xml);
	}

	public function testDocumentTypes(): void
	{
		$invoice = $this->createBasicInvoice();

		$invoice->setDocumentType(DocumentType::Invoice);
		self::assertStringContainsString('<DocumentType>1</DocumentType>', $invoice->export()->toString());

		$invoice->setDocumentType(DocumentType::CreditNote);
		self::assertStringContainsString('<DocumentType>2</DocumentType>', $invoice->export()->toString());

		$invoice->setDocumentType(DocumentType::DebitNote);
		self::assertStringContainsString('<DocumentType>3</DocumentType>', $invoice->export()->toString());

		$invoice->setDocumentType(DocumentType::ProformaInvoice);
		self::assertStringContainsString('<DocumentType>4</DocumentType>', $invoice->export()->toString());
	}

	public function testForeignCurrency(): void
	{
		$invoice = $this->createBasicInvoice();
		$invoice->setCurrencyCode('CZK')->setForeignCurrencyCode('EUR')->setCurrencyRate(25.5)->setRefCurrencyRate(1.0);
		$xml = $invoice->export()->toString();

		self::assertStringContainsString('<LocalCurrencyCode>CZK</LocalCurrencyCode>', $xml);
		self::assertStringContainsString('<ForeignCurrencyCode>EUR</ForeignCurrencyCode>', $xml);
		self::assertStringContainsString('<CurrRate>25.50</CurrRate>', $xml);
		self::assertStringContainsString('<RefCurrRate>1.00</RefCurrRate>', $xml);
	}

	public function testBuildingNumberAlwaysPresent(): void
	{
		$invoice = new Invoice();
		$invoice->setNumber('1')->setIssueDate('2024-01-01')->setDueDate('2024-01-31');
		$invoice->supplier->setName('S')->setStreet('No Number Street')->setCity('Prague')->setZip('11000');
		$invoice->customer->setName('C')->setStreet('Another Street')->setCity('Brno')->setZip('60200');
		$invoice->addItem('Item', 1.0, 'pcs', 100.0, 100.0, 0.0);
		$xml = $invoice->export()->toString();

		// BuildingNumber must always be present (XSD requirement)
		$count = substr_count($xml, 'BuildingNumber');

		// 2 parties x 2 (opening + closing tag) = 4
		self::assertSame(4, $count);
	}

	public function testPartyIdentificationAlwaysPresent(): void
	{
		$invoice = new Invoice();
		$invoice->setNumber('1')->setIssueDate('2024-01-01')->setDueDate('2024-01-31');
		$invoice->supplier->setName('Supplier')->setStreet('Street 1')->setCity('Prague')->setZip('11000');
		$invoice->customer->setName('Customer')->setStreet('Street 2')->setCity('Brno')->setZip('60200');
		$invoice->addItem('Item', 1.0, 'pcs', 100.0, 100.0, 0.0);
		$xml = $invoice->export()->toString();

		// PartyIdentification must always be present even without company ID
		self::assertGreaterThan(0, substr_count($xml, 'PartyIdentification'));
	}

	public function testDefaultCountryWhenEmpty(): void
	{
		$invoice = new Invoice();
		$invoice->setNumber('1')->setIssueDate('2024-01-01')->setDueDate('2024-01-31');
		$invoice->supplier->setName('S')->setStreet('Street 1')->setCity('Prague')->setZip('11000');
		$invoice->customer->setName('C')->setStreet('Street 2')->setCity('Brno')->setZip('60200');
		$invoice->addItem('Item', 1.0, 'pcs', 100.0, 100.0, 0.0);
		$xml = $invoice->export()->toString();

		// Default country should be Czech Republic when not set
		self::assertStringContainsString('<IdentificationCode>CZ</IdentificationCode>', $xml);
		self::assertStringContainsString('<Name>Czech Republic</Name>', $xml);
	}
}
