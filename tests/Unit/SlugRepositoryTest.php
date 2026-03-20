<?php

namespace Tests\Unit;

use App\Models\Slug;
use App\Repositories\SlugRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugRepositoryTest extends TestCase
{
  use RefreshDatabase;

  private SlugRepository $repository;

  protected function setUp(): void
  {
    parent::setUp();
    $this->repository = new SlugRepository();
  }

  public function test_paginate_returns_paginated_slugs(): void
  {
    Slug::factory()->count(20)->create();

    $result = $this->repository->paginate(10);

    $this->assertCount(10, $result->items());
    $this->assertEquals(20, $result->total());
  }

  public function test_find_by_slug_returns_slug_model(): void
  {
    $slug = Slug::factory()->create(['slug' => 'find-me']);

    $result = $this->repository->findBySlug('find-me');

    $this->assertNotNull($result);
    $this->assertEquals($slug->id, $result->id);
  }

  public function test_find_by_slug_returns_null_when_not_found(): void
  {
    $result = $this->repository->findBySlug('not-here');

    $this->assertNull($result);
  }

  public function test_find_by_slug_does_not_find_soft_deleted(): void
  {
    $slug = Slug::factory()->create(['slug' => 'deleted-slug']);
    $slug->delete();

    $result = $this->repository->findBySlug('deleted-slug');

    $this->assertNull($result);
  }

  public function test_find_similar_slugs_returns_matching_slugs(): void
  {
    Slug::factory()->create(['slug' => 'base']);
    Slug::factory()->create(['slug' => 'base-1']);
    Slug::factory()->create(['slug' => 'base-2']);
    Slug::factory()->create(['slug' => 'other']);

    $result = $this->repository->findSimilarSlugs('base');

    $this->assertCount(3, $result);
    $this->assertTrue($result->contains('base'));
    $this->assertTrue($result->contains('base-1'));
    $this->assertTrue($result->contains('base-2'));
    $this->assertFalse($result->contains('other'));
  }

  public function test_find_similar_slugs_includes_soft_deleted(): void
  {
    $slug = Slug::factory()->create(['slug' => 'gone']);
    $slug->delete();
    Slug::factory()->create(['slug' => 'gone-1']);

    $result = $this->repository->findSimilarSlugs('gone');

    $this->assertCount(2, $result);
  }

  public function test_save_persists_slug(): void
  {
    $slug = new Slug();
    $slug->original_url = 'https://example.com';
    $slug->slug = 'saved-slug';

    $result = $this->repository->save($slug);

    $this->assertTrue($result->exists);
    $this->assertDatabaseHas('slugs', ['slug' => 'saved-slug']);
  }

  public function test_delete_soft_deletes_slug(): void
  {
    $slug = Slug::factory()->create(['slug' => 'to-delete']);

    $this->repository->delete($slug);

    $this->assertSoftDeleted('slugs', ['slug' => 'to-delete']);
  }
}
