<?php declare(strict_types=1);

namespace Kenod\IsdocExporter;

use DOMDocument;
use DOMElement;
use RuntimeException;
use function bin2hex;
use function chr;
use function file_put_contents;
use function htmlspecialchars;
use function number_format;
use function ord;
use function preg_replace;
use function random_bytes;
use function round;
use function str_replace;
use function str_split;
use function vsprintf;

final class IsdocExporter
{
	private const string NAMESPACE_URI = 'http://isdoc.cz/namespace/2013';

	private const string VERSION = '6.0.2';

	private DOMDocument $xml;

	public function __construct(private readonly Invoice $invoice)
	{
		$this->xml = new DOMDocument('1.0', 'UTF-8');
	}

	public function toString(): string
	{
		$this->xml = new DOMDocument('1.0', 'UTF-8');
		$this->xml->formatOutput = true;

		$root = $this->xml->createElementNS(self::NAMESPACE_URI, 'Invoice');
		$root->setAttribute('version', self::VERSION);
		$this->xml->appendChild($root);

		$this->addDocumentType($root);
		$this->addBasicInfo($root);
		$this->addParty($root, 'AccountingSupplierParty', $this->invoice->supplier);
		$this->addParty($root, 'AccountingCustomerParty', $this->invoice->customer);
		$this->addOrderReferences($root);
		$this->addOriginalDocumentReferences($root);
		$this->addInvoiceLines($root);
		$this->addTaxTotal($root);
		$this->addLegalMonetaryTotal($root);
		$this->addPaymentMeans($root);

		$result = $this->xml->saveXML();

		return $result !== false ? $result : '';
	}

	public function save(string $filepath): int
	{
		$xml = $this->toString();
		$result = file_put_contents($filepath, $xml);

		if ($result === false) {
			throw new RuntimeException('Failed to write file: ' . $filepath);
		}

		return $result;
	}

	public function download(?string $filename = null): void
	{
		if ($filename === null) {
			$invoiceNumber = preg_replace('/[^a-zA-Z0-9_-]/', '_', $this->invoice->getNumber());
			$filename = 'isdoc_' . $invoiceNumber . '.isdoc';
		}

		header('Content-Type: application/xml; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Cache-Control: no-cache, must-revalidate');

		echo $this->toString();
	}

	private function addDocumentType(DOMElement $parent): void
	{
		$this->addElement($parent, 'DocumentType', (string) $this->invoice->getDocumentType()->value);
	}

	private function addBasicInfo(DOMElement $parent): void
	{
		$this->addElement($parent, 'ID', $this->invoice->getNumber());
		$this->addElement($parent, 'UUID', self::generateUuid());

		if ($this->invoice->getIssuingSystem() !== '') {
			$this->addElement($parent, 'IssuingSystem', $this->invoice->getIssuingSystem());
		}

		$this->addElement($parent, 'IssueDate', $this->invoice->getIssueDate());

		if ($this->invoice->isVatPayer() && $this->invoice->getDeliveryDate() !== '') {
			$this->addElement($parent, 'TaxPointDate', $this->invoice->getDeliveryDate());
		}

		$this->addElement($parent, 'VATApplicable', $this->invoice->isVatPayer() ? 'true' : 'false');
		$this->addElement($parent, 'ElectronicPossibilityAgreementReference');

		if ($this->invoice->getFooterText() !== '') {
			$this->addElement($parent, 'Note', $this->invoice->getFooterText());
		}

		$this->addElement($parent, 'LocalCurrencyCode', $this->invoice->getCurrencyCode());

		if ($this->invoice->getForeignCurrencyCode() !== '') {
			$this->addElement($parent, 'ForeignCurrencyCode', $this->invoice->getForeignCurrencyCode());
		}

		$this->addElement($parent, 'CurrRate', self::formatAmount($this->invoice->getCurrencyRate()));
		$this->addElement($parent, 'RefCurrRate', self::formatAmount($this->invoice->getRefCurrencyRate()));
	}

