<?php declare(strict_types=1);

/**
 * Basic invoice from a non-VAT payer.
 */

require __DIR__ . '/../vendor/autoload.php';

use Kenod\IsdocExporter\Invoice;

$invoice = new Invoice();
$invoice
	->setNumber('2024001')
	->setIssueDate('2024-01-15')
	->setDueDate('2024-02-15')
	->setIssuingSystem('ISDOC Exporter Example');

$invoice->supplier
	->setName('Jan Novak')
	->setStreet('Main Street 10')
	->setCity('Prague')
	->setZip('110 00')
	->setCompanyId('12345678')
	->setEmail('jan@novak.cz')
	->setPhone('+420 777 123 456');

$invoice->customer
	->setName('Client s.r.o.')
	->setStreet('Side Street 20')
	->setCity('Brno')
	->setZip('602 00')
	->setCompanyId('87654321');

$invoice->payment
	->setAccountNumber('123456789')
	->setBankCode('0100')
	->setVariableSymbol('2024001');

$invoice
	->addItem('Website development', 1.0, 'pcs', 15000.0, 15000.0, 0.0)
	->addItem('Hosting for 1 year', 12.0, 'months', 200.0, 200.0, 0.0);

$invoice->export()->save(__DIR__ . '/01_basic_invoice.isdoc');

echo "Invoice generated: 01_basic_invoice.isdoc\n";
