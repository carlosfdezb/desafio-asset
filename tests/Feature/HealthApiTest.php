<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthApiTest extends TestCase
{
  use RefreshDatabase;

  public function test_health_returns_ok_status(): void
  {
    $response = $this->getJson('/api/health');

    $response->assertOk()
      ->assertJsonStructure(['status', 'database', 'timestamp'])
      ->assertJsonPath('status', 'ok')
      ->assertJsonPath('database', 'connected');
  }
}
