<?php

declare(strict_types=1);

use App\Domains\Attribute\DataObjects\AttributeData;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;

test('attribute with field type toggle is created successfully', function (): void {
    $attribute = [
        'default_value' => true,
        'description' => 'Test toggle',
        'field_type' => 1,
        'from' => null,
        'is_required' => true,
        'name' => 'Toggle 1',
        'options' => null,
        'status' => true,
        'to' => null,
    ];
    $request = new Request($attribute);
    $request->validate(AttributeData::rules($request));
})->throwsNoExceptions();

test('attribute with field type decimal is created successfully', function (): void {
    $attribute = [
        'default_value' => 50.50,
        'description' => 'decimal desc',
        'field_type' => 2,
        'from' => 50,
        'is_required' => true,
        'name' => 'Decimal 1',
        'options' => null,
        'status' => true,
        'to' => 100,
    ];
    $request = new Request($attribute);
    $request->validate(AttributeData::rules($request));
})->throwsNoExceptions();

test('attribute with field type number is created successfully', function (): void {
    $attribute = [
        'default_value' => 40,
        'description' => 'number desc',
        'field_type' => 3,
        'from' => 10,
        'is_required' => true,
        'name' => 'Number 1',
        'options' => null,
        'status' => true,
        'to' => 100,
    ];
    $request = new Request($attribute);
    $request->validate(AttributeData::rules($request));
})->throwsNoExceptions();

test('attribute with field type text is created successfully', function (): void {
    $attribute = [
        'default_value' => 'default text here',
        'description' => null,
        'field_type' => 4,
        'from' => null,
        'is_required' => true,
        'name' => 'Text',
        'options' => null,
        'status' => true,
        'to' => null,
    ];
    $request = new Request($attribute);
    $request->validate(AttributeData::rules($request));
})->throwsNoExceptions();

test('attribute with field type date is created successfully', function (): void {
    $attribute = [
        'default_value' => '2024-05-15',
        'description' => null,
        'field_type' => 5,
        'from' => '2024-05-07',
        'is_required' => true,
        'name' => 'Date 1',
        'options' => null,
        'status' => true,
        'to' => '2024-05-15',
    ];
    $request = new Request($attribute);
    $request->validate(AttributeData::rules($request));
})->throwsNoExceptions();

test('attribute with field type datetime is created successfully', function (): void {
    $attribute = [
        'default_value' => '2024-05-23 16:39:00',
        'description' => null,
        'field_type' => 6,
        'from' => '2024-05-08 16:39:00',
        'is_required' => true,
        'name' => 'Datetime 1',
        'options' => null,
        'status' => true,
        'to' => '2024-05-31 16:39:00',
    ];
    $request = new Request($attribute);
    $request->validate(AttributeData::rules($request));
})->throwsNoExceptions();

test('attribute with field type select is created successfully', function (): void {
    $attribute = [
        'default_value' => null,
        'description' => null,
        'field_type' => 7,
        'from' => null,
        'is_required' => true,
        'name' => 'Select ttest',
        'options' => ['option 1', 'option 2', 'option 3'],
        'status' => true,
        'to' => null,
    ];
    $request = new Request($attribute);
    $request->validate(AttributeData::rules($request));
})->throwsNoExceptions();

test('attribute with field type list is created successfully', function (): void {
    $attribute = [
        'default_value' => null,
        'description' => null,
        'field_type' => 8,
        'from' => null,
        'is_required' => true,
        'name' => 'List Test',
        'options' => ['Option 1'],
        'status' => true,
        'to' => null,
    ];
    $request = new Request($attribute);
    $request->validate(AttributeData::rules($request));
})->throwsNoExceptions();

test('attribute name is required.', function (): void {
    $attribute = [
        'description' => 'Attribute Desc',
        'field_type' => 1,
        'options' => null,
        'from' => null,
        'to' => null,
        'default_value' => true,
        'status' => true,
        'is_required' => true,
    ];
    $request = new Request($attribute);
    $request->validate(AttributeData::rules($request));
})->throws(ValidationException::class);

test('attribute field_type is required.', function (): void {
    $attribute = [
        'description' => 'Attribute Desc',
        'name' => 'some attribute name',
        'options' => null,
        'from' => null,
        'to' => null,
        'default_value' => true,
        'status' => true,
        'is_required' => true,
    ];
    $request = new Request($attribute);
    $request->validate(AttributeData::rules($request));
})->throws(ValidationException::class);

test('default value should be between from and to for decimal', function (): void {
    $attribute = [
        'default_value' => 45.3,
        'description' => 'decimal desc',
        'field_type' => 2,
        'from' => '50',
        'is_required' => true,
        'name' => 'Decimal 1',
        'options' => null,
        'status' => true,
        'to' => '100',
    ];
    $request = new Request($attribute);
    $request->validate(AttributeData::rules($request));
})->throws(ValidationException::class);

test('default value should be between from and to for date', function (): void {
    $attribute = [
        'default_value' => '2024-04-15',
        'description' => null,
        'field_type' => 5,
        'from' => '2024-05-07',
        'is_required' => true,
        'name' => 'Date 1',
        'options' => null,
        'status' => true,
        'to' => '2024-05-15',
    ];
    $request = new Request($attribute);
    $request->validate(AttributeData::rules($request));
})->throws(ValidationException::class);

test('default value should be between from the added options', function (): void {
    $attribute = [
        'default_value' => 'option 33',
        'description' => null,
        'field_type' => 7,
        'from' => null,
        'is_required' => true,
        'name' => 'Select ttest',
        'options' => ['option 1', 'option 2', 'option 3'],
        'status' => true,
        'to' => null,
    ];
    $request = new Request($attribute);
    $request->validate(AttributeData::rules($request));
})->throws(ValidationException::class);

function mockRouterParameterRequest($templateId, $attributeId, $requestData): Request
{
    $routeMock = Mockery::mock(Route::class);

    $routeMock->shouldReceive('parameter')
        ->with('templateId')
        ->andReturn($templateId);

    $routeMock->shouldReceive('getName')
        ->andReturn('admin.attributes.store');

    $routeMock->shouldReceive('originalParameter')
        ->with('templateId')
        ->andReturn($templateId);

    $routeMock->shouldReceive('originalParameter')
        ->with('attributeId')
        ->andReturn($attributeId);

    $request = new Request($requestData);
    $request->setRouteResolver(fn () => $routeMock);

    return $request;
}
