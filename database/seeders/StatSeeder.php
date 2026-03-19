<?php

namespace Database\Seeders;

use App\Models\Slug;
use App\Models\Stat;
use Illuminate\Database\Seeder;

class StatSeeder extends Seeder
{
  public function run(): void
  {
    $slugIds = Slug::pluck('id')->toArray();

    // Insert in chunks of 5000 to avoid memory issues
    $chunkSize = 5000;
    $total = 100_000;
    $now = now()->toDateTimeString();

    for ($i = 0; $i < $total; $i += $chunkSize) {
      $records = [];
      $count = min($chunkSize, $total - $i);

      for ($j = 0; $j < $count; $j++) {
        $records[] = [
          'slug_id' => fake()->randomElement($slugIds),
          'referer_host' => fake()->domainName(),
          'country' => fake()->country(),
          'clicked_at' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d H:i:s'),
          'created_at' => $now,
          'updated_at' => $now,
        ];
      }

      Stat::insert($records);
    }
  }
}
