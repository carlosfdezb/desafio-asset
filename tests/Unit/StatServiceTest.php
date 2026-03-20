<?php

namespace Tests\Unit;

use App\Models\Slug;
use App\Models\Stat;
use App\Services\StatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StatServiceTest extends TestCase
{
  use RefreshDatabase;

  private StatService $statService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->statService = app(StatService::class);
  }

  public function test_get_stats_for_public_slug(): void
  {
    $slug = Slug::factory()->create([
      'slug' => 'public',
      'api_key' => null,
    ]);

    Stat::factory()->count(3)->create([
      'slug_id' => $slug->id,
      'clicked_at' => now(),
    ]);

    $stats = $this->statService->getStatsBySlug('public');

    $this->assertEquals('public', $stats['slug']);
    $this->assertEquals($slug->original_url, $stats['original_url']);
    $this->assertEquals(3, $stats['total_clicks']);
    $this->assertIsIterable($stats['clicks_per_day']);
    $this->assertIsIterable($stats['top_referers']);
    $this->assertIsIterable($stats['clicks_by_country']);
  }

  public function test_get_stats_for_protected_slug_with_valid_key(): void
  {
    $slug = Slug::factory()->create([
      'slug' => 'protected',
      'api_key' => Hash::make('valid-key-12345'),
    ]);

    $stats = $this->statService->getStatsBySlug('protected', 'valid-key-12345');

    $this->assertEquals('protected', $stats['slug']);
  }

  public function test_get_stats_throws_403_for_protected_slug_without_key(): void
  {
    Slug::factory()->create([
      'slug' => 'secret',
      'api_key' => Hash::make('my-secret-key-1'),
    ]);

    try {
      $this->statService->getStatsBySlug('secret');
      $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $e) {
      $this->assertEquals(403, $e->getStatusCode());
    }
  }

  public function test_get_stats_throws_403_for_protected_slug_with_wrong_key(): void
  {
    Slug::factory()->create([
      'slug' => 'secret',
      'api_key' => Hash::make('correct-key-123'),
    ]);

    try {
      $this->statService->getStatsBySlug('secret', 'wrong-key-12345');
      $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $e) {
      $this->assertEquals(403, $e->getStatusCode());
    }
  }

  public function test_get_stats_throws_404_for_nonexistent_slug(): void
  {
    $this->expectException(HttpException::class);

    $this->statService->getStatsBySlug('nope');
  }

  public function test_get_stats_returns_clicks_per_day_with_correct_format(): void
  {
    $slug = Slug::factory()->create(['api_key' => null]);

    Stat::factory()->create([
      'slug_id' => $slug->id,
      'clicked_at' => now(),
    ]);

    Stat::factory()->create([
      'slug_id' => $slug->id,
      'clicked_at' => now()->subDay(),
    ]);

    $stats = $this->statService->getStatsBySlug($slug->slug);

    foreach ($stats['clicks_per_day'] as $day) {
      $this->assertArrayHasKey('date', $day);
      $this->assertArrayHasKey('clicks', $day);
      $this->assertIsInt($day['clicks']);
    }
  }

  public function test_get_stats_returns_top_referers_capped(): void
  {
    $slug = Slug::factory()->create(['api_key' => null]);

    $domains = ['a.com', 'b.com', 'c.com', 'd.com', 'e.com', 'f.com'];
    foreach ($domains as $domain) {
      Stat::factory()->create([
        'slug_id' => $slug->id,
        'referer_host' => $domain,
        'clicked_at' => now(),
      ]);
    }

    $stats = $this->statService->getStatsBySlug($slug->slug);

    $this->assertLessThanOrEqual(5, count($stats['top_referers']));
  }
}
