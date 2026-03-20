<?php

namespace Tests\Feature;

use App\Jobs\RecordStatJob;
use App\Models\Slug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RedirectTest extends TestCase
{
  use RefreshDatabase;

  public function test_redirect_to_original_url(): void
  {
    Queue::fake();

    Slug::factory()->create([
      'slug' => 'go',
      'original_url' => 'https://example.com/page',
    ]);

    $response = $this->get('/go');

    $response->assertRedirect('https://example.com/page');
  }

  public function test_redirect_dispatches_record_stat_job(): void
  {
    Queue::fake();

    $slug = Slug::factory()->create([
      'slug' => 'tracked',
      'original_url' => 'https://example.com',
    ]);

    $this->get('/tracked');

    Queue::assertPushed(RecordStatJob::class, function ($job) use ($slug) {
      return $job->slugId === $slug->id;
    });
  }

  public function test_redirect_returns_404_for_nonexistent_slug(): void
  {
    $response = $this->get('/nonexistent-slug-xyz');

    $response->assertStatus(404);
  }

  public function test_redirect_returns_410_for_expired_slug(): void
  {
    Slug::factory()->create([
      'slug' => 'expired',
      'original_url' => 'https://example.com',
      'expires_at' => now()->subDay(),
    ]);

    $response = $this->get('/expired');

    $response->assertStatus(410);
  }

  public function test_redirect_works_for_non_expired_slug(): void
  {
    Queue::fake();

    Slug::factory()->create([
      'slug' => 'valid',
      'original_url' => 'https://example.com',
      'expires_at' => now()->addDay(),
    ]);

    $response = $this->get('/valid');

    $response->assertRedirect('https://example.com');
  }

  public function test_redirect_works_for_slug_without_expiration(): void
  {
    Queue::fake();

    Slug::factory()->create([
      'slug' => 'forever',
      'original_url' => 'https://example.com',
      'expires_at' => null,
    ]);

    $response = $this->get('/forever');

    $response->assertRedirect('https://example.com');
  }
}
