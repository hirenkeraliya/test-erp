<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum GoodsReceivedNoteImportColumns: string
{
    use PrepareEnumDataMethods;

    case UPC = 'upc';
    case QUANTITY = 'quantity';
    case DERIVATIVE_NAME = 'derivative_name';
    case FOB = 'fob';
    case FREIGHT_CHARGES = 'freight_charges';
    case INSURANCE_CHARGES = 'insurance_charges';
    case DUTY = 'duty';
    case SST = 'sst';
    case HANDLING_CHARGES = 'handling_charges';
    case OTHER_CHARGES = 'other_charges';
    case BATCH_EXPIRY_DATE = 'batch_expiry_date';
    case BATCH_NUMBER = 'batch_number';
    case BATCH_NOTES = 'batch_notes';
    case BATCH_EXTERNAL_ID = 'batch_external_id';
    case SERIAL_NUMBER = 'serial_number';
}
