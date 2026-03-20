<?php

namespace App\Http\Controllers;

use App\Services\QrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class QrController extends Controller
{
  public function __construct(
    private QrService $qrService
  ) {}

  public function generate(string $slug): JsonResponse | Response
  {
    try {
      $qrCode = $this->qrService->generateQr($slug);

      return response($qrCode, 200, [
        'Content-Type' => 'image/svg+xml',
      ]);
    } catch (HttpException $e) {
      return response()->json([
        'message' => $e->getMessage(),
      ], 404);
    }
  }
}
