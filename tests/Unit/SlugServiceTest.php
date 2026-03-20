<?php

namespace Tests\Unit;

use App\Models\Slug;
use App\Repositories\Contracts\SlugRepositoryInterface;
use App\Services\SlugService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SlugServiceTest extends TestCase
{
  use RefreshDatabase;

  private SlugService $slugService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->slugService = app(SlugService::class);
  }

  // ── list ────────────────────────────────────────────────

  public function test_list_returns_paginated_results(): void
  {
    Slug::factory()->count(20)->create();

    $result = $this->slugService->list(10);

    $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    $this->assertCount(10, $result->items());
    $this->assertEquals(20, $result->total());
  }

  // ── createSlug ──────────────────────────────────────────

  public function test_create_slug_with_random_slug(): void
  {
    $slug = $this->slugService->createSlug(['url' => 'https://example.com']);

    $this->assertInstanceOf(Slug::class, $slug);
    $this->assertEquals('https://example.com', $slug->original_url);
    $this->assertEquals(8, strlen($slug->slug));
    $this->assertNull($slug->api_key);
  }

  public function test_create_slug_with_custom_slug(): void
  {
    $slug = $this->slugService->createSlug([
      'url' => 'https://example.com',
      'custom_slug' => 'my-link',
    ]);

    $this->assertEquals('my-link', $slug->slug);
  }

  public function test_create_slug_with_api_key_hashes_it(): void
  {
    $slug = $this->slugService->createSlug([
      'url' => 'https://example.com',
      'api_key' => 'test-key-12345',
    ]);

    $this->assertNotNull($slug->api_key);
    $this->assertTrue(Hash::check('test-key-12345', $slug->api_key));
  }

  public function test_create_slug_with_expires_at(): void
  {
    $expiresAt = now()->addDays(7)->toDateTimeString();

    $slug = $this->slugService->createSlug([
      'url' => 'https://example.com',
      'expires_at' => $expiresAt,
    ]);

    $this->assertNotNull($slug->expires_at);
  }

  public function test_custom_slug_increments_when_base_exists(): void
  {
    Slug::factory()->create(['slug' => 'taken']);

    $slug = $this->slugService->createSlug([
      'url' => 'https://example.com',
      'custom_slug' => 'taken',
    ]);

    $this->assertEquals('taken-1', $slug->slug);
  }

  public function test_custom_slug_increments_sequentially(): void
  {
    Slug::factory()->create(['slug' => 'taken']);
    Slug::factory()->create(['slug' => 'taken-1']);
    Slug::factory()->create(['slug' => 'taken-2']);

    $slug = $this->slugService->createSlug([
      'url' => 'https://example.com',
      'custom_slug' => 'taken',
    ]);

    $this->assertEquals('taken-3', $slug->slug);
  }

  public function test_custom_slug_is_slugified(): void
  {
    $slug = $this->slugService->createSlug([
      'url' => 'https://example.com',
      'custom_slug' => 'My Custom Slug',
    ]);

    $this->assertEquals('my-custom-slug', $slug->slug);
  }

  // ── redirect ────────────────────────────────────────────

  public function test_redirect_returns_slug_model(): void
  {
    Queue::fake();

    $slug = Slug::factory()->create([
      'slug' => 'redirect-test',
      'original_url' => 'https://example.com',
    ]);

    $result = $this->slugService->redirect('redirect-test');

    $this->assertEquals($slug->id, $result->id);
    $this->assertEquals('https://example.com', $result->original_url);
  }

  public function test_redirect_throws_404_for_missing_slug(): void
  {
    $this->expectException(HttpException::class);

    $this->slugService->redirect('does-not-exist');
  }

  public function test_redirect_throws_410_for_expired_slug(): void
  {
    Slug::factory()->create([
      'slug' => 'expired',
      'expires_at' => now()->subDay(),
    ]);

    try {
      $this->slugService->redirect('expired');
      $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $e) {
      $this->assertEquals(410, $e->getStatusCode());
    }
  }

  // ── deleteSlug ──────────────────────────────────────────

  public function test_delete_slug_with_correct_api_key(): void
  {
    $slug = Slug::factory()->create([
      'slug' => 'deletable',
      'api_key' => Hash::make('secret-key-12345'),
    ]);

    $this->slugService->deleteSlug('deletable', 'secret-key-12345');

    $this->assertSoftDeleted('slugs', ['id' => $slug->id]);
  }

  public function test_delete_slug_throws_404_for_missing_slug(): void
  {
    $this->expectException(HttpException::class);

    $this->slugService->deleteSlug('missing', 'any-key-12345');
  }

  public function test_delete_slug_throws_403_for_null_api_key_slug(): void
  {
    Slug::factory()->create([
      'slug' => 'no-key',
      'api_key' => null,
    ]);

    try {
      $this->slugService->deleteSlug('no-key', 'any-key-12345');
      $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $e) {
      $this->assertEquals(403, $e->getStatusCode());
    }
  }

  public function test_delete_slug_throws_403_without_api_key(): void
  {
    Slug::factory()->create([
      'slug' => 'protected',
      'api_key' => Hash::make('my-key-12345678'),
    ]);

    try {
      $this->slugService->deleteSlug('protected', null);
      $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $e) {
      $this->assertEquals(403, $e->getStatusCode());
    }
  }

  public function test_delete_slug_throws_403_with_wrong_api_key(): void
  {
    Slug::factory()->create([
      'slug' => 'protected',
      'api_key' => Hash::make('correct-key-1234'),
    ]);

    try {
      $this->slugService->deleteSlug('protected', 'wrong-key-12345');
      $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $e) {
      $this->assertEquals(403, $e->getStatusCode());
    }
  }
}
