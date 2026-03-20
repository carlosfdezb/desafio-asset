<?php

namespace App\Services;

use App\Repositories\Contracts\SlugRepositoryInterface;
use RuntimeException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpKernel\Exception\HttpException;

class QrService
{
  public function __construct(
    private SlugRepositoryInterface $slugRepository
  ) {}

  public function generateQr(string $slug): string
  {
    $slugModel = $this->slugRepository->findBySlug($slug);

    if (!$slugModel) {
      throw new HttpException(404, 'Slug no encontrado.');
    }

    $shortUrl = url('/' . $slugModel->slug);

    return QrCode::format('svg')
      ->size(300)
      ->generate($shortUrl);
  }
}
