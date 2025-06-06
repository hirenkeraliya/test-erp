<?php

declare(strict_types=1);

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Attribute\DataObjects\AttributeData;
use App\Http\Controllers\Admin\TemplateAttributeController;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

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
            $mock->shouldReceive('templateAttributeListQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $templateAttributeController = new TemplateAttributeController($attributeQueries);

        $response = $templateAttributeController->fetchTemplateAttributes(new Request($requestParameter));

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

        $attributeQueries = $this->mock(AttributeQueries::class, function ($mock) use ($attributeData): void {
            $mock->shouldReceive('addTemplateAttributeNew')
                ->once()
                ->with($attributeData, 1);
        });

        $templateAttributeController = new TemplateAttributeController($attributeQueries);
        $redirectResponse = $templateAttributeController->store($attributeData);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Attribute added successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString('admin/template-attributes', $redirectResponse->getTargetUrl());
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

    $attribute = Attribute::factory()->make([
        'id' => 1,
        'template_id' => 1,
        'company_id' => $companyId,
    ]);

    $attributeQueries = $this->mock(AttributeQueries::class, function ($mock) use (
        $attributeData,
        $attribute,
        $companyId
    ): void {
        $mock->shouldReceive('updateTemplateAttribute')
            ->once()
            ->with($attributeData, $attribute->id, $companyId);
    });

    $templateAttributeController = new TemplateAttributeController($attributeQueries);
    $redirectResponse = $templateAttributeController->update($attributeData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Attribute updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/template-attributes', $redirectResponse->getTargetUrl());
});

it('deletes the attribute successfully', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $attributeQueries = $this->mock(AttributeQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('doesAttributeExistInTemplate')
            ->once()
            ->with(1, $companyId)
            ->andReturn(false);

        $mock->shouldReceive('deleteTemplateAttribute')
            ->once()
            ->with(1, $companyId);
    });

    $templateAttributeController = new TemplateAttributeController($attributeQueries);
    $redirectResponse = $templateAttributeController->delete(1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Attribute deleted successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/template-attributes', $redirectResponse->getTargetUrl());
});
