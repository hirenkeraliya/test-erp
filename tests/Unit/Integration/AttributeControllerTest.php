<?php

declare(strict_types=1);

use App\Domains\Attribute\AttributeQueries;
use App\Http\Controllers\Api\Integration\AttributeController;
use App\Models\Integration;
use Illuminate\Http\Request;

test('It calls the getAllAttributes method of the attributeQueries class', function (): void {
    $integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Integration => $integration);

    $attributeData = [
        [
            'id' => 100,
            'company_id' => 10,
            'name' => 'TEST',
            'description' => '123',
            'field_type' => 'text',
            'default_value' => '123',
            'from' => 100,
            'to' => 100,
            'options' => [
                'option1' => 'option1',
                'option2' => 'option2',
            ],
            'is_required' => 100,
        ],
    ];

    $this->mock(AttributeQueries::class, function ($mock) use ($attributeData): void {
        $mock->shouldReceive('getAllAttributesByCompanyId')
            ->once()
            ->andReturn(collect($attributeData));
    });

    $attributeController = new AttributeController();
    $response = $attributeController->getAllAttributes($request);

    expect($response['attributes']->first())->toHaveKeys([
        'id',
        'company_id',
        'name',
        'description',
        'field_type',
        'default_value',
        'from',
        'to',
        'options',
        'is_required',
    ]);
});
