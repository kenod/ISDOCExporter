<?php declare(strict_types=1);

/**
 * Reverse charge invoice for construction services.
 */

require __DIR__ . '/../vendor/autoload.php';

use Kenod\IsdocExporter\Invoice;

$invoice = new Invoice();
$invoice
	->setNumber('FV-2024-RC-001')
	->setIssueDate('2024-06-30')
	->setDueDate('2024-07-30')
	->setVatPayer(true)
	->setDeliveryDate('2024-06-30')
	->setReverseCharge(true)
	->setReverseChargeType('4')
	->setFooterText('VAT to be paid by the customer (reverse charge mechanism).')
	->setIssuingSystem('ISDOC Exporter Example');

$invoice->supplier
	->setName('BuildPro s.r.o.')
	->setStreet('Construction Street 789/12')
	->setCity('Ostrava')
	->setZip('702 00')
	->setCompanyId('11223344')
	->setVatId('CZ11223344')
	->setRegisterInfo('Registered in the Commercial Register of the Regional Court in Ostrava');

$invoice->customer
	->setName('Developer Group a.s.')
	->setStreet('Investor Avenue 1000')
	->setCity('Prague 4')
	->setZip('140 00')
	->setCompanyId('55667788')
	->setVatId('CZ55667788');

$invoice->payment
	->setAccountNumber('1234567890')
	->setBankCode('5500')
	->setVariableSymbol('2024001');

$invoice
	->addItem('Construction work - shell', 1.0, 'pcs', 850000.0, 1028500.0, 21.0)
	->addItem('Electrical installation', 1.0, 'pcs', 120000.0, 145200.0, 21.0)
	->addItem('Plumbing and heating', 1.0, 'pcs', 95000.0, 114950.0, 21.0, 'Including materials and delivery');

$invoice->export()->save(__DIR__ . '/03_reverse_charge.isdoc');

echo "Invoice generated: 03_reverse_charge.isdoc\n";
