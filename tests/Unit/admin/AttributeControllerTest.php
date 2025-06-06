<?php

declare(strict_types=1);

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Attribute\DataObjects\AttributeData;
use App\Domains\Attribute\DataObjects\AttributeOldData;
use App\Domains\Template\TemplateQueries;
use App\Http\Controllers\Admin\AttributeController;
use App\Models\Attribute;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

it(
    'lists attributes successfully',
    function (): void {
        $companyId = 1;

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'field_type',
            'sort_direction' => 'asc',
            'per_page' => '15',
        ];

        setCompanyIdInSession($companyId);

        $attributeQueries = $this->mock(AttributeQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, 1, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $attributeController = new AttributeController($attributeQueries);

        $response = $attributeController->fetchAttributes(new Request($requestParameter), 1);

        expect($response['total_records'])->toBe(50);
        expect($response['data'])->toBeObject();
    }
);

it(
    'stores attributes successfully',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $attributeData = new AttributeData(
            name: 'Attribute Name',
            description: 'Attribute Desc',
            field_type: 1,
            options: null,
            from: null,
            to: null,
            default_value: 'true',
            is_required: true,
        );

        $requestParameter = [
            'name' => 'xyz',
        ];

        $templateQueries = $this->mock(TemplateQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('selectTemplateId')
                ->once()
                ->with(1, $companyId)
                ->andReturn(new Template($requestParameter));
        });

        $attributeQueries = $this->mock(AttributeQueries::class, function ($mock) use ($attributeData): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->with($attributeData, 1, 1);
        });

        $attributeController = new AttributeController($attributeQueries);
        $redirectResponse = $attributeController->store($attributeData, 1);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Attribute added successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString(
            'admin/templates/' . 1 . '/attributes',
            $redirectResponse->getTargetUrl()
        );
    }
);

it(
    'storeOld attributes successfully',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $attributeOldData = new AttributeOldData(attribute_id: 1, existing_attribute: false);

        $attribute = Attribute::factory()->make([
            'name' => 'New Attribute',
            'company_id' => $companyId,
        ]);

        $attributeQueries = $this->mock(AttributeQueries::class, function ($mock) use ($attribute): void {
            $mock->shouldReceive('getById')
                ->once()
                ->with(1, 1)
                ->andReturn($attribute);

            $mock->shouldReceive('attachTemplate')
                ->once()
                ->with($attribute, 1);
        });

        $attributeController = new AttributeController($attributeQueries);
        $redirectResponse = $attributeController->storeOld($attributeOldData, 1);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Attribute added successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString(
            'admin/templates/' . 1 . '/attributes',
            $redirectResponse->getTargetUrl()
        );
    }
);

it(
    'calls for single attribute edit',
    function (): void {
        $companyId = 1;

        $template = Template::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'name' => 'New Template',
        ]);

        $attribute = Attribute::factory()->make([
            'name' => 'New Attribute',
            'company_id' => $companyId,
        ]);

        setCompanyIdInSession($companyId);

        $attributeQueries = $this->mock(AttributeQueries::class, function ($mock) use ($attribute): void {
            $mock->shouldReceive('getById')
                ->once()
                ->with(1, 1)
                ->andReturn($attribute);
        });

        $this->mock(TemplateQueries::class, function ($mock) use ($template, $companyId): void {
            $mock->shouldReceive('getById')
                ->once()
                ->with(1, $companyId)
                ->andReturn($template);
        });

        $attributeController = new AttributeController($attributeQueries);

        $response = $attributeController->edit(1, 1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
                ->has(
                    'attribute.data',
                    fn (Assert $attribute): Assert => $attribute
                        ->where('name', 'New Attribute')
                        ->etc()
                )
                ->etc()
        );
    }
);

it('updates attribute successfully', function (): void {
    $attributeData = new AttributeData(
        name: 'Attribute Name',
        description: 'Attribute Desc',
        field_type: 1,
        options: null,
        from: null,
        to: null,
        default_value: 'true',
        is_required: true,
    );

    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'name' => 'xyz',
    ];

    Template::factory()->make([
        'company_id' => $companyId,
    ]);

    $attribute = Attribute::factory()->make([
        'template_id' => 1,
        'company_id' => $companyId,
    ]);

    $templateQueries = $this->mock(TemplateQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('selectTemplateId')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new Template($requestParameter));
    });

    $attributeQueries = $this->mock(AttributeQueries::class, function ($mock) use (
        $attributeData,
        $attribute,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($attributeData, $attribute['template_id'], 1, $companyId);
    });

    $attributeController = new AttributeController($attributeQueries);
    $redirectResponse = $attributeController->update($attributeData, $attribute['template_id'], 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Attribute updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString(
        'admin/templates/' . $attribute['template_id'] . '/attributes',
        $redirectResponse->getTargetUrl()
    );
});

it('deletes the attribute successfully', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $attributeQueries = $this->mock(AttributeQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('delete')
            ->once()
            ->with(1, 1, $companyId);
    });

    $attributeController = new AttributeController($attributeQueries);
    $redirectResponse = $attributeController->delete(1, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Attribute deleted successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/templates/' . 1 . '/attributes', $redirectResponse->getTargetUrl());
});
