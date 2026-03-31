<?php declare(strict_types=1);

namespace Kenod\IsdocExporter;

final class PaymentInfo
{
	private string $accountNumber = '';

	private string $bankCode = '';

	private string $variableSymbol = '';

	private string $constantSymbol = '';

	private string $specificSymbol = '';

	public function getAccountNumber(): string
	{
		return $this->accountNumber;
	}

	public function setAccountNumber(string $accountNumber): self
	{
		$this->accountNumber = $accountNumber;

		return $this;
	}

	public function getBankCode(): string
	{
		return $this->bankCode;
	}

	public function setBankCode(string $bankCode): self
	{
		$this->bankCode = $bankCode;

		return $this;
	}

	public function getVariableSymbol(): string
	{
		return $this->variableSymbol;
	}

	public function setVariableSymbol(string $variableSymbol): self
	{
		$this->variableSymbol = $variableSymbol;

		return $this;
	}

	public function getConstantSymbol(): string
	{
		return $this->constantSymbol;
	}

	public function setConstantSymbol(string $constantSymbol): self
	{
		$this->constantSymbol = $constantSymbol;

		return $this;
	}

	public function getSpecificSymbol(): string
	{
		return $this->specificSymbol;
	}

	public function setSpecificSymbol(string $specificSymbol): self
	{
		$this->specificSymbol = $specificSymbol;

		return $this;
	}

	public function hasBankAccount(): bool
	{
		return $this->accountNumber !== '' && $this->bankCode !== '';
	}
}
