<?php

namespace Database\Factories;

use App\Models\ClientToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClientToken>
 */
class ClientTokenFactory extends Factory
{
    protected $model = ClientToken::class;

    public function definition(): array
    {
        return [
            'name'      => $this->faker->company() . ' Client Token',
            'token'     => Str::random(40),
            'is_active' => true,
        ];
    }
}