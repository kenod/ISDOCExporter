<?php declare(strict_types=1);

namespace Kenod\IsdocExporter;

final class Party
{
	private string $name = '';

	private string $street = '';

	private string $city = '';

	private string $zip = '';

	private string $country = '';

	private string $companyId = '';

	private string $vatId = '';

	private string $registerInfo = '';

	private string $email = '';

	private string $phone = '';

	private string $web = '';

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): self
	{
		$this->name = $name;

		return $this;
	}

	public function getStreet(): string
	{
		return $this->street;
	}

	public function setStreet(string $street): self
	{
		$this->street = $street;

		return $this;
	}

	public function getCity(): string
	{
		return $this->city;
	}

	public function setCity(string $city): self
	{
		$this->city = $city;

		return $this;
	}

	public function getZip(): string
	{
		return $this->zip;
	}

	public function setZip(string $zip): self
	{
		$this->zip = $zip;

		return $this;
	}

	public function getCountry(): string
	{
		return $this->country;
	}

	public function setCountry(string $country): self
	{
		$this->country = $country;

		return $this;
	}

	public function getCompanyId(): string
	{
		return $this->companyId;
	}

	public function setCompanyId(string $companyId): self
	{
		$this->companyId = $companyId;

		return $this;
	}

	public function getVatId(): string
	{
		return $this->vatId;
	}

	public function setVatId(string $vatId): self
	{
		$this->vatId = $vatId;

		return $this;
	}

	public function getRegisterInfo(): string
	{
		return $this->registerInfo;
	}

	public function setRegisterInfo(string $registerInfo): self
	{
		$this->registerInfo = $registerInfo;

		return $this;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function setEmail(string $email): self
	{
		$this->email = $email;

		return $this;
	}

	public function getPhone(): string
	{
		return $this->phone;
	}

	public function setPhone(string $phone): self
	{
		$this->phone = $phone;

		return $this;
	}

	public function getWeb(): string
	{
		return $this->web;
	}

	public function setWeb(string $web): self
	{
		$this->web = $web;

		return $this;
	}
}
