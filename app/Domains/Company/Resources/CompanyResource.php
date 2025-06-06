<?php

declare(strict_types=1);

namespace App\Domains\Company\Resources;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $company = $this->resource;

        return [
            'id' => $company->id,
            'name' => $company->name,
            'code' => $company->code,
            'email' => $company->email,
            'is_email_verified' => $company->is_email_verified,
            'currency_rate_auto_update' => $company->currency_rate_auto_update,
            'light_logo' => $company->getDiskBasedFirstMediaUrl('light_logo'),
            'dark_logo' => $company->getDiskBasedFirstMediaUrl('dark_logo'),
            'deleted_at' => $company->deleted_at,
            'is_restore' => $this->isRestore($company),
        ];
    }

    private function isRestore(Company $company): bool
    {
        if (null === $company->deleted_at) {
            return false;
        }

        return now()->format('Y-m-d H:i:s') <= $company->deleted_at->addDay()->format('Y-m-d H:i:s');
    }
}
