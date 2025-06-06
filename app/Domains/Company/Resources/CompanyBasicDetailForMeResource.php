<?php

declare(strict_types=1);

namespace App\Domains\Company\Resources;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyBasicDetailForMeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Company $company */
        $company = $this;

        return [
            'name' => $company->name,
            'email' => $company->email,
            'employer_identification_number' => $company->employer_identification_number,
            'social_security_number' => $company->social_security_number,
        ];
    }
}
