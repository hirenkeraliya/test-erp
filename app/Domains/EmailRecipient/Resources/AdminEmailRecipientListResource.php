<?php

declare(strict_types=1);

namespace App\Domains\EmailRecipient\Resources;

use App\Domains\EmailRecipient\Enums\EmailTypes;
use App\Models\EmailRecipient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminEmailRecipientListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var EmailRecipient $emailRecipient */
        $emailRecipient = $this;

        return [
            'id' => $emailRecipient->id,
            'email_type' => EmailTypes::getFormattedCaseName($emailRecipient->email_type_id),
            'receiver_name' => $emailRecipient->receiver_name,
            'receiver_email' => $emailRecipient->receiver_email,
            'is_email_verified' => $emailRecipient->is_email_verified,
        ];
    }
}
