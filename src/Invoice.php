<?php declare(strict_types=1);

namespace Kenod\IsdocExporter;

final class Invoice
{
	public readonly Party $supplier;

	public readonly Party $customer;

	public readonly PaymentInfo $payment;

	/** @var list<InvoiceItem> */
	private array $items = [];

	private DocumentType $documentType = DocumentType::Invoice;

	private string $number = '';

	private string $issueDate = '';

	private string $dueDate = '';

	private string $deliveryDate = '';

	private bool $vatPayer = false;

	private bool $reverseCharge = false;

	private string $reverseChargeType = '';

	private string $footerText = '';

	private string $orderNumber = '';

	private string $originalDocumentNumber = '';

	private string $originalDocumentDate = '';

	private bool $roundTotal = false;

	private string $issuingSystem = '';

	private string $currencyCode = 'CZK';

	private string $foreignCurrencyCode = '';

	private float $currencyRate = 1.0;

	private float $refCurrencyRate = 1.0;

	public function __construct()
	{
		$this->supplier = new Party();
		$this->customer = new Party();
		$this->payment = new PaymentInfo();
	}

	public function getDocumentType(): DocumentType
	{
		return $this->documentType;
	}

	public function setDocumentType(DocumentType $documentType): self
	{
		$this->documentType = $documentType;

		return $this;
	}

	public function getNumber(): string
	{
		return $this->number;
	}

	public function setNumber(string $number): self
	{
		$this->number = $number;

		return $this;
	}

	public function getIssueDate(): string
	{
		return $this->issueDate;
	}

	public function setIssueDate(string $issueDate): self
	{
		$this->issueDate = $issueDate;

		return $this;
	}

	public function getDueDate(): string
	{
		return $this->dueDate;
	}

	public function setDueDate(string $dueDate): self
	{
		$this->dueDate = $dueDate;

		return $this;
	}

	public function getDeliveryDate(): string
	{
		return $this->deliveryDate;
	}

	public function setDeliveryDate(string $deliveryDate): self
	{
		$this->deliveryDate = $deliveryDate;

		return $this;
	}

	public function isVatPayer(): bool
	{
		return $this->vatPayer;
	}

	public function setVatPayer(bool $vatPayer): self
	{
		$this->vatPayer = $vatPayer;

		return $this;
	}

	public function isReverseCharge(): bool
	{
		return $this->reverseCharge;
	}

	public function setReverseCharge(bool $reverseCharge): self
	{
		$this->reverseCharge = $reverseCharge;

		return $this;
	}

	public function getReverseChargeType(): string
	{
		return $this->reverseChargeType;
	}

	public function setReverseChargeType(string $reverseChargeType): self
	{
		$this->reverseChargeType = $reverseChargeType;

		return $this;
	}

	public function getFooterText(): string
	{
		return $this->footerText;
	}

	public function setFooterText(string $footerText): self
	{
		$this->footerText = $footerText;

		return $this;
	}

	public function getOrderNumber(): string
	{
		return $this->orderNumber;
	}

	public function setOrderNumber(string $orderNumber): self
	{
		$this->orderNumber = $orderNumber;

		return $this;
	}

	public function getOriginalDocumentNumber(): string
	{
		return $this->originalDocumentNumber;
	}

	public function setOriginalDocumentNumber(string $originalDocumentNumber): self
	{
		$this->originalDocumentNumber = $originalDocumentNumber;

		return $this;
	}

	public function getOriginalDocumentDate(): string
	{
		return $this->originalDocumentDate;
	}

	public function setOriginalDocumentDate(string $originalDocumentDate): self
	{
		$this->originalDocumentDate = $originalDocumentDate;

		return $this;
	}

	public function isRoundTotal(): bool
	{
		return $this->roundTotal;
	}

	public function setRoundTotal(bool $roundTotal): self
	{
		$this->roundTotal = $roundTotal;

		return $this;
	}

	public function getIssuingSystem(): string
	{
		return $this->issuingSystem;
	}

	public function setIssuingSystem(string $issuingSystem): self
	{
		$this->issuingSystem = $issuingSystem;

		return $this;
	}

	public function getCurrencyCode(): string
	{
		return $this->currencyCode;
	}

	public function setCurrencyCode(string $currencyCode): self
	{
		$this->currencyCode = $currencyCode;

		return $this;
	}

	public function getForeignCurrencyCode(): string
	{
		return $this->foreignCurrencyCode;
	}

	public function setForeignCurrencyCode(string $foreignCurrencyCode): self
	{
		$this->foreignCurrencyCode = $foreignCurrencyCode;

		return $this;
	}

	public function getCurrencyRate(): float
	{
		return $this->currencyRate;
	}

	public function setCurrencyRate(float $currencyRate): self
	{
		$this->currencyRate = $currencyRate;

		return $this;
	}

	public function getRefCurrencyRate(): float
	{
		return $this->refCurrencyRate;
	}

	public function setRefCurrencyRate(float $refCurrencyRate): self
	{
		$this->refCurrencyRate = $refCurrencyRate;

		return $this;
	}

	/** @return list<InvoiceItem> */
	public function getItems(): array
	{
		return $this->items;
	}

	public function addItem(
		string $name,
		float $quantity,
		string $unit,
		float $unitPrice,
		float $unitPriceWithVat,
		float $vatRate,
		string $note = '',
	): self {
		$this->items[] = new InvoiceItem($name, $quantity, $unit, $unitPrice, $unitPriceWithVat, $vatRate, $note);

		return $this;
	}

	public function export(): IsdocExporter
	{
		return new IsdocExporter($this);
	}
}
