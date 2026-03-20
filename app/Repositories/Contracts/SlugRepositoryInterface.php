<?php

namespace App\Repositories\Contracts;

use App\Models\Slug;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SlugRepositoryInterface
{
  public function paginate(int $perPage = 15): LengthAwarePaginator;

  public function findBySlug(string $slug): ?Slug;

  public function findSimilarSlugs(string $baseSlug): Collection;

  public function save(Slug $slug): Slug;

  public function delete(Slug $slug): void;
}
