<?php

namespace App\Repositories\Contracts;

use App\Models\Stat;
use Illuminate\Support\Collection;

interface StatRepositoryInterface
{
  public function save(Stat $stat): void;

  public function countBySlugId(int $slugId): int;

  public function getClicksPerDay(int $slugId): Collection;

  public function getTopReferers(int $slugId): Collection;

  public function getClicksByCountry(int $slugId, int $limit = 10): Collection;
}
