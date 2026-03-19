<?php

return [

  'service' => 'ipapi',

  'cache' => false,
  'cache_tags' => null,

  'services' => [

    'ipapi' => [
      'class' => \Torann\GeoIP\Services\IPApi::class,
      'secure' => true,
      'key' => null,
    ],

    'maxmind_database' => [
      'class' => \Torann\GeoIP\Services\MaxMindDatabase::class,
      'database_path' => storage_path('app/geoip.mmdb'),
      'locales' => ['en'],
    ],

  ],

];