	private function addParty(DOMElement $parent, string $elementName, Party $party): void
	{
		$wrapper = $this->addElement($parent, $elementName);
		$partyNode = $this->addElement($wrapper, 'Party');

		// PartyIdentification is required by XSD - always include it
		$partyId = $this->addElement($partyNode, 'PartyIdentification');
		$companyId = $party->getCompanyId() !== '' ? $party->getCompanyId() : '0';
		$this->addElement($partyId, 'UserID', $companyId);
		$this->addElement($partyId, 'ID', $companyId);

		$partyName = $this->addElement($partyNode, 'PartyName');
		$this->addElement($partyName, 'Name', $party->getName());

		$address = $this->addElement($partyNode, 'PostalAddress');
		$streetParts = AddressParser::parseStreetAndNumber($party->getStreet());
		$this->addElement($address, 'StreetName', $streetParts['street']);

		// BuildingNumber is required by XSD - always include it
		$buildingNumber = $streetParts['number'] !== '' ? $streetParts['number'] : '0';
		$this->addElement($address, 'BuildingNumber', $buildingNumber);

		$this->addElement($address, 'CityName', $party->getCity());
		$this->addElement($address, 'PostalZone', str_replace(' ', '', $party->getZip()));

		$countryName = $party->getCountry() !== '' ? $party->getCountry() : 'Czech Republic';
		$country = $this->addElement($address, 'Country');
		$this->addElement($country, 'IdentificationCode', CountryResolver::getCountryCode($countryName));
		$this->addElement($country, 'Name', $countryName);

		if ($party->getVatId() !== '') {
			$taxScheme = $this->addElement($partyNode, 'PartyTaxScheme');
			$this->addElement($taxScheme, 'CompanyID', $party->getVatId());
			$this->addElement($taxScheme, 'TaxScheme', 'VAT');
		}

		if ($party->getRegisterInfo() !== '') {
			$register = $this->addElement($partyNode, 'RegisterIdentification');
			$this->addElement($register, 'Preformatted', $party->getRegisterInfo());
		}

		if ($party->getEmail() !== '' || $party->getPhone() !== '') {
			$contact = $this->addElement($partyNode, 'Contact');

			if ($party->getPhone() !== '') {
				$this->addElement($contact, 'Telephone', $party->getPhone());
			}

			if ($party->getEmail() !== '') {
				$this->addElement($contact, 'ElectronicMail', $party->getEmail());
			}
		}
	}

	private function addOrderReferences(DOMElement $parent): void
	{
		if ($this->invoice->getOrderNumber() === '') {
			return;
		}

		$orderReferences = $this->addElement($parent, 'OrderReferences');
		$orderReference = $this->addElement($orderReferences, 'OrderReference');
		$orderReference->setAttribute('id', $this->invoice->getOrderNumber());
		$this->addElement($orderReference, 'SalesOrderID', $this->invoice->getOrderNumber());
	}

	private function addOriginalDocumentReferences(DOMElement $parent): void
	{
		if ($this->invoice->getOriginalDocumentNumber() === '') {
			return;
		}

		$references = $this->addElement($parent, 'OriginalDocumentReferences');
		$reference = $this->addElement($references, 'OriginalDocumentReference');
		$reference->setAttribute('id', $this->invoice->getOriginalDocumentNumber());
		$this->addElement($reference, 'ID', $this->invoice->getOriginalDocumentNumber());

		if ($this->invoice->getOriginalDocumentDate() !== '') {
			$this->addElement($reference, 'IssueDate', $this->invoice->getOriginalDocumentDate());
		}
	}

