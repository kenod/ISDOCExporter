<?php declare(strict_types=1);

namespace Kenod\IsdocExporter;

final class InvoiceItem
{
	public function __construct(
		private string $name,
		private float $quantity,
		private string $unit,
		private float $unitPrice,
		private float $unitPriceWithVat,
		private float $vatRate,
		private string $note = '',
	) {
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): self
	{
		$this->name = $name;

		return $this;
	}

	public function getQuantity(): float
	{
		return $this->quantity;
	}

	public function setQuantity(float $quantity): self
	{
		$this->quantity = $quantity;

		return $this;
	}

	public function getUnit(): string
	{
		return $this->unit;
	}

	public function setUnit(string $unit): self
	{
		$this->unit = $unit;

		return $this;
	}

	public function getUnitPrice(): float
	{
		return $this->unitPrice;
	}

	public function setUnitPrice(float $unitPrice): self
	{
		$this->unitPrice = $unitPrice;

		return $this;
	}

	public function getUnitPriceWithVat(): float
	{
		return $this->unitPriceWithVat;
	}

	public function setUnitPriceWithVat(float $unitPriceWithVat): self
	{
		$this->unitPriceWithVat = $unitPriceWithVat;

		return $this;
	}

	public function getVatRate(): float
	{
		return $this->vatRate;
	}

	public function setVatRate(float $vatRate): self
	{
		$this->vatRate = $vatRate;

		return $this;
	}

	public function getNote(): string
	{
		return $this->note;
	}

	public function setNote(string $note): self
	{
		$this->note = $note;

		return $this;
	}

	public function getLineTotal(): float
	{
		return $this->unitPrice * $this->quantity;
	}

	public function getLineTotalWithVat(): float
	{
		return $this->unitPriceWithVat * $this->quantity;
	}

	public function getLineTaxAmount(): float
	{
		return ($this->unitPriceWithVat - $this->unitPrice) * $this->quantity;
	}
}
