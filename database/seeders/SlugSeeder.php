<?php

namespace Database\Seeders;

use App\Models\Slug;
use Illuminate\Database\Seeder;

class SlugSeeder extends Seeder
{
  public function run(): void
  {
    Slug::factory()->count(50)->create();
    Slug::factory()->count(50)->create(['api_key' => null]);
  }
}
