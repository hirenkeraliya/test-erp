<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Models\Location;

test('Sequence can be added', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $typeId = SequenceTypes::TO->value;

    $sequenceQueries = new SequenceQueries();
    $sequenceQueries->addNew($location->id, $typeId);

    $this->assertDatabaseHas('sequences', [
        'location_id' => $location->id,
        'type_id' => $typeId,
        'number' => '00000001',
    ]);
});
