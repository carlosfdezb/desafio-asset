<?php

namespace Tests\Unit;

use App\Models\Slug;
use App\Services\QrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class QrServiceTest extends TestCase
{
  use RefreshDatabase;

  private QrService $qrService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->qrService = app(QrService::class);
  }

  public function test_generate_qr_returns_svg_string(): void
  {
    Slug::factory()->create(['slug' => 'qr-test']);

    $result = $this->qrService->generateQr('qr-test');

    $this->assertIsString($result);
    $this->assertStringContainsString('<svg', $result);
  }

  public function test_generate_qr_throws_404_for_missing_slug(): void
  {
    $this->expectException(HttpException::class);

    $this->qrService->generateQr('nonexistent');
  }
}
