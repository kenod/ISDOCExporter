# ISDOC Exporter

PHP knihovna pro generování elektronických faktur ve formátu [ISDOC](https://isdoc.cz/) (verze 6.0.2) -- český národní standard pro elektronickou fakturaci.

## Vlastnosti

- Výstup XML kompatibilní s ISDOC 6.0.2
- Všechny typy dokumentů: faktura, dobropis, vrubopis, zálohová faktura, daňový doklad při přijetí platby, zjednodušený daňový doklad
- Faktury plátce i neplátce DPH
- Režim přenesení daňové povinnosti (reverse charge)
- Více sazeb DPH na jedné faktuře
- Podpora cizích měn
- Databáze českých bank (názvy, BIC/SWIFT, generování IBAN)
- Automatický parsing adresy (ulice + číslo popisné)
- Převod názvu země na ISO kód
- Fluent API s řetězením metod
- Zaokrouhlení celkové částky
- Uložení do souboru nebo stažení přes HTTP

## Požadavky

- PHP 8.3+
- Rozšíření: `dom`, `bcmath`

## Instalace

```bash
composer require kenod/isdoc-exporter
```

## Rychlý start

```php
use Kenod\IsdocExporter\Invoice;

$invoice = new Invoice();
$invoice
    ->setNumber('2024001')
    ->setIssueDate('2024-01-15')
    ->setDueDate('2024-02-15');

$invoice->supplier
    ->setName('Dodavatel s.r.o.')
    ->setStreet('Hlavní 10')
    ->setCity('Praha')
    ->setZip('110 00')
    ->setCompanyId('12345678');

$invoice->customer
    ->setName('Odběratel a.s.')
    ->setStreet('Vedlejší 20')
    ->setCity('Brno')
    ->setZip('602 00')
    ->setCompanyId('87654321');

$invoice->payment
    ->setAccountNumber('123456789')
    ->setBankCode('0100')
    ->setVariableSymbol('2024001');

$invoice
    ->addItem('Tvorba webových stránek', 1.0, 'ks', 15000.0, 15000.0, 0.0)
    ->addItem('Hosting na 1 rok', 12.0, 'měs', 200.0, 200.0, 0.0);

// Uložení do souboru
$invoice->export()->save('faktura.isdoc');

// Nebo získání XML jako string
$xml = $invoice->export()->toString();

// Nebo stažení přes HTTP
$invoice->export()->download('faktura.isdoc');
```

## Faktura plátce DPH

```php
$invoice = new Invoice();
$invoice
    ->setNumber('FV-2024-042')
    ->setIssueDate('2024-03-01')
    ->setDueDate('2024-03-15')
    ->setVatPayer(true)
    ->setDeliveryDate('2024-02-29')
    ->setRoundTotal(true);

// Nastavení dodavatele a odběratele...

// Položky s DPH (cena bez DPH, cena s DPH, sazba DPH)
$invoice
    ->addItem('Vývoj aplikace', 80.0, 'hod', 1500.0, 1815.0, 21.0)
    ->addItem('Tištěná příručka', 50.0, 'ks', 350.0, 392.0, 12.0);

$invoice->export()->save('faktura_dph.isdoc');
```

## Dobropis

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

## Přenesení daňové povinnosti

```php
$invoice = new Invoice();
$invoice
    ->setNumber('FV-2024-RC-001')
    ->setIssueDate('2024-06-30')
    ->setDueDate('2024-07-30')
    ->setVatPayer(true)
    ->setDeliveryDate('2024-06-30')
    ->setReverseCharge(true)
    ->setReverseChargeType('4'); // 4 = stavební práce

// ...
```

## Cizí měna

```php
$invoice = new Invoice();
$invoice
    ->setCurrencyCode('CZK')
    ->setForeignCurrencyCode('EUR')
    ->setCurrencyRate(25.35)
    ->setRefCurrencyRate(1.0);

// ...
```

## Typy dokumentů

| Typ | Enum hodnota | Popis |
|-----|-------------|-------|
| 1 | `DocumentType::Invoice` | Faktura - daňový doklad (výchozí) |
| 2 | `DocumentType::CreditNote` | Opravný daňový doklad (dobropis) |
| 3 | `DocumentType::DebitNote` | Opravný daňový doklad (vrubopis) |
| 4 | `DocumentType::ProformaInvoice` | Zálohová faktura |
| 5 | `DocumentType::AdvanceInvoice` | Daňový doklad při přijetí platby |
| 6 | `DocumentType::AdvanceCreditNote` | Opravný daňový doklad při přijetí platby |
| 7 | `DocumentType::SimplifiedInvoice` | Zjednodušený daňový doklad |

## Přehled API

### Invoice

| Metoda | Popis |
|--------|-------|
| `setNumber(string)` | Číslo faktury |
| `setIssueDate(string)` | Datum vystavení (YYYY-MM-DD) |
| `setDueDate(string)` | Datum splatnosti (YYYY-MM-DD) |
| `setDeliveryDate(string)` | Datum uskutečnění zdanitelného plnění |
| `setDocumentType(DocumentType)` | Typ dokumentu (výchozí: Invoice) |
| `setVatPayer(bool)` | Zda je dodavatel plátce DPH |
| `setReverseCharge(bool)` | Režim přenesení daňové povinnosti |
| `setReverseChargeType(string)` | Kód reverse charge (1=zlato, 2=emise, 4=stavebnictví, 5=odpady) |
| `setFooterText(string)` | Poznámka / text v patičce |
| `setOrderNumber(string)` | Číslo objednávky |
| `setOriginalDocumentNumber(string)` | Odkaz na původní doklad (pro dobropisy/vrubopisy) |
| `setOriginalDocumentDate(string)` | Datum vystavení původního dokladu (YYYY-MM-DD) |
| `setRoundTotal(bool)` | Zaokrouhlit celkovou částku na celé koruny |
| `setIssuingSystem(string)` | Název vystavujícího systému |
| `setCurrencyCode(string)` | Kód lokální měny (výchozí: CZK) |
| `setForeignCurrencyCode(string)` | Kód cizí měny |
| `setCurrencyRate(float)` | Kurz |
| `setRefCurrencyRate(float)` | Referenční kurz |
| `addItem(název, množství, jednotka, cena, cenaSDPH, sazbaDPH, poznámka?)` | Přidání položky |
| `export()` | Vrátí instanci `IsdocExporter` |

### Vlastnosti Invoice

| Vlastnost | Typ | Popis |
|-----------|-----|-------|
| `$invoice->supplier` | `Party` | Dodavatel (fluent settery) |
| `$invoice->customer` | `Party` | Odběratel (fluent settery) |
| `$invoice->payment` | `PaymentInfo` | Platební údaje (fluent settery) |

### Party

| Metoda | Popis |
|--------|-------|
| `setName(string)` | Název firmy nebo jméno osoby |
| `setStreet(string)` | Ulice s číslem popisným |
| `setCity(string)` | Město |
| `setZip(string)` | PSČ |
| `setCountry(string)` | Název země (automaticky převeden na ISO kód) |
| `setCompanyId(string)` | IČO |
| `setVatId(string)` | DIČ |
| `setRegisterInfo(string)` | Zápis v obchodním rejstříku |
| `setEmail(string)` | E-mail |
| `setPhone(string)` | Telefon |
| `setWeb(string)` | Webová stránka |

### PaymentInfo

| Metoda | Popis |
|--------|-------|
| `setAccountNumber(string)` | Číslo účtu (např. `19-2000145399`) |
| `setBankCode(string)` | Kód banky (např. `0800`) |
| `setVariableSymbol(string)` | Variabilní symbol |
| `setConstantSymbol(string)` | Konstantní symbol |
| `setSpecificSymbol(string)` | Specifický symbol |

### IsdocExporter

| Metoda | Popis |
|--------|-------|
| `toString()` | Vrátí ISDOC XML jako string |
| `save(string $cesta)` | Uloží XML do souboru |
| `download(?string $název)` | Odešle XML jako HTTP stažení |

## Vývoj

```bash
composer install
composer check    # spustí phpcs + phpstan + phpunit
composer phpcs    # kontrola kódového stylu
composer phpcbf   # automatická oprava stylu
composer phpstan  # statická analýza (level 8)
composer test     # unit testy
```

## Licence

MIT
