<?php

namespace App\Providers;

use App\Repositories\Contracts\StatRepositoryInterface;
use App\Repositories\Contracts\SlugRepositoryInterface;
use App\Repositories\StatRepository;
use App\Repositories\SlugRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
  public function register(): void
  {
      $this->app->bind(SlugRepositoryInterface::class, SlugRepository::class);
      $this->app->bind(StatRepositoryInterface::class, StatRepository::class);
  }

  public function boot(): void
  {
      //
  }
}
