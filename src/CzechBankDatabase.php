<?php declare(strict_types=1);

namespace Kenod\IsdocExporter;

use function bcmod;
use function count;
use function explode;
use function str_pad;
use const STR_PAD_LEFT;

final class CzechBankDatabase
{
	private const array BANK_NAMES = [
		'0100' => 'Komerční banka, a.s.',
		'0300' => 'Československá obchodní banka, a. s.',
		'0600' => 'MONETA Money Bank, a.s.',
		'0710' => 'Česká národní banka',
		'0800' => 'Česká spořitelna, a.s.',
		'2010' => 'Fio banka, a.s.',
		'2020' => 'MUFG Bank (Europe) N.V. Prague Branch',
		'2060' => 'Citfin, spořitelní družstvo',
		'2070' => 'TRINITY BANK a.s.',
		'2100' => 'Hypoteční banka, a.s.',
		'2200' => 'Peněžní dům, spořitelní družstvo',
		'2220' => 'Artesa, spořitelní družstvo',
		'2240' => 'Poštová banka, a.s., pobočka Česká republika',
		'2250' => 'Banka CREDITAS a.s.',
		'2260' => 'NEY spořitelní družstvo',
		'2275' => 'Podnikatelská družstevní záložna',
		'2600' => 'Citibank Europe plc, organizační složka',
		'2700' => 'UniCredit Bank Czech Republic and Slovakia, a.s.',
		'3030' => 'Air Bank a.s.',
		'3050' => 'BNP Paribas Personal Finance SA, odštěpný závod',
		'3060' => 'PKO BP S.A., Czech Branch',
		'3500' => 'ING Bank N.V.',
		'4000' => 'Expobank CZ a.s.',
		'4300' => 'Českomoravská záruční a rozvojová banka, a.s.',
		'5500' => 'Raiffeisenbank a.s.',
		'5800' => 'J&T BANKA, a.s.',
		'6000' => 'PPF banka a.s.',
		'6100' => 'Equa bank a.s.',
		'6200' => 'COMMERZBANK Aktiengesellschaft, pobočka Praha',
		'6210' => 'mBank S.A., organizační složka',
		'6300' => 'BNP Paribas S.A., pobočka Česká republika',
		'6700' => 'Všeobecná úverová banka a.s., pobočka Praha',
		'6800' => 'Sberbank CZ, a.s.',
		'7910' => 'Deutsche Bank Aktiengesellschaft Filiale Prag',
		'7940' => 'Waldviertler Sparkasse Bank AG',
		'7950' => 'Raiffeisen stavební spořitelna a.s.',
		'7960' => 'Českomoravská stavební spořitelna, a.s.',
		'7970' => 'Wüstenrot - stavební spořitelna a.s.',
		'7980' => 'Wüstenrot hypoteční banka a.s.',
		'7990' => 'Modrá pyramida stavební spořitelna, a.s.',
		'8030' => 'Raiffeisenbank im Stiftland Waldsassen eG pobočka Cheb',
		'8040' => 'Oberbank AG pobočka Česká republika',
		'8060' => 'Stavební spořitelna České spořitelny, a.s.',
		'8090' => 'Česká exportní banka, a.s.',
		'8150' => 'HSBC Continental Europe, Czech Republic',
		'8200' => 'PRIVAT BANK AG der Raiffeisenlandesbank Oberösterreich Aktiengesellschaft, pobočka Česká republika',
		'8220' => 'Payment Execution s.r.o.',
		'8230' => 'EEPAYS s.r.o.',
		'8240' => 'Družstevní záložna Kredit',
	];

	private const array BIC_CODES = [
		'0100' => 'KOMBCZPP',
		'0300' => 'CEKOCZPP',
		'0600' => 'AGBACZPP',
		'0710' => 'CNBACZPP',
		'0800' => 'GIBACZPX',
		'2010' => 'FIOBCZPP',
		'2020' => 'BOTKCZPP',
		'2060' => 'CITFCZPP',
		'2070' => 'TRZPCZPP',
		'2100' => 'HYPOCZPP',
		'2200' => 'PNBPCZPP',
		'2220' => 'ARTTCZPP',
		'2240' => 'POBNCZPP',
		'2250' => 'CTASCZ22',
		'2260' => 'YNEYPLPR',
		'2275' => 'PDUNCZPP',
		'2600' => 'CITICZPX',
		'2700' => 'BACXCZPP',
		'3030' => 'AIRACZPP',
		'3050' => 'CETACZPP',
		'3060' => 'BPKOCZPP',
		'3500' => 'INGBCZPP',
		'4000' => 'EXPNCZPP',
		'4300' => 'CMZRCZP1',
		'5500' => 'RZBCCZPP',
		'5800' => 'JTBPCZPP',
		'6000' => 'PMBPCZPP',
		'6100' => 'EQBKCZPP',
		'6200' => 'COBACZPX',
		'6210' => 'BREXCZPP',
		'6300' => 'BNPACZPP',
		'6700' => 'SUBACZPP',
		'6800' => 'VBOECZ2X',
		'7910' => 'DEUTCZPX',
		'7940' => 'RVVGAT2B',
		'7950' => 'RZSTAT2G',
		'7960' => 'CASKCZPP',
		'7970' => 'WUSTCZPP',
		'7980' => 'WUSTCZP1',
		'7990' => 'MODPCZPP',
		'8030' => 'GENODED1WDB',
		'8040' => 'OBKLCZ2X',
		'8060' => 'STAVCZPP',
		'8090' => 'CZEECZPP',
		'8150' => 'MIDLCZPP',
		'8200' => 'PRRBAT2W',
		'8220' => 'PAERCZP1',
		'8230' => 'EEPACZPP',
		'8240' => 'KREDCZPP',
	];

	public static function getBankName(string $bankCode): string
	{
		return self::BANK_NAMES[$bankCode] ?? 'Unknown bank';
	}

	public static function getBic(string $bankCode): ?string
	{
		return self::BIC_CODES[$bankCode] ?? null;
	}

	public static function generateIban(string $accountNumber, string $bankCode): string
	{
		$parts = explode('-', $accountNumber);

		if (count($parts) === 2) {
			$prefix = $parts[0];
			$account = $parts[1];
		} else {
			$prefix = '';
			$account = $parts[0];
		}

		$prefix = str_pad($prefix, 6, '0', STR_PAD_LEFT);
		$account = str_pad($account, 10, '0', STR_PAD_LEFT);
		$bankCode = str_pad($bankCode, 4, '0', STR_PAD_LEFT);

		$bban = $bankCode . $prefix . $account;

		// CZ = 12 35, check digits = 98 - (BBAN . 123500) mod 97
		$temp = $bban . '123500';
		$mod = bcmod($temp, '97');
		$checkDigits = str_pad((string) (98 - (int) $mod), 2, '0', STR_PAD_LEFT);

		return 'CZ' . $checkDigits . $bban;
	}
}
