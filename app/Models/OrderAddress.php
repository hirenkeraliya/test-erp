<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\OrderAddress\Enums\OrderAddressesType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAddress extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'type_id',
        'first_name',
        'last_name',
        'phone',
        'address_line_1',
        'address_line_2',
        'country_code',
        'country_id',
        'state_id',
        'city_id',
        'country_name',
        'state_name',
        'city_name',
        'area_code',
    ];

    protected $casts = [
        'type_id' => OrderAddressesType::class,
    ];

    // Relationships

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    // Get Methods

    public function getOrderId(): int
    {
        return $this->order_id;
    }

    public function getTypeId(): OrderAddressesType
    {
        return $this->type_id;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getAddressLine1(): string
    {
        return $this->address_line_1;
    }

    public function getAddressLine2(): ?string
    {
        return $this->address_line_2;
    }

    public function getCountryCode(): ?string
    {
        return $this->country_code;
    }

    public function getCountryId(): ?int
    {
        return $this->country_id;
    }

    public function getCountryName(): ?string
    {
        return $this->country_name;
    }

    public function getStateId(): ?int
    {
        return $this->state_id;
    }

    public function getStateName(): ?string
    {
        return $this->state_name;
    }

    public function getCityId(): ?int
    {
        return $this->city_id;
    }

    public function getCityName(): ?string
    {
        return $this->city_name;
    }

    public function getAreaCode(): string
    {
        return $this->area_code;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function getState(): ?State
    {
        return $this->state;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }
}
