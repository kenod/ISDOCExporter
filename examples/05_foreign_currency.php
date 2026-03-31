<?php declare(strict_types=1);

/**
 * Invoice with foreign currency (EUR).
 */

require __DIR__ . '/../vendor/autoload.php';

use Kenod\IsdocExporter\Invoice;

$invoice = new Invoice();
$invoice
	->setNumber('FV-2024-EUR-001')
	->setIssueDate('2024-05-01')
	->setDueDate('2024-05-31')
	->setVatPayer(true)
	->setDeliveryDate('2024-05-01')
	->setCurrencyCode('CZK')
	->setForeignCurrencyCode('EUR')
	->setCurrencyRate(25.35)
	->setRefCurrencyRate(1.0)
	->setIssuingSystem('ISDOC Exporter Example');

$invoice->supplier
	->setName('ExportCo s.r.o.')
	->setStreet('International Boulevard 1')
	->setCity('Prague')
	->setZip('110 00')
	->setCompanyId('11111111')
	->setVatId('CZ11111111');

$invoice->customer
	->setName('EuroClient GmbH')
	->setStreet('Hauptstrasse 50')
	->setCity('Berlin')
	->setZip('10115')
	->setCountry('Germany')
	->setCompanyId('DE123456789')
	->setVatId('DE123456789');

$invoice->payment
	->setAccountNumber('123456789')
	->setBankCode('0100')
	->setVariableSymbol('2024001');

$invoice->addItem('Consulting services', 40.0, 'hrs', 2000.0, 2420.0, 21.0);

$invoice->export()->save(__DIR__ . '/05_foreign_currency.isdoc');

echo "Invoice generated: 05_foreign_currency.isdoc\n";
