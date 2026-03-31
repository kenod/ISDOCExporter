# ISDOC Exporter

PHP library for generating electronic invoices in the [ISDOC](https://isdoc.cz/) format (version 6.0.2) -- the Czech national standard for electronic invoicing.

## Features

- ISDOC 6.0.2 compliant XML output
- All document types: invoice, credit note, debit note, proforma, advance invoice, simplified invoice
- VAT payer and non-VAT payer invoices
- Reverse charge support
- Multiple VAT rates
- Foreign currency support
- Czech bank database (names, BIC/SWIFT, IBAN generation)
- Automatic address parsing (street + building number)
- Country name to ISO code resolution
- Fluent API with method chaining
- Total rounding
- Save to file or download as HTTP response

## Requirements

- PHP 8.3+
- Extensions: `dom`, `bcmath`

## Installation

```bash
composer require kenod/isdoc-exporter
```

## Quick Start

```php
use Kenod\IsdocExporter\Invoice;

$invoice = new Invoice();
$invoice
    ->setNumber('2024001')
    ->setIssueDate('2024-01-15')
    ->setDueDate('2024-02-15');

$invoice->supplier
    ->setName('Supplier s.r.o.')
    ->setStreet('Main Street 10')
    ->setCity('Prague')
    ->setZip('110 00')
    ->setCompanyId('12345678');

$invoice->customer
    ->setName('Customer a.s.')
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

// Save to file
$invoice->export()->save('invoice.isdoc');

// Or get XML as string
$xml = $invoice->export()->toString();

// Or send as HTTP download
$invoice->export()->download('invoice.isdoc');
```

## VAT Payer Invoice

```php
$invoice = new Invoice();
$invoice
    ->setNumber('FV-2024-042')
    ->setIssueDate('2024-03-01')
    ->setDueDate('2024-03-15')
    ->setVatPayer(true)
    ->setDeliveryDate('2024-02-29')
    ->setRoundTotal(true);

// Supplier and customer setup...

// Items with VAT (unitPrice without VAT, unitPriceWithVat with VAT, vatRate)
$invoice
    ->addItem('Development', 80.0, 'hrs', 1500.0, 1815.0, 21.0)
    ->addItem('Printed manual', 50.0, 'pcs', 350.0, 392.0, 12.0);

$invoice->export()->save('vat_invoice.isdoc');
```

## Credit Note

```php
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
    ->setOriginalDocumentNumber('FV-2024-042')
    ->setOriginalDocumentDate('2024-03-01');

// ...
```

## Reverse Charge

```php
$invoice = new Invoice();
$invoice
    ->setNumber('FV-2024-RC-001')
    ->setIssueDate('2024-06-30')
    ->setDueDate('2024-07-30')
    ->setVatPayer(true)
    ->setDeliveryDate('2024-06-30')
    ->setReverseCharge(true)
    ->setReverseChargeType('4'); // 4 = construction services

// ...
```

## Foreign Currency

```php
$invoice = new Invoice();
$invoice
    ->setCurrencyCode('CZK')
    ->setForeignCurrencyCode('EUR')
    ->setCurrencyRate(25.35)
    ->setRefCurrencyRate(1.0);

// ...
```

## Document Types

| Type | Enum Value | Description |
|------|-----------|-------------|
| 1 | `DocumentType::Invoice` | Standard invoice (default) |
| 2 | `DocumentType::CreditNote` | Credit note |
| 3 | `DocumentType::DebitNote` | Debit note |
| 4 | `DocumentType::ProformaInvoice` | Proforma invoice |
| 5 | `DocumentType::AdvanceInvoice` | Advance invoice |
| 6 | `DocumentType::AdvanceCreditNote` | Advance credit note |
| 7 | `DocumentType::SimplifiedInvoice` | Simplified invoice |

## API Reference

### Invoice

| Method | Description |
|--------|-------------|
| `setNumber(string)` | Invoice number |
| `setIssueDate(string)` | Issue date (YYYY-MM-DD) |
| `setDueDate(string)` | Due date (YYYY-MM-DD) |
| `setDeliveryDate(string)` | Delivery / tax point date |
| `setDocumentType(DocumentType)` | Document type (default: Invoice) |
| `setVatPayer(bool)` | Whether the supplier is a VAT payer |
| `setReverseCharge(bool)` | Enable reverse charge |
| `setReverseChargeType(string)` | Reverse charge code (1=gold, 2=emission, 4=construction, 5=waste) |
| `setFooterText(string)` | Note / footer text |
| `setOrderNumber(string)` | Order reference number |
| `setOriginalDocumentNumber(string)` | Original document reference (for credit/debit notes) |
| `setOriginalDocumentDate(string)` | Original document issue date (YYYY-MM-DD) |
| `setRoundTotal(bool)` | Round the total to integer |
| `setIssuingSystem(string)` | Name of the issuing system |
| `setCurrencyCode(string)` | Local currency code (default: CZK) |
| `setForeignCurrencyCode(string)` | Foreign currency code |
| `setCurrencyRate(float)` | Exchange rate |
| `setRefCurrencyRate(float)` | Reference exchange rate |
| `addItem(name, qty, unit, price, priceWithVat, vatRate, note?)` | Add an invoice line item |
| `export()` | Returns `IsdocExporter` instance |

### Invoice Properties

| Property | Type | Description |
|----------|------|-------------|
| `$invoice->supplier` | `Party` | Supplier party (fluent setters) |
| `$invoice->customer` | `Party` | Customer party (fluent setters) |
| `$invoice->payment` | `PaymentInfo` | Payment details (fluent setters) |

### Party

| Method | Description |
|--------|-------------|
| `setName(string)` | Company or person name |
| `setStreet(string)` | Street with building number |
| `setCity(string)` | City |
| `setZip(string)` | Postal code |
| `setCountry(string)` | Country name (auto-resolved to ISO code) |
| `setCompanyId(string)` | Company registration number |
| `setVatId(string)` | VAT identification number |
| `setRegisterInfo(string)` | Commercial register info |
| `setEmail(string)` | Email address |
| `setPhone(string)` | Phone number |
| `setWeb(string)` | Website URL |

### PaymentInfo

| Method | Description |
|--------|-------------|
| `setAccountNumber(string)` | Bank account number (e.g. `19-2000145399`) |
| `setBankCode(string)` | Bank code (e.g. `0800`) |
| `setVariableSymbol(string)` | Variable symbol |
| `setConstantSymbol(string)` | Constant symbol |
| `setSpecificSymbol(string)` | Specific symbol |

### IsdocExporter

| Method | Description |
|--------|-------------|
| `toString()` | Returns the ISDOC XML as a string |
| `save(string $filepath)` | Saves the XML to a file |
| `download(?string $filename)` | Sends the XML as an HTTP download |

## Development

```bash
composer install
composer check    # runs phpcs + phpstan + phpunit
composer phpcs    # code style check
composer phpcbf   # auto-fix code style
composer phpstan  # static analysis (level 8)
composer test     # unit tests
```

## License

MIT
