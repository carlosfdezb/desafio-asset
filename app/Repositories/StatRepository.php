<?php

namespace App\Repositories;

use App\Models\Stat;
use App\Repositories\Contracts\StatRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatRepository implements StatRepositoryInterface
{
  public function save(Stat $stat): void
  {
    $stat->save();
  }

  public function countBySlugId(int $slugId): int
  {
    return Stat::where('slug_id', $slugId)->count();
  }

  public function getClicksPerDay(int $slugId): Collection
  {
    $startDate = now()->subDays(6)->toDateString();

    return Stat::query()
      ->selectRaw('DATE(clicked_at) as date, COUNT(*) as clicks')
      ->where('slug_id', $slugId)
      ->whereDate('clicked_at', '>=', $startDate)
      ->groupBy(DB::raw('DATE(clicked_at)'))
      ->orderByDesc('date')
      ->get();
  }

  public function getTopReferers(int $slugId): Collection
  {
    return Stat::query()
      ->selectRaw('referer_host as referer, COUNT(*) as clicks')
      ->where('slug_id', $slugId)
      ->whereNotNull('referer_host')
      ->where('referer_host', '!=', '')
      ->groupBy('referer_host')
      ->orderByDesc('clicks')
      ->limit(5)
      ->get();
  }

  public function getClicksByCountry(int $slugId, int $limit = 10): Collection
  {
    return Stat::query()
      ->selectRaw('country, COUNT(*) as clicks')
      ->where('slug_id', $slugId)
      ->whereNotNull('country')
      ->where('country', '!=', '')
      ->groupBy('country')
      ->orderByDesc('clicks')
      ->limit($limit)
      ->get();
  }
}
