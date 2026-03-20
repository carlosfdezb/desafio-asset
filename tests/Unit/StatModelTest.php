<?php

namespace Tests\Unit;

use App\Models\Slug;
use App\Models\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatModelTest extends TestCase
{
  use RefreshDatabase;

  public function test_slug_relationship(): void
  {
    $slug = Slug::factory()->create();
    $stat = Stat::factory()->create(['slug_id' => $slug->id]);

    $this->assertInstanceOf(Slug::class, $stat->slug);
    $this->assertEquals($slug->id, $stat->slug->id);
  }

  public function test_clicked_at_is_cast_to_datetime(): void
  {
    $stat = Stat::factory()->create([
      'clicked_at' => '2026-03-19 12:00:00',
    ]);

    $stat->refresh();

    $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $stat->clicked_at);
  }

  public function test_fillable_fields(): void
  {
    $slug = Slug::factory()->create();

    $stat = Stat::create([
      'slug_id' => $slug->id,
      'referer_host' => 'test.com',
      'country' => 'Chile',
      'clicked_at' => now(),
    ]);

    $this->assertEquals($slug->id, $stat->slug_id);
    $this->assertEquals('test.com', $stat->referer_host);
    $this->assertEquals('Chile', $stat->country);
  }

  public function test_nullable_fields(): void
  {
    $slug = Slug::factory()->create();

    $stat = Stat::create([
      'slug_id' => $slug->id,
      'referer_host' => null,
      'country' => null,
      'clicked_at' => now(),
    ]);

    $this->assertNull($stat->referer_host);
    $this->assertNull($stat->country);
  }
}
