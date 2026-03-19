<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class HealthController extends Controller
{
  public function __invoke(): JsonResponse
  {
    try {
      DB::connection()->getPdo();

      $database = 'connected';
      $status = 'ok';
    } catch (Exception $e) {
      $database = 'disconnected';
      $status = 'error';
    }

    return response()->json([
      'status' => $status,
      'database' => $database,
      'timestamp' => now(),
    ]);
  }
}
