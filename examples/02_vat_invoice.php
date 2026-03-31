<?php declare(strict_types=1);

/**
 * VAT payer invoice with multiple VAT rates, rounding, and footer text.
 */

require __DIR__ . '/../vendor/autoload.php';

use Kenod\IsdocExporter\Invoice;

$invoice = new Invoice();
$invoice
	->setNumber('FV-2024-0042')
	->setIssueDate('2024-03-01')
	->setDueDate('2024-03-15')
	->setVatPayer(true)
	->setDeliveryDate('2024-02-29')
	->setFooterText('Thank you for your business.')
	->setOrderNumber('OBJ-2024-100')
	->setRoundTotal(true)
	->setIssuingSystem('ISDOC Exporter Example');

$invoice->supplier
	->setName('WebStudio s.r.o.')
	->setStreet('Developer Street 42')
	->setCity('Prague 5')
	->setZip('150 00')
	->setCompanyId('12345678')
	->setVatId('CZ12345678')
	->setRegisterInfo('Registered in the Commercial Register of the Municipal Court in Prague, Section C, Insert 12345')
	->setEmail('billing@webstudio.cz')
	->setPhone('+420 222 333 444');

$invoice->customer
	->setName('Client a.s.')
	->setStreet('Order Street 100/5')
	->setCity('Brno')
	->setZip('602 00')
	->setCountry('Czech Republic')
	->setCompanyId('87654321')
	->setVatId('CZ87654321');

$invoice->payment
	->setAccountNumber('19-2000145399')
	->setBankCode('0800')
	->setVariableSymbol('20240042')
	->setConstantSymbol('0308');

// 21% VAT items
$invoice
	->addItem('Web application development', 80.0, 'hrs', 1500.0, 1815.0, 21.0)
	->addItem('Graphic design', 1.0, 'pcs', 25000.0, 30250.0, 21.0);

// 12% VAT item
$invoice->addItem('User manual (printed)', 50.0, 'pcs', 350.0, 392.0, 12.0);

$invoice->export()->save(__DIR__ . '/02_vat_invoice.isdoc');

echo "Invoice generated: 02_vat_invoice.isdoc\n";
