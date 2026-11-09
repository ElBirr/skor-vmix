<?php

namespace Database\Factories;

use App\Models\LowerThird;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LowerThird>
 */
class LowerThirdFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => strtoupper(fake()->name()),
            'subtitle' => fake()->sentence(3),
            'logo' => null,
            'duration' => 0,
            'is_live' => false,
            'shown_at' => null,
        ];
    }

    /**
     * Lower-third yang sedang tayang.
     */
    public function live(int $duration = 0): static
    {
        return $this->state(fn (): array => [
            'is_live' => true,
            'shown_at' => now(),
            'duration' => $duration,
        ]);
    }
}