	private function addInvoiceLines(DOMElement $parent): void
	{
		$items = $this->invoice->getItems();
		$zeroVat = $this->isZeroVat();

		$lines = $this->addElement($parent, 'InvoiceLines');

		foreach ($items as $index => $item) {
			if ($item->getNote() !== '') {
				$this->addNoteLine($lines, $index + 1, $item);
			}

			$line = $this->addElement($lines, 'InvoiceLine');
			$this->addElement($line, 'ID', (string) ($index + 1));

			$quantity = $this->addElement($line, 'InvoicedQuantity', (string) $item->getQuantity());
			$quantity->setAttribute('unitCode', $item->getUnit());

			$lineTotal = $item->getLineTotal();
			$lineTotalWithVat = $zeroVat ? $lineTotal : $item->getLineTotalWithVat();
			$lineTaxAmount = $zeroVat ? 0.0 : $item->getLineTaxAmount();

			if ($this->hasForeignCurrency()) {
				$rate = $this->invoice->getCurrencyRate();
				$this->addElement($line, 'LineExtensionAmountCurr', self::formatAmount($lineTotal / $rate));
			}

			$this->addElement($line, 'LineExtensionAmount', self::formatAmount($lineTotal));

			if ($this->hasForeignCurrency()) {
				$rate = $this->invoice->getCurrencyRate();
				$this->addElement($line, 'LineExtensionAmountTaxInclusiveCurr', self::formatAmount($lineTotalWithVat / $rate));
			}

			$this->addElement($line, 'LineExtensionAmountTaxInclusive', self::formatAmount($lineTotalWithVat));
			$this->addElement($line, 'LineExtensionTaxAmount', self::formatAmount($lineTaxAmount));

			$unitPrice = $item->getUnitPrice();
			$unitPriceWithVat = $zeroVat ? $unitPrice : $item->getUnitPriceWithVat();

			$this->addElement($line, 'UnitPrice', self::formatAmount($unitPrice));
			$this->addElement($line, 'UnitPriceTaxInclusive', self::formatAmount($unitPriceWithVat));

			$taxCategory = $this->addElement($line, 'ClassifiedTaxCategory');
			$this->addElement($taxCategory, 'Percent', self::formatAmount($item->getVatRate()));
			$this->addElement($taxCategory, 'VATCalculationMethod', '0');

			if ($this->invoice->isVatPayer() && $this->invoice->isReverseCharge()) {
				$localRc = $this->addElement($taxCategory, 'LocalReverseCharge');
				$this->addElement($localRc, 'LocalReverseChargeCode', $this->invoice->getReverseChargeType());
			}

			$itemNode = $this->addElement($line, 'Item');
			$this->addElement($itemNode, 'Description', $item->getName());
		}
	}

	private function addNoteLine(DOMElement $lines, int $lineNumber, InvoiceItem $item): void
	{
		$noteLine = $this->addElement($lines, 'InvoiceLine');
		$this->addElement($noteLine, 'ID', $lineNumber . '-D');
		$this->addElement($noteLine, 'InvoicedQuantity', '0');
		if ($this->hasForeignCurrency()) {
			$this->addElement($noteLine, 'LineExtensionAmountCurr', '0');
		}

		$this->addElement($noteLine, 'LineExtensionAmount', '0');

		if ($this->hasForeignCurrency()) {
			$this->addElement($noteLine, 'LineExtensionAmountTaxInclusiveCurr', '0');
		}

		$this->addElement($noteLine, 'LineExtensionAmountTaxInclusive', '0');
		$this->addElement($noteLine, 'LineExtensionTaxAmount', '0');

		$this->addElement($noteLine, 'UnitPrice', '0');
		$this->addElement($noteLine, 'UnitPriceTaxInclusive', '0');

		$noteCategory = $this->addElement($noteLine, 'ClassifiedTaxCategory');
		$this->addElement($noteCategory, 'Percent', '0');
		$this->addElement($noteCategory, 'VATCalculationMethod', '1');

		$noteItem = $this->addElement($noteLine, 'Item');
		$this->addElement($noteItem, 'Description', $item->getName() . ' - ' . $item->getNote());
	}

	private function addTaxTotal(DOMElement $parent): void
	{
		$items = $this->invoice->getItems();
		$taxTotal = $this->addElement($parent, 'TaxTotal');

		if (!$this->invoice->isVatPayer()) {
			$this->addNonVatTaxTotal($taxTotal, $items);

			return;
		}

		$this->addVatPayerTaxTotal($taxTotal, $items, $this->invoice->isReverseCharge());
	}

