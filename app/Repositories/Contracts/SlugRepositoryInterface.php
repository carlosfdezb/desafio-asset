<?php

namespace App\Repositories\Contracts;

use App\Models\Slug;
use Illuminate\Support\Collection;

interface SlugRepositoryInterface
{
  public function findBySlug(string $slug): ?Slug;

  public function findSimilarSlugs(string $baseSlug): Collection;

  public function save(Slug $slug): Slug;

  public function delete(Slug $slug): void;
}
