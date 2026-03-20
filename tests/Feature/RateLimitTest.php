<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
  use RefreshDatabase;

  public function test_stats_route_is_rate_limited(): void
  {
    for ($i = 0; $i < 60; $i++) {
      $response = $this->getJson('/api/stats/test-slug');
      $response->assertStatus($response->status()); // cualquier status, mientras no sea 429
      $this->assertNotEquals(429, $response->status(), "Request $i fue limitada antes de tiempo");
    }

    $response = $this->getJson('/api/stats/test-slug');
    $response->assertStatus(429);
  }

  public function test_stats_route_returns_rate_limit_headers(): void
  {
    $response = $this->getJson('/api/stats/test-slug');

    $response->assertHeader('X-RateLimit-Limit', 60);
    $response->assertHeader('X-RateLimit-Remaining', 59);
  }

  public function test_non_throttled_route_is_not_rate_limited(): void
  {
    for ($i = 0; $i < 65; $i++) {
      $response = $this->getJson('/api/health');
    }

    $response->assertStatus(200);
  }
}