	/** @param list<InvoiceItem> $items */
	private function addNonVatTaxTotal(DOMElement $taxTotal, array $items): void
	{
		$totalAmount = 0.0;

		foreach ($items as $item) {
			$totalAmount += $item->getLineTotal();
		}

		$taxSubtotal = $this->addElement($taxTotal, 'TaxSubTotal');
		$hasFc = $this->hasForeignCurrency();
		$fcRate = $hasFc ? $this->invoice->getCurrencyRate() : 1.0;

		if ($hasFc) {
			$this->addElement($taxSubtotal, 'TaxableAmountCurr', self::formatAmount($totalAmount / $fcRate));
		}

		$this->addElement($taxSubtotal, 'TaxableAmount', self::formatAmount($totalAmount));

		if ($hasFc) {
			$this->addElement($taxSubtotal, 'TaxAmountCurr', '0.00');
		}

		$this->addElement($taxSubtotal, 'TaxAmount', '0.00');

		if ($hasFc) {
			$this->addElement($taxSubtotal, 'TaxInclusiveAmountCurr', self::formatAmount($totalAmount / $fcRate));
		}

		$this->addElement($taxSubtotal, 'TaxInclusiveAmount', self::formatAmount($totalAmount));

		if ($hasFc) {
			$this->addElement($taxSubtotal, 'AlreadyClaimedTaxableAmountCurr', '0.00');
		}

		$this->addElement($taxSubtotal, 'AlreadyClaimedTaxableAmount', '0.00');

		if ($hasFc) {
			$this->addElement($taxSubtotal, 'AlreadyClaimedTaxAmountCurr', '0.00');
		}

		$this->addElement($taxSubtotal, 'AlreadyClaimedTaxAmount', '0.00');

		if ($hasFc) {
			$this->addElement($taxSubtotal, 'AlreadyClaimedTaxInclusiveAmountCurr', '0.00');
		}

		$this->addElement($taxSubtotal, 'AlreadyClaimedTaxInclusiveAmount', '0.00');

		if ($hasFc) {
			$this->addElement($taxSubtotal, 'DifferenceTaxableAmountCurr', self::formatAmount($totalAmount / $fcRate));
		}

		$this->addElement($taxSubtotal, 'DifferenceTaxableAmount', self::formatAmount($totalAmount));

		if ($hasFc) {
			$this->addElement($taxSubtotal, 'DifferenceTaxAmountCurr', '0.00');
		}

		$this->addElement($taxSubtotal, 'DifferenceTaxAmount', '0.00');

		if ($hasFc) {
			$this->addElement($taxSubtotal, 'DifferenceTaxInclusiveAmountCurr', self::formatAmount($totalAmount / $fcRate));
		}

		$this->addElement($taxSubtotal, 'DifferenceTaxInclusiveAmount', self::formatAmount($totalAmount));

		$taxCategory = $this->addElement($taxSubtotal, 'TaxCategory');
		$this->addElement($taxCategory, 'Percent', '0.00');

		if ($hasFc) {
			$this->addElement($taxTotal, 'TaxAmountCurr', '0.00');
		}

		$this->addElement($taxTotal, 'TaxAmount', '0.00');
	}

