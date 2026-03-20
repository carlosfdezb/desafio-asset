<?php

namespace Tests\Feature;

use App\Models\Slug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Tests\TestCase;

class QrApiTest extends TestCase
{
  use RefreshDatabase;

  public function test_generate_returns_svg_for_existing_slug(): void
  {
    Slug::factory()->create(['slug' => 'qr-test']);

    $response = $this->get('/api/qr/qr-test');

    $response->assertOk()
      ->assertHeader('Content-Type', 'image/svg+xml');
  }

  public function test_generate_returns_404_for_nonexistent_slug(): void
  {
    $response = $this->getJson('/api/qr/nonexistent');

    $response->assertStatus(404)
      ->assertJsonPath('message', 'Slug no encontrado.');
  }

  public function test_generate_returns_valid_svg_content(): void
  {
    Slug::factory()->create(['slug' => 'svg-test']);

    $response = $this->get('/api/qr/svg-test');

    $response->assertOk();
    $this->assertStringContainsString('<svg', $response->getContent());
  }
}
