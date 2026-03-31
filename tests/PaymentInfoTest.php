<?php declare(strict_types=1);

namespace Kenod\IsdocExporter\Tests;

use Kenod\IsdocExporter\PaymentInfo;
use PHPUnit\Framework\TestCase;

final class PaymentInfoTest extends TestCase
{
	public function testFluentSetters(): void
	{
		$payment = new PaymentInfo();
		$result = $payment
			->setAccountNumber('19-2000145399')
			->setBankCode('0800')
			->setVariableSymbol('2024001')
			->setConstantSymbol('0308')
			->setSpecificSymbol('123');

		self::assertSame($payment, $result);
		self::assertSame('19-2000145399', $payment->getAccountNumber());
		self::assertSame('0800', $payment->getBankCode());
		self::assertSame('2024001', $payment->getVariableSymbol());
		self::assertSame('0308', $payment->getConstantSymbol());
		self::assertSame('123', $payment->getSpecificSymbol());
		self::assertTrue($payment->hasBankAccount());
	}

	public function testDefaultsAreEmpty(): void
	{
		$payment = new PaymentInfo();

		self::assertSame('', $payment->getAccountNumber());
		self::assertSame('', $payment->getBankCode());
		self::assertSame('', $payment->getVariableSymbol());
		self::assertSame('', $payment->getConstantSymbol());
		self::assertSame('', $payment->getSpecificSymbol());
		self::assertFalse($payment->hasBankAccount());
	}

	public function testHasBankAccountRequiresBothFields(): void
	{
		$p1 = (new PaymentInfo())->setAccountNumber('123456');
		self::assertFalse($p1->hasBankAccount());

		$p2 = (new PaymentInfo())->setBankCode('0100');
		self::assertFalse($p2->hasBankAccount());

		$p3 = (new PaymentInfo())->setAccountNumber('123456')->setBankCode('0100');
		self::assertTrue($p3->hasBankAccount());
	}
}
