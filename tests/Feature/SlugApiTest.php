<?php

namespace Tests\Feature;

use App\Models\Slug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SlugApiTest extends TestCase
{
  use RefreshDatabase;

  // ── GET /api/slugs ──────────────────────────────────────

  public function test_index_returns_paginated_slugs(): void
  {
    Slug::factory()->count(20)->create();

    $response = $this->getJson('/api/slugs');

    $response->assertOk()
      ->assertJsonStructure([
        'data' => [['id', 'original_url', 'slug', 'expires_at', 'created_at', 'updated_at']],
        'current_page',
        'last_page',
        'per_page',
        'total',
      ]);

    $this->assertCount(15, $response->json('data'));
  }

  public function test_index_respects_per_page_parameter(): void
  {
    Slug::factory()->count(10)->create();

    $response = $this->getJson('/api/slugs?per_page=5');

    $response->assertOk();
    $this->assertCount(5, $response->json('data'));
  }

  public function test_index_caps_per_page_at_100(): void
  {
    Slug::factory()->count(5)->create();

    $response = $this->getJson('/api/slugs?per_page=200');

    $response->assertOk();
    $this->assertEquals(100, $response->json('per_page'));
  }

  public function test_index_minimum_per_page_is_1(): void
  {
    Slug::factory()->count(5)->create();

    $response = $this->getJson('/api/slugs?per_page=0');

    $response->assertOk();
    $this->assertEquals(1, $response->json('per_page'));
  }

  public function test_index_returns_empty_when_no_slugs(): void
  {
    $response = $this->getJson('/api/slugs');

    $response->assertOk();
    $this->assertCount(0, $response->json('data'));
  }

  public function test_index_hides_api_key_field(): void
  {
    Slug::factory()->create();

    $response = $this->getJson('/api/slugs');

    $response->assertOk();
    $this->assertArrayNotHasKey('api_key', $response->json('data.0'));
  }

  // ── POST /api/shorten ───────────────────────────────────

  public function test_shorten_creates_slug_with_random_slug(): void
  {
    $response = $this->postJson('/api/shorten', [
      'url' => 'https://example.com',
    ]);

    $response->assertOk()
      ->assertJsonStructure(['short_url', 'slug', 'original_url', 'expires_at']);

    $this->assertDatabaseHas('slugs', [
      'original_url' => 'https://example.com',
    ]);
  }

  public function test_shorten_creates_slug_with_custom_slug(): void
  {
    $response = $this->postJson('/api/shorten', [
      'url' => 'https://example.com',
      'custom_slug' => 'my-custom',
    ]);

    $response->assertOk()
      ->assertJsonPath('slug', 'my-custom')
      ->assertJsonPath('original_url', 'https://example.com');

    $this->assertDatabaseHas('slugs', ['slug' => 'my-custom']);
  }

  public function test_shorten_custom_slug_auto_increments_on_duplicate(): void
  {
    Slug::factory()->create(['slug' => 'test-slug']);

    $response = $this->postJson('/api/shorten', [
      'url' => 'https://example.com',
      'custom_slug' => 'test-slug',
    ]);

    $response->assertOk()
      ->assertJsonPath('slug', 'test-slug-1');
  }

  public function test_shorten_with_api_key(): void
  {
    $response = $this->postJson('/api/shorten', [
      'url' => 'https://example.com',
      'api_key' => 'my-secret-key-123',
    ]);

    $response->assertOk();

    $slug = Slug::where('original_url', 'https://example.com')->first();
    $this->assertNotNull($slug->api_key);
    $this->assertTrue(Hash::check('my-secret-key-123', $slug->api_key));
  }

  public function test_shorten_with_expires_at(): void
  {
    $expiresAt = now()->addDays(7)->toDateTimeString();

    $response = $this->postJson('/api/shorten', [
      'url' => 'https://example.com',
      'expires_at' => $expiresAt,
    ]);

    $response->assertOk()
      ->assertJsonPath('original_url', 'https://example.com');
  }

  public function test_shorten_validation_requires_url(): void
  {
    $response = $this->postJson('/api/shorten', []);

    $response->assertStatus(422)
      ->assertJsonValidationErrors('url');
  }

  public function test_shorten_validation_rejects_invalid_url(): void
  {
    $response = $this->postJson('/api/shorten', [
      'url' => 'not-a-url',
    ]);

    $response->assertStatus(422)
      ->assertJsonValidationErrors('url');
  }

  public function test_shorten_validation_rejects_ftp_url(): void
  {
    $response = $this->postJson('/api/shorten', [
      'url' => 'ftp://example.com/file',
    ]);

    $response->assertStatus(422)
      ->assertJsonValidationErrors('url');
  }

  public function test_shorten_validation_rejects_short_api_key(): void
  {
    $response = $this->postJson('/api/shorten', [
      'url' => 'https://example.com',
      'api_key' => 'short',
    ]);

    $response->assertStatus(422)
      ->assertJsonValidationErrors('api_key');
  }

  public function test_shorten_validation_rejects_past_expires_at(): void
  {
    $response = $this->postJson('/api/shorten', [
      'url' => 'https://example.com',
      'expires_at' => now()->subDay()->toDateTimeString(),
    ]);

    $response->assertStatus(422)
      ->assertJsonValidationErrors('expires_at');
  }

  public function test_shorten_validation_rejects_long_custom_slug(): void
  {
    $response = $this->postJson('/api/shorten', [
      'url' => 'https://example.com',
      'custom_slug' => str_repeat('a', 101),
    ]);

    $response->assertStatus(422)
      ->assertJsonValidationErrors('custom_slug');
  }

  // ── DELETE /api/{slug} ──────────────────────────────────

  public function test_delete_slug_with_valid_api_key(): void
  {
    $slug = Slug::factory()->create([
      'slug' => 'to-delete',
      'api_key' => Hash::make('my-secret-key-123'),
    ]);

    $response = $this->deleteJson('/api/to-delete', [
      'api_key' => 'my-secret-key-123',
    ]);

    $response->assertOk()
      ->assertJsonPath('message', 'Slug eliminado correctamente.');

    $this->assertSoftDeleted('slugs', ['id' => $slug->id]);
  }

  public function test_delete_slug_returns_404_when_not_found(): void
  {
    $response = $this->deleteJson('/api/nonexistent', [
      'api_key' => 'my-secret-key-123',
    ]);

    $response->assertStatus(404)
      ->assertJsonPath('message', 'Slug no encontrado.');
  }

  public function test_delete_slug_returns_403_with_wrong_api_key(): void
  {
    Slug::factory()->create([
      'slug' => 'protected',
      'api_key' => Hash::make('correct-key-12345'),
    ]);

    $response = $this->deleteJson('/api/protected', [
      'api_key' => 'wrong-key-12345',
    ]);

    $response->assertStatus(403)
      ->assertJsonPath('message', 'API key inválida.');
  }

  public function test_delete_slug_returns_403_without_api_key_when_slug_has_key(): void
  {
    Slug::factory()->create([
      'slug' => 'protected',
      'api_key' => Hash::make('correct-key-12345'),
    ]);

    $response = $this->deleteJson('/api/protected');

    $response->assertStatus(403);
  }

  public function test_delete_slug_returns_403_when_slug_created_without_api_key(): void
  {
    Slug::factory()->create([
      'slug' => 'no-key',
      'api_key' => null,
    ]);

    $response = $this->deleteJson('/api/no-key', [
      'api_key' => 'any-key-12345',
    ]);

    $response->assertStatus(403)
      ->assertJsonPath('message', 'Este slug no puede ser eliminado, ya que se creó sin API key.');
  }

  public function test_delete_validation_rejects_short_api_key(): void
  {
    $response = $this->deleteJson('/api/some-slug', [
      'api_key' => 'short',
    ]);

    $response->assertStatus(422)
      ->assertJsonValidationErrors('api_key');
  }
}
