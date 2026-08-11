<?php

namespace Database\Factories;

use App\Models\AllowedDomain;
use App\Models\ClientToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AllowedDomain>
 */
class AllowedDomainFactory extends Factory
{
    protected $model = AllowedDomain::class;

    public function definition(): array
    {
        return [
            'client_token_id' => ClientToken::factory(),
            'domain'          => 'https://' . $this->faker->unique()->domainName(),
            'is_active'       => true,
        ];
    }
}