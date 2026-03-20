<?php

namespace App\Services;

use App\Jobs\RecordStatJob;
use App\Models\Slug;
use App\Repositories\Contracts\SlugRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SlugService
{
  public function __construct(
    private SlugRepositoryInterface $slugRepository
  ) {}

  public function list(int $perPage = 15): LengthAwarePaginator
  {
    return $this->slugRepository->paginate($perPage);
  }

  public function redirect(string $slug, ?string $referer = null, ?string $ip = null): Slug
  {
    $slugModel = $this->slugRepository->findBySlug($slug);

    if (!$slugModel) {
      throw new HttpException(404, 'Slug no encontrado.');
    }

    if ($slugModel->isExpired()) {
      throw new HttpException(410, 'Este enlace ha expirado.');
    }

    RecordStatJob::dispatch(
      $slugModel->id,
      $referer,
      $ip,
      now()
    );

    return $slugModel;
  }

  public function createSlug(array $data): Slug
  {
    if (!empty($data['custom_slug'])) {
      return $this->createWithCustomSlug($data);
    }

    return $this->createWithRandomSlug($data);
  }

  private function createWithCustomSlug(array $data): Slug
  {
    $baseSlug = Str::slug($data['custom_slug']);
    $lockKey = 'slug:create:' . $baseSlug;

    return Cache::lock($lockKey, 5)->block(3, function () use ($data, $baseSlug) {
      $slugValue = $this->generateCustomSlug($baseSlug);

      $slug = new Slug();
      $slug->original_url = $data['url'];
      $slug->slug = $slugValue;
      $slug->api_key = !empty($data['api_key']) ? Hash::make($data['api_key']) : null;
      $slug->expires_at = $data['expires_at'] ?? null;

      return $this->slugRepository->save($slug);
    });
  }

  private function createWithRandomSlug(array $data): Slug
  {
    $slugValue = $this->generateRandomSlug();

    $slug = new Slug();
    $slug->original_url = $data['url'];
    $slug->slug = $slugValue;
    $slug->api_key = !empty($data['api_key']) ? Hash::make($data['api_key']) : null;
    $slug->expires_at = $data['expires_at'] ?? null;
    return $this->slugRepository->save($slug);
  }

  private function generateCustomSlug(string $customSlug): string
  {
    $baseSlug = Str::slug($customSlug);

    $existingSlugs = $this->slugRepository->findSimilarSlugs($baseSlug);

    // primer intento: no está usado el slug base
    if ($existingSlugs->isEmpty()) {
      return $baseSlug;
    }

    // segundo intento: usar el slug base limpio
    if (!$existingSlugs->contains($baseSlug)) {
      return $baseSlug;
    }

    $maxSuffix = 0;

    // tercer intento: buscar el siguiente slug disponible con formato base-slug-{número}
    foreach ($existingSlugs as $existingSlug) {
      if (preg_match('/^' . preg_quote($baseSlug, '/') . '-(\d+)$/', $existingSlug, $matches)) {
        $suffix = (int) $matches[1];
        $maxSuffix = max($maxSuffix, $suffix);
      }
    }

    return $baseSlug . '-' . ($maxSuffix + 1);
  }

  private function generateRandomSlug(int $length = 8, int $maxAttempts = 5): string
  {
    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
      $slug = strtolower(Str::random($length));

      if (!$this->slugRepository->findBySlug($slug)) {
        return $slug;
      }
    }
    throw new HttpException(500, 'No fue posible generar un slug único.');
  }

  public function deleteSlug(string $slug, ?string $apiKey): void
  {
    $slugModel = $this->slugRepository->findBySlug($slug);

    if (! $slugModel) {
      throw new HttpException(404, 'Slug no encontrado.');
    }

    if (is_null($slugModel->api_key)) {
      throw new HttpException(403, 'Este slug no puede ser eliminado, ya que se creó sin API key.');
    }

    if (empty($apiKey)) {
      throw new HttpException(403, 'Se requiere una API key para eliminar este slug.');
    }

    if (!Hash::check($apiKey, $slugModel->api_key)) {
      throw new HttpException(403, 'API key inválida.');
    }

    $this->slugRepository->delete($slugModel);
  }
}
