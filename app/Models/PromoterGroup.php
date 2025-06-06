<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromoterGroup extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'code', 'company_id', 'type_id', 'created_by_id', 'created_by_type'];

    protected $casts = [
        'type_id' => SaleReturnOrVoidSaleReasonTypes::class,
    ];

    public function getName(): string
    {
        return $this->name;
    }
}
