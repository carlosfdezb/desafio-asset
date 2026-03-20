<?php

namespace Tests\Unit;

use App\Models\Slug;
use App\Models\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugModelTest extends TestCase
{
  use RefreshDatabase;

  public function test_is_expired_returns_true_for_past_date(): void
  {
    $slug = Slug::factory()->create([
      'expires_at' => now()->subDay(),
    ]);

    $this->assertTrue($slug->isExpired());
  }

  public function test_is_expired_returns_false_for_future_date(): void
  {
    $slug = Slug::factory()->create([
      'expires_at' => now()->addDay(),
    ]);

    $this->assertFalse($slug->isExpired());
  }

  public function test_is_expired_returns_false_when_null(): void
  {
    $slug = Slug::factory()->create([
      'expires_at' => null,
    ]);

    $this->assertFalse($slug->isExpired());
  }

  public function test_stats_relationship(): void
  {
    $slug = Slug::factory()->create();
    Stat::factory()->count(3)->create(['slug_id' => $slug->id]);

    $this->assertCount(3, $slug->stats);
    $this->assertInstanceOf(Stat::class, $slug->stats->first());
  }

  public function test_api_key_is_hidden_in_serialization(): void
  {
    $slug = Slug::factory()->create();

    $array = $slug->toArray();

    $this->assertArrayNotHasKey('api_key', $array);
  }

  public function test_soft_delete(): void
  {
    $slug = Slug::factory()->create();

    $slug->delete();

    $this->assertSoftDeleted('slugs', ['id' => $slug->id]);
    $this->assertNull(Slug::find($slug->id));
    $this->assertNotNull(Slug::withTrashed()->find($slug->id));
  }

  public function test_expires_at_is_cast_to_datetime(): void
  {
    $slug = Slug::factory()->create([
      'expires_at' => '2026-12-31 23:59:59',
    ]);

    $slug->refresh();

    $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $slug->expires_at);
  }
}