	/** @param list<InvoiceItem> $items */
	private function addVatPayerTaxTotal(DOMElement $taxTotal, array $items, bool $isReverseCharge): void
	{
		/** @var array<string, array{taxableAmount: float, taxAmount: float}> $vatByRate */
		$vatByRate = [];
		$totalTaxAmount = 0.0;

		foreach ($items as $item) {
			$rate = self::formatAmount($item->getVatRate());

			if (!isset($vatByRate[$rate])) {
				$vatByRate[$rate] = [
					'taxableAmount' => 0.0,
					'taxAmount' => 0.0,
				];
			}

			$itemTaxAmount = $isReverseCharge ? 0.0 : $item->getLineTaxAmount();
			$vatByRate[$rate]['taxableAmount'] += $item->getLineTotal();
			$vatByRate[$rate]['taxAmount'] += $itemTaxAmount;
			$totalTaxAmount += $itemTaxAmount;
		}

		$hasFc = $this->hasForeignCurrency();
		$fcRate = $hasFc ? $this->invoice->getCurrencyRate() : 1.0;

		foreach ($vatByRate as $rate => $amounts) {
			$taxSubtotal = $this->addElement($taxTotal, 'TaxSubTotal');
			$taxInclusiveAmount = $amounts['taxableAmount'] + $amounts['taxAmount'];

			if ($hasFc) {
				$this->addElement($taxSubtotal, 'TaxableAmountCurr', self::formatAmount($amounts['taxableAmount'] / $fcRate));
			}

			$this->addElement($taxSubtotal, 'TaxableAmount', self::formatAmount($amounts['taxableAmount']));

			if ($hasFc) {
				$this->addElement($taxSubtotal, 'TaxAmountCurr', self::formatAmount($amounts['taxAmount'] / $fcRate));
			}

			$this->addElement($taxSubtotal, 'TaxAmount', self::formatAmount($amounts['taxAmount']));

			if ($hasFc) {
				$this->addElement($taxSubtotal, 'TaxInclusiveAmountCurr', self::formatAmount($taxInclusiveAmount / $fcRate));
			}

			$this->addElement($taxSubtotal, 'TaxInclusiveAmount', self::formatAmount($taxInclusiveAmount));

			if ($hasFc) {
				$this->addElement($taxSubtotal, 'AlreadyClaimedTaxableAmountCurr', '0.00');
			}

			$this->addElement($taxSubtotal, 'AlreadyClaimedTaxableAmount', '0.00');

			if ($hasFc) {
				$this->addElement($taxSubtotal, 'AlreadyClaimedTaxAmountCurr', '0.00');
			}

			$this->addElement($taxSubtotal, 'AlreadyClaimedTaxAmount', '0.00');

			if ($hasFc) {
				$this->addElement($taxSubtotal, 'AlreadyClaimedTaxInclusiveAmountCurr', '0.00');
			}

			$this->addElement($taxSubtotal, 'AlreadyClaimedTaxInclusiveAmount', '0.00');

			if ($hasFc) {
				$this->addElement($taxSubtotal, 'DifferenceTaxableAmountCurr', self::formatAmount($amounts['taxableAmount'] / $fcRate));
			}

			$this->addElement($taxSubtotal, 'DifferenceTaxableAmount', self::formatAmount($amounts['taxableAmount']));

			if ($hasFc) {
				$this->addElement($taxSubtotal, 'DifferenceTaxAmountCurr', self::formatAmount($amounts['taxAmount'] / $fcRate));
			}

			$this->addElement($taxSubtotal, 'DifferenceTaxAmount', self::formatAmount($amounts['taxAmount']));

			if ($hasFc) {
				$this->addElement($taxSubtotal, 'DifferenceTaxInclusiveAmountCurr', self::formatAmount($taxInclusiveAmount / $fcRate));
			}

			$this->addElement($taxSubtotal, 'DifferenceTaxInclusiveAmount', self::formatAmount($taxInclusiveAmount));

			$taxCategory = $this->addElement($taxSubtotal, 'TaxCategory');
			$this->addElement($taxCategory, 'Percent', $rate);

			if ($isReverseCharge) {
				$this->addElement($taxCategory, 'LocalReverseChargeFlag', 'true');
			}
		}

		if ($hasFc) {
			$this->addElement($taxTotal, 'TaxAmountCurr', self::formatAmount($totalTaxAmount / $fcRate));
		}

		$this->addElement($taxTotal, 'TaxAmount', self::formatAmount($totalTaxAmount));
	}

	private function addLegalMonetaryTotal(DOMElement $parent): void
	{
		$items = $this->invoice->getItems();
		$zeroVat = $this->isZeroVat();

		$totalWithoutVat = 0.0;
		$totalWithVat = 0.0;

		foreach ($items as $item) {
			$lineTotal = $item->getLineTotal();
			$totalWithoutVat += $lineTotal;
			$totalWithVat += $zeroVat ? $lineTotal : $item->getLineTotalWithVat();
		}

		$roundingAmount = 0.0;

		if ($this->invoice->isRoundTotal()) {
			$rounded = round($totalWithVat);
			$roundingAmount = $rounded - $totalWithVat;
			$totalWithVat = $rounded;
		}

		$monetary = $this->addElement($parent, 'LegalMonetaryTotal');
		$hasFc = $this->hasForeignCurrency();
		$fcRate = $hasFc ? $this->invoice->getCurrencyRate() : 1.0;
		$netAmount = $totalWithVat - $roundingAmount;

		$this->addElement($monetary, 'TaxExclusiveAmount', self::formatAmount($totalWithoutVat));

		if ($hasFc) {
			$this->addElement($monetary, 'TaxExclusiveAmountCurr', self::formatAmount($totalWithoutVat / $fcRate));
		}

		$this->addElement($monetary, 'TaxInclusiveAmount', self::formatAmount($netAmount));

		if ($hasFc) {
			$this->addElement($monetary, 'TaxInclusiveAmountCurr', self::formatAmount($netAmount / $fcRate));
		}

		$this->addElement($monetary, 'AlreadyClaimedTaxExclusiveAmount', '0.00');

		if ($hasFc) {
			$this->addElement($monetary, 'AlreadyClaimedTaxExclusiveAmountCurr', '0.00');
		}

		$this->addElement($monetary, 'AlreadyClaimedTaxInclusiveAmount', '0.00');

		if ($hasFc) {
			$this->addElement($monetary, 'AlreadyClaimedTaxInclusiveAmountCurr', '0.00');
		}

		$this->addElement($monetary, 'DifferenceTaxExclusiveAmount', self::formatAmount($totalWithoutVat));

		if ($hasFc) {
			$this->addElement($monetary, 'DifferenceTaxExclusiveAmountCurr', self::formatAmount($totalWithoutVat / $fcRate));
		}

		$this->addElement($monetary, 'DifferenceTaxInclusiveAmount', self::formatAmount($netAmount));

		if ($hasFc) {
			$this->addElement($monetary, 'DifferenceTaxInclusiveAmountCurr', self::formatAmount($netAmount / $fcRate));
		}

		$this->addElement($monetary, 'PayableRoundingAmount', self::formatAmount($roundingAmount));

		if ($hasFc) {
			$this->addElement($monetary, 'PayableRoundingAmountCurr', self::formatAmount($roundingAmount / $fcRate));
		}

		$this->addElement($monetary, 'PaidDepositsAmount', '0.00');

		if ($hasFc) {
			$this->addElement($monetary, 'PaidDepositsAmountCurr', '0.00');
		}

		$this->addElement($monetary, 'PayableAmount', self::formatAmount($totalWithVat));

		if ($hasFc) {
			$this->addElement($monetary, 'PayableAmountCurr', self::formatAmount($totalWithVat / $fcRate));
		}
	}

