<?php

namespace App\Providers;

use App\Repositories\Contracts\StatRepositoryInterface;
use App\Repositories\Contracts\SlugRepositoryInterface;
use App\Repositories\StatRepository;
use App\Repositories\SlugRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
    RateLimiter::for('api', function (Request $request) {
      return Limit::perMinute(60)->by($request->ip());
    });
  }
}
