<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'formID' => 'company' . Str::random(10),
            'company_name' => fake()->company(),
            'company_image' => null,
            'company_type' => fake()->randomElement(['broiler', 'slaughterhouse', 'sme', 'logistic']),
        ];
    }
    
    /**
     * Set the company type.
     */
    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'company_type' => $type,
        ]);
    }
}