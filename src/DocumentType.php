<?php declare(strict_types=1);

namespace Kenod\IsdocExporter;

enum DocumentType: int
{
	case Invoice = 1;
	case CreditNote = 2;
	case DebitNote = 3;
	case ProformaInvoice = 4;
	case AdvanceInvoice = 5;
	case AdvanceCreditNote = 6;
	case SimplifiedInvoice = 7;
}
