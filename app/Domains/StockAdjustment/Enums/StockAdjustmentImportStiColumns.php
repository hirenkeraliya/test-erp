<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustment\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StockAdjustmentImportStiColumns: string
{
    use PrepareEnumDataMethods;

    case LOCATION_TYPE = 'location_type';
    case LOCATION_NAME = 'location_name';
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
}
