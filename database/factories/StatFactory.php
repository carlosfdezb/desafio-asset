<?php

namespace Database\Factories;

use App\Models\Slug;
use App\Models\Stat;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatFactory extends Factory
{
  protected $model = Stat::class;

  public function definition(): array
  {
    return [
      'slug_id' => Slug::factory(),
      'referer_host' => fake()->domainName(),
      'country' => fake()->country(),
      'clicked_at' => fake()->dateTimeBetween('-6 months', 'now'),
    ];
  }
}
