<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatsRequest;
use App\Services\StatService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StatController extends Controller
{
  public function __construct(
    private StatService $statService
  ) {}

  public function stats(StatsRequest $request, string $slug)
  {
    try {
      $stats = $this->statService->getStatsBySlug(
        slug: $slug,
        apiKey: $request->validated('api_key')
      );

      return response()->json($stats);
    } catch (HttpException $e) {
      return response()->json([
        'message' => $e->getMessage(),
      ], $e->getStatusCode());
    }
  }
}
