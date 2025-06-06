<?php

declare(strict_types=1);

namespace App\Domains\Tag\DataObjects;

use App\Models\Tag;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TagExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $tags
    ) {
    }

    public function collection(): Collection
    {
        return $this->tags->map(fn (Tag $tag): array => [
            'name' => $tag->name,
        ]);
    }

    public function headings(): array
    {
        return ['Name'];
    }
}
