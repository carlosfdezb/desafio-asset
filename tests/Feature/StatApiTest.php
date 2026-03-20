<?php

namespace Tests\Feature;

use App\Models\Slug;
use App\Models\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StatApiTest extends TestCase
{
  use RefreshDatabase;

  public function test_stats_returns_data_for_public_slug(): void
  {
    $slug = Slug::factory()->create([
      'slug' => 'public-slug',
      'api_key' => null,
    ]);

    Stat::factory()->count(5)->create([
      'slug_id' => $slug->id,
      'clicked_at' => now(),
    ]);

    $response = $this->getJson('/api/stats/public-slug');

    $response->assertOk()
      ->assertJsonStructure([
        'slug',
        'original_url',
        'total_clicks',
        'clicks_per_day',
        'top_referers',
        'clicks_by_country',
      ])
      ->assertJsonPath('slug', 'public-slug')
      ->assertJsonPath('total_clicks', 5);
  }

  public function test_stats_returns_data_with_valid_api_key(): void
  {
    $slug = Slug::factory()->create([
      'slug' => 'protected-slug',
      'api_key' => Hash::make('valid-key-12345'),
    ]);

    Stat::factory()->count(3)->create(['slug_id' => $slug->id]);

    $response = $this->getJson('/api/stats/protected-slug', [
      'X-API-Key' => 'valid-key-12345',
    ]);

    $response->assertOk()
      ->assertJsonPath('total_clicks', 3);
  }

  public function test_stats_returns_403_for_protected_slug_without_key(): void
  {
    Slug::factory()->create([
      'slug' => 'secret-slug',
      'api_key' => Hash::make('secret-key-12345'),
    ]);

    $response = $this->getJson('/api/stats/secret-slug');

    $response->assertStatus(403);
  }

  public function test_stats_returns_403_for_protected_slug_with_wrong_key(): void
  {
    Slug::factory()->create([
      'slug' => 'secret-slug',
      'api_key' => Hash::make('correct-key-12345'),
    ]);

    $response = $this->getJson('/api/stats/secret-slug', [
      'X-API-Key' => 'wrong-key-123456',
    ]);

    $response->assertStatus(403);
  }

  public function test_stats_returns_404_for_nonexistent_slug(): void
  {
    $response = $this->getJson('/api/stats/doesnt-exist');

    $response->assertStatus(404)
      ->assertJsonPath('message', 'Slug no encontrado.');
  }

  public function test_stats_returns_zero_clicks_for_slug_with_no_stats(): void
  {
    Slug::factory()->create([
      'slug' => 'no-clicks',
      'api_key' => null,
    ]);

    $response = $this->getJson('/api/stats/no-clicks');

    $response->assertOk()
      ->assertJsonPath('total_clicks', 0)
      ->assertJsonPath('clicks_per_day', [])
      ->assertJsonPath('top_referers', [])
      ->assertJsonPath('clicks_by_country', []);
  }

  public function test_stats_clicks_per_day_structure(): void
  {
    $slug = Slug::factory()->create([
      'slug' => 'daily-slug',
      'api_key' => null,
    ]);

    Stat::factory()->create([
      'slug_id' => $slug->id,
      'clicked_at' => now(),
    ]);

    $response = $this->getJson('/api/stats/daily-slug');

    $response->assertOk();
    $clicksPerDay = $response->json('clicks_per_day');
    if (!empty($clicksPerDay)) {
      $this->assertArrayHasKey('date', $clicksPerDay[0]);
      $this->assertArrayHasKey('clicks', $clicksPerDay[0]);
    }
  }

  public function test_stats_top_referers_structure(): void
  {
    $slug = Slug::factory()->create([
      'slug' => 'referer-slug',
      'api_key' => null,
    ]);

    Stat::factory()->create([
      'slug_id' => $slug->id,
      'referer_host' => 'google.com',
      'clicked_at' => now(),
    ]);

    $response = $this->getJson('/api/stats/referer-slug');

    $response->assertOk();
    $referers = $response->json('top_referers');
    if (!empty($referers)) {
      $this->assertArrayHasKey('referer', $referers[0]);
      $this->assertArrayHasKey('clicks', $referers[0]);
    }
  }

  public function test_stats_clicks_by_country_structure(): void
  {
    $slug = Slug::factory()->create([
      'slug' => 'country-slug',
      'api_key' => null,
    ]);

    Stat::factory()->create([
      'slug_id' => $slug->id,
      'country' => 'Chile',
      'clicked_at' => now(),
    ]);

    $response = $this->getJson('/api/stats/country-slug');

    $response->assertOk();
    $countries = $response->json('clicks_by_country');
    if (!empty($countries)) {
      $this->assertArrayHasKey('country', $countries[0]);
      $this->assertArrayHasKey('clicks', $countries[0]);
    }
  }
}
