<?php

namespace App\Services;

use App\Repositories\Contracts\SlugRepositoryInterface;
use App\Repositories\Contracts\StatRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StatService
{
  public function __construct(
    private StatRepositoryInterface $statRepository,
    private SlugRepositoryInterface $slugRepository
  ) {}

  public function getStatsBySlug(string $slug, ?string $apiKey = null): array
  {
    $slug = $this->slugRepository->findBySlug($slug);

    if (!$slug) {
      throw new HttpException(404, 'Slug no encontrado.');
    }

    if (!is_null($slug->api_key)) {
      if (empty($apiKey) || ! Hash::check($apiKey, $slug->api_key)) {
        throw new HttpException(403, 'La API key enviada no es válida.');
      }
    }

    return [
      'slug' => $slug->slug,
      'original_url' => $slug->original_url,
      'total_clicks' => $this->statRepository->countBySlugId($slug->id),
      'clicks_per_day' => $this->statRepository
        ->getClicksPerDay($slug->id)
        ->map(fn($item) => [
          'date' => $item->date,
          'clicks' => (int) $item->clicks,
        ])
        ->values(),
      'top_referers' => $this->statRepository
        ->getTopReferers($slug->id)
        ->map(fn($item) => [
          'referer' => $item->referer,
          'clicks' => (int) $item->clicks,
        ])
        ->values(),
      'clicks_by_country' => $this->statRepository
        ->getClicksByCountry($slug->id)
        ->map(fn($item) => [
          'country' => $item->country,
          'clicks' => (int) $item->clicks,
        ])
        ->values(),
    ];
  }
}
