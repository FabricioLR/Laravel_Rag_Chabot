<?php

namespace Database\Factories;

use App\Models\AllowedDomain;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AllowedDomain>
 */
class AllowedDomainFactory extends Factory
{
    protected $model = AllowedDomain::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' ERP Origin',
            'domain' => 'https://' . $this->faker->unique()->domainName(),
            'token' => (string) Str::uuid(),
            'is_active' => true,
        ];
    }
}