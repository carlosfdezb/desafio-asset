<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardTest extends TestCase
{
  public function test_dashboard_returns_view(): void
  {
    $response = $this->get('/dashboard');

    $response->assertOk()
      ->assertViewIs('dashboard');
  }
}
