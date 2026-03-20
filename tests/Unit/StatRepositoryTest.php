<?php

namespace Tests\Unit;

use App\Models\Slug;
use App\Models\Stat;
use App\Repositories\StatRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatRepositoryTest extends TestCase
{
  use RefreshDatabase;

  private StatRepository $repository;

  protected function setUp(): void
  {
    parent::setUp();
    $this->repository = new StatRepository();
  }

  public function test_save_persists_stat(): void
  {
    $slug = Slug::factory()->create();

    $stat = new Stat();
    $stat->slug_id = $slug->id;
    $stat->referer_host = 'google.com';
    $stat->country = 'Chile';
    $stat->clicked_at = now();

    $this->repository->save($stat);

    $this->assertDatabaseHas('stats', [
      'slug_id' => $slug->id,
      'referer_host' => 'google.com',
      'country' => 'Chile',
    ]);
  }

  public function test_count_by_slug_id(): void
  {
    $slug = Slug::factory()->create();
    Stat::factory()->count(5)->create(['slug_id' => $slug->id]);

    $otherSlug = Slug::factory()->create();
    Stat::factory()->count(3)->create(['slug_id' => $otherSlug->id]);

    $this->assertEquals(5, $this->repository->countBySlugId($slug->id));
    $this->assertEquals(3, $this->repository->countBySlugId($otherSlug->id));
  }

  public function test_count_by_slug_id_returns_zero_when_no_stats(): void
  {
    $slug = Slug::factory()->create();

    $this->assertEquals(0, $this->repository->countBySlugId($slug->id));
  }

  public function test_get_clicks_per_day_returns_recent_data(): void
  {
    $slug = Slug::factory()->create();

    Stat::factory()->create([
      'slug_id' => $slug->id,
      'clicked_at' => now(),
    ]);

    Stat::factory()->create([
      'slug_id' => $slug->id,
      'clicked_at' => now()->subDay(),
    ]);

    // Old stat beyond 6 days
    Stat::factory()->create([
      'slug_id' => $slug->id,
      'clicked_at' => now()->subDays(10),
    ]);

    $result = $this->repository->getClicksPerDay($slug->id);

    // Should only have entries from last 7 days
    $this->assertLessThanOrEqual(7, $result->count());
    $this->assertGreaterThanOrEqual(1, $result->count());
  }

  public function test_get_top_referers_returns_correct_data(): void
  {
    $slug = Slug::factory()->create();

    Stat::factory()->count(3)->create([
      'slug_id' => $slug->id,
      'referer_host' => 'google.com',
      'clicked_at' => now(),
    ]);

    Stat::factory()->count(2)->create([
      'slug_id' => $slug->id,
      'referer_host' => 'twitter.com',
      'clicked_at' => now(),
    ]);

    $result = $this->repository->getTopReferers($slug->id);

    $this->assertCount(2, $result);
    $this->assertEquals('google.com', $result->first()->referer);
    $this->assertEquals(3, $result->first()->clicks);
  }

  public function test_get_top_referers_excludes_null_referers(): void
  {
    $slug = Slug::factory()->create();

    Stat::factory()->create([
      'slug_id' => $slug->id,
      'referer_host' => null,
      'clicked_at' => now(),
    ]);

    Stat::factory()->create([
      'slug_id' => $slug->id,
      'referer_host' => '',
      'clicked_at' => now(),
    ]);

    Stat::factory()->create([
      'slug_id' => $slug->id,
      'referer_host' => 'valid.com',
      'clicked_at' => now(),
    ]);

    $result = $this->repository->getTopReferers($slug->id);

    $this->assertCount(1, $result);
    $this->assertEquals('valid.com', $result->first()->referer);
  }

  public function test_get_top_referers_limits_to_5(): void
  {
    $slug = Slug::factory()->create();
    $domains = ['a.com', 'b.com', 'c.com', 'd.com', 'e.com', 'f.com', 'g.com'];

    foreach ($domains as $domain) {
      Stat::factory()->create([
        'slug_id' => $slug->id,
        'referer_host' => $domain,
        'clicked_at' => now(),
      ]);
    }

    $result = $this->repository->getTopReferers($slug->id);

    $this->assertCount(5, $result);
  }

  public function test_get_clicks_by_country_returns_correct_data(): void
  {
    $slug = Slug::factory()->create();

    Stat::factory()->count(4)->create([
      'slug_id' => $slug->id,
      'country' => 'Chile',
      'clicked_at' => now(),
    ]);

    Stat::factory()->count(2)->create([
      'slug_id' => $slug->id,
      'country' => 'Argentina',
      'clicked_at' => now(),
    ]);

    $result = $this->repository->getClicksByCountry($slug->id);

    $this->assertCount(2, $result);
    $this->assertEquals('Chile', $result->first()->country);
    $this->assertEquals(4, $result->first()->clicks);
  }

  public function test_get_clicks_by_country_excludes_null_countries(): void
  {
    $slug = Slug::factory()->create();

    Stat::factory()->create([
      'slug_id' => $slug->id,
      'country' => null,
      'clicked_at' => now(),
    ]);

    Stat::factory()->create([
      'slug_id' => $slug->id,
      'country' => 'Chile',
      'clicked_at' => now(),
    ]);

    $result = $this->repository->getClicksByCountry($slug->id);

    $this->assertCount(1, $result);
  }

  public function test_get_clicks_by_country_respects_limit(): void
  {
    $slug = Slug::factory()->create();

    for ($i = 0; $i < 15; $i++) {
      Stat::factory()->create([
        'slug_id' => $slug->id,
        'country' => "Country $i",
        'clicked_at' => now(),
      ]);
    }

    $result = $this->repository->getClicksByCountry($slug->id, 5);

    $this->assertCount(5, $result);
  }
}
