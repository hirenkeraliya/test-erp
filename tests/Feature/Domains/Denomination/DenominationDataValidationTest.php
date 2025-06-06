<?php

declare(strict_types=1);

use App\Domains\Denomination\DataObjects\DenominationData;
use Illuminate\Http\Request;

test('user can add denomination.', function (): void {
    $request = new Request([
        'denomination' => 100,
    ]);

    DenominationData::validate($request);
    $this->assertTrue(true);
});
