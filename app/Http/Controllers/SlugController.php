<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\SlugDeleteRequest;
use App\Services\SlugService;
use App\Http\Requests\SlugShortenRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class SlugController extends Controller
{
  public function __construct(
    private SlugService $slugService
  ) {}

  public function shorten(SlugShortenRequest $request): JsonResponse
  {
    try {
      $slug = $this->slugService->createSlug($request->validated());
      return response()->json([
        'short_url' => url('/' . $slug->slug),
        'slug' => $slug->slug,
        'original_url' => $slug->original_url,
      ]);
    } catch (RuntimeException $e) {
      return response()->json([
        'message' => $e->getMessage(),
      ], 404);
    }
  }

  public function delete(SlugDeleteRequest $request, string $slug): JsonResponse
  {
    try {
      $this->slugService->deleteSlug($slug, $request->input('api_key'));
      return response()->json(['message' => 'Slug eliminado correctamente.']);
    } catch (ModelNotFoundException $e) {
      return response()->json([
        'message' => $e->getMessage(),
      ], 404);
    } catch (InvalidArgumentException $e) {
      return response()->json([
        'message' => $e->getMessage(),
      ], 400);
    }
  }

  public function redirect(Request $request)
  {
    try {
      $slug = $this->slugService->redirect(
        slug: $request->route('slug'),
        referer: $request->header('referer'),
        ip: $request->ip()
      );
      return redirect()->away($slug->original_url);
    } catch (ModelNotFoundException $e) {
      return response()->json([
        'message' => $e->getMessage(),
      ], 404);
    }
  }
}
