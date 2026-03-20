<?php

namespace Tests\Unit;

use App\Jobs\RecordStatJob;
use App\Models\Slug;
use App\Models\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Torann\GeoIP\Facades\GeoIP;

class RecordStatJobTest extends TestCase
{
  use RefreshDatabase;

  public function test_job_creates_stat_record(): void
  {
    GeoIP::shouldReceive('getLocation')
      ->once()
      ->andReturn((object) ['country' => 'Chile']);

    $slug = Slug::factory()->create();
    $clickedAt = now()->toDateTimeString();

    $job = new RecordStatJob(
      slugId: $slug->id,
      referer: 'https://google.com/search?q=test',
      ip: '8.8.8.8',
      clickedAt: $clickedAt
    );

    $job->handle(app(\App\Repositories\Contracts\StatRepositoryInterface::class));

    $this->assertDatabaseHas('stats', [
      'slug_id' => $slug->id,
      'referer_host' => 'google.com',
      'country' => 'Chile',
    ]);
  }

  public function test_job_stores_null_referer_host_when_no_referer(): void
  {
    GeoIP::shouldReceive('getLocation')
      ->once()
      ->andReturn((object) ['country' => 'Argentina']);

    $slug = Slug::factory()->create();

    $job = new RecordStatJob(
      slugId: $slug->id,
      referer: null,
      ip: '8.8.8.8',
      clickedAt: now()->toDateTimeString()
    );

    $job->handle(app(\App\Repositories\Contracts\StatRepositoryInterface::class));

    $stat = Stat::where('slug_id', $slug->id)->first();
    $this->assertNull($stat->referer_host);
  }

  public function test_job_extracts_host_from_referer_url(): void
  {
    GeoIP::shouldReceive('getLocation')
      ->once()
      ->andReturn((object) ['country' => 'Chile']);

    $slug = Slug::factory()->create();

    $job = new RecordStatJob(
      slugId: $slug->id,
      referer: 'https://www.twitter.com/some/path?param=value',
      ip: '8.8.8.8',
      clickedAt: now()->toDateTimeString()
    );

    $job->handle(app(\App\Repositories\Contracts\StatRepositoryInterface::class));

    $stat = Stat::where('slug_id', $slug->id)->first();
    $this->assertEquals('www.twitter.com', $stat->referer_host);
  }

  public function test_job_handles_unknown_country(): void
  {
    GeoIP::shouldReceive('getLocation')
      ->once()
      ->andReturn((object) ['country' => null]);

    $slug = Slug::factory()->create();

    $job = new RecordStatJob(
      slugId: $slug->id,
      referer: null,
      ip: '8.8.8.8',
      clickedAt: now()->toDateTimeString()
    );

    $job->handle(app(\App\Repositories\Contracts\StatRepositoryInterface::class));

    $stat = Stat::where('slug_id', $slug->id)->first();
    $this->assertEquals('Desconocido', $stat->country);
  }
}
