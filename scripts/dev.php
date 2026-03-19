<?php

$isWindows = PHP_OS_FAMILY === 'Windows';

// script para determinar so, y no levantar pcntl en windows. Modifica comando composer run dev

if ($isWindows) {
  $command = 'npx concurrently -c "#93c5fd,#c4b5fd,#fdba74" "php artisan serve" "php artisan queue:listen --tries=1 --timeout=0" "npm run dev" --names=server,queue,vite --kill-others';
} else {
  $command = 'npx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" "php artisan serve" "php artisan queue:listen --tries=1 --timeout=0" "php artisan pail --timeout=0" "npm run dev" --names=server,queue,logs,vite --kill-others';
}

$exitCode = 0;
passthru($command, $exitCode);
exit($exitCode);
