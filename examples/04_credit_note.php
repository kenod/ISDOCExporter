<?php declare(strict_types=1);

/**
 * Credit note referencing an original invoice.
 */

require __DIR__ . '/../vendor/autoload.php';

use Kenod\IsdocExporter\DocumentType;
use Kenod\IsdocExporter\Invoice;

$invoice = new Invoice();
$invoice
	->setDocumentType(DocumentType::CreditNote)
	->setNumber('DN-2024-001')
	->setIssueDate('2024-04-01')
	->setDueDate('2024-04-15')
	->setVatPayer(true)
	->setDeliveryDate('2024-04-01')
	->setOriginalDocumentNumber('FV-2024-0042')
	->setOriginalDocumentDate('2024-03-15')
	->setFooterText('Credit note for invoice FV-2024-0042.')
	->setIssuingSystem('ISDOC Exporter Example');

$invoice->supplier
	->setName('WebStudio s.r.o.')
	->setStreet('Developer Street 42')
	->setCity('Prague 5')
	->setZip('150 00')
	->setCompanyId('12345678')
	->setVatId('CZ12345678');

$invoice->customer
	->setName('Client a.s.')
	->setStreet('Order Street 100/5')
	->setCity('Brno')
	->setZip('602 00')
	->setCompanyId('87654321')
	->setVatId('CZ87654321');

$invoice->payment
	->setAccountNumber('19-2000145399')
	->setBankCode('0800')
	->setVariableSymbol('20240042');

$invoice->addItem('Discount on web application development', 10.0, 'hrs', 1500.0, 1815.0, 21.0);

$invoice->export()->save(__DIR__ . '/04_credit_note.isdoc');

echo "Credit note generated: 04_credit_note.isdoc\n";
