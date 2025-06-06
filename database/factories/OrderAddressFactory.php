<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\OrderAddress\Enums\OrderAddressesType;
use App\Models\City;
use App\Models\Country;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<OrderAddress>
 */
class OrderAddressFactory extends Factory
{
    protected bool $isCreating = false;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'order_id' => fn () => $this->isCreating ? Order::factory()->create()->id : 1,
            'type_id' => array_rand(array_flip(array_column(OrderAddressesType::cases(), 'value'))),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->phoneNumber(),
            'address_line_1' => fake()->address(),
            'address_line_2' => fake()->address(),
            'country_code' => fake()->countryCode(),
            'country_id' => fn () => $this->isCreating ? Country::factory()->create()->id : 1,
            'state_id' => fn () => $this->isCreating ? State::factory()->create()->id : 1,
            'city_id' => fn () => $this->isCreating ? City::factory()->create()->id : 1,
            'area_code' => fake()->countryCode(),
        ];
    }

    /**
     * Create a new instance of the model.
     *
     * @param array<string, mixed> $attributes
     */
    public function create($attributes = [], ?Model $parent = null): OrderAddress
    {
        $this->isCreating = true;

        return parent::create($attributes, $parent);
    }
}