	private function addPaymentMeans(DOMElement $parent): void
	{
		$totalWithVat = $this->calculateTotalWithVat();
		$paymentInfo = $this->invoice->payment;

		$paymentMeans = $this->addElement($parent, 'PaymentMeans');
		$payment = $this->addElement($paymentMeans, 'Payment');
		$this->addElement($payment, 'PaidAmount', self::formatAmount($totalWithVat));
		$this->addElement($payment, 'PaymentMeansCode', '42');

		if (!$paymentInfo->hasBankAccount()) {
			return;
		}

		$details = $this->addElement($payment, 'Details');

		if ($this->invoice->getDueDate() !== '') {
			$this->addElement($details, 'PaymentDueDate', $this->invoice->getDueDate());
		}

		$this->addElement($details, 'ID', $paymentInfo->getAccountNumber());
		$this->addElement($details, 'BankCode', $paymentInfo->getBankCode());
		$this->addElement($details, 'Name', CzechBankDatabase::getBankName($paymentInfo->getBankCode()));
		$this->addElement($details, 'IBAN', CzechBankDatabase::generateIban($paymentInfo->getAccountNumber(), $paymentInfo->getBankCode()));

		$bic = CzechBankDatabase::getBic($paymentInfo->getBankCode());

		if ($bic !== null) {
			$this->addElement($details, 'BIC', $bic);
		}

		if ($paymentInfo->getVariableSymbol() !== '') {
			$this->addElement($details, 'VariableSymbol', $paymentInfo->getVariableSymbol());
		}

		if ($paymentInfo->getConstantSymbol() !== '') {
			$this->addElement($details, 'ConstantSymbol', $paymentInfo->getConstantSymbol());
		}

		if ($paymentInfo->getSpecificSymbol() !== '') {
			$this->addElement($details, 'SpecificSymbol', $paymentInfo->getSpecificSymbol());
		}
	}

	private function hasForeignCurrency(): bool
	{
		return $this->invoice->getForeignCurrencyCode() !== '';
	}

	private function isZeroVat(): bool
	{
		return !$this->invoice->isVatPayer() || $this->invoice->isReverseCharge();
	}

	private function calculateTotalWithVat(): float
	{
		$zeroVat = $this->isZeroVat();
		$totalWithVat = 0.0;

		foreach ($this->invoice->getItems() as $item) {
			$totalWithVat += $zeroVat ? $item->getLineTotal() : $item->getLineTotalWithVat();
		}

		if ($this->invoice->isRoundTotal()) {
			$totalWithVat = round($totalWithVat);
		}

		return $totalWithVat;
	}

	private function addElement(DOMElement $parent, string $name, ?string $value = null): DOMElement
	{
		$element = $this->xml->createElementNS(self::NAMESPACE_URI, $name);
		$parent->appendChild($element);

		if ($value !== null) {
			$element->nodeValue = htmlspecialchars($value, ENT_XML1, 'UTF-8');
		}

		return $element;
	}

	private static function formatAmount(float $amount): string
	{
		return number_format($amount, 2, '.', '');
	}

	private static function generateUuid(): string
	{
		$data = random_bytes(16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);

		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}
}
