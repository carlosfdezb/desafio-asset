<?php

namespace Database\Factories;

use App\Models\Slug;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SlugFactory extends Factory
{
  protected $model = Slug::class;

  public function definition(): array
  {
    return [
      'original_url' => fake()->url(),
      'slug' => Str::lower(Str::random(8)),
      'api_key' => Hash::make(Str::random(32)),
    ];
  }
}
