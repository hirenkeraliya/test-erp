<?php

declare(strict_types=1);

use App\Domains\Tag\DataObjects\TagData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('name is compulsory while adding a tag.', function (): void {
    $tag = [
        'name' => '',
    ];
    $request = new Request($tag);
    $request->validate(TagData::rules());
})->throws(ValidationException::class);
