<?php

namespace App\Repositories;

use App\Models\Slug;
use App\Repositories\Contracts\SlugRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SlugRepository implements SlugRepositoryInterface
{
  public function paginate(int $perPage = 15): LengthAwarePaginator
  {
    return Slug::latest()->paginate($perPage);
  }

  public function findBySlug(string $slug): ?Slug
  {
    return Slug::where('slug', $slug)->first();
  }

  public function findSimilarSlugs(string $baseSlug): Collection
  {
    return Slug::withTrashed()
      ->where(function ($query) use ($baseSlug) {
        $query->where('slug', $baseSlug)
          ->orWhere('slug', 'like', $baseSlug . '-%');
      })
      ->pluck('slug');
  }

  public function save(Slug $slug): Slug
  {
    $slug->save();
    return $slug;
  }

  public function delete(Slug $slug): void
  {
    $slug->delete();
  }
}
