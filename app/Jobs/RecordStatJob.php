<?php

namespace App\Jobs;

use App\Models\Stat;
use App\Repositories\Contracts\StatRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Torann\GeoIP\Facades\GeoIP;

class RecordStatJob implements ShouldQueue
{
  use Queueable;

  private const LOCAL_TEST_IP_POOL = [
    '8.8.8.8',
    '1.1.1.1',
    '200.6.99.2',
    '181.30.0.1',
    '189.203.240.1',
    '52.67.0.1',
    '83.44.196.93',
    '81.2.69.142',
  ];

  public function __construct(
    public int $slugId,
    public ?string $referer,
    public ?string $ip,
    public string $clickedAt
  ) {}

  public function handle(
    StatRepositoryInterface $statRepository
  ): void {
    $ip = $this->resolveIpForGeoLookup($this->ip);
    $location = GeoIP::getLocation($ip);
    $country = $location->country ?? 'Desconocido';

    $stat = new Stat();
    $stat->slug_id = $this->slugId;
    $stat->referer_host = $this->referer ? parse_url($this->referer, PHP_URL_HOST) : null;
    $stat->country = $country;
    $stat->clicked_at = $this->clickedAt;

    $statRepository->save($stat);
  }

  private function resolveIpForGeoLookup(?string $ip): string
  {
    $isLocalIp = in_array($ip, ['127.0.0.1', '::1', null], true);

    if (app()->isLocal() && $isLocalIp) {
      $fakeIp = $this->getRandomTestIp();

      return $fakeIp;
    }

    return $ip;
  }

  private function getRandomTestIp(): string
  {
    return self::LOCAL_TEST_IP_POOL[array_rand(self::LOCAL_TEST_IP_POOL)];
  }
}
